<?php

class HcaUISummaryReport
{
	public $search_query = [];
	public $hca_ui_checklist_items = [];

	public $has_mbath = false;
	public $has_hbath = false;

	public $items_total = [];
	public $items_pending = [];
	public $items_replaced = [];

	public $work_orders_report = [];

	function getCheckedItems()
	{
		global $DBLayer;

		$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
		$search_by_item_id  = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
		$search_by_date_inspected  = isset($_GET['date_inspected']) ? swift_trim($_GET['date_inspected']) : '';
		$search_by_job_type = isset($_GET['job_type']) ? intval($_GET['job_type']) : 0;
		$search_by_year = isset($_GET['year']) ? intval($_GET['year']) : 0;

		$this->search_query[] = 'i.summary_report=1';
		if ($search_by_job_type == 0) {
			$this->search_query[] = 'ch.inspection_completed=2';
			$this->search_query[] = 'ch.work_order_completed=1';
		}

		if ($search_by_property_id > 0)
			$this->search_query[] = 'ch.property_id='.$search_by_property_id;
		if ($search_by_job_type > 0)
			$this->search_query[] = 'ci.job_type='.$search_by_job_type;
		if ($search_by_item_id > 0)
			$this->search_query[] = 'ci.item_id='.$search_by_item_id;
		if ($search_by_date_inspected != '')
			$this->search_query[]  = 'DATE(ch.date_inspected)=\''.$DBLayer->escape($search_by_date_inspected).'\'';
		if ($search_by_year > 0)
			$this->search_query[] = 'YEAR(ch.date_inspected)=\''.$DBLayer->escape($search_by_year).'\'';

		if ($search_by_job_type == 0)
			$this->search_query[] = '(ci.job_type=0 OR ci.job_type=4)';
		else if ($search_by_job_type == 1)
			$this->search_query[] = 'ci.job_type=1';

		$query = [
			'SELECT'	=> 'ci.item_id, ci.job_type, ci.checklist_id, ch.property_id, ch.num_problem, ch.num_pending, ch.num_replaced, ch.num_repaired, ch.num_reset, ch.inspection_completed, ch.work_order_completed, ch.work_order_comment, i.element_id, p.pro_name, un.unit_number, un.mbath, un.hbath, u1.realname AS owner_name',
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
				[
					'LEFT JOIN'		=> 'users AS u1',
					'ON'			=> 'u1.id=ch.owned_by'
				],
			],
			'ORDER BY'	=> 'i.display_position'
		];
		if (!empty($this->search_query))
		{
			$query['WHERE'] = implode(' AND ', $this->search_query);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			while($row = $DBLayer->fetch_assoc($result))
			{
				$this->hca_ui_checklist_items[] = $row;
			}
		}

		if (!empty($this->search_query)) 
		{
			if (!empty($this->hca_ui_checklist_items))
			{
				foreach($this->hca_ui_checklist_items as $cur_info)
				{
					if (isset($this->items_total[$cur_info['item_id']]))
						++$this->items_total[$cur_info['item_id']];
					else
						$this->items_total[$cur_info['item_id']] = 1;
			
					if (!$this->has_mbath && $cur_info['mbath'] == 1)
						$this->has_mbath = true;
			
					if (!$this->has_hbath && $cur_info['hbath'] == 1)
						$this->has_hbath = true;
			
					//$hca_ui_checklist_ids[$row['checklist_id']] = $row['checklist_id'];
				}
			}
		}
	}

	function getReplaced($id){
		return isset($this->elements_replaced[$id]) ? $this->elements_replaced[$id] : 0;
	}

	function getPending($id){
		return isset($this->elements_pending[$id]) ? $this->elements_pending[$id] : 0;
	}

	function genWorkOrderReport()
	{
		if (!empty($this->hca_ui_checklist_items))
		{
			$num_pending = $num_replaced = [];
			foreach($this->hca_ui_checklist_items as $cur_info)
			{
				//if ($cur_info['num_problem'] > 0 && $cur_info['work_order_completed'] == 1)
				//{
				//	if (!isset($this->replaced_on_property[$cur_info['checklist_id']]))
				//	{
						if (isset($num_pending[$cur_info['checklist_id']]))
							++$num_pending[$cur_info['checklist_id']];
						else
							$num_pending[$cur_info['checklist_id']] = 1;

						$this->work_orders_report[$cur_info['checklist_id']] = [
							'checklist_id' => $cur_info['checklist_id'],
							'pro_name' => $cur_info['pro_name'],
							'unit_number' => $cur_info['unit_number'],
							'owner_name' => $cur_info['owner_name'],
							'inspection_completed' => $cur_info['inspection_completed'],
							'work_order_completed' => $cur_info['work_order_completed'],
							'work_order_comment' => $cur_info['work_order_comment'],
							'num_problem' => $cur_info['num_problem'],

							'num_pending' => $num_pending[$cur_info['checklist_id']],
							'num_replaced' => $num_pending[$cur_info['checklist_id']],
							'num_repaired' => $cur_info['num_repaired'],
							'num_reset' => $cur_info['num_reset'],

						];
					//}
				//}
			}

			$this->work_orders_report = array_msort($this->work_orders_report, ['unit_number' => SORT_ASC]);

			return $this->work_orders_report;
		}
	}
}
