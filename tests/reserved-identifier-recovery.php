<?php
$root = dirname(__DIR__);
$db = file_get_contents($root . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-database.php');
$checks = array(
    'all schema identifiers are quoted' => 4 === substr_count($db, '`schema` varchar(') && false === strpos($db, "\n\t\t\tschema varchar("),
    'all affected tables receive native recovery' => false !== strpos($db, 'create_table_if_missing( $proposal_approvals, $sql_proposal_approvals )')
        && false !== strpos($db, 'create_table_if_missing( $service_intelligence_findings, $sql_service_intelligence_findings )')
        && false !== strpos($db, 'create_table_if_missing( $payment_handoffs, $sql_payment_handoffs )')
        && false !== strpos($db, 'create_table_if_missing( $platform_handoffs, $sql_platform_handoffs )'),
    'critical inventory contains all four tables' => false !== strpos($db, "array( 'proposal_approvals', 'service_intelligence_findings', 'payment_handoffs', 'platform_handoffs' )"),
);
foreach ($checks as $label => $ok) {
    if (!$ok) { fwrite(STDERR, "FAIL: {$label}\n"); exit(1); }
    echo "PASS: {$label}\n";
}
echo 'PASS: reserved-identifier table recovery contract (' . count($checks) . " checks)\n";
