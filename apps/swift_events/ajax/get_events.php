<?php

define('SITE_ROOT', '../../../');
require SITE_ROOT.'include/common.php';

//if ($User->is_guest())
//	message($lang_common['No permission']);
$access3 = ($User->checkAccess('swift_events', 3)) ? true : false;//edit

$date = isset($_POST['date']) ? swift_trim($_POST['date']) : date('Y-m-d');
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

// FIST OF EVENTS
if ($date != '')
{
	$dt1 = new DateTime($date);
	$dt2 = new DateTime($date);
	$dt1->modify('Monday this week');
	$first_date_of_week = $dt1->format('Y-m-d');
	$dt2->modify('Sunday this week');
	$last_date_of_week = $dt2->format('Y-m-d');

	$ajax_content = $events_info = $search_query = [];
	
	$search_query[] = 'DATE(e.datetime_created) >= \''.$DBLayer->escape($first_date_of_week).'\'';
	$search_query[] = 'DATE(e.datetime_created) <= \''.$DBLayer->escape($last_date_of_week).'\'';
	if ($user_id > 0)
		$search_query[] = 'e.user_id='.$user_id;

	$query = [
		'SELECT'	=> 'e.*, u.realname',
		'FROM'		=> 'swift_events AS e',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'u.id=e.user_id'
			],
		],
//		'WHERE'		=> 'e.date='.$date.' AND e.poster_id='.$User->get('id'),
//		'WHERE'		=> 'e.date='.$date,
		//'WHERE'		=> 'DATE(e.datetime_created) >= \''.$DBLayer->escape($first_date_of_week).'\' AND DATE(e.datetime_created) <= \''.$DBLayer->escape($last_date_of_week).'\'',// 
		'ORDER BY'	=> 'e.datetime_created',
	];
	if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$events_info[] = $row;
	}
	
	$ajax_content[] = '<div class="card-header">';
	$ajax_content[] = '<h6 class="card-title mb-0">'.format_date($date, 'F, Y').'</h6>';
	$ajax_content[] = '</div>';

	$ajax_content[] = '<div class="accordion" id="accordionExample">';

	for($d = 1; $d < 8; $d++)
	{
		$cur_event = [];
		if (!empty($events_info))
		{
			foreach($events_info as $cur_info)
			{
				if ($d == format_date($cur_info['datetime_created'], 'w'))
				{
					$actions = ($access3 && $cur_info['user_id'] == $User->get('id')) ? '<span class="float-end"><i class="fas fa-edit" onclick="editEvent('.$cur_info['id'].')" data-bs-toggle="modal" data-bs-target="#modalWindow"></i><span>' : '<span class="float-end text-muted">'.html_encode($cur_info['realname']).'<span>';

					if ($cur_info['event_type'] == 1)
						$event_type = '<i class="fas fa-thumbtack text-primary me-1"></i>';
					else if ($cur_info['event_type'] == 2)
						$event_type = '<i class="fas fa-bell text-danger me-1"></i>';
					else
						$event_type = '<i class="fas fa-file-alt text-warning me-1"></i>';

					$event_status = ($cur_info['event_status'] == 1) ? '<i class="fas fa-check-circle text-success ms-1"></i>' : '';

					$cur_event[] = '<div class="row">';
					$cur_event[] = '<div class="col-md-1"><span class="">'.format_date($cur_info['datetime_created'], 'H:i').$event_status.'</span></div>';
					$cur_event[] = '<div class="col">'.$event_type.html_encode($cur_info['message']).''.$actions.'</div>';
					$cur_event[] = '</div>';
				}
			}
		}

		//$accordion_class[] = ($date == $dt1->format('Y-m-d') ? 'show' : '');
		$accordion_class = ['accordion-collapse collapse'];

		if (!empty($cur_event))
			$accordion_class[] = 'show';

		$ajax_content[] = '<div class="accordion-item">';
		$ajax_content[] = '<h2 class="accordion-header" id="heading'.$d.'">';
		$ajax_content[] = '<button class="accordion-button fw-bold py-1" type="button" data-bs-toggle="collapse" data-bs-target="#collapse'.$d.'" aria-expanded="true" aria-controls="collapse'.$d.'">'.$dt1->format('l, j').'</button>';
		$ajax_content[] = '</h2>';
		$ajax_content[] = '<div id="collapse'.$d.'" class="'.implode(' ', $accordion_class).'" aria-labelledby="heading'.$d.'">';
		$ajax_content[] = '<div class="accordion-body py-1">';

		if (!empty($cur_event))
			$ajax_content[] = implode("\n", $cur_event);
		else
			$ajax_content[] = '<div class="col alert-warning">No actions</div>';

		$ajax_content[] = '</div>';
		$ajax_content[] = '</div>';
		$ajax_content[] = '</div>';

		$dt1->modify('+1 day');
	}

	$ajax_content[] = '</div>';
	
	echo json_encode(
		['ajax_content' => implode('', $ajax_content)]
	);
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();