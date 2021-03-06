<?php
require_once CONFIG_PATH . "conf.php";
$CC_CONFIG = Config::getConfig();

require_once CONFIG_PATH . "ACL.php";
require_once 'propel/runtime/lib/Propel.php';

// Since we initialize the database during the configuration check,
// check the $configRun global to avoid reinitializing unnecessarily
if (!isset($configRun) || !$configRun) {
    Propel::init(CONFIG_PATH . 'airtime-conf-production.php');
}

require_once CONFIG_PATH . "constants.php";
require_once 'Preference.php';
require_once 'Locale.php';
require_once "DateHelper.php";
require_once "HTTPHelper.php";
require_once "OsPath.php";
require_once "Database.php";
require_once "Timezone.php";
require_once "Auth.php";
require_once __DIR__.'/forms/helpers/ValidationTypes.php';
require_once __DIR__.'/forms/helpers/CustomDecorators.php';
require_once __DIR__.'/controllers/plugins/RabbitMqPlugin.php';
require_once __DIR__.'/upgrade/Upgrades.php';

require_once (APPLICATION_PATH . "/logging/Logging.php");
Logging::setLogPath('/var/log/airtime/zendphp.log');

Config::setAirtimeVersion();
require_once (CONFIG_PATH . 'navigation.php');

Zend_Validate::setDefaultNamespaces("Zend");

Application_Model_Auth::pinSessionToClient(Zend_Auth::getInstance());

$front = Zend_Controller_Front::getInstance();
$front->registerPlugin(new RabbitMqPlugin());

//localization configuration
Application_Model_Locale::configureLocalization();

//only to avoid complaints for now, we should never rely on the default timezone in Airtime.
date_default_timezone_set("UTC");

/* The bootstrap class should only be used to initialize actions that return a view.
   Actions that return JSON will not use the bootstrap class! */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initDoctype()
    {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->doctype('XHTML1_STRICT');
    }

    protected function _initGlobals()
    {
        $view = $this->getResource('view');
        $baseUrl = Application_Common_OsPath::getBaseDir();

        $view->headScript()->appendScript("var baseUrl = '$baseUrl'");
    }

    protected function _initHeadLink()
    {
        $CC_CONFIG = Config::getConfig();

        $view = $this->getResource('view');

        $baseUrl = Application_Common_OsPath::getBaseDir();

        $view->headLink()->appendStylesheet($baseUrl.'css/bootstrap.css?'.$CC_CONFIG['airtime_version']);
        $view->headLink()->appendStylesheet($baseUrl.'css/redmond/jquery-ui-1.8.8.custom.css?'.$CC_CONFIG['airtime_version']);
        $view->headLink()->appendStylesheet($baseUrl.'css/pro_dropdown_3.css?'.$CC_CONFIG['airtime_version']);
        $view->headLink()->appendStylesheet($baseUrl.'css/qtip/jquery.qtip.min.css?'.$CC_CONFIG['airtime_version']);
        $view->headLink()->appendStylesheet($baseUrl.'css/styles.css?'.$CC_CONFIG['airtime_version']);
        $view->headLink()->appendStylesheet($baseUrl.'css/masterpanel.css?'.$CC_CONFIG['airtime_version']);
        $view->headLink()->appendStylesheet($baseUrl.'css/tipsy/jquery.tipsy.css?'.$CC_CONFIG['airtime_version']);

        //jPlayer css.
        $view->headLink()->appendStylesheet($baseUrl.'css/jplayer.airtime.audio.preview.css?'.$CC_CONFIG['airtime_version']);
        $view->headLink()->appendStylesheet($baseUrl.'css/jquery-ui-timepicker-addon.css?'.$CC_CONFIG['airtime_version']);
    }

    protected function _initHeadScript()
    {
        $CC_CONFIG = Config::getConfig();

        $view = $this->getResource('view');

        $baseUrl = Application_Common_OsPath::getBaseDir();

        $view->headScript()->appendFile($baseUrl.'js/libs/jquery-1.8.3.min.js?'.$CC_CONFIG['airtime_version'],'text/javascript');
        $view->headScript()->appendFile($baseUrl.'js/libs/jquery-ui-1.8.24.min.js?'.$CC_CONFIG['airtime_version'],'text/javascript');
        $view->headScript()->appendFile($baseUrl.'js/bootstrap/bootstrap.js?'.$CC_CONFIG['airtime_version'],'text/javascript');

        $view->headScript()->appendFile($baseUrl.'js/libs/underscore-min.js?'.$CC_CONFIG['airtime_version'],'text/javascript');

        $view->headScript()->appendFile($baseUrl.'js/qtip/jquery.qtip.js?'.$CC_CONFIG['airtime_version'],'text/javascript');
        $view->headScript()->appendFile($baseUrl.'js/jplayer/jquery.jplayer.min.js?'.$CC_CONFIG['airtime_version'], 'text/javascript');
        $view->headScript()->appendFile($baseUrl.'js/sprintf/sprintf-0.7-beta1.js?'.$CC_CONFIG['airtime_version'],'text/javascript');
        $view->headScript()->appendFile($baseUrl.'js/cookie/jquery.cookie.js?'.$CC_CONFIG['airtime_version'],'text/javascript');
        $view->headScript()->appendFile($baseUrl.'js/i18n/jquery.i18n.js?'.$CC_CONFIG['airtime_version'],'text/javascript');
        $view->headScript()->appendFile($baseUrl.'locale/general-translation-table?'.$CC_CONFIG['airtime_version'],'text/javascript');
        $view->headScript()->appendFile($baseUrl.'locale/datatables-translation-table?'.$CC_CONFIG['airtime_version'],'text/javascript');
        $view->headScript()->appendScript("$.i18n.setDictionary(general_dict)");

        $view->headScript()->appendFile($baseUrl.'js/timepicker/jquery-ui-timepicker-addon.js?'.$CC_CONFIG['airtime_version'],'text/javascript');


        $view->headScript()->appendScript("var baseUrl='$baseUrl'");

		//These timezones are needed to adjust javascript Date objects on the client to make sense to the user's set timezone
		//or the server's set timezone.
        $serverTimeZone = new DateTimeZone(Application_Model_Preference::GetDefaultTimezone());
        $now = new DateTime("now", $serverTimeZone);
        $offset = $now->format("Z") * -1;
        $view->headScript()->appendScript("var serverTimezoneOffset = {$offset}; //in seconds");

        if (class_exists("Zend_Auth", false) && Zend_Auth::getInstance()->hasIdentity()) {
            $userTimeZone = new DateTimeZone(Application_Model_Preference::GetUserTimezone());
            $now = new DateTime("now", $userTimeZone);
            $offset = $now->format("Z") * -1;
            $view->headScript()->appendScript("var userTimezoneOffset = {$offset}; //in seconds");
        }

        //scripts for now playing bar
        $view->headScript()->appendFile($baseUrl.'js/airtime/airtime_bootstrap.js?'.$CC_CONFIG['airtime_version'],'text/javascript');
        $view->headScript()->appendFile($baseUrl.'js/airtime/dashboard/helperfunctions.js?'.$CC_CONFIG['airtime_version'],'text/javascript');
        $view->headScript()->appendFile($baseUrl.'js/airtime/dashboard/dashboard.js?'.$CC_CONFIG['airtime_version'],'text/javascript');
        $view->headScript()->appendFile($baseUrl.'js/airtime/dashboard/versiontooltip.js?'.$CC_CONFIG['airtime_version'],'text/javascript');
        $view->headScript()->appendFile($baseUrl.'js/tipsy/jquery.tipsy.js?'.$CC_CONFIG['airtime_version'],'text/javascript');

        $view->headScript()->appendFile($baseUrl.'js/airtime/common/common.js?'.$CC_CONFIG['airtime_version'],'text/javascript');

        //scripts for the jPlayer preview bar.
        $view->headScript()->appendFile($baseUrl.'js/jplayer/jplayer.playlist.min.js?'.$CC_CONFIG['airtime_version'], 'text/javascript');
        $view->headScript()->appendFile($baseUrl.'js/airtime/audiopreview/jPlayerWindow.js?'.$CC_CONFIG['airtime_version'],'text/javascript');

        $user = Application_Model_User::getCurrentUser();
        if (!is_null($user)){
            $userType = $user->getType();
        } else {
            $userType = "";
        }
        $view->headScript()->appendScript("var userType = '$userType';");

        if (isset($CC_CONFIG['demo']) && $CC_CONFIG['demo'] == 1) {
            $view->headScript()->appendFile($baseUrl.'js/libs/google-analytics.js?'.$CC_CONFIG['airtime_version'],'text/javascript');
        }

        if (Application_Model_Preference::GetPlanLevel() != "disabled"
                && !($_SERVER['REQUEST_URI'] == $baseUrl.'Dashboard/stream-player' ||
                     strncmp($_SERVER['REQUEST_URI'], $baseUrl.'audiopreview/audio-preview', strlen($baseUrl.'audiopreview/audio-preview'))==0)) {

            $client_id = Application_Model_Preference::GetClientId();
            $view->headScript()->appendScript("var livechat_client_id = '$client_id';");
            $view->headScript()->appendFile($baseUrl . 'js/airtime/common/livechat.js?'.$CC_CONFIG['airtime_version'], 'text/javascript');
        }

    }

    protected function _initViewHelpers()
    {
        $view = $this->getResource('view');
        $view->addHelperPath(APPLICATION_PATH . 'views/helpers', 'Airtime_View_Helper');
    }

    protected function _initTitle()
    {
        $view = $this->getResource('view');
        $view->headTitle(Application_Model_Preference::GetHeadTitle());
    }

    protected function _initZFDebug()
    {

        Zend_Controller_Front::getInstance()->throwExceptions(false);

        /*
        if (APPLICATION_ENV == "development") {
            $autoloader = Zend_Loader_Autoloader::getInstance();
            $autoloader->registerNamespace('ZFDebug');

            $options = array(
                'plugins' => array('Variables',
                                   'Exception',
                                   'Memory',
                                   'Time')
            );
            $debug = new ZFDebug_Controller_Plugin_Debug($options);

            $this->bootstrap('frontController');
            $frontController = $this->getResource('frontController');
            $frontController->registerPlugin($debug);
        }
        */
    }

    protected function _initRouter()
    {
        $front = Zend_Controller_Front::getInstance();
        $router = $front->getRouter();
        $front->setBaseUrl(Application_Common_OsPath::getBaseDir());

        $router->addRoute(
            'password-change',
            new Zend_Controller_Router_Route('password-change/:user_id/:token', array(
                'module' => 'default',
                'controller' => 'login',
                'action' => 'password-change',
            )));
    }

    protected function _initAutoload () {

    	// configure new autoloader
    	$autoloader = new Zend_Application_Module_Autoloader (array ('namespace' => '', 'basePath' => APPLICATION_PATH));

    	// autoload form validators & filters definition
    	$autoloader->addResourceType ('Filter', 'forms/filters', 'Filter_');
    	$autoloader->addResourceType ('Validator', 'forms/validators', 'Validate_');

    	$autoloader->addResourceType ('Interface', 'models/interfaces', 'Interface_');
    	$autoloader->addResourceType ('Presentation', 'models/presentation', 'Presentation_');
    	$autoloader->addResourceType ('Format', 'models/formatters', 'Format_');
    	$autoloader->addResourceType ('Strategy', 'models/strategy', 'Strategy_');
    }
}
