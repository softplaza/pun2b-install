<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id < 1)
	message($lang_common['Bad request']);

$access = ($User->is_admmod() || $User->get('sm_pc_access') == 5) ? true : false;
if(!$access)
	message($lang_common['No permission']);

if (isset($_POST['create_event']))
{
	$event_date = isset($_POST['event_date']) ? strtotime($_POST['event_date']) : time();
	$event_text = swift_trim($_POST['event_text']);
	
	if (!empty($event_date) && !empty($event_text))
	{
		$cur_user = (!empty($User->get('realname'))) ? $User->get('realname') : $User->get('username');
		$query = array(
			'INSERT'	=> 'project_id, user_id, user_name, event_date, event_text',
			'INTO'		=> 'sm_pest_control_events',
			'VALUES'	=> 
				'\''.$DBLayer->escape($id).'\',
				\''.$DBLayer->escape($User->get('id')).'\',
				\''.$DBLayer->escape($cur_user).'\',
				\''.$DBLayer->escape($event_date).'\',
				\''.$DBLayer->escape($event_text).'\''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Add flash message
	$FlashMessenger->add_info('New event was created');
	redirect($URL->link('sm_pest_control_events', $id), '.');
	}
}
else if (isset($_POST['delete_event']))
{
	$eid = intval(key($_POST['delete_event']));

	$query = array(
		'DELETE'	=> 'sm_pest_control_events',
		'WHERE'		=> 'id='.$eid
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Add flash message
	$FlashMessenger->add_info('Event was deleted');
	redirect($URL->link('sm_pest_control_events', $id), '.');
}

$query = array(
	'SELECT'	=> 'property, unit',
	'FROM'		=> 'sm_pest_control_records',
	'WHERE'		=> 'id='.$id
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$records_info = $DBLayer->fetch_assoc($result);

$query = array(
	'SELECT'	=> 'id, event_date, event_text',
	'FROM'		=> 'sm_pest_control_events',
	'WHERE'		=> 'project_id='.$id, //.' AND event_date<'.time(),
	'ORDER BY'	=> 'event_date ASC'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$events_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$events_info[] = $fetch_assoc;
}

// Setup the form
$page_param['fld_count'] = $page_param['group_count'] = $page_param['item_count'] = 0;

$Core->set_page_title('Events');
$Core->set_page_id('sm_pest_control_events', 'hca_pc');
require SITE_ROOT.'header.php';
?>

<style>
.main-subhead .hn{color: rebeccapurple;font-weight: bold;}
tbody td {
	padding: 1px 10px !important;
	border: 1px solid #adafc7;
}
table {table-layout: initial;}
textarea:focus {height: 100px;}
.datetime{width: 10%;}
.button{text-align: center;width: 10%;}
.ct-group{overflow-x:auto;}
.ct-group textarea{width:97%}
.datetime{width: 10%;}
.textarea{min-width:155px;}
.odd{background: #fafaeb;}
.even{background: #ebfaef;}
.empty-row{background-color: #eeebf1;padding: 10px 1px !important;}
</style>

  <div class="main-content main-frm">
	<div class="ct-group">
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			
			<table>
				<thead>
					<tr>
						<th class="datetime"><input type="date" name="event_date" value="<?php echo date('Y-m-d', time()); ?>" required/></th>
						<th class="textarea"><textarea name="event_text" rows="2" cols="55" placeholder="Leave your Follow-Up message here" required></textarea></th>
						<th class="button"><span class="submit primary"><input type="submit" name="create_event" value="Create" /></span></th>
					</tr>
					<tr>
						<th class="empty-row"></th>
						<th class="empty-row"></th>
						<th class="empty-row"></th>
					</tr>
					<tr>
						<th>Follow-Up Date</th>
						<th>Follow-Up Message</th>
						<th>Delete</th>
					</tr>
				</thead>
		</form>
		<form method="post" accept-charset="utf-8" action="">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			</div>
				<tbody>
<?php
$odd_even_class = 'odd';
foreach ($events_info as $event) 
{
?>
					<tr class="<?php echo $odd_even_class; ?>">
						<td class="datetime"><?php echo date('F d/Y', $event['event_date']); ?></td>
						<td><?php echo html_encode($event['event_text']); ?></td>
						<td class="button"><span class="submit primary caution"><input type="submit" name="delete_event[<?php echo $event['id']; ?>]" value="X" onclick="return confirm('Are you sure you want to delete this event?')"></span>
						</td>
					</tr>
<?php
$odd_even_class = ($odd_even_class == 'odd') ? 'even' : 'odd';
}
?>
				</tbody>
			</table>
		</form>
	</div>

<?php
if (empty($events_info))
{
?>
	<div class="ct-box info-box">
		<p>All events of all your projects will appear here. You don't have a single event yet.</p>
	</div>
<?php
}
?>
</div>
	
<?php
require SITE_ROOT.'footer.php';