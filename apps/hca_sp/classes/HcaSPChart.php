<?php

class HcaSPChart
{
	public $TimeLineProgressData = [];

	public $detailed_chart = [];

	function __construct(){}

	// Generate Horisontal Bar Chart
	function addTimeLineProgressData($data)
	{
		global $DBLayer;

		$data['pro_name'] = $data['property_name'] != '' ? $data['property_name'] : $data['pro_name'];
		$data['project_desc'] = $DBLayer->escape($data['project_desc']);

		$this->TimeLineProgressData[] = $data;
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
	
	// Timeline Stacked Chart
	function getTimeLineLinkedData()
	{
		global $URL, $DBLayer;
		
		$output = [];

		$property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
		$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
		$start = isset($_GET['start']) ? swift_trim($_GET['start']) : '';
		$end = isset($_GET['end']) ? swift_trim($_GET['end']) : '';
		$work_status = isset($_GET['work_status']) ? intval($_GET['work_status']) : 0;

		foreach($this->TimeLineProgressData as $id => $data)
		{
			//$data['project_desc'] = str_replace('"', '', $data['project_desc']);
			$project_title = $DBLayer->escape($data['pro_name'].' - '.$data['project_desc']);

			if ($data['date_bid_start'] != '1000-01-01' && $data['date_bid_end'] != '1000-01-01' && $data['date_bid_start'] != '0000-00-00' && $data['date_bid_end'] != '0000-00-00')
			{
				$output[] = '[{v: "'.$project_title.'", 
					p: {link: "'.$URL->link('sm_special_projects_chart', [$data['id'],$property_id,$user_id,$start,$end,$work_status]).'"}}, 
					"'.$this->getDateDiff($data['date_bid_start'], $data['date_bid_end']).' days", 
					"bid", 
					"#ffbc00", 
					new Date('.$this->formatDate($data['date_bid_start']).'), 
					new Date('.$this->formatDate($data['date_bid_end']).')]';
			}

			if ($data['date_contract_start'] != '1000-01-01' && $data['date_contract_end'] != '1000-01-01' && $data['date_contract_start'] != '0000-00-00' && $data['date_contract_end'] != '0000-00-00')
			{
				$output[] = '[{v: "'.$project_title.'", 
					p: {link: "'.$URL->link('sm_special_projects_chart', [$data['id'],$property_id,$user_id,$start,$end,$work_status]).'"}},  
					"'.$this->getDateDiff($data['date_contract_start'], $data['date_contract_end']).' days", 
					"contract", 
					"#2a56c6", 
					new Date('.$this->formatDate($data['date_contract_start']).'), 
					new Date('.$this->formatDate($data['date_contract_end']).')]';
			}

			if ($data['date_job_start'] != '1000-01-01' && $data['date_job_end'] != '1000-01-01' && $data['date_job_start'] != '0000-00-00' && $data['date_job_end'] != '0000-00-00')
			{
				$output[] = '[{v: "'.$project_title.'",
					p: {link: "'.$URL->link('sm_special_projects_chart', [$data['id'],$property_id,$user_id,$start,$end,$work_status]).'"}}, 
					"'.$this->getDateDiff($data['date_job_start'], $data['date_job_end']).' days", 
					"job", 
					"#0b8043", 
					new Date('.$this->formatDate($data['date_job_start']).'), 
					new Date('.$this->formatDate($data['date_job_end']).')]';
			}

		}
		return implode(','."\n", $output);
	}

	// Generate Detailed Single Gantt Chart
	function addDetailedData($data){
		$data['pro_name'] = $data['property_name'] != '' ? $data['property_name'] : $data['pro_name'];
		$data['project_desc'] = str_replace('"', '', $data['project_desc']);
		$this->detailed_chart = $data;
	}

	// Gantt Chart
	function getDetailedChart()
	{
		$output = [];
		$bid = $contract = $job = false;
		if(!empty($this->detailed_chart))
		{
			$start = $this->getFirstAction($this->detailed_chart);
			$end = $this->getLastAction($this->detailed_chart);

			$output[] = '["'.$this->detailed_chart['id'].'", 
				"'.$this->detailed_chart['pro_name'].' ('.$this->detailed_chart['project_desc'].')", 
				"Full Period", 
				new Date('.$this->formatDate($start).'), 
				new Date('.$this->formatDate($end).'), 
				null, 
				100, 
				null ]';

			if (format_date($this->detailed_chart['date_bid_start']) != '' && format_date($this->detailed_chart['date_bid_end']) != '')
			{
				$output[] = '["'.$this->detailed_chart['id'].'-1", 
				"Bid Phase", 
				"Bid Phase", 
				new Date('.$this->formatDate($this->detailed_chart['date_bid_start']).'), 
				new Date('.$this->formatDate($this->detailed_chart['date_bid_end']).'), 
				null, 
				100, 
				null ]';

				$bid = true;
			}


			if ($bid && format_date($this->detailed_chart['date_contract_start']) != '' && format_date($this->detailed_chart['date_contract_end']) != '')
			{
				$output[] = '["'.$this->detailed_chart['id'].'-2", 
				"Contract Phase", 
				"Contract Phase",    
				new Date('.$this->formatDate($this->detailed_chart['date_contract_start']).'), 
				new Date('.$this->formatDate($this->detailed_chart['date_contract_end']).'), 
				null, 
				100, 
				null ]';

				$contract = true;
			}


			if ($contract && format_date($this->detailed_chart['date_job_start']) != '' && format_date($this->detailed_chart['date_job_end']) != '')
				$output[] = '["'.$this->detailed_chart['id'].'-3", 
				"Job Phase", 
				"Job Phase", 
				new Date('.$this->formatDate($this->detailed_chart['date_job_start']).'), 
				new Date('.$this->formatDate($this->detailed_chart['date_job_end']).'), 
				null, 
				100, 
				null ]';
		}

		return implode(','."\n", $output);
	}

	function getFirstAction($data)
	{
		if (format_date($this->detailed_chart['date_bid_start']) != '')
			return $this->detailed_chart['date_bid_start'];
		else if (format_date($this->detailed_chart['date_bid_end']) != '')
			return $this->detailed_chart['date_bid_end'];
		else if (format_date($this->detailed_chart['date_contract_start']) != '')
			return $this->detailed_chart['date_contract_start'];
		else if (format_date($this->detailed_chart['date_contract_end']) != '')
			return $this->detailed_chart['date_contract_end'];
		else if (format_date($this->detailed_chart['date_job_start']) != '')
			return $this->detailed_chart['date_job_start'];
		else if (format_date($this->detailed_chart['date_job_end']) != '')
			return $this->detailed_chart['date_job_end'];
	}
	
	function getLastAction($data)
	{
		if (format_date($this->detailed_chart['date_job_end']) != '')
			return $this->detailed_chart['date_job_end'];
		else if (format_date($this->detailed_chart['date_job_start']) != '')
			return $this->detailed_chart['date_job_start'];
		else if (format_date($this->detailed_chart['date_contract_end']) != '')
			return $this->detailed_chart['date_contract_end'];
		else if (format_date($this->detailed_chart['date_contract_start']) != '')
			return $this->detailed_chart['date_contract_start'];
		else if (format_date($this->detailed_chart['date_bid_end']) != '')
			return $this->detailed_chart['date_bid_end'];
		else if (format_date($this->detailed_chart['date_bid_start']) != '')
			return $this->detailed_chart['date_bid_start'];
		//else if (format_date($this->detailed_chart['date_action_start']) != '')
		//	return $this->detailed_chart['date_action_start'];
	}
}
