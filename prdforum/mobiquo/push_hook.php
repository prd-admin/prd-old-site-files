<?php
if (!defined('SMF'))
    die('Hacking attempt...');

error_reporting(0);

function tapatalk_push_new_topic($post_id, $pushed_user_ids = array()){
    global $user_info, $smcFunc, $context, $boardurl, $modSettings, $topic;

    if (empty($post_id)){
        return false;
    }
    $ttp_data = array(
        'type'      => 'newtopic',
        'id'        => $topic,
        'subid'     => $post_id,
        'subfid'    => $_GET['board'],
        'title'     => tt_push_clean($_POST['subject']),
        'author'    => tt_push_clean($user_info['name']),
        'authorid'  => $user_info['id'],
        'author_postcount' => $user_info['posts'],
        'dateline'  => time(),
    );

    if (!empty($ttp_data['subfid'])){
        $request = $smcFunc['db_query']('', '
            SELECT b.name
            FROM {db_prefix}boards as b
            WHERE b.id_board = {int:subfid}',
            array(
                'subfid' => intval($ttp_data['subfid']),
            )
        );
        $row = $smcFunc['db_fetch_assoc']($request);
        if (!empty($row)){
            $ttp_data['sub_forum_name'] = tt_push_clean($row['name']);
        }
    }
    $request = $smcFunc['db_query']('', '
        SELECT ts.id_member
        FROM {db_prefix}log_notify ts
        LEFT JOIN {db_prefix}tapatalk_users tu ON (ts.id_member=tu.userid)
        WHERE ts.id_board = {int:board_id}',
        array(
            'board_id' => $_GET['board'],
        )
    );

    if(isset($modSettings['tp_push_notifications']) && $modSettings['tp_push_notifications'] == 1){
        $ttp_data['content'] = TT_handle_content($_POST['message']);
    }

    tapatalk_push_quote_tag($post_id, $ttp_data, true, $pushed_user_ids);

    $userids = '';
    while ($row = $smcFunc['db_fetch_assoc']($request)){
        if ($row['id_member'] == $user_info['id']) continue;
        if (in_array($row['id_member'], $pushed_user_ids)) continue;
        if(!tapatalk_user_can_receive_push_topic($row['id_member'], $ttp_data['subfid'],  $ttp_data['id'])) continue;
        $userids = empty($userids) ? $row['id_member'] : $userids.','.$row['id_member'];
        $pushed_user_ids[] = $row['id_member'];
        $ttp_data['userid'] = $row['id_member'];
        store_as_alert($ttp_data);
    }
    $ttp_data['userid'] = $userids;

    $return_status = tt_do_post_request($ttp_data);
}
function tapatalk_user_can_receive_push_topic($userId,$boardId,$topicId)
{
    global $smcFunc;
    try
    {
        if(empty($userId))
        {
            return false;
        }
        $member = $smcFunc['db_query']('', '
		SELECT
		mem.id_member, mem.email_address, mem.notify_regularity, mem.notify_send_body, mem.lngfile,
			 mem.id_group, mem.additional_groups,
			mem.id_post_group
		FROM {db_prefix}members mem
		WHERE mem.id_member = {int:current_member}
			AND mem.is_activated = {int:is_activated}
		',
            array(
                'current_member' => (int)$userId,
                'is_activated' => 1,
            )
        );
        if($rowmember = $smcFunc['db_fetch_assoc']($member))
        {
            if ($rowmember['id_group'] != 1)
            {
                $board = $smcFunc['db_query']('', '
		        SELECT b.member_groups
		        FROM {db_prefix}boards AS b
		        WHERE b.id_board = ({int:current_board})',
               array(
                   'current_board' => $boardId,
               )
               );
                if($rowboard = $smcFunc['db_fetch_assoc']($board))
                {

                    $allowed = explode(',', $rowboard['member_groups']);
                    $rowmember['additional_groups'] = explode(',', $rowmember['additional_groups']);
                    $rowmember['additional_groups'][] = $rowmember['id_group'];
                    $rowmember['additional_groups'][] = $rowmember['id_post_group'];

                    if (count(array_intersect($allowed, $rowmember['additional_groups'])) == 0)
                    {
                        return false;
                    }
                }
            }
        }
        else
        {
            return false;
        }

    }
    catch(Exception $ex)
    {
    }
    return true;

}
function tapatalk_push_reply($post_id)
{
    global $user_info, $context, $smcFunc, $boardurl, $modSettings;
    //subscribe push
    $pushed_user_ids = array();
    if ($context['current_topic'] && $post_id && (function_exists('curl_init') || ini_get('allow_url_fopen')))
    {
        $ttp_data = array(
            'type'      => 'sub',
            'id'        => $context['current_topic'],
            'subid'     => $post_id,
            'subfid'    => $_GET['board'],
            'title'     => tt_push_clean($_POST['subject']),
            'author'    => tt_push_clean($user_info['name']),
            'authorid'  => $user_info['id'],
            'author_postcount' => $user_info['posts'],
            'dateline'  => time(),
        );

        if (!empty($ttp_data['subfid'])){
            $request = $smcFunc['db_query']('', '
            SELECT b.name
            FROM {db_prefix}boards as b
            WHERE b.id_board = {int:subfid}',
            array(
                'subfid' => intval($ttp_data['subfid']),
            )
            );
            $row = $smcFunc['db_fetch_assoc']($request);
            if (!empty($row)){
                $ttp_data['sub_forum_name'] = tt_push_clean($row['name']);
            }
        }
        $message = $_POST['message'];
        $request = $smcFunc['db_query']('', '
            SELECT ts.id_member
            FROM {db_prefix}log_notify ts
            LEFT JOIN {db_prefix}tapatalk_users tu ON (ts.id_member=tu.userid)
            WHERE ts.id_topic = {int:topic_id}',
            array(
                'topic_id' => $context['current_topic'],
            )
        );

        if(isset($modSettings['tp_push_notifications']) && $modSettings['tp_push_notifications'] == 1){
            $ttp_data['content'] = TT_handle_content($message);
        }

        tapatalk_push_quote_tag($post_id, $ttp_data, false, $pushed_user_ids);

        $userids = '';
        while($row = $smcFunc['db_fetch_assoc']($request))
        {
            if ($row['id_member'] == $user_info['id']) continue;
            if (in_array($row['id_member'], $pushed_user_ids)) continue;
            if(!tapatalk_user_can_receive_push_topic($row['id_member'], $ttp_data['subfid'],  $ttp_data['id'])) continue;
            $userids = empty($userids) ? $row['id_member'] : $userids.','.$row['id_member'];
            $pushed_user_ids[] = $row['id_member'];
            $ttp_data['userid'] = $row['id_member'];
            store_as_alert($ttp_data);
        }
        $ttp_data['userid'] = $userids;

        $return_status = tt_do_post_request($ttp_data);
    }
}

function tapatalk_push_quote_tag($post_id, $ttp_data, $newtopic = false, &$pushed_user_ids = array())
{
    global $user_info, $context, $smcFunc, $boardurl, $modSettings, $topic;
    if (($newtopic ? $topic : $context['current_topic']) && isset($_POST['message']) && $post_id && (function_exists('curl_init') || ini_get('allow_url_fopen')))
    {
        $message = $_POST['message'];

        //@ push
        if (preg_match_all( '/(?<=^@|\s@)(#(.{1,50})#|\S{1,50}(?=[,\.;!\?]|\s|$))/U', $message, $tags ) )
        {
            foreach ($tags[2] as $index => $tag)
            {
                if ($tag) $tags[1][$index] = $tag;
            }
            $tagged_usernames =  array_unique($tags[1]);
            $tag_ids = verify_smf_userids_from_names($tagged_usernames);
            if(!empty($tag_ids))
            {
                $ttp_data['type'] = 'tag';
                $request = $smcFunc['db_query']('', '
                    SELECT tu.userid
                    FROM {db_prefix}tapatalk_users tu
                    WHERE tu.userid IN ({array_int:tag_ids})' ,
                    array(
                        'tag_ids' => $tag_ids,
                    )
                );
                $userids = '';
                while($row = $smcFunc['db_fetch_assoc']($request))
                {
                    if ($row['userid'] == $user_info['id']) continue;
                    if (in_array($row['userid'], $pushed_user_ids)) continue;
                    if(!tapatalk_user_can_receive_push_topic($row['userid'], $ttp_data['subfid'],  $ttp_data['id'])) continue;
                    $pushed_user_ids[] = $row['userid'];
                    $userids = empty($userids) ? $row['userid'] : $userids.','.$row['userid'];
                    $ttp_data['userid'] = $row['userid'];
                    store_as_alert($ttp_data);
                }
                $ttp_data['userid'] = $userids;

                if (!empty($userids)){
                    $return_status = tt_do_post_request($ttp_data);
                }
            }
        }

        //quote push
        $quotedUsers = array();
        if(preg_match_all('/\[quote author=(.*?) link=.*?\]/si', $message, $quote_matches))
        {
            $quotedUsers = $quote_matches[1];
            $quote_ids = verify_smf_userids_from_names($quotedUsers);
            if(!empty($quote_ids))
            {
                if (is_array($quote_ids)){
                    foreach ($quote_ids as $key => $quote_id){
                        $quote_ids[$key] = intval($quote_id);
                    }
                } else {
                    $quote_ids = intval($quote_ids);
                }

                $ttp_data['type'] = 'quote';

                $request = $smcFunc['db_query']('', '
                    SELECT tu.userid
                    FROM {db_prefix}tapatalk_users tu
                    WHERE tu.userid IN ({'.(is_array($quote_ids) ? 'array_int': 'int').':quoteids})' ,
                    array(
                        'quoteids' => $quote_ids,
                    )
                );

                $userids = '';
                while($row = $smcFunc['db_fetch_assoc']($request))
                {
                    if ($row['userid'] == $user_info['id']) continue;
                    if (in_array($row['userid'], $pushed_user_ids)) continue;
                    if(!tapatalk_user_can_receive_push_topic($row['userid'], $ttp_data['subfid'],  $ttp_data['id'])) continue;
                    $userids = empty($userids) ? $row['userid'] : $userids.','.$row['userid'];
                    $pushed_user_ids[] = $row['userid'];
                    $ttp_data['userid'] = $row['userid'];
                    store_as_alert($ttp_data);
                }
                $ttp_data['userid'] = $userids;

                if (!empty($userids)){
                    $return_status = tt_do_post_request($ttp_data);
                }
            }
        }
    }
}

function tapatalk_push_pm()
{
    global $user_info, $smcFunc, $boardurl, $modSettings, $context;

    $sent_recipients = !empty($context['send_log']) && !empty($context['send_log']['sent']) ? array_keys($context['send_log']['sent']) : array();

    if (isset($sent_recipients) && !empty($sent_recipients) && isset($_POST['subject']))
    {
        $timestr = time();
        $id_pm_req = $smcFunc['db_query']('', '
            SELECT p.id_pm, p.body, p.msgtime
            FROM {db_prefix}personal_messages p
            WHERE p.msgtime > {int:msgtime_l} AND p.msgtime < {int:msgtime_h} AND p.id_member_from = {int:send_userid} ',
            array(
                'msgtime_l' => $timestr-10,
                'msgtime_h' => $timestr+10,
                'send_userid' => $user_info['id'],
            ));
        $id_pm = $smcFunc['db_fetch_assoc']($id_pm_req);

        if($id_pm_req)
            $smcFunc['db_free_result']($id_pm_req);

        if ($id_pm)
        {
            $ttp_data = array(
                'type'      => 'pm',
                'id'        => $id_pm['id_pm'],
                'title'     => tt_push_clean($_POST['subject']),
                'author'    => tt_push_clean($user_info['name']),
                'authorid'  => $user_info['id'],
                'author_postcount' => $user_info['posts'],
                'dateline'  => $id_pm['msgtime'],
            );
            $request = $smcFunc['db_query']('', '
                SELECT tu.userid
                FROM {db_prefix}tapatalk_users tu
                WHERE tu.userid IN ({array_int:recipient_to})',
                array(
                    'recipient_to' => $sent_recipients,//$recipientList['to'],
                )
            );
            $userids = '';
            while($row = $smcFunc['db_fetch_assoc']($request))
            {
                if ($row['userid'] == $user_info['id']) continue;
                $userids = empty($userids) ? $row['userid'] : $userids.','.$row['userid'];
                $ttp_data['userid'] = $row['userid'];
                store_as_alert($ttp_data);
            }
            $ttp_data['userid'] = $userids;

            if(isset($modSettings['tp_push_notifications']) && $modSettings['tp_push_notifications'] == 1){
                $ttp_data['content'] = TT_handle_content($id_pm['body']);
            }

            tt_do_post_request($ttp_data);
        }
    }
}

function tapatalk_push_subscirbe($thread_id, $pushed_user_ids = array()){
    global $user_info, $smcFunc, $context, $boardurl, $topic, $modSettings;
    if (empty($thread_id)){
        $thread_id = $topic;
    }

    $ttp_data = array(
        'type'      => 'newsub',
        'id'        => $topic,
        'subfid'    => $context['current_board'],
        'author'    => tt_push_clean($user_info['name']),
        'authorid'  => $user_info['id'],
        'author_postcount' => $user_info['posts'],
        'dateline'  => time(),
    );

    if (!empty($ttp_data['subfid'])){
        $request = $smcFunc['db_query']('', '
            SELECT b.name
            FROM {db_prefix}boards as b
            WHERE b.id_board = {int:subfid}',
            array(
                'subfid' => intval($ttp_data['subfid']),
            )
        );
        $row = $smcFunc['db_fetch_assoc']($request);
        if (!empty($row)){
            $ttp_data['sub_forum_name'] = tt_push_clean($row['name']);
        }
    }

    // Get all the important topic info.
    $request = $smcFunc['db_query']('', '
        SELECT ms.subject
        FROM {db_prefix}topics AS t
            INNER JOIN {db_prefix}messages AS ms ON (ms.id_msg = t.id_first_msg)
        WHERE t.id_topic = {int:thread_id}
        LIMIT 1',
        array(
            'thread_id' => $thread_id,
        )
    );
    $topicinfo = $smcFunc['db_fetch_assoc']($request);
    $ttp_data['title'] = isset($topicinfo['subject']) ? $topicinfo['subject']: '';
    $message = $_POST['message'];
    if(isset($modSettings['tp_push_notifications']) && $modSettings['tp_push_notifications'] == 1){
        $ttp_data['content'] = TT_handle_content($message);
    }
    $request = $smcFunc['db_query']('', '
        SELECT ts.id_member_started as id_member
        FROM {db_prefix}topics ts
        LEFT JOIN {db_prefix}tapatalk_users tu ON (ts.id_member_started=tu.userid)
        WHERE ts.id_topic = {int:thread_id}',
        array(
            'thread_id' => $thread_id,
        )
    );
    $userids = '';
    while ($row = $smcFunc['db_fetch_assoc']($request)){
        if ($row['id_member'] == $user_info['id']) continue;
        if (in_array($row['id_member'], $pushed_user_ids)) continue;
        if(!tapatalk_user_can_receive_push_topic($row['id_member'], $ttp_data['subfid'],  $ttp_data['id'])) continue;
        $userids = empty($userids) ? $row['id_member'] : $userids.','.$row['id_member'];
        $pushed_user_ids[] = $row['id_member'];
        $ttp_data['userid'] = $row['id_member'];
        store_as_alert($ttp_data);
    }
    $ttp_data['userid'] = $userids;

    if (!empty($userids)){
        $return_status = tt_do_post_request($ttp_data);
    }
}

function tt_do_post_request($data)
{
    global $boardurl, $modSettings;
    $push_url = 'http://push.tapatalk.com/push.php';

    if(!function_exists('updateSettings'))
        require_once($sourcedir . '/Subs.php');

    //Initial this key in modSettings
    if(!isset($modSettings['push_slug']))
        updateSettings(array('push_slug' => 0));

    //Get push_slug from db
    $push_slug = isset($modSettings['push_slug'])? $modSettings['push_slug'] : 0;
    $slug = base64_decode($push_slug);
    $slug = push_slug($slug, 'CHECK');
    $check_res = @unserialize($slug);

    //If it is valide(result = true) and it is not sticked, we try to send push
    if($check_res['result'] && !$check_res['stick'])
    {
        //Slug is initialed or just be cleared
        if($check_res['save'])
        {
            updateSettings(array('push_slug' => base64_encode($slug)));
        }

        //add general information
        $data['url'] = $boardurl;
        if(isset($modSettings['tp_push_key']) && !empty($modSettings['tp_push_key'])){
            $data['key'] = $modSettings['tp_push_key'];
        }
        $data['author_ip'] = getClientIp();
        $data['author_ua'] = getClienUserAgent();
        $data['author_type'] = getUserType();
        $data['from_app'] = getIsFromApp();

        //Send push
        if(!defined('IN_MOBIQUO'))
            define('IN_MOBIQUO', true);
        require_once(dirname(__FILE__).'/lib/classTTConnection.php');
        $connection = new classTTConnection();
        $push_resp = $connection->getContentFromSever($push_url, $data, 'POST');
        if(trim($push_resp) === 'Invalid push notification key') $push_resp = 1;
        if(!is_numeric($push_resp))
        {
            //Sending push failed, try to update push_slug to db
            $slug = push_slug($slug, 'UPDATE');
            $update_res = @unserialize($slug);
            if($update_res['result'] && $update_res['save'])
            {
                updateSettings(array('push_slug' => base64_encode($slug)));
            }
        }
    }
}

function push_slug($push_v, $method = 'NEW')
{
    if(empty($push_v))
        $push_v = serialize(array());
    $push_v_data = @unserialize($push_v);
    $current_time = time();
    if(!is_array($push_v_data))
        return serialize(array('result' => 0, 'result_text' => 'Invalid v data', 'stick' => 0));
    if($method != 'CHECK' && $method != 'UPDATE' && $method != 'NEW')
        return serialize(array('result' => 0, 'result_text' => 'Invalid method', 'stick' => 0));

    if($method != 'NEW' && !empty($push_v_data))
    {
        $push_v_data['save'] = $method == 'UPDATE';
        if($push_v_data['stick'] == 1)
        {
            if($push_v_data['stick_timestamp'] + $push_v_data['stick_time'] > $current_time)
                return $push_v;
            else
                $method = 'NEW';
        }
    }

    if($method == 'NEW' || empty($push_v_data))
    {
        $push_v_data = array();                       //Slug
        $push_v_data['max_times'] = 3;                //max push failed attempt times in period
        $push_v_data['max_times_in_period'] = 300;      //the limitation period
        $push_v_data['result'] = 1;                   //indicate if the output is valid of not
        $push_v_data['result_text'] = '';             //invalid reason
        $push_v_data['stick_time_queue'] = array();   //failed attempt timestamps
        $push_v_data['stick'] = 0;                    //indicate if push attempt is allowed
        $push_v_data['stick_timestamp'] = 0;          //when did push be sticked
        $push_v_data['stick_time'] = 600;             //how long will it be sticked
        $push_v_data['save'] = 1;                     //indicate if you need to save the slug into db
        return serialize($push_v_data);
    }

    if($method == 'UPDATE')
    {
        $push_v_data['stick_time_queue'][] = $current_time;
    }
    $sizeof_queue = count($push_v_data['stick_time_queue']);

    $period_queue = $sizeof_queue > 1 && isset($push_v_data['stick_time_queue'][$sizeof_queue - 1]) && isset($push_v_data['stick_time_queue'][0]) ? ($push_v_data['stick_time_queue'][$sizeof_queue - 1] - $push_v_data['stick_time_queue'][0]) : 0;

    $times_overflow = $sizeof_queue > $push_v_data['max_times'];
    $period_overflow = $period_queue > $push_v_data['max_times_in_period'];

    if($period_overflow)
    {
        if(!array_shift($push_v_data['stick_time_queue']))
            $push_v_data['stick_time_queue'] = array();
    }

    if($times_overflow && !$period_overflow)
    {
        $push_v_data['stick'] = 1;
        $push_v_data['stick_timestamp'] = $current_time;
    }

    return serialize($push_v_data);
}

function tt_push_clean($str)
{
    $str = strip_tags($str);
    $str = trim($str);
    return html_entity_decode($str, ENT_QUOTES, 'UTF-8');
}

function store_as_alert($push_data)
{
    global $smcFunc, $db_prefix, $modSettings;
    db_extend();

    $matched_tables = $smcFunc['db_list_tables'](false, $db_prefix . "tapatalk_push");
    if(!empty($matched_tables))
    {
        $push_data['title'] = $smcFunc['db_escape_string']($push_data['title']);
        $push_data['author'] = $smcFunc['db_escape_string']($push_data['author']);
        $request = $smcFunc['db_insert']('ignore',
                    '{db_prefix}tapatalk_push',
                    array('userid' => 'int', 'type' => 'string', 'id' => 'int', 'subid' => 'int', 'subfid' => 'int', 'mid' => 'int', 'title' => 'string', 'author' => 'string', 'authorid' => 'int','dateline' => 'int'),
                    array($push_data['userid'], $push_data['type'], $push_data['id'], isset($push_data['subid'])? $push_data['subid'] : 0, isset($push_data['subfid'])? $push_data['subfid'] : 0, isset($push_data['mid'])? $push_data['mid'] : 0, $push_data['title'], $push_data['author'], $push_data['authorid'], $push_data['dateline']),
                    array('userid')
        );
        $affected_rows = $smcFunc['db_affected_rows']($request);
    }
    $current_time = time();
    // Check outdated push data and clean
    if(isset($modSettings['tp_alert_clean_time']) && !empty($modSettings['tp_alert_clean_time']))
    {
        $last_clean_time = $modSettings['tp_alert_clean_time'];
        $clean_period = 1*24*60*60;
        if($current_time - $last_clean_time > $clean_period)
        {
            $d_request = $smcFunc['db_query']('', '
                DELETE
                FROM {db_prefix}tapatalk_push
                    WHERE dateline < {int:outdateTime}',
                array(
                    'outdateTime' => $current_time - 30*24*60*60
                )
            );
            updateSettings(array('tp_alert_clean_time' => $current_time),true);
        }
    }
    else
    {
        updateSettings(array('tp_alert_clean_time' => $current_time));
    }
}

function verify_smf_userids_from_names($names)
{
    $direct_ids = array();
    $valid_names = array();
    $verified_ids = array();
    foreach($names as $index => $user)
    {
        if(is_numeric($user) && $user == intval($user))
            $direct_ids[] = $user;
        else
            $valid_names[] = $user;
    }
    if(!empty($valid_names))
    {
        $loaded_ids = loadMemberData($valid_names, true);
        //make sure tids only contains integer values
        if(is_array($loaded_ids))
        {
            foreach($loaded_ids as $idx => $loaded_id)
                if(is_numeric($loaded_id) && $loaded_id == intval($loaded_id))
                    $verified_ids[] = $loaded_id;
        }
        else
            if(is_numeric($loaded_ids) && $loaded_ids == intval($loaded_ids))
                    $verified_ids[] = $loaded_ids;
    }
    $verified_ids = array_unique(array_merge($direct_ids, $verified_ids));
    return $verified_ids;
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

function TT_handle_content($content){
    global $boarddir;
    return TT_post_html_clean($content);
}
function getClientIp()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function getClienUserAgent()
{
    $useragent = $_SERVER['HTTP_USER_AGENT'];
    return $useragent;
}

function getIsFromApp()
{
    return defined('IN_MOBIQUO') ? 1 : 0;
}

function getUserType(){
    global $user_info;
    $user_type = 'normal';
    if ($user_info['is_mod']) {
        $user_type = 'mod';
    }
    if ($user_info['is_admin']) {
        $user_type = 'admin';
    }
    return $user_type;
}

function TT_post_html_clean($str)
{
    global $modSettings;

    // custom content replacement.
    if(isset($modSettings['tp_custom_content_replacement']) && !empty($modSettings['tp_custom_content_replacement']))
    {
        $custom_replacement = $modSettings['tp_custom_content_replacement'];
        $custom_replacement = preg_replace('/"/', '&quot;', $custom_replacement);
        if(!empty($custom_replacement))
        {
            $replace_arr = explode("\n", $custom_replacement);
            foreach ($replace_arr as $replace)
            {
                preg_match('/^\s*(\'|")((\#|\/|\!).+\3[ismexuADUX]*?)\1\s*,\s*(\'|")(.*?)\4\s*$/', $replace,$matches);
                if(count($matches) == 6)
                {
                    $temp_post = $str;
                    $str = @preg_replace($matches[2], $matches[5], $str);
                    if(empty($str))
                    {
                        $str = $temp_post;
                    }
                }
            }
        }
    }

    //handle quote
    //$str = preg_replace_callback('/<div class="quoteheader"><div class="topslice_quote"><a href="[^>]*#msg([^>]*)">Quote from: ([^<]*) on <strong>([^<]*)<\/strong> at ([^<]*)<\/a><\/div><\/div><blockquote class="(bbc_standard_quote|bbc_alternate_quote)">(.*)<\/blockquote><div class="quotefooter"><div class="botslice_quote"><\/div><\/div>(?!<div class="quotefooter"><div class="botslice_quote"><\/div><\/div>)/is', create_function('$matches','return "[quote name=\"$matches[2]\" post=$matches[1] timestamp=".strtotime($matches[3]." ".$matches[4])."]".exttMbqRecurHandleQuote($matches[6])."[/quote]";'), $str);
    //$str = preg_replace_callback('/<div class="quoteheader"><div class="topslice_quote"><a href="[^>]*#msg([^>]*)">Quote from: ([^<]*) on .*?m<\/a><\/div><\/div><blockquote class="(bbc_standard_quote|bbc_alternate_quote)">(.*)<\/blockquote><div class="quotefooter"><div class="botslice_quote"><\/div><\/div>(?!<div class="quotefooter"><div class="botslice_quote"><\/div><\/div>)/is', create_function('$matches','return "[quote name=\"$matches[2]\" post=$matches[1]]".exttMbqRecurHandleQuote($matches[4])."[/quote]";'), $str);
    //$str = preg_replace_callback('/<div class="quoteheader"><div class="topslice_quote"><a href="[^>]*(#msg([^>]*)|action=profile[^>]*)">Quote from: ([^<]*) on .*?m<\/a><\/div><\/div><blockquote class="(bbc_standard_quote|bbc_alternate_quote)">(.*)<\/blockquote><div class="quotefooter"><div class="botslice_quote"><\/div><\/div>(?!<div class="quotefooter"><div class="botslice_quote"><\/div><\/div>)/is', create_function('$matches','return "[quote name=\"$matches[3]\"".($matches[2] ? " post=$matches[2]":"")."]".exttMbqRecurHandleQuote($matches[5])."[/quote]";'), $str);
    $str = preg_replace('/\[\/dummyquote\]/si', '[/quote]', $str);
    // [dummyquote author=admin link=topic=1.msg4#msg4 date=1392612506]
    $str = preg_replace('/\[dummyquote author=([^\]]*?) link=[^\]]*?#msg([^\]]*?) date=([^\]]*?)\]/si', '[quote name="$1" post=$2 timestamp=$3]', $str);
    $str = preg_replace('/\[dummyquote([^\]]*?)\]/si', '[quote]', $str);

    //handle code
    $str = preg_replace('/<code class="bbc_code">(.*?)<\/code>/si', '[code]$1[/code]', $str);
    $str = preg_replace('/<a [^>]*?class="codeoperation"[^>]*?>\[Select\]<\/a>/si', '', $str);

    $search = array(
        '/<img .*?src="(.*?)".*?\/?>/si',
        '/<a [^>]*?href="(?!javascript)([^"]*?)"[^>]*?>([^<]*?)<\/a>/si',
        '/(<br\s*\/?>|<\/cite>|<\/li>|<\/ul>|<\/pre>|<\/div>|<\/tr>|<\/table>|<\/code>|<\/em>|<em.*?>)/si',
        '/<\/td>/si',
        '/<code class="[^"]+">(.+)<\/code>/si',
    );

    $replace = array(
        '[img]$1[/img]',
        '[url=$1]$2[/url]',
        "$1\n",
        ' ',
        '[quote]$1[/quote]',
    );

    $str = preg_replace('/\n|\r/si', '', $str);
    //$str = parse_quote($str); //looks useless in future
    $str = preg_replace('/<i class="pstatus".*?>.*?<\/i>(<br\s*\/>){0,2}/', '', $str);
    $str = preg_replace('/<script.*?>.*?<\/script>/', '', $str);
    $str = preg_replace($search, $replace, $str);
    $str = preg_replace('/\[url(.*?)\](\s*)(.*?)\[\/url\]/', '[url$1]$3[/url]', $str);

    $str = TT_basic_clean($str);
    $str = TT_parse_bbcode($str);
    $str = preg_replace('/\[quote\](.*?)\[\/quote\](((<\/?br *?\/?>)|\n)*)(.*?)$/si','[quote]$1[/quote]$5', $str);
//    $str = preg_replace_callback('/\[code\](.*?)\[\/code\]/si', create_function('$matches','return "[code]".exttmbq_restore_for_code($matches[1])."[/code]";'), $str);
    return $str;
}

function TT_parse_quote($str)
{
    $blocks = preg_split('/(<blockquote.*?>|<\/blockquote>)/i', $str, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

    $quote_level = 0;
    $message = '';
    $supported_quote_level = 5;

    foreach($blocks as $block)
    {
        if (preg_match('/<blockquote.*?>/i', $block)) {
            if ($quote_level <= $supported_quote_level - 1) $message .= '[quote]';
            $quote_level++;
        } else if (preg_match('/<\/blockquote>/i', $block)) {
            if ($quote_level <= $supported_quote_level) $message .= '[/quote]';
            if ($quote_level >= $supported_quote_level) {
                $quote_level--;
                $message .= "\n";
            }
        } else {
            if ($quote_level <= $supported_quote_level) $message .= $block;
        }
    }

    return $message;
}

function TT_parse_quote_bbcode($str)
{
    $blocks = preg_split('/(\[quote.*?\]|\[\/quote\])/si', $str, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

    $quote_level = 0;
    $message = '';

    foreach($blocks as $block)
    {
        if (preg_match('/\[quote.*?\]/i', $block)) {
            $quote_level++;
        } else if (preg_match('/\[\/quote\]/i', $block)) {
            $quote_level--;
            if ($quote_level == 0) $message .= ' [quote] ';
        } else {
            if ($quote_level == 0) $message .= $block;
        }
    }

    return $message;
}

function TT_parse_bbcode($str)
{
    $search = array(
        '#\[(b)\](.*?)\[/b\]#si',
        '#\[(u)\](.*?)\[/u\]#si',
        '#\[(i)\](.*?)\[/i\]#si',
        '#\[color=(\#[\da-fA-F]{3}|\#[\da-fA-F]{6}|[A-Za-z]{1,20}|rgb\(\d{1,3}, ?\d{1,3}, ?\d{1,3}\))\](.*?)\[/color\]#si',
    );

    if (isset($GLOBALS['return_html']) && $GLOBALS['return_html']) {
        $str = htmlspecialchars($str);
        $str = preg_replace('/&quot;/si', '"', $str);
        $replace = array(
            '<$1>$2</$1>',
            '<$1>$2</$1>',
            '<$1>$2</$1>',
            '<font color="$1">$2</font>',
        );
        $str = str_replace("\n", '<br />', $str);
    } else {
        $replace = '$2';
    }

    return preg_replace($search, $replace, $str);
}

function TT_basic_clean($str, $cut = 0, $is_shortcontent = 0, $to_utf8 = TRUE)
{
    global $modSettings;

    if($is_shortcontent)
    {
        $str = preg_replace('/\[url\].*?\[\/url\]/si', '[emoji288]', $str);
        $str = preg_replace('/\[url=[^\]]*?\]http.*?\[\/url\]/si', '[emoji288]', $str);
        $str = preg_replace('/\[url=.*?\](.*?)\[\/url\]/si', '[emoji288]', $str);
        $str = preg_replace('/\[img.*?\].*?\[\/img\]/si', '[emoji328]', $str);
        $str = preg_replace('/\[color=.*\](.*)\[\/color\]/U', '$1', $str);
        $str = preg_replace('/\[color=.*\](.*)/U', '$1', $str);
        $str = preg_replace('/Code: \[Select\]/', 'Code: ', $str);
        $str = preg_replace('/\[[u|i|b]\](.*)\[\/[u|i|b]\]/U', '$1', $str);
        $str = preg_replace('/-{3}/', '', $str);
        $str = TT_parse_quote_bbcode($str);
        $str = preg_replace('/&nbsp;/', ' ', $str);
        $str = preg_replace('/&n*b*s*p*$/','...',$str);
        $str = preg_replace('/\[\/dummyquote\]/si', '', $str);
        $str = preg_replace('/\[dummyquote([^\]]*?)\]/si', '', $str);
        $str = preg_replace('/\[email=[^\]]*?\][^\[]*?\[\/email\]/si', '[emoji394]', $str);
        $str = preg_replace('/\[flash.*?\].*?\[\/flash\]/si', '[emoji327]', $str);
        $str = preg_replace('/\[map.*?\].*?\[\/map\]/si', ' [map] ', $str);
        $str = preg_replace('/\[code.*?\].*?\[\/code\]/si', ' [code] ', $str);
        $str = preg_replace('/\[attach.*?\].*?\[\/attach\]/si', '[emoji420]', $str);
        $str = preg_replace('/\[spoiler].*?\[\/spoiler\]/si', '[emoji85]', $str);
        $str = preg_replace('/\[spoiler.*?\].*?\[\/spoiler\]/si', '[emoji85]', $str);
    }
    $str = preg_replace('/<a.*?>Quote from:.*?<\/a>/', ' ', $str);
    $str = strip_tags($str);
    if ($to_utf8) {
        $str = TT_to_utf8($str);
    }
    $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    if (function_exists('censorText')) censorText($str);
    if($is_shortcontent)
        $str = preg_replace('/\s+/', ' ', $str);

    if ($cut > 0)
    {
        $str = preg_replace('/\[url=.*?\].*?\[\/url\]\s*\[quote\].*?\[\/quote\]/si', '', $str);
        $str = preg_replace('/\[?!(quote|url|img).*?\]/si', '', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        $str = trim($str);
        $str = cutstr($str, $cut);
    }

    return trim($str);
}
function TT_to_utf8($str)
{
    global $context;

    if (!empty($context) && !$context['utf8'])
    {
        $str = TT_mobiquo_encode($str);
    }

    return $str;
}

function TT_mobiquo_encode($str, $mode = '')
{
    if (empty($str)) return $str;

    static $charset, $charset_89, $charset_AF, $charset_8F, $charset_chr, $charset_html, $support_mb, $charset_entity;

    if (!isset($charset))
    {
        global $context;
        $charset = $context['character_set'];

        if(!defined('IN_MOBIQUO'))
            define('IN_MOBIQUO', true);
        require_once(dirname(__FILE__).'/include/charset.php');

        if (preg_match('/iso-?8859-?1/i', $charset))
        {
            $charset = 'Windows-1252';
            $charset_chr = $charset_8F;
        }
        if (preg_match('/iso-?8859-?(\d+)/i', $charset, $match_iso))
        {
            $charset = 'ISO-8859-' . $match_iso[1];
            $charset_chr = $charset_AF;
        }
        else if (preg_match('/windows-?125(\d)/i', $charset, $match_win))
        {
            $charset = 'Windows-125' . $match_win[1];
            $charset_chr = $charset_8F;
        }
        else
        {
            // x-sjis is not acceptable, but sjis do
            $charset = preg_replace('/^x-/i', '', $charset);
            $support_mb = function_exists('mb_convert_encoding') && @mb_convert_encoding('test', $charset, 'UTF-8');
        }
    }


    if (preg_match('/utf-?8/i', $charset))
    {
        $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    }
    else if (function_exists('mb_convert_encoding') && (strpos($charset, 'ISO-8859-') === 0 || strpos($charset, 'Windows-125') === 0) && isset($charset_html[$charset]))
    {
        if ($mode == 'to_local')
        {
            $str = @mb_convert_encoding($str, 'HTML-ENTITIES', 'UTF-8');
            $str = str_replace($charset_html[$charset], $charset_chr, $str);
        }
        else
        {
            if (strpos($charset, 'ISO-8859-') === 0)
            {
                // windows-1252 issue on ios
                $str = str_replace(array(chr(129), chr(141), chr(143), chr(144), chr(157)),
                                   array('&#129;', '&#141;', '&#143;', '&#144;', '&#157;'), $str);
            }

            $str = str_replace($charset_chr, $charset_html[$charset], $str);
            $str = @html_entity_decode($str, ENT_QUOTES, 'UTF-8');
            if($charset == 'ISO-8859-1') {
                $str = utf8_encode($str);
            }
        }
    }
    else if ($support_mb)
    {
        if ($mode == 'to_local')
        {
            $str = @mb_convert_encoding($str, 'HTML-ENTITIES', 'UTF-8');
            $str = @mb_convert_encoding($str, $charset, 'UTF-8');
        }
        else
        {
            $str = @mb_convert_encoding($str, 'UTF-8', $charset);
            $str = @html_entity_decode($str, ENT_QUOTES, 'UTF-8');
        }
    }
    else if (function_exists('iconv') && @iconv($charset, 'UTF-8', 'test-str'))
    {
        if ($mode == 'to_local')
        {
            $str = @htmlentities($str, ENT_NOQUOTES | ENT_IGNORE, 'UTF-8');
            $str = @iconv('UTF-8', $charset.'//IGNORE', $str);
        }
        else
        {
            $str = @iconv($charset, 'UTF-8//IGNORE', $str);
            $str = @html_entity_decode($str, ENT_QUOTES, 'UTF-8');
        }
    }
    else
    {
        if ($mode == 'to_local')
        {
            $str = @htmlentities($str, ENT_NOQUOTES | ENT_IGNORE, 'UTF-8');
            $str = @html_entity_decode($str, ENT_QUOTES, $charset);

            if($charset == 'ISO-8859-1') {
                $str = utf8_decode($str);
            }
        }
        else
        {
            $str = @html_entity_decode($str, ENT_QUOTES, 'UTF-8');
            if($charset == 'ISO-8859-1') {
                $str = utf8_encode($str);
            }
        }
    }

    // html entity convert
    if ($mode == 'to_local')
    {
        $str = str_replace(array_keys($charset_entity), array_values($charset_entity), $str);
    }

    return TT_remove_unknown_char($str);
}

function TT_remove_unknown_char($str)
{
    for ($i = 1; $i < 32; $i++)
    {
        if (in_array($i, array(10, 13))) continue;
        $str = str_replace(chr($i), '', $str);
    }

    return $str;
}
