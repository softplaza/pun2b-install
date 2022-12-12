<?php

class HcaHVACPropertyReport
{
	var $pending_inspections_info = [];
	var $pending_work_orders_info = [];
	var $completed_work_orders_info = [];
	var $replaced_filters = [];

	var $inspections_dates = [];

	var $found_units_ids = [];
	var $never_ispected_units = [];
	var $num_never_inspected = 0;

	var $num_pending_inspections = 0;
	var $num_pending_work_orders = 0;
 	var $num_completed_work_orders = 0;

	var $hca_hvac_inspections_checklist_items = [];
	var $num_filters_replaced = 0;
	var $total_items_pending = 0;

	function __construct()
	{
		$this->search_by_year = isset($_GET['year']) ? intval($_GET['year']) : 12;
		$this->search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
		$this->search_by_job_type = isset($_GET['job_type']) ? intval($_GET['job_type']) : 0;
		$this->search_by_datetime_inspection_start  = isset($_GET['datetime_inspection_start']) ? swift_trim($_GET['datetime_inspection_start']) : '';
	}

	function genChecklistData()
	{
		global $DBLayer;

		$DateTime = new DateTime();

		$search_query = [];

		if ($this->search_by_property_id > 0)
			$search_query[] = 'ch.property_id='.$this->search_by_property_id;

		if ($this->search_by_year == 0) // Today
			$search_query[] = 'DATE(ch.datetime_inspection_start)=\''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		else if ($this->search_by_year > 2020)
			$search_query[] = 'YEAR(ch.datetime_inspection_start)=\''.$DBLayer->escape($this->search_by_year).'\'';
		else if ($this->search_by_year == 1)
		{
			$DateTime->modify('-1 month');
			$search_query[] = 'DATE(ch.datetime_inspection_start) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}
		else if ($this->search_by_year == 3)
		{
			$DateTime->modify('-3 months');
			$search_query[] = 'DATE(ch.datetime_inspection_start) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}
		else if ($this->search_by_year == 6)
		{
			$DateTime->modify('-6 months');
			$search_query[] = 'DATE(ch.datetime_inspection_start) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}
		else
		{
			$DateTime->modify('-1 year');
			$search_query[] = 'DATE(ch.datetime_inspection_start) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}

		if ($this->search_by_datetime_inspection_start != '')
			$search_query[] = 'DATE(ch.datetime_inspection_start)=\''.$DBLayer->escape($this->search_by_datetime_inspection_start).'\'';

		$query = [
			'SELECT'	=> 'ch.*, p.pro_name, un.unit_number, u1.realname AS inspected_name, f.filter_size',
			'FROM'		=> 'hca_hvac_inspections_checklist AS ch',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'sm_property_db AS p',
					'ON'			=> 'p.id=ch.property_id'
				],
				[
					'INNER JOIN'	=> 'sm_property_units AS un',
					'ON'			=> 'un.id=ch.unit_id'
				],
				[
					'INNER JOIN'	=> 'users AS u1',
					'ON'			=> 'u1.id=ch.inspected_by'
				],
				[
					'LEFT JOIN'		=> 'hca_hvac_inspections_filters AS f',
					'ON'			=> 'f.id=ch.filter_size_id'
				],
			],
			'ORDER BY'	=> 'ch.datetime_inspection_start'
		];
		if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while($row = $DBLayer->fetch_assoc($result))
		{
			/*
			if ($row['filter_size_id'] > 0)
			{
				if (isset($this->replaced_filters[$row['filter_size_id']]))
					++$this->replaced_filters[$row['filter_size_id']];
				else
					$this->replaced_filters[$row['filter_size_id']] = 1;

				++$this->num_filters_replaced;
			}
*/

			if ($row['inspection_completed'] < 2 && $row['work_order_completed'] < 2)
			{
				$this->pending_inspections_info[] = $row;
				++$this->num_pending_inspections;
			}

			if ($row['inspection_completed'] == 2 && $row['work_order_completed'] == 1)
			{
				$this->pending_work_orders_info[] = $row;
				++$this->num_pending_work_orders;
			}

			if ($row['work_order_completed'] == 2)
			{
				$this->completed_work_orders_info[] = $row;
				++$this->num_completed_work_orders;
			}	

			$this->found_units_ids[$row['unit_id']] = $row['unit_id'];

			if (strtotime($row['datetime_inspection_start']) > 0)
			{
				$datetime_inspection_start = format_date($row['datetime_inspection_start'], 'Y-m-d');
				$this->inspections_dates[$datetime_inspection_start] = $datetime_inspection_start;
			}
		}
	}

	function genNeverInspected()
	{
		global $DBLayer, $URL;

		$query = array(
			'SELECT'	=> 'un.id, un.unit_number, un.property_id',
			'FROM'		=> 'sm_property_units AS un',
			'WHERE'		=> 'un.property_id='.$this->search_by_property_id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result))
		{
			if (!in_array($row['id'], $this->found_units_ids))
			{
				$this->never_ispected_units[] = '<a href="'.$URL->genLink('hca_hvac_inspections_inspections', ['property_id' => $row['property_id'], 'unit_number' => $row['unit_number']]).'" target="_blank" class="badge bg-primary me-1 text-white">'.$row['unit_number'].'</a>';

				++$this->num_never_inspected;
			}
				
		}
	}

	// Get list of insected Filters
	function genFiltersData()
	{
		global $DBLayer, $URL;

		$DateTime = new DateTime();

		$search_query = [];
		$search_query[] = 'ci.item_id=10';
		$search_query[] = 'ch.filter_size_id > 0';

		if ($this->search_by_year == 0)
			$search_query[] = 'DATE(ch.datetime_inspection_start)=\''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		else if ($this->search_by_year > 2020)
			$search_query[] = 'YEAR(ch.datetime_inspection_start)=\''.$DBLayer->escape($this->search_by_year).'\'';
		else if ($this->search_by_year == 1)
		{
			$DateTime->modify('-1 month');
			$search_query[] = 'DATE(ch.datetime_inspection_start) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}
		else if ($this->search_by_year == 3)
		{
			$DateTime->modify('-3 months');
			$search_query[] = 'DATE(ch.datetime_inspection_start) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}
		else if ($this->search_by_year == 6)
		{
			$DateTime->modify('-6 months');
			$search_query[] = 'DATE(ch.datetime_inspection_start) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}
		else
		{
			$DateTime->modify('-1 year');
			$search_query[] = 'DATE(ch.datetime_inspection_start) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}

		if ($this->search_by_datetime_inspection_start != '')
			$search_query[] = 'DATE(ch.datetime_inspection_start)=\''.$DBLayer->escape($this->search_by_datetime_inspection_start).'\'';

		if ($this->search_by_property_id > 0)
			$search_query[] = 'ch.property_id='.$this->search_by_property_id;

		$query = [
			'SELECT'	=> 'ci.*, f.filter_size',
			'FROM'		=> 'hca_hvac_inspections_checklist_items AS ci',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'hca_hvac_inspections_checklist AS ch',
					'ON'			=> 'ch.id=ci.checklist_id'
				],
				[
					'INNER JOIN'	=> 'hca_hvac_inspections_items AS i',
					'ON'			=> 'i.id=ci.item_id'
				],
				[
					'INNER JOIN'	=> 'hca_hvac_inspections_filters AS f',
					'ON'			=> 'f.id=ch.filter_size_id'
				],
			],
			//'ORDER BY'	=> 'i.equipment_id, i.display_position'
		];
		$query['WHERE'] = implode(' AND ', $search_query);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while($row = $DBLayer->fetch_assoc($result))
		{
			if ($row['check_type'] == 2 && $this->search_by_job_type == 1) // 2 - replaced
			{
				if (isset($this->replaced_filters[$row['filter_size']]))
					++$this->replaced_filters[$row['filter_size']];
				else
					$this->replaced_filters[$row['filter_size']] = 1;

				++$this->num_filters_replaced;
			}
			else if ($row['check_type'] == 1 && $this->search_by_job_type == 0) // 1 - NOT replaced
			{
				if (isset($this->replaced_filters[$row['filter_size']]))
					++$this->replaced_filters[$row['filter_size']];
				else
					$this->replaced_filters[$row['filter_size']] = 1;

				++$this->num_filters_replaced;

				$this->hca_hvac_inspections_checklist_items[] = $row['filter_size'];
			}
		}

		return $this->hca_hvac_inspections_checklist_items;
	}

	function implodeInspectedDates()
	{
		global $URL;

		$checklist_date_links = [];
		if (!empty($this->inspections_dates))
		{
			ksort($this->inspections_dates);
			foreach($this->inspections_dates as $key => $value)
			{
				$sub_link_args = [
					'property_id'				=> $this->search_by_property_id,
					//'inspection_type'			=> $this->search_by_inspection_type,
					//'item_id'					=> $this->search_by_item_id,
					'job_type'					=> $this->search_by_job_type,
					'datetime_inspection_start'	=> $key
				];
	
				$checklist_date_links[] = '<a href="'.$URL->genLink('hca_hvac_inspections_property_report', $sub_link_args).'" class="btn btn-outline-primary" role="button">'.$key.'</a>';
			}
		}

		return implode("\n", $checklist_date_links);
	}

}
