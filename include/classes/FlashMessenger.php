<?php
/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

class FlashMessenger
{
	const TEMPLATE_MSG_BLOCK 	= '%s';
	const TEMPLATE_MSG 			= '<span class="%s">%s</span>';

	const MSG_TYPE_ERROR 		= 'message_error';
	const MSG_TYPE_WARNING 		= 'message_warning';
	const MSG_TYPE_INFO 		= 'message_info';

	private $message;


	public function __construct()
	{
		global $Config;

		$disabled = intval($Config->get('o_redirect_delay'), 10) > 0;

		if (!$disabled)
		{
			check_session_start();
		}

		$this->message = $this->get_message();
	}

	public function add_error($msg)
	{
		$this->add_message($msg, self::MSG_TYPE_ERROR);
	}

	public function add_warning($msg)
	{
		$this->add_message($msg, self::MSG_TYPE_WARNING);
	}

	public function add_info($msg)
	{
		$this->add_message($msg, self::MSG_TYPE_INFO);
	}

	public function show($just_return = false)
	{
		if (empty($this->message))
			return;

		$message = sprintf(self::TEMPLATE_MSG, html_encode($this->message[1]), html_encode($this->message[0]));

		$m = sprintf(self::TEMPLATE_MSG_BLOCK, $message);
		if ($just_return) {
			$this->clear();
			return $m;
		}

		echo $m;

		$this->clear();
	}

	public function clear()
	{
		$this->message = NULL;
		$this->save_message();
	}


	private function add_message($message, $type)
	{
		$this->message = array($message, $type);
		$this->save_message();
	}

	private function save_message()
	{
		$_SESSION['swift_manager_flash'] = serialize($this->message);
	}

	public function get_message()
	{
		$message = NULL;

		if (isset($_SESSION['swift_manager_flash'])) {
			$tmp_message = unserialize($_SESSION['swift_manager_flash']);

			if (!is_null($tmp_message) && !empty($tmp_message))
			{
				if (is_array($tmp_message) && !empty($tmp_message[0]) && !empty($tmp_message[1]))
				{
					$message = $tmp_message;
				}
			}
		}

		return $message;
	}
}
