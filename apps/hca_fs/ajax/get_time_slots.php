<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$start_date = isset($_POST['date']) ? strtotime($_POST['date']) : 0;
$user_id = isset($_POST['uid']) ? intval($_POST['uid']) : 0;

$user_assignment = array();
if ($user_id > 0)
{
	$query = array(
		'SELECT'	=> 'employee_id, time_slot',
		'FROM'		=> 'hca_fs_requests',
		'WHERE'		=> 'start_date='.$start_date.' AND employee_id='.$user_id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$user_assignment[] = $fetch_assoc;
	}
}

$json_array = array();
$json_array['slot'] = '<select name="time_slot">'."\n";
$all = $am = $pm = true;

if (!empty($user_assignment))
{
	foreach ($user_assignment as $cur_info)
	{
		if ($cur_info['time_slot'] == 1)
			$all = false;
		else if ($cur_info['time_slot'] == 2)
			$am = false;
		else if ($cur_info['time_slot'] == 3)
			$pm = false;
	}
}

if ($all && $am && $pm)
{
	$json_array['slot'] .= '<option value="1">ALL DAY</option>'."\n";
	$json_array['slot'] .= '<option value="2">A.M.</option>'."\n";
	$json_array['slot'] .= '<option value="3">P.M.</option>'."\n";
}
else if ($am)
	$json_array['slot'] .= '<option value="2">A.M.</option>'."\n";
else if ($pm)
	$json_array['slot'] .= '<option value="3">P.M.</option>'."\n";

$json_array['slot'] .= '</select>'."\n";

echo json_encode(array(
	'edit_time_slot'	=> $json_array['slot'],
));

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();
