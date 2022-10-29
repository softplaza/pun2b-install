<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

require '../class_get_vendors.php';
$date = isset($_POST['date']) ? intval($_POST['date']) : 0;

// FIST OF EVENTS
if ($date > 0)
{
	$events_info = array();
	$json_vendors = $json_statistic = '';
	
$query = array(
	'SELECT'	=> 'i.*, p.unit_number, p.unit_size, p.property_id, p.status, pt.pro_name, v.vendor_name, v.email, v.phone_number, v.orders_limit',
	'FROM'		=> 'hca_vcr_invoices AS i',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'hca_vcr_projects AS p',
			'ON'			=> 'p.id=i.project_id'
		),
		array(
			'LEFT JOIN'		=> 'sm_vendors AS v',
			'ON'			=> 'v.id=i.vendor_id'
		),
		array(
			'LEFT JOIN'		=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=p.property_id'
		),
	),
	'WHERE'		=> 'i.in_house=0 AND i.date_time='.$date,
	'ORDER BY'	=> 'v.vendor_name, pt.pro_name, LENGTH(p.unit_number), p.unit_number'
);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$events_info[] = $row;
	}
	
	if (!empty($events_info))
	{
		$vendors_today = array();
		$json_vendors .= '<div class="st-title"><p id="event_date">Vendor List</p></div>'."\n";
		foreach($events_info as $cur_info)
		{
			$json_vendors .= '<div class="sb-event">'."\n";
			$json_vendors .= '<p class="subject"><strong>'.html_encode($cur_info['vendor_name']).'</strong></p>'."\n";
			$json_vendors .= '<p><strong>'.html_encode($cur_info['pro_name']).'</strong>';
			if ($cur_info['unit_number'] != '')
				$json_vendors .= '<strong>, #'.html_encode($cur_info['unit_number']).'</strong>';
			$json_vendors .= '<strong>, '.html_encode($cur_info['unit_size']).'</strong>';
			$json_vendors .= '</p>'."\n";
			$json_vendors .= '<p>'.html_encode($cur_info['remarks']).'</p>'."\n";
			
			$json_vendors .= '</div>'."\n";
			
			if (isset($vendors_today[$cur_info['vendor_id']]))
				$vendors_today[$cur_info['vendor_id']] = ++$vendors_today[$cur_info['vendor_id']];
			else
				$vendors_today[$cur_info['vendor_id']] = 1;
		}
		
		$json_statistic .= '<div class="st-title"><p id="event_date">Vendor orders limit</p></div>'."\n";
		$json_statistic .= '<div class="st-event">'."\n";
		foreach($vendors_today as $key => $val) {
			$orders_limit = $VCRVendors->get_orders_limit($key);
			if ($val > $orders_limit && $orders_limit != 0)
				$json_statistic .= '<p class="stat"><span class="circle-notify cn-pink">'.$val.'</span><strong>'.$VCRVendors->get_vendor_name($key).'</strong></p>'."\n";
			else if ($val == $orders_limit)
				$json_statistic .= '<p class="stat"><span class="circle-notify cn-orange">'.$val.'</span><strong>'.$VCRVendors->get_vendor_name($key).'</strong></p>'."\n";
			else
				$json_statistic .= '<p class="stat"><span class="circle-notify cn-green">'.$val.'</span><strong>'.$VCRVendors->get_vendor_name($key).'</strong></p>'."\n";
		}
		$json_statistic .= '</div>'."\n";
		$json_statistic .= $json_vendors;
	}
	
	echo json_encode(array(
		//Use for Calendar
		'event_date'		=> format_time($date, 1),
		'sb_events'			=> !empty($json_statistic) ? $json_statistic : '<div class="sb-event"><p>No events</p></div>',
		'start_time'		=> '<input type="datetime-local" name="start_time" value="'.date('Y-m-d\TH:i', $date).'" />'
	));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();