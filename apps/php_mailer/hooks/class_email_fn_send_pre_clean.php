<?php if (!defined('DB_CONFIG')) die();

global $PHPMailer;

if (class_exists('PHPMailer\PHPMailer\PHPMailer') && isset($PHPMailer))
{	
	$PHPMailer = new \PHPMailer\PHPMailer\PHPMailer;
	$PHPMailer->SetFrom($from_email, $from_name);
	
	$emails_array = explode(',', $to);
	foreach($emails_array as $cur_email)
	{
		$PHPMailer->AddAddress($cur_email, '');
	}
	
	if ($reply_to_email != '')
		$PHPMailer->addReplyTo($reply_to_email, $reply_to_name);
		
	$PHPMailer->Subject = $subject;
	
	if ($this->isHTML)
		$PHPMailer->MsgHTML($message);
	else
		$PHPMailer->Body = $message;
	
	if (!empty($this->AttachedFiles))
	{
		foreach($this->AttachedFiles as $file_path => $file_name)
			$PHPMailer->AddAttachment($file_path);
	}
	
	$PHPMailer->Send();
	
	$email_already_sent = true;
}
else
	$this->Errors[] = 'Can\'t find or connect class PHPMailer';
