<?php

/**
 * @author SwiftProjectManager.Com. Based on PunBB & phpBB functions
 * @copyright (C) 2021 SwiftManager license GPL
 * @package SwiftMailer
 * @version 1.01
**/

class SwiftMailer
{
	// Main sender. Use your server Email
	public $from_email = '';
	public $from_name = '';

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
	//private $from = '';
	private $content = '';
	private $headers = '';
	private $additional_params = '';

	// Helpers
	public $errors = [];
	public $warnings = [];

	function __construct()
	{
		global $Config;
		
		$this->from_email = $Config->get('o_webmaster_email');
		$this->from_name = $Config->get('o_board_title');
		
		if ($Config->get('o_smtp_host') != '') 
		{
			$this->smtp_host = $Config->get('o_smtp_host');
			$this->smtp_port = ($Config->get('o_smtp_port') != '') ? $Config->get('o_smtp_port') : 25;
			$this->smtp_user = $Config->get('o_smtp_user');
			$this->smtp_pass = $Config->get('o_smtp_pass');
			$this->smtp_ssl = ($Config->get('o_smtp_ssl') == '1') ? true : false;

			if (!empty($this->smtp_host) && !empty($this->smtp_port) && !empty($this->smtp_user) && !empty($this->smtp_pass) && $Config->get('o_email_mode') == '2')
				$this->isSMTP = true;
		}
	}

	function setFrom($email, $name = '')
	{
		$this->from_email = $email;
		$this->from_name = $name;
	}
	
	function addReplyTo($email, $name = '')
	{
		if ($email != '')
			$this->reply_to[$email] = $name;
	}
	
	function isH1() {
		$this->isH1 = true;
	}

	function isHTML(){
		$this->isHTML = true;
	}

	// Chechk and convert HTML tags to simple text
	function htmlToText()
	{
		$text = $this->message;
		
		$text = strip_tags($text, '<br>');
		$text = str_replace('<br>', "\n", $text);
		
		$this->message = $text;
	}
	
	// Check and convert simple text to HTML
	function textToHtml()
	{
		$text = str_replace("\n", '<br>', $this->message);
		$text = $this->doLink($text, BASE_URL);
		
		$html_text = '<html>';
		$html_text .= '<head>';
		$html_text .= '<title>'.$this->subject.'</title>';
		$html_text .= '</head>';
		$html_text .= '<body>';
		if ($this->isH1)
			$html_text .= '<h1>'.$this->subject.'</h1>';
		//$html_text .= html_encode($text);
		$html_text .= str_replace("'", "\\'", $text);
		$html_text .= '</body>';
		$html_text .= '</html>';
		
		$this->message = $html_text;
	}
	
	function thisSMTP()
	{
		$this->isSMTP = true;
	}
	
	// Add attachment as string or array
	function addAttachment($files = [])
	{
		if (is_array($files))
		{
			foreach($files as $file)
				$this->attachments[] = $file;
		}
		else
			$this->attachments[] = $files;
	}
	
	// For simple Email sending use params: 
	function send($to = '', $subject = '', $message = '', $attachments = [])
	{
		global $Config, $Hooks;
		
		if (defined('SWIFT_DISABLE_EMAIL'))
			return;
		
		$this->to = ($to != '') ? str_replace(' ', '', $to) : str_replace(' ', '', $this->to);
		$this->subject = ($subject != '') ? $subject : $this->subject;
		$this->message = ($message != '') ? $message : $this->message;
		$this->attachments = !empty($attachments) ? $attachments : $this->attachments;
		$this->attachments = is_array($this->attachments) ? $this->attachments : [$this->attachments];

		$is_html = ($this->isHTML) ? $this->textToHtml() : $this->htmlToText(); // 1
		$this->content_type = ($this->isHTML) ? 'text/html' : 'text/plain';
		
		// Do a little spring cleaning
		$this->to = $this->trim(preg_replace('#[\n\r]+#s', '', $this->to));
		$this->subject = $this->trim(preg_replace('#[\n\r]+#s', '', $this->subject));
		$from_email = $this->trim(preg_replace('#[\n\r:]+#s', '', $this->from_email));
		$from_name = $this->trim(preg_replace('#[\n\r:]+#s', '', str_replace('"', '', $this->from_name)));
		
		// Set up some headers to take advantage of UTF-8
		$from = "=?UTF-8?B?".base64_encode($from_name)."?=".' <'.$from_email.'>';
		$mail_subject = "=?UTF-8?B?".base64_encode($this->subject)."?=";
		
		$this->headers = 'From: '.$from."\r\n".'Date: '.gmdate('r')."\r\n".'MIME-Version: 1.0'."\r\n".'Content-transfer-encoding: 8bit'."\r\n".'Content-type: '.$this->content_type.'; charset=utf-8'."\r\n".'X-Mailer: PHP/'.phpversion();
		
		// Multi Uploading Files
		if (!empty($this->attachments))
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
			"Content-Transfer-Encoding: 7bit\n\n".$this->message."\n\n";
			
			foreach($this->attachments as $path)
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
		if (!empty($this->reply_to))
		{
			foreach($this->reply_to as $email => $name)
			{
				$reply_to_email = $this->trim(preg_replace('#[\n\r:]+#s', '', $email));
				$reply_to_name = $this->trim(preg_replace('#[\n\r:]+#s', '', str_replace('"', '', $name)));
	
				$reply_to = "=?UTF-8?B?".base64_encode($reply_to_name)."?=".' <'.$reply_to_email.'>';
				$this->headers .= "\r\n".'Reply-To: '.$reply_to;
			}

		}
		
		// Make sure all linebreaks are CRLF in message (and strip out any NULL bytes)
		$this->content = ($this->content != '') ? $this->content : $this->message;
		$this->content = str_replace(array("\n", "\0"), array("\r\n", ''), $this->convert_line_breaks($this->content));

		if ($this->to == '') {
			$this->errors[] = 'No email addresses to send email.';
			return;
		}

		if ($this->isSMTP)
			$this->sent = $this->smtpMail($this->to, $mail_subject, $this->content);
		else if ($Config->get('o_email_mode') == '1')
		{
			// Change the linebreaks used in the headers according to OS
			if (strtoupper(substr(PHP_OS, 0, 3)) == 'MAC')
				$this->headers = str_replace("\r\n", "\r", $this->headers);
			else if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN')
				$this->headers = str_replace("\r\n", "\n", $this->headers);
			
			$this->sent = mail($this->to, $mail_subject, $this->content, $this->headers, $this->additional_params);
		}
		else
		{
			$this->subject = 'Email sending is disabled.';
		}

		$Hooks->get_hook('ClassSwiftMailerFnSendEnd');
		
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
	
		fwrite($socket, 'MAIL FROM: <'.$this->from_email.'>'."\r\n");
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

	// Convert \r\n and \r to \n
	function convert_line_breaks($str)
	{
		return str_replace(array("\r\n", "\r"), "\n", $str);
	}

	// Trim whitespace including non-breaking space
	function trim($str, $charlist = " \t\n\r\0\x0B\xC2\xA0")
	{
		return $this->utf8_trim($str, $charlist);
	}

	//---------------------------------------------------------------
	/**
	* UTF-8 aware replacement for trim()
	* Note: you only need to use this if you are supplying the charlist
	* optional arg and it contains UTF-8 characters. Otherwise trim will
	* work normally on a UTF-8 string
	* @author Andreas Gohr <andi@splitbrain.org>
	* @see http://www.php.net/trim
	* @see http://dev.splitbrain.org/view/darcs/dokuwiki/inc/utf8.php
	* @return string
	* @package utf8
	* @subpackage strings
	*/
	function utf8_trim( $str, $charlist = FALSE ) {
		if ($charlist === FALSE) {
			return trim($str);
		}

		// Quote charlist for use in a characterclass
		$charlist = preg_quote($charlist, '#');

		return preg_replace('#^['.$charlist.']+|['.$charlist.']+$#u', '', $str);
	}

	function doLink($content, $text = 'Link') {
		//$url = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i'; 
		//return preg_replace($url, '<a href="$0">'.$text.'</a>', $content);
		return preg_replace('%(https?|ftp)://([-A-Z0-9-./_*?&;=#]+)%i', 
			'<a href="$0">$0</a>', $content);
	}
}
