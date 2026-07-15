#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'
VERSION="1.5.0"; SLUG="sustainable-catalyst-engagement-intake"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
ZIP_PATH="${SC_EI_ZIP_PATH:-$SCRIPT_DIR/${SLUG}-v${VERSION}-repo.zip}"
REPO_URL="${SC_EI_REPO_URL:-git@github.com:Content-Catalyst-LLC/${SLUG}.git}"
REPO_DIR="${SC_EI_REPO_DIR:-}"; SKIP_PUSH="${SC_EI_SKIP_PUSH:-0}"
WORK="$(mktemp -d "${TMPDIR:-/tmp}/sc-ei-v150-push.XXXXXX")"; BACKUPS="${SC_EI_BACKUP_DIR:-$HOME/Downloads/${SLUG}-local-backups}"; STAMP="$(date +%Y%m%d-%H%M%S)"
cleanup(){ rm -rf "$WORK"; }; trap cleanup EXIT; trap 'rc=$?; echo "ERROR: v1.5.0 update failed at line $LINENO (exit $rc)." >&2; exit $rc' ERR
say(){ printf '\n==> %s\n' "$1"; }; fail(){ echo "ERROR: $1" >&2; exit 1; }
for c in git unzip rsync grep find mktemp php python3; do command -v "$c" >/dev/null || fail "Missing required command: $c"; done
[[ -f "$ZIP_PATH" ]] || fail "Repository ZIP not found: $ZIP_PATH"
if [[ -z "$REPO_DIR" ]]; then
 for c in "$HOME/Downloads/$SLUG" "$HOME/GitHub/$SLUG" "$HOME/github/$SLUG" "$HOME/github-repos/$SLUG" "$HOME/Documents/GitHub/$SLUG" "$HOME/Documents/GitHub Repos/$SLUG" "$HOME/Desktop/GitHub/$SLUG"; do [[ -d "$c/.git" ]] && REPO_DIR="$c" && break; done
fi
[[ -n "$REPO_DIR" ]] || REPO_DIR="$HOME/Downloads/$SLUG"
say "Extracting v$VERSION repository archive"; unzip -tq "$ZIP_PATH" >/dev/null; unzip -q "$ZIP_PATH" -d "$WORK"
SRC="$WORK/${SLUG}-v${VERSION}-repo"; MAIN="$SRC/$SLUG/$SLUG.php"; README="$SRC/$SLUG/readme.txt"
[[ -f "$MAIN" && -f "$README" && -f "$SRC/$SLUG/includes/class-sc-ei-workspace-repository.php" && -f "$SRC/tests/client-workspace-collaboration.php" ]] || fail 'Invalid v1.5.0 repository archive.'
grep -Fq 'Version:     1.5.0' "$MAIN" || fail 'Plugin version marker missing.'
grep -Fq "SC_EI_DB_VERSION', '1.5.0'" "$MAIN" || fail 'Database version marker missing.'
grep -Fq "SC_EI_PLATFORM_SCHEMA_VERSION', '1.5.0'" "$MAIN" || fail 'Platform schema marker missing.'
grep -Fq "SC_EI_PORTAL_SCHEMA_VERSION', '1.7.0'" "$MAIN" || fail 'Portal schema marker missing.'
grep -Fq "SC_EI_WORKSPACE_SCHEMA_VERSION', '1.0.0'" "$MAIN" || fail 'Workspace schema marker missing.'
grep -Fq 'Stable tag: 1.5.0' "$README" || fail 'Stable tag missing.'
grep -Fq "MIGRATION_KEY = 'v1_5_0_secure_client_workspace_collaboration'" "$SRC/$SLUG/includes/class-sc-ei-workspace-repository.php" || fail 'v1.5.0 workspace migration missing.'
grep -Fq 'sender_projection_keys' "$SRC/$SLUG/includes/class-sc-ei-workspace-schema.php" || fail 'Sender projection allowlist missing.'
grep -Fq 'add_sender_message' "$SRC/$SLUG/includes/class-sc-ei-workspace-repository.php" || fail 'Workspace sender messaging missing.'
grep -Fq 'record_sender_deliverable_decision' "$SRC/$SLUG/includes/class-sc-ei-workspace-repository.php" || fail 'Deliverable decision workflow missing.'
if [[ -d "$REPO_DIR/.git" ]]; then origin="$(git -C "$REPO_DIR" remote get-url origin 2>/dev/null || true)"; [[ "$origin" == *"$SLUG"* || "${SC_EI_ALLOW_ANY_ORIGIN:-0}" == 1 ]] || fail "Unexpected origin: $origin"; else rm -rf "$REPO_DIR"; git clone "$REPO_URL" "$REPO_DIR"; fi
mkdir -p "$BACKUPS/${SLUG}-before-v${VERSION}-$STAMP"; rsync -a --exclude='.git/' "$REPO_DIR/" "$BACKUPS/${SLUG}-before-v${VERSION}-$STAMP/"
if git -C "$REPO_DIR" rev-parse --verify HEAD >/dev/null 2>&1; then git -C "$REPO_DIR" reset --hard HEAD >/dev/null; git -C "$REPO_DIR" clean -fd >/dev/null; fi
git -C "$REPO_DIR" fetch origin || true
if git -C "$REPO_DIR" show-ref --verify --quiet refs/remotes/origin/main; then git -C "$REPO_DIR" checkout -B main origin/main; else git -C "$REPO_DIR" checkout -B main; fi
say 'Replacing repository contents while preserving .git'; rsync -a --delete --exclude='.git/' "$SRC/" "$REPO_DIR/"; cd "$REPO_DIR"
say 'Validating repository'; while IFS= read -r f; do php -l "$f" >/dev/null; done < <(find "$SLUG" tests -type f -name '*.php' | sort); for t in tests/*.php; do php "$t" >/dev/null; done
if command -v node >/dev/null; then while IFS= read -r f; do node --check "$f"; done < <(find "$SLUG" -type f -name '*.js' | sort); fi
python3 -m json.tool release-manifest.json >/dev/null
git add -A
if git diff --cached --quiet; then echo 'No changes detected.'; else git commit -m 'Build v1.5.0 Secure Client Workspace and Collaboration'; fi
if [[ "$SKIP_PUSH" == 1 ]]; then echo 'Push skipped.'; else git push -u origin main; fi
say "Completed v$VERSION repository update"; echo "Repository: $REPO_DIR"; echo "Backup: $BACKUPS/${SLUG}-before-v${VERSION}-$STAMP"
