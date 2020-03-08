<?php

define('IN_MOBIQUO', 1);

if(isset($_GET['allowAccess']))
{
    echo "yes";
    exit;
}

if (isset($_GET['checkip']))
{
    print do_post_request(array('ip' => 1) , true);
}
else
{
    $output = 'Tapatalk Push Notification Status Monitor<br><br>';
    $output .= 'Push notification test: <b>';
    require_once(dirname(dirname(__FILE__)) . '/SSI.php');
    global $modSettings, $smcFunc;
    if(isset($modSettings['tp_push_key']) && !empty($modSettings['tp_push_key']))
    {
        $push_key = $modSettings['tp_push_key'];
        $return_status = do_post_request(array('test' => 1, 'key' => $push_key), true);
        if ($return_status === '1')
            $output .= 'Success</b>';
        else
            $output .= 'Failed</b><br />'.$return_status;
    }
    else
    {
        $output .= 'Failed</b><br />Please set Tapatalk API Key at forum option/setting<br />';
    }
    
    //$ip =  do_post_request(array('ip' => 1), true);
    $forum_url =  get_forum_path();

    $table_exist = mobi_table_exist('tapatalk_users') ?'Yes' : 'No';

    $output .="<br>Current forum url: ".$forum_url."<br>";
    //$output .="Current server IP: ".$ip."<br>";
    $output .="Tapatalk user table existence:".$table_exist."<br>";
    if(isset($modSettings['push_slug']))
    {
        $push_slug = @unserialize(base64_decode($modSettings['push_slug']));
        if(!empty($push_slug) && is_array($push_slug))
            $output .= 'Push Slug Status : ' . ($push_slug['stick'] == 1 ? 'Stick' : 'Free') . '<br />';
        if(isset($_GET['slug']))
            $output .= 'Push Slug Value: ' . $modSettings['push_slug'] . "<br /><br />";
    }
    $output .="<br>
<a href=\"http://tapatalk.com/api.php\" target=\"_blank\">Tapatalk API for Universal Forum Access</a> | <a href=\"http://tapatalk.com/build.php\" target=\"_blank\">Build Your Own</a><br>
For more details, please visit <a href=\"http://tapatalk.com\" target=\"_blank\">http://tapatalk.com</a>";
    echo $output;
}

function do_post_request($data, $pushTest = false)
{
    $push_url = 'http://push.tapatalk.com/push.php';
    require_once(dirname(__FILE__).'/lib/classTTConnection.php');
    $connection = new classTTConnection();
    $response = $connection->getContentFromSever($push_url, $data, 'POST');
    return $response;
}

function mobi_table_exist($table_name)
{
    global $smcFunc, $db_prefix, $db_name;
    $tb_prefix = preg_replace('/`'.$db_name.'`./', '', $db_prefix);
    db_extend();
    $tables = $smcFunc['db_list_tables'](false, $tb_prefix . "tapatalk_users");
    return !empty($tables);
}

function get_forum_path()
{
    $path =  '../';

    if (!empty($_SERVER['SCRIPT_NAME']) && !empty($_SERVER['HTTP_HOST']))
    {
        $path = $_SERVER['HTTP_HOST'];
        $path .= dirname(dirname($_SERVER['SCRIPT_NAME']));
    }
    return $path;
}

if (!function_exists('http_build_query')) {

    function http_build_query($data, $prefix = null, $sep = '', $key = '')
    {
        $ret = array();
        foreach ((array )$data as $k => $v) {
            $k = urlencode($k);
            if (is_int($k) && $prefix != null) {
                $k = $prefix . $k;
            }
 
            if (!empty($key)) {
                $k = $key . "[" . $k . "]";
            }
 
            if (is_array($v) || is_object($v)) {
                array_push($ret, http_build_query($v, "", $sep, $k));
            } else {
                array_push($ret, $k . "=" . urlencode($v));
            }
        }
 
        if (empty($sep)) {
            $sep = ini_get("arg_separator.output");
        }
 
        return implode($sep, $ret);
    }
}