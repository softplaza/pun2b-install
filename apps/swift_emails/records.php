<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->is_admin())
	message($lang_common['No permission']);

$search_by_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$search_by_app_id = isset($_GET['app_id']) ? swift_trim($_GET['app_id']) : '';

$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
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
$users_info = $assigned_users = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $fetch_assoc;
}

$search_query = [];
if ($search_by_app_id != '')
	$search_query[] = 'e.project_id=\''.$DBLayer->escape($search_by_app_id).'\'';
if ($search_by_user_id > 0)
	$search_query[] = 'e.sent_from='.$search_by_user_id;

$query = array(
	'SELECT'	=> 'COUNT(e.id)',
	'FROM'		=> 'swift_emails AS e',
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 'u.realname, u.last_visit, e.*',
	'FROM'		=> 'swift_emails AS e',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=e.sent_from'
		)
	),
	'ORDER BY'	=> 'e.sent_time DESC',
	'LIMIT'		=> $PagesNavigator->limit(),
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($row = $DBLayer->fetch_assoc($result))
{
	$main_info[] = $row;
}
$PagesNavigator->num_items($main_info);

$query = array(
	'SELECT'	=> 'e.*, u.realname',
	'FROM'		=> 'swift_emails AS e',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=e.sent_from'
		)
	),
	'ORDER BY'	=> 'e.sent_time',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$daily_data = $pie_data = [];
while ($row = $DBLayer->fetch_assoc($result))
{
	$today = '"'.date('Y-m-d', $row['sent_time']).'"';
	$daily_data[$today] = (isset($daily_data[$today])) ? ++$daily_data[$today] : 1;

	$realname = '"'.html_encode($row['realname']).'"';
	if ($row['realname'] != '')
		$pie_data[$realname] = (isset($pie_data[$realname])) ? ++$pie_data[$realname] : 1;
}

$Core->set_page_id('swift_emails_records', 'admin');
require SITE_ROOT.'header.php';
?>

<style>
.accordion-body {overflow-wrap: break-word;white-space: pre-wrap;}
</style>

<nav class="navbar search-bar">
	<form method="get" accept-charset="utf-8" action="">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col">
					<select name="user_id" class="form-control-sm">
<?php
$optgroup = 0;
echo '<option value="0" selected>All Users</option>';
foreach ($users_info as $cur_user)
{
	if ($cur_user['group_id'] != $optgroup) {
		if ($optgroup) {
			echo '</optgroup>';
		}
		echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
		$optgroup = $cur_user['group_id'];
	}
	
	if ($search_by_user_id == $cur_user['id'])
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'" selected>'.html_encode($cur_user['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col">
					<select name="app_id" class="form-control-sm">
<?php
if (isset($Hooks->apps_info) && !empty($Hooks->apps_info))
{
	echo '<option value="" selected>All Apps</option>';
	$apps_info = array_msort($Hooks->apps_info, ['title' => SORT_ASC]);
	foreach($apps_info as $cur_info)
	{
		if ($search_by_app_id == $cur_info['id'])
			echo '<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['title']).'</option>';
		else
			echo '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['title']).'</option>';
	}
}
?>
					</select>
				</div>
				<div class="col">
					<button class="btn btn-sm btn-outline-success" type="submit">Search</button>
				</div>
			</div>
		</div>
	</form>	
</nav>

<div class="row">
	<div class="col-8"><div id="chart_timeline_basic"></div></div>
	<div class="col-4"><div id="chart_pie"></div></div>
</div>

<div class="card-header">
	<h6 class="card-title mb-0">List of Emails</h6>
</div>
<?php		
if (!empty($main_info))
{
?>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Date/Time</th>
			<th>Sender</th>
			<th>Email from</th>
			<th>Recipients</th>
			<th>Message</th>
			<th>Status</th>
			<th>Reply to</th>
			<th>Type</th>
		</tr>
	</thead>
	<tbody>
<?php
	$i = 1;
	foreach ($main_info as $cur_info)
	{
		$status = ($cur_info['response'] == 'SENT') ? '<span class="badge badge-success">Sent</span>' : '<span class="badge badge-danger">Failed</span>';
?>
		<tr>
			<td><?php echo format_time($cur_info['sent_time']) ?></td>
			<td><a href="<?php echo $URL->link('user', $cur_info['sent_from']) ?>"><?php echo html_encode($cur_info['realname']) ?></a></td>
			<td><?php echo html_encode($cur_info['from_email']) ?></td>
			<td><?php echo html_encode($cur_info['sent_to']) ?></td>
			<td>
				<div class="accordion accordion-flush" id="accordionFlushExample<?=$i?>">
					<div class="accordion-item">
						<h2 class="accordion-header" id="flush-headingOne<?=$i?>">
						<button class="accordion-button collapsed p-1" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne<?=$i?>" aria-expanded="false" aria-controls="flush-collapseOne<?=$i?>"><?php echo html_encode($cur_info['subject']) ?></button>
						</h2>
						<div id="flush-collapseOne<?=$i?>" class="accordion-collapse collapse" aria-labelledby="flush-headingOne<?=$i?>" data-bs-parent="#accordionFlushExample<?=$i?>">
						<div class="accordion-body p-1"><?php echo html_to_text($cur_info['message']) ?></div>
						</div>
					</div>
				</div>
			</td>
			<td><?php echo $status ?></td>
			<td><?php echo html_encode($cur_info['reply_to']) ?></td>
			<td><?php echo html_encode($cur_info['email_type']) ?></td>
		</tr>
<?php
		++$i;
	}
?>
	</tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
var options = {
		series: [{
		name: "Emails sent",
		data: [<?php echo implode(',', array_values($daily_data)) ?>]
	}],
	chart: {
		height: 200,
		type: 'line',
		zoom: {
			enabled: false
		},
		toolbar: {show: false}
	},
	dataLabels: {
		enabled: true
	},
	stroke: {
		curve: 'straight'
	},
	grid: {
		row: {
		colors: ['#f3f3f3', 'transparent'], // takes an array which will be repeated on columns
		opacity: 0.5
		},
	},
	xaxis: {
		type: 'datetime',
		categories: [<?php echo implode(',', array_keys($daily_data)) ?>],
	}
};

var chart = new ApexCharts(document.querySelector("#chart_timeline_basic"), options);
chart.render();
</script>

<script>
	/*
var options2 = {
	series: [{
		data: [<?php echo implode(',', array_values($pie_data)) ?>]
	}],
	chart: {
		type: 'bar',
		height: 300,
		toolbar: {
            show: false
        }
	},
	plotOptions: {
		bar: {
			barHeight: '100%',
			distributed: true,
			horizontal: true,
		}
	},
	colors: ['#33b2df', '#546E7A', '#d4526e', '#13d8aa', '#A5978B', '#2b908f', '#f9a3a4', '#90ee7e',
		'#f48024', '#69d2e7'
	],
	dataLabels: {
		enabled: true,
		textAnchor: 'start',
		style: {
			colors: ['#fff']
		},
		formatter: function (val, opt) {
			return opt.w.globals.labels[opt.dataPointIndex] + ":  " + val
		},
		offsetX: 0,
		dropShadow: {
			enabled: true
		}
	},
	stroke: {
		width: 1,
		colors: ['#fff']
	},
	xaxis: {
		categories: [<?php echo implode(',', array_keys($pie_data)) ?>],
	},
	yaxis: {
		labels: {
			show: false
		}
	},
	tooltip: {
		theme: 'dark',
		x: {
			show: false
		},
		y: {
			title: {
				formatter: function () {
					return ''
				}
			}
		}
	},
	legend: {
        show: false,
    },
	dataLabels: {
        formatter: function (val, opts) {
            return opts.w.config.series[opts.seriesIndex]
        },
    },
};
var chart = new ApexCharts(document.querySelector("#chart_pie"), options2);
chart.render();
*/

var options = {
	series: [<?php echo implode(',', array_values($pie_data)) ?>],
		chart: {
		width: 380,
		type: 'pie',
	},
	labels: [<?php echo implode(',', array_keys($pie_data)) ?>],
	responsive: [{
		breakpoint: 480,
		options: {
		chart: {
			width: 200
		},
		legend: {
			position: 'bottom'
		}
		}
	}],
	// display values
	dataLabels: {
        formatter: function (val, opts) {
            return opts.w.config.series[opts.seriesIndex]
        },
    },
};

var chart = new ApexCharts(document.querySelector("#chart_pie"), options);
chart.render();
</script>

<?php
} else {
?>
<div class="alert alert-warning my-3" role="alert">You have no items on this page or not found within your search criteria.</div>
<?php
}
require SITE_ROOT.'footer.php';
