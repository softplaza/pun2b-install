<?php 

// Checking Permissions of Project
// 1 - Any, 2 -New, 3 - MoveOt Changed
function hca_vcr_check_notify($val = -1, $param = '')
{
	$output = false;
	
	if ($param != '')
	{
		$mailing = explode(',', $param);
	
		if (in_array($val, $mailing))
			$output = true;
	}
	
	return $output;
}

// Checking Permissions of Project
function hca_vcr_check_perms($input = -1)
{
	global $User;
	
	$output = false;
	
	$hca_fs_perms = explode(',', $User->get('hca_vcr_perms'));
	
	if ($User->is_admin() || in_array($input, $hca_fs_perms))
		$output = true;
	
	return $output;
}

// Runs on projects list
function hca_vcr_check_expired_final_walk()
{
	global $DBLayer, $Config, $FlashMessenger;
	
	$output = '';
	
	if ($Config->get('o_hca_vcr_complete_expired_days') > 0)
	{
		$expired_days = time() - ($Config->get('o_hca_vcr_complete_expired_days') * 86400);
		$query = array(
			'SELECT'	=> 'p.id',
			'FROM'		=> 'hca_vcr_projects AS p',
			'WHERE'		=> 'p.status=0 AND p.move_in_date > 0 AND p.move_in_date < '.$expired_days,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$projects_ids = array();
		while ($row = $DBLayer->fetch_assoc($result)) {
			$projects_ids[] = $row['id'];
		}
		
		if (!empty($projects_ids))
		{
			$query = array(
				'UPDATE'	=> 'hca_vcr_projects',
				'SET'		=> 'status=1',
				'WHERE'		=> 'id IN('.implode(',', $projects_ids).')'
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			$output = 'Expired Projects #'.implode(' #', $projects_ids).' has been marked as completed.';
			$FlashMessenger->add_info($output);
		}
	}
	
	return $output;
}

