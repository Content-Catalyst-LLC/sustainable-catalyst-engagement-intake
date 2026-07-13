#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

VERSION="0.9.1"
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
WORK_DIR="$(mktemp -d "${TMPDIR:-/tmp}/sc-ei-v091.XXXXXX")"

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
  local database="${REPO_DIR}/${PLUGIN_SLUG}/includes/class-sc-ei-database.php"
  local graph_crypto="${REPO_DIR}/${PLUGIN_SLUG}/includes/class-sc-ei-graph-crypto.php"
  local graph_credentials="${REPO_DIR}/${PLUGIN_SLUG}/includes/class-sc-ei-graph-credentials.php"
  local graph_client="${REPO_DIR}/${PLUGIN_SLUG}/includes/class-sc-ei-graph-client.php"
  local graph_repo="${REPO_DIR}/${PLUGIN_SLUG}/includes/class-sc-ei-graph-repository.php"
  local graph_admin="${REPO_DIR}/${PLUGIN_SLUG}/includes/class-sc-ei-graph-admin.php"
  local workflow_view="${REPO_DIR}/${PLUGIN_SLUG}/admin/views/teams-proposals.php"

  grep -Fq "Version:     ${VERSION}" "${main_file}" || die "Plugin version marker is missing."
  grep -Fq "SC_EI_DB_VERSION', '0.9.1'" "${main_file}" || die "Database version marker 0.9.1 is missing."
  grep -Fq "SC_EI_WORKFLOW_SCHEMA_VERSION', '1.1.0'" "${main_file}" || die "Workflow schema marker 1.1.0 is missing."
  grep -Fq "SC_EI_GRAPH_SCHEMA_VERSION', '1.0.0'" "${main_file}" || die "Graph schema marker 1.0.0 is missing."

  grep -Fq '$sql_graph_operations' "${database}" || die "Graph operation table declaration is missing."
  grep -Fq 'dbDelta( $sql_graph_operations )' "${database}" || die "Graph operation table installation is missing."
  grep -Fq 'graph_transaction_id char(36)' "${database}" || die "Persistent Graph transaction field is missing."
  grep -Fq 'graph_join_url text' "${database}" || die "Graph join URL field is missing."
  grep -Fq 'UNIQUE KEY idempotency_key' "${database}" || die "Graph idempotency uniqueness is missing."

  grep -Fq 'sodium_crypto_secretbox' "${graph_crypto}" || die "Sodium encryption support is missing."
  grep -Fq 'aes-256-gcm' "${graph_crypto}" || die "OpenSSL AES-256-GCM fallback is missing."

  grep -Fq 'SC_EI_Graph_Crypto::seal_array( $vault )' "${graph_credentials}" || die "Encrypted credential vault is missing."
  grep -Fq 'SC_EI_Graph_Crypto::seal_array( $payload )' "${graph_credentials}" || die "Encrypted token cache is missing."
  grep -Fq '$old_token_key = self::token_cache_key_for_runtime( $current )' "${graph_credentials}" || die "Credential rotation token invalidation is missing."

  grep -Fq "GRAPH_RESOURCE . '/.default'" "${graph_client}" || die "Correct Graph OAuth resource scope is missing."
  if grep -Fq "GRAPH_BASE . '/.default'" "${graph_client}"; then
    die "Graph OAuth scope incorrectly includes the v1.0 API path."
  fi
  if grep -Fq "graph.microsoft.com/beta" "${graph_client}"; then
    die "Graph beta API usage is not allowed."
  fi
  grep -Fq "'client-request-id'" "${graph_client}" || die "Graph client request correlation is missing."
  grep -Fq 'parse_retry_after' "${graph_client}" || die "Retry-After handling is missing."
  grep -Fq 'consecutive_failures' "${graph_client}" || die "Graph circuit breaker is missing."

  grep -Fq "'transactionId'         => (string) \$offer['graph_transaction_id']" "${graph_repo}" || die "Graph transactionId payload is missing."
  grep -Fq "hash( 'sha256', 'create|' . \$meeting_offer_id . '|' . \$transaction_id )" "${graph_repo}" || die "Local Graph idempotency key is missing."
  grep -Fq 'SC_EI_Graph_Crypto::seal_array' "${graph_repo}" || die "Encrypted Graph operation payload is missing."
  grep -Fq 'graph_stale_lock_recovered' "${graph_repo}" || die "Stale Graph lock recovery is missing."
  grep -Fq 'SC_EI_Graph_Client::retry_delay' "${graph_repo}" || die "Bounded Graph retry scheduling is missing."
  grep -Fq 'public static function retry_operation' "${graph_repo}" || die "Same-operation manual retry is missing."
  grep -Fq "'idempotency_preserved'=> true" "${graph_repo}" || die "Manual retry idempotency evidence is missing."
  grep -Fq "'graph_local_state_blocked'" "${graph_repo}" || die "Stale local-state remote-create protection is missing."
  grep -Fq "'remote_exists_local_closed'" "${graph_repo}" || die "Closed-meeting resurrection protection is missing."
  grep -Fq "onlineMeeting']['joinUrl" "${graph_repo}" || die "Supported Teams joinUrl reconciliation is missing."

  grep -Fq "'GRAPH ' . strtoupper" "${graph_admin}" || die "Typed Graph event creation is missing."
  grep -Fq "'RECONCILE ' . strtoupper" "${graph_admin}" || die "Typed Graph reconciliation is missing."
  grep -Fq "'DELETE GRAPH ' . strtoupper" "${graph_admin}" || die "Typed remote event deletion is missing."
  grep -Fq "'RETRY GRAPH ' . \$operation_id" "${graph_admin}" || die "Typed Graph operation retry is missing."

  grep -Fq 'Manual Teams URL finalization remains available regardless of connector state.' "${workflow_view}" \
    || die "Manual Teams fallback boundary is missing."

  if grep -Fq "wp_mail(" "${graph_repo}"; then
    die "Graph repository contains direct email delivery."
  fi
  if grep -Fq "proposal" "${graph_repo}"; then
    die "Graph repository is coupled to proposal or contract automation."
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
  --exclude='PUSH_ENGAGEMENT_INTAKE_V091_CLEAN.sh' \
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
