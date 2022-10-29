<?php

// 1 - Urine Scan
// 2 - Painter Vendor or In house
// 3 - Vinyl
// 4 - Carpet
// 5 - Pest
// 6 - Cleaning Service
// 7 - Refinish
// 8 - Maintenance
// 9 - Carpet Clean

class HCAVCRProjects
{
	function insertVendor()
	{
		global $DBLayer, $User, $SwiftMailer;

		$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
		$invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
		$in_house = isset($_POST['in_house']) ? intval($_POST['in_house']) : 0;

		$form_data = [];
		$form_data['vendor_group_id'] = isset($_POST['vendor_group_id']) ? intval($_POST['vendor_group_id']) : 0;
		$form_data['vendor_id'] = isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : 0;
		$form_data['in_house'] = isset($_POST['in_house']) ? intval($_POST['in_house']) : 0;
		$form_data['shift'] = isset($_POST['shift']) ? intval($_POST['shift']) : 0;
		$form_data['date_time'] = isset($_POST['date_time']) ? strtotime($_POST['date_time']) : 0;
		$form_data['remarks'] = isset($_POST['remarks']) ? swift_trim($_POST['remarks']) : 0;
		$form_data['project_id'] = $project_id;
		$form_data['project_name'] = 'hca_vcr_projects';

		// Send request to In-House
		if ($in_house == 1)
		{
			$project_info = $DBLayer->select('hca_vcr_projects', $project_id);

			$fs_data = array(
				'property_id'		=> $project_info['property_id'],
				'unit_number'		=> $project_info['unit_number'],
				'group_id'			=> ($form_data['vendor_group_id'] == 2) ? 9 : 3, // 9 - InHouse Painter // 3 - Maintenance
				'time_slot'			=> $form_data['shift'],
				'request_msg'		=> $form_data['remarks'],
				'msg_for_maint'		=> $form_data['remarks'],
				'created'			=> time(),
				'scheduled'			=> date('Ymd', $form_data['date_time']),
				'start_date'		=> $form_data['date_time'],
				'week_of'			=> ($form_data['date_time'] > 0) ? strtotime('Monday this week', $form_data['date_time']) : 0,
				'execution_priority'=> 2,// hight
				'permission_enter'	=> 1,
				'requested_by'		=> $User->get('realname'),
				'template_type'		=> 2,
			);

			// If technician has been selected
			if ($form_data['vendor_id'] > 0)
			{
				$fs_data['employee_id'] = $form_data['vendor_id'];
				$fs_data['work_status'] = 1;
			}
			$new_fs_req = $DBLayer->insert_values('hca_fs_requests', $fs_data);
			
			
			// HOOK START
			include SITE_ROOT.'apps/punch_list_management/classes/PunchList.php';
			$PunchList = new PunchList;

			$punch_data = [
				'start_date'	=> $form_data['date_time'],
				'property_id'	=> $project_info['property_id'],
				'unit_number'	=> $project_info['unit_number'],
				'employee_id'	=> isset($fs_data['employee_id']) ? $fs_data['employee_id'] : 0
			];

			if ($form_data['vendor_group_id'] == 2)
				$punch_form_id = $PunchList->createPainterForm($punch_data);
			else
				$punch_form_id = $PunchList->createMaintForm($punch_data);

			if ($punch_form_id)
			{
				$query = array(
					'UPDATE'	=> 'hca_fs_requests',
					'SET'		=> 'punch_form_id='.$punch_form_id,
					'WHERE'		=> 'id='.$new_fs_req
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			}
			// HOOK END


			if ($form_data['vendor_group_id'] == 2)
				$emails = $this->getEmails(4); // check Painter Managers
			else
				$emails = $this->getEmails(3);
			
			// Send Email to Tech Manager
			if (!empty($emails))
			{
				$property_info = $DBLayer->select('sm_property_db', $project_info['property_id']);

				$mail_message = [];
				if ($form_data['vendor_id'] > 0)
					$mail_message[] = 'Property request #'.$new_fs_req.' has been created and placed in the Weekly Schedule.'."\n";
				else
					$mail_message[] = 'Property request #'.$new_fs_req.' has been created. Please add this to the Weekly Schedule.'."\n";

				$mail_message[] = 'Property name: '.$property_info['pro_name'];
				$mail_message[] = 'Unit #: '.$project_info['unit_number'];
				$mail_message[] = 'Comment: '.$form_data['remarks'];
				$mail_message[] = 'Submitted by: '.$User->get('realname');

				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->send(implode(',', $emails), 'A new property request', implode("\n", $mail_message));
			}

			$form_data['fs_request_id'] = $new_fs_req;
		}

		$DBLayer->insert('hca_vcr_invoices', $form_data);
	}

	function updateVendor()
	{
		global $DBLayer, $User, $SwiftMailer;

		$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
		$invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
		$in_house = isset($_POST['in_house']) ? intval($_POST['in_house']) : 0;

		$form_data = $mail_message = [];
		$form_data['vendor_group_id'] = isset($_POST['vendor_group_id']) ? intval($_POST['vendor_group_id']) : 0;
		$form_data['vendor_id'] = isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : 0;
		$form_data['in_house'] = isset($_POST['in_house']) ? intval($_POST['in_house']) : 0;
		$form_data['shift'] = isset($_POST['shift']) ? intval($_POST['shift']) : 0;
		$form_data['date_time'] = isset($_POST['date_time']) ? strtotime($_POST['date_time']) : 0;
		$form_data['remarks'] = isset($_POST['remarks']) ? swift_trim($_POST['remarks']) : 0;

		$project_info = $DBLayer->select('hca_vcr_projects', $project_id);
		$invoice_info = $DBLayer->select('hca_vcr_invoices', $invoice_id);

		// Update: Prew = In-House to Current = In-House
		if ($invoice_info['in_house'] == 1 && $in_house == 1)
		{
			$mail_message = [];
			if ($invoice_info['fs_request_id'] > 0)
			{
				$DBLayer->update('hca_fs_requests', ['work_status' => 5], $invoice_info['fs_request_id']);
				$mail_message[] = 'Property request #'.$invoice_info['fs_request_id'].' has been canceled.'."\n";

				$form_data['fs_request_id'] = 0;
			}

			if ($form_data['date_time'] > 0)
			{
				$fs_data = array(
					'property_id'		=> $project_info['property_id'],
					'unit_number'		=> $project_info['unit_number'],
					'group_id'			=> ($form_data['vendor_group_id'] == 2) ? 9 : 3, // 9 - InHouse Painter // 3 - Maintenance
					'time_slot'			=> $form_data['shift'],
					'request_msg'		=> $form_data['remarks'],
					'msg_for_maint'		=> $form_data['remarks'],
					'created'			=> time(),
					'scheduled'			=> date('Ymd', $form_data['date_time']),
					'start_date'		=> $form_data['date_time'],
					'week_of'			=> ($form_data['date_time'] > 0) ? strtotime('Monday this week', $form_data['date_time']) : 0,
					'execution_priority'=> 2,// hight
					'permission_enter'	=> 1,
					'requested_by'		=> $User->get('realname'),
					'template_type'		=> 2,
				);

				// If technician has been selected
				if ($form_data['vendor_id'] > 0)
				{
					$fs_data['employee_id'] = $form_data['vendor_id'];
					$fs_data['work_status'] = 1;
				}
				$new_fs_req = $DBLayer->insert_values('hca_fs_requests', $fs_data);

				$form_data['fs_request_id'] = $new_fs_req;
			}

			if ($form_data['vendor_group_id'] == 2)
				$emails = $this->getEmails(4); // check Painter Managers
			else
				$emails = $this->getEmails(3);

			// Send Email to Tech Manager
			if (!empty($emails) && isset($form_data['fs_request_id']))
			{
				$property_info = $DBLayer->select('sm_property_db', $project_info['property_id']);

				if ($form_data['vendor_id'] > 0)
					$mail_message[] = 'Property request #'.$new_fs_req.' has been re-created and placed in the Weekly Schedule.'."\n";
				else
					$mail_message[] = 'Property request #'.$new_fs_req.' has been re-created. Please add this to the Weekly Schedule.'."\n";

				$mail_message[] = 'Property name: '.$property_info['pro_name'];
				$mail_message[] = 'Unit #: '.$project_info['unit_number'];
				$mail_message[] = 'Comment: '.$form_data['remarks'];
				$mail_message[] = 'Submitted by: '.$User->get('realname');

				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->send(implode(',', $emails), 'A new property request', implode("\n", $mail_message));
			}
		}
		// Update: Prew = Vendor to Current = In-House
		else if ($invoice_info['in_house'] == 0 && $in_house == 1)
		{
			if ($form_data['date_time'] > 0)
			{
				$fs_data = array(
					'property_id'		=> $project_info['property_id'],
					'unit_number'		=> $project_info['unit_number'],
					'group_id'			=> ($form_data['vendor_group_id'] == 2) ? 9 : 3, // 9 - InHouse Painter // 3 - Maintenance
					'time_slot'			=> $form_data['shift'],
					'request_msg'		=> $form_data['remarks'],
					'msg_for_maint'		=> $form_data['remarks'],
					'created'			=> time(),
					'scheduled'			=> date('Ymd', $form_data['date_time']),
					'start_date'		=> $form_data['date_time'],
					'week_of'			=> ($form_data['date_time'] > 0) ? strtotime('Monday this week', $form_data['date_time']) : 0,
					'execution_priority'=> 2,// hight
					'permission_enter'	=> 1,
					'requested_by'		=> $User->get('realname'),
					'template_type'		=> 2,
				);

				// If technician has been selected
				if ($form_data['vendor_id'] > 0)
				{
					$fs_data['employee_id'] = $form_data['vendor_id'];
					$fs_data['work_status'] = 1;
				}

				$new_fs_req = $DBLayer->insert_values('hca_fs_requests', $fs_data);
				$form_data['fs_request_id'] = $new_fs_req;

				if ($form_data['vendor_group_id'] == 2)
					$emails = $this->getEmails(4); // check Painter Managers
				else
					$emails = $this->getEmails(3);

				// Send Email to Tech Manager
				if (!empty($emails))
				{
					$property_info = $DBLayer->select('sm_property_db', $project_info['property_id']);

					$mail_message = [];

					if ($form_data['vendor_id'] > 0)
						$mail_message[] = 'Property request #'.$new_fs_req.' has been created and placed in the Weekly Schedule.'."\n";
					else
						$mail_message[] = 'Property request #'.$new_fs_req.' has been created. Please add this to the Weekly Schedule.'."\n";

					$mail_message[] = 'Property name: '.$property_info['pro_name'];
					$mail_message[] = 'Unit #: '.$project_info['unit_number'];
					$mail_message[] = 'Comment: '.$form_data['remarks'];
					$mail_message[] = 'Submitted by: '.$User->get('realname');

					$SwiftMailer = new SwiftMailer;
					$SwiftMailer->send(implode(',', $emails), 'A new property request', implode("\n", $mail_message));
				}
			}
		}
		// Update: Prew = In-House to Current = Vendor
		else if ($invoice_info['in_house'] == 1 && $in_house == 0)
		{
			if ($invoice_info['fs_request_id'] > 0)
			{
				$DBLayer->update('hca_fs_requests', ['work_status' => 5], $invoice_info['fs_request_id']);

				if ($form_data['vendor_group_id'] == 2)
					$emails = $this->getEmails(4); // check Painter Managers
				else
					$emails = $this->getEmails(3);

				// Send Email to Tech Manager
				if (!empty($emails))
				{
					$property_info = $DBLayer->select('sm_property_db', $project_info['property_id']);

					$mail_message = [];
					$mail_message[] = 'Property request #'.$invoice_info['fs_request_id'].' has been canceled.'."\n";
					$mail_message[] = 'Property name: '.$property_info['pro_name'];
					$mail_message[] = 'Unit #: '.$project_info['unit_number'];
					$mail_message[] = 'Comment: '.$form_data['remarks'];
					$mail_message[] = 'Submitted by: '.$User->get('realname');

					$SwiftMailer = new SwiftMailer;
					$SwiftMailer->send(implode(',', $emails), 'Property request canceled', implode("\n", $mail_message));
				}

				$form_data['fs_request_id'] = 0;
			}
		}

		$DBLayer->update('hca_vcr_invoices', $form_data, $invoice_id);
	}

	function getEmails($key = 0)
	{
		global $DBLayer;

		$query = [
			'SELECT'	=> 'n.*, u.email',
			'FROM'		=> 'user_notifications AS n',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'u.id=n.n_uid'
				],
			],
			'WHERE'		=> 'n.n_to=\'hca_vcr\' AND n.n_key='.$key.' AND n.n_value=1',
			//'WHERE'		=> 'n.n_to=\'hca_vcr\'',
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$emails = [];
		while ($row = $DBLayer->fetch_assoc($result))
		{
			$emails[$row['n_uid']] = $row['email'];
		}

		return $emails;
	}
}
