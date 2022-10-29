<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_vcr', 3)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

require 'class_auto_assigner.php';
require 'class_get_vendors.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message($lang_common['Bad request']);

$time_slots = array(0 => 'ANY TIME', 1 => 'ALL DAY', 2 => 'A.M.', 3 => 'P.M.');
$query = array(
	'SELECT'	=> 'pj.*, pt.pro_name',
	'FROM'		=> 'hca_vcr_projects AS pj',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		)
	),
	'WHERE'		=> 'pj.id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $DBLayer->fetch_assoc($result);

if (empty($main_info))
	message($lang_common['Bad request']);

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'hca_vcr_invoices',
	'WHERE'		=> 'project_id='.$id,//AND project_name
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$vendors_schedule = $vendors_schedule_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$vendors_schedule[$row['vendor_group_id']] = $row;
}

for($i = 1; $i < 10; $i++)
{
	if (isset($vendors_schedule[$i]))
		$vendors_schedule_info[$i] = $vendors_schedule[$i];
	else
		$vendors_schedule_info[$i] = array(
			'vendor_id'		=> 0,
			'date_time'		=> 0,
			'remarks'		=> '',
			'shift'			=> 0
		);
}

$query = array(
	'SELECT'	=> 'u.*',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'u.id > 2',
	'ORDER BY'	=> 'realname'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$painter_managers = $maintenance_managers = [];
while ($row = $DBLayer->fetch_assoc($result))
{
	if ($row['hca_fs_group'] == $Config->get('o_hca_fs_maintenance'))
		$maintenance_managers[] = $row;
	else if ($row['hca_fs_group'] == $Config->get('o_hca_fs_painters'))
		$painter_managers[] = $row;
}

$query = array(
	'SELECT'	=> 'id, pro_name, manager_email',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'display_position'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[$row['id']] = $row;
}

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_vendors',
	'WHERE'		=> 'hca_vcr=1',
	'ORDER BY'	=> 'vendor_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$vendors_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$vendors_info[$row['id']] = $row;
}

if (isset($_POST['form_sent']))
{
	$reschedule = isset($_POST['reschedule']) ? intval($_POST['reschedule']) : 0;
	$maint_shift = isset($_POST['maint_shift']) ? intval($_POST['maint_shift']) : 0;//8
	$paint_shift = isset($_POST['paint_shift']) ? intval($_POST['paint_shift']) : 0;//2
	
	//Use if you have ID in form:  name="property_name['.$cur_info['id'].']
	$FormChecker = new FormChecker;
	
	$form_text = $FormChecker->trim_arr(array('unit_size', 'move_out_comment', 'move_in_comment', 'pre_walk_name', 'pre_walk_comment', /*'maint_name',*/ 'maint_comment', 'paint_name', 'paint_comment', 'urine_scan_comment', 'clean_name', 'clean_comment', 'refinish_name', 'refinish_comment', 'vinyl_name', 'vinyl_comment', 'crpt_name', 'crpt_comment', 'crpt_clean_comment', 'walk', 'walk_comment', 'pest_name', 'pest_comment', 'remarks'));
	
	$form_number = $FormChecker->intval_arr(array('maint_time_slot', 'paint_time_slot', 'paint_inhouse', 'urine_scan', 'urine_scan_vendor_id', 'vinyl_replaced', 'crpt_replaced', 'crpt_vendor_id', 'crpt_clean_vendor_id', 'refinish_check', /*'status',*/ 'paint_vendor_id', 'clean_vendor_id', 'refinish_vendor_id', 'vinyl_vendor_id', 'crpt_repair', 'pest_vendor_id'));
	
	$form_time = $FormChecker->strtotime_arr(array('move_out_date','move_in_date', 'pre_walk_date', 'maint_start_date', 'maint_end_date', 'paint_start_date', 'paint_end_date', 'urine_scan_date', 'clean_date', 'refinish_start_date', 'refinish_end_date', 'vinyl_date', 'crpt_date', 'crpt_clean_date', 'walk_date', 'pest_date', 'submited_date'));
	
	$form_data = array();
	$form_data = array_merge($form_data, $form_text, $form_number, $form_time);
	
//	$form_data['submited_by'] = $User->get('realname');
	
	//Check Maint Date
//	if ($form_data['maint_start_date'] > 0 && in_array($form_data['maint_start_date'], array($form_data['paint_start_date'], $form_data['clean_date'], $form_data['vinyl_date'], $form_data['pest_date'], $form_data['refinish_start_date'])))
//		$Core->add_error('Maintenance Start Date cannot be scheduled on the same day with other Vendors.');
	
	//Check Paint Date
//	if ($form_data['paint_start_date'] > 0 && in_array($form_data['paint_start_date'], array($form_data['maint_start_date'], $form_data['clean_date'], $form_data['vinyl_date'], $form_data['crpt_date'], $form_data['pest_date'], $form_data['refinish_start_date'])))
//		$Core->add_error('Painter Start Date cannot be scheduled on the same day with other Vendors.');
	
	//Check Vinyl Date
	if ($form_data['vinyl_date'] > 0 && in_array($form_data['vinyl_date'], array($form_data['maint_start_date'], $form_data['paint_start_date'], $form_data['clean_date'], $form_data['pest_date'], $form_data['refinish_start_date'])))
		$Core->add_error('Vinyl Date cannot be scheduled on the same day with other Vendors.');
	
	//Check Carpet Date
	if ($form_data['crpt_date'] > 0 && in_array($form_data['crpt_date'], array($form_data['maint_start_date'], $form_data['paint_start_date'], $form_data['clean_date'], $form_data['pest_date'], $form_data['refinish_start_date'])))
		$Core->add_error('Carpet Date cannot be scheduled on the same day with other Vendors.');
	
	//Check Cleaning Date
	if ($form_data['clean_date'] > 0 && in_array($form_data['clean_date'], array($form_data['maint_start_date'], $form_data['paint_start_date'], $form_data['vinyl_date'], $form_data['crpt_date'], $form_data['pest_date'], $form_data['refinish_start_date'])))
		$Core->add_error('Cleaning Date cannot be scheduled on the same day with other Vendors.');
	
	//Check Pest Control Date
	if ($form_data['pest_date'] > 0 && in_array($form_data['pest_date'], array($form_data['maint_start_date'], $form_data['paint_start_date'], $form_data['vinyl_date'], $form_data['crpt_date'], $form_data['clean_date'], $form_data['refinish_start_date'])))
		$Core->add_error('Pest Control Date cannot be scheduled on the same day with other Vendors.');
	
	//Check Refinish Date
	if ($form_data['refinish_start_date'] > 0 && in_array($form_data['refinish_start_date'], array($form_data['maint_start_date'], $form_data['paint_start_date'], $form_data['vinyl_date'], $form_data['crpt_date'], $form_data['clean_date'], $form_data['pest_date'])))
		$Core->add_error('Refinish Start Date cannot be scheduled on the same day with other Vendors.');
	
	$time_now = time();
	if (empty($Core->errors) && !empty($form_data))
	{
		//Check Move Out Date and setup all dates
		if (($form_data['move_out_date'] != $main_info['move_out_date'] && $form_data['move_out_date'] > 0) || ($reschedule == 1 && $form_data['move_out_date'] > 0))
		{
			$AutoAssigner->setMoveOutDate($form_data['move_out_date']);
			
			//Set Up Services Date
			$form_data['maint_start_date'] = $AutoAssigner->setMaintDate();
			$form_data['paint_start_date'] = $AutoAssigner->setPaintDate();
			// Set p the same date as Maintenance
			if ($form_data['urine_scan'] == 1)
				$form_data['urine_scan_date'] = $form_data['maint_start_date'];

			// if Carpet or Vinyl replaced -> assign Cleaning after Carpet and Vinyl
			if ($form_data['crpt_replaced'] == 1 || $form_data['vinyl_replaced'] == 1)
			{
				if ($form_data['vinyl_comment'] != '')
					$form_data['vinyl_date'] = $AutoAssigner->setVinylDate();
				
				//Is it the same date? 
				//$form_data['crpt_date'] = $AutoAssigner->setCarpetDate();
				if ($form_data['crpt_replaced'] == 1 || $form_data['crpt_repair'] == 1)
					$form_data['crpt_date'] = ($form_data['vinyl_date'] > 0) ? $form_data['vinyl_date'] : $AutoAssigner->setCarpetDate();
				
				if ($form_data['crpt_replaced'] == 0)
					$form_data['crpt_clean_date'] = $AutoAssigner->setCleanCarpetDate();
				
				$form_data['clean_date'] = $AutoAssigner->setCleanDate();
			}
			else
			{
				$form_data['clean_date'] = $AutoAssigner->setCleanDate();
				
				if ($form_data['vinyl_comment'] != '')
					$form_data['vinyl_date'] = $AutoAssigner->setVinylDate();
				
				//Is it the same date? 
				//$form_data['crpt_date'] = $AutoAssigner->setCarpetDate();
				if ($form_data['crpt_repair'] == 1)
					$form_data['crpt_date'] = ($form_data['vinyl_date'] > 0) ? $form_data['vinyl_date'] : $AutoAssigner->setCarpetDate();
				
				$form_data['crpt_clean_date'] = $AutoAssigner->setCleanCarpetDate();
			}
			
			//if Fail/Decenf set date
			if ($form_data['refinish_check'] == 1)
				$form_data['refinish_start_date'] = $AutoAssigner->setRefinishDate();
			
			$form_data['pest_date'] = $AutoAssigner->setPestDate();
			
			// Setup the same date as Cleaning Date if the date == 0 OR Cleaning Day is NOT Saturday && Not Sunday
			$daynum_clean_date = date("N", $form_data['clean_date']);
			if ($form_data['walk_date'] == 0 && $daynum_clean_date > 0 && $daynum_clean_date < 6)
				$form_data['walk_date'] = $form_data['clean_date'];
			
			//Setup Pre Walk Date 10 days before Move Out Date when is No TurnOver Inspection
			if ($main_info['pre_walk_date'] == 0 && $main_info['turn_over_id'] == 0)
				$form_data['pre_walk_date'] = $form_data['move_out_date'] - 864000;//10 days
			
			//Setup defaul Vendors
			if ($form_data['crpt_date'] > 0 && $form_data['crpt_vendor_id'] == 0)
				$form_data['crpt_vendor_id'] = $Config->get('o_hca_vcr_default_carpet_vendor');
			
			if ($form_data['crpt_clean_date'] > 0 && $form_data['crpt_clean_vendor_id'] == 0)
				$form_data['crpt_clean_vendor_id'] = $Config->get('o_hca_vcr_default_carpet_vendor');
			
			if ($form_data['vinyl_date'] > 0 && $form_data['vinyl_vendor_id'] == 0)
				$form_data['vinyl_vendor_id'] = $Config->get('o_hca_vcr_default_vinyl_vendor');
			
			if ($form_data['urine_scan_date'] > 0 && $form_data['urine_scan_vendor_id'] == 0)
				$form_data['urine_scan_vendor_id'] = $Config->get('o_hca_vcr_default_urine_vendor');
			
			if ($form_data['pest_date'] > 0 && $form_data['pest_vendor_id'] == 0)
				$form_data['pest_vendor_id'] = $Config->get('o_hca_vcr_default_pest_vendor');
			
			if ($form_data['clean_date'] > 0 && $form_data['clean_vendor_id'] == 0)
				$form_data['clean_vendor_id'] = $Config->get('o_hca_vcr_default_cleaning_vendor');
			
			if ($form_data['paint_start_date'] > 0 && $form_data['paint_vendor_id'] == 0)
				$form_data['paint_vendor_id'] = $Config->get('o_hca_vcr_default_painter_vendor');
			
			if ($form_data['refinish_start_date'] > 0 && $form_data['refinish_vendor_id'] == 0)
				$form_data['refinish_vendor_id'] = $Config->get('o_hca_vcr_default_refinish_vendor');
		}
		
		$query = array(
			'SELECT'	=> 'i.*',
			'FROM'		=> 'hca_vcr_invoices AS i',
			'WHERE'		=> 'i.project_id='.$id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$invoices_info = array();
		while ($row = $DBLayer->fetch_assoc($result)) {
			$invoices_info[$row['vendor_group_id']] = $row;
		}
		
		// !!! Setup max groups
		// 1 - Urine Scan
		// 2 - Painter Vendor or In house
		// 3 - Vinyl
		// 4 - Carpet
		// 5 - Pest
		// 6 - Cleaning Service
		// 7 - Refinish
		// 8 - Maintenance
		// 9 - Carpet Clean
		for($i = 1; $i < 10; $i++)
		{
			//UPDATE VENDORS SCHEDULE
			if (isset($invoices_info[$i]))
			{
				//Urine Scan Vendor
				if ($i == 1)
				{
					$query = array(
						'UPDATE'	=> 'hca_vcr_invoices',
						'SET'		=> 
							'date_time=\''.$DBLayer->escape($form_data['urine_scan_date']).'\',
							vendor_id=\''.$DBLayer->escape($form_data['urine_scan_vendor_id']).'\',
							remarks=\''.$DBLayer->escape($form_data['urine_scan_comment']).'\'',
						'WHERE'		=> 'id='.$invoices_info[$i]['id']
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				}
				
				//Painters Group $maint_shift
				if ($i == 2)
				{
					$query = array(
						'UPDATE'	=> 'hca_vcr_invoices',
						'SET'		=> 
							'date_time=\''.$DBLayer->escape($form_data['paint_start_date']).'\',
							vendor_id=\''.$DBLayer->escape($form_data['paint_vendor_id']).'\',
							remarks=\''.$DBLayer->escape($form_data['paint_comment']).'\',
							in_house=\''.$DBLayer->escape($form_data['paint_inhouse']).'\',
							shift=\''.$DBLayer->escape($paint_shift).'\'',
						'WHERE'		=> 'id='.$invoices_info[$i]['id']
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);

					$mail_message = [];
					if ($main_info['paint_fs_req_id'] > 0)
					{
						$DBLayer->update('hca_fs_requests', ['work_status' => 5], $main_info['paint_fs_req_id']);
						$mail_message[] = 'Property Request #'.$main_info['paint_fs_req_id'].' has been cancelsed.'."\n";
					}

					if ($form_data['paint_inhouse'] == 1 && $form_data['paint_start_date'] > 0)
					{
						$fs_data = array(
							'property_id'		=> $main_info['property_id'],
							'unit_number'		=> $main_info['unit_number'],
							'group_id'			=> 9,
							'time_slot'			=> $paint_shift,
							'request_msg'		=> $form_data['paint_comment'],
							'msg_for_maint'		=> $form_data['paint_comment'],
							'created'			=> time(),
							'scheduled'			=> date('Ymd', $form_data['paint_start_date']),
							'start_date'		=> $form_data['paint_start_date'],
							'execution_priority'=> 2,// hight
							'permission_enter'	=> 1,
							'requested_by'		=> $User->get('realname'),
							'template_type'		=> 2,
						);
						$new_fs_paint_req = $DBLayer->insert_values('hca_fs_requests', $fs_data);

						//$painter_managers = $maintenance_managers
						if (!empty($painter_managers))
						{
							$emails = [];
							foreach($painter_managers as $painter_manager)
							{
								$emails[] = $painter_manager['email'];
							}

							$mail_message[] = 'A new Property Request #'.$new_fs_paint_req.' has been created. See the details bellow'."\n";
							$mail_message[] = 'Property name: '.$main_info['pro_name'];
							$mail_message[] = 'Unit #: '.$main_info['unit_number'];
							$mail_message[] = 'Comment: '.$form_data['paint_comment'];
							$mail_message[] = 'Submitted by: '.$User->get('realname');

							$SwiftMailer = new SwiftMailer;
							$SwiftMailer->send(implode(',', $emails), 'A new property request', implode("\n", $mail_message));
						}
					}
				}
				
				// Vinyl Vendor Invoice
				if ($i == 3)
				{
					$query = array(
						'UPDATE'	=> 'hca_vcr_invoices',
						'SET'		=> 
							'date_time=\''.$DBLayer->escape($form_data['vinyl_date']).'\',
							vendor_id=\''.$DBLayer->escape($form_data['vinyl_vendor_id']).'\',
							remarks=\''.$DBLayer->escape($form_data['vinyl_comment']).'\'',
						'WHERE'		=> 'id='.$invoices_info[$i]['id']
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				}	
				
				// Carpet Vendor Invoice
				if ($i == 4)
				{
					$query = array(
						'UPDATE'	=> 'hca_vcr_invoices',
						'SET'		=> 
							'date_time=\''.$DBLayer->escape($form_data['crpt_date']).'\',
							vendor_id=\''.$DBLayer->escape($form_data['crpt_vendor_id']).'\',
							remarks=\''.$DBLayer->escape($form_data['crpt_comment']).'\'',
						'WHERE'		=> 'id='.$invoices_info[$i]['id']
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				}
				
				// Pest Control Vendor Invoice
				if ($i == 5)
				{
					$query = array(
						'UPDATE'	=> 'hca_vcr_invoices',
						'SET'		=> 
							'date_time=\''.$DBLayer->escape($form_data['pest_date']).'\',
							vendor_id=\''.$DBLayer->escape($form_data['pest_vendor_id']).'\',
							remarks=\''.$DBLayer->escape($form_data['pest_comment']).'\'',
						'WHERE'		=> 'id='.$invoices_info[$i]['id']
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				}
				
				// Cleaning Vendor
				if ($i == 6)
				{
					$query = array(
						'UPDATE'	=> 'hca_vcr_invoices',
						'SET'		=> 
							'date_time=\''.$DBLayer->escape($form_data['clean_date']).'\',
							vendor_id=\''.$DBLayer->escape($form_data['clean_vendor_id']).'\',
							remarks=\''.$DBLayer->escape($form_data['clean_comment']).'\'',
						'WHERE'		=> 'id='.$invoices_info[$i]['id']
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);	
				}
				
				// Refinish Vendor
				if ($i == 7)
				{	
					$query = array(
						'UPDATE'	=> 'hca_vcr_invoices',
						'SET'		=> 
							'date_time=\''.$DBLayer->escape($form_data['refinish_start_date']).'\',
							vendor_id=\''.$DBLayer->escape($form_data['refinish_vendor_id']).'\',
							remarks=\''.$DBLayer->escape($form_data['refinish_comment']).'\'',
						'WHERE'		=> 'id='.$invoices_info[$i]['id']
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);	
				}
				
				// Maintenance Group
				if ($i == 8)
				{
					$query = array(
						'UPDATE'	=> 'hca_vcr_invoices',
						'SET'		=> 
							'date_time=\''.$DBLayer->escape($form_data['maint_start_date']).'\',
							remarks=\''.$DBLayer->escape($form_data['maint_comment']).'\',
							in_house=1,
							shift=\''.$DBLayer->escape($maint_shift).'\'',
						'WHERE'		=> 'id='.$invoices_info[$i]['id']
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);

					$mail_message = [];
					if ($main_info['maint_fs_req_id'] > 0)
					{
						$DBLayer->update('hca_fs_requests', ['work_status' => 5], $main_info['maint_fs_req_id']);
						$mail_message[] = 'Property Request #'.$main_info['paint_fs_req_id'].' has been cancelsed.'."\n";
					}
					
					if ($form_data['maint_start_date'] > 0)
					{
						$fs_data = array(
							'property_id'		=> $main_info['property_id'],
							'unit_number'		=> $main_info['unit_number'],
							'group_id'			=> 9,
							'time_slot'			=> $maint_shift,
							'request_msg'		=> $form_data['maint_comment'],
							'msg_for_maint'		=> $form_data['maint_comment'],
							'created'			=> time(),
							'scheduled'			=> date('Ymd', $form_data['maint_start_date']),
							'start_date'		=> $form_data['maint_start_date'],
							'execution_priority'=> 2,// hight
							'permission_enter'	=> 1,
							'requested_by'		=> $User->get('realname'),
							'template_type'		=> 2,
						);
						$new_fs_maint_req = $DBLayer->insert_values('hca_fs_requests', $fs_data);

						//$painter_managers = $maintenance_managers
						if (!empty($maintenance_managers))
						{
							$emails = [];
							foreach($maintenance_managers as $maintenance_manager)
							{
								$emails[] = $maintenance_manager['email'];
							}

							$mail_message[] = 'A new Property Request #'.$new_fs_maint_req.' has been created. See the details bellow'."\n";
							$mail_message[] = 'Property name: '.$main_info['pro_name'];
							$mail_message[] = 'Unit #: '.$main_info['unit_number'];
							$mail_message[] = 'Comment: '.$form_data['maint_comment'];
							$mail_message[] = 'Submitted by: '.$User->get('realname');

							$SwiftMailer = new SwiftMailer;
							$SwiftMailer->send(implode(',', $emails), 'A new property request', implode("\n", $mail_message));
						}
					}
				}
				
				// Carpet Clean
				if ($i == 9)
				{
					$query = array(
						'UPDATE'	=> 'hca_vcr_invoices',
						'SET'		=> 
							'date_time=\''.$DBLayer->escape($form_data['crpt_clean_date']).'\',
							vendor_id=\''.$DBLayer->escape($form_data['crpt_clean_vendor_id']).'\',
							remarks=\''.$DBLayer->escape($form_data['crpt_clean_comment']).'\'',
						'WHERE'		=> 'id='.$invoices_info[$i]['id']
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				}
			}
			
			// Insert to invoice
			else
			{
				// Refinish Vendor Invoice
				if ($i == 1 && $form_data['urine_scan_vendor_id'] > 0 && $form_data['urine_scan_date'] > 0)
				{
					$query = array(
						'INSERT'	=> 'project_id, date_time, vendor_id, vendor_group_id, remarks',
						'INTO'		=> 'hca_vcr_invoices',
						'VALUES'	=> 
							'\''.$DBLayer->escape($id).'\',
							\''.$DBLayer->escape($form_data['urine_scan_date']).'\',
							\''.$DBLayer->escape($form_data['urine_scan_vendor_id']).'\',
							\''.$DBLayer->escape($i).'\',
							\''.$DBLayer->escape($form_data['urine_scan_comment']).'\''
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				}
				
				// Painter Vendor Invoice
				if ($i == 2 && $form_data['paint_start_date'] > 0)
				{
					$query = array(
						'INSERT'	=> 'project_id, date_time, vendor_id, vendor_group_id, remarks, in_house, shift',
						'INTO'		=> 'hca_vcr_invoices',
						'VALUES'	=> 
							'\''.$DBLayer->escape($id).'\',
							\''.$DBLayer->escape($form_data['paint_start_date']).'\',
							\''.$DBLayer->escape($form_data['paint_vendor_id']).'\',
							\''.$DBLayer->escape($i).'\',
							\''.$DBLayer->escape($form_data['paint_comment']).'\',
							\''.$DBLayer->escape($form_data['paint_inhouse']).'\',
							\''.$DBLayer->escape($paint_shift).'\''
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);

					$mail_message = [];
					if ($form_data['paint_inhouse'] == 1)
					{
						$fs_data = array(
							'property_id'		=> $main_info['property_id'],
							'unit_number'		=> $main_info['unit_number'],
							'group_id'			=> 9,
							'time_slot'			=> $paint_shift,
							'request_msg'		=> $form_data['paint_comment'],
							'msg_for_maint'		=> $form_data['paint_comment'],
							'created'			=> time(),
							'scheduled'			=> date('Ymd', $form_data['paint_start_date']),
							'start_date'		=> $form_data['paint_start_date'],
							'execution_priority'=> 2,// hight
							'permission_enter'	=> 1,
							'requested_by'		=> $User->get('realname'),
							'template_type'		=> 2,
						);
						$new_fs_paint_req = $DBLayer->insert_values('hca_fs_requests', $fs_data);

						//$painter_managers = $maintenance_managers
						if (!empty($painter_managers))
						{
							$emails = [];
							foreach($painter_managers as $painter_manager)
							{
								$emails[] = $painter_manager['email'];
							}

							$mail_message[] = 'A new Property Request #'.$new_fs_paint_req.' has been created. See the details bellow'."\n";
							$mail_message[] = 'Property name: '.$main_info['pro_name'];
							$mail_message[] = 'Unit #: '.$main_info['unit_number'];
							$mail_message[] = 'Comment: '.$form_data['paint_comment'];
							$mail_message[] = 'Submitted by: '.$User->get('realname');

							$SwiftMailer = new SwiftMailer;
							$SwiftMailer->send(implode(',', $emails), 'A new property request', implode("\n", $mail_message));
						}
					}
				}
				
				// Vinyl Vendor Invoice
				if ($i == 3 && $form_data['vinyl_date'] > 0 && $form_data['vinyl_vendor_id'] > 0)
				{
					$query = array(
						'INSERT'	=> 'project_id, date_time, vendor_id, vendor_group_id, remarks',
						'INTO'		=> 'hca_vcr_invoices',
						'VALUES'	=> 
							'\''.$DBLayer->escape($id).'\',
							\''.$DBLayer->escape($form_data['vinyl_date']).'\',
							\''.$DBLayer->escape($form_data['vinyl_vendor_id']).'\',
							\''.$DBLayer->escape($i).'\',
							\''.$DBLayer->escape($form_data['vinyl_comment']).'\''
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				}	
				
				// Carpet Vendor Invoice
				if ($i == 4 && $form_data['crpt_date'] > 0 && $form_data['crpt_vendor_id'] > 0)
				{
					$query = array(
						'INSERT'	=> 'project_id, date_time, vendor_id, vendor_group_id, remarks',
						'INTO'		=> 'hca_vcr_invoices',
						'VALUES'	=> 
							'\''.$DBLayer->escape($id).'\',
							\''.$DBLayer->escape($form_data['crpt_date']).'\',
							\''.$DBLayer->escape($form_data['crpt_vendor_id']).'\',
							\''.$DBLayer->escape($i).'\',
							\''.$DBLayer->escape($form_data['crpt_comment']).'\''
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				}
				
				// Pest Control Vendor Invoice
				if ($i == 5 && $form_data['pest_date'] > 0 && $form_data['pest_vendor_id'] > 0)
				{
					$query = array(
						'INSERT'	=> 'project_id, date_time, vendor_id, vendor_group_id, remarks',
						'INTO'		=> 'hca_vcr_invoices',
						'VALUES'	=> 
							'\''.$DBLayer->escape($id).'\',
							\''.$DBLayer->escape($form_data['pest_date']).'\',
							\''.$DBLayer->escape($form_data['pest_vendor_id']).'\',
							\''.$DBLayer->escape($i).'\',
							\''.$DBLayer->escape($form_data['pest_comment']).'\''
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				}
				
				// Cleaning Vendor Invoice
				if ($i == 6 && $form_data['clean_date'] > 0 && $form_data['clean_vendor_id'] > 0)
				{
					$query = array(
						'INSERT'	=> 'project_id, date_time, vendor_id, vendor_group_id, remarks',
						'INTO'		=> 'hca_vcr_invoices',
						'VALUES'	=> 
							'\''.$DBLayer->escape($id).'\',
							\''.$DBLayer->escape($form_data['clean_date']).'\',
							\''.$DBLayer->escape($form_data['clean_vendor_id']).'\',
							\''.$DBLayer->escape($i).'\',
							\''.$DBLayer->escape($form_data['clean_comment']).'\''
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				}
				
				// Refinish Vendor Invoice
				if ($i == 7 && $form_data['refinish_start_date'] > 0 && $form_data['refinish_vendor_id'] > 0)
				{
					$query = array(
						'INSERT'	=> 'project_id, date_time, vendor_id, vendor_group_id, remarks',
						'INTO'		=> 'hca_vcr_invoices',
						'VALUES'	=> 
							'\''.$DBLayer->escape($id).'\',
							\''.$DBLayer->escape($form_data['refinish_start_date']).'\',
							\''.$DBLayer->escape($form_data['refinish_vendor_id']).'\',
							\''.$DBLayer->escape($i).'\',
							\''.$DBLayer->escape($form_data['refinish_comment']).'\''
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				}
				
				// Maintenance Vendor Invoice
				if ($i == 8 && $form_data['maint_start_date'] > 0)
				{
					$query = array(
						'INSERT'	=> 'project_id, date_time, vendor_group_id, remarks, in_house, shift',
						'INTO'		=> 'hca_vcr_invoices',
						'VALUES'	=> 
							'\''.$DBLayer->escape($id).'\',
							\''.$DBLayer->escape($form_data['maint_start_date']).'\',
							\''.$DBLayer->escape($i).'\',
							\''.$DBLayer->escape($form_data['maint_comment']).'\',
							\''.$DBLayer->escape(1).'\',
							\''.$DBLayer->escape($maint_shift).'\''
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);

					$fs_data = array(
						'property_id'		=> $main_info['property_id'],
						'unit_number'		=> $main_info['unit_number'],
						'group_id'			=> 9,
						'time_slot'			=> $maint_shift,
						'request_msg'		=> $form_data['maint_comment'],
						'msg_for_maint'		=> $form_data['maint_comment'],
						'created'			=> time(),
						'scheduled'			=> date('Ymd', $form_data['maint_start_date']),
						'start_date'		=> $form_data['maint_start_date'],
						'execution_priority'=> 2,// hight
						'permission_enter'	=> 1,
						'requested_by'		=> $User->get('realname'),
						'template_type'		=> 2,
					);
					$new_fs_maint_req = $DBLayer->insert_values('hca_fs_requests', $fs_data);

					//$painter_managers = $maintenance_managers
					if (!empty($maintenance_managers))
					{
						$emails = [];
						foreach($maintenance_managers as $maintenance_manager)
						{
							$emails[] = $maintenance_manager['email'];
						}

						$mail_message[] = 'A new Property Request #'.$new_fs_maint_req.' has been created. See the details bellow'."\n";
						$mail_message[] = 'Property name: '.$main_info['pro_name'];
						$mail_message[] = 'Unit #: '.$main_info['unit_number'];
						$mail_message[] = 'Comment: '.$form_data['maint_comment'];
						$mail_message[] = 'Submitted by: '.$User->get('realname');

						$SwiftMailer = new SwiftMailer;
						$SwiftMailer->send(implode(',', $emails), 'A new property request', implode("\n", $mail_message));
					}
				}
				
				// Maintenance Vendor Invoice
				if ($i == 9 && $form_data['crpt_clean_date'] > 0)
				{
					$query = array(
						'INSERT'	=> 'project_id, date_time, vendor_id, vendor_group_id, remarks',
						'INTO'		=> 'hca_vcr_invoices',
						'VALUES'	=> 
							'\''.$DBLayer->escape($id).'\',
							\''.$DBLayer->escape($form_data['crpt_clean_date']).'\',
							\''.$DBLayer->escape($form_data['crpt_clean_vendor_id']).'\',
							\''.$DBLayer->escape($i).'\',
							\''.$DBLayer->escape($form_data['crpt_clean_comment']).'\''
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				}
			}
		}
		
		$project_data = array(
			'unit_size' 		=> $form_data['unit_size'],
			'move_out_date' 	=> $form_data['move_out_date'],
			'move_out_comment' 	=> $form_data['move_out_comment'],
			'move_in_date' 		=> $form_data['move_in_date'],
			'move_in_comment' 	=> $form_data['move_in_comment'],
			'pre_walk_date' 	=> $form_data['pre_walk_date'],
			'pre_walk_name' 	=> $form_data['pre_walk_name'],
			'pre_walk_comment' 	=> $form_data['pre_walk_comment'],
			'paint_inhouse' 	=> $form_data['paint_inhouse'],
			'crpt_replaced' 	=> $form_data['crpt_replaced'],
			'crpt_repair' 		=> $form_data['crpt_repair'],
			'vinyl_replaced' 	=> $form_data['vinyl_replaced'],
			'refinish_check' 	=> $form_data['refinish_check'],
			'walk' 				=> $form_data['walk'],
			'walk_date' 		=> $form_data['walk_date'],
			'walk_comment' 		=> $form_data['walk_comment'],
			'remarks' 			=> $form_data['remarks'],
		);
		if (isset($new_fs_paint_req) && $new_fs_paint_req > 0)
			$project_data['paint_fs_req_id'] = $new_fs_paint_req;
		if (isset($new_fs_maint_req) && $new_fs_maint_req > 0)
			$project_data['maint_fs_req_id'] = $new_fs_maint_req;
		$DBLayer->update_values('hca_vcr_projects', $id, $project_data);

		$new_id = 0;

		//Send Email
		$mail_subject = 'VCR Project Changes';
		$mail_message = 'Hello,'."\n";
		$mail_message .= 'A VCR Project has been changed. See details bellow.'."\n\n";
		$mail_message .= 'Property: <strong>'.$main_info['pro_name'].'</strong>'."\n";
		$mail_message .= 'Unit#: <strong>'.$main_info['unit_number'].'</strong>'."\n";
		if ($form_data['move_out_date'] != $main_info['move_out_date'] && $form_data['move_out_date'] > 0) {
			$mail_message .= 'Old Move Out Date: <strong>'.format_time($main_info['move_out_date'], 1).'</strong>'."\n";
			$mail_message .= 'New Move Out Date: <strong>'.format_time($form_data['move_out_date'], 1).'</strong>'."\n";
		} else
			$mail_message .= 'Move Out Date: <strong>'.format_time($form_data['move_out_date'], 1).'</strong>'."\n";
		$mail_message .= 'Changed by: <strong>'.$User->get('realname').'</strong>'."\n\n";
		$mail_message .= 'To view this project follow the link below '.$URL->link('hca_vcr_manage_project', $id)."\n\n";
/*
		$SwiftMailer = new SwiftMailer;
		//$SwiftMailer->isHTML();
		$SwiftMailer->send($emails, 'Moisture Project', $mail_message);
*/
		$flash_message = 'Project #'.$id.' has been updated.';
		if ($form_data['move_out_date'] != $main_info['move_out_date'] && $form_data['move_out_date'] > 0)
			$flash_message .= ' Move Out Date has been changed on '.format_time($form_data['move_out_date'], 1);
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete']))
{
	if ($id > 0)
	{
		$query = array(
			'DELETE'	=> 'hca_vcr_projects',
			'WHERE'		=> 'id='.$id,
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		$query = array(
			'DELETE'	=> 'hca_vcr_invoices',
			'WHERE'		=> 'project_id='.$id,
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		/*
		//Send Email
		$mail_subject = 'VCR Project Deleted';
		$mail_message = 'Hello,'."\n";
		$mail_message .= 'A VCR Project has been deleted. See details bellow.'."\n\n";
		$mail_message .= 'Property: <strong>'.$main_info['pro_name'].'</strong>'."\n";
		$mail_message .= 'Unit#: <strong>'.$main_info['unit_number'].'</strong>'."\n";
		
		foreach($vcr_managers_access as $cur_info)
		{

		}
		$SwiftMailer = new SwiftMailer;
		//$SwiftMailer->isHTML();
		$SwiftMailer->send($emails, 'Moisture Project', $mail_message);
		*/
		$flash_message = 'Project #'.$id.' has been deleted.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_vcr_projects', 'active'), $flash_message);
	}
}

$Core->set_page_id('hca_vcr_manage_project', 'hca_vcr');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Project Management</h6>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-3">
					<label class="form-label" for="fld_pro_name">Property name</label>
					<input value="<?php echo html_encode($main_info['pro_name']) ?>" class="form-control" id="fld_pro_name" disabled>
				</div>
				<div class="col-md-3">
					<label class="form-label" for="fld_unit_number">Unit number</label>
					<div id="unit_number">
						<input type="text" name="unit_number" value="<?php echo isset($_POST['unit_number']) ? html_encode($_POST['unit_number']) : html_encode($main_info['unit_number']) ?>" class="form-control" id="fld_unit_number">
					</div>
				</div>
				<div class="col-md-3">
					<label class="form-label" for="fld_unit_size">Unit size</label>
					<input type="text" name="unit_size" value="<?php echo isset($_POST['unit_size']) ? html_encode($_POST['unit_size']) : html_encode($main_info['unit_size']) ?>" placeholder="Enter size" list="fld_unit_size" class="form-control">
					<datalist id="fld_unit_size">
<?php
$query = [
	'SELECT'	=> 's.size_title',
	'FROM'		=> 'sm_property_unit_sizes AS s',
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$unit_sizes = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	echo "\t\t\t\t\t\t\t".'<option value="'.$row['size_title'].'">'."\n";
}
?>
					</datalist>
				</div>
			</div>
		</div>

		<div class="card-header">
			<h6 class="card-title mb-0">Move Out/Move In Dates</h6>
		</div>
		<div class="card-body">
			<div class="alert alert-info" role="alert">
				<ul>
					<li>If you set/change Move Out Date, the Start Dates of all Vendors will be changed automatically according to the Move Out Date.</li>
				</ul>
			</div>
			<div class="mb-3 col-md-3">
				<label class="form-label" for="fld_move_out_date">Move Out date</label>
				<input type="date" name="move_out_date" value="<?php echo isset($_POST['move_out_date']) ? $_POST['move_out_date'] : sm_date_input($main_info['move_out_date']) ?>" class="form-control" id="fld_move_out_date">
			</div>
			<div class="mb-3 col-md-6 form-check">
				<input type="hidden" name="reschedule" value="0">
				<label class="form-label" for="fld_reschedule">Reschedule all dates </label>
				<input class="form-check-input" type="checkbox" name="reschedule" value="1" id="fld_reschedule">
				<label class="form-check-label text-muted">Set this checkbox if you want to reschedule all dates with the same Move Out Date.</label>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_move_out_comment">Comment</label>
				<textarea id="fld_move_out_comment" name="move_out_comment" class="form-control"><?php echo isset($_POST['move_out_comment']) ? $_POST['move_out_comment'] : html_encode($main_info['move_out_comment']) ?></textarea>
			</div>
			<div class="mb-3 col-md-3">
				<label class="form-label" for="fld_move_in_date">Move In date</label>
				<input type="date" name="move_in_date" value="<?php echo isset($_POST['move_in_date']) ? $_POST['move_in_date'] : sm_date_input($main_info['move_in_date']) ?>" class="form-control" id="fld_move_in_date">
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_move_in_comment">Move In comment</label>
				<textarea id="fld_move_in_comment" name="move_in_comment" class="form-control"><?php echo isset($_POST['move_in_comment']) ? $_POST['move_in_comment'] : html_encode($main_info['move_in_comment']) ?></textarea>
			</div>
		</div>

		<div class="card-header">
			<h6 class="card-title mb-0">Pre Walk Information</h6>
		</div>
		<div class="card-body">
			<div class="alert alert-info" role="alert">
				<ul>
					<li>Pre Walk Date will be set automatically 10 days before the Move Out Date or set your own date.</li>
				</ul>
			</div>
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_pre_walk_date">Pre-Walk Date</label>
					<input type="date" name="pre_walk_date" value="<?php echo isset($_POST['pre_walk_date']) ? $_POST['pre_walk_date'] : sm_date_input($main_info['pre_walk_date']) ?>" class="form-control" id="fld_pre_walk_date">
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_pre_walk_date">Pre Walk Performed</label>
					<input type="text" name="pre_walk_name" value="<?php echo html_encode($main_info['pre_walk_name']) ?>" list="pre_walk_name" class="form-control">
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_pre_walk_comment">Comment</label>
				<textarea id="fld_pre_walk_comment" name="pre_walk_comment" class="form-control"><?php echo isset($_POST['pre_walk_comment']) ? $_POST['pre_walk_comment'] : html_encode($main_info['pre_walk_comment']) ?></textarea>
			</div>
		</div>

		<div class="card-header">
			<h6 class="card-title mb-0">In-House Information</h6>
		</div>
		<div class="card-body">
			<div class="alert alert-info" role="alert">
				<ul>
					<li>Whenever you change the maintenance or painter date, the previous request sent to the In-House will be canceled, a new request will be created instead.</li>
				</ul>
			</div>
			<div class="section-header">
				<h6 class="mb-0">Maintenance</h6>
			</div>
			<div class="row mb-3">
				<div class="col-md-3" id="technician_start_date_3">
					<label class="form-label" for="fld_maint_start_date">Maintenance Date</label>
					<input type="date" name="maint_start_date" value="<?php echo isset($_POST['maint_start_date']) ? $_POST['maint_start_date'] : sm_date_input($vendors_schedule_info[8]['date_time']) ?>" class="form-control" id="fld_maint_start_date">
				</div>
				<div class="col-md-2">
					<label for="fld_maint_shift" class="form-label">Shift</label>
					<select name="maint_shift" id="fld_maint_shift" class="form-select">
<?php
foreach ($time_slots as $key => $val)
{
	if ($vendors_schedule_info[8]['shift'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
}
?>
					</select>
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_maint_comment">Comment</label>
				<textarea id="fld_maint_comment" name="maint_comment" class="form-control"><?php echo ($vendors_schedule_info[8]['remarks'] != '' ? html_encode($vendors_schedule_info[8]['remarks']) : 'Make Ready') ?></textarea>
			</div>

			<div class="section-header">
				<h6 class="mb-0">Painter</h6>
			</div>
			<div class="form-check form-check-inline">
				<input class="form-check-input" type="radio" name="paint_inhouse" id="fld_paint_inhouse0" value="0" checked onchange="inHousePainter(0)">
				<label class="form-check-label" for="fld_paint_inhouse0">Vendor</label>
			</div>
			<div class="form-check form-check-inline">
				<input class="form-check-input" type="radio" name="paint_inhouse" id="fld_paint_inhouse1" value="1" onchange="inHousePainter(1)" <?php echo ($main_info['paint_inhouse'] == 1) ? 'checked' : '' ?>>
				<label class="form-check-label" for="fld_paint_inhouse1">In-House Painter</label>
			</div>

			<div class="mb-3 col-md-4" id="painter_vendors" <?php echo ($main_info['paint_inhouse'] == 1) ? 'style="display:none"' : '' ?>>
				<label for="fld_paint_vendor_id" class="form-label">Vendor list</label>
				<select name="paint_vendor_id" id="fld_paint_vendor_id" class="form-select">
<?php
echo "\t\t\t\t\t\t\t".'<option value="0" selected>Any vendor</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($vendors_schedule_info[2]['vendor_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
}
?>
				</select>
			</div>
			<div class="row">
				<div class="col-md-3">
					<label class="form-label" for="fld_paint_start_date">Painter Date</label>
					<input type="date" name="paint_start_date" value="<?php echo isset($_POST['paint_start_date']) ? $_POST['paint_start_date'] : sm_date_input($vendors_schedule_info[2]['date_time']) ?>" class="form-control" id="fld_paint_start_date">
					<label><?php echo $FormatDateTime->dayOfWeek($vendors_schedule_info[2]['date_time']) ?></label>
				</div>
				<div class="col-md-2">
					<label for="fld_paint_shift" class="form-label">Shift</label>
					<select name="paint_shift" id="fld_paint_shift" class="form-select">
<?php
foreach ($time_slots as $key => $val)
{
	if ($vendors_schedule_info[2]['shift'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
}
?>
					</select>
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_paint_comment">Comment</label>
				<textarea id="fld_paint_comment" name="paint_comment" class="form-control"><?php echo !empty($vendors_schedule_info[2]['remarks']) ? html_encode($vendors_schedule_info[2]['remarks']) : 'Paint Apartment' ?></textarea>
			</div>
		</div>

		<div class="card-header">
			<h6 class="card-title mb-0">Services</h6>
		</div>
		<div class="card-body">
			<div class="alert alert-info" role="alert">
				<ul>
					<li>In case of Vinyl or Carpet is "Replaced", the date of Cleaning Service scheduled after Vinyl and Carpet Services.</li>
					<li>Only Vinyl, Carpet or Cleaning can be scheduled for Saturday.</li>
				</ul>
			</div>
			<div class="form-check">
				<input type="hidden" name="urine_scan" value="0">
				<input type="checkbox" name="urine_scan" value="1" <?php echo ($main_info['urine_scan'] == 1) ? 'checked' : '' ?> class="form-check-input" id="fld_urine_scan">
				<label class="form-check-label" for="fld_urine_scan">Urine Scan</label>
			</div>
			<div class="section-header">
				<h6 class="mb-0">Urine Scan Service</h6>
			</div>
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_urine_scan_date">Start Date</label>
					<input type="date" name="urine_scan_date" value="<?php echo isset($_POST['urine_scan_date']) ? $_POST['urine_scan_date'] : sm_date_input($vendors_schedule_info[1]['date_time']) ?>" class="form-control" id="fld_urine_scan_date">
					<?php echo $FormatDateTime->dayOfWeek($vendors_schedule_info[1]['date_time']) ?>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_urine_scan_vendor_id">Vendor list</label>
					<select name="urine_scan_vendor_id" id="fld_urine_scan_vendor_id" class="form-select">
<?php
echo '<option value="0" selected>Any Vendor</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($vendors_schedule_info[1]['vendor_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
}
?>
					</select>
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_paint_comment">Comment</label>
				<textarea id="fld_paint_comment" name="urine_scan_comment" class="form-control"><?php echo !empty($vendors_schedule_info[1]['remarks']) ? html_encode($vendors_schedule_info[1]['remarks']) : 'Urine Scan' ?></textarea>
			</div>

<?php if ($main_info['vinyl_replaced'] == 0 && $main_info['crpt_replaced'] == 0) : ?>
			<div class="section-header">
				<h6 class="mb-0">Cleaning Service</h6>
			</div>
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_clean_date">Start Date</label>
					<input type="date" name="clean_date" value="<?php echo isset($_POST['clean_date']) ? $_POST['clean_date'] : sm_date_input($vendors_schedule_info[6]['date_time']) ?>" class="form-control" id="fld_clean_date">
					<?php echo $FormatDateTime->dayOfWeek($vendors_schedule_info[6]['date_time']) ?>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_clean_vendor_id">Vendor list</label>
					<select name="clean_vendor_id" id="fld_clean_vendor_id" class="form-select">
<?php
echo '<option value="0" selected>Any Vendor</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($vendors_schedule_info[6]['vendor_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
}
?>
					</select>
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_clean_comment">Comment</label>
				<textarea id="fld_clean_comment" name="clean_comment" class="form-control"><?php echo !empty($vendors_schedule_info[6]['remarks']) ? html_encode($vendors_schedule_info[6]['remarks']) : 'Clean Apartment' ?></textarea>
			</div>
<?php endif; ?>

			<div class="section-header">
				<h6 class="mb-0">Carpet Service</h6>
			</div>
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_crpt_date">Start date</label>
					<input type="date" name="crpt_date" value="<?php echo isset($_POST['crpt_date']) ? $_POST['crpt_date'] : sm_date_input($vendors_schedule_info[4]['date_time']) ?>" class="form-control" id="fld_crpt_date">
					<?php echo $FormatDateTime->dayOfWeek($vendors_schedule_info[4]['date_time']) ?>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_crpt_vendor_id">Vendor list</label>
					<select name="crpt_vendor_id" id="fld_crpt_vendor_id" class="form-select">
<?php
echo '<option value="0" selected>Any Vendor</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($vendors_schedule_info[4]['vendor_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
}
?>
					</select>
				</div>
			</div>
			<div class="form-check form-check-inline">
				<input type="hidden" name="crpt_replaced" value="0">
				<input class="form-check-input" type="checkbox" name="crpt_replaced" id="fld_crpt_replaced" value="1" <?php echo ($main_info['crpt_replaced'] == 1) ? 'checked="checked"' : '' ?>  onchange="checkComment(this, 'crpt_comment')">
				<label class="form-check-label" for="fld_crpt_replaced">Replace</label>
			</div>
			<div class="form-check form-check-inline">
				<input type="hidden" name="crpt_repair" value="0">
				<input class="form-check-input" type="checkbox" name="crpt_repair" id="fld_crpt_repair" value="1" <?php echo ($main_info['crpt_repair'] == 1) ? 'checked="checked"' : '' ?>>
				<label class="form-check-label" for="fld_crpt_repair">Rapair</label>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_crpt_comment">Comment</label>
				<textarea id="fld_crpt_comment" name="crpt_comment" class="form-control"><?php echo !empty($vendors_schedule_info[4]['remarks']) ? html_encode($vendors_schedule_info[4]['remarks']) : (($main_info['crpt_replaced'] == 1) ? 'Replace Carpet' : '') ?></textarea>
			</div>

			<div class="section-header">
				<h6 class="mb-0">Carpet Clean</h6>
			</div>
			<div class="alert alert-info" role="alert">
				<ul class="info-list">
					<li>Carpet Clean Date scheduled if <strong>Carpet Replace is not required</strong>.</li>
				</ul>
			</div>
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_crpt_clean_date">Start date</label>
					<input type="date" name="crpt_clean_date" value="<?php echo isset($_POST['crpt_clean_date']) ? $_POST['crpt_clean_date'] : sm_date_input($vendors_schedule_info[9]['date_time']) ?>" class="form-control" id="fld_crpt_clean_date">
					<?php echo $FormatDateTime->dayOfWeek($vendors_schedule_info[9]['date_time']) ?>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_crpt_clean_vendor_id">Vendor list</label>
					<select name="crpt_clean_vendor_id" id="fld_crpt_clean_vendor_id" class="form-select">
<?php
echo '<option value="0" selected>Any Vendor</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($vendors_schedule_info[9]['vendor_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
}
?>
					</select>
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_crpt_clean_comment">Comment</label>
				<textarea id="fld_crpt_clean_comment" name="crpt_clean_comment" class="form-control"><?php echo !empty($vendors_schedule_info[9]['remarks']) ? html_encode($vendors_schedule_info[9]['remarks']) : 'Carpet Clean' ?></textarea>
			</div>

			<div class="section-header">
				<h6 class="mb-0">Vinyl Service</h6>
			</div>
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_vinyl_date">Start date</label>
					<input type="date" name="vinyl_date" value="<?php echo isset($_POST['vinyl_date']) ? $_POST['vinyl_date'] : sm_date_input($vendors_schedule_info[3]['date_time']) ?>" class="form-control" id="fld_vinyl_date">
					<?php echo $FormatDateTime->dayOfWeek($vendors_schedule_info[3]['date_time']) ?>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_vinyl_vendor_id">Vendor list</label>
					<select name="vinyl_vendor_id" id="fld_vinyl_vendor_id" class="form-select">
<?php
echo '<option value="0" selected>Any Vendor</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($vendors_schedule_info[3]['vendor_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
}
?>
					</select>
				</div>
			</div>
			<div class="form-check form-check-inline">
				<input type="hidden" name="vinyl_replaced" value="0">
				<input class="form-check-input" type="checkbox" name="vinyl_replaced" id="fld_vinyl_replaced" value="1" <?php echo ($main_info['vinyl_replaced'] == 1) ? 'checked="checked"' : '' ?> onchange="checkComment(this, 'vinyl_comment')">
				<label class="form-check-label" for="fld_vinyl_replaced">Replace</label>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_vinyl_comment">Comment</label>
				<textarea id="fld_vinyl_comment" name="vinyl_comment" class="form-control"><?php echo !empty($vendors_schedule_info[3]['remarks']) ? html_encode($vendors_schedule_info[3]['remarks']) : (($main_info['vinyl_replaced'] == 1) ? 'Replace Vinyl' : '') ?></textarea>
			</div>
			
<?php if ($main_info['vinyl_replaced'] == 1 || $main_info['crpt_replaced'] == 1) : ?>
			<div class="section-header">
				<h6 class="mb-0">Cleaning Service</h6>
			</div>
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_clean_date">Start Date</label>
					<input type="date" name="clean_date" value="<?php echo isset($_POST['clean_date']) ? $_POST['clean_date'] : sm_date_input($vendors_schedule_info[6]['date_time']) ?>" class="form-control" id="fld_clean_date">
					<?php echo $FormatDateTime->dayOfWeek($vendors_schedule_info[6]['date_time']) ?>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_clean_vendor_id">Vendor list</label>
					<select name="clean_vendor_id" id="fld_clean_vendor_id" class="form-select">
<?php
echo '<option value="0" selected>Any Vendor</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($vendors_schedule_info[6]['vendor_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
}
?>
					</select>
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_clean_comment">Comment</label>
				<textarea id="fld_clean_comment" name="clean_comment" class="form-control"><?php echo !empty($vendors_schedule_info[6]['remarks']) ? html_encode($vendors_schedule_info[6]['remarks']) : 'Clean Apartment' ?></textarea>
			</div>
<?php endif; ?>

			<div class="section-header">
				<h6 class="mb-0">Pest Control Service</h6>
			</div>
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_pest_date">Start Date</label>
					<input type="date" name="pest_date" value="<?php echo isset($_POST['pest_date']) ? $_POST['pest_date'] : sm_date_input($vendors_schedule_info[5]['date_time']) ?>" class="form-control" id="fld_pest_date">
					<?php echo $FormatDateTime->dayOfWeek($vendors_schedule_info[5]['date_time']) ?>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_pest_vendor_id">Vendor list</label>
					<select name="pest_vendor_id" id="fld_pest_vendor_id" class="form-select">
<?php
echo '<option value="0" selected>Any Vendor</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($vendors_schedule_info[5]['vendor_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
}
?>
					</select>
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_pest_comment">Comment</label>
				<textarea id="fld_pest_comment" name="pest_comment" class="form-control"><?php echo ($vendors_schedule_info[5]['remarks'] != '' ? html_encode($vendors_schedule_info[5]['remarks']) : 'General Service') ?></textarea>
			</div>

			<div class="section-header">
				<h6 class="mb-0">Refinish Service</h6>
			</div>
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_refinish_start_date">Start date</label>
					<input type="date" name="refinish_start_date" value="<?php echo isset($_POST['refinish_start_date']) ? $_POST['refinish_start_date'] : sm_date_input($vendors_schedule_info[7]['date_time']) ?>" class="form-control" id="fld_refinish_start_date">
					<?php echo $FormatDateTime->dayOfWeek($vendors_schedule_info[7]['date_time']) ?>
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_refinish_vendor_id">Vendor list</label>
					<select name="refinish_vendor_id" id="fld_refinish_vendor_id" class="form-select">
<?php
echo '<option value="0" selected>Any Vendor</option>'."\n";
foreach ($vendors_info as $cur_info)
{
	if ($vendors_schedule_info[7]['vendor_id'] == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['vendor_name']).'</option>'."\n";
}
?>
					</select>
				</div>
			</div>
			<div class="form-check form-check-inline">
				<input type="hidden" name="refinish_check" value="0">
				<input class="form-check-input" type="checkbox" name="refinish_check" id="fld_refinish_check" value="1" <?php echo ($main_info['refinish_check'] == 1) ? 'checked' : '' ?>>
				<label class="form-check-label" for="fld_refinish_check">Check this box if Decent or Fail</label>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_refinish_comment">Comment</label>
				<textarea id="fld_refinish_comment" name="refinish_comment" class="form-control"><?php echo isset($_POST['refinish_comment']) ? html_encode($_POST['refinish_comment']) : html_encode($vendors_schedule_info[7]['remarks']) ?></textarea>
			</div>
		</div>

		<div class="card-header">
			<h6 class="card-title mb-0">Final Walk Information</h6>
		</div>
		<div class="card-body">
			<div class="alert alert-info" role="alert">
				<ul>
					<li>The default Inspection Date is the same as the Cleaning Date, except for Saturday.</li>
				</ul>
			</div>
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_walk_date">Start date</label>
					<input type="date" name="walk_date" value="<?php echo isset($_POST['walk_date']) ? $_POST['walk_date'] : sm_date_input($main_info['walk_date']) ?>" class="form-control" id="fld_walk_date">
				</div>
				<div class="col-md-4">
					<label class="form-label" for="fld_walk">Inspector Name</label>
					<input class="form-control" type="text" name="walk" value="<?php echo html_encode($main_info['walk']) ?>" list="walk_list" id="fld_walk">

					<label>To edit the name of the inspector, first clear the field, then click the dropdown again.</label>
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_walk_comment">Comment</label>
				<textarea id="fld_walk_comment" name="walk_comment" class="form-control"><?php echo html_encode($main_info['walk_comment']) ?></textarea>
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_remarks">Remarks</label>
				<textarea id="fld_remarks" name="remarks" class="form-control"><?php echo html_encode($main_info['remarks']) ?></textarea>
			</div>

			<button type="submit" name="form_sent" class="btn btn-primary">Update</button>
			<?php if ($User->checkAccess('hca_vcr', 4)): ?>
			<button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to remove this project?')">Delete</button>
			<?php endif; ?>

		</div>
	</div>
</form>

<div class="modal fade" id="modalWindow" tabindex="-1" aria-labelledby="modalWindowLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="modal-header">
					<h5 class="modal-title">Edit information</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<!--modal_fields-->
				</div>
				<div class="modal-footer">
					<!--modal_buttons-->
				</div>
			</form>
		</div>
	</div>
</div>

<script>
function inHousePainter(i){
	if (i==1) {
		$("#painter_vendors").css('display', 'none');
		$("#painter_vendors select").val(0);
	} else {
		$("#painter_vendors").css('display', 'block');
	}
}
function checkComment(e,id){
	var c = $('#fld_'+id).val();
	if (e.checked) {
		if (c == 'Clean Carpet')
			$('#fld_'+id).val("Replace Carpet");
		else if (id == 'vinyl_comment')
			$('#fld_'+id).val("Replace Vinyl");
	} else {
		if (c == 'Replace Carpet')
			$('#fld_'+id).val("Clean Carpet");
	}
}
</script>

<?php
require SITE_ROOT.'footer.php';