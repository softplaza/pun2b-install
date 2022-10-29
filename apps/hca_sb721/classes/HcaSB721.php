<?php

class HcaSB721
{
	function checkProjectStatus()
	{
		global $DBLayer, $FlashMessenger;
	
		$query = [
			'SELECT'	=> 'v.*',
			'FROM'		=> 'hca_sb721_vendors AS v',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'hca_sb721_projects AS p',
					'ON'			=> 'p.id=v.project_id'
				],
			],
			'WHERE'		=> 'p.project_status=1'
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$ids = [];
		while ($row = $DBLayer->fetch_assoc($result))
		{
			if (compare_dates(date('Y-m-d'), $row['date_end_job'], 1))
				$ids[] = $row['project_id'];
		}
	
		if (!empty($ids))
		{
			$query = [
				'UPDATE'	=> 'hca_sb721_projects',
				'SET'		=> 'project_status=5',
				'WHERE'		=> 'id IN('.implode(',', $ids).')'
			];
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
			$flash_message = 'Projects have been marked as completed';
			$FlashMessenger->add_info($flash_message);
		}
	}
}
