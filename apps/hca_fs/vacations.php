<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_fs', 9)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$search_by_year = isset($_GET['search_by_year']) ? intval($_GET['search_by_year']) : date('Y', time());
$previous_year = date('Ymd', strtotime(($search_by_year - 1).'1231'));
$current_year = strtotime($search_by_year);
$next_year = date('Ymd', strtotime(($search_by_year + 1).'0101'));

function hca_fs_get_vacations($arr, $uid, $month)
{
	$subarr = isset($arr[$uid]) ? $arr[$uid] : '';
	$output = array();
	
	if (!empty($subarr))
	{
		foreach ($subarr as $key => $data)
		{
			$scheduled_time = strtotime($data['scheduled']);
			$data_month = date('m', $scheduled_time);
			
			if ($month == $data_month)
			{
				if ($data['time_slot'] == 4)
					$output[] = '<span class="day-off">'.date('d', $scheduled_time).'</span>';
				else if ($data['time_slot'] == 5)
					$output[] = '<span class="sick-day">'.date('d', $scheduled_time).'</span>';
				else if ($data['time_slot'] == 6)
					$output[] = '<span class="vacation">'.date('d', $scheduled_time).'</span>';
			}
		}
	}
	
	return $output;
}

$time_slots = array(
	1 => 'ALL DAY',
	2 => 'A.M.',
	3 => 'P.M.',
	4 => 'DAY OFF',
	5 => 'SICK DAY',
	6 => 'VACATION',
);

$query = array(
	'SELECT'	=> 'r.*, u.realname, p.pro_name',
	'FROM'		=> 'hca_fs_requests AS r',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'r.employee_id=u.id'
		),
		array(
			'LEFT JOIN'		=> 'sm_property_db AS p',
			'ON'			=> 'r.property_id=p.id'
		),
	),
	'WHERE'		=> '(r.time_slot=4 OR r.time_slot=5 OR r.time_slot=6)',
	'ORDER BY'	=> 'r.scheduled',
);
$query['WHERE'] .= ' AND r.scheduled >'.$previous_year.' AND r.scheduled < '.$next_year;
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$assignment_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	if (!isset($assignment_info[$row['employee_id']][$row['scheduled']]))
		$assignment_info[$row['employee_id']][$row['scheduled']] = $row;
}

// Grab the users
$query = array(
	'SELECT'	=> 'u.id, u.email, u.realname, u.registered, g.g_id, g.g_user_title',
	'FROM'		=> 'users AS u',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'groups AS g',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'g.hca_fs=1',
	'ORDER BY'	=> 'u.realname ASC, u.username ASC',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$users_info[$row['id']] = $row;
}

// Setup the form
$page_param['fld_count'] = $page_param['group_count'] = $page_param['item_count'] = 0;

$Core->set_page_title('Vacations');
$Core->set_page_id('hca_fs_vacations', 'hca_fs');
require SITE_ROOT.'header.php';

$months_of_year = array(
	1 => 'January',
	2 => 'February',
	3 => 'March',
	4 => 'April',
	5 => 'May',
	6 => 'June',
	7 => 'July',
	8 => 'August',
	9 => 'September',
	10 => 'October',
	11 => 'November',
	12 => 'December',
);
?>

<div class="main-content main-frm" id="vacations">

	<div class="alert alert-warning" role="alert">
		<span>Legends:</span>
		<span class="legends day-off"></span><span>Day Off</span>
		<span class="legends sick-day"></span><span>Sick Day</span>
		<span class="legends vacation"></span><span>Vacation</span>
	</div>

	<nav class="navbar container-fluid search-box">
		<form method="get" accept-charset="utf-8" action="">
			<div class="row">
				<div class="col">
					<input type="number" name="search_by_year" value="<?php echo $search_by_year ?>" min="2020" max="2099" class="form-control"/>
				</div>
				<div class="col">
					<button type="submit" class="btn btn-outline-success float-none">Go</button>
				</div>
			</div>
		</form>
	</nav>

<?php if (!empty($users_info)) 
{ 
?>
	<table class="table table-striped table-bordered">
		<thead>
			<tr>
				<th>Name/Month</th>
<?php
	foreach ($months_of_year as $key => $month) {
		echo '<th class="month">'.$month.'</th>'."\n";
	}
?>
			</tr>
		</thead>
		<tbody>
<?php
	$odd_even_class = 'odd';
	foreach ($users_info as $cur_info)
	{
?>
			<tr class="<?php echo $odd_even_class; ?>">
				<td>
					<strong><a href="<?php echo $URL->link('user', $cur_info['id']) ?>"><?php echo html_encode($cur_info['realname']) ?></a></strong>
					<p><?php echo html_encode($cur_info['g_user_title']) ?></p>
				</td>
<?php
		foreach ($months_of_year as $key => $month)
		{
			$vacations = implode(' ', hca_fs_get_vacations($assignment_info, $cur_info['id'], $key));
			echo '<td class="days-numbers"><div class="time-slot">'.$vacations.'</div></td>'."\n";
		}
?>

			</tr>
<?php
		$odd_even_class = ($odd_even_class == 'odd') ? 'even' : 'odd';
	}
?>
		</tbody>
	</table>
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
</div>
<?php
require SITE_ROOT.'footer.php';