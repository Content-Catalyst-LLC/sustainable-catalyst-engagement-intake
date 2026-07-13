#!/usr/bin/env bash
set -euo pipefail

REPO_URL="git@github.com:Content-Catalyst-LLC/sustainable-catalyst-engagement-intake.git"
REPO_DIR="${HOME}/Downloads/sustainable-catalyst-engagement-intake"
ZIP_NAME="sustainable-catalyst-engagement-intake-v0.5.0-repo.zip"
ZIP_PATH="${HOME}/Downloads/${ZIP_NAME}"
WORK_DIR="$(mktemp -d)"

cleanup() {
  rm -rf "${WORK_DIR}"
}
trap cleanup EXIT

if [[ ! -f "${ZIP_PATH}" ]]; then
  echo "Could not find repo zip: ${ZIP_PATH}"
  exit 1
fi

echo "Checking GitHub CLI authentication..."
gh auth status >/dev/null

echo "Extracting Engagement Intake v0.5.0..."
unzip -q "${ZIP_PATH}" -d "${WORK_DIR}"
SOURCE_DIR="${WORK_DIR}/sustainable-catalyst-engagement-intake-v0.5.0-repo"

if [[ ! -d "${SOURCE_DIR}" ]]; then
  echo "Expected source directory not found: ${SOURCE_DIR}"
  exit 1
fi

if [[ -d "${REPO_DIR}/.git" ]]; then
  echo "Using the existing local repository."
  git -C "${REPO_DIR}" fetch origin
  git -C "${REPO_DIR}" checkout main
  git -C "${REPO_DIR}" reset --hard origin/main
  find "${REPO_DIR}" -mindepth 1 -maxdepth 1 ! -name .git -exec rm -rf {} +
else
  echo "Cloning repository..."
  git clone "${REPO_URL}" "${REPO_DIR}"
fi

echo "Replacing repository contents with v0.5.0..."
rsync -a --delete --exclude='.git' "${SOURCE_DIR}/" "${REPO_DIR}/"

echo "Running PHP syntax checks..."
find "${REPO_DIR}/sustainable-catalyst-engagement-intake" -name '*.php' -print0 |
  xargs -0 -n1 php -l >/dev/null

echo "Running JavaScript syntax checks..."
node --check "${REPO_DIR}/sustainable-catalyst-engagement-intake/assets/js/public.js"
node --check "${REPO_DIR}/sustainable-catalyst-engagement-intake/assets/js/admin.js"

echo "Running release test suite..."
php "${REPO_DIR}/tests/smoke.php"
php "${REPO_DIR}/tests/validator-fixtures.php"
php "${REPO_DIR}/tests/storage-fixtures.php"
php "${REPO_DIR}/tests/upload-environment-fixtures.php"
php "${REPO_DIR}/tests/scanner-fixtures.php"
php "${REPO_DIR}/tests/quarantine-operations.php"
php "${REPO_DIR}/tests/review-schema-fixtures.php"
php "${REPO_DIR}/tests/review-operations.php"
php "${REPO_DIR}/tests/communication-schema-fixtures.php"
php "${REPO_DIR}/tests/communication-operations.php"
php "${REPO_DIR}/tests/schema-mapping.php"

echo "Checking release markers..."
grep -q "Version:     0.5.0" \
  "${REPO_DIR}/sustainable-catalyst-engagement-intake/sustainable-catalyst-engagement-intake.php"

grep -q "SC_EI_COMMUNICATION_SCHEMA_VERSION', '1.0.0" \
  "${REPO_DIR}/sustainable-catalyst-engagement-intake/sustainable-catalyst-engagement-intake.php"

grep -q "class-sc-ei-communication-repository.php" \
  "${REPO_DIR}/sustainable-catalyst-engagement-intake/sustainable-catalyst-engagement-intake.php"

grep -q "accepted_by_mail_transport_not_proven_delivered" \
  "${REPO_DIR}/sustainable-catalyst-engagement-intake/includes/class-sc-ei-mailer.php"

grep -q "sender_acknowledgment_enabled'         => 0" \
  "${REPO_DIR}/sustainable-catalyst-engagement-intake/includes/class-sc-ei-admin.php"

grep -q "Saving does not send" \
  "${REPO_DIR}/sustainable-catalyst-engagement-intake/admin/views/communication-thread.php"

echo "Running push-safe secret scan..."
if grep -RInE \
  --exclude-dir=.git \
  --exclude='PUSH_ENGAGEMENT_INTAKE_V050_CLEAN.sh' \
  '(AIza[0-9A-Za-z_-]{20,}|sk-[0-9A-Za-z]{20,}|ghp_[0-9A-Za-z]{20,}|-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----)' \
  "${REPO_DIR}"; then
  echo "Potential secret found. Push cancelled."
  exit 1
fi

cd "${REPO_DIR}"
git add -A

if git diff --cached --quiet; then
  echo "No changes to commit."
else
  git commit -m "Build Engagement Intake v0.5.0"
fi

git branch -M main
git push -u origin main

echo
echo "Engagement Intake v0.5.0 pushed successfully."
echo "Repository: https://github.com/Content-Catalyst-LLC/sustainable-catalyst-engagement-intake"
