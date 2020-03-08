<?php
defined('IN_MOBIQUO') or exit;
include_once TT_ROOT.'lib/classTTSSO.php';

class TTSSOForum implements TTSSOForumInterface
{
    public function getUserByEmail($email)
    {
        return get_user_by_name_or_email($email , true);
    }

    public function getUserByName($username)
    {
        return get_user_by_name_or_email($username , false);
    }

    public function validateUsernameHandle($username)
    {
        global $sourcedir, $smcFunc, $context, $txt;

        // Clean it up like mother would.
        $username = preg_replace('~[\t\n\r \x0B\0' . ($context['utf8'] ? ($context['server']['complex_preg_chars'] ? '\x{A0}\x{AD}\x{2000}-\x{200F}\x{201F}\x{202F}\x{3000}\x{FEFF}' : "\xC2\xA0\xC2\xAD\xE2\x80\x80-\xE2\x80\x8F\xE2\x80\x9F\xE2\x80\xAF\xE2\x80\x9F\xE3\x80\x80\xEF\xBB\xBF") : '\x00-\x08\x0B\x0C\x0E-\x19\xA0') . ']+~' . ($context['utf8'] ? 'u' : ''), ' ', $username);
        if ($smcFunc['strlen']($username) > 25){
            $username = $smcFunc['htmltrim']($smcFunc['substr']($username, 0, 25));
        }
        // Only these characters are permitted.
        if (preg_match('~[<>&"\'=\\\]~', preg_replace('~&#(?:\\d{1,7}|x[0-9a-fA-F]{1,6});~', '', $username)) != 0 || $username == '_' || $username == '|' || strpos($username, '[code') !== false || strpos($username, '[/code') !== false){
            return false;
        }

        if (stristr($username, $txt['guest_title']) !== false){
            return false;
        }

        if (trim($username) == ''){
            return false;
        }else{
            require_once($sourcedir . '/Subs-Members.php');
            return isReservedName($username, 0, false, false) ? false : true;
        }
    }

    public function validatePasswordHandle($password)
    {
        global $sourcedir;

        require_once($sourcedir . '/Subs-Auth.php');
        $passwordError = validatePassword($password, $regOptions['username'], array($regOptions['email']));

        // Password isn't legal?
        if ($passwordError === null){
            return true;
        }
        return fatal_lang_error('profile_error_password_' . $passwordError, false);
    }

    public function createUserHandle($email, $username, $password, $verified, $custom_register_fields, $profile, &$errors)
    {
        global $sourcedir, $context, $modSettings, $maintenance, $mmessage, $scripturl, $smcFunc;

        checkSession();

        $_POST['emailActivate'] = true;

        if(empty($password)) get_error('password cannot be empty');
        if(!($maintenance == 0)) get_error('Forum is in maintenance model or Tapatalk is disabled by forum administrator.');

        if ($modSettings['registration_method'] == 0){
            $register_mode = 'nothing';
        }else if ($modSettings['registration_method'] == 1){
            $register_mode = $verified ? 'nothing' : 'activation';
        }else{
            $register_mode = (isset($modSettings['auto_approval_tp_user']) && $modSettings['auto_approval_tp_user'] && $verified) ? 'nothing' : 'approval';
        }

        $email = htmltrim__recursive(str_replace(array("\n", "\r"), '', $email));
        $username = htmltrim__recursive(str_replace(array("\n", "\r"), '', $username));
        $password = htmltrim__recursive(str_replace(array("\n", "\r"), '', $password));

        $group = 0;
        if ($register_mode == 'nothing' && isset($modSettings['tp_iar_usergroup_assignment'])) {
            $group = $modSettings['tp_iar_usergroup_assignment'];
        }
        $regOptions = array(
            'interface' => $register_mode == 'approval' ? 'guest' : 'admin',
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'password_check' => $password,
            'check_reserved_name' => true,
            'check_password_strength' => true,
            'check_email_ban' => false,
            'send_welcome_email' => isset($_POST['emailPassword']) || empty($password),
            'require' => $register_mode,
            'memberGroup' => (int) $group,
        );

        // Collect all extra registration fields someone might have filled in.
        $possible_strings = array(
            'website_url', 'website_title',
            'aim', 'yim',
            'location', 'birthdate',
            'time_format',
            'buddy_list',
            'pm_ignore_list',
            'smiley_set',
            'signature', 'personal_text', 'avatar',
            'lngfile',
            'secret_question', 'secret_answer',
        );
        $possible_ints = array(
            'pm_email_notify',
            'notify_types',
            'icq',
            'gender',
            'id_theme',
        );
        $possible_floats = array(
            'time_offset',
        );
        $possible_bools = array(
            'notify_announcements', 'notify_regularity', 'notify_send_body',
            'hide_email', 'show_online',
        );

        if (!empty($custom_register_fields)){
            foreach ($custom_register_fields as $key => $value){
                if (in_array($key, $possible_ints)){
                    $regOptions['extra_register_vars'][$key] = (int)$value;
                }
                if (in_array($key, $possible_floats)){
                    $regOptions['extra_register_vars'][$key] = (float)$value;
                }
                if (in_array($key, $possible_bools)){
                    $regOptions['extra_register_vars'][$key] = empty($value) ? 0 : 1;
                }
                if (in_array($key, $possible_strings)){
                    $value = mobiquo_encode($value, 'to_local');
                    $regOptions['extra_register_vars'][$key] = $smcFunc['htmlspecialchars']($value, ENT_QUOTES);
                }
            }
        }

        define('mobi_register',1);
        require_once($sourcedir . '/Subs-Members.php');
        $memberID = registerMember($regOptions);

        $request = $smcFunc['db_query']('', '
            SELECT
                col_name, field_name, field_desc, field_type, field_length, field_options,
                default_value, bbc, enclose, placement
            FROM {db_prefix}custom_fields
            WHERE active = 1 AND show_reg != 0',
            array()
        );

        while ($custom_field = $smcFunc['db_fetch_assoc']($request)){
            $field_name = $custom_field['col_name'];
            if (!isset($custom_register_fields[$field_name])) continue;
            $field_value = mobiquo_encode($custom_register_fields[$field_name], 'to_local');

            if ($custom_field['field_type'] == 'check'){
                if (empty($field_value)){
                    continue;
                }
                $_POST['customfield'][$field_name] = 'on';
            }

            $_POST['customfield'][$field_name] = $field_value;
        }

        if (!empty($memberID) && !empty($_POST['customfield']))
        {
            require_once(ExttMbqBase::$otherParameters['sourcedir'] . '/Profile.php');
            require_once(ExttMbqBase::$otherParameters['sourcedir'] . '/Profile-Modify.php');
            makeCustomFieldChanges($memberID, 'register');
        }

        if (!empty($memberID))
        {
            $context['new_member'] = array(
            'id' => $memberID,
            'name' => $username,
            'href' => $scripturl . '?action=profile;u=' . $memberID,
            'link' => '<a href="' . $scripturl . '?action=profile;u=' . $memberID . '">' . $username . '</a>',
            );
            $context['registration_done'] = sprintf($txt['admin_register_done'], $context['new_member']['link']);

            //update profile
            if(isset($profile) && !empty($profile) && is_array($profile))
            {

                $profile_vars = array(
                'avatar' => $profile['avatar_url'],
                //'birthdate' => $_POST['tid_profile']['birthday'],
                //'gender' => $_POST['tid_profile']['gender'] == 'male' ? 1 : 2,
                //'location' => $_POST['tid_profile']['location'],
                //'personal_text' => $_POST['tid_profile']['description'],
                //'signature' => $_POST['tid_profile']['signature'],
                //'website_url' => $_POST['tid_profile']['link'],
                );
                updateMemberData($memberID, $profile_vars);
            }
            return get_user_by_name_or_email($username , false);
        }
        return null;
    }

    public function loginUserHandle($userInfo, $register)
    {
        global $request_name;
        $request_name = 'login';
        $_REQUEST['action'] = $_GET['action'] = $_POST['action'] = 'login2';
        $_REQUEST['user'] = $_GET['user'] = $_POST['user'] = $userInfo['member_name'];
        $_REQUEST['passwrd'] = $_GET['passwrd'] = $_POST['passwrd'] = $userInfo['passwd'];
        $_REQUEST['cookielength'] = $_GET['cookielength'] = $_POST['cookielength'] = -1;
        before_action_login();
        require_once('include/LogInOut.php');
        Login2();
    }

    public function getAPIKey(){
        global $modSettings;
        return $modSettings['tp_push_key'];
    }

    public function getForumUrl(){
        global $boardurl;
        return $boardurl;
    }

    function getEmailByUserInfo($userInfo){
        return $userInfo['email_address'];
    }
}
