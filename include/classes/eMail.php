<?php

/**
 * @author SwiftManager.Org
 * @copyright (C) 2021 SwiftManager license GPL
 * @package eMail
**/

class eMail
{
	public $FromEmail = '';
	public $FromName = '';
	public $Addresses = [];
	public $Subject = '';
	public $Body = '';
	public $ReplyEmail = '';
	public $ReplyName = '';
	public $ReplyTo = [];
	public $AttachedFiles = [];	

/* REBUILDING TO NEW SIMPLE PARAMETRES */

	// Main sender. Use your server Email
	private $from_email = '';
	private $from_name = '';

	//SMTP params
	private $isSMTP = false;
	private $smtp_host = '';
	private $smtp_port = '25';
	private $smtp_user = '';
	private $smtp_psss = '';
	private $smtp_ssl = false;
	private $local_host = '';

	// Default contention's params
	public $content_type = 'text/plain';
	public $isHTML = false;
	public $isH1 = false;
	public $sent = false;

	// Input params
	public $to = '';
	public $subject = '';
	public $message = '';
	public $attachments = [];
	public $reply_to = [];

	// Output params
	private $from = '';
	private $content = '';
	private $headers = '';
	private $additional_params = '';

	// Helpers
	public $errors = [];
	public $warnings = [];

	function __construct()
	{
		global $Config;
		
		$this->FromEmail = $Config->get('o_webmaster_email');
		$this->FromName = $Config->get('o_board_title');
		
		if ($Config->get('o_smtp_host') != '') 
		{
			$this->smtp_host = $Config->get('o_smtp_host');
			$this->smtp_port = ($Config->get('o_smtp_port') != '') ? $Config->get('o_smtp_port') : 25;
			$this->smtp_user = $Config->get('o_smtp_user');
			$this->smtp_pass = $Config->get('o_smtp_pass');
			$this->smtp_ssl = ($Config->get('o_smtp_ssl') == 1) ? true : false;

			if (!empty($this->smtp_host) && !empty($this->smtp_port) && !empty($this->smtp_user) && !empty($this->smtp_pass))
				$this->isSMTP = true;
		}
	}

	function SetFrom($email = '', $name = '')
	{
		global $Config;
		
		$this->FromEmail = $email;
		$this->FromName = $name;
	}
	
	function AddReplyTo($email = '', $name = '')
	{
		if ($email != '')
		{
			$this->ReplyEmail = $email;
			$this->ReplyName = $name;
			
			$this->ReplyTo[$email] = $name;
		}
	}
	
	function AddH1($val = false)
	{
		if ($val)
			$this->isH1 = true;
	}
	
	function AddAddress($email = '', $name = '')
	{
		if ($email != '')
		{
			$this->Addresses[$email] = $name;
		}
	}

	function thisHTML(){
		$this->isHTML = true;
	}
	function isHTML(){
		$this->isHTML = true;
	}

	// Chechk and convert HTML tags to simple text
	function HtmlToText()
	{
		$text = $this->Body;
		
		$text = strip_tags($text, '<br>');
		$text = str_replace('<br>', "\n", $text);
		
		$this->Body = $text;
	}
	
	// Check and convert simple text to HTML
	function TextToHtml()
	{
		$text = str_replace("\n", '<br>', $this->Body);
		
		$html_text = '<html>';
		$html_text .= '<head>';
		$html_text .= '<title>'.$this->Subject.'</title>';
		$html_text .= '</head>';
		$html_text .= '<body>';
		if ($this->isH1)
			$html_text .= '<h1>'.$this->Subject.'</h1>';
		//$html_text .= html_encode($text);
		$html_text .= str_replace("'", "\\'", $text);
		$html_text .= '</body>';
		$html_text .= '</html>';
		
		$this->Body = $html_text;
	}
	
	function thisSMTP()
	{
		$this->isSMTP = true;
	}
	
	// IN DEVELOPING
	function AddAttachment($file_path='')
	{
		if ($file_path != '')
			$this->AttachedFiles[] = $file_path;
	}
	
	// For simple Email sending use params: 
	function Send($to = '', $subject = '', $message = '', $attachments = [])
	{
		global $Hooks;
		
		if (defined('SWIFT_DISABLE_EMAIL'))
			return;
		
		$this->to = ($to != '') ? $to : $this->to;
		$this->Subject = ($subject != '') ? $subject : $this->Subject;
		$this->Body = ($message != '') ? $message : $this->Body;
		$this->AttachedFiles = !empty($attachments) ? $attachments : $this->AttachedFiles;
		$this->AttachedFiles = is_array($this->AttachedFiles) ? $this->AttachedFiles : [$this->AttachedFiles];

		// Default param
		$from_email = $this->FromEmail;
		$from_name = $this->FromName;
		
		if (!empty($this->Addresses))
		{
			foreach($this->Addresses as $email => $name)
			{
				$this->to .= ($this->to != '') ? ','.$email : $email;
			}
		}
		
		// 
		$is_html = ($this->isHTML) ? $this->TextToHtml() : $this->HtmlToText(); // 1

		$this->content_type = ($this->isHTML) ? 'text/html' : 'text/plain';
		
		$reply_to_email = $this->ReplyEmail;
		$reply_to_name = $this->ReplyName;
		
		// Do a little spring cleaning
		$this->to = spm_trim(preg_replace('#[\n\r]+#s', '', $this->to));
		$this->Subject = spm_trim(preg_replace('#[\n\r]+#s', '', $this->Subject));
		$from_email = spm_trim(preg_replace('#[\n\r:]+#s', '', $from_email));
		$from_name = spm_trim(preg_replace('#[\n\r:]+#s', '', str_replace('"', '', $from_name)));
		$reply_to_email = spm_trim(preg_replace('#[\n\r:]+#s', '', $reply_to_email));
		$reply_to_name = spm_trim(preg_replace('#[\n\r:]+#s', '', str_replace('"', '', $reply_to_name)));
		
		// Set up some headers to take advantage of UTF-8
		$from = "=?UTF-8?B?".base64_encode($from_name)."?=".' <'.$from_email.'>';
		$mail_subject = "=?UTF-8?B?".base64_encode($this->Subject)."?=";
		
		$this->headers = 'From: '.$from."\r\n".'Date: '.gmdate('r')."\r\n".'MIME-Version: 1.0'."\r\n".'Content-transfer-encoding: 8bit'."\r\n".'Content-type: '.$this->content_type.'; charset=utf-8'."\r\n".'X-Mailer: PHP/'.phpversion();
		
		// Multi Uploading Files
		if (!empty($this->AttachedFiles))
		{
			// Boundary  
			$semi_rand = md5(time());  
			$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";  
			
			// Headers for attachment
			$this->headers = 'From: '.$from."\r\n";
			$this->headers .= "MIME-Version: 1.0"."\r\n";
			$this->headers .= "Content-Type: multipart/mixed;"."\r\n";
			$this->headers .= " boundary=\"{$mime_boundary}\"";
			
			// Multipart boundary  
			$this->content = "--{$mime_boundary}\n".
			"Content-Type: text/html; charset=\"UTF-8\"\n".
			"Content-Transfer-Encoding: 7bit\n\n".$this->Body."\n\n";
			
			foreach($this->AttachedFiles as $path)
			{
				if (is_file($path))
				{ 
					$this->content .= "--{$mime_boundary}\n"; 
					$fp =    @fopen($path, "rb"); 
					$data =  @fread($fp,filesize($path)); 
					
					@fclose($fp); 
					$data = chunk_split(base64_encode($data)); 
					$this->content .= "Content-Type: application/octet-stream; name=\"".basename($path)."\"\n".
					"Content-Description: ".basename($path)."\n".
					"Content-Disposition: attachment;\n"." filename=\"".basename($path)."\"; size=".filesize($path).";\n".
					"Content-Transfer-Encoding: base64\n\n".$data."\n\n"; 
				}
				// Setup limit files and BREAK
				//break;
			}
			
			$this->content .= "--{$mime_boundary}--";
			$this->additional_params = "-f".$from;
		}

		// If we specified a reply-to email, we deal with it here
		if (!empty($reply_to_email))
		{
			$reply_to = "=?UTF-8?B?".base64_encode($reply_to_name)."?=".' <'.$reply_to_email.'>';
			$this->headers .= "\r\n".'Reply-To: '.$reply_to;
		}
		
		// Make sure all linebreaks are CRLF in message (and strip out any NULL bytes)
		$this->content = ($this->content != '') ? $this->content : $this->Body;
		$this->content = str_replace(array("\n", "\0"), array("\r\n", ''), convert_line_breaks($this->content));

		if ($this->to == '') {
			$this->errors[] = 'No email addresses to send email.';
			return;
		}

		if ($this->isSMTP)
			$this->sent = $this->smtpMail($this->to, $mail_subject, $this->content);
		else
		{
			// Change the linebreaks used in the headers according to OS
			if (strtoupper(substr(PHP_OS, 0, 3)) == 'MAC')
				$this->headers = str_replace("\r\n", "\r", $this->headers);
			else if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN')
				$this->headers = str_replace("\r\n", "\n", $this->headers);
			
			$this->sent = mail($this->to, $mail_subject, $this->content, $this->headers, $this->additional_params);
		}

		$Hooks->get_hook('ClassEmailFnSendEnd');
		
		return $this->sent;
	}
	
	// This function was originally a part of the phpBB Group forum software phpBB2 (http://www.phpbb.com).
	// They deserve all the credit for writing it. 
	function smtpMail($to, $subject, $content)
	{
		$recipients = explode(',', $to);

		// Sanitize the message
		$content = str_replace("\r\n.", "\r\n..", $content);
		$content = (substr($content, 0, 1) == '.' ? '.'.$content : $content);
	
		// If your version of PHP supports SSL and your SMTP server requires it
		$this->smtp_host = ($this->smtp_ssl) ? 'ssl://'.$this->smtp_host : $this->smtp_host;

		if (!($socket = fsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, 15)))
		{
			$this->errors[] = 'Could not connect to smtp host "'.$this->smtp_host.'" ('.$errno.') ('.$errstr.').';
			return false;
		}
	
		$this->serverParse($socket, '220');
	
		if ($this->local_host == '')
		{
			// Here we try to determine the *real* hostname (reverse DNS entry preferably)
			$this->local_host = php_uname('n');
	
			// Able to resolve name to IP
			if (($local_addr = @gethostbyname($this->local_host)) !== $this->local_host)
			{
				// Able to resolve IP back to name
				if (($local_name = @gethostbyaddr($local_addr)) !== $local_addr)
					$this->local_host = $local_name;
			}
		}
	
		if ($this->smtp_user != '' && $this->smtp_pass != '')
		{
			fwrite($socket, 'EHLO '.$this->local_host."\r\n");
			$this->serverParse($socket, '250');
	
			fwrite($socket, 'AUTH LOGIN'."\r\n");
			$this->serverParse($socket, '334');
	
			fwrite($socket, base64_encode($this->smtp_user)."\r\n");
			$this->serverParse($socket, '334');
	
			fwrite($socket, base64_encode($this->smtp_pass)."\r\n");
			$this->serverParse($socket, '235');
		}
		else
		{
			fwrite($socket, 'HELO '.$this->local_host."\r\n");
			$this->serverParse($socket, '250');
		}
	
		fwrite($socket, 'MAIL FROM: <'.$this->FromEmail.'>'."\r\n");
		$this->serverParse($socket, '250');
	
		foreach ($recipients as $email)
		{
			fwrite($socket, 'RCPT TO: <'.$email.'>'."\r\n");
			$this->serverParse($socket, '250');
		}
	
		fwrite($socket, 'DATA'."\r\n");
		$this->serverParse($socket, '354');
	
		fwrite($socket, 'Subject: '.$subject."\r\n".'To: <'.implode('>, <', $recipients).'>'."\r\n".$this->headers."\r\n\r\n".$content."\r\n");
	
		fwrite($socket, '.'."\r\n");
		$this->serverParse($socket, '250');
	
		fwrite($socket, 'QUIT'."\r\n");
		fclose($socket);
	
		return true;
	}
	
	// This function was originally a part of the phpBB Group forum software phpBB2 (http://www.phpbb.com).
	// They deserve all the credit for writing it. I made small modifications for it to suit PunBB and it's coding standards.
	function serverParse($socket, $expected_response)
	{
		$server_response = '';

		while (substr($server_response, 3, 1) != ' ')
		{
			if (!($server_response = fgets($socket, 256)))
			{
				$this->errors[] = 'Couldn\'t get mail server response codes.<br />Please contact the site administrator.';
				break;
			}
		}
	
		if (!(substr($server_response, 0, 3) == $expected_response))
		{
			$this->errors[] = 'Unable to send e-mail.<br />Please contact the site administrator with the following error message reported by the SMTP server: "'.$server_response.'"';
			return false;
		}
	}
	
	// Validate an e-mail address
	function is_valid($email)
	{
		if (strlen($email) > 80)
			return false;
	
		return preg_match('/^(([^<>()[\]\\.,;:\s@"\']+(\.[^<>()[\]\\.,;:\s@"\']+)*)|("[^"\']+"))@((\[\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\])|(([a-zA-Z\d\-]+\.)+[a-zA-Z]{2,}))$/', $email);
	}

	// Check if $email is banned
	function is_banned($email)
	{
		global $DBLayer, $bans_info;
	
		foreach ($bans_info as $cur_ban)
		{
			if ($cur_ban['email'] != '' &&
				($email == $cur_ban['email'] ||
				(strpos($cur_ban['email'], '@') === false && stristr($email, '@'.$cur_ban['email']))))
				return true;
		}
	
		return false;
	}
}
