<?php

if (!defined('DB_CONFIG')) die();

function swift_emails_co_modify_url_scheme()
{
    global $URL;

    $URL->add('swift_emails_records', 'apps/swift_emails/records.php');
}

function swift_emails_IncludeCommon()
{
    global $User, $URL, $SwiftMenu;

    if ($User->is_admin())
        $SwiftMenu->addItem(['title' => 'Sent Emails', 'link' => $URL->link('swift_emails_records'), 'id' => 'swift_emails', 'parent_id' => 'admin']);
}

function swift_emails_FnSendEmailEnd()
{
    global $DBLayer, $User, $response, $from_email, $to, $reply_to_email, $type, $subject, $message;

    $db_data = [
        'sent_time'         => time(),
        'sent_from'         => $User->get('id'),
        'from_email'        => isset($from_email) ? $from_email : 'n/a',
        'sent_to'           => isset($to) ? $to : 'n/a',
        'reply_to'          => isset($reply_to_email) ? $reply_to_email : 'n/a',
        'email_type'        => 'TEXT',
        'subject'           => isset($subject) ? $subject : 'n/a',
        'message'           => isset($message) ? $message : 'n/a',
        'response'          => ($response) ? 'sent_email(sent)' : $_SERVER["SCRIPT_FILENAME"]
    ];
    $new_id = $DBLayer->insert_values('swift_emails', $db_data);
/*
    $cleaning_emails_time = $db_data['sent_time'] - 5184000; // 2592000 = 1 month / 5184000 = 2 month
    $query = array(
        'DELETE'	=> 'swift_emails',
        'WHERE'		=> 'sent_time < '.$cleaning_emails_time
    );
    $DBLayer->query_build($query) or error(__FILE__, __LINE__);
*/
}

function swift_emails_ClassSwiftMailerFnSendEnd()
{
    global $DBLayer, $User, $SwiftMailer;

    if (isset($SwiftMailer))
    {
        $db_data = [
            'sent_time'         => time(),
            'sent_from'         => $User->get('id'),
            'from_email'        => isset($SwiftMailer->from_email) ? $SwiftMailer->from_email : '',
            'sent_to'           => isset($SwiftMailer->to) ? $SwiftMailer->to : '',
            'reply_to'          => !empty($SwiftMailer->reply_to) ? implode(', ', $SwiftMailer->reply_to) : '',
            'email_type'        => ($SwiftMailer->isHTML) ? 'HTML' : 'TEXT',
            'subject'           => isset($SwiftMailer->subject) ? $SwiftMailer->subject : '',
            'message'           => isset($SwiftMailer->message) ? $SwiftMailer->message : '',
            'response'          => ($SwiftMailer->sent) ? 'SENT' : $_SERVER["SCRIPT_FILENAME"]
        ];
        $new_id = $DBLayer->insert_values('swift_emails', $db_data);
/*  
        $cleaning_emails_time = $db_data['sent_time'] - 5184000; // 2592000 = 1 month / 5184000 = 2 month
        $query = array(
            'DELETE'	=> 'swift_emails',
            'WHERE'		=> 'sent_time < '.$cleaning_emails_time
        );
        $DBLayer->query_build($query) or error(__FILE__, __LINE__);
*/
    }
}
