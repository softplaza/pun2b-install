<?php

class HcaCC
{
	var $project_status = [
		0 => 'Removed',
		1 => 'Upcoming',
		2 => 'Pending',
		3 => 'Completed',
	];

	var $frequency = [
		//1 => 'Daily',
		//2 => 'Bi-Weekly',
		3 => 'Weekly',
		4 => 'Monthly',
		5 => 'Quarterly', // every quarter 1
		6 => 'Bi-Annual', //Twice a year 2
		7 => 'Annual', // every year 3
		8 => 'Every 2 Years',
		9 => 'Every 3 Years',
		10 => 'Every 4 Years',
		11 => 'Every 5 Years',
		12 => 'Every 6 Years',
		//13 => 'Every 7 Years',
		//14 => 'Every 8 Years',
		//15 => 'Every 9 Years',
		16 => 'Every 10 Years',
	];

	var $departments = [
		1 => 'Admin',
		2 => 'Accounting',
		3 => 'Compliance',
		4 => 'HR',
		5 => 'Landscaping',
		6 => 'Maintenance',
		7 => 'Marketing',
		8 => 'Permits',
		9 => 'Pest Control',
		10 => 'Loose Control'
	];

	var $required_by = [
		1 => 'HCA',
		2 => 'CITY',
		3 => 'STATE',
	];

	var $months = [
		1 => 'January',
		2 => 'February',
		3 => 'March',
		4 => 'April',
		5 => 'May',
		6 => 'June',
		7 => 'July',
		8 => 'August',
		9 => 'September',
		10 => 'October',
		11 => 'November',
		12 => 'December'
	];

    function getOwners($id, $info = [])
    {
		$output = [];

		if (!empty($info))
		{
			foreach($info as $cur_info)
			{
				if ($cur_info['item_id'] == $id)
					$output[] = $cur_info['realname'];
			}
		}

		return implode(', ', $output);
	}

    function getProperties($id, $info = [])
    {
		$output = [];

		if (!empty($info))
		{
			foreach($info as $cur_info)
			{
				if ($cur_info['item_id'] == $id)
					$output[] = $cur_info['pro_name'];
			}
		}

		return implode(', ', $output);
	}

    function getMonths($months = '')
    {
		$output = [];
		$months = explode(',', $months);

		if (!empty($months))
		{
			foreach($months as $month)
			{
				if (isset($this->months[$month]))
					$output[] = $this->months[$month];
			}
		}

		return implode(', ', $output);
	}

	function genDueDate($form_data)
	{
		$d = $form_data['date_due'];
		$DateTime = new DateTime($d);

		// Quaterly
		if ($form_data['frequency'] == 1)
		{
			return $this->genNextDate($form_data);
		}
		//Bi-Annual', // Twice a year
		else if ($form_data['frequency'] == 2)
		{
			return $this->genNextDate($form_data);
		}
		else if ($form_data['frequency'] == 3)
		{
			$DateTime->modify('+1 year');
		}
		else if ($form_data['frequency'] == 4)
		{
			$DateTime->modify('+2 years');
		}
		else if ($form_data['frequency'] == 5)
		{
			$DateTime->modify('+3 years');
		}
		else if ($form_data['frequency'] == 6)
		{
			$DateTime->modify('+4 years');
		}
		else if ($form_data['frequency'] == 7)
		{
			$DateTime->modify('+5 years');
		}
		else if ($form_data['frequency'] == 8)
		{
			$DateTime->modify('+6 years');
		}
		else if ($form_data['frequency'] == 12)
		{
			$DateTime->modify('+10 years');
		}

		return $DateTime->format('Y-m-d');
	}

	function genNextDate($form_data)
	{
		$arr_months = explode(',', $form_data['months_due']);
		asort($arr_months);

		if (!empty($arr_months))
		{
			$cur_due_month = date('m', strtotime($form_data['date_due']));
			$next_month = 0;
			$cur_year = date('Y', strtotime($form_data['date_due']));

			foreach($arr_months as $cur_month)
			{
				$cur_month = ($cur_month < 10) ? 0 . $cur_month : $cur_month;

				if ($next_month == 0)
					$next_month = $cur_month;

				if ($cur_month > $cur_due_month)
					return $cur_year.'-'.$cur_month.'-01';
			}

			if ($next_month > 0)
			{
				$next_year = intval($cur_year) + 1;
				return $next_year.'-'.$next_month.'-01';
			}
		}
	}
}
