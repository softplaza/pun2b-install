<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->is_admmod() || $User->get('sm_calendar_access') > 0) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
$action = isset($_GET['action']) ? swift_trim($_GET['action']) : '';

$query = array(
	'SELECT'	=> 'id, pro_name',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'display_position',
	'WHERE'		=> $User->get('id'),
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[$row['id']] = $row;
}

$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, u.sm_calendar_access, g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'group_id > 2',
	'ORDER BY'	=> 'g.g_id, u.realname',
);
if ($User->get('sm_calendar_access') == 3)
	$query['WHERE'] .= ' AND u.id='.$User->get('id');
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$users_info[$row['id']] = $row;
}

if (isset($_POST['update']))
{
	$id = intval(key($_POST['update']));
	$project_manager_id = isset($_POST['project_manager_id'][$id]) ? intval($_POST['project_manager_id'][$id]) : 0;
	$project_manager_name = isset($users_info[$project_manager_id]) ? $users_info[$project_manager_id]['realname'] : '';
	
	$start_date = isset($_POST['start_date'][$id]) ? strtotime($_POST['start_date'][$id]) : 0;
	$end_date = isset($_POST['end_date'][$id]) ? strtotime($_POST['end_date'][$id]) : 0;
	
	$status = isset($_POST['status'][$id]) ? intval($_POST['status'][$id]) : 0;
	$remarks = isset($_POST['remarks'][$id]) ? swift_trim($_POST['remarks'][$id]) : '';
	$time_now = time();
	
	$query = array(
		'UPDATE'	=> 'sm_calendar_projects',
		'SET'		=> 'project_manager_id=\''.$DBLayer->escape($project_manager_id).'\',
			project_manager_name=\''.$DBLayer->escape($project_manager_name).'\',
			start_date=\''.$DBLayer->escape($start_date).'\',
			end_date=\''.$DBLayer->escape($end_date).'\',
			updated=\''.$DBLayer->escape($time_now).'\',
			updated_by=\''.$DBLayer->escape($User->get('id')).'\',
			status=\''.$DBLayer->escape($status).'\',
			remarks=\''.$DBLayer->escape($remarks).'\'',
		'WHERE'		=> 'id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
	// Add flash message
	$flash_message = 'Project updated';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_building_number = isset($_GET['building_number']) ? swift_trim($_GET['building_number']) : '';
$search_by_unit_number = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';
$query = array(
	'SELECT'	=> 'id, property_id, property_name, building_number, unit_number, project_manager_id, project_manager_name, project_title, project_desc, start_date, end_date, created, created_by, updated, updated_by, status, remarks',
	'FROM'		=> 'sm_calendar_projects',
	'WHERE'		=> 'status>-1',
	'ORDER BY'	=> 'property_id'
);
if ($search_by_property_id > 0)
	$query['WHERE'] .= ' AND property_id='.$search_by_property_id;
if ($search_by_building_number != '') {
	$search_by_building_number2 = '%'.$search_by_building_number.'%';
	$query['WHERE'] .= ' AND building_number LIKE \''.$DBLayer->escape($search_by_building_number2).'\'';
}
if ($search_by_unit_number != '') {
	$search_by_unit_number2 = '%'.$search_by_unit_number.'%';
	$query['WHERE'] .= ' AND unit_number LIKE \''.$DBLayer->escape($search_by_unit_number2).'\'';
}
if ($User->get('sm_calendar_access') == 3)
	$query['WHERE'] .= ' AND project_manager_id='.$User->get('id');
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
}

$Core->set_page_title('Projects');
$Core->set_page_id('sm_calendar_projects', 'sm_calendar');
require SITE_ROOT.'header.php';
?>

<style>
table{table-layout: initial;}
.ct-group td{
	vertical-align: top;
	padding:0;
}
.ct-group th {
    position: -webkit-sticky;
    position: sticky;
    top: 0;
    font-weight: bold !important;
    text-align: center;
    text-transform: uppercase;
}
.th-property {
    left: 0;
    position: sticky;
    z-index: 250;
}
.td-property {
	background: #c1deff !important;
	font-weight: bold;
}
.td-property p{padding-left: 5px;}
.ct-group textarea{width: 95%;}
.remarks{min-width: 250px;}
.td-manager, .td-manager p, .actions p, .date p{text-align: center;}
</style>

<div class="main-content main-frm">
	<div class="ct-group">
		<div class="search-box">	
			<form method="get" accept-charset="utf-8" action="">
				<select name="property_id"><option value="">Select property</option>
<?php 
foreach ($property_info as $val){
			if($search_by_property_id == $val['id'])
				echo '<option value="'.$val['id'].'" selected="selected">'.$val['pro_name'].'</option>';
			else
				echo '<option value="'.$val['id'].'">'.$val['pro_name'].'</option>';
} 
?>
				</select>
				BLDG# <input name="building_number" type="text" value="<?php echo isset($_GET['building_number']) ? html_encode($_GET['building_number']) : '' ?>" size="5"/>
				UNIT# <input name="unit_number" type="text" value="<?php echo isset($_GET['unit_number']) ? html_encode($_GET['unit_number']) : '' ?>" size="5"/>
				<input type="submit" value="Go" />
			</form>
		</div>	
<?php
	if (!empty($main_info))
	{
?>
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<table>
				<thead>
					<tr>
						<th class="th1">Property Information</th>
						<th class="th-manager">Project Manager</th>
						<th>Start/End Dates</th>
						<th>Project Information</th>
						<th>Remarks</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
<?php
		foreach ($main_info as $cur_info)
		{
			$page_param['td'] = array();
			$page_param['td']['property_name'] = isset($property_info[$cur_info['property_id']]) ? $property_info[$cur_info['property_id']]['pro_name'] : '';
			$page_param['td']['building_number'] = !empty($cur_info['building_number'])? 'Bldg #: '.html_encode($cur_info['building_number']) : '';
			$page_param['td']['unit_number'] = !empty($cur_info['unit_number'])? 'Unit #: '.html_encode($cur_info['unit_number']) : '';
			$page_param['td']['project_manager_name'] = html_encode($cur_info['project_manager_name']);
			$page_param['td']['project_title'] = ($cur_info['project_title'] != '') ? 'Title: '.html_encode($cur_info['project_title']) : '';
			$page_param['td']['project_desc'] = ($cur_info['project_desc'] != '') ? 'Description: '.html_encode($cur_info['project_desc']) : '';
			$page_param['td']['start_date'] = sm_date_input($cur_info['start_date']);
			$page_param['td']['end_date'] = sm_date_input($cur_info['end_date']);
			$page_param['td']['remarks'] = html_encode($cur_info['remarks']);
			$page_param['td']['status'] = ($cur_info['status'] == 1 ? ' checked="checked"' : '');
?>
					<tr>
						<td class="td1">
							<p><?php echo $page_param['td']['property_name'] ?></p>
							<p><?php echo $page_param['td']['building_number'] ?></p>
							<p><?php echo $page_param['td']['unit_number'] ?></p>
						</td>
						<td class="td-manager">
							<p><select name="project_manager_id[<?php echo $cur_info['id'] ?>]">
<?php
		echo '<option value="0" selected="selected" disabled>Project Manager</option>'."\n";
		$optgroup = 0;
		foreach ($users_info as $cur_user)
		{
			if ($cur_user['group_id'] != $optgroup) {
				if ($optgroup) {
					echo '</optgroup>';
				}
				echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
				$optgroup = $cur_user['group_id'];
			}
			
			if($cur_info['project_manager_id'] == $cur_user['id'])
				echo "\t\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'" selected="selected">'.html_encode($cur_user['realname']).'</option>'."\n";
			else
				echo "\t\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
		}
?>
							</select></p>
							<p><strong><a href="<?php echo $URL->link('sm_calendar_events', array(date('Y-m', $cur_info['start_date']), $cur_info['id'], 'sm_calendar')) ?>">View Project Events</a></strong></p>
						</td>
						
						<td class="date">
							<p><input type="date" name="start_date[<?php echo $cur_info['id'] ?>]" value="<?php echo $page_param['td']['start_date'] ?>"/></p>
							<p><input type="date" name="end_date[<?php echo $cur_info['id'] ?>]" value="<?php echo $page_param['td']['end_date'] ?>"/></p>
						</td>
						
						<td>
							<p><?php echo $page_param['td']['project_title'] ?></p>
							<p><?php echo $page_param['td']['project_desc'] ?></p>
						</td>
						
						<td class="remarks"><textarea name="remarks[<?php echo $cur_info['id'] ?>]" rows="4"><?php echo $page_param['td']['remarks'] ?></textarea></td>
						
						<td class="actions">
							<p>
								<select name="status[<?php echo $cur_info['id'] ?>]">
					
<?php
$statuses = array(0 => 'ACTIVE', 1 => 'COMPLETED', 2 => 'ON HOLD');
	foreach ($statuses as $key => $val){
		if ($cur_info['status'] == $key)
			echo '<option value="'.$key.'" selected="selected">'.$val.'</option>';
		else
			echo '<option value="'.$key.'">'.$val.'</option>';
}
?>
								</select>
							</p>
<?php if ($User->is_admmod() || $User->get('sm_calendar_access') >= 3) : ?>
							<p><span class="submit primary"><input type="submit" name="update[<?php echo $cur_info['id'] ?>]" value="Update" /></span></p>
<?php endif; ?>
						</td>
					</tr>
<?php 
		}
?>
				</tbody>
			</table>
		</form>
	</div>
<?php
	} else {
?>
	<div class="ct-box warn-box">
		<p>You have no active projects on this page.</p>
	</div>
<?php
	}
?>
</div>

<?php
require SITE_ROOT.'footer.php';