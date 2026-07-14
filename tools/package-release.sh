#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
VERSION="1.2.0"
SLUG="sustainable-catalyst-engagement-intake"
REPO_ROOT="${SLUG}-v${VERSION}-repo"
DIST="${ROOT}/dist"
WORK="$(mktemp -d "${TMPDIR:-/tmp}/sc-ei-v120-package.XXXXXX")"
TEST_LOG="${WORK}/tests.log"

cleanup() { rm -rf "$WORK"; }
trap cleanup EXIT
trap 'rc=$?; echo "Packaging failed on line $LINENO (exit $rc)." >&2; exit $rc' ERR

for command_name in php python3 zip unzip find grep sha256sum rsync; do
  command -v "$command_name" >/dev/null 2>&1 || { echo "Missing required command: $command_name" >&2; exit 1; }
done

cd "$ROOT"

printf 'Validating PHP syntax...\n'
while IFS= read -r file; do php -l "$file" >/dev/null; done < <(find "$SLUG" tests -type f -name '*.php' | sort)

printf 'Running release suites...\n'
: > "$TEST_LOG"
for test_file in tests/*.php; do php "$test_file" >> "$TEST_LOG"; done

if command -v node >/dev/null 2>&1; then
  while IFS= read -r file; do node --check "$file"; done < <(find "$SLUG" -type f -name '*.js' | sort)
fi

bash -n "$0"
bash -n PUSH_ENGAGEMENT_INTAKE_V120_CLEAN.sh
python3 -m json.tool composer.json >/dev/null

printf 'Scanning for common secret material...\n'
if grep -RInE --exclude-dir=.git --exclude-dir=dist --exclude='release-manifest.json' \
  '(AIza[0-9A-Za-z_-]{20,}|sk-[0-9A-Za-z]{20,}|ghp_[0-9A-Za-z]{20,}|github_pat_[0-9A-Za-z_]{20,}|AKIA[0-9A-Z]{16}|-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----)' .; then
  echo 'Potential secret material detected.' >&2
  exit 1
fi

printf 'Generating release manifest...\n'
python3 - "$ROOT" "$VERSION" "$TEST_LOG" <<'PY'
import hashlib, json, pathlib, sys
root = pathlib.Path(sys.argv[1])
version = sys.argv[2]
test_log = pathlib.Path(sys.argv[3])
files = {}
for path in sorted(root.rglob('*')):
    if not path.is_file():
        continue
    rel = path.relative_to(root).as_posix()
    if rel == 'release-manifest.json' or rel.startswith('dist/') or '/.git/' in f'/{rel}/' or rel.endswith('/.DS_Store') or rel == '.DS_Store':
        continue
    files[rel] = hashlib.sha256(path.read_bytes()).hexdigest()
manifest = {
    'name': 'Sustainable Catalyst Contact and Engagement Platform',
    'version': version,
    'release': 'Support Operations and Product Intelligence Integration',
    'plugin_slug': 'sustainable-catalyst-engagement-intake',
    'text_domain': 'sustainable-catalyst-engagement-intake',
    'requires_wordpress': '6.5',
    'requires_php': '8.1',
    'database_version': '1.2.0',
    'schemas': {
        'review': '1.0.0', 'communication': '1.0.0', 'privacy': '1.0.0', 'fit': '1.0.0',
        'portal': '1.4.0', 'workflow': '1.1.0', 'graph': '1.0.0', 'engagement': '1.1.0',
        'analytics': '1.0.0', 'hardening': '1.0.0', 'workflow_core': '1.0.0', 'platform': '1.2.0',
        'lifecycle': '1.0.0', 'support': '1.0.0'
    },
    'migration_keys': [
        'v1_0_0_unified_contact_engagement_platform',
        'v1_0_2_production_readiness_live_validation',
        'v1_0_3_pilot_findings_public_launch_hardening',
        'v1_1_0_advisory_operations_engagement_lifecycle',
        'v1_1_1_inquiry_persistence_lifecycle_reliability',
        'v1_2_0_support_operations_product_intelligence'
    ],
    'recommended_shortcode': '[sc_contact_engagement_platform]',
    'routed_entries': [
        'general', 'support', 'advisory', 'ai-assurance', 'evidence-systems', 'knowledge-architecture', 'technical-storytelling',
        'responsible-ai', 'collaboration', 'media', 'technical', 'partnership', 'workshop', 'monthly-advisory'
    ],
    'legacy_shortcodes': ['[sc_contact_hub]', '[sc_contact_form]', '[sc_engagement_inquiry]', '[sc_sender_portal]'],
    'support_shortcode': '[sc_support_request]',
    'production_gate': {
        'required_score': 100,
        'required_failures': 0,
        'warnings': 0,
        'live_validation_max_age_days': 7,
        'backup_attestation_max_age_days': 7,
        'external_mail_evidence_max_age_days': 14,
        'pilot_evidence_max_age_days': 14,
        'minimum_controlled_inquiries': 5,
        'operational_blockers': 0,
        'typed_human_launch_action': True
    },
    'live_validation': [
        'version and database contract', 'published page and shortcode contracts',
        'cron schedules and callbacks', 'rendered accessibility contract',
        'duplicate fingerprint and request lock', 'routed entry contracts',
        'clean upload acceptance and disguised executable rejection', 'protected storage probe',
        'strict-mode inquiry persistence and status transition', 'sender portal token verification',
        'private file integrity and deletion', 'WordPress mail transport acceptance',
        'temporary artifact cleanup', 'lifecycle schema and migration contract',
        'lifecycle cron callback and overdue-work gate', 'sender-safe lifecycle projection',
        'support schema and migration contract', 'real support-case creation and governed triage transition',
        'sender-safe support projection', 'personal-data rejection and privacy-safe intelligence signal storage'
    ],
    'lifecycle': {
        'stages': ['new_inquiry', 'under_review', 'needs_information', 'qualified', 'meeting_requested',
            'meeting_scheduled', 'proposal_preparation', 'proposal_sent', 'accepted',
            'active_engagement', 'completed', 'declined', 'archived'],
        'tables': ['lifecycle_events', 'lifecycle_notes', 'lifecycle_tasks'],
        'typed_human_transitions': True,
        'automatic_acceptance': False,
        'automatic_rejection': False,
        'automatic_scheduling': False,
        'automatic_proposal_publication': False,
        'automatic_engagement_activation': False,
        'internal_notes_sender_visible': False,
        'task_email_default_enabled': False
    },
    'support_operations': {
        'handoff_schema': 'sc-product-support-handoff/1.0',
        'tables': ['support_cases', 'support_case_events', 'support_case_links', 'support_signals'],
        'stages': ['new_support_request', 'triage', 'needs_information', 'reproducing', 'known_issue',
            'workaround_provided', 'fix_planned', 'resolved', 'closed'],
        'products': ['workbench', 'decision-studio', 'research-lab', 'knowledge-library', 'site-intelligence',
            'research-librarian', 'platform-core', 'feature-suggestions', 'contact-engagement', 'other'],
        'private_cases': True,
        'sender_safe_projection': True,
        'personal_data_in_signals': False,
        'automatic_case_resolution': False,
        'automatic_feature_decisions': False
    },
    'pilot_checklist': [
        'general inquiry', 'advisory inquiry', 'AI Assurance inquiry', 'private upload',
        'administrative notification', 'sender acknowledgment', 'portal isolation',
        'mobile and browser validation', 'rollback verification'
    ],
    'boundaries': {
        'automatic_launch': False, 'automatic_acceptance': False, 'automatic_fit_decision': False,
        'automatic_proposal': False, 'automatic_contract': False, 'automatic_activation': False,
        'automatic_payment': False, 'arbitrary_webhook_delivery': False,
        'unverified_inbound_commands': False
    },
    'validation': {
        'plugin_php_files_linted': sum(1 for p in (root/'sustainable-catalyst-engagement-intake').rglob('*.php')),
        'php_files_including_tests_linted': sum(1 for base in [root/'sustainable-catalyst-engagement-intake', root/'tests'] for p in base.rglob('*.php')),
        'javascript_bundles_checked': sum(1 for p in (root/'sustainable-catalyst-engagement-intake').rglob('*.js')),
        'test_suites': sum(1 for p in (root/'tests').glob('*.php')),
        'explicit_pass_assertions': sum(1 for line in test_log.read_text(encoding='utf-8').splitlines() if line.startswith('PASS:')),
        'secret_scan': True,
        'push_script_bash_syntax': True,
        'zip_crc_verified': True
    },
    'files': files
}
(root/'release-manifest.json').write_text(json.dumps(manifest, indent=2) + '\n', encoding='utf-8')
PY

rm -rf "$DIST"
mkdir -p "$DIST" "$WORK/$REPO_ROOT"

printf 'Creating installable plugin archive...\n'
zip -qr "$DIST/${SLUG}-v${VERSION}.zip" "$SLUG" -x '*/.DS_Store' '*/__MACOSX/*'

printf 'Creating repository archive...\n'
rsync -a --delete \
  --exclude='.git/' --exclude='dist/' --exclude='.DS_Store' --exclude='__MACOSX/' \
  "$ROOT/" "$WORK/$REPO_ROOT/"
(
  cd "$WORK"
  zip -qr "$DIST/${SLUG}-v${VERSION}-repo.zip" "$REPO_ROOT" -x '*/.DS_Store' '*/__MACOSX/*'
)

unzip -tq "$DIST/${SLUG}-v${VERSION}.zip" >/dev/null
unzip -tq "$DIST/${SLUG}-v${VERSION}-repo.zip" >/dev/null

(
  cd "$DIST"
  sha256sum "${SLUG}-v${VERSION}.zip" "${SLUG}-v${VERSION}-repo.zip" > "${SLUG}-v${VERSION}-SHA256.txt"
)

printf 'Created:\n  %s\n  %s\n  %s\n' \
  "$DIST/${SLUG}-v${VERSION}.zip" \
  "$DIST/${SLUG}-v${VERSION}-repo.zip" \
  "$DIST/${SLUG}-v${VERSION}-SHA256.txt"
