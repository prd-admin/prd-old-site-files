<?php

defined('IN_MOBIQUO') or exit;

register_shutdown_function('xmlrpc_shutdown');

function mobiquo_exit($str = '')
{
    global $request_name, $context, $topic, $board;

    switch ($request_name) {
        case    'authorize_user': if (preg_match('/^action=login2;sa=check;member=/', $str)) return; break;
        case'update_push_status': if (preg_match('/^action=login2;sa=check;member=/', $str)) return; break;
        //case             'login': if (preg_match('/^action=login2;sa=check;member=/', $str)) return; break;
        case             'login': return;
        case        'login_user': if (preg_match('/^action=login2;sa=check;member=/', $str)) return; break;
        case       'logout_user': if (isset($_COOKIE['PHPSESSID'])) sessionDestroy($_COOKIE['PHPSESSID']); return; break;
        case    'delete_message': return;
        case    'create_message': if ($str == $context['current_label_redirect']) return;
        case   'subscribe_topic': if ($str == 'topic=' . $topic . '.' . $_REQUEST['start']) return; break;
        case 'unsubscribe_topic': if ($str == 'topic=' . $topic . '.' . $_REQUEST['start']) return; break;
        case   'subscribe_forum': if ($str == 'board=' . $board . '.' . $_REQUEST['start']) return; break;
        case 'unsubscribe_forum': if ($str == 'board=' . $board . '.' . $_REQUEST['start']) return; break;
        case       'report_post': return;
        case  'mark_all_as_read': return;
        case        'm_ban_user': return;
        case     'upload_avatar': if ('action=profile;area=forumprofile;updated' == $str) return; break;
        case      'update_email': if (preg_match('/^action=profile;area=account;updated/', $str)) return; break;
        case     'm_stick_topic': if (preg_match("/^topic=$topic/", $str)) return; break;
        case     'm_close_topic': if (preg_match("/^topic=$topic/", $str)) return; break;
        case    'm_delete_topic': if ($str == 'board=' . $board . '.0') return; break;
        case     'm_delete_post': if ($str == 'action=recent') return; break;
        case      'm_move_topic': if ($str == 'board=' . $board . '.0') return; break;
        case     'm_merge_topic': if (preg_match("/^action=mergetopics;sa=done;to=/", $str)) return; break;
        case        'thank_post': if (preg_match("/^topic=$topic/", $str)) return; break;
    }
    get_error('Unknown error');
}

function xmlrpc_shutdown()
{
    if (function_exists('error_get_last'))
    {
        $error = error_get_last();

        if(!empty($error)){
            switch($error['type']){
                case E_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                case E_PARSE:
                    get_error("Server error occurred: '{$error['message']} (".basename($error['file']).":{$error['line']})'");
                    break;
            }
        }
    }
}

function process_page($start_num, $end)
{
    global $start, $limit;

    $start = intval($start_num);
    $end = intval($end);
    $start = empty($start) ? 0 : max($start, 0);
    $end = (empty($end) || $end < $start) ? ($start + 19) : max($end, $start);
    if ($end - $start >= 50) {
        $end = $start + 49;
    }
    $limit = $end - $start + 1;

    return array($start, $limit);
}

function to_utf8($str)
{
    global $context;

    if (!empty($context) && !$context['utf8'])
    {
        $str = mobiquo_encode($str);
    }

    return $str;
}

function utf8_to_local()
{
    global $context, $request_name;

    if (!empty($context) && $context['utf8']) return;

    switch ($request_name) {
        case 'login_mod':
        case 'login':
            $_REQUEST['user'] = mobiquo_encode($_REQUEST['user'], 'to_local');
            $_REQUEST['password'] = mobiquo_encode($_REQUEST['password'], 'to_local');
            break;
        case 'create_message':
            foreach($_POST['recipient_to'] as $index => $name) {
                $_POST['recipient_to'][$index] = mobiquo_encode($name, 'to_local');
            }
            $_REQUEST['subject'] = mobiquo_encode($_REQUEST['subject'], 'to_local');
            $_REQUEST['message'] = mobiquo_encode($_REQUEST['message'], 'to_local');
            break;
        case 'new_topic':
        case 'create_topic':
        case 'reply_topic':
        case 'reply_post':
        case 'save_raw_post':
            $_POST['subject'] = mobiquo_encode($_POST['subject'], 'to_local');
            $_POST['message'] = mobiquo_encode($_POST['message'], 'to_local');
            break;
        case 'get_user_info':
        case 'get_user_reply_post':
        case 'get_user_topic':
            if (isset($_REQUEST['user']))
                $_REQUEST['user'] = mobiquo_encode($_REQUEST['user'], 'to_local');
            break;
        case 'get_participated_topic':
            if($GLOBALS['search_user'])
                $GLOBALS['search_user'] = mobiquo_encode($GLOBALS['search_user'], 'to_local');
            break;
        case 'search_topic':
        case 'search_post':
            $_POST['search'] = mobiquo_encode($_POST['search'], 'to_local');
            break;
        case 'report_post':
            $_POST['comment'] = mobiquo_encode($_POST['comment'], 'to_local');
            break;
    }
}

function get_short_content($message, $length = 100)
{
    $message = preg_replace('/\[url.*?\].*?\[\/url\]/si', '###url###', $message);
    $message = preg_replace('/\[img.*?\].*?\[\/img\]/si', '###img###', $message);
    $message = preg_replace('/\[attach.*?\].*?\[\/attach\]/si', '###attach###', $message);
    $message = preg_replace('/\[(i|code|quote|free|media|audio|flash|hide|swf).*?\].*?\[\/\\1\]/si', '', $message);
    $message = preg_replace('/\[.*?\]/si', '', $message);
    $message = preg_replace('/###(url|img|attach)###/si', '[$1]', $message);
    $message = preg_replace('/^\s*|\s*$/', '', $message);
    $message = preg_replace('/[\n\r\t]+/', ' ', $message);
    $message = preg_replace('/\s+/', ' ', $message);
    if (function_exists('censorText')) censorText($message);
    $message = cutstr($message, $length);
    $message = basic_clean($message);

    return $message;
}

function cutstr($string, $length, $dot = ' ...') {
    global $context;

    if(strlen($string) <= $length) {
        return $string;
    }

    $string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $string);

    $strcut = '';

    $n = $tn = $noc = 0;
    while($n < strlen($string)) {

        $t = ord($string[$n]);
        if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
            $tn = 1; $n++; $noc++;
        } elseif(194 <= $t && $t <= 223) {
            $tn = 2; $n += 2; $noc += 2;
        } elseif(224 <= $t && $t <= 239) {
            $tn = 3; $n += 3; $noc += 2;
        } elseif(240 <= $t && $t <= 247) {
            $tn = 4; $n += 4; $noc += 2;
        } elseif(248 <= $t && $t <= 251) {
            $tn = 5; $n += 5; $noc += 2;
        } elseif($t == 252 || $t == 253) {
            $tn = 6; $n += 6; $noc += 2;
        } else {
            $n++;
        }

        if($noc >= $length) {
            break;
        }

    }
    if($noc > $length) {
        $n -= $tn;
    }

    $strcut = substr($string, 0, $n);

    return $strcut.$dot;
}

function get_error($err_str = '')
{
    global $context, $tt_sso_result;

    @ob_clean();

    if(!headers_sent())
    {
        @header('HTTP/1.1 200 OK');
        @header('Mobiquo_is_login:'.($context['user']['is_logged'] ? 'true' : 'false'));
        @header('Content-Type: text/xml');
    }

    $result = array(
                'result'        => new xmlrpcval(false, 'boolean'),
                'result_text'   => new xmlrpcval(error_clean($err_str))
            );

    if (isset($tt_sso_result) &&!empty($tt_sso_result)){
        $result['regiest'] = new xmlrpcval($tt_sso_result->register, 'boolean');
        if (isset($tt_sso_result->userInfo['is_activated']) && $tt_sso_result->userInfo['is_activated'] == 3){
            $result['user_type'] = new xmlrpcval('inactive', 'base64');
        }
    }

    $response = new xmlrpcresp(
        new xmlrpcval($result,'struct')
    );

    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".$response->serialize('UTF-8');
    exit;
}

function log_it($log_data)
{
    global $mobiquo_config;

    if(!$mobiquo_config['keep_log'] || !$log_data)
    {
        return;
    }

    $log_file = './log/'.date('Ymd_H').'.log';

    file_put_contents($log_file, print_r($log_data, true), FILE_APPEND);
}

/**
 * recursively handle quote code and return
 *
 * @param  String  $newStr
 * @return  String
 */
function exttMbqRecurHandleQuote($str) {
    //return preg_replace_callback('/<div class="quoteheader"><div class="topslice_quote"><a href="[^>]*#msg([^>]*)">Quote from: ([^<]*) on .*?m<\/a><\/div><\/div><blockquote class="(bbc_standard_quote|bbc_alternate_quote)">(.*)<\/blockquote><div class="quotefooter"><div class="botslice_quote"><\/div><\/div>(?!<div class="quotefooter"><div class="botslice_quote"><\/div><\/div>)/is', create_function('$matches','return "[quote name=\"$matches[2]\" post=$matches[1]]".exttMbqRecurHandleQuote($matches[4])."[/quote]";'), $str);
    return preg_replace_callback('/<div class="quoteheader"><div class="topslice_quote"><a href="[^>]*(#msg([^>]*)|action=profile[^>]*)">Quote from: ([^<]*) on .*?m<\/a><\/div><\/div><blockquote class="(bbc_standard_quote|bbc_alternate_quote)">(.*)<\/blockquote><div class="quotefooter"><div class="botslice_quote"><\/div><\/div>(?!<div class="quotefooter"><div class="botslice_quote"><\/div><\/div>)/is', create_function('$matches','return "[quote name=\"$matches[3]\"".($matches[2] ? " post=$matches[2]":"")."]".exttMbqRecurHandleQuote($matches[5])."[/quote]";'), $str);
}

function post_html_clean($str)
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

    $str = basic_clean($str);
    $str = parse_bbcode($str);
    $str = preg_replace('/\[quote\](.*?)\[\/quote\](((<\/?br *?\/?>)|\n)*)(.*?)$/si','[quote]$1[/quote]$5', $str);
    $str = preg_replace_callback('/\[code\](.*?)\[\/code\]/si', create_function('$matches','return "[code]".exttmbq_restore_for_code($matches[1])."[/code]";'), $str);
    return $str;
}

function parse_quote($str)
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

function parse_quote_bbcode($str)
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
    if(empty($message))
    {
        $message = $str;
    }
    return $message;
}

function parse_bbcode($str)
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

function basic_clean($str, $cut = 0, $is_shortcontent = 0, $to_utf8 = TRUE)
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
        $str = parse_quote_bbcode($str);
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
    $str = preg_replace('/<a.*?>Quote from:.*?<\/a>/', '[quote]', $str);
    $str = strip_tags($str);
    if ($to_utf8) {
        $str = to_utf8($str);
    }
    $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    if (function_exists('censorText')) censorText($str);
    if($is_shortcontent)
        $str = preg_replace('/\s+/', ' ', $str);

    if ($cut > 0)
    {
        $str = preg_replace('/\[url=.*?\].*?\[\/url\]\s*\[quote\].*?\[\/quote\]/si', '[quote]', $str);
        $str = preg_replace('/\[?!(quote|url|img).*?\]/si', '[quote]', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        $str = trim($str);
        $str = cutstr($str, $cut);
    }

    return trim($str);
}

function error_clean($str)
{
    $str = preg_replace('/<br\s*\/?>/si', "\n", $str);
    $str = strip_tags($str);
    $str = preg_replace('/(\r|\n)+/si', "\n", $str);
    $str = to_utf8($str);
    $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');

    return trim($str);
}

function get_topic_info($fid, $tid)
{
    global $smcFunc, $user_info, $modSettings, $scripturl, $settings;

    static $mobi_permission;
    if(!$tid) return array();

    $perms_action_array = array('mark_notify', 'remove_any', 'remove_own', 'lock_any', 'lock_own', 'make_sticky', 'move_any', 'move_own', 'modify_any', 'modify_own', 'approve_posts');

    foreach ($perms_action_array as $perm)
        if (!isset($mobi_permission[$fid][$perm])) $mobi_permission[$fid][$perm] = allowedTo($perm, $fid);

    $request = $smcFunc['db_query']('substring', '
        SELECT
            t.id_topic, t.num_replies, t.locked, t.num_views, t.is_sticky, t.id_poll, t.id_previous_board,
            ' . ($user_info['is_guest'] ? '0' : 'IFNULL(lt.id_msg, IFNULL(lmr.id_msg, -1)) + 1') . ' AS new_from,
            ' . ($user_info['is_guest'] ? '0' : 'ln.id_topic') . ' AS is_marked_notify,
            t.id_last_msg, t.approved, t.unapproved_posts, ml.poster_time AS last_poster_time,
            ml.id_msg_modified, ml.subject AS last_subject, ml.icon AS last_icon,
            ml.poster_name AS last_member_name, ml.id_member AS last_id_member,
            IFNULL(meml.real_name, ml.poster_name) AS last_display_name, t.id_first_msg,
            mf.poster_time AS first_poster_time, mf.subject AS first_subject, mf.icon AS first_icon,
            mf.poster_name AS first_member_name, mf.id_member AS first_id_member,
            IFNULL(memf.real_name, mf.poster_name) AS first_display_name, SUBSTRING(ml.body, 1, 500) AS last_body,
            SUBSTRING(mf.body, 1, 500) AS first_body, ml.smileys_enabled AS last_smileys, mf.smileys_enabled AS first_smileys,
            IFNULL(al.id_attach, 0) AS id_attach_last, al.filename as filename_last, al.attachment_type as attachment_type_last, meml.avatar as avatar_last,
            IFNULL(af.id_attach, 0) AS id_attach_first, af.filename as filename_first, af.attachment_type as attachment_type_first, memf.avatar as avatar_first
        FROM {db_prefix}topics AS t
            INNER JOIN {db_prefix}messages AS ml ON (ml.id_msg = t.id_last_msg)
            INNER JOIN {db_prefix}messages AS mf ON (mf.id_msg = t.id_first_msg)
            LEFT JOIN {db_prefix}members AS meml ON (meml.id_member = ml.id_member)
            LEFT JOIN {db_prefix}members AS memf ON (memf.id_member = mf.id_member)
            LEFT JOIN {db_prefix}attachments AS al ON (al.id_member = meml.id_member)
            LEFT JOIN {db_prefix}attachments AS af ON (af.id_member = memf.id_member)' . ($user_info['is_guest'] ? '' : '
            LEFT JOIN {db_prefix}log_topics AS lt ON (lt.id_topic = t.id_topic AND lt.id_member = {int:current_member})
            LEFT JOIN {db_prefix}log_mark_read AS lmr ON (lmr.id_board = {int:current_board} AND lmr.id_member = {int:current_member})
            LEFT JOIN {db_prefix}log_notify AS ln ON (ln.id_topic = t.id_topic AND ln.id_member = {int:current_member})'). '
        WHERE t.id_topic = {int:current_topic}',
        array(
            'current_board' => intval($fid),
            'current_topic' => intval($tid),
            'current_member' => $user_info['id'],
        )
    );
    $topic = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);

    if(empty($topic)) return array();

    // Check for notifications on this topic
    $request = $smcFunc['db_query']('', '
        SELECT sent, id_topic
        FROM {db_prefix}log_notify
        WHERE id_topic = {int:current_topic} AND id_member = {int:current_member}
        LIMIT 1',
        array(
            'current_member' => $user_info['id'],
            'current_topic' => $topic['id_topic'],
        )
    );
    $topic['is_marked_notify'] = $smcFunc['db_num_rows']($request) ? true : false;
    $smcFunc['db_free_result']($request);
    $started = $topic['first_id_member'] == $user_info['id'];
    $topic['can_lock']        = $mobi_permission[$fid]['lock_any'] || ($started && $mobi_permission[$fid]['lock_own']);
    $topic['can_sticky']      = $mobi_permission[$fid]['make_sticky'] && !empty($modSettings['enableStickyTopics']);
    $topic['can_move']        = $mobi_permission[$fid]['move_any'] || ($started && $mobi_permission[$fid]['move_own']);
    $topic['can_modify']      = $mobi_permission[$fid]['modify_any'] || ($started && $mobi_permission[$fid]['modify_own']);
    $topic['can_remove']      = $mobi_permission[$fid]['remove_any'] || ($started && $mobi_permission[$fid]['remove_own']);
    $topic['can_approve']     = $mobi_permission[$fid]['approve_posts'];
    $topic['can_mark_notify'] = $mobi_permission[$fid]['mark_notify'] && !$user_info['is_guest'];


    $topic['is_sticky'] = !empty($modSettings['enableStickyTopics']) && !empty($topic['is_sticky']);
    $topic['is_locked'] = !empty($topic['locked']);
    $topic['is_approved'] = !empty($topic['approved']);
    $topic['new']       = $topic['new_from'] <= $topic['id_msg_modified'];
    $topic['last_poster_timestamp'] = $topic['last_poster_time'];
    $topic['last_poster_time'] = timeformat($topic['last_poster_time']);
    $topic['first_poster_time'] = timeformat($topic['first_poster_time']);

    if (!empty($settings['message_index_preview']))
    {
        // Limit them to 100 characters - do this FIRST because it's a lot of wasted censoring otherwise.
        $topic['first_body'] = basic_clean($topic['first_body'], 200, 1);
        $topic['last_body'] = basic_clean($topic['last_body'], 200, 1);

        // Censor the subject and message preview.
        censorText($topic['first_subject']);
        censorText($topic['first_body']);

        // Don't censor them twice!
        if ($topic['id_first_msg'] == $topic['id_last_msg'])
        {
            $topic['last_subject'] = $topic['first_subject'];
            $topic['last_body'] = $topic['first_body'];
        }
        else
        {
            censorText($topic['last_subject']);
            censorText($topic['last_body']);
        }
    }
    else
    {
        $topic['first_body'] = '';
        $topic['last_body'] = '';
        censorText($topic['first_subject']);

        if ($topic['id_first_msg'] == $topic['id_last_msg'])
            $topic['last_subject'] = $topic['first_subject'];
        else
            censorText($topic['last_subject']);
    }

    if (!empty($settings['show_user_images']))
    {
        $topic['first_poster_avatar'] = $topic['avatar_first'] == '' ? ($topic['id_attach_first'] > 0 ? (empty($topic['attachment_type_first']) ? $scripturl . '?action=dlattach;attach=' . $topic['id_attach_first'] . ';type=avatar' : $modSettings['custom_avatar_url'] . '/' . $topic['filename_first']) : '') : (stristr($topic['avatar_first'], 'http://') ? $topic['avatar_first'] : $modSettings['avatar_url'] . '/' . $topic['avatar_first']);
        $topic['last_poster_avatar'] = $topic['avatar_last'] == '' ? ($topic['id_attach_last'] > 0 ? (empty($topic['attachment_type_last']) ? $scripturl . '?action=dlattach;attach=' . $topic['id_attach_last'] . ';type=avatar' : $modSettings['custom_avatar_url'] . '/' . $topic['filename_last']) : '') : (stristr($topic['avatar_last'], 'http://') ? $topic['avatar_last'] : $modSettings['avatar_url'] . '/' . $topic['avatar_last']);
    }

    $topic['num_views'] = intval($topic['num_views']);
    $topic['num_replies'] = intval($topic['num_replies']);

    return $topic;
}

function mobiquo_parse_bbc($message, $smileys = true, $cache_id = '', $parse_tags = array())
{
    global $user_info, $modSettings, $context;

    $message = preg_replace_callback('/\[code\](.*?)\[\/code\]/si', create_function('$matches','return "[code]".exttmbq_convert_for_code($matches[1])."[/code]";'), $message);
    //$message = preg_replace_callback('/\[code[^\]]*\](.*?)\[\/code\]/si', create_function('$matches','return "[code]".exttmbq_convert_for_code($matches[1])."[/code]";'), $message);
    $message = preg_replace('/\[quote([^\]]*)\]/si', '[dummyquote$1]', $message);   //normal quote
    $message = preg_replace('/\[\/quote\]/si', '[/dummyquote]', $message);  //normal quote
    //$message = preg_replace('/\[(\/?)(code|php|html)\]/si', '[$1quote]', $message);
    $message = preg_replace('/\[(\/?)(php|html)\]/si', '[$1quote]', $message);
    $message = process_list_tag($message);
    $message = preg_replace_callback('/\[(youtube|yt)\](.*?)\[\/\1\]/si', "video_bbcode_format", $message);
    $user_info['time_format'] = $user_info['user_time_format'];
    $modSettings['todayMod'] = $modSettings['todayMod_bak'];
    $message = isset($context['user_post_avaible']) && $context['user_post_avaible']? $message : preg_replace('/\[hide\](.*?)\[\/hide\]/','',$message);
    $message = str_replace('[spoiler]', "\nSpoiler for Hiden:\n[quote]", $message);
    $message = str_replace('[/spoiler]', '[/quote]', $message);
    $message = parse_bbc($message, $smileys, $cache_id, $parse_tags);
    $user_info['time_format'] = '%Y%m%dT%H:%M:%S+00:00';
    $modSettings['todayMod'] = 0;

    return $message;
}

function exttmbq_convert_for_code($str) {
    $leftJ = $GLOBALS['exttMbqVarArr']['microtime'].'-leftJ-'.$GLOBALS['exttMbqVarArr']['microtime'];
    $rightJ= $GLOBALS['exttMbqVarArr']['microtime'].'-rightJ-'.$GLOBALS['exttMbqVarArr']['microtime'];
    $leftZ = $GLOBALS['exttMbqVarArr']['microtime'].'-leftZ-'.$GLOBALS['exttMbqVarArr']['microtime'];
    $rightZ= $GLOBALS['exttMbqVarArr']['microtime'].'-rightZ-'.$GLOBALS['exttMbqVarArr']['microtime'];
    $str = str_replace('<', $leftJ, $str);
    $str = str_replace('>', $rightJ, $str);
    $str = str_replace('[', $leftZ, $str);
    $str = str_replace(']', $rightZ, $str);
    return $str;
}

function exttmbq_restore_for_code($str) {
    $leftJ = $GLOBALS['exttMbqVarArr']['microtime'].'-leftJ-'.$GLOBALS['exttMbqVarArr']['microtime'];
    $rightJ= $GLOBALS['exttMbqVarArr']['microtime'].'-rightJ-'.$GLOBALS['exttMbqVarArr']['microtime'];
    $leftZ = $GLOBALS['exttMbqVarArr']['microtime'].'-leftZ-'.$GLOBALS['exttMbqVarArr']['microtime'];
    $rightZ= $GLOBALS['exttMbqVarArr']['microtime'].'-rightZ-'.$GLOBALS['exttMbqVarArr']['microtime'];
    $str = str_replace($leftJ, '<', $str);
    $str = str_replace($rightJ, '>', $str);
    $str = str_replace($leftZ, '[', $str);
    $str = str_replace($rightZ, ']', $str);
    return $str;
}

function video_bbcode_format($matches)
{
    $type = $matches[1];
    $url = $matches[2];
    $url = trim($url);

    switch (strtolower($type)) {
        case 'yt':
        case 'youtube':
            if (preg_match('#^(https?://)?((www|m)\.)?(youtube\.com/(watch\?.*?v=|v/)|youtu\.be/)([-\w]+)#', $url, $matches)) {
                $message = '[url='.$url.']YouTube Video[/url]';
            } else if (preg_match('/^[-\w]+$/', $url)) {
                $url = 'http://www.youtube.com/watch?v='.$url;
                $message = '[url='.$url.']YouTube Video[/url]';
            } else {
                $message = '';
            }
            break;

        default: $message = '';
    }
    return $message;
}

function mobi_parse_requrest()
{
    global $request_name, $request_params, $params_num, $server_data;

    $ver = phpversion();
    if ($ver[0] >= 5) {
        $data = file_get_contents('php://input');
    } else {
        $data = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : '';
    }

    $server_data = $data;

    if (count($_SERVER) == 0)
    {
        $r = new xmlrpcresp('', 15, 'XML-RPC: '.__METHOD__.': cannot parse request headers as $_SERVER is not populated');
        echo $r->serialize('UTF-8');
        exit;
    }

    if(isset($_SERVER['HTTP_CONTENT_ENCODING'])) {
        $content_encoding = str_replace('x-', '', $_SERVER['HTTP_CONTENT_ENCODING']);
    } else {
        $content_encoding = '';
    }

    if($content_encoding != '' && strlen($data)) {
        if($content_encoding == 'deflate' || $content_encoding == 'gzip') {
            // if decoding works, use it. else assume data wasn't gzencoded
            if(function_exists('gzinflate')) {
                if ($content_encoding == 'deflate' && $degzdata = @gzuncompress($data)) {
                    $data = $degzdata;
                } elseif ($degzdata = @gzinflate(substr($data, 10))) {
                    $data = $degzdata;
                }
            } else {
                $r = new xmlrpcresp('', 106, 'Received from client compressed HTTP request and cannot decompress');
                echo $r->serialize('UTF-8');
                exit;
            }
        }
    }

    $parsers = php_xmlrpc_decode_xml($data);
    if($parsers)
    {
        $request_name = $parsers->methodname;
        $request_params = php_xmlrpc_decode(new xmlrpcval($parsers->params, 'array'));
        $params_num = count($request_params);
    }
}

function mobiquo_encode($str, $mode = '')
{
    if (empty($str)) return $str;

    static $charset, $charset_89, $charset_AF, $charset_8F, $charset_chr, $charset_html, $support_mb, $charset_entity;

    if (!isset($charset))
    {
        global $context;
        $charset = $context['character_set'];

        include_once('include/charset.php');

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

    return remove_unknown_char($str);
}

/**
 * send email
 *
 * @return  Boolean
 */
function exttmbq_sendmail($email, $subject, $body) {
    global $modSettings, $webmaster_email;
    if ( substr(phpversion(),0,1) < 5 ) {
        $subject = mobiquo_encode($subject,'to_local');
        $body = mobiquo_encode($body,'to_local');
        return sendmail($email, $subject, $body);
    } else {
        if ($modSettings['mail_type'] == 1) {
            $mail = new PHPMailer();
            $mail->CharSet = 'utf-8';
            $mail->IsSMTP();
            $mail->Host = $modSettings['smtp_host'];
            $mail->Port = $modSettings['smtp_port'];

            $mail->From = $webmaster_email;
            $mail->FromName   = "";
            $mail->SMTPAuth = true;

            $mail->Username = $modSettings['smtp_username'];
            $mail->Password = base64_decode($modSettings['smtp_password']);

            $mail->IsHTML(true);
            $mail->WordWrap = 50;
            $mail->Subject=$subject;
            $mail->AltBody = $body;
            $mail->Body = str_replace("\r", "", $mail->AltBody);
            $mail->Body = str_replace("\n", "<br />", $mail->Body);
            //$mail->MsgHTML($body);

            $mail->AddAddress($email);

            if(!$mail->Send())
            {
                return false;
            }else
            {
                return true;
            }
        } else {
            $subject = mobiquo_encode($subject,'to_local');
            $body = mobiquo_encode($body,'to_local');
            return sendmail($email, $subject, $body);
        }
    }
}

function remove_unknown_char($str)
{
    for ($i = 1; $i < 32; $i++)
    {
        if (in_array($i, array(10, 13))) continue;
        $str = str_replace(chr($i), '', $str);
    }

    return $str;
}

function process_list_tag($str)
{
    $contents = preg_split('#(\[list\s+type=[^\]]*?\]|\[/?list\])#siU', $str, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

    $result = '';
    $status = 'out';
    foreach($contents as $content)
    {
        if ($status == 'out')
        {
            if ($content == '[list]')
            {
                $status = 'inlist';
            } elseif (strpos($content, '[list ') !== false)
            {
                $status = 'inorder';
            } else {
                $result .= $content;
            }
        } elseif ($status == 'inlist')
        {
            if ($content == '[/list]')
            {
                $status = 'out';
            } else
            {
                $result .= preg_replace('/\[li\](.*?)\[\/li\]/', '  * $1', ltrim($content));
            }
        } elseif ($status == 'inorder')
        {
            if ($content == '[/list]')
            {
                $status = 'out';
            } else
            {

                $result .= preg_replace_callback('/\[li\](.*?)\[\/li\]/si', 'mobi_list_tag_callback', ltrim($content));
            }
        }
    }

    return $result;
}
$mobi_list_tag_index = 1;
function mobi_list_tag_callback($matches)
{
    return '* '. $matches[1];
}
function mobi_table_exist($table_name)
{
    global $smcFunc, $db_prefix;
    db_extend();
    $tables = $smcFunc['db_list_tables'](false, $db_prefix . $table_name);
    return !empty($tables);
}

function getStarndardNameByTableKey($key)
{
    $starndard_key_map = array(
        'conv'     => 'conv',
        'pm'       => 'pm',
        'subscribe'=> 'sub',
        'liked'    => 'like',
        'quote'    => 'quote',
        'newtopic' => 'newtopic',
        'tag'      => 'tag',
        'announcement'      => '',
    );
    return isset($starndard_key_map[$key])? $starndard_key_map[$key]: '';
}

function tp_get_forum_icon($id, $type = 'forum', $lock = false, $new = false)
{
    if (!in_array($type, array('link', 'category', 'forum')))
        $type = 'forum';

    $icon_name = $type;
    if ($type != 'link')
    {
        if ($lock) $icon_name .= '_lock';
        if ($new) $icon_name .= '_new';
    }

    $icon_map = array(
        'category_lock_new' => array('category_lock', 'category_new', 'lock_new', 'category', 'lock', 'new'),
        'category_lock'     => array('category', 'lock'),
        'category_new'      => array('category', 'new'),
        'lock_new'          => array('lock', 'new'),
        'forum_lock_new'    => array('forum_lock', 'forum_new', 'lock_new', 'forum', 'lock', 'new'),
        'forum_lock'        => array('forum', 'lock'),
        'forum_new'         => array('forum', 'new'),
        'category'          => array(),
        'forum'             => array(),
        'lock'              => array(),
        'new'               => array(),
        'link'              => array(),
    );

    $final = empty($icon_map[$icon_name]);

    if ($url = tp_get_forum_icon_by_name($id, $icon_name, $final))
        return $url;

    foreach ($icon_map[$icon_name] as $sub_name)
    {
        $final = empty($icon_map[$sub_name]);
        if ($url = tp_get_forum_icon_by_name($id, $sub_name, $final))
            return $url;
    }

    return '';
}

function tp_get_forum_icon_by_name($id, $name, $final)
{
    global $boarddir, $boardurl;

    $tapatalk_forum_icon_dir = $boarddir.'/mobiquo/forum_icons/';
    $tapatalk_forum_icon_url = $boardurl.'/mobiquo/forum_icons/';

    $filename_array = array(
        $name.'_'.$id.'.png',
        $name.'_'.$id.'.jpg',
        $id.'.png', $id.'.jpg',
        $name.'.png',
        $name.'.jpg',
    );

    foreach ($filename_array as $filename)
    {
        if (file_exists($tapatalk_forum_icon_dir.$filename))
        {
            return $tapatalk_forum_icon_url.$filename;
        }
    }

    if ($final) {
        if (file_exists($tapatalk_forum_icon_dir.'default.png'))
            return $tapatalk_forum_icon_url.'default.png';
        else if (file_exists($tapatalk_forum_icon_dir.'default.jpg'))
            return $tapatalk_forum_icon_url.'default.jpg';
    }

    return '';
}

function update_push()
{
    global $smcFunc, $user_info, $db_prefix;

    if ($user_info['id'] && mobi_table_exist('tapatalk_users'))
    {
        $request = $smcFunc['db_query']('','SELECT * FROM {db_prefix}tapatalk_users WHERE userid = {int:user_id}',
                array(
                    'user_id' => $user_info['id'],
                ));
        if ($smcFunc['db_num_rows']($request) == 0)
        {
            $request = $smcFunc['db_insert']('ignore',
                        '{db_prefix}tapatalk_users',
                        array('userid' => 'int', 'updated' => 'int'),
                        array($user_info['id'], time()),
                        array('userid')
                    );
        }
        else
        {
            $smcFunc['db_query']('', '
                UPDATE {db_prefix}tapatalk_users
                SET updated = '.time().'
                WHERE userid = {int:user_id}',
                array(
                    'user_id' => $user_info['id'],
                )
            );
        }
    }
}

function getEmailFromScription($token, $code, $key = '')
{
    global $boardurl;

    include_once('lib/classTTJson.php');
    $verification_url = 'http://directory.tapatalk.com/au_reg_verify.php?token='.$token.'&'.'code='.$code.'&key='.$key.'&url='.$boardurl;
    $response = getContentFromRemoteServer($verification_url, 10, $error);
    if($response)
        $result = @ json_decode($response, true);
    if(isset($result) && isset($result['result']))
        return $result;
    else
    {
        $data = array(
            'token' => $token,
            'code'  => $code,
            'key'   => $key,
            'url'   => $boardurl,
        );
        $response = getContentFromRemoteServer('http://directory.tapatalk.com/au_reg_verify.php', 10, $error, 'POST', $data);
        if($response)
            $result = @ json_decode($response, true);
        if(isset($result) && isset($result['result']))
            return $result;
        else
            return 0; //No connection to Tapatalk Server.
    }
}

/**
 * Get content from remote server
 *
 * @param string $url      NOT NULL          the url of remote server, if the method is GET, the full url should include parameters; if the method is POST, the file direcotry should be given.
 * @param string $holdTime [default 0]       the hold time for the request, if holdtime is 0, the request would be sent and despite response.
 * @param string $error_msg                  return error message
 * @param string $method   [default GET]     the method of request.
 * @param string $data     [default array()] post data when method is POST.
 *
 * @exmaple: getContentFromRemoteServer('http://push.tapatalk.com/push.php', 0, $error_msg, 'POST', $ttp_post_data)
 * @return string when get content successfully|false when the parameter is invalid or connection failed.
*/
function getContentFromRemoteServer($url, $holdTime = 0, &$error_msg, $method = 'GET', $data = array())
{
    require_once(dirname(__FILE__).'/lib/classTTConnection.php');
    $connection = new classTTConnection();
    $connection->timeout = $holdTime;
    $response = $connection->getContentFromSever($url, $data, $method);
    $error_msg = $connection->errors;
    return $response;
}

function mobi_lang($err_type)
{
    global $boarddir, $mobiquo_dir, $txt, $tptxt;

    include_once $boarddir.'/'.$mobiquo_dir.'/config/Tapatalk.language.php';

    return isset($tptxt[$err_type])? $tptxt[$err_type] : $txt[$err_type];
}

function get_user_by_name_or_email($name, $is_email = false)
{
    global $smcFunc;

    if(empty($name)) return array();

    if(!$is_email)
    {
       $request = $smcFunc['db_query']('', '
           SELECT passwd, member_name, id_member, id_group, lngfile, is_activated, email_address, additional_groups, member_name, password_salt,
           openid_uri, passwd_flood, id_post_group, validation_code
           FROM {db_prefix}members
           WHERE ' . ($smcFunc['db_case_sensitive'] ? 'LOWER(member_name) = LOWER({string:user_name})' : 'member_name = {string:user_name}') . '
           LIMIT 1',
           array(
           'user_name' => $smcFunc['db_case_sensitive'] ? strtolower($name) : $name,
           )
       );
    }
    else
    {
        $request = $smcFunc['db_query']('', '
            SELECT passwd, member_name, id_member, id_group, lngfile, is_activated, email_address, additional_groups, member_name, password_salt, openid_uri,
            passwd_flood, validation_code
            FROM {db_prefix}members
            WHERE email_address = {string:user_name}
            LIMIT 1',
            array(
            'user_name' => $name,
            )
        );
    }
    $user = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    return $user;
}

function error_status($status = 0, $result_text = '')
{
    @ob_clean();

    if(!headers_sent())
    {
        header('200 OK');
        header('Mobiquo_is_login: false');
        header('Content-Type: text/xml');
    }

    $response = new xmlrpcresp(
        new xmlrpcval(array(
            'result'        => new xmlrpcval(false, 'boolean'),
            'status'        => new xmlrpcval($status, 'string'),
            'result_text'   => new xmlrpcval($result_text, 'base64'),
        ),'struct')
    );

    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".$response->serialize('UTF-8');
    exit;
}

function merge_users($uids, $_uids)
{
    if(!empty($_uids) && is_array($_uids))
    {
        foreach($_uids as $id => $score)
        {
            if(isset($uids[$id]))
            {
                $uids[$id] += $score;
            }
            else
            {
                $uids[$id] = $score;
            }
        }
    }
    return $uids;
}

function keyED($txt,$encrypt_key)
{
    $encrypt_key = md5($encrypt_key);
    $ctr=0;
    $tmp = "";
    for ($i=0;$i<strlen($txt);$i++)
    {
        if ($ctr==strlen($encrypt_key)) $ctr=0;
        $tmp.= substr($txt,$i,1) ^ substr($encrypt_key,$ctr,1);
        $ctr++;
    }
    return $tmp;
}

function encrypt($txt,$key)
{
    srand((double)microtime()*1000000);
    $encrypt_key = md5(rand(0,32000));
    $ctr=0;
    $tmp = "";
    for ($i=0;$i<strlen($txt);$i++)
    {
        if ($ctr==strlen($encrypt_key)) $ctr=0;
        $tmp.= substr($encrypt_key,$ctr,1) .
        (substr($txt,$i,1) ^ substr($encrypt_key,$ctr,1));
        $ctr++;
    }
    return keyED($tmp,$key);
}

/* calculate the flags for In App Registration */
function exttMbqMakeFlags() {
    global $modSettings;

    /* calculate the flags for In App Registration */
    /* default */
    $exttMbqSignIn = 1;
    $exttMbqInappreg = 1;
    $exttMbqSsoLogin = 1;
    $exttMbqSsoSignin = 1;
    $exttMbqSsoRegister = 1;
    $exttMbqNativeRegister = 1;
    /* final */
    if ($modSettings['registration_method'] == 3)
    {
        $exttMbqSignIn = 0;
        $exttMbqInappreg = 0;
        $exttMbqSsoSignin = 0;
        $exttMbqSsoRegister = 0;
        $exttMbqNativeRegister = 0;
    }
    if (!function_exists('curl_init') && !@ini_get('allow_url_fopen'))
    {
        $exttMbqSignIn = 0;
        $exttMbqInappreg = 0;
        $exttMbqSsoLogin = 0;
        $exttMbqSsoSignin = 0;
        $exttMbqSsoRegister = 0;
    }
    if (isset($modSettings['tp_iar_registration_options'])) {
        if (!$modSettings['tp_iar_registration_options']) {
            $exttMbqSignIn = 0;
            $exttMbqInappreg = 0;
            $exttMbqSsoSignin = 0;
            $exttMbqSsoRegister = 0;
            $exttMbqNativeRegister = 0;
        }
    }

    ExttMbqBase::$otherParameters['exttMbqSignIn'] = $exttMbqSignIn;
    ExttMbqBase::$otherParameters['exttMbqInappreg'] = $exttMbqInappreg;
    ExttMbqBase::$otherParameters['exttMbqSsoLogin'] = $exttMbqSsoLogin;
    ExttMbqBase::$otherParameters['exttMbqSsoSignin'] = $exttMbqSsoSignin;
    ExttMbqBase::$otherParameters['exttMbqSsoRegister'] = $exttMbqSsoRegister;
    ExttMbqBase::$otherParameters['exttMbqNativeRegister'] = $exttMbqNativeRegister;
}

/**
 * get attachments
 *
 * @param  Int  $id
 * @return  Array
 */
function exttMbqGetAtt($id)
{
    global $smcFunc;

    if ($id) {
        $ret = array();
        $request = $smcFunc['db_query']('', '
            SELECT
                a.id_attach, a.id_folder, a.id_msg, a.filename, a.file_hash, a.size as filesize, a.downloads, a.approved, a.width, a.height, a.id_thumb, b.width as thumb_width, b.height as thumb_height
            FROM {db_prefix}attachments AS a
                LEFT JOIN {db_prefix}attachments AS b ON (a.id_thumb = b.id_attach AND b.attachment_type = 3)
            WHERE
                a.id_msg = {int:id} AND a.attachment_type = 0',
            array(
                'id' => $id,
            )
        );
        while ($row = $smcFunc['db_fetch_assoc']($request)) {
            $ret[$row['id_msg']][] = $row;
        }
        $smcFunc['db_free_result']($request);
        $GLOBALS['attachments'] = $ret;
        require_once('include/Display.php');
        return loadAttachmentContext($id);
    } else {
        return array();
    }
}

/**
 * get post
 *
 * @param  Mixed  $id  post id
 * @return  Mixed
 */
function exttMbqGetPost($id) {
    global $smcFunc;

    $request = $smcFunc['db_query']('', '
        SELECT
            *
        FROM {db_prefix}messages AS a
        WHERE
            a.id_msg = {int:id}',
        array(
            'id' => intval($id),
        )
    );
    if ($row = $smcFunc['db_fetch_assoc']($request)) {
        $smcFunc['db_free_result']($request);
        return $row;
    } else {
        $smcFunc['db_free_result']($request);
        return false;
    }
}

/**
 * get topic
 *
 * @param  Mixed  $id  topic id
 * @return  Mixed
 */
function exttMbqGetTopic($id) {
    global $smcFunc;

    $request = $smcFunc['db_query']('', '
        SELECT
            *
        FROM {db_prefix}topics AS a
        WHERE
            a.id_topic = {int:id}',
        array(
            'id' => $id,
        )
    );
    if ($row = $smcFunc['db_fetch_assoc']($request)) {
        $smcFunc['db_free_result']($request);
        return $row;
    } else {
        $smcFunc['db_free_result']($request);
        return false;
    }
}

function set_report_level()
{
    if (isset($_SERVER['HTTP_DEBUG']) && $_SERVER['HTTP_DEBUG'])
    {
        error_reporting(-1);
        @ini_set('display_errors', 1);
    }
    else
        error_reporting(0);
}

function tt_encrypt($string, $password){
    if (empty($password)){
        return false;
    }
    require_once TT_ROOT.'lib/classTTCipherEncrypt.php';
    $e = new TT_Cipher;
    return $e->encrypt($string, $password);
}

function tt_handle_custome_field_type($type){
    $field_type = '';
    switch ($type){
        case 'text':
            $field_type = 'input';
            break;
        case 'textarea':
            $field_type = 'textarea';
            break;
        case 'check':
        case 'radio':
            $field_type = 'radio';
            break;
        case 'select':
            $field_type = 'drop';
            break;
        default:

    }
    return $field_type;
}
function tt_get_user_type($userId)
{
    global $user_profile;

    loadMemberData($userId, false, 'profile');
    $profile = $user_profile[$userId];
    $user_type = 'normal';
    if (is_numeric($profile['id_group'])) {
        if ($profile['id_group'] == 0) {
            $user_type = 'normal';
        } elseif ($profile['id_group'] == 1) {
            $user_type = 'admin';
        } elseif (($profile['id_group'] == 2) || ($profile['id_group'] == 3)) {
            $user_type = 'mod';
        }
    }
    if(tt_checkIfUserIsBanned($userId))
    {
        $user_type = 'banned';
    }
    return $user_type;

}
function tt_checkIfUserIsBanned($userId)
{
    global $smcFunc;
    $request = $smcFunc['db_query']('', '
		SELECT DISTINCT mem.id_member
		FROM {db_prefix}ban_groups AS bg
			INNER JOIN {db_prefix}ban_items AS bi ON (bg.id_ban_group = bi.id_ban_group)
			INNER JOIN {db_prefix}members AS mem ON (bi.id_member = mem.id_member)
		WHERE (bg.cannot_access = {int:cannot_access} OR bg.cannot_login = {int:cannot_login})
			AND mem.id_member = {int:member_id}',
        array(
            'cannot_access' => 1,
            'cannot_login' => 1,

            'member_id' => $userId
        )
    );
    $isBanned= false;
    while ($row = $smcFunc['db_fetch_assoc']($request))
    {
        $isBanned = true;
    }
    return $isBanned;

}