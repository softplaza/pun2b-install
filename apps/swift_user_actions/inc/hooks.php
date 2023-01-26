<?php

if (!defined('DB_CONFIG')) die();

function swift_user_actions_co_modify_url_scheme()
{
    global $URL;

    $URL->add('swift_user_actions', 'apps/swift_user_actions/actions.php');
}

function swift_user_actions_IncludeCommon()
{
    global $Core, $User, $URL, $DBLayer, $SwiftMenu;

    if ($User->is_admin())
        $SwiftMenu->addItem(['title' => 'User Actions', 'link' => $URL->link('swift_user_actions'), 'id' => 'swift_user_actions', 'parent_id' => 'admin']);
}

// REDIRECT
function swift_user_actions_fn_redirect_start()
{
    global $DBLayer, $User, $message, $flash_message;

    if (!defined('SWIFT_USER_ACTIONS'))
    {
        $db_data = [
            'a_user_id'       => $User->get('id'),
            //'a_user_agent'    => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'a_time'          => time(),
            'a_ip'            => get_remote_address(),
            'a_cur_url'       => str_replace(BASE_URL, '', get_current_url()),
            'a_referer_url'   => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '',
            'a_project_id'    => (defined('PAGE_SECTION_ID') ? PAGE_SECTION_ID : ''),
            'a_http_code'     => '301',
            'a_message'       => (isset($message) ? $message : $flash_message),
            'a_type'          => 1 // redirect
        ];
        @$DBLayer->insert_values('swift_user_actions', $db_data);

        define('SWIFT_USER_ACTIONS', 1);
    }
}
// 
function swift_user_actions_ft_end()
{
    global $Core, $DBLayer, $User;

    $time_now = time();
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    if (!empty($Core->errors))
    {
        $db_data = [
            'a_user_id'       => $User->get('id'),
            'a_user_agent'    => $user_agent,
            'a_time'          => $time_now,
            'a_ip'            => get_remote_address(),
            'a_cur_url'       => str_replace(BASE_URL, '', get_current_url()),
            'a_referer_url'   => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '',
            'a_project_id'    => (defined('PAGE_SECTION_ID') ? PAGE_SECTION_ID : 'index'),
            'a_http_code'     => 'Form error',
            'a_message'       => (!empty($Core->errors) ? implode("\n", $Core->errors) : ''),
            'a_type'          => 2 // Form error
        ];
        @$DBLayer->insert_values('swift_user_actions', $db_data);
    }
    else if (!empty($Core->message))
    {
        $db_data = [
            'a_user_id'       => $User->get('id'),
            'a_user_agent'    => $user_agent,
            'a_time'          => $time_now,
            'a_ip'            => get_remote_address(),
            'a_cur_url'       => str_replace(BASE_URL, '', get_current_url()),
            'a_referer_url'   => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '',
            'a_project_id'    => (defined('PAGE_SECTION_ID') ? PAGE_SECTION_ID : 'index'),
            'a_http_code'     => 'System message',
            'a_message'       => $Core->message,
            'a_type'          => 3 // message
        ];
        @$DBLayer->insert_values('swift_user_actions', $db_data);
    }
    // Just visit
    else if (!$User->is_admin() && !defined('SWIFT_USER_ACTIONS'))
    {
        $db_data = [
            'a_user_id'       => $User->get('id'),
            'a_user_agent'    => $user_agent,
            'a_time'          => time(),
            'a_ip'            => get_remote_address(),
            'a_cur_url'       => str_replace(BASE_URL, '', get_current_url()),
            'a_referer_url'   => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '',
            'a_project_id'    => (defined('PAGE_SECTION_ID') ? PAGE_SECTION_ID : 'index'),
            'a_http_code'     => '200',
            'a_message'       => '',
            'a_type'          => 0 // 200
        ];
        @$DBLayer->insert_values('swift_user_actions', $db_data);
    }

    $cleaning_time = $time_now - 7776000;// 2592000 - 1 month // 7776000 - 3 months
    $query = array(
        'DELETE'	=> 'swift_user_actions',
        'WHERE'		=> 'a_type=0 AND a_time < '.$cleaning_time
    );
    @$DBLayer->query_build($query);
}

// 
function swift_user_actions_RewriteEmpty()
{
    global $DBLayer;

    if (!defined('SWIFT_USER_ACTIONS'))
    {
        $db_data = [
            //'a_user_id'       => $User->get('id'),
            'a_user_agent'    => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'a_time'          => time(),
            'a_ip'            => get_remote_address(),
            'a_cur_url'       => str_replace(BASE_URL, '', get_current_url()),
            'a_referer_url'   => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '',
            'a_project_id'    => (defined('PAGE_SECTION_ID') ? PAGE_SECTION_ID : ''),
            'a_http_code'     => '404',
            'a_message'       => 'Error 404: Page Not Found.',
            'a_type'          => 4 // 404
        ];
        $DBLayer->insert_values('swift_user_actions', $db_data);

        define('SWIFT_USER_ACTIONS', 1);
    }
}

function swift_user_actions_IncludeDBLayerEndTransaction()
{
    global $DBLayer, $User;

    if (!defined('SWIFT_USER_ACTIONS') && !defined('SPM_HEADER'))
    {
        $db_data = [
            'a_user_id'       => $User->get('id'),
            'a_user_agent'    => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'a_time'          => time(),
            'a_ip'            => get_remote_address(),
            'a_cur_url'       => str_replace(BASE_URL, '', get_current_url()),
            'a_referer_url'   => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '',
            'a_project_id'    => (defined('PAGE_SECTION_ID') ? PAGE_SECTION_ID : ''),
            'a_http_code'     => 'AJAX',
            'a_message'       => 'AJAX request.',
            'a_type'          => 5 // AJAX
        ];
        $DBLayer->insert_values('swift_user_actions', $db_data);

        define('SWIFT_USER_ACTIONS', 1);
    }
}

function swift_user_actions_IncludeFunctionsCsrfConfirmForm()
{
    global $DBLayer, $User;

    if (!defined('SWIFT_USER_ACTIONS'))
    {
        $db_data = [
            'a_user_id'       => $User->get('id'),
            'a_user_agent'    => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'a_time'          => time(),
            'a_ip'            => get_remote_address(),
            'a_cur_url'       => str_replace(BASE_URL, '', get_current_url()),
            'a_referer_url'   => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '',
            'a_project_id'    => (defined('PAGE_SECTION_ID') ? PAGE_SECTION_ID : ''),
            'a_http_code'     => 'CSRF',
            'a_message'       => 'CSRF Token.',
            'a_type'          => 6 // CSRF Token
        ];
        $DBLayer->insert_values('swift_user_actions', $db_data);

        define('SWIFT_USER_ACTIONS', 1);
    }
}

/*
function swift_user_actions_IncludeFunctionsErrorEnd()
{
    global $DBLayer, $email_content;

    if (!defined('SWIFT_USER_ACTIONS'))
    {
        $db_data = [
            //'a_user_id'       => $User->get('id'),
            //'a_user_agent'    => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'a_time'          => time(),
            'a_ip'            => get_remote_address(),
            'a_cur_url'       => str_replace(BASE_URL, '', get_current_url()),
            'a_project_id'    => (defined('PAGE_SECTION_ID') ? PAGE_SECTION_ID : ''),
            'a_http_code'     => '404',
            'a_message'       => !empty($email_content) ? implode("\n", $email_content) : '',
            'a_type'          => 5 // System Error
        ];
        @$DBLayer->insert_values('swift_user_actions', $db_data);

        define('SWIFT_USER_ACTIONS', 1);
    }
}
*/
