<?php

class HcaHVACSummaryReport
{
	var $search_by_year = 12;

	var $found_property_ids = [];
	var $found_units_ids = [];

	var $num_pending_inspections = [];
	var $num_pending_wo = [];
	var $num_never_inspected = []; // key = property_id, value = number
	var $date_last_inspected = [];

	var $total_pending_inspections = 0;
	var $total_pending_wo = 0;
	var $total_never_inspected = 0;

	function __construct()
	{
		$this->search_by_year = isset($_GET['year']) ? intval($_GET['year']) : 12;
	}

	function genSummaryData()
	{
		global $DBLayer;

		$DateTime = new DateTime();

		$search_query = [];

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

		$query = [
			'SELECT'	=> 'ch.*',
			'FROM'		=> 'hca_hvac_inspections_checklist AS ch',
			'ORDER BY'	=> 'ch.datetime_inspection_start'
		];
		if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while($row = $DBLayer->fetch_assoc($result))
		{
			if ($row['inspection_completed'] < 2 && $row['work_order_completed'] < 2)
			{
				if (isset($this->num_pending_inspections[$row['property_id']]))
					++$this->num_pending_inspections[$row['property_id']];
				else
					$this->num_pending_inspections[$row['property_id']] = 1;

				++$this->total_pending_inspections;
			}

			if ($row['inspection_completed'] == 2 && $row['work_order_completed'] == 1)
			{
				if (isset($this->num_pending_wo[$row['property_id']]))
					++$this->num_pending_wo[$row['property_id']];
				else
					$this->num_pending_wo[$row['property_id']] = 1;

				++$this->total_pending_wo;
			}

			$this->found_property_ids[$row['property_id']] = $row['property_id'];
			$this->found_units_ids[$row['unit_id']] = $row['unit_id'];

			if (strtotime($row['datetime_inspection_start']) > 0)
				$this->date_last_inspected[$row['property_id']] = $row['datetime_inspection_start'];
		}

		// Gen list of Never Inspected Units
		if (!empty($this->found_property_ids))
		{
			$query = array(
				'SELECT'	=> 'un.id, un.unit_number, un.property_id',
				'FROM'		=> 'sm_property_units AS un',
				'WHERE'		=> 'un.property_id IN ('.implode(',', $this->found_property_ids).')',
			);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			while ($row = $DBLayer->fetch_assoc($result))
			{
				if (!in_array($row['id'], $this->found_units_ids))
				{
					if (isset($this->num_never_inspected[$row['property_id']]))
						++$this->num_never_inspected[$row['property_id']];
					else
						$this->num_never_inspected[$row['property_id']] = 1;
	
					++$this->total_never_inspected;
				}	
			}
		}

	}
}
