<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
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
	if ($type == 1)
	{
		$query = array(
			'UPDATE'	=> 'user_access',
			'SET'		=> 'a_value='.$val,
			'WHERE'		=> 'id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
		echo json_encode(array(
			'message'		=> '<div class="alert alert-success alert-dismissible fade show" role="alert">Access has been updated.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>',
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
			'message'		=> '<div class="alert alert-success alert-dismissible fade show" role="alert">Permission has been updated.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>',
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
			'message'		=> '<div class="alert alert-success alert-dismissible fade show" role="alert">Notifications has been updated.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>',
		));
	}
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();