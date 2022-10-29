<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

$access = (!$User->is_guest()) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$project_id = isset($_POST['pid']) ? intval($_POST['pid']) : 0;

$json_array = array();
$json_array['walk'] = array();
if ($project_id > 0)
{
	$query = array(
		'SELECT'	=> 'p.*',
		'FROM'		=> 'hca_vcr_projects AS p',
		'WHERE'		=> 'p.id='.$project_id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$main_info = $DBLayer->fetch_assoc($result);
	
	$query = array(
		'SELECT'	=> 'u.id, u.realname',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.hca_vcr_access > 0',
		'ORDER BY'	=> 'u.realname'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$users_info = array();
	while ($row = $DBLayer->fetch_assoc($result)) {
		$users_info[] = $row;
	}
	
	$json_array['walk'][] = '<input type="text" name="walk" value="'.html_encode($main_info['walk']).'" list="walk_name">'."\n";
	if (!empty($users_info))
	{
		$json_array['walk'][] = '<datalist id="walk_name">'."\n";
		foreach($users_info as $user_info)
		{
			$json_array['walk'][] = '<option value="'.$user_info['realname'].'">'."\n"; 
		}
		
		$json_array['walk'][] = '</datalist>'."\n";
	}
	
	echo json_encode(array(
		'move_in_date'	=> ($main_info['move_in_date'] > 0) ? '<strong>'.format_time($main_info['move_in_date'], 1).'</strong>' : '<strong style="color:red">Setup MoveIn Date</strong>',
		'walk_date'		=> '<input type="date" name="walk_date" value="'.sm_date_input($main_info['walk_date']).'"/>',
		'walk'			=> !empty($json_array['walk']) ? implode('', $json_array['walk']) : '<input type="text" name="walk" value="'.html_encode($main_info['walk']).'" list="walk_name">',
		'walk_comment'	=> html_encode($main_info['walk_comment']),
	));
}




// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
