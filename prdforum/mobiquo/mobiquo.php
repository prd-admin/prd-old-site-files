<?php

define('IN_MOBIQUO', true);
define('TT_ROOT', getcwd() . DIRECTORY_SEPARATOR);
// Get everything started up...

class ExttMbqBase {
    public static $requestName;
    public static $oMbqDataPage;
    public static $otherParameters = array();
}
$GLOBALS['exttMbqVarArr'] = array();    /* used for global variables */
$GLOBALS['exttMbqVarArr']['microtime'] = microtime();

if (function_exists('set_magic_quotes_runtime'))
    @set_magic_quotes_runtime(0);

define('MBQ_PATH', (($getcwd = getcwd()) ? $getcwd : '.') . '/');
define('MBQ_3RD_LIB_PATH', (($getcwd = getcwd()) ? $getcwd : '.') . '/lib/');
require_once(MBQ_3RD_LIB_PATH. "ExceptionHelper.php");

$mobiquo_dir = 'mobiquo';

include_once('lib/classTTJson.php');
include_once('lib/classTTConnection.php');
require('pretreat.php');
define('SMF', 1);
require('MbqDataPage.php');
require('lib/xmlrpc.inc');
require('lib/xmlrpcs.inc');
require('server_define.php');
require('mobiquo_common.php');
require('mobiquo_action.php');
require('env_setting.php');
require('smf_entry.php');
require('xmlrpcresp.php');

$rpcServer = new xmlrpc_server($server_param, false);
$rpcServer->setDebug(1);
$rpcServer->compress_response = true;
$rpcServer->response_charset_encoding = 'UTF-8';
$rpcServer->service($server_data);

exit;

?>