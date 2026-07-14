#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

VERSION="1.0.2"
SLUG="sustainable-catalyst-engagement-intake"
REPO_URL="${SC_EI_REPO_URL:-git@github.com:Content-Catalyst-LLC/${SLUG}.git}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
ZIP_PATH="${SC_EI_ZIP_PATH:-$SCRIPT_DIR/${SLUG}-v${VERSION}-repo.zip}"
REPO_DIR="${SC_EI_REPO_DIR:-}"
SKIP_PUSH="${SC_EI_SKIP_PUSH:-0}"
WORK="$(mktemp -d "${TMPDIR:-/tmp}/sc-ei-v102-push.XXXXXX")"
BACKUP_ROOT="${SC_EI_BACKUP_DIR:-$HOME/Downloads/${SLUG}-local-backups}"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"

cleanup() { rm -rf "$WORK"; }
trap cleanup EXIT
trap 'rc=$?; echo; echo "ERROR: v1.0.2 repository update failed on line $LINENO (exit $rc)." >&2; exit $rc' ERR
say() { printf '\n==> %s\n' "$1"; }
fail() { echo "ERROR: $1" >&2; exit 1; }

for command_name in git unzip rsync grep find mktemp php; do
  command -v "$command_name" >/dev/null 2>&1 || fail "Missing required command: $command_name"
done
[[ -f "$ZIP_PATH" ]] || fail "Repository ZIP not found: $ZIP_PATH"

if [[ -z "$REPO_DIR" ]]; then
  for candidate in \
    "$HOME/Downloads/$SLUG" \
    "$HOME/Downloads/${SLUG}-repo" \
    "$HOME/GitHub/$SLUG" \
    "$HOME/github/$SLUG" \
    "$HOME/github-repos/$SLUG" \
    "$HOME/Documents/GitHub/$SLUG" \
    "$HOME/Documents/GitHub Repos/$SLUG" \
    "$HOME/Desktop/GitHub/$SLUG"; do
    if [[ -d "$candidate/.git" ]]; then REPO_DIR="$candidate"; break; fi
  done
fi
[[ -n "$REPO_DIR" ]] || REPO_DIR="$HOME/Downloads/$SLUG"

say "Extracting v$VERSION repository archive"
unzip -tq "$ZIP_PATH" >/dev/null
unzip -q "$ZIP_PATH" -d "$WORK"
SRC="$WORK/${SLUG}-v${VERSION}-repo"
[[ -d "$SRC/$SLUG" ]] || fail "Invalid repository ZIP: plugin directory missing."
MAIN="$SRC/$SLUG/$SLUG.php"
README="$SRC/$SLUG/readme.txt"
REPO="$SRC/$SLUG/includes/class-sc-ei-platform-repository.php"
VALIDATION="$SRC/$SLUG/includes/class-sc-ei-platform-validation.php"
[[ -f "$MAIN" && -f "$README" && -f "$VALIDATION" ]] || fail "Required v1.0.2 files are missing."
grep -Fq "Version:     $VERSION" "$MAIN" || fail "Plugin version marker is not $VERSION."
grep -Fq "Stable tag: $VERSION" "$README" || fail "WordPress stable tag is not $VERSION."
grep -Fq "PATCH_MIGRATION_KEY = 'v1_0_2_production_readiness_live_validation'" "$REPO" || fail "v1.0.2 patch migration is missing."
grep -Fq "100 === \$score && empty( \$required_failures ) && empty( \$warnings )" "$REPO" || fail "Strict production gate is missing."
grep -Fq "class SC_EI_Platform_Validation" "$VALIDATION" || fail "Live validation class is missing."

say "Preparing local Git repository"
if [[ -d "$REPO_DIR/.git" ]]; then
  origin_url="$(git -C "$REPO_DIR" remote get-url origin 2>/dev/null || true)"
  [[ "$origin_url" == *"$SLUG"* ]] || fail "Detected checkout has an unexpected origin: $origin_url"
else
  rm -rf "$REPO_DIR"
  git clone "$REPO_URL" "$REPO_DIR"
fi
mkdir -p "$BACKUP_ROOT"
BACKUP_DIR="$BACKUP_ROOT/${SLUG}-before-v${VERSION}-$TIMESTAMP"
mkdir -p "$BACKUP_DIR"
rsync -a --exclude='.git/' "$REPO_DIR/" "$BACKUP_DIR/"
git -C "$REPO_DIR" status --short > "$BACKUP_DIR/git-status.txt" || true
git -C "$REPO_DIR" diff > "$BACKUP_DIR/uncommitted.patch" || true

git -C "$REPO_DIR" fetch origin main
git -C "$REPO_DIR" checkout -B main origin/main

say "Replacing repository contents while preserving .git"
rsync -a --delete --exclude='.git/' "$SRC/" "$REPO_DIR/"
cd "$REPO_DIR"

say "Validating repository"
php_count=0
while IFS= read -r file; do
  php -l "$file" >/dev/null
  php_count=$((php_count + 1))
done <<FILES
$(find "$SLUG" tests -type f -name '*.php' | sort)
FILES

test_count=0
for test_file in tests/*.php; do
  php "$test_file" >/dev/null
  test_count=$((test_count + 1))
done

if command -v node >/dev/null 2>&1; then
  node --check "$SLUG/assets/js/public.js"
  node --check "$SLUG/assets/js/admin.js"
else
  echo "Node.js is unavailable; JavaScript checks were skipped locally."
fi

echo "PHP syntax passed: $php_count files"
echo "Release suites passed: $test_count"

say "Reviewing and committing changes"
git status --short
git add -A
if git diff --cached --quiet; then
  echo "No changes detected. Nothing to commit."
else
  git commit -m "Build v1.0.2 Production Readiness and Live Validation"
fi

if [[ "$SKIP_PUSH" == "1" ]]; then
  echo "Push skipped because SC_EI_SKIP_PUSH=1."
else
  say "Pushing main to GitHub"
  git push origin main
fi

say "Complete"
echo "Repository: $REPO_DIR"
echo "Backup:     $BACKUP_DIR"
echo "Version:    $VERSION"
