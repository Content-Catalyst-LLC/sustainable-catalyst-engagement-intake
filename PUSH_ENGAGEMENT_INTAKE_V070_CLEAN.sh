#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

VERSION="0.7.0"
PLUGIN_SLUG="sustainable-catalyst-engagement-intake"
REPO_ARCHIVE_BASENAME="${PLUGIN_SLUG}-v${VERSION}-repo.zip"

DEFAULT_REPO_URL="git@github.com:Content-Catalyst-LLC/sustainable-catalyst-engagement-intake.git"
DEFAULT_REPO_DIR="${HOME}/Downloads/${PLUGIN_SLUG}"

REPO_URL="${SC_EI_REPO_URL:-${DEFAULT_REPO_URL}}"
REPO_DIR="${SC_EI_REPO_DIR:-${DEFAULT_REPO_DIR}}"
ZIP_PATH="${SC_EI_ZIP_PATH:-}"
SKIP_PUSH="${SC_EI_SKIP_PUSH:-0}"
SKIP_REMOTE_CHECK="${SC_EI_SKIP_REMOTE_CHECK:-0}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
WORK_DIR="$(mktemp -d "${TMPDIR:-/tmp}/sc-ei-v070.XXXXXX")"

cleanup() {
  rm -rf "${WORK_DIR}"
}

on_error() {
  local exit_code=$?
  local line_number="${1:-unknown}"
  echo >&2
  echo "ERROR: Engagement Intake v${VERSION} push workflow failed on line ${line_number}." >&2
  echo "Exit code: ${exit_code}" >&2
  echo "The local repository was left at: ${REPO_DIR}" >&2
  exit "${exit_code}"
}

trap cleanup EXIT
trap 'on_error "$LINENO"' ERR

log() {
  printf '\n%s\n' "$1"
}

die() {
  echo "ERROR: $*" >&2
  exit 1
}

require_command() {
  command -v "$1" >/dev/null 2>&1 || die "Required command not found: $1"
}

find_archive() {
  local exact_script_dir="${SCRIPT_DIR}/${REPO_ARCHIVE_BASENAME}"
  local exact_downloads="${HOME}/Downloads/${REPO_ARCHIVE_BASENAME}"
  local candidate=""
  local count=0
  local path=""

  if [[ -n "${ZIP_PATH}" ]]; then
    [[ -f "${ZIP_PATH}" ]] || die "SC_EI_ZIP_PATH does not point to a file: ${ZIP_PATH}"
    return
  fi

  if [[ -f "${exact_script_dir}" ]]; then
    ZIP_PATH="${exact_script_dir}"
    return
  fi

  if [[ -f "${exact_downloads}" ]]; then
    ZIP_PATH="${exact_downloads}"
    return
  fi

  for path in \
    "${SCRIPT_DIR}/${PLUGIN_SLUG}-v${VERSION}-repo"*.zip \
    "${HOME}/Downloads/${PLUGIN_SLUG}-v${VERSION}-repo"*.zip
  do
    [[ -f "${path}" ]] || continue
    candidate="${path}"
    count=$((count + 1))
  done

  if [[ "${count}" -eq 1 ]]; then
    ZIP_PATH="${candidate}"
    return
  fi

  if [[ "${count}" -gt 1 ]]; then
    echo "Multiple matching repository ZIP files were found:" >&2
    for path in \
      "${SCRIPT_DIR}/${PLUGIN_SLUG}-v${VERSION}-repo"*.zip \
      "${HOME}/Downloads/${PLUGIN_SLUG}-v${VERSION}-repo"*.zip
    do
      [[ -f "${path}" ]] && printf '  %s\n' "${path}" >&2
    done
    die "Keep one matching ZIP or set SC_EI_ZIP_PATH to the exact file."
  fi

  die "Could not find ${REPO_ARCHIVE_BASENAME}. Place it beside this script or in ~/Downloads, or set SC_EI_ZIP_PATH."
}

find_source_directory() {
  local path=""
  SOURCE_DIR=""

  for path in "${WORK_DIR}"/*; do
    [[ -d "${path}" ]] || continue
    if [[ -d "${path}/${PLUGIN_SLUG}" && -f "${path}/composer.json" ]]; then
      if [[ -n "${SOURCE_DIR}" ]]; then
        die "The archive contains more than one possible repository root."
      fi
      SOURCE_DIR="${path}"
    fi
  done

  [[ -n "${SOURCE_DIR}" ]] || die "The archive does not contain the expected repository structure."
}

validate_release_markers() {
  local main_file="${REPO_DIR}/${PLUGIN_SLUG}/${PLUGIN_SLUG}.php"
  local fit_schema="${REPO_DIR}/${PLUGIN_SLUG}/includes/class-sc-ei-fit-schema.php"
  local fit_repo="${REPO_DIR}/${PLUGIN_SLUG}/includes/class-sc-ei-fit-repository.php"
  local fit_admin="${REPO_DIR}/${PLUGIN_SLUG}/includes/class-sc-ei-fit-admin.php"
  local fit_view="${REPO_DIR}/${PLUGIN_SLUG}/admin/views/fit-assessment-detail.php"

  php -r '
    $file = $argv[1];
    $expected = $argv[2];
    $contents = file_get_contents($file);
    if ($contents === false || strpos($contents, $expected) === false) {
        fwrite(STDERR, "Missing release marker: {$expected}\nFile: {$file}\n");
        exit(1);
    }
  ' "${main_file}" "Version:     ${VERSION}"

  php -r '
    $file = $argv[1];
    $contents = file_get_contents($file);
    if ($contents === false || !preg_match("/SC_EI_DB_VERSION\x27,\s*\x270\.7\.0\x27/", $contents)) {
        fwrite(STDERR, "Database version marker 0.7.0 is missing.\nFile: {$file}\n");
        exit(1);
    }
  ' "${main_file}"

  php -r '
    $file = $argv[1];
    $contents = file_get_contents($file);
    if ($contents === false || !preg_match("/SC_EI_FIT_SCHEMA_VERSION\x27,\s*\x271\.0\.0\x27/", $contents)) {
        fwrite(STDERR, "Fit schema marker 1.0.0 is missing.\nFile: {$file}\n");
        exit(1);
    }
  ' "${main_file}"

  grep -Fq "calculate_score" "${fit_schema}" \
    || die "The transparent advisory score implementation is missing."

  grep -Fq "second_review_reasons" "${fit_schema}" \
    || die "The second-review trigger logic is missing."

  grep -Fq "'automatic_status_change' => false" "${fit_repo}" \
    || die "The status-neutral finalization marker is missing."

  grep -Fq "'automatic_communication' => false" "${fit_repo}" \
    || die "The communication-neutral finalization marker is missing."

  grep -Fq "'automatic_scheduling' => false" "${fit_repo}" \
    || die "The scheduling-neutral finalization marker is missing."

  if grep -Fq "SC_EI_Inquiry_Repository::update_status" "${fit_repo}" || grep -Fq "wp_mail(" "${fit_repo}"; then
    die "The fit repository contains an automatic status or mail path."
  fi

  grep -Fq "'FINALIZE ' . \$assessment_id" "${fit_admin}" \
    || die "Typed finalization confirmation is missing."

  grep -Fq "'APPLY ' . \$assessment_id" "${fit_admin}" \
    || die "Typed Review Workspace application confirmation is missing."

  grep -Fq "Human judgment only" "${fit_view}" \
    || die "The human-control interface boundary is missing."
}

run_release_tests() {
  local test_file=""
  local tests=(
    "tests/smoke.php"
    "tests/validator-fixtures.php"
    "tests/storage-fixtures.php"
    "tests/upload-environment-fixtures.php"
    "tests/scanner-fixtures.php"
    "tests/quarantine-operations.php"
    "tests/review-schema-fixtures.php"
    "tests/review-operations.php"
    "tests/communication-schema-fixtures.php"
    "tests/communication-operations.php"
    "tests/privacy-schema-fixtures.php"
    "tests/privacy-operations.php"
    "tests/fit-schema-fixtures.php"
    "tests/fit-operations.php"
    "tests/schema-mapping.php"
  )

  for test_file in "${tests[@]}"; do
    [[ -f "${REPO_DIR}/${test_file}" ]] || die "Required test file is missing: ${test_file}"
    echo "  php ${test_file}"
    php "${REPO_DIR}/${test_file}"
  done
}

log "Checking required commands..."
for command_name in bash git unzip rsync php node grep find mktemp; do
  require_command "${command_name}"
done

find_archive
echo "Repository ZIP: ${ZIP_PATH}"
echo "Target repository: ${REPO_DIR}"
echo "Remote: ${REPO_URL}"

if [[ "${SKIP_REMOTE_CHECK}" != "1" && "${REPO_URL}" == git@github.com:* ]]; then
  require_command ssh
  log "Checking GitHub SSH access..."
  SSH_OUTPUT="$(ssh -o BatchMode=yes -o ConnectTimeout=15 -T git@github.com 2>&1 || true)"
  if ! grep -Eq "successfully authenticated|does not provide shell access" <<< "${SSH_OUTPUT}"; then
    printf '%s\n' "${SSH_OUTPUT}" >&2
    die "GitHub SSH authentication failed. Run: ssh -T git@github.com"
  fi
fi

log "Extracting Engagement Intake v${VERSION}..."
unzip -q "${ZIP_PATH}" -d "${WORK_DIR}"
find_source_directory
echo "Source directory: ${SOURCE_DIR}"

if [[ -e "${REPO_DIR}" && ! -d "${REPO_DIR}/.git" ]]; then
  die "The target exists but is not a Git repository: ${REPO_DIR}. Rename or remove it, or set SC_EI_REPO_DIR."
fi

if [[ -d "${REPO_DIR}/.git" ]]; then
  log "Refreshing the existing local repository..."
  git -C "${REPO_DIR}" remote set-url origin "${REPO_URL}"
  git -C "${REPO_DIR}" fetch origin --prune

  if git -C "${REPO_DIR}" show-ref --verify --quiet refs/remotes/origin/main; then
    git -C "${REPO_DIR}" checkout -B main origin/main
    git -C "${REPO_DIR}" reset --hard origin/main
  else
    git -C "${REPO_DIR}" checkout -B main
  fi

  git -C "${REPO_DIR}" clean -fdx
else
  log "Cloning the repository..."
  mkdir -p "$(dirname "${REPO_DIR}")"
  git clone "${REPO_URL}" "${REPO_DIR}"

  if git -C "${REPO_DIR}" show-ref --verify --quiet refs/remotes/origin/main; then
    git -C "${REPO_DIR}" checkout -B main origin/main
  else
    git -C "${REPO_DIR}" checkout -B main
  fi
fi

log "Replacing repository contents with v${VERSION}..."
rsync -a --delete --exclude='.git/' "${SOURCE_DIR}/" "${REPO_DIR}/"

log "Validating PHP syntax..."
PHP_FILE_COUNT=0
while IFS= read -r -d '' php_file; do
  php -l "${php_file}" >/dev/null
  PHP_FILE_COUNT=$((PHP_FILE_COUNT + 1))
done < <(find "${REPO_DIR}/${PLUGIN_SLUG}" -type f -name '*.php' -print0)
echo "  ${PHP_FILE_COUNT} PHP files passed."

log "Validating JavaScript syntax..."
node --check "${REPO_DIR}/${PLUGIN_SLUG}/assets/js/public.js"
node --check "${REPO_DIR}/${PLUGIN_SLUG}/assets/js/admin.js"

log "Running the complete release test suite..."
run_release_tests

log "Checking release markers..."
validate_release_markers

log "Running push-safe secret scan..."
if grep -RInE -I \
  --exclude-dir=.git \
  --exclude='PUSH_ENGAGEMENT_INTAKE_V070_CLEAN.sh' \
  '(AIza[0-9A-Za-z_-]{20,}|sk-[0-9A-Za-z]{20,}|ghp_[0-9A-Za-z]{20,}|-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----)' \
  "${REPO_DIR}"
then
  die "Potential secret found. Review the matches above before pushing."
fi
echo "  No push-blocking secret pattern found."

log "Preparing the Git commit..."
git -C "${REPO_DIR}" add -A

if git -C "${REPO_DIR}" diff --cached --quiet; then
  echo "No file changes require a new commit."
else
  git -C "${REPO_DIR}" commit -m "Build Engagement Intake v${VERSION}"
fi

git -C "${REPO_DIR}" branch -M main

if [[ "${SKIP_PUSH}" == "1" ]]; then
  log "Validation completed without pushing."
  echo "SC_EI_SKIP_PUSH=1 was set."
  echo "Prepared local repository: ${REPO_DIR}"
else
  log "Pushing main to the configured Git remote..."
  git -C "${REPO_DIR}" push -u origin main

  echo
  echo "Engagement Intake v${VERSION} pushed successfully."
  echo "Remote: ${REPO_URL}"
fi
