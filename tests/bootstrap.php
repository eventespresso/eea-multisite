<?php
/**
 * Bootstrap for eea-multisite tests
 */
use EETests\bootstrap\AddonLoader;

$core_tests_dir = dirname(__FILE__, 3) . '/event-espresso-core/tests/';
//if still don't have $core_tests_dir, then let's check tmp folder.
if (! is_dir($core_tests_dir)) {
    $core_tests_dir = '/tmp/event-espresso-core/tests/';
}
require $core_tests_dir . 'includes/CoreLoader.php';
require $core_tests_dir . 'includes/AddonLoader.php';
define('EEA_MULTISITE_PLUGIN_DIR', dirname(__FILE__, 2) . '/');
define('EEA_MULTISITE_TESTS_DIR', EEA_MULTISITE_PLUGIN_DIR . 'tests/');
$addon_loader = new AddonLoader(
    EEA_MULTISITE_TESTS_DIR,
    EEA_MULTISITE_PLUGIN_DIR,
    'eea-multisite.php'
);
$addon_loader->init();
require EEA_MULTISITE_TESTS_DIR . 'includes/EE_Multisite_UnitTestCase.class.php';
//tell core to not do any db collation changes. Those will break transactions.
add_filter('FHEE__EE_DMS_Core_4_9_0__verify_db_collations__check_overridden', '__return_true');
