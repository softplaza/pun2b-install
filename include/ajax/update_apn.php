<?php
/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->is_admmod()) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$type = isset($_POST['type']) ? intval($_POST['type']) : 0;
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$val = isset($_POST['val']) ? intval($_POST['val']) : 0;

if ($id > 0)
{
	$toast_message = [];
	$toast_message[] = '<div id="liveToast" class="toast position-fixed bottom-0 end-0 m-2" role="alert" aria-live="assertive" aria-atomic="true">';
	//$toast_message[] = '<div class="toast-header toast-success">';
	//$toast_message[] = '<strong class="me-auto">Message</strong>';
	//$toast_message[] = '</div>';
	$toast_message[] = '<div class="toast-body toast-success">Settings updated successfully.</div>';
	$toast_message[] = '</div>';

	if ($type == 1)
	{
		$query = array(
			'UPDATE'	=> 'user_access',
			'SET'		=> 'a_value='.$val,
			'WHERE'		=> 'id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		echo json_encode(array(
			'toast_message' => implode('', $toast_message)
		));
	}
	else if ($type == 2)
	{
		$query = array(
			'UPDATE'	=> 'user_permissions',
			'SET'		=> 'p_value='.$val,
			'WHERE'		=> 'id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
		echo json_encode(array(
			'toast_message' => implode('', $toast_message)
		));
	}
	else if ($type == 3)
	{
		$query = array(
			'UPDATE'	=> 'user_notifications',
			'SET'		=> 'n_value='.$val,
			'WHERE'		=> 'id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
		echo json_encode(array(
			'toast_message' => implode('', $toast_message)
		));
	}
}
else
{
	$toast_message = [];
	$toast_message[] = '<div id="liveToast" class="toast position-fixed bottom-0 end-0 m-2" role="alert" aria-live="assertive" aria-atomic="true">';
	$toast_message[] = '<div class="toast-header toast-danger">';
	$toast_message[] = '<strong class="me-auto">Error</strong>';
	$toast_message[] = '</div>';
	$toast_message[] = '<div class="toast-body toast-danger">Failed to update settings.</div>';
	$toast_message[] = '</div>';

	echo json_encode(array(
		'toast_message' => implode('', $toast_message)
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();