<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_fs', 8)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$gid = isset($_GET['gid']) ? intval($_GET['gid']) : 0;
$time_slots = array(1 => 'ALL DAY', 2 => 'A.M.', 3 => 'P.M.');
$days_of_week = array(
	'1' => 'Monday',
	'2' => 'Tuesday',
	'3' => 'Wednesday',
	'4' => 'Thursday',
	'5' => 'Friday',
	'6' => 'Saturday',
	'7' => 'Sunday',
);

if (isset($_POST['add_new']))
{
	$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
	$property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
	$day_of_week = isset($_POST['day_of_week']) ? intval($_POST['day_of_week']) : 0;
	$time_shift = isset($_POST['time_shift']) ? intval($_POST['time_shift']) : 1;

	if ($user_id == 0)
		$Core->add_error('Select an employee.');
	if ($property_id == 0)
		$Core->add_error('Select property.');
	if ($day_of_week == 0)
		$Core->add_error('Select day of week.');
	
	if (empty($Core->errors))
	{
		$query = array(
			'INSERT'	=> 'user_id, group_id, property_id, day_of_week, time_shift',
			'INTO'		=> 'hca_fs_permanent_assignments',
			'VALUES'	=> 
				'\''.$DBLayer->escape($user_id).'\',
				\''.$DBLayer->escape($gid).'\',
				\''.$DBLayer->escape($property_id).'\',
				\''.$DBLayer->escape($day_of_week).'\',
				\''.$DBLayer->escape($time_shift).'\''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		// Add flash message
		$flash_message = 'Permanently assignment has been created for user #'.$user_id;
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete_assignment']))
{
	$assign_id = intval(key($_POST['delete_assignment']));
	
	if ($assign_id < 1)
		$Core->add_error('Cannot delete this assignment. Wrong assignment ID.');
	
	if (empty($Core->errors))
	{
		$query = array(
			'DELETE'		=> 'hca_fs_permanent_assignments',
			'WHERE'			=> 'id='.$assign_id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		// Add flash message
		$flash_message = 'Assignment #'.$assign_id.' has been deleted';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

// Grab the users
$query = array(
	'SELECT'	=> 'u.id, u.username, u.email, u.title, u.realname, u.num_posts, u.registered, u.last_visit, u.hca_fs_property_id, g.g_id, g.g_user_title',
	'FROM'		=> 'users AS u',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'groups AS g',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'u.group_id='.$gid,
	'ORDER BY'	=> 'u.username',
//	'LIMIT'		=> 50
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$founded_user_datas = array();
while ($user_data = $DBLayer->fetch_assoc($result)) {
	$founded_user_datas[] = $user_data;
}

$query = array(
	'SELECT'	=> 'id, pro_name',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[$fetch_assoc['id']] = $fetch_assoc;
}

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'hca_fs_permanent_assignments',
	'ORDER BY'	=> 'start_time',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$assignments_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$assignments_info[] = $fetch_assoc;
}

$Core->set_page_id('hca_fs_permanently_assignments_'.$gid, 'hca_fs');
require SITE_ROOT.'header.php';

if (!empty($founded_user_datas))
{
	$page_param['table_header'] = array();
	
	$page_param['table_header']['realname'] = '<th class="tc'.count($page_param['table_header']).'" rowspan="2" scope="col"><strong>Full name</strong></th>';
	$page_param['table_header']['days'] = '<th class="tc'.count($page_param['table_header']).'" colspan="7" scope="col"><strong>Days of Week</strong></th>';
?>

<nav class="navbar container-fluid search-box">
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="row">
			<div class="col">
				<select name="user_id" class="form-select" required>
					<option value="" selected disabled>Select an Employee</option>
<?php
foreach ($founded_user_datas as $val)
{
		echo '<option value="'.$val['id'].'">'.$val['realname'].'</option>';
}
?>
				</select>
			</div>
			<div class="col">
				<select name="property_id" class="form-select" required>
					<option value="" selected disabled>Select Property</option>
<?php
foreach ($property_info as $val)
{
		echo '<option value="'.$val['id'].'">'.$val['pro_name'].'</option>';
}
?>
				</select>
			</div>
			<div class="col">
				<select name="day_of_week" class="form-select" required>
					<option value="" selected disabled>Day of Week</option>
<?php
foreach ($days_of_week as $key => $val)
{
		echo '<option value="'.$key.'">'.$val.'</option>';
}
?>
				</select>
			</div>
			<div class="col">
				<select name="time_shift" class="form-select" required>
<?php
foreach ($time_slots as $key => $val)
{
		echo '<option value="'.$key.'">'.$val.'</option>';
}
?>
				</select>
			</div>
			<div class="col">
				<button type="submit" name="add_new" class="btn btn-outline-success">Assign</button>
			</div>
			<div class="col">
				<button type="button" class="btn btn-warning" onclick="showWeekend()">Weekend</button>
			</div>
		</div>
	</form>
</nav>
			
<form method="post" accept-charset="utf-8" action="" id="permanently_assignments">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<table class="table table-bordered">
		<thead>
			<tr>
				<?php echo implode("\n\t\t\t\t\t\t", $page_param['table_header'])."\n" ?>
			</tr>
			<tr class="days-of-week">
				<th><?php echo $days_of_week[1] ?></th>
				<th><?php echo $days_of_week[2] ?></th>
				<th><?php echo $days_of_week[3] ?></th>
				<th><?php echo $days_of_week[4] ?></th>
				<th><?php echo $days_of_week[5] ?></th>
				<th class="th-weekend"><?php echo $days_of_week[6] ?></th>
				<th class="th-weekend"><?php echo $days_of_week[7] ?></th>
			</tr>
		</thead>
		<tbody>
<?php
	foreach ($founded_user_datas as $user_data)
	{
		$css_group = ($user_data['g_id'] == $Config->get('o_hca_fs_maintenance')) ? 'peach' : 'beige';
		
		$page_param['table_row'] = array();
		$page_param['table_row']['realname'] = '<td class="user-info"><a href="'.$URL->link('user', $user_data['id']).'">'.html_encode($user_data['realname']).'</a><p>'.get_title($user_data).'</p></td>';
?>
				<tr class="<?php echo $css_group ?>">
					<?php echo implode("\n\t\t\t\t\t\t", $page_param['table_row'])."\n" ?>
<?php
		foreach($days_of_week as $key => $day)
		{
			$css_prop = (isset($user_property_days[$key]) && isset($property_info[$user_property_days[$key]])) ? ' ps-prop' : '';
			
			$assignment_list = array();
			foreach($assignments_info as $assignment)
			{
				$cur_assignment = array();
				if ($user_data['id'] == $assignment['user_id'] && $key == $assignment['day_of_week'])
				{
					$cur_assignment[] = '<strong>'.$property_info[$assignment['property_id']]['pro_name'].'</strong>';
					$cur_assignment[] = '<p>'.$time_slots[$assignment['time_shift']].'</p>';
					
					$action_remove = '<input type="image" src="'.BASE_URL.'/img/close.png" name="delete_assignment['.$assignment['id'].']" onclick="return confirm(\'Are you sure you want to unassign from this property?\')">';
					$assignment_list[] = '<div class="assign-info ps-prop">'.$action_remove . implode('', $cur_assignment).'</div>';
				}
			}
			
			$td_css =  (in_array($key, array(6,7)) ? ' td-weekend' : '');
?>
				<td class="property <?php echo $css_prop ?><?php echo $td_css ?>">
					<?php echo implode('', $assignment_list); ?>
				</td>
<?php
		}
?>
			</tr>
<?php
	}
?>
		</tbody>
	</table>
</form>
<?php
}
else
{
?>

<div class="alert alert-warning" role="alert">You have no items on this page.</div>

<?php
}
?>

<script>
function showWeekend(){
	$(".th-weekend, .td-weekend").toggle();
	$('.weekend-toggle input[type="button"]').val($('.weekend-toggle input[type="button"]').val() == "Weekend" ? "Hide" : "Weekend");
}
</script>

<?php
require SITE_ROOT.'footer.php';