<?php

class HcaCCChart
{
	public $hca_cc_items = [];

	public $num_expired = 0;
	public $num_upcoming = 0;
	public $num_completed = 0;

	function __construct()
	{
		global $DBLayer;

		$query = [
			'SELECT'	=> 'i.*',
			'FROM'		=> 'hca_cc_items AS i',
			'ORDER BY'	=> 'i.date_due',
		];
		//if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result))
		{
			$this->hca_cc_items[] = $row;
		}

		if (!empty($this->hca_cc_items))
		{
			$time_now = time();
			foreach($this->hca_cc_items as $cur_info)
			{
				$date_due_time = strtotime($cur_info['date_due']);
				$next_month = $date_due_time - 2592000;

				if ($time_now > $date_due_time)
					++$this->num_expired;
				else if ($time_now > $next_month)
					++$this->num_upcoming;
				else
					++$this->num_completed;
			}
		}
	}
}
