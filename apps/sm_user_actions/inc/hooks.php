<?php

if (!defined('DB_CONFIG')) die();

function sm_user_actions_co_modify_url_scheme()
{
    global $URL;

    $URL->add('sm_user_actions', 'apps/sm_user_actions/actions.php');
}

function sm_user_actions_IncludeCommon()
{
    global $User, $URL, $Config, $SwiftMenu;

    if ($User->is_admin())
        $SwiftMenu->addItem(['title' => 'User Actions', 'link' => $URL->link('sm_user_actions'), 'id' => 'sm_user_actions', 'parent_id' => 'admin']);
}

function sm_user_actions_fn_redirect_start()
{
    global $DBLayer, $User, $message, $flash_message;

    $time_now = time();
    $db_data = [
        'user_id'       => $User->get('id'),
        'visit_time'    => $time_now,
        'ip'            => get_remote_address(),
        'cur_url'       => get_current_url(),
        'project_id'    => (defined('PAGE_SECTION_ID') ? PAGE_SECTION_ID : 'index'),
        'http_code'     => (http_response_code() ? http_response_code() : 'Unknown'),
        'message'       => (isset($message) ? $message : $flash_message)
    ];
    @$DBLayer->insert_values('sm_user_actions', $db_data);
}

function sm_user_actions_LoginPreRedirect()
{
    global $Config, $SwiftMailer, $form_username;

    if (isset($form_username))
    {
        $mail_subject = 'Autorization on '.html_encode($Config->get('o_board_title'));
        
        $mail_message = 'Autorization on '.html_encode($Config->get('o_board_title'))."\n\n";
        $mail_message .= 'Username: '.$form_username."\n\n";
        $mail_message .= 'Logged time: '.format_time(time())."\n\n";
        $mail_message .= 'Site URL: '.BASE_URL."\n\n";
        
        if (isset($_SERVER['HTTP_USER_AGENT']))
            $mail_message .= 'User Agent Info: '.$_SERVER['HTTP_USER_AGENT']."\n\n";
        
        $SwiftMailer = new SwiftMailer;
        $SwiftMailer->send($Config->get('o_admin_email'), $mail_subject, $mail_message);
    }
}

function sm_user_actions_ft_end()
{
    global $Core, $DBLayer, $User;

   // if (!$User->is_admin()){
        $time_now = time();
        $db_data = [
            'user_id'       => $User->get('id'),
            'visit_time'    => $time_now,
            'ip'            => get_remote_address(),
            'cur_url'       => get_current_url(),
            'project_id'    => (defined('PAGE_SECTION_ID') ? PAGE_SECTION_ID : 'index'),
            'http_code'     => (http_response_code() ? http_response_code() : 'Unknown'),
            'message'       => (!empty($Core->errors) ? implode("\n", $Core->errors) : '')
        ];
        $DBLayer->insert_values('sm_user_actions', $db_data);

        $cleaning_time = $time_now - 7776000;// 2592000 - 1 month // 7776000 - 3 months
        $query = array(
            'DELETE'	=> 'sm_user_actions',
            'WHERE'		=> 'visit_time < '.$cleaning_time
        );
        @$DBLayer->query_build($query);
    //}
}
