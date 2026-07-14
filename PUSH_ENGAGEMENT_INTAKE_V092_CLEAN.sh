#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

VERSION="0.9.2"
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
WORK_DIR="$(mktemp -d "${TMPDIR:-/tmp}/sc-ei-v092.XXXXXX")"

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

ensure_fixture_compatibility() {
  local root_dir="$1"
  local fixture="${root_dir}/tests/graph-client-fixtures.php"
  local patched="${fixture}.patched.$$"

  [[ -f "${fixture}" ]] || die "Required Graph client fixture is missing: ${fixture}"

  if ! grep -Fq "if ( ! function_exists( 'mb_substr' ) ) {" "${fixture}"; then
    awk '
      BEGIN { in_function = 0; depth = 0; found = 0 }
      !in_function && $0 ~ /^function[[:space:]]+mb_substr[[:space:]]*\(/ {
        print "if ( ! function_exists( '\''mb_substr'\'' ) ) {"
        in_function = 1
        found = 1
        line = $0
        print "\t" line
        temp = line
        opens = gsub(/\{/, "&", temp)
        temp = line
        closes = gsub(/\}/, "&", temp)
        depth += opens - closes
        if (depth == 0) {
          print "}"
          in_function = 0
        }
        next
      }
      in_function {
        line = $0
        print "\t" line
        temp = line
        opens = gsub(/\{/, "&", temp)
        temp = line
        closes = gsub(/\}/, "&", temp)
        depth += opens - closes
        if (depth == 0) {
          print "}"
          in_function = 0
        }
        next
      }
      { print }
      END {
        if (!found || in_function || depth != 0) {
          exit 3
        }
      }
    ' "${fixture}" > "${patched}" || {
      rm -f "${patched}"
      die "Could not locate and guard the mb_substr fixture fallback."
    }
    mv "${patched}" "${fixture}"
    echo "  Repaired the Graph client fixture for PHP installations with mbstring enabled."
  fi

  grep -Fq "if ( ! function_exists( 'mb_substr' ) ) {" "${fixture}" \
    || die "The Graph client fixture mb_substr fallback is not compatibility guarded."

  php -l "${fixture}" >/dev/null \
    || die "The Graph client fixture is malformed after compatibility repair."
}



validate_release_markers() {
  local main_file="${REPO_DIR}/${PLUGIN_SLUG}/${PLUGIN_SLUG}.php"
  local database="${REPO_DIR}/${PLUGIN_SLUG}/includes/class-sc-ei-database.php"
  local engagement_schema="${REPO_DIR}/${PLUGIN_SLUG}/includes/class-sc-ei-engagement-schema.php"
  local engagement_repo="${REPO_DIR}/${PLUGIN_SLUG}/includes/class-sc-ei-engagement-repository.php"
  local engagement_admin="${REPO_DIR}/${PLUGIN_SLUG}/includes/class-sc-ei-engagement-admin.php"
  local portal_schema="${REPO_DIR}/${PLUGIN_SLUG}/includes/class-sc-ei-portal-schema.php"
  local portal_view="${REPO_DIR}/${PLUGIN_SLUG}/public/views/sender-portal.php"
  local privacy="${REPO_DIR}/${PLUGIN_SLUG}/includes/class-sc-ei-privacy.php"
  local retention="${REPO_DIR}/${PLUGIN_SLUG}/includes/class-sc-ei-retention-engine.php"
  local diagnostics="${REPO_DIR}/${PLUGIN_SLUG}/includes/class-sc-ei-diagnostics.php"
  local graph_client_fixture="${REPO_DIR}/tests/graph-client-fixtures.php"

  grep -Fq "if ( ! function_exists( 'mb_substr' ) ) {" "${graph_client_fixture}" \
    || grep -Fq 'if ( ! function_exists( "mb_substr" ) ) {' "${graph_client_fixture}" \
    || die "Graph client fixture mb_substr fallback is not guarded."

  grep -Fq "Version:     ${VERSION}" "${main_file}" || die "Plugin version marker is missing."
  grep -Fq "SC_EI_DB_VERSION', '0.9.2'" "${main_file}" || die "Database version marker 0.9.2 is missing."
  grep -Fq "SC_EI_PORTAL_SCHEMA_VERSION', '1.3.0'" "${main_file}" || die "Portal schema marker 1.3.0 is missing."
  grep -Fq "SC_EI_WORKFLOW_SCHEMA_VERSION', '1.1.0'" "${main_file}" || die "Workflow schema marker 1.1.0 is missing."
  grep -Fq "SC_EI_GRAPH_SCHEMA_VERSION', '1.0.0'" "${main_file}" || die "Graph schema marker 1.0.0 is missing."
  grep -Fq "SC_EI_ENGAGEMENT_SCHEMA_VERSION', '1.0.0'" "${main_file}" || die "Engagement schema marker 1.0.0 is missing."

  grep -Fq '$sql_engagements' "${database}" || die "Engagement table declaration is missing."
  grep -Fq '$sql_engagement_snapshots' "${database}" || die "Engagement snapshot table declaration is missing."
  grep -Fq '$sql_engagement_requirements' "${database}" || die "Engagement requirement table declaration is missing."
  grep -Fq '$sql_engagement_events' "${database}" || die "Engagement event table declaration is missing."
  grep -Fq 'dbDelta( $sql_engagements )' "${database}" || die "Engagement table installation is missing."
  grep -Fq 'dbDelta( $sql_engagement_snapshots )' "${database}" || die "Engagement snapshot installation is missing."
  grep -Fq 'dbDelta( $sql_engagement_requirements )' "${database}" || die "Engagement requirement installation is missing."
  grep -Fq 'dbDelta( $sql_engagement_events )' "${database}" || die "Engagement event installation is missing."
  grep -Fq 'UNIQUE KEY proposal_id (proposal_id)' "${database}" || die "One-engagement-per-proposal uniqueness is missing."

  grep -Fq "'engagement_no_auto_activation'" "${engagement_schema}" || die "No-auto-activation safeguard is missing."
  grep -Fq "'engagement_no_auto_provisioning'" "${engagement_schema}" || die "No-auto-provisioning safeguard is missing."
  grep -Fq "'engagement_no_auto_invoice'" "${engagement_schema}" || die "No-auto-invoice safeguard is missing."
  grep -Fq "'engagement_no_auto_payment'" "${engagement_schema}" || die "No-auto-payment safeguard is missing."
  grep -Fq "'engagement_no_auto_signature'" "${engagement_schema}" || die "No-auto-signature safeguard is missing."

  grep -Fq "'contracted' !== \$proposal['status']" "${engagement_repo}" || die "Contracted-proposal eligibility is missing."
  grep -Fq 'engagement_duplicate_proposal' "${engagement_repo}" || die "Duplicate engagement prevention is missing."
  grep -Fq 'START TRANSACTION' "${engagement_repo}" || die "Atomic handoff transaction is missing."
  grep -Fq 'ROLLBACK' "${engagement_repo}" || die "Atomic handoff rollback is missing."
  grep -Fq "hash( 'sha256', \$snapshot_json )" "${engagement_repo}" || die "Handoff snapshot hashing is missing."
  grep -Fq 'public static function verify_snapshot' "${engagement_repo}" || die "Snapshot integrity verification is missing."
  grep -Fq "'status'                          => 'handoff_pending'" "${engagement_repo}" || die "Handoff-pending initial state is missing."
  grep -Fq 'engagement_readiness_changed' "${engagement_repo}" || die "Fresh activation readiness check is missing."
  grep -Fq "'provisioned'   => false" "${engagement_repo}" || die "Export-only integration boundary is missing."
  grep -Fq 'SET payload_json = %s, content_hash = %s' "${engagement_repo}" || die "Verifiable privacy tombstone hashing is missing."

  grep -Fq "'HANDOFF ' . strtoupper" "${engagement_admin}" || die "Typed handoff control is missing."
  grep -Fq "'READY ' . strtoupper" "${engagement_admin}" || die "Typed readiness control is missing."
  grep -Fq "'ACTIVATE ' . strtoupper" "${engagement_admin}" || die "Typed activation control is missing."
  grep -Fq "'PAUSE '" "${engagement_admin}" || die "Typed pause control is missing."
  grep -Fq "'COMPLETE '" "${engagement_admin}" || die "Typed completion control is missing."

  grep -Fq "'view_engagements'" "${portal_schema}" || die "Portal engagement permission is missing."
  grep -Fq 'The separately executed agreement remains the binding commercial record.' "${portal_view}" \
    || die "Sender-facing commercial boundary is missing."
  grep -Fq 'No invoice, payment, signature, or external project is created by this portal.' "${portal_view}" \
    || die "Sender-facing no-automation boundary is missing."

  grep -Fq 'Engagement Intake Engagement Handoffs' "${privacy}" || die "Engagement privacy export is missing."
  grep -Fq 'SC_EI_Engagement_Repository::redact_for_privacy' "${retention}" || die "Engagement approved erasure is missing."
  grep -Fq "'human_activation_required'  => true" "${diagnostics}" || die "Human activation diagnostic is missing."
  grep -Fq "'automatic_provisioning'     => false" "${diagnostics}" || die "No-provisioning diagnostic is missing."

  if grep -Fq 'wp_mail(' "${engagement_repo}"; then
    die "Engagement repository contains direct email delivery."
  fi
  if grep -Fq 'wp_remote_' "${engagement_repo}"; then
    die "Engagement repository contains external API provisioning."
  fi
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
    "tests/portal-schema-fixtures.php"
    "tests/portal-operations.php"
    "tests/portal-auth-recovery.php"
    "tests/workflow-schema-fixtures.php"
    "tests/workflow-operations.php"
    "tests/graph-crypto-fixtures.php"
    "tests/graph-client-fixtures.php"
    "tests/graph-credentials.php"
    "tests/graph-operations.php"
    "tests/engagement-schema-fixtures.php"
    "tests/engagement-operations.php"
    "tests/schema-mapping.php"
  )

  for test_file in "${tests[@]}"; do
    [[ -f "${REPO_DIR}/${test_file}" ]] || die "Required test file is missing: ${test_file}"
    echo "  php ${test_file}"
    php "${REPO_DIR}/${test_file}"
  done
}

log "Checking required commands..."
for command_name in bash git unzip rsync php node grep find mktemp awk; do
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

log "Repairing cross-platform fixture compatibility in the extracted source..."
ensure_fixture_compatibility "${SOURCE_DIR}"

log "Replacing repository contents with v${VERSION}..."
rsync -a --delete --exclude='.git/' "${SOURCE_DIR}/" "${REPO_DIR}/"

log "Verifying cross-platform fixture compatibility in the local repository..."
ensure_fixture_compatibility "${REPO_DIR}"

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
  --exclude='PUSH_ENGAGEMENT_INTAKE_V092_CLEAN.sh' \
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
