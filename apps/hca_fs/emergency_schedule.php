<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_fs', 6)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$EmergencySchedule = new EmergencySchedule;

function formatDST($timestamp)
{
	return $timestamp = (date('I', $timestamp) == 1) ? $timestamp + 3600 : $timestamp;
}

$number_of_week = isset($_GET['weeks']) && intval($_GET['weeks'] < 8) ? intval($_GET['weeks']) : intval($Config->get('o_hca_fs_number_of_week'));

$FirstDayOfWeek = new DateTime($_GET['date']);
$FirstDayOfWeek->modify('Monday this week');

$query = array(
	'SELECT'	=> 'u.id, u.realname, u.email, u.first_name, u.last_name, g.g_id, g.g_user_title',
	'FROM'		=> 'users AS u',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'groups AS g',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'g.g_id='.$Config->get('o_hca_fs_maintenance'),
	'ORDER BY'	=> 'u.username',
);
$query['ORDER BY'] = ($User->get('users_sort_by') == 1) ? 'last_name' : 'realname';
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$founded_user_datas = array();
while ($row = $DBLayer->fetch_assoc($result)) {
//	if (!isset($founded_user_datas[$row['id']]))
	$founded_user_datas[$row['id']] = $row;
		
	if ($User->get('users_sort_by') == 1)
		$founded_user_datas[$row['id']]['realname'] = $row['last_name'].' '.$row['first_name'];		
}

$LastDayOfPeriod = clone $FirstDayOfWeek;
$LastDayOfPeriod->modify('+'.$Config->get('o_hca_fs_number_of_week').' weeks');

$query = array(
	'SELECT'	=> 'es.*, u.realname, u.group_id, u.first_name, u.last_name',
	'FROM'		=> 'hca_fs_emergency_schedule AS es',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'es.user_id=u.id'
		),
	),
	'WHERE'		=> 'es.date_week_of >= \''.$FirstDayOfWeek->format('Y-m-d').'\' AND es.date_week_of <= \''.$LastDayOfPeriod->format('Y-m-d').'\'',
	'ORDER BY'	=> 'es.date_week_of',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[$row['id']] = $row;
	
	if ($User->get('users_sort_by') == 1)
		$main_info[$row['id']]['realname'] = $row['last_name'].' '.$row['first_name'];
}

$query = array(
	'SELECT'	=> 'p.zone, p.pro_name, p.emergency_uid',
	'FROM'		=> 'sm_property_db AS p',
	'WHERE'		=> 'p.enabled=1 AND p.zone > 0',
	'ORDER BY'	=> 'p.zone',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
}

// Update only by one PopUpBox
if (isset($_POST['update']))
{
	$em_id = isset($_POST['em_id']) ? intval($_POST['em_id']) : 0;
	$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
	$zone = isset($_POST['zone']) ? intval($_POST['zone']) : 0;
	$date_week_of = isset($_POST['date_week_of']) ? swift_trim($_POST['date_week_of']) : 0;

	$query = array(
		'SELECT'	=> 'es.id',
		'FROM'		=> 'hca_fs_emergency_schedule AS es',
		'WHERE'		=> 'date_week_of=\''.$DBLayer->escape($date_week_of).'\' AND es.zone='.$zone,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$duplicate = $DBLayer->fetch_assoc($result);

	if ($user_id == 0)
		$Core->add_error('Select a employee.');

	if (empty($Core->errors))
	{
		if (isset($duplicate['id']) && $duplicate['id'] > 0 && $user_id > 0)
		{
			$query = array(
				'UPDATE'	=> 'hca_fs_emergency_schedule',
				'SET'		=> 'user_id='.$user_id,
				'WHERE'		=> 'id='.$duplicate['id']
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
		else if ($em_id > 0)
		{
			$query = array(
				'UPDATE'	=> 'hca_fs_emergency_schedule',
				'SET'		=> 'user_id=\''.$DBLayer->escape($user_id).'\'',
				'WHERE'		=> 'id='.$em_id
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
		else
		{
			$query = array(
				'INSERT'	=> 'user_id, zone, date_week_of',
				'INTO'		=> 'hca_fs_emergency_schedule',
				'VALUES'	=> 
					'\''.$DBLayer->escape($user_id).'\',
					\''.$DBLayer->escape($zone).'\',
					\''.$DBLayer->escape($date_week_of).'\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
		
		$EmergencySchedule->update_pdf();
		
		// Add flash message
		$flash_message = 'Emergency schedule has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
// Update all schedule by "Refresh Schedule"
else if (isset($_POST['refresh_pdf']))
{
	if (!class_exists('Mpdf\Mpdf'))
		$Core->add_error('No such Mpdf Class.');

	if (empty($Core->errors))
	{
		$form = isset($_POST['form']) ? $_POST['form'] : array();

		if (!empty($form))
		{
			$query = array(
				'SELECT'	=> 'es.*',
				'FROM'		=> 'hca_fs_emergency_schedule AS es',
				'WHERE'		=> 'es.date_week_of >= \''.$FirstDayOfWeek->format('Y-m-d').'\' AND es.date_week_of <= \''.$LastDayOfPeriod->format('Y-m-d').'\'',
			);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$db_info = array();
			while ($row = $DBLayer->fetch_assoc($result)) {
				$db_info[$row['zone']][$row['date_week_of']] = $row;
			}

			foreach($form as $date_week_of => $data)
			{
				foreach($data as $zone => $uid)
				{
					if (!isset($db_info[$zone][$date_week_of]))
					{
						$query = array(
							'INSERT'	=> 'user_id, zone, date_week_of',
							'INTO'		=> 'hca_fs_emergency_schedule',
							'VALUES'	=> 
								'\''.$DBLayer->escape($uid).'\',
								\''.$DBLayer->escape($zone).'\',
								\''.$DBLayer->escape($date_week_of).'\''
						);
						$DBLayer->query_build($query) or error(__FILE__, __LINE__);
					}
					else
					{
						$query = array(
							'UPDATE'	=> 'hca_fs_emergency_schedule',
							'SET'		=> 'user_id=\''.$DBLayer->escape($uid).'\'',
							'WHERE'		=> 'id='.$db_info[$zone][$date_week_of]['id']
						);
						$DBLayer->query_build($query) or error(__FILE__, __LINE__);
					}
				}
			}
		}

		$EmergencySchedule->update_pdf();
		
		// Add flash message
		$flash_message = 'Emergency schedule has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

//send_email for each one
else if (isset($_POST['send_email']))
{
	$form = isset($_POST['form']) ? $_POST['form'] : array();
	
	if (empty($Core->errors))
	{
		if (!empty($form))
		{
			foreach($form as $date_week_of => $data)
			{
				foreach($data as $zone => $uid)
				{	
					$query = array(
						'INSERT'	=> 'user_id, zone, date_week_of',
						'INTO'		=> 'hca_fs_emergency_schedule',
						'VALUES'	=> 
							'\''.$DBLayer->escape($uid).'\',
							\''.$DBLayer->escape($zone).'\',
							\''.$DBLayer->escape($date_week_of).'\''
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);	
				}
			}
		}
		
		$EmergencySchedule->update_pdf();
		
		$mail_subject = 'Maintenance Emergency Stand-By Weekends';
		$mail_message = [];
		$mail_message[] = 'Hello,';
		$mail_message[] = 'Emergency Covering Weekends has been updated.';
		$mail_message[] = 'Please see the schedule in the attached file.';
		$mail_attachments = ['files/emergency_schedule.pdf'];

		$email_list = [];
		foreach($founded_user_datas as $user_info) {
			$email_list[] = $user_info['email'];
		}

		if (!empty($email_list))
		{
			$SwiftMailer = new SwiftMailer;
			$SwiftMailer->send(implode(',', $email_list), $mail_subject, implode(',', $mail_message), $mail_attachments);
		}

		// Add flash message
		$flash_message = 'Emergency schedule has been sent by email';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'u.id, u.realname, u.email, u.group_id, u.first_name, u.last_name, u.hca_fs_zone',
	'FROM'		=> 'users AS u',
	'WHERE'		=> 'u.hca_fs_zone > 0 AND u.group_id='.intval($Config->get('o_hca_fs_maintenance')),
	'ORDER BY'	=> 'u.realname',
);
$query['ORDER BY'] = ($User->get('users_sort_by') == 1) ? 'last_name' : 'realname';
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$emergency_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$emergency_info[$row['id']] = $row;
	
	if ($User->get('users_sort_by') == 1);
		$emergency_info[$row['id']]['realname'] = $row['last_name'].' '.$row['first_name'];
}

$Core->add_page_action('<li><a class="dropdown-item" href="files/emergency_schedule.pdf?'.time().'" target="_blank"><i class="fa fa-file-pdf-o fa-2x"></i>Print as PDF</a></li>');
$SwiftMenu->addNavAction('<li><a class="dropdown-item" href="files/emergency_schedule.pdf?'.time().'" target="_blank"><i class="fa fa-file-pdf-o fa-1x" aria-hidden="true"></i> Print as PDF</a></li>');

$Core->set_page_title('Maintenance Emergency Stand-By Weekends');
$Core->set_page_id('hca_fs_emergency_schedule', 'hca_fs');
require SITE_ROOT.'header.php';

if (!empty($founded_user_datas))
{
	$page_param['th'] = array();
	$page_param['th']['pro_name'] = '<th rowspan="2" scope="col"><strong>Property Name</strong></th>';
	$page_param['th']['days'] = '<th colspan="7" scope="col"><strong>Days of Week</strong></th>';
?>
	<div class="alert alert-warning" role="alert">Covering Weekends - Friday 5:00 pm through Monday - 8:00 am</div>

	<nav class="navbar container-fluid search-box">
		<form method="get" accept-charset="utf-8" action="">
			<div class="row">
				<div class="col">
					<input type="date" name="date" value="<?php echo $_GET['date'] ?>" class="form-control"/>
				</div>
				<div class="col">
					<input type="number" name="weeks" value="<?php echo $number_of_week ?>" size="5" class="form-control"/>
				</div>
				<div class="col">
					<button type="submit" class="btn btn-outline-success float-none">Go to Date</button>
				</div>
			</div>
		</form>
	</nav>

	<form method="post" accept-charset="utf-8" action="" id="emergency_schedule">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<table class="table table-striped table-bordered">
			<thead>
				<tr class="head1">
					<th class="" colspan="8"></th>
					<th class="h-zone">Zone 1</th>
					<th class="h-zone">Zone 2</th>
					<th class="h-zone">Zone 3</th>
				</tr>
				<tr class="p-zone">
					<td class="" colspan="8"></td>
					<td><?php echo implode(', ', $EmergencySchedule->getPropertiesByZone($property_info, 1)) ?></td>
					<td><?php echo implode(', ', $EmergencySchedule->getPropertiesByZone($property_info, 2)) ?></td>
					<td><?php echo implode(', ', $EmergencySchedule->getPropertiesByZone($property_info, 3)) ?></td>
				</tr>
				<tr>
					<th class=""></th>
					<th class="day-of-week">Mo</th>
					<th class="day-of-week">Tu</th>
					<th class="day-of-week">We</th>
					<th class="day-of-week">Th</th>
					<th class="day-of-week">Fr</th>
					<th class="day-of-week">Sa</th>
					<th class="day-of-week">Su</th>
					<th colspan="3"></th>
				</tr>
			</thead>
			<tbody>
<?php

	$NextMonday = clone $FirstDayOfWeek;
	for($i = 0; $i < $number_of_week; ++$i)
	{
		$emergency_zone = array();
		$cur_day = $NextMonday->format('Y-m-d');

		foreach($main_info as $cur_info)
		{
			$img_setup_user = '<span class="setup-user"><img src="'.BASE_URL.'/img/edit.png"/></span>';
			// Finding Assigned users
			if ($cur_info['date_week_of'] == $cur_day) {
				$emergency_zone[$cur_info['zone']] = '<div class="assign-info scheduled" onclick="openPopUpWindow(\''.$cur_day.'\','.$cur_info['zone'].','.$cur_info['id'].');">'.$img_setup_user.'<strong>'.$cur_info['realname'].'</strong></div>';
				
				$emergency_info = $EmergencySchedule->moveToEnd($cur_info['user_id']);
			}
		}
		
		if (!isset($emergency_zone[1])) {
			$emergency_zone[1] = $EmergencySchedule->getNextAssigned(1, $cur_day);
			$emergency_info = $EmergencySchedule->rebuildArray();
		}
		
		if (!isset($emergency_zone[2])) {
			$emergency_zone[2] = $EmergencySchedule->getNextAssigned(2, $cur_day);
			$emergency_info = $EmergencySchedule->rebuildArray();
		}
		
		if (!isset($emergency_zone[3])) {
			$emergency_zone[3] = $EmergencySchedule->getNextAssigned(3, $cur_day);
			$emergency_info = $EmergencySchedule->rebuildArray();
		}
		
		$td = [];
		$CurDayOfWeek = clone $NextMonday;
		
		for($d = 1; $d < 8; ++$d)
		{
			$css_day = (in_array($d, array(5,6,7))) ? 'day-on' : 'day-off';
			$td[] = '<td class="'.$css_day.'">'.$CurDayOfWeek->format("j").'</td>';
			$CurDayOfWeek->modify("+1 day");
		}
?>
				<tr>
					<td class="months"><?php echo $NextMonday->format("F") ?></td>
					<?php echo implode('', $td) ?>
					<td id="td_<?php echo $cur_day ?>_1"><?php echo isset($emergency_zone[1]) ? $emergency_zone[1] : 'error' ?></td>
					<td id="td_<?php echo $cur_day ?>_2"><?php echo isset($emergency_zone[2]) ? $emergency_zone[2] : 'error' ?></td>
					<td id="td_<?php echo $cur_day ?>_3"><?php echo isset($emergency_zone[3]) ? $emergency_zone[3] : 'error' ?></td>
				</tr>
<?php
		$NextMonday->modify("+1 week");
	}
?>
			</tbody>
		</table>
		<div class="card">
			<div class="card-body">
				<button type="submit" name="refresh_pdf" class="btn btn-primary">Refresh PDF</button>
				<button type="submit" name="send_email" class="btn btn-success" onclick="return confirm('Are you sure you want to send the Schedule for each Maintenance?')">Send Schedule</button>
			</div>
		</div>
	</form>
	<div class="card">
		<div class="card-body">
			<style>#pdf_preview{width: 100%;height: 400px;zoom: 2;}</style>
			<iframe id="pdf_preview" src="files/emergency_schedule.pdf?<?php echo time() ?>"></iframe>
		</div>
	</div>

<?php
} else {
?>
	<div class="card">
		<div class="card-body">
			<div class="alert alert-warning" role="alert">You have no items on this page.</div>
		</div>
	</div>
<?php
}
?>
<div class="pop-up-window">
	<form method="post" accept-charset="utf-8" action="">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<input type="hidden" name="em_id" value="0" />
		</div>
		<div class="head">
			<p class="close"><img src="<?=BASE_URL?>/img/close.png" onclick="closePopUpWindow()"></p>
			<p class="title">Assigning</p>
		</div>
		<div class="edit-assign">
			<p id="emergency_users">LOADING...</p>
			<p>*The employees marked in red are already assigned.</p>
			<p class="btn-action"><span class="submit primary"><input type="submit" name="update" value="Assign"/></span></p>
		</div>
	</form>
</div>

<script>
function openPopUpWindow(d,z,id)
{
	var pos = $("#td_"+d+"_"+z).position();
	var posL = pos.left + 175;
	var posT = pos.top - 165;
	$(".pop-up-window").css("top", posT + "px");
	$(".pop-up-window").css("left", posL + "px");
	$(".pop-up-window").slideDown("2000");
	$('.pop-up-window input[name="em_id"]').val(id);
	
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_fs_ajax_get_emergency_users')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_fs_ajax_get_emergency_users') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({date_week_of:d,zone:z,id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#emergency_users").empty().html(re.users_list);
		},
		error: function(re){
			document.getElementById("#emergency_users").innerHTML = re;
		}
	});
}
function closePopUpWindow()
{
	$(".pop-up-window").css("display","none");
	$("#emergency_users").empty().html('');
	$('.pop-up-window input[name="em_id"]').val(0);
}
</script>

<?php
require SITE_ROOT.'footer.php';