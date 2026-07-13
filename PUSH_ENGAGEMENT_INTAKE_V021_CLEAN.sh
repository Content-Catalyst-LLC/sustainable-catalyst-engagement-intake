#!/usr/bin/env bash
set -euo pipefail

REPO_URL="git@github.com:Content-Catalyst-LLC/sustainable-catalyst-engagement-intake.git"
REPO_DIR="${HOME}/Downloads/sustainable-catalyst-engagement-intake"
ZIP_NAME="sustainable-catalyst-engagement-intake-v0.2.1-repo.zip"
ZIP_PATH="${HOME}/Downloads/${ZIP_NAME}"
WORK_DIR="$(mktemp -d)"

cleanup() {
  rm -rf "${WORK_DIR}"
}
trap cleanup EXIT

if [[ ! -f "${ZIP_PATH}" ]]; then
  echo "Could not find repo zip: ${ZIP_PATH}"
  echo "Download ${ZIP_NAME} into your Downloads folder, then run this script again."
  exit 1
fi

echo "Checking GitHub CLI authentication..."
gh auth status >/dev/null

echo "Extracting Engagement Intake v0.2.1..."
unzip -q "${ZIP_PATH}" -d "${WORK_DIR}"
SOURCE_DIR="${WORK_DIR}/sustainable-catalyst-engagement-intake-v0.2.1-repo"

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

echo "Replacing repository contents with v0.2.1..."
rsync -a --delete --exclude='.git' "${SOURCE_DIR}/" "${REPO_DIR}/"

echo "Running PHP syntax checks..."
find "${REPO_DIR}/sustainable-catalyst-engagement-intake" -name '*.php' -print0 |
  xargs -0 -n1 php -l >/dev/null

echo "Running JavaScript syntax check..."
node --check "${REPO_DIR}/sustainable-catalyst-engagement-intake/assets/js/public.js"

echo "Running smoke checks..."
php "${REPO_DIR}/tests/smoke.php"

echo "Checking release markers..."
grep -q "Version:     0.2.1" \
  "${REPO_DIR}/sustainable-catalyst-engagement-intake/sustainable-catalyst-engagement-intake.php"

grep -q "Microsoft Teams" \
  "${REPO_DIR}/sustainable-catalyst-engagement-intake/includes/class-sc-ei-teams.php"

echo "Running push-safe secret scan..."
if grep -RInE \
  --exclude-dir=.git \
  --exclude='PUSH_ENGAGEMENT_INTAKE_V021_CLEAN.sh' \
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
  git commit -m "Build Engagement Intake v0.2.1"
fi

git branch -M main
git push -u origin main

echo
echo "Engagement Intake v0.2.1 pushed successfully."
echo "Repository: https://github.com/Content-Catalyst-LLC/sustainable-catalyst-engagement-intake"
