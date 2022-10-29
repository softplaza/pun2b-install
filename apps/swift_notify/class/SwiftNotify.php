<?php

class SwiftNotify
{
	public $notify_info = [];
	
	function addInfo($id, $num_msgs, $class = 'top-0 start-100 translate-middle badge rounded-pill bg-danger')
	{
		$this->notify_info[$id] = '<span class="swift-notice '.$class.'">'.$num_msgs.'</span>';
	}

	function getInfo()
	{
		if (!empty($this->notify_info))
			return $this->notify_info;
	}
}
