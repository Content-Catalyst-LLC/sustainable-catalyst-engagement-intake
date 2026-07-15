#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
VERSION="1.7.0"
SLUG="sustainable-catalyst-engagement-intake"
REPO_ROOT="${SLUG}-v${VERSION}-repo"
DIST="$ROOT/dist"
WORK="$(mktemp -d "${TMPDIR:-/tmp}/sc-ei-v170-package.XXXXXX")"
TEST_LOG="$WORK/tests.log"

cleanup(){ rm -rf "$WORK"; }
trap cleanup EXIT
trap 'rc=$?; echo "Packaging failed on line $LINENO (exit $rc)." >&2; exit $rc' ERR

for c in php python3 zip unzip find grep sha256sum rsync; do
  command -v "$c" >/dev/null || { echo "Missing required command: $c" >&2; exit 1; }
done

cd "$ROOT"
printf 'Validating PHP syntax...\n'
while IFS= read -r f; do php -l "$f" >/dev/null; done < <(find "$SLUG" tests -type f -name '*.php' | sort)

printf 'Running release suites...\n'
: > "$TEST_LOG"
for t in tests/*.php; do
  printf '=== %s ===\n' "$t" >> "$TEST_LOG"
  php "$t" >> "$TEST_LOG"
done

if command -v node >/dev/null; then
  while IFS= read -r f; do node --check "$f"; done < <(find "$SLUG" -type f -name '*.js' | sort)
fi

bash -n "$0"
bash -n PUSH_ENGAGEMENT_INTAKE_V170_CLEAN.sh
python3 -m json.tool composer.json >/dev/null

printf 'Scanning for common secret material...\n'
if grep -RInE --exclude-dir=.git --exclude-dir=dist --exclude='release-manifest.json' '(AIza[0-9A-Za-z_-]{20,}|sk-[0-9A-Za-z]{20,}|ghp_[0-9A-Za-z]{20,}|github_pat_[0-9A-Za-z_]{20,}|AKIA[0-9A-Z]{16}|-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----)' .; then
  echo 'Potential secret material detected.' >&2
  exit 1
fi

printf 'Generating release manifest...\n'
python3 - "$ROOT" "$VERSION" "$TEST_LOG" <<'PY'
import hashlib
import json
import pathlib
import re
import sys

root = pathlib.Path(sys.argv[1])
version = sys.argv[2]
text = pathlib.Path(sys.argv[3]).read_text(encoding='utf-8')
lines = text.splitlines()

files = {}
for path in sorted(root.rglob('*')):
    if not path.is_file():
        continue
    rel = path.relative_to(root).as_posix()
    if (
        rel == 'release-manifest.json'
        or rel.startswith('dist/')
        or '/.git/' in f'/{rel}/'
        or rel.endswith('.DS_Store')
    ):
        continue
    files[rel] = hashlib.sha256(path.read_bytes()).hexdigest()

passes = sum(1 for line in lines if line.startswith('PASS:'))
for block in text.split('=== '):
    if 'PASS:' in block:
        continue
    for pattern in (r'\((\d+) assertions\)', r'\((\d+) checks\)'):
        matches = re.findall(pattern, block)
        if matches:
            passes += int(matches[-1])
            break

manifest = {
    'name': 'Sustainable Catalyst Contact and Engagement Platform',
    'version': version,
    'release': 'Billing, Invoicing, and Payment Handoffs',
    'plugin_slug': 'sustainable-catalyst-engagement-intake',
    'requires_wordpress': '6.5',
    'requires_php': '8.1',
    'database_version': '1.7.0',
    'schemas': {
        'review': '1.0.0',
        'communication': '1.0.0',
        'privacy': '1.0.0',
        'fit': '1.0.0',
        'portal': '1.8.0',
        'workflow': '1.3.0',
        'graph': '1.0.0',
        'engagement': '1.2.0',
        'analytics': '1.1.0',
        'service_intelligence': '1.0.0',
        'billing': '1.0.0',
        'hardening': '1.0.0',
        'workflow_core': '1.0.0',
        'platform': '1.7.0',
        'lifecycle': '1.0.0',
        'support': '1.0.1',
        'calendar': '1.0.1',
        'proposal_governance': '1.0.1',
        'workspace': '1.0.0',
    },
    'migration_keys': [
        'v1_0_0_unified_contact_engagement_platform',
        'v1_0_2_production_readiness_live_validation',
        'v1_0_3_pilot_findings_public_launch_hardening',
        'v1_1_0_advisory_operations_engagement_lifecycle',
        'v1_1_1_inquiry_persistence_lifecycle_reliability',
        'v1_2_0_support_operations_product_intelligence',
        'v1_2_1_support_operations_cross_product_reliability',
        'v1_3_0_microsoft_teams_calendar_coordination',
        'v1_3_1_scheduling_reminder_timezone_reliability',
        'v1_4_0_proposals_statements_of_work_engagement_approvals',
        'v1_4_1_proposal_versioning_approval_reliability',
        'v1_5_0_secure_client_workspace_collaboration',
        'v1_6_0_engagement_analytics_service_intelligence',
        'v1_7_0_billing_invoicing_payment_handoffs',
    ],
    'recommended_shortcode': '[sc_contact_engagement_platform]',
    'support_shortcode': '[sc_support_request]',
    'portal_shortcode': '[sc_sender_portal]',
    'billing': {
        'invoice_schema': 'sc-engagement-invoice/1.0',
        'payment_handoff_schema': 'sc-payment-handoff/1.0',
        'event_schema': 'sc-billing-event/1.0',
        'tables': [
            'billing_profiles',
            'invoices',
            'invoice_items',
            'invoice_versions',
            'payment_handoffs',
            'billing_events',
        ],
        'engagement_linked': True,
        'invoice_version_snapshots': True,
        'sha256_snapshot_hashes': True,
        'typed_confirmation': True,
        'optimistic_locking': True,
        'event_compensation': True,
        'external_https_handoffs_only': True,
        'idempotent_handoffs': True,
        'idempotent_provider_events': True,
        'human_review_required': True,
        'sender_allowlist_projection': True,
        'stores_payment_instruments': False,
        'stores_provider_secrets': False,
        'automatic_collection': False,
        'automatic_reconciliation': False,
    },
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
        'fresh_service_intelligence_snapshot': True,
        'billing_operational_blockers': 0,
        'typed_human_launch_action': True,
    },
    'live_validation': [
        'database and v1.7.0 billing migration contract',
        'temporary inquiry and governed lifecycle',
        'support case and cross-product privacy contract',
        'Teams scheduling and reminder workflow',
        'proposal SOW approval conversion and change request workflow',
        'temporary client workspace and sender-safe collaboration',
        'aggregate service-intelligence privacy and evidence checks',
        'temporary engagement-linked billing profile and invoice',
        'versioned human-reviewed invoice issue',
        'sensitive payment metadata rejection',
        'HTTPS payment handoff and idempotent replay',
        'settlement event and paid invoice reconciliation',
        'sender-safe invoice and handoff projection',
        'complete cleanup',
    ],
    'boundaries': {
        'automatic_launch': False,
        'automatic_acceptance': False,
        'automatic_proposal': False,
        'automatic_contract': False,
        'electronic_signature': False,
        'automatic_activation': False,
        'automatic_payment': False,
        'automatic_invoice_issue': False,
        'automatic_reconciliation': False,
        'stores_payment_instruments': False,
        'stores_bank_credentials': False,
        'stores_provider_secrets': False,
        'automatic_meeting_booking': False,
        'automatic_workspace_enrollment': False,
        'automatic_scope_expansion': False,
        'automatic_service_decisions': False,
        'sender_ranking': False,
    },
    'validation': {
        'plugin_php_files_linted': sum(1 for p in (root / 'sustainable-catalyst-engagement-intake').rglob('*.php')),
        'php_files_including_tests_linted': sum(
            1
            for base in [root / 'sustainable-catalyst-engagement-intake', root / 'tests']
            for p in base.rglob('*.php')
        ),
        'javascript_bundles_checked': sum(1 for p in (root / 'sustainable-catalyst-engagement-intake').rglob('*.js')),
        'test_suites': sum(1 for p in (root / 'tests').glob('*.php')),
        'explicit_pass_assertions': passes,
        'secret_scan': True,
        'push_script_bash_syntax': True,
        'zip_crc_verified': True,
    },
    'files': files,
}

(root / 'release-manifest.json').write_text(json.dumps(manifest, indent=2) + '\n', encoding='utf-8')
PY

rm -rf "$DIST"
mkdir -p "$DIST" "$WORK/$REPO_ROOT"

printf 'Creating installable plugin archive...\n'
(
  cd "$ROOT"
  zip -qr "$DIST/${SLUG}-v${VERSION}.zip" "$SLUG" -x '*/.DS_Store' '*/__MACOSX/*'
)

printf 'Creating repository archive...\n'
rsync -a --delete \
  --exclude='.git/' \
  --exclude='dist/' \
  --exclude='.DS_Store' \
  --exclude='__MACOSX/' \
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
