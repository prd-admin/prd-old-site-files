<?php
define('MBQ_PROTOCOL','web');
define('MBQ_DEBUG', 0);
define('IN_MOBIQUO', true);
define('TT_ROOT', getcwd() . DIRECTORY_SEPARATOR);
define('MBQ_PATH', getcwd() . DIRECTORY_SEPARATOR);
define('MBQ_FRAME_PATH', getcwd() . DIRECTORY_SEPARATOR . 'include'. DIRECTORY_SEPARATOR);
define('MBQ_3RD_LIB_PATH', getcwd() . DIRECTORY_SEPARATOR . 'lib'. DIRECTORY_SEPARATOR);

class ExttMbqBase {
    public static $requestName;
    public static $oMbqDataPage;
    public static $otherParameters = array();
}
error_reporting(0);
@ ob_start();
define('SMF', 1);
$forum_version = 'SMF 2.0';

// Do some cleaning, just in case.
foreach (array('db_character_set', 'cachedir') as $variable)
    if (isset($GLOBALS[$variable]))
        unset($GLOBALS[$variable]);

// Load the settings...
require_once(dirname(dirname(__FILE__)) . '/Settings.php');

// Make absolutely sure the cache directory is defined.
if ((empty($cachedir) || !file_exists($cachedir)) && file_exists($boarddir . '/cache'))
    $cachedir = $boarddir . '/cache';

ExttMbqBase::$otherParameters['sourcedir'] = $sourcedir;

// And important includes.
require_once($sourcedir . '/QueryString.php');
require_once('include/Subs.php');
require_once('include/error_control.php');
require_once('include/Load.php');

if (file_exists($sourcedir . '/DreamPortal.php'))
    require_once($sourcedir . '/DreamPortal.php');

require($sourcedir . '/Security.php');

// Using an pre-PHP5 version?
if (@version_compare(PHP_VERSION, '5') == -1)
    require_once($sourcedir . '/Subs-Compat.php');

// If $maintenance is set specifically to 2, then we're upgrading or something.
if (!empty($maintenance) && $maintenance == 2)
    db_fatal_error();

// Create a variable to store some SMF specific functions in.
$smcFunc = array();

// Initate the database connection and define some database functions to use.
loadDatabase();

// Load the settings from the settings table, and perform operations like optimizing.
reloadSettings();
$_SERVER['QUERY_STRING'] = '';

// Clean the request variables, add slashes, etc.
cleanRequest();
$context = array();

// Seed the random generator.
if (empty($modSettings['rand_seed']) || mt_rand(1, 250) == 69)
    smf_seed_generator();

// Before we get carried away, are we doing a scheduled task? If so save CPU cycles by jumping out!
if (isset($_GET['scheduled']))
{
    require_once($sourcedir . '/ScheduledTasks.php');
    AutoTask();
}

// Start the session. (assuming it hasn't already been.)
loadSession();
$sc = $_SESSION['session_value'];
$_GET[$_SESSION['session_var']] = $_SESSION['session_value'];
$_POST[$_SESSION['session_var']] = $_SESSION['session_value'];

// Load the user's cookie (or set as guest) and load their settings.
loadUserSettings();

// Load the current board's information.
loadBoard();

// Load the current user's permissions.
loadPermissions();

require('mobiquo_common.php');
require('config/config.php');

require_once(MBQ_PATH . '/logger.php');
require_once(MBQ_FRAME_PATH . '/MbqBaseStatus.php');
class MbqStatus extends MbqBaseStatus
{

    public function GetLoggedUserName()
    {
        global $user_info;
        return isset($user_info) ? $user_info['username'] : 'guest';
    }
    protected function GetMobiquoFileSytemDir()
    {
        return TT_ROOT;
    }
    protected function GetMobiquoDir()
    {
        return 'mobiquo';
    }
    protected function GetApiKey()
    {
        global $modSettings;
        return isset($modSettings['tp_push_key']) ? $modSettings['tp_push_key'] : '';
    }
    protected function GetForumUrl()
    {
        global $boardurl;
        return $boardurl;
    }
    protected function GetPushSlug()
    {
        global $modSettings;

        $push_slug = @unserialize(base64_decode($modSettings['push_slug']));
        return $push_slug;
    }

    protected function ResetPushSlug()
    {
        updateSettings(array('push_slug' => 0), true);
    }

    protected function GetBYOInfo()
    {
        global $modSettings;
        $app_banner_enable = isset($modSettings['tp_full_banner']) ? intval($modSettings['tp_full_banner']) : 1;
        $google_indexing_enabled = isset($modSettings['tp_google_indexing_enabled']) ? intval($modSettings['tp_google_indexing_enabled']) : 1;
        $facebook_indexing_enabled = isset($modSettings['tp_facebook_indexing_enabled']) ? intval($modSettings['tp_facebook_indexing_enabled']) : 1;
        $twitter_indexing_enabled = isset($modSettings['tp_twitter_indexing_enabled']) ? intval($modSettings['tp_twitter_indexing_enabled']) : 1;
        $TT_bannerControlData = isset($modSettings['tt_banner_control']) ? @unserialize($modSettings['tt_banner_control']) : false;
        $TT_updateTime = isset($modSettings['tt_banner_expire']) ? $modSettings['tt_banner_expire'] : 0;
        $tapatalk_dir = 'mobiquo';
        include_once('lib/classTTConnection.php');
        $TT_connection = new classTTConnection();
        $TT_connection->calcSwitchOptions($TT_bannerControlData, $app_banner_enable, $google_indexing_enabled, $facebook_indexing_enabled, $twitter_indexing_enabled);
        $TT_bannerControlData['update'] = $TT_updateTime;
        $TT_bannerControlData['banner_enable'] = $app_banner_enable;
        $TT_bannerControlData['google_enable'] = $google_indexing_enabled;
        $TT_bannerControlData['facebook_enable'] = $facebook_indexing_enabled;
        $TT_bannerControlData['twitter_enable'] = $twitter_indexing_enabled;
        return $TT_bannerControlData;
    }
    protected function ResetBYOInfo()
    {
        global $boardurl,$modSettings;
        $tapatalk_dir = 'mobiquo';
        $TT_bannerControlData = null;
        include_once('lib/classTTConnection.php');
        $TT_connection = new classTTConnection();
        $api_key = isset($modSettings['tp_push_key']) ? $modSettings['tp_push_key'] : '';
        $TT_bannerControlData = $TT_connection->getForumInfo($boardurl, $api_key);
        $tt_input = array();
        $tt_input['tt_banner_control'] = serialize($TT_bannerControlData);
        $tt_input['tt_banner_expire'] = time();
        updateSettings($tt_input);
    }
    protected function GetOtherPlugins()
    {
        global $sourcedir;
        require_once($sourcedir . '/Subs-Package.php');
        $instmods = loadInstalledPackages();
        $result = array();
        foreach($instmods as $plugin)
        {
            $result[] = array('name'=>$plugin['name'], 'version'=>$plugin['version']);
        }
        return $result;
    }
    public function UserIsAdmin()
    {
        global $user_info;

        if (isset($user_info) && $user_info['is_admin']) {
            return true;
        }
        return false;
    }
    protected function GetPluginVersion()
    {
        $tt_config = get_mobiquo_config();
        return $tt_config['version'];
    }
}
$mbqStatus = new MbqStatus();

