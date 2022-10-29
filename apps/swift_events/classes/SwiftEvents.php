<?php

/**
 * 
 * 
 */

class SwiftEvents
{
	var $swift_events = [];
	var $search_query = [];

	var $search_by_date;
	var $search_by_user_id;
	var $search_by_type;
	var $cur_month;
	var $monday_this_week;
	var $sunday_this_week;

	var $weekdays = [
		//0 => 'Sunday',
		1 => 'Monday',
		2 => 'Tuesday',
		3 => 'Wednesday',
		4 => 'Thursday',
		5 => 'Friday',
		6 => 'Saturday',
		7 => 'Sunday'
	];
	var $short_weekdays = [
		//0 => 'Sunday',
		1 => 'Mo',
		2 => 'Tu',
		3 => 'We',
		4 => 'Th',
		5 => 'Fr',
		6 => 'Sa',
		0 => 'Su'
	];

	var $event_types = [
		0 => 'Note',
		1 => 'Task',
		2 => 'Reminder',
	];

	var $event_statuses = [
		0 => 'Active',
		1 => 'Completed',
		2 => 'On Hold',
		3 => 'Removed',
	];

	function getEvents()
	{
		global $DBLayer, $User;

		$this->search_by_date = isset($_GET['date']) ? swift_trim($_GET['date']) : date('Y-m-d');
		$this->search_by_type = isset($_GET['type']) ? swift_trim($_GET['type']) : 'year';
		$this->search_by_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

		if ($this->search_by_type == 'day')
			$this->search_query[] = 'DATE(e.datetime_created)=\''.$DBLayer->escape($this->search_by_date).'\'';
		else if ($this->search_by_type == 'year')
			$this->search_query[] = 'YEAR(e.datetime_created)=\''.$DBLayer->escape($this->search_by_date).'\'';

		if ($this->search_by_user_id > 0)
			$this->search_query[] = 'u.id='.$this->search_by_user_id;

		// Get events
		$query = [
			'SELECT'	=> 'e.*, u.realname',
			'FROM'		=> 'swift_events as e',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'u.id=e.user_id'
				],
			],
			'ORDER BY'	=> 'e.datetime_created',
		];
		if (!empty($this->search_query)) $query['WHERE'] = implode(' AND ', $this->search_query);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result)) {
			$this->swift_events[] = $row;
		}
	}

	function showEvents()
	{
		global $User;

		$access3 = ($User->checkAccess('swift_events', 3)) ? true : false;//edit

		$dt1 = new DateTime($this->search_by_date);
		$dt1->modify('Monday this week');

		$content = [];
		$content[] = '<div class="card-header">';
		$content[] = '<h6 class="card-title mb-0">'.format_date($this->search_by_date, 'F, Y').'</h6>';
		$content[] = '</div>';

		$content[] = '<div class="accordion" id="accordionExample">';

		for($d = 1; $d < 8; $d++)
		{
			$cur_event = [];
			if (!empty($this->swift_events))
			{
				foreach($this->swift_events as $cur_info)
				{
					if ($dt1->format('Y-m-d') == format_date($cur_info['datetime_created'], 'Y-m-d'))
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
						$cur_event[] = '<div class="col">'.$event_type.html_encode($cur_info['message']).' '.$actions.'</div>';
						$cur_event[] = '</div>';
					}
				}
			}

			$accordion_class = ['accordion-collapse collapse'];
			//$accordion_class[] = ($date == $dt1->format('Y-m-d') ? 'show' : '');

			if (!empty($cur_event))
				$accordion_class[] = 'show';

			$content[] = '<div class="accordion-item">';
			$content[] = '<h2 class="accordion-header" id="heading'.$d.'">';
			$content[] = '<button class="accordion-button fw-bold py-1" type="button" data-bs-toggle="collapse" data-bs-target="#collapse'.$d.'" aria-expanded="true" aria-controls="collapse'.$d.'">'.$dt1->format('l, j').'</button>';
			$content[] = '</h2>';
			$content[] = '<div id="collapse'.$d.'" class="'.implode(' ', $accordion_class).'" aria-labelledby="heading'.$d.'">';
			$content[] = '<div class="accordion-body py-1">';

			if (!empty($cur_event))
				$content[] = implode("\n", $cur_event);
			else
				$content[] = '<div class="col alert-warning">No actions</div>';

			$content[] = '</div>';
			$content[] = '</div>';
			$content[] = '</div>';

			$dt1->modify('+1 day');
		}
		$content[] = '</div>';

		echo implode("\n", $content);
	}

	function showMonth()
	{
		global $URL;

		$dt = new DateTime($this->search_by_date);
		$this->cur_month = $dt->format('n');
		$dt->modify('first day of this month');
		$dt->modify('Monday this week');
?>
<div class="card-header">
	<h6 class="card-title mb-0"><?=format_date($this->search_by_date, 'F, Y')?></h6>
</div>
<div class="row row-cols-7 row-cols-md-7 g-4 mx-1">
<?php
		foreach($this->short_weekdays as $key => $weekday)
		{
?>
	<div class="col px-0">
		<div class="card h-100">
			<div class="card-body px-1 py-1">
				<h5 class="card-title"><?=$weekday?></h5>
			</div>
		</div>
	</div>
<?php
		}
?>
</div>

<?php
		for($w = 1; $w < 7; $w++)
		{
?>
<div class="row row-cols-7 row-cols-md-7 g-4 mx-1">
<?php
			for($d = 1; $d < 8; $d++)
			{
				$cur_date = $dt->format('Y-m-d');
				$display_date = ($this->cur_month == $dt->format('n')) ? $dt->format('j') : '';
				//$display_message = ($this->cur_month == $dt->format('n')) ? $this->getMonthMessage($cur_date) : '';

				$css_today = '';
				if ($dt->format('Y-m-d') == date('Y-m-d'))
					$css_today = ' alert-info fw-bold';
				else if ($this->hasMessages($dt->format('Y-m-d'))) // 
					$css_today = ' alert-warning';
?>
	<div class="col px-0">
		<div class="card h-100<?=$css_today?>">
			<div class="card-body px-1 py-1">
				<h2 class="card-title"><a href="#!" onclick="getEventsOfWeek('<?=$dt->format('Y-m-d')?>')"><?=$display_date?></a></h2>
			</div>
		</div>
	</div>
<?php
				$dt->modify('+1 day');
			}
?>
</div>
<?php
		}
	}

	function showYear()
	{
		global $URL;

		$dt = new DateTime($this->search_by_date);

		$next_day = $dt->setDate($dt->format('Y'), 1, 1);
		$this->cur_month = $dt->format('n');
?>
<div class="card-header">
	<h6 class="card-title mb-0"><?=format_date($this->search_by_date, 'Y')?></h6>
</div>

<div class="row row-cols-4 row-cols-md-4 g-4 mx-1">
<?php
		for($m = 1; $m < 13; $m++)
		{
			$next_month = $dt->setDate($dt->format('Y'), $m, 1);
			$date_of_month = ($m == $dt->format('n') ? date('Y-m-d') : $next_month->format('Y-m-d'));
?>
	<div class="col px-0 card-month">
		<div class="card h-100">
			<div class="card-body px-1 py-0 my-1">
				<h5 class="card-title"><a href="<?=$URL->genLink('swift_events_calendar', ['type' => 'month', 'date' => $date_of_month])?>"><?=$next_month->format('F')?></a></h5>
				<!--<h5 class="card-title"><?=$next_month->format('F')?></h5>-->
				<hr class="my-0">
<?php

			echo '<div class="row row-cols-7 row-cols-md-7 g-4 mx-1">';
			foreach($this->short_weekdays as $key => $shortday)
			{
				echo '<div class="col px-0 border">';
				echo '<span class="'.(in_array($key, [6,7,0]) ? 'text-danger' : 'text-success').'">'.$shortday.'</span>';
				echo '</div>';
			}
			echo '</div>';

			for($w = 1; $w < 7; $w++)
			{
				$dt->modify('Monday this week');

				echo '<div class="row row-cols-7 row-cols-md-7 g-4 mx-1">';

				foreach($this->weekdays as $key => $weekday)
				{
					// $URL->genLink('swift_events_calendar', ['type' => 'day', 'date' => $next_month->format('Y-m-d')])
					$current_date = ($m == $dt->format('n')) ? '<a href="#heading'.$key.'" onclick="getEventsOfWeek(\''.$next_month->format('Y-m-d').'\')">'.$next_day->format('j').'</a>' : '';

					$css_today = '';
					if ($next_day->format('Y-m-d') == date('Y-m-d'))
						$css_today = ' alert-info fw-bold';
					else if ($this->hasMessages($next_day->format('Y-m-d')) && $m == $dt->format('n')) // 
						$css_today = ' alert-warning';
						//$css_today = ' '.$next_day->format('Y-m-d');

					echo '<div class="col px-0 border'.$css_today.'">';
					echo '<span class="">'.$current_date.'</span>';
					//echo '<i class="fas fa-dot-circle"></i>';
					echo '</div>';

					$next_day->modify('+1 day');
					$this->cur_month = $next_day->format('n');
				}
				echo '</div>';
			}
?>
			</div>
		</div>
	</div>
<?php
			$next_month->modify('+1 month');
		}
?>
</div>
<?php
	}

	function hasMessages($date)
	{
		if (!empty($this->swift_events))
		{
			foreach($this->swift_events as $cur_info)
			{
				if ($date == format_date($cur_info['datetime_created'], 'Y-m-d'))
				{
					return true;
					//break;
				}
			}
		}
		return false;
	}

	function getMessage($date)
	{
		if (!empty($this->swift_events)) 
		{
			$output = [];
			foreach($this->swift_events as $event)
			{
				$event_date = format_date($event['datetime_created'], 'Y-m-d');
				if ($date == $event_date)
					$output[] = '<p class="card-text" style=""><span class="text-muted text-decoration-underline">'.format_date($event['datetime_created'], 'H:i').'</span> '.html_encode($event['message']).'</p>';
			}

			return implode("\n", $output);
		}
	}

	function getMonthMessage($date)
	{
		if (!empty($this->swift_events)) 
		{
			$output = [];
			foreach($this->swift_events as $event)
			{
				$event_date = format_date($event['datetime_created'], 'Y-m-d');
				if ($date == $event_date)
					$output[] = '<p class="card-text" style=""><span class="text-muted text-decoration-underline">'.format_date($event['datetime_created'], 'H:i').'</span> '.substr($event['message'], 0, 20).'...</p>';
			}

			return implode("\n", $output);
		}
	}
}
