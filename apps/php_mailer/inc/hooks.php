<?php

// mPDF Hooks

function php_mailer_es_essentials()
{
    global $Config;

    //use PHPMailer\PHPMailer\PHPMailer;
    //use PHPMailer\PHPMailer\Exception;
    //use PHPMailer\PHPMailer\SMTP;
    
    require SITE_ROOT.'apps/php_mailer/vendor/autoload.php';
    
    $PHPMailer = new \PHPMailer\PHPMailer\PHPMailer;
    
    if ($Config->get('o_smtp_host') != '')
    {
        $PHPMailer->isSMTP();
        $PHPMailer->Host = $Config->get('o_smtp_host');
        $PHPMailer->Port = $Config->get('o_smtp_port');
        $PHPMailer->SMTPAuth = true;
        $PHPMailer->Username = $Config->get('o_smtp_user');
        $PHPMailer->Password = $Config->get('o_smtp_pass');
    }
}
