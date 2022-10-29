<?php

class HCAVCR
{
	function search_dupe_projects()
	{
		global $DBLayer, $URL;
		
		$query = array(
			'SELECT'	=> 'pj.id, pj.property_id, pj.unit_number, pj.status, pt.pro_name',
			'FROM'		=> 'hca_vcr_projects AS pj',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'sm_property_db AS pt',
					'ON'			=> 'pt.id=pj.property_id'
				),
			),
			'WHERE'		=> 'pj.status!=1', // Not completed
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$duplicate_info = $duplicates = [];
		while ($row = $DBLayer->fetch_assoc($result))
		{
			if (isset($duplicates[$row['property_id']][$row['unit_number']]))
			{
				$duplicate_info[] = $duplicates[$row['property_id']][$row['unit_number']];
				
				if ($row['status'] == 2)
					$duplicate_info[] = 'Duplicate project found: <a href="'.$URL->link('hca_vcr_projects', 'on_hold').'&row='.$row['id'].'#row'.$row['id'].'">'.$row['pro_name'].', #'.$row['unit_number'].'</a> (On Hold)';
				else if ($row['status'] == 5)
					$duplicate_info[] = 'Duplicate project found: <a href="'.$URL->link('hca_vcr_projects', 'recycle').'&row='.$row['id'].'#row'.$row['id'].'">'.$row['pro_name'].', #'.$row['unit_number'].'</a> (Recycle)';
				else
					$duplicate_info[] = 'Duplicate project found: '.$row['pro_name'].', #'.$row['unit_number'].'  (Active)';
			}
			else
			{
				if ($row['status'] == 2)
					$duplicates[$row['property_id']][$row['unit_number']] = 'Duplicate project found: <a href="'.$URL->link('hca_vcr_projects', ['on_hold', $row['id']]).'">'.$row['pro_name'].', #'.$row['unit_number'].'</a> (On Hold)';
				else if ($row['status'] == 5)
				
					$duplicates[$row['property_id']][$row['unit_number']] = 'Duplicate project found: <a href="'.$URL->link('hca_vcr_projects', ['recycle', $row['id']]).'">'.$row['pro_name'].', #'.$row['unit_number'].'</a> (Recycle)';
				else
					$duplicates[$row['property_id']][$row['unit_number']] = 'Duplicate project found: '.$row['pro_name'].', #'.$row['unit_number'].'  (Active)';
			}
		}
		
		return $duplicate_info;
	}
}

$HCAVCR = new HCAVCR;