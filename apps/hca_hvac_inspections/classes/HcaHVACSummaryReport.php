<?php

class HcaHVACSummaryReport
{
	var $search_query = [];

	var $search_by_year = 0;

	var $hca_ui_checklist_items = [];
	var $hca_ui_checklist = [];
	var $hca_ui_checklist_ids = [];
	// Found units for Never Inspected Units
	var $found_units_ids = [];

	var $inspections_pending = [];
	var $inspections_completed = [];

	var $items_pending = [];
	var $items_replaced = [];
	var $items_repaired = [];
	// key = property_id, value = number
	var $units_never_inspected = [];

	var $total_inspections_pending = 0;
	var $total_inspections_completed = 0;

	var $total_pending = 0;
	var $total_replaced = 0;
	var $total_repaired = 0;
	var $total_units_never_inspected = 0;

	var $date_last_inspected = [];

	function __construct()
	{
		global $DBLayer;

		$this->search_by_year = isset($_GET['year']) ? intval($_GET['year']) : 12;

		$search_query = [];
		$search_query[] = 'i.summary_report=1';

		$DateTime = new DateTime();
		if ($this->search_by_year > 2020)
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
			'SELECT'	=> 'ci.job_type, ci.checklist_id, ch.property_id, ch.unit_id',
			'FROM'		=> 'hca_hvac_inspections_checklist_items AS ci',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'hca_hvac_inspections_checklist AS ch',
					'ON'			=> 'ch.id=ci.checklist_id'
				],
				[
					'INNER JOIN'	=> 'hca_ui_items AS i',
					'ON'			=> 'i.id=ci.item_id'
				],
			],
			'ORDER BY'	=> 'i.display_position'
		];
		$query['WHERE'] = implode(' AND ', $search_query);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while($row = $DBLayer->fetch_assoc($result))
		{
			$this->hca_ui_checklist_items[] = $row;
		}

		foreach($this->hca_ui_checklist_items as $row)
		{
			if ($row['job_type'] == 0)
			{
				if (isset($this->items_pending[$row['property_id']]))
					++$this->items_pending[$row['property_id']];
				else
					$this->items_pending[$row['property_id']] = 1;

				++$this->total_pending;
			}

			if ($row['job_type'] == 1)
			{
				if (isset($this->items_replaced[$row['property_id']]))
					++$this->items_replaced[$row['property_id']];
				else
					$this->items_replaced[$row['property_id']] = 1;

				++$this->total_replaced;
			}

			if ($row['job_type'] == 2)
			{
				if (isset($this->items_repaired[$row['property_id']]))
					++$this->items_repaired[$row['property_id']];
				else
					$this->items_repaired[$row['property_id']] = 1;

				++$this->total_repaired;
			}

			$this->hca_ui_checklist_ids[$row['checklist_id']] = $row['checklist_id'];
		}

		$search_query = [];
		if ($this->search_by_year > 2020)
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
			'ORDER BY'	=> 'ch.inspection_completed'
		];
		if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while($row = $DBLayer->fetch_assoc($result))
		{
			if ($row['inspection_completed'] == 2)
			{
				if (isset($this->inspections_completed[$row['property_id']]))
					++$this->inspections_completed[$row['property_id']];
				else
					$this->inspections_completed[$row['property_id']] = 1;

				++$this->total_inspections_completed;
			}
			else
			{
				if (isset($this->inspections_pending[$row['property_id']]))
					++$this->inspections_pending[$row['property_id']];
				else
					$this->inspections_pending[$row['property_id']] = 1;

				++$this->total_inspections_pending;
			}

			// search any created inspections
			$this->found_units_ids[$row['unit_id']] = $row['unit_id'];

			if (strtotime($row['datetime_inspection_start']) > 0)
				$this->date_last_inspected[$row['property_id']] = $row['datetime_inspection_start'];
		}

		$query = array(
			'SELECT'	=> 'un.id, un.unit_number, un.property_id',
			'FROM'		=> 'sm_property_units AS un',
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result))
		{
			if (!in_array($row['id'], $this->found_units_ids))
			{
				if (isset($this->units_never_inspected[$row['property_id']]))
					++$this->units_never_inspected[$row['property_id']];
				else
					$this->units_never_inspected[$row['property_id']] = 1;

				//++$this->total_units_never_inspected;
			}	
		}
	}
}
