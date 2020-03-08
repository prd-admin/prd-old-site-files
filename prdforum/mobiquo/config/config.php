<?php

defined('IN_MOBIQUO') or exit;

function get_mobiquo_config()
{
    $result = array(
        'version' => 'sm-2a_4.4.1',
        'api_level' => 3,
        'disable_search' => 0,
        'disable_latest' => 0,
        'disable_bbcode' => 0,
        'report_post' => 1,
        'close_report' => 1,
        'mark_forum' => 1,
        'goto_unread' => 1,
        'goto_post' => 1,
        'get_latest_topic' => 1,
        'login_type' => 'username',
        'soft_delete' => 0,
        'delete_reason' => 0,
        'get_forum' => 1,
        'disable_pm_verification' => 1,
        'announcement' => 0,
        'subscribe_load' => 1,
        'pm_load' => 1,
        'inbox_stat' => 1,
        'searchid' => 1,
        'alert' => 1,
        'advanced_search' => 1,
        'push_type' => 'quote,tag,sub,pm,newtopic,newsub',
        'ban_delete_type' => 'hard_delete',
        'ban_expires' => 1,
        'user_id' => 1,
        'inappsignin' => 1,
        'mark_pm_read' => 1,
        'mark_pm_unread' => 0,
        'search_user' => 1,
        'm_report' => 1,
        'user_recommended' => 1,
        'emoji_support' => 1,
        'advanced_edit' => 1,
        'is_beta' => 1,
        'advanced_move' => 1,
        'sso_activate' => 1,
        'set_api_key' => 1,
        'user_subscription' => 1,
        'push_content_check' => 1,
        'guest_group_id' => 0,
        'ignore_user' => 1,
        'set_forum_info' => 1,
        'avatar' => 1,
/* For mod conflict situation.
If your forum installed some mod which changed code in file like 'Load.php' and called function defined in the mod, tapatalk may not work.
In this case, you can config these mod or functions here, and it can help pass the function call problem.
However, we can not guarantee this can fix all mod conflict with tapatalk and the mod features may not work in tapatalk.
We set some known conflict mod and functions here as an example, you can add more seperated by comma.
*/
        //'mod_function' => array('AnnoyUser', 'TP_loadTheme', 'TPortal_init', 'pmx_checkECL_Cookie', 'pmx_ECL_Error', 'allowPmx', 'hideTagExists'),
        'mod_function' => array('AnnoyUser', 'pmx_checkECL_Cookie', 'pmx_ECL_Error', 'allowPmx', 'hideTagExists'),

/* Here indicate the flag is for which mod
MOD CONTROL FLAG        MOD NAME
------------------------------------------
projectEnabled:         SMF Project Tools
simplesef_enable:       SimpleSEF
*/
        'conflict_mod' => array('projectEnabled', 'simplesef_enable'),
        'search_started_by' => 1,
    );

    return  $result;
}

?>