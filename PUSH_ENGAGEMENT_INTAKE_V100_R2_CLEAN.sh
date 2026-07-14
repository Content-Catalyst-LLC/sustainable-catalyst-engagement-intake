#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

VERSION="1.0.0"
SLUG="sustainable-catalyst-engagement-intake"
ARCHIVE="${SLUG}-v${VERSION}-repo-r2.zip"
SCRIPT_NAME="PUSH_ENGAGEMENT_INTAKE_V100_R2_CLEAN.sh"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
REPO_URL="${SC_EI_REPO_URL:-git@github.com:Content-Catalyst-LLC/sustainable-catalyst-engagement-intake.git}"
REPO_DIR="${SC_EI_REPO_DIR:-$HOME/Downloads/$SLUG}"
ZIP_PATH="${SC_EI_ZIP_PATH:-$SCRIPT_DIR/$ARCHIVE}"
SKIP_PUSH="${SC_EI_SKIP_PUSH:-0}"
SKIP_REMOTE_CHECK="${SC_EI_SKIP_REMOTE_CHECK:-0}"
WORK="$(mktemp -d "${TMPDIR:-/tmp}/sc-ei-v100.XXXXXX")"

cleanup() { rm -rf "$WORK"; }
trap cleanup EXIT
trap 'rc=$?; echo; echo "ERROR: Unified Contact and Engagement Platform v$VERSION push workflow failed on line $LINENO." >&2; echo "Exit code: $rc" >&2; echo "The local repository was left at: $REPO_DIR" >&2; exit $rc' ERR

for command_name in bash git unzip rsync php node grep find mktemp python3; do
  command -v "$command_name" >/dev/null || { echo "Missing command: $command_name" >&2; exit 1; }
done

[[ -f "$ZIP_PATH" ]] || { echo "Repository ZIP not found: $ZIP_PATH" >&2; exit 1; }

echo "==> Extracting Unified Contact and Engagement Platform v$VERSION"
unzip -q "$ZIP_PATH" -d "$WORK"
EXPECTED_ROOT="$WORK/${SLUG}-v${VERSION}-repo"
[[ -d "$EXPECTED_ROOT/$SLUG" && -f "$EXPECTED_ROOT/composer.json" ]] || { echo "Invalid repository archive root or contents." >&2; exit 1; }
SRC="$EXPECTED_ROOT"

# Repair the historical mb_substr fixture fallback before any tests run.
FIX="$SRC/tests/graph-client-fixtures.php"
if [[ -f "$FIX" ]] && ! grep -Fq "if ( ! function_exists( 'mb_substr' ) )" "$FIX"; then
  python3 - "$FIX" <<'PY_FIX'
from pathlib import Path
import re, sys
path=Path(sys.argv[1])
text=path.read_text(encoding='utf-8')
match=re.search(r'(?m)^function\s+mb_substr\s*\([^\n]+\)[^{\n]*\{', text)
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

echo "==> Validating v1.0.0 stable platform contract"
MAIN="$SLUG/$SLUG.php"
DB="$SLUG/includes/class-sc-ei-database.php"
PLATFORM_SCHEMA="$SLUG/includes/class-sc-ei-platform-schema.php"
PLATFORM_REPO="$SLUG/includes/class-sc-ei-platform-repository.php"
PLATFORM_PUBLIC="$SLUG/includes/class-sc-ei-platform-public.php"
PLATFORM_ADMIN="$SLUG/includes/class-sc-ei-platform-admin.php"
PLATFORM_VIEW="$SLUG/admin/views/platform-overview.php"
ADMIN="$SLUG/includes/class-sc-ei-admin.php"
CAPS="$SLUG/includes/class-sc-ei-capabilities.php"
REST="$SLUG/includes/class-sc-ei-rest.php"
CORE_REPO="$SLUG/includes/class-sc-ei-workflow-core-repository.php"
CORE_CONTRACT="$SLUG/includes/class-sc-ei-workflow-core-contract.php"
README="$SLUG/readme.txt"

require_marker() {
  local marker="$1"
  local file="$2"
  local label="$3"
  if ! grep -Fq -- "$marker" "$file"; then
    echo "Missing release marker ($label): $marker" >&2
    exit 1
  fi
}

# Use explicit checks instead of Bash array slicing for macOS Bash 3.2 compatibility.
require_marker "Plugin Name: Sustainable Catalyst Contact and Engagement Platform" "$MAIN" "plugin name"
require_marker "Version:     1.0.0" "$MAIN" "plugin version"
require_marker "SC_EI_DB_VERSION', '1.0.0'" "$MAIN" "database version"
require_marker "SC_EI_PLATFORM_SCHEMA_VERSION', '1.0.0'" "$MAIN" "platform schema"
require_marker "Stable tag: 1.0.0" "$README" "WordPress stable tag"

for table in platform_snapshots platform_migrations; do
  grep -Fq "\$sql_${table}" "$DB" || { echo "Platform table declaration missing: $table" >&2; exit 1; }
  grep -Fq "dbDelta( \$sql_${table} )" "$DB" || { echo "Platform dbDelta missing: $table" >&2; exit 1; }
done

grep -Fq "public const MIGRATION_KEY = 'v1_0_0_unified_contact_engagement_platform'" "$PLATFORM_REPO" || { echo "Stable migration key missing." >&2; exit 1; }
grep -Fq "'completed' === \$existing['status']" "$PLATFORM_REPO" || { echo "Idempotent migration completion check missing." >&2; exit 1; }
grep -Fq "'no_destructive_migration' => true" "$PLATFORM_REPO" || { echo "Non-destructive migration evidence missing." >&2; exit 1; }
grep -Fq "'content_hash'      => hash( 'sha256', \$json )" "$PLATFORM_REPO" || { echo "Readiness snapshot integrity hash missing." >&2; exit 1; }
grep -Fq "'production' === \$state && empty( \$readiness['ready_for_production'] )" "$PLATFORM_REPO" || { echo "Production readiness gate missing." >&2; exit 1; }
grep -Fq "'automatic_launch' => false" "$PLATFORM_REPO" || { echo "Human launch boundary missing." >&2; exit 1; }
grep -Fq "platform_no_auto_acceptance" "$PLATFORM_SCHEMA" || { echo "No automatic acceptance boundary missing." >&2; exit 1; }
grep -Fq "platform_no_auto_activation" "$PLATFORM_SCHEMA" || { echo "No automatic activation boundary missing." >&2; exit 1; }

grep -Fq "add_shortcode( 'sc_contact_engagement_platform'" "$PLATFORM_PUBLIC" || { echo "Unified public shortcode missing." >&2; exit 1; }
grep -Fq "SC_EI_Public::contact_hub" "$PLATFORM_PUBLIC" || { echo "Unified shortcode must compose the existing contact hub." >&2; exit 1; }
for legacy_shortcode in sc_contact_hub sc_contact_form sc_engagement_inquiry; do
  grep -Fq "add_shortcode( '$legacy_shortcode'" "$SLUG/includes/class-sc-ei-public.php" || { echo "Legacy shortcode missing: $legacy_shortcode" >&2; exit 1; }
done
grep -Fq "add_shortcode( 'sc_sender_portal'" "$SLUG/includes/class-sc-ei-portal-public.php" || { echo "Legacy sender portal shortcode missing." >&2; exit 1; }

grep -Fq "__( 'Contact & Engagement'" "$ADMIN" || { echo "Unified parent menu missing." >&2; exit 1; }
grep -Fq "'sc-engagement-intake-inquiries'" "$ADMIN" || { echo "Dedicated inquiries workspace missing." >&2; exit 1; }
grep -Fq "SC_EI_Admin::inquiries_page()" "$PLATFORM_ADMIN" || { echo "Legacy inquiry routing compatibility missing." >&2; exit 1; }
grep -Fq "SET PLATFORM ' . strtoupper( \$state )" "$PLATFORM_ADMIN" || { echo "Typed human launch control missing." >&2; exit 1; }
grep -Fq "Stable human-control boundary" "$PLATFORM_VIEW" || { echo "Platform boundary disclosure missing." >&2; exit 1; }

for capability in sc_intake_view_platform sc_intake_manage_platform sc_intake_snapshot_platform sc_intake_export_platform sc_intake_launch_platform; do
  grep -Fq "'$capability'" "$CAPS" || { echo "Platform capability missing: $capability" >&2; exit 1; }
done

grep -Fq "'/platform/status'" "$REST" || { echo "Read-only platform REST status missing." >&2; exit 1; }
grep -Fq "'read_only'    => true" "$REST" || { echo "Platform REST read-only marker missing." >&2; exit 1; }
grep -Fq "unset( \$command['payload_json'], \$command['result_json'], \$command['reason'], \$command['error_message'] )" "$REST" || { echo "Workflow Core REST command redaction missing." >&2; exit 1; }

# Inherited integration and safety boundaries remain required.
grep -Fq "SCHEMA_ID = 'sc-engagement-workflow-handoff/1.0'" "$CORE_CONTRACT" || { echo "Workflow Core handoff contract missing." >&2; exit 1; }
grep -Fq "recover_stale_outbox_claims" "$CORE_REPO" || { echo "Workflow Core outbox recovery missing." >&2; exit 1; }
if grep -Fq 'wp_remote_' "$PLATFORM_REPO" || grep -Fq 'wp_mail(' "$PLATFORM_REPO"; then echo "Platform repository may not perform arbitrary external delivery." >&2; exit 1; fi
if grep -Fq 'SC_EI_Inquiry_Repository::update_status' "$PLATFORM_REPO" || grep -Fq 'SC_EI_Fit_Repository::finalize' "$PLATFORM_REPO" || grep -Fq 'SC_EI_Engagement_Repository::activate' "$PLATFORM_REPO"; then echo "Platform layer may not mutate authoritative business decisions." >&2; exit 1; fi

echo "==> PHP and JavaScript syntax"
while IFS= read -r -d '' file; do php -l "$file" >/dev/null; done < <(find "$SLUG" -name '*.php' -print0)
node --check "$SLUG/assets/js/public.js"
node --check "$SLUG/assets/js/admin.js"
bash -n "$SCRIPT_NAME"

echo "==> Complete release suite"
python3 - <<'PY_TESTS'
from pathlib import Path
import json, subprocess
root=Path('.')
command=json.loads((root/'composer.json').read_text(encoding='utf-8'))['scripts']['test']
result=subprocess.run(['bash','-lc',command], cwd=root)
raise SystemExit(result.returncode)
PY_TESTS

echo "==> Push-safe secret scan"
if grep -RInE --exclude="$SCRIPT_NAME" --exclude-dir=.git '(AIza[0-9A-Za-z_-]{20,}|sk-[0-9A-Za-z]{20,}|ghp_[0-9A-Za-z]{20,}|-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----)' .; then
  echo "Potential secret detected." >&2
  exit 1
fi

git add -A
if git diff --cached --quiet; then
  echo "No changes to commit."
else
  git commit -m "Build v1.0.0 Unified Contact and Engagement Platform"
fi

if [[ "$SKIP_PUSH" == "1" ]]; then
  echo "Validation complete; push skipped."
else
  git push origin main
fi

echo "Unified Contact and Engagement Platform v1.0.0 push workflow completed."
