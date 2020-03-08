<?php
if (!defined('SMF'))
    die('Hacking attempt...');
global $board_info, $context, $boardurl, $modSettings;

$app_location = get_scheme_url();
$script_to_page = array(
   'home'          => 'home',
   'index'          => 'home',
   'topic'     => 'topic',
   'post'   => 'post',
   'forum'   => 'forum',
);
$page_type = isset($GLOBALS['exttMbqTempPageType']) && isset($script_to_page[$GLOBALS['exttMbqTempPageType']]) ? $script_to_page[$GLOBALS['exttMbqTempPageType']] : 'others';
$is_mobile_skin = false;
$app_forum_name = !empty($GLOBALS['mbname'])? $GLOBALS['mbname'] : '';
$tapatalk_dir = 'mobiquo';
$tapatalk_dir_url = $boardurl. '/mobiquo';
$api_key = isset($modSettings['tp_push_key']) ? $modSettings['tp_push_key'] : '';
$board_url = $boardurl;
$app_banner_enable = isset($modSettings['tp_full_banner']) ? intval($modSettings['tp_full_banner']) : 1;
$google_indexing_enabled = isset($modSettings['tp_google_indexing_enabled']) ? intval($modSettings['tp_google_indexing_enabled']) : 1;
$facebook_indexing_enabled = isset($modSettings['tp_facebook_indexing_enabled']) ? intval($modSettings['tp_facebook_indexing_enabled']) : 1;
$twitter_indexing_enabled = isset($modSettings['tp_twitter_indexing_enabled']) ? intval($modSettings['tp_twitter_indexing_enabled']) : 1;

$TT_expireTime = isset($modSettings['tt_banner_expire']) ? intval($modSettings['tt_banner_expire']) : null;
$TT_bannerControlData = isset($modSettings['tt_banner_control']) ? $modSettings['tt_banner_control']  : null;
if (!empty($TT_bannerControlData)){
    $TT_bannerControlData = @unserialize($TT_bannerControlData);
}
$forum_root = dirname(__FILE__);

require_once $forum_root  . '/lib/classTTConnection.php';

if (class_exists('classTTConnection')){
    $TT_connection = new classTTConnection();
    $TT_connection->calcSwitchOptions($TT_bannerControlData, $app_banner_enable, $google_indexing_enabled, $facebook_indexing_enabled, $twitter_indexing_enabled);
}

if(isset($TT_bannerControlData['byo_info']) && !empty($TT_bannerControlData['byo_info']))
{
    $app_rebranding_id = $TT_bannerControlData['byo_info']['app_rebranding_id'];
    $app_url_scheme = $TT_bannerControlData['byo_info']['app_url_scheme'];
    $app_icon_url = $TT_bannerControlData['byo_info']['app_icon_url'];
    $app_name = $TT_bannerControlData['byo_info']['app_name'];
    $app_alert_status = $TT_bannerControlData['byo_info']['app_alert_status'];
    $app_alert_message = $TT_bannerControlData['byo_info']['app_alert_message'];

    $app_android_id = $TT_bannerControlData['byo_info']['app_android_id'];
    $app_android_description = $TT_bannerControlData['byo_info']['app_android_description'];
    $app_banner_message_android = $TT_bannerControlData['byo_info']['app_banner_message_android'];
    $app_banner_message_android = preg_replace('/\r\n/','<br>',$app_banner_message_android);

    $app_ios_id = $TT_bannerControlData['byo_info']['app_ios_id'];
    $app_ios_description = $TT_bannerControlData['byo_info']['app_ios_description'];
    $app_banner_message_ios = $TT_bannerControlData['byo_info']['app_banner_message_ios'];
    $app_banner_message_ios = preg_replace('/\r\n/','<br>',$app_banner_message_ios);
}


$twitterfacebook_card_enabled = isset($modSettings['tp_deep_link_enabled']) ? $modSettings['tp_deep_link_enabled'] : '';
$twc_site = isset($TT_bannerControlData['twitter_account']) && !empty($TT_bannerControlData['twitter_account']) ? $TT_bannerControlData['twitter_account'] : "tapatalk";
$twc_title = isset($context['page_title_html_safe']) ? $context['page_title_html_safe'] : '';
$twc_description = ($page_type == 'forum') && isset($board_info['description']) ? $board_info['description'] : $twc_title;
if (file_exists($forum_root .'/smartbanner/head.inc.php'))
    include($forum_root .'/smartbanner/head.inc.php');

$hide_forums = array();
if (isset($modSettings['boards_hide_for_tapatalk'])){
    foreach(explode(',', $modSettings['boards_hide_for_tapatalk']) as $hide_forum){
        $hide_forum = intval($hide_forum);
        if (!empty($hide_forum)){
            $hide_forums[] = $hide_forum;
        }
    }
}
//$context['html_headers'] .= $app_head_include;
if (isset($context['html_headers']) && isset($app_head_include) && (!isset($context['current_board']) || !in_array($context['current_board'], $hide_forums))){
    $context['html_headers'] .= $app_head_include;
}

if (!isset($context['tapatalk_body_hook']))
    $context['tapatalk_body_hook'] = '';

$context['tapatalk_body_hook'] .= '
<!-- Tapatalk Detect body start -->
<script type="text/javascript">if (typeof(tapatalkDetect) == "function") tapatalkDetect();</script>
<!-- Tapatalk Detect banner body end -->

';

function get_scheme_url()
{
    global $boardurl, $user_info, $context, $exttMbqTempPageType, $modSettings, $options, $board, $topic;

    $baseUrl = $boardurl;
    $baseUrl = preg_replace('/https?:\/\//i', '', $baseUrl);

    $location = 'index';
    $other_info = array();
    //is action? 'pm', 'profile', 'login2', 'login', 'search2'
    if(isset($_GET['action']) && !empty($_GET['action']))
    {
        if($_GET['action'] == 'pm')
            $location = 'message';
        else if($_GET['action'] == 'profile')
        {
            $location = 'profile';
            if(isset($_GET['u']) && !empty($_GET['u']))
                $other_info[] = 'uid='.$_GET['u'];
            else if(!empty($user_info['id']))
                $other_info[] = 'uid='.$user_info['id'];
        }
        else if($_GET['action'] == 'login2' || $_GET['action'] == 'login')
            $location = 'login';
        else if($_GET['action'] == 'search2')
            $location = 'search';
        else if($_GET['action'] == 'who')
            $location = 'online';
    }
    //Query string topic=36.msg123 board=1.0 topic=36.0
    else
    {
        $fid = 0;
        if(is_array($board) && array_key_exists('id',$board))
        {
            $fid = $board['id'];
        }
        else if(!is_array($board))
        {
            $fid = $board;
        }

        if (!empty($board) && empty($topic))
        {
            $location = 'forum';
            $other_info[] = 'fid='. $fid;
            
            $topics_per_page = empty($modSettings['disableCustomPerPage']) && !empty($options['topics_per_page']) && !WIRELESS ? $options['topics_per_page'] : $modSettings['defaultMaxTopics'];
            $current_page = isset($_REQUEST['start']) ? ($_REQUEST['start'] / $topics_per_page + 1) : 1;
            
            $other_info[] = 'page='.$current_page;
            $other_info[] = 'perpage='.$topics_per_page;
        }
        else if (!empty($topic))
        {
            $messages_per_page = empty($modSettings['disableCustomPerPage']) && !empty($options['messages_per_page']) && !WIRELESS ? $options['messages_per_page'] : $modSettings['defaultMaxMessages'];
            
            $other_info[] = 'fid='. $fid;
            $other_info[] = 'tid='.$topic;
            $other_info[] = 'perpage='.$messages_per_page;
            
            if (substr($_REQUEST['start'], 0, 3) == 'msg')
            {
                $location = 'post';
                $other_info[] = 'pid='.(int) substr($_REQUEST['start'], 3);
            }
            else
            {
                $location = 'topic';
                $current_page = isset($_REQUEST['start']) ? ($_REQUEST['start'] / $messages_per_page + 1) : 1;
                $other_info[] = 'page='.$current_page;
            }
        }
    }
    $other_info_str = implode('&', $other_info);
    $scheme_url = $baseUrl . '/' . (!empty($user_info['id']) ? '?user_id='.$user_info['id'].'&' : '?') . 'location='.$location.(!empty($other_info_str) ? '&'.$other_info_str : '');
    
    $exttMbqTempPageType = $location;
    if (
        (isset($_GET['action']) && $_GET['action'] == 'post' && isset($_GET['msg']) && isset($_GET['topic'])) || 
        (isset($_GET['action']) && $_GET['action'] == 'post' && isset($_GET['topic']) && isset($_GET['last_msg'])) || 
        (isset($_GET['action']) && $_GET['action'] == 'help') || 
        (isset($_GET['action']) && $_GET['action'] == 'search') || 
        (isset($_GET['action']) && $_GET['action'] == 'calendar') || 
        (isset($_GET['action']) && $_GET['action'] == 'register') || 
        (isset($_GET['action']) && $_GET['action'] == 'post' && isset($_GET['board'])) || 
        (isset($_GET['action']) && $_GET['action'] == 'post' && isset($_GET['quote']) && isset($_GET['topic'])) || 
        (isset($_GET['action']) && $_GET['action'] == 'editpoll' && isset($_GET['add']) && isset($_GET['topic'])) || 
        (isset($_GET['action']) && $_GET['action'] == 'splittopics' && isset($_GET['topic']) && isset($_GET['at'])) || 
        (isset($_GET['action']) && $_GET['action'] == 'post' && isset($_GET['board']) && isset($_GET['poll'])) || 
        (isset($_GET['action']) && $_GET['action'] == 'emailuser' && isset($_GET['sa']) && isset($_GET['topic'])) || 
        (isset($_GET['action']) && $_GET['action'] == 'admin') || 
        (isset($_GET['action']) && $_GET['action'] == 'moderate') || 
        (isset($_GET['action']) && $_GET['action'] == 'mlist')
    ) {
        $exttMbqTempPageType = 'other';
    }
    return $scheme_url;
}

function tt_handle_forum_info($forum_info){
    $result = array();
    if (empty($forum_info)){
        return $result;
    }
    $infos = preg_split('/\s*?\n\s*?/', $forum_info);
    foreach ($infos as $info){
        $value = preg_split('/\s*:\s*/', $info, 2);
        $result[trim($value[0])] = isset($value[1]) ? $value[1] : '';
    }
    return $result;
}