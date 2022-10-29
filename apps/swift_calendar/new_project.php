<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->is_admmod() || $User->get('sm_calendar_access') >= 3) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$query = array(
	'SELECT'	=> 'id, pro_name, manager_email',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'display_position'
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
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$users_info[$row['id']] = $row;
}

if (isset($_POST['new_project']))
{
	$property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
	$property_name = isset($property_info[$property_id]) ? $property_info[$property_id]['pro_name'] : '';
	$building_number = isset($_POST['building_number']) ? swift_trim($_POST['building_number']) : '';
	$unit_number = isset($_POST['unit_number']) ? swift_trim($_POST['unit_number']) : '';

	$project_manager_id = isset($_POST['project_manager_id']) ? intval($_POST['project_manager_id']) : 0;
	$project_manager_name = isset($users_info[$project_manager_id]) ? $users_info[$project_manager_id]['realname'] : '';

	$project_title = isset($_POST['project_title']) ? swift_trim($_POST['project_title']) : '';
	$project_desc = isset($_POST['project_desc']) ? swift_trim($_POST['project_desc']) : '';
	
	$start_time = isset($_POST['start_time']) ? strtotime($_POST['start_time']) : 0;
	$end_date = isset($_POST['end_date']) ? strtotime($_POST['end_date']) : 0;
	
	$time_now = time();
	
	if ($property_id < 1)
		$Core->add_error('Select a property from dropdown list.');
	if ($project_manager_id < 1)
		$Core->add_error('Select a project manager from dropdown list.');
	if ($start_time > $end_date && $end_date > 0)
		$Core->add_error('The start date cannot be greater than the end date.');
	
	if (empty($Core->errors))
	{
		$query = array(
			'INSERT'	=> 'project_title, project_desc, project_manager_id, project_manager_name, property_id, property_name, building_number, unit_number, start_date, end_date, created, created_by',
			'INTO'		=> 'sm_calendar_projects',
			'VALUES'	=> 
				'\''.$DBLayer->escape($project_title).'\',
				\''.$DBLayer->escape($project_desc).'\',
				\''.$DBLayer->escape($project_manager_id).'\',
				\''.$DBLayer->escape($project_manager_name).'\',
				\''.$DBLayer->escape($property_id).'\',
				\''.$DBLayer->escape($property_name).'\',
				\''.$DBLayer->escape($building_number).'\',
				\''.$DBLayer->escape($unit_number).'\',
				\''.$DBLayer->escape($start_time).'\',
				\''.$DBLayer->escape($end_date).'\',
				\''.$DBLayer->escape($time_now).'\', 
				\''.$DBLayer->escape($User->get('id')).'\''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$new_pid = $DBLayer->insert_id();
		
		if ($new_pid)
		{
			if ($start_time > 0)
			{
				$start_date = intval(date('Ymd', $start_time));
				
				$query = array(
					'INSERT'	=> 'project_id, subject, message, time, date, posted, poster_id, poster_name',
					'INTO'		=> 'sm_calendar_events',
					'VALUES'	=> 
						'\''.$DBLayer->escape($new_pid).'\',
						\''.$DBLayer->escape($project_title).'\',
						\''.$DBLayer->escape($project_desc).'\',
						\''.$DBLayer->escape($start_time).'\',
						\''.$DBLayer->escape($start_date).'\',
						\''.$DBLayer->escape($time_now).'\',
						\''.$DBLayer->escape($User->get('id')).'\', 
						\''.$DBLayer->escape($User->get('realname')).'\''
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				
				$flash_message = 'Project created';
				$FlashMessenger->add_info($flash_message);
				redirect($URL->link('sm_calendar_events', array(date('Y-m', $start_time), $new_pid, '')), $flash_message);
			}
			else
			{
				// Add flash message
				$flash_message = 'Project created';
				$FlashMessenger->add_info($flash_message);
				redirect($URL->link('sm_calendar_projects'), $flash_message);
			}
		}
	}
}

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_calendar_vendors',
	'ORDER BY'	=> 'vendor_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$vendors_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$vendors_info[] = $row;
}

// Setup the form
$page_param['fld_count'] = $page_param['group_count'] = $page_param['item_count'] = 0;

$Core->set_page_title('New projects');
$Core->set_page_id('sm_calendar_new_project', 'sm_calendar');
require SITE_ROOT.'header.php';
?>

<div class="main-content main-frm">
	<div id="admin-alerts" class="ct-set warn-set">
		<div class="ct-box warn-box">
			<h6 class="ct-legend hn warn"><span>Information:</span></h6>
			<p>Fill in all the fields and click "Submit".</p>
		</div>
	</div>
	<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<fieldset class="frm-group group1">
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box select">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span style="color:red;"><strong>Project Manager</strong></span></label><br>
					<span class="fld-input"><select name="project_manager_id" required>
<?php
echo '<option value="0" selected="selected" disabled>Select Manager</option>'."\n";
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
	
	if(isset($_POST['project_manager_id']) && $_POST['project_manager_id'] == $cur_user['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'" selected="selected">'.html_encode($cur_user['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
}
?>
					</select></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box select">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span style="color:red;"><strong>Property</strong></span></label><br>
					<span class="fld-input"><select id="property_id" name="property_id" required>
<?php
echo '<option value="0" selected="selected" disabled>Select Property</option>'."\n";
foreach ($property_info as $cur_info) {
	if(isset($_POST['property_id']) && $_POST['property_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
					</select></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box select">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><strong>Building #</strong></span></label><br>
					<span class="fld-input"><input type="text" name="building_number" value="<?php echo isset($_POST['building_number']) ? html_encode($_POST['building_number']) : '' ?>"/></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box select">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><strong>Unit #</strong></span></label><br>
					<span class="fld-input"><input type="text" name="unit_number" value="<?php echo isset($_POST['unit_number']) ? html_encode($_POST['unit_number']) : '' ?>"/></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box select">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><strong>Project name</strong></span></label><br>
					<span class="fld-input"><input type="text" name="project_title" value="<?php echo isset($_POST['project_title']) ? html_encode($_POST['project_title']) : '' ?>" size="40"/></span>
				</div>
			</div>
			<div class="txt-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="txt-box textarea">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><strong>Project description</strong></span><small>Please leave your description.</small></label>
					<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $page_param['fld_count'] ?>" name="project_desc" rows="5" cols="45" placeholder="Enter text message here and submit this form"><?php echo isset($_POST['project_desc']) ? html_encode($_POST['project_desc']) : '' ?></textarea></span></div>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box select">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><strong>Start Date</strong></span></label><br>
					<span class="fld-input"><input type="date" name="start_time" value="<?php echo isset($_POST['start_time']) && $start_time > 0 ? date('Y-m-d', $start_time) : '' ?>"/></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>" style="display:none">
				<div class="sf-box select">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span><strong>End Date</strong></span></label><br>
					<span class="fld-input"><input type="date" name="end_date" value="<?php echo isset($_POST['end_date']) && $end_date > 0 ? date('Y-m-d', $end_date) : '' ?>"/></span>
				</div>
			</div>
		</fieldset>
		<div class="frm-buttons">
			<span class="submit primary"><input type="submit" name="new_project" value="Submit" /></span>
		</div>
	</form>
</div>
	
<?php
require SITE_ROOT.'footer.php';