#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

VERSION="0.12.0"
SLUG="sustainable-catalyst-engagement-intake"
ARCHIVE="${SLUG}-v${VERSION}-repo.zip"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
REPO_URL="${SC_EI_REPO_URL:-git@github.com:Content-Catalyst-LLC/sustainable-catalyst-engagement-intake.git}"
REPO_DIR="${SC_EI_REPO_DIR:-$HOME/Downloads/$SLUG}"
ZIP_PATH="${SC_EI_ZIP_PATH:-$SCRIPT_DIR/$ARCHIVE}"
SKIP_PUSH="${SC_EI_SKIP_PUSH:-0}"
SKIP_REMOTE_CHECK="${SC_EI_SKIP_REMOTE_CHECK:-0}"
WORK="$(mktemp -d "${TMPDIR:-/tmp}/sc-ei-v0120.XXXXXX")"

cleanup() { rm -rf "$WORK"; }
trap cleanup EXIT
trap 'rc=$?; echo; echo "ERROR: Engagement Intake v$VERSION push workflow failed on line $LINENO." >&2; echo "Exit code: $rc" >&2; echo "The local repository was left at: $REPO_DIR" >&2; exit $rc' ERR

for command_name in bash git unzip rsync php node grep find mktemp python3; do
  command -v "$command_name" >/dev/null || { echo "Missing command: $command_name" >&2; exit 1; }
done

[[ -f "$ZIP_PATH" ]] || { echo "Repository ZIP not found: $ZIP_PATH" >&2; exit 1; }

echo "==> Extracting Engagement Intake v$VERSION"
unzip -q "$ZIP_PATH" -d "$WORK"
SRC="$(find "$WORK" -mindepth 1 -maxdepth 1 -type d | head -1)"
[[ -n "$SRC" && -d "$SRC/$SLUG" && -f "$SRC/composer.json" ]] || { echo "Invalid repository archive." >&2; exit 1; }

# Repair the historical mb_substr fixture fallback before any tests run.
FIX="$SRC/tests/graph-client-fixtures.php"
if [[ -f "$FIX" ]] && ! grep -Fq "if ( ! function_exists( 'mb_substr' ) )" "$FIX"; then
  python3 - "$FIX" <<'PY_FIX'
from pathlib import Path
import re, sys
path=Path(sys.argv[1])
text=path.read_text(encoding='utf-8')
match=re.search(r'(?m)^function\s+mb_substr\s*\([^\n]+\)\s*\{', text)
if not match:
    raise SystemExit('mb_substr fixture fallback not found')
start=match.start(); cursor=match.end(); depth=1
while cursor < len(text) and depth:
    depth += (text[cursor] == '{') - (text[cursor] == '}')
    cursor += 1
if depth:
    raise SystemExit('mb_substr fixture fallback is unbalanced')
block=text[start:cursor]
indented='\n'.join('\t'+line for line in block.splitlines())
text=text[:start]+"if ( ! function_exists( 'mb_substr' ) ) {\n"+indented+"\n}\n"+text[cursor:]
path.write_text(text, encoding='utf-8')
PY_FIX
fi
grep -Fq "if ( ! function_exists( 'mb_substr' ) )" "$FIX" || { echo "mb_substr compatibility guard missing." >&2; exit 1; }

if [[ -d "$REPO_DIR/.git" ]]; then
  if [[ "$SKIP_REMOTE_CHECK" != "1" ]]; then git -C "$REPO_DIR" fetch origin main; fi
  git -C "$REPO_DIR" checkout -B main origin/main 2>/dev/null || git -C "$REPO_DIR" checkout -B main
else
  if [[ -e "$REPO_DIR" && ! -d "$REPO_DIR" ]]; then echo "Target is not a directory: $REPO_DIR" >&2; exit 1; fi
  rm -rf "$REPO_DIR"
  git clone "$REPO_URL" "$REPO_DIR"
  git -C "$REPO_DIR" checkout -B main origin/main 2>/dev/null || git -C "$REPO_DIR" checkout -B main
fi

echo "==> Replacing repository contents"
find "$REPO_DIR" -mindepth 1 -maxdepth 1 ! -name .git -exec rm -rf {} +
rsync -a "$SRC/" "$REPO_DIR/"
cd "$REPO_DIR"

echo "==> Validating v0.12.0 release contract"
MAIN="$SLUG/$SLUG.php"
DB="$SLUG/includes/class-sc-ei-database.php"
CORE_SCHEMA="$SLUG/includes/class-sc-ei-workflow-core-schema.php"
CORE_CONTRACT="$SLUG/includes/class-sc-ei-workflow-core-contract.php"
CORE_REPO="$SLUG/includes/class-sc-ei-workflow-core-repository.php"
CORE_SERVICE="$SLUG/includes/class-sc-ei-workflow-core-service.php"
CORE_ADMIN="$SLUG/includes/class-sc-ei-workflow-core-admin.php"
CORE_VIEW="$SLUG/admin/views/workflow-core.php"
REST="$SLUG/includes/class-sc-ei-rest.php"

required_markers=(
  "Version:     0.12.0"
  "SC_EI_DB_VERSION', '0.12.0'"
  "SC_EI_WORKFLOW_CORE_SCHEMA_VERSION', '1.0.0'"
)
for marker in "${required_markers[@]}"; do grep -Fq "$marker" "$MAIN" || { echo "Missing release marker: $marker" >&2; exit 1; }; done

for table in workflow_cases workflow_commands workflow_handoffs workflow_outbox; do
  grep -Fq "\$sql_${table}" "$DB" || { echo "Workflow Core table declaration missing: $table" >&2; exit 1; }
  grep -Fq "dbDelta( \$sql_${table} )" "$DB" || { echo "Workflow Core dbDelta missing: $table" >&2; exit 1; }
done

grep -Fq "workflow_core_no_auto_acceptance" "$CORE_SCHEMA" || { echo "No automatic acceptance boundary missing." >&2; exit 1; }
grep -Fq "workflow_core_no_auto_activation" "$CORE_SCHEMA" || { echo "No automatic activation boundary missing." >&2; exit 1; }
grep -Fq "SCHEMA_ID = 'sc-engagement-workflow-handoff/1.0'" "$CORE_CONTRACT" || { echo "Workflow Core handoff schema missing." >&2; exit 1; }
grep -Fq "hash_hmac( 'sha256'" "$CORE_CONTRACT" || { echo "HMAC signature missing." >&2; exit 1; }
grep -Fq "public static function verify" "$CORE_CONTRACT" || { echo "Handoff verification missing." >&2; exit 1; }
grep -Fq "\$command_key = hash(" "$CORE_REPO" || { echo "Idempotent command key missing." >&2; exit 1; }
grep -Fq "\$handoff_key = hash(" "$CORE_REPO" || { echo "Idempotent handoff key missing." >&2; exit 1; }
grep -Fq "recover_stale_outbox_claims" "$CORE_REPO" || { echo "Outbox stale-claim recovery missing." >&2; exit 1; }
grep -Fq "workflow_core_adapter_unavailable" "$CORE_SERVICE" || { echo "Explicit adapter requirement missing." >&2; exit 1; }
grep -Fq "public static function register_adapter" "$CORE_SERVICE" || { echo "Adapter registry missing." >&2; exit 1; }
grep -Fq "SYNC WORKFLOW CORE" "$CORE_ADMIN" || { echo "Typed core synchronization missing." >&2; exit 1; }
grep -Fq "DISPATCH OUTBOX" "$CORE_ADMIN" || { echo "Typed outbox dispatch missing." >&2; exit 1; }
grep -Fq "No arbitrary URL or webhook field is exposed" "$CORE_VIEW" || { echo "Adapter-only boundary disclosure missing." >&2; exit 1; }
grep -Fq "'/workflow-core/cases'" "$REST" || { echo "Read-only Workflow Core REST resource missing." >&2; exit 1; }
if grep -Fq "'/workflow-core/commands'" "$REST"; then echo "Public or REST command endpoint is not allowed." >&2; exit 1; fi
if grep -Fq 'wp_remote_' "$CORE_REPO" || grep -Fq 'wp_remote_' "$CORE_SERVICE"; then echo "Direct external HTTP delivery is not allowed." >&2; exit 1; fi
if grep -Fq 'SC_EI_Inquiry_Repository::update_status' "$CORE_REPO" || grep -Fq 'SC_EI_Engagement_Repository::activate' "$CORE_REPO"; then echo "Workflow Core may not mutate authoritative decisions." >&2; exit 1; fi

echo "==> PHP syntax"
while IFS= read -r -d '' file; do php -l "$file" >/dev/null; done < <(find "$SLUG" -name '*.php' -print0)
node --check "$SLUG/assets/js/public.js"
node --check "$SLUG/assets/js/admin.js"
bash -n "PUSH_ENGAGEMENT_INTAKE_V0120_CLEAN.sh"

echo "==> Release tests"
python3 - <<'PY_TESTS'
from pathlib import Path
import json, subprocess
root=Path('.')
command=json.loads((root/'composer.json').read_text(encoding='utf-8'))['scripts']['test']
result=subprocess.run(['bash','-lc',command], cwd=root)
raise SystemExit(result.returncode)
PY_TESTS

echo "==> Push-safe secret scan"
if grep -RInE --exclude='PUSH_ENGAGEMENT_INTAKE_V0120_CLEAN.sh' --exclude-dir=.git '(AIza[0-9A-Za-z_-]{20,}|sk-[0-9A-Za-z]{20,}|ghp_[0-9A-Za-z]{20,}|-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----)' .; then
  echo "Potential secret detected." >&2
  exit 1
fi

git add -A
if git diff --cached --quiet; then
  echo "No changes to commit."
else
  git commit -m "Build Engagement Intake v0.12.0 Workflow Core Integration"
fi

if [[ "$SKIP_PUSH" == "1" ]]; then
  echo "Validation complete; push skipped."
else
  git push origin main
fi

echo "Engagement Intake v0.12.0 push workflow completed."
