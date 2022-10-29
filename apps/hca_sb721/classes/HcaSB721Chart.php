<?php

class HcaSB721Chart
{
	public $year = '2022';

	public $all_projects = [];
	public $all_vendors = [];

	public $project_data = [];
	public $vendors_data = [];
	public $events_data = [];

	function __construct(){}

	function addAllProjects($data){
		$this->all_projects = $data;
	}
	function addAllvendors($data){
		$this->all_vendors = $data;
	}
	function addProject($data){
		$this->project_data = $data;
	}
	function addVendors($data){
		$this->vendors_data = $data;
	}
	function addEvents($data){
		$this->events_data = $data;
	}

	function getRow($role, $name, $style, $start, $end)
	{
		$cur_row = [];
		$cur_row[] = ',';
		$cur_row[] = '[';
		$cur_row[] = '"'.swift_escape($role).'", ';
		$cur_row[] = '"'.swift_escape($name).'", ';
		$cur_row[] = '"'.$style.'", ';
		$cur_row[] = 'new Date('.$this->formatDate($start).'), ';
		$cur_row[] = 'new Date('.$this->formatDate($end).')';
		$cur_row[] = ']';
	
		return implode('', $cur_row);
	}

	function getAllProjects()
	{
		$output = [];

		if (!empty($this->all_projects))
		{
			$date_today = date('Y-m-d');
			foreach($this->all_projects as $cur_info)
			{
				$cur_row = [];
				$project_title = swift_escape($cur_info['pro_name'].' '.$cur_info['unit_number']);

				if (format_date($cur_info['date_preinspection_start']) != '')
				{
					$cur_info['date_preinspection_end'] = (format_date($cur_info['date_preinspection_end']) != '') ? $cur_info['date_preinspection_end'] : $cur_info['date_preinspection_start'];

					$cur_row[] = '[';
					$cur_row[] = '"'.$project_title.'", ';
					$cur_row[] = '"'.swift_escape($cur_info['realname']).'", ';
					$cur_row[] = '"#0dcaf0", ';
					$cur_row[] = 'new Date('.$this->formatDate($cur_info['date_preinspection_start']).'), ';
					$cur_row[] = 'new Date('.$this->formatDate($cur_info['date_preinspection_end']).')';
					$cur_row[] = ']';
				}

				if (format_date($cur_info['date_city_inspection_start']) != '')
				{
					$cur_info['date_city_inspection_end'] = (format_date($cur_info['date_city_inspection_end']) != '') ? $cur_info['date_city_inspection_end'] : $cur_info['date_city_inspection_start'];

					$cur_row[] = ','."\n";
					$cur_row[] = '[';
					$cur_row[] = '"'.$project_title.'", ';
					$cur_row[] = '"'.swift_escape($cur_info['city_engineer']).'", ';
					$cur_row[] = '"#2a56c6", ';
					$cur_row[] = 'new Date('.$this->formatDate($cur_info['date_city_inspection_start']).'), ';
					$cur_row[] = 'new Date('.$this->formatDate($cur_info['date_city_inspection_end']).')';
					$cur_row[] = ']';
				}

				if (!empty($this->all_vendors))
				{
					foreach($this->all_vendors as $vendor)
					{
						$incompleted_job = false;

						if ($vendor['project_id'] == $cur_info['id'] && (format_date($vendor['date_bid']) != '' || format_date($vendor['date_start_job']) != '' || format_date($vendor['date_end_job']) != ''))
						{
							if (format_date($vendor['date_bid']) != '' && format_date($vendor['date_start_job']) != '' && format_date($vendor['date_end_job']) != '')
							{
								$cur_row[] = $this->getRow($project_title, $vendor['vendor_name'], '#ffbc00', $vendor['date_bid'], $vendor['date_start_job']);//orange
								$cur_row[] = $this->getRow($project_title, '', '#0b8043', $vendor['date_start_job'], $vendor['date_end_job']);//green
							}
							else if (format_date($vendor['date_bid']) != '' && format_date($vendor['date_start_job']) != '' && format_date($vendor['date_end_job']) == '')
							{
								$cur_row[] = $this->getRow($project_title, $vendor['vendor_name'], '#ffbc00', $vendor['date_bid'], $vendor['date_start_job']);//orange

								if (compare_dates($date_today, $vendor['date_start_job'], 1))
									$cur_row[] = $this->getRow($project_title, '', '#0b8043', $vendor['date_start_job'], $date_today);//green
							}
							else if (format_date($vendor['date_bid']) != '' && format_date($vendor['date_start_job']) == '' && format_date($vendor['date_end_job']) == '')
							{
								if (compare_dates($date_today, $vendor['date_bid'], 1))
									$cur_row[] = $this->getRow($project_title, $vendor['vendor_name'], '#ffbc00', $vendor['date_bid'], $date_today);//orange
							}
						}
					}
				}

				$output[] = implode('', $cur_row);
			}
		}

		return implode(','."\n", $output);
	}

	function genChartTimeline()
	{
		$project = $vendors = $output = $dates = [];
		$min_date = $max_date = '';

		if (!empty($this->project_data))
		{ 
			$date_today = date('Y-m-d');
			if (format_date($this->project_data['date_preinspection_start']) != '')
			{
				$this->project_data['date_preinspection_end'] = (format_date($this->project_data['date_preinspection_end']) != '') ? $this->project_data['date_preinspection_end'] : $this->project_data['date_preinspection_start'];

				$project[] = '["Pre-Inspection", 
					"'.$this->project_data['realname'].': '.format_date($this->project_data['date_preinspection_start'], 'n/j/y').' - '.format_date($this->project_data['date_preinspection_end'], 'n/j/y').'", 
					"#0dcaf0", 
					new Date('.$this->formatDate($this->project_data['date_preinspection_start']).'), 
					new Date('.$this->formatDate($this->project_data['date_preinspection_end']).')]';

				$dates[] = $this->project_data['date_preinspection_start'];
				$dates[] = $this->project_data['date_preinspection_end'];
			}
			
			if (format_date($this->project_data['date_city_inspection_start']) != '')
			{
				$this->project_data['date_city_inspection_end'] = (format_date($this->project_data['date_city_inspection_end']) != '') ? $this->project_data['date_city_inspection_end'] : $this->project_data['date_city_inspection_start'];

				$project[] = '["Engineer Inspection", 
					"'.$this->project_data['city_engineer'].': '.format_date($this->project_data['date_city_inspection_start'], 'n/j/y').' - '.format_date($this->project_data['date_city_inspection_end'], 'n/j/y').'", 
					"#2a56c6", 
					new Date('.$this->formatDate($this->project_data['date_city_inspection_start']).'), 
					new Date('.$this->formatDate($this->project_data['date_city_inspection_end']).')]';

				$dates[] = $this->project_data['date_city_inspection_start'];
				$dates[] = $this->project_data['date_city_inspection_end'];
			}
		}

		if (!empty($this->vendors_data))
		{
			foreach($this->vendors_data as $vendor)
			{
				$incompleted_job = false;

				if (format_date($vendor['date_bid']) != '' && format_date($vendor['date_start_job']) != '' && format_date($vendor['date_end_job']) != '')
				{
					if (format_date($vendor['date_bid']) != '' && format_date($vendor['date_start_job']) != '' && format_date($vendor['date_end_job']) != '')
					{
						$vendors[] = '["'.$vendor['vendor_name'].'", 
						"'.format_date($vendor['date_bid'], 'n/j/y').' - '.format_date($vendor['date_start_job'], 'n/j/y').'", "#ffbc00",
						new Date('.$this->formatDate($vendor['date_bid']).'), 
						new Date('.$this->formatDate($vendor['date_start_job']).')]';

						$vendors[] = '["'.$vendor['vendor_name'].'", 
						"'.format_date($vendor['date_start_job'], 'n/j/y').' - '.format_date($vendor['date_end_job'], 'n/j/y').'", "#0b8043",
						new Date('.$this->formatDate($vendor['date_start_job']).'), 
						new Date('.$this->formatDate($vendor['date_end_job']).')]';
					}
					else if (format_date($vendor['date_bid']) != '' && format_date($vendor['date_start_job']) != '' && format_date($vendor['date_end_job']) == '')
					{
						$vendors[] = '["'.$vendor['vendor_name'].'", 
						"'.format_date($vendor['date_bid'], 'n/j/y').' - '.format_date($vendor['date_start_job'], 'n/j/y').'", "#ffbc00",
						new Date('.$this->formatDate($vendor['date_bid']).'), 
						new Date('.$this->formatDate($vendor['date_start_job']).')]';

						if (compare_dates($date_today, $vendor['date_start_job'], 1))
							$vendors[] = '["'.$vendor['vendor_name'].'", 
							"'.format_date($vendor['date_start_job'], 'n/j/y').' - '.format_date($date_today, 'n/j/y').'", "#0b8043",
							new Date('.$this->formatDate($vendor['date_start_job']).'), 
							new Date('.$this->formatDate($date_today).')]';
					}
					else if (format_date($vendor['date_bid']) != '' && format_date($vendor['date_start_job']) == '' && format_date($vendor['date_end_job']) == '')
					{
						if (compare_dates($date_today, $vendor['date_bid'], 1))
							$vendors[] = '["'.$vendor['vendor_name'].'", 
							"'.format_date($vendor['date_bid'], 'n/j/y').' - '.format_date($date_today, 'n/j/y').'", "#ffbc00",
							new Date('.$this->formatDate($vendor['date_bid']).'), 
							new Date('.$this->formatDate($date_today).')]';
					}
				}

				else if (format_date($vendor['date_bid']) != '' && format_date($vendor['date_start_job']) != '' && format_date($vendor['date_end_job']) == '')
				{
					$date_end_job = (compare_dates(date('Y-m-d'), $vendor['date_start_job'], 1)) ? date('Y-m-d') : $vendor['date_start_job'];

					$vendors[] = '["'.$vendor['vendor_name'].'", 
						"'.format_date($vendor['date_bid'], 'n/j/y').' - '.format_date($vendor['date_start_job'], 'n/j/y').'", "#ffbc00",
						new Date('.$this->formatDate($vendor['date_bid']).'), 
						new Date('.$this->formatDate($vendor['date_start_job']).')]';

					$vendors[] = '["'.$vendor['vendor_name'].'", 
						"'.format_date($vendor['date_start_job'], 'n/j/y').' - '.format_date($date_end_job, 'n/j/y').'", "#0b8043",
						new Date('.$this->formatDate($vendor['date_start_job']).'), 
						new Date('.$this->formatDate($date_end_job).')]';
				}

				else if (format_date($vendor['date_bid']) != '' && format_date($vendor['date_start_job']) == '' && format_date($vendor['date_end_job']) == '')
				{
					$date_end_job = (compare_dates(date('Y-m-d'), $vendor['date_bid'], 1)) ? date('Y-m-d') : $vendor['date_bid'];

					$vendors[] = '["'.$vendor['vendor_name'].'", 
						"'.format_date($vendor['date_bid'], 'n/j/y').' - '.format_date($date_end_job, 'n/j/y').'", "#ffbc00",
						new Date('.$this->formatDate($vendor['date_bid']).'), 
						new Date('.$this->formatDate($date_end_job).')]';
				}

				$dates[] = $vendor['date_bid'];
				$dates[] = $vendor['date_start_job'];
				$dates[] = $vendor['date_end_job'];
			}
		}


		$output[] = '["'.swift_escape($this->project_data['pro_name']).'", 
		"'.($this->project_data['unit_number'] != '' ? $this->project_data['unit_number'] : '').'",
		"#fff", 
		new Date('.$this->formatDate($this->getMinDate($dates)).'), 
		new Date('.$this->formatDate($this->getMaxDate($dates)).')]';

		$output[] = implode(','."\n", $project);
		$output[] = implode(','."\n", $vendors);

		return implode(','."\n", $output);
	}

	function genCalendarFollowUp()
	{
		$output = [];
		if (!empty($this->events_data))
		{
			$output[] = '[new Date(2022, 0, 1), 0, ""]';
			foreach($this->events_data as $data)
			{
				if (format_date($data['date_time']) != '')
				{
					$message = format_date($data['date_time'], 'F, j').': '.swift_escape($data['message']);
					$output[] = '[new Date('.$this->formatDate($data['date_time']).'), '.$data['id'].', "'.$message.'"]';
				}	
			}
		}
		return implode(','."\n", $output);
	}

	function getDiffNumber(DateInterval $interval)
	{
		return $interval->format("%a");//d
	}

	function getDateDiff($start, $end)
	{
		if ($start != '1000-01-01' && $end != '1000-01-01')
		{
			$first_date = new DateTime($start);
			$second_date = new DateTime($end);
			$difference = $first_date->diff($second_date);

			return $this->getDiffNumber($difference);
		}
		else
			return 0;
	}

	function formatDate($date)
	{
		$dt = new DateTime($date);
		$y = $dt->format('Y');
		$m = $dt->format('n') - 1;
		$d = $dt->format('j');
		return $y.', '.$m.', '.$d;
	}

	function getMinDate($array){
		$new_data = [];
		if (!empty($array)){
			foreach($array as $date){
				if (format_date($date) != '')
					$new_data[] = $date;
			}
		}
		return min($new_data);
	}
	function getMaxDate($array){
		$new_data = [];
		if (!empty($array)){
			foreach($array as $date){
				if (format_date($date) != '')
					$new_data[] = $date;
			}
		}
		return max($new_data);
	}
}
