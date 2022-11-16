<?php

class HcaUIPropertyReport
{
	var $search_query = [];

	var $search_by_inspection_type = 0;
	var $search_by_property_id = 0;
	var $search_by_item_id  = 0;
	var $search_by_date_inspected = '';
	var $search_by_job_type = 0;
	var $search_by_year = 0;

	var $hca_ui_checklist_items = [];
	var $hca_ui_checklist_dates = [];
	var $hca_ui_items = [];
	var $hca_ui_checklist_ids = [];
	var $hca_ui_checklist = [];
	var $work_orders_info = [];
	var $inspections_info = [];
	var $unispected_units = [];

	var $has_mbath = false;
	var $has_hbath = false;

	var $num_items_pending = [];
	var $num_never_inspected = 0;

	function __construct()
	{
		$this->search_by_inspection_type = isset($_GET['inspection_type']) ? intval($_GET['inspection_type']) : 0;
		$this->search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
		$this->search_by_item_id  = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
		$this->search_by_date_inspected  = isset($_GET['date_inspected']) ? swift_trim($_GET['date_inspected']) : '';
		$this->search_by_job_type = isset($_GET['job_type']) ? intval($_GET['job_type']) : 0;
		$this->search_by_year = isset($_GET['year']) ? intval($_GET['year']) : 12;
	}

	function genItemsData()
	{
		global $DBLayer;

		$this->search_query[] = 'i.summary_report=1';
		if ($this->search_by_property_id > 0)
			$this->search_query[] = 'ch.property_id='.$this->search_by_property_id;

		if ($this->search_by_inspection_type == 1)
			$this->search_query[] = 'ch.type_audit=1';
		if ($this->search_by_inspection_type == 2)
			$this->search_query[] = 'ch.type_flapper=1';

		if ($this->search_by_item_id > 0)
			$this->search_query[] = 'ci.item_id='.$this->search_by_item_id;
		if ($this->search_by_date_inspected != '')
			$this->search_query[]  = 'DATE(ch.date_inspected)=\''.$DBLayer->escape($this->search_by_date_inspected).'\'';

		if ($this->search_by_year > 2020)
			$this->search_query[] = 'YEAR(ch.date_inspected)=\''.$DBLayer->escape($this->search_by_year).'\'';
		else if ($this->search_by_year == 1)
		{
			$DateTime = new DateTime();
			$DateTime->modify('-1 month');
			$this->search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}
		else if ($this->search_by_year == 3)
		{
			$DateTime = new DateTime();
			$DateTime->modify('-3 months');
			$this->search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}
		else if ($this->search_by_year == 6)
		{
			$DateTime = new DateTime();
			$DateTime->modify('-6 months');
			$this->search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}
		else
		{
			$DateTime = new DateTime();
			$DateTime->modify('-1 year');
			$this->search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}

		if ($this->search_by_job_type == 0)
			$this->search_query[] = 'ci.job_type=0';
		else if ($this->search_by_job_type == 1)
			$this->search_query[] = 'ci.job_type > 0';//???

		$query = [
			'SELECT'	=> 'ci.*, ch.*, i.*, p.pro_name, un.unit_number, un.mbath, un.hbath',
			'FROM'		=> 'hca_ui_checklist_items AS ci',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'hca_ui_checklist AS ch',
					'ON'			=> 'ch.id=ci.checklist_id'
				],
				[
					'INNER JOIN'	=> 'hca_ui_items AS i',
					'ON'			=> 'i.id=ci.item_id'
				],
				[
					'INNER JOIN'	=> 'sm_property_db AS p',
					'ON'			=> 'p.id=ch.property_id'
				],
				[
					'INNER JOIN'	=> 'sm_property_units AS un',
					'ON'			=> 'un.id=ch.unit_id'
				],
			],
			'ORDER BY'	=> 'i.display_position'
		];
		$query['WHERE'] = implode(' AND ', $this->search_query);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

		while($row = $DBLayer->fetch_assoc($result))
		{
			if (!$this->has_mbath && $row['mbath'] == 1)
				$this->has_mbath = true;
	
			if (!$this->has_hbath && $row['hbath'] == 1)
				$this->has_hbath = true;

			$this->hca_ui_checklist_items[] = $row;

			$this->hca_ui_checklist_dates[$row['date_inspected']] = $row['checklist_id'];

			$this->hca_ui_checklist_ids[$row['checklist_id']] = $row['checklist_id'];

			$this->hca_ui_items[$row['item_id']] = $row;


			if (isset($this->num_items_pending[$row['item_id']]))
				++$this->num_items_pending[$row['item_id']];
			else
				$this->num_items_pending[$row['item_id']] = 1;

		}
	}

	function implodeInspectedDates()
	{
		global $URL;

		$checklist_date_links = [];
		if (!empty($this->hca_ui_checklist_dates))
		{
			ksort($this->hca_ui_checklist_dates);
			foreach($this->hca_ui_checklist_dates as $key => $value)
			{
				$sub_link_args = [
					'property_id'		=> $this->search_by_property_id,
					'inspection_type'	=> $this->search_by_inspection_type,
					'item_id'			=> $this->search_by_item_id,
					'job_type'			=> $this->search_by_job_type,
					'date_inspected'	=> $key
				];
	
				$checklist_date_links[] = '<a href="'.$URL->genLink('hca_ui_property_report', $sub_link_args).'" class="btn btn-outline-primary" role="button">'.$key.'</a>';
			}
		}

		return implode("\n", $checklist_date_links);
	}

	function getChecklistData()
	{
		global $DBLayer;

		$search_query = [];
		if ($this->search_by_property_id > 0)
			$search_query[] = 'ch.property_id='.$this->search_by_property_id;

		if ($this->search_by_inspection_type == 1)
			$search_query[] = 'ch.type_audit=1';
		if ($this->search_by_inspection_type == 2)
			$search_query[] = 'ch.type_flapper=1';

		if ($this->search_by_date_inspected != '')
			$search_query[]  = 'DATE(ch.date_inspected)=\''.$DBLayer->escape($this->search_by_date_inspected).'\'';

		if ($this->search_by_year > 2020)
			$search_query[] = 'YEAR(ch.date_inspected)=\''.$DBLayer->escape($this->search_by_year).'\'';
		else if ($this->search_by_year == 1)
		{
			$DateTime = new DateTime();
			$DateTime->modify('-1 month');
			$this->search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}
		else if ($this->search_by_year == 3)
		{
			$DateTime = new DateTime();
			$DateTime->modify('-3 months');
			$search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}
		else if ($this->search_by_year == 6)
		{
			$DateTime = new DateTime();
			$DateTime->modify('-6 months');
			$search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}
		else
		{
			$DateTime = new DateTime();
			$DateTime->modify('-1 year');
			$search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}

		// Move down
		//if ($this->search_by_job_type == 1) // completed
		//	$search_query[] = 'ch.inspection_completed=2 AND ch.work_order_completed=2';
		//else // pending
		//	$search_query[] = 'ch.inspection_completed=2 AND ch.work_order_completed=1 AND ch.num_problem > 0';

		$query = [
			'SELECT'	=> 'ch.*, p.pro_name, un.unit_number, u1.realname AS owner_name, u2.realname AS inspected_name, u3.realname AS completed_name, u4.realname AS updated_name, u5.realname AS started_name',
			'FROM'		=> 'hca_ui_checklist as ch',
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
					'LEFT JOIN'		=> 'users AS u1',
					'ON'			=> 'u1.id=ch.owned_by'
				],
				[
					'LEFT JOIN'		=> 'users AS u2',
					'ON'			=> 'u2.id=ch.inspected_by'
				],
				[
					'LEFT JOIN'		=> 'users AS u3',
					'ON'			=> 'u3.id=ch.completed_by'
				],
				[
					'LEFT JOIN'		=> 'users AS u4',
					'ON'			=> 'u4.id=ch.updated_by'
				],
				[
					'LEFT JOIN'		=> 'users AS u5',
					'ON'			=> 'u5.id=ch.started_by'
				],
			],
			//'WHERE'		=> 'ch.id IN ('.implode(',', $this->hca_ui_checklist_ids).')',
			'ORDER BY'	=> 'ch.inspection_completed, ch.work_order_completed, p.pro_name, LENGTH(un.unit_number), un.unit_number',
		];
		if (!empty($search_query) )$query['WHERE'] = implode(' AND ', $search_query);


		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result))
		{
			$this->hca_ui_checklist[] = $row;

			// Get completed WORK ORDERS
			if ($this->search_by_job_type == 1)
			{
				if ($row['inspection_completed'] == 2 && $row['work_order_completed'] == 2)
					$this->work_orders_info[] = $row;
			}
			// Get pending WO & Inspections
			else
			{
				if ($row['inspection_completed'] == 2 && $row['work_order_completed'] == 1 && $row['num_problem'] > 0)
					$this->work_orders_info[] = $row;

				if ($row['inspection_completed'] == 1)
					$this->inspections_info[] = $row;
			}
		}
	}

	function getNeverInspectedUnits()
	{
		global $DBLayer;

		$unispected_units_checklist = [];

		// Add by period
		$search_query = [];
		if ($this->search_by_property_id > 0)
			$search_query[] = 'ch.property_id='.$this->search_by_property_id;

		$DateTime = new DateTime();
		if ($this->search_by_year > 2020)
			$search_query[] = 'YEAR(ch.date_inspected)=\''.$DBLayer->escape($this->search_by_year).'\'';
		else if ($this->search_by_year == 1)
		{
			$DateTime->modify('-1 month');
			$search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}
		else if ($this->search_by_year == 3)
		{
			$DateTime->modify('-3 months');
			$search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}
		else if ($this->search_by_year == 6)
		{
			$DateTime->modify('-6 months');
			$search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}
		else
		{
			$DateTime->modify('-1 year');
			$search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
		}

		$query = array(
			'SELECT'	=> 'ch.unit_id, ch.inspection_completed',
			'FROM'		=> 'hca_ui_checklist AS ch',
		);
		if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result))
		{
			// if found any created inspections
			$unispected_units_checklist[$row['unit_id']] = $row['unit_id'];
		}

		$query = array(
			'SELECT'	=> 'un.id, un.unit_number',
			'FROM'		=> 'sm_property_units AS un',
			'WHERE'		=> 'un.property_id='.$this->search_by_property_id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result))
		{
			if (!in_array($row['id'], $unispected_units_checklist))
			{
				$this->unispected_units[] = $row['unit_number'];
				++$this->num_never_inspected;
			}	
		}
	}
}
