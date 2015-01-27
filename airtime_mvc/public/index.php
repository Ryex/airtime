<?php

$configRun = false;
$extensions = get_loaded_extensions();
$airtimeSetup = false;

function showConfigCheckPage() {
    global $configRun;
    if (!$configRun) {
        // This will run any necessary setup we need if
        // configuration hasn't been initialized
        checkConfiguration();
    }

    require_once(CONFIG_PATH . 'config-check.php');
    die();
}

function isApiCall() {
    $path = $_SERVER['PHP_SELF'];
    return strpos($path, "api") !== false;
}

// Define application path constants
define('ROOT_PATH', dirname( __DIR__) . '/');
define('LIB_PATH', ROOT_PATH . 'library/');
define('BUILD_PATH', ROOT_PATH . 'build/');
define('SETUP_PATH', BUILD_PATH . 'airtime-setup/');
define('APPLICATION_PATH', ROOT_PATH . 'application/');
define('CONFIG_PATH', APPLICATION_PATH . 'configs/');

define("AIRTIME_CONFIG_STOR", "/etc/airtime/");

define('AIRTIME_CONFIG', 'airtime.conf');

require_once(LIB_PATH . "propel/runtime/lib/Propel.php");
require_once(CONFIG_PATH . 'conf.php');
require_once(SETUP_PATH . 'load.php');

// This allows us to pass ?config as a parameter to any page
// and get to the config checklist.
if (array_key_exists('config', $_GET)) {
    showConfigCheckPage();
}

/** Zend_Application */
require_once 'Zend/Application.php';
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

require_once (APPLICATION_PATH."/logging/Logging.php");
Logging::setLogPath('/var/log/airtime/zendphp.log');

// Create application, bootstrap, and run
try {
    $sapi_type = php_sapi_name();
    if (substr($sapi_type, 0, 3) == 'cli') {
        set_include_path(APPLICATION_PATH . PATH_SEPARATOR . get_include_path());
        require_once("Bootstrap.php");
    } else {
        $application->bootstrap()->run();
    }
} catch (Exception $e) {

    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);

    Logging::error($e->getMessage());
    if (VERBOSE_STACK_TRACE) {
        echo $e->getMessage();
        echo "<pre>";
        echo $e->getTraceAsString();
        echo "</pre>";
        Logging::error($e->getTraceAsString());
    } else {
        Logging::error($e->getTrace());
    }
    die();
}
