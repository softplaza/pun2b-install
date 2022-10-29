<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_ui', 2)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$HcaUIAppendixB = new HcaUIAppendixB;
$HcaUnitInspection = new HcaUnitInspection;
$SwiftUploader = new SwiftUploader;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message('Sorry, this Special Project does not exist or has been removed.');

$query = array(
	'SELECT'	=> 'ch.*, p.pro_name, u.unit_number, u.hbath',
	'FROM'		=> 'hca_ui_checklist AS ch',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=ch.property_id'
		],
		[
			'INNER JOIN'	=> 'sm_property_units AS u',
			'ON'			=> 'u.id=ch.unit_id'
		],
	],
	'WHERE'		=> 'ch.id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_info = $DBLayer->fetch_assoc($result);

$query = [
	'SELECT'	=> 'ci.*, i.item_name, i.location_id',
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
	],
	'WHERE'		=> 'ch.id='.$id,
	'ORDER BY'	=> 'i.display_position'
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$checked_items = [];
while($row = $DBLayer->fetch_assoc($result))
{
	$checked_items[] = $row;
}

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_property_db',
	'WHERE'		=> 'id='.$project_info['property_id'],
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = $DBLayer->fetch_assoc($result);

if (isset($_POST['create_pdf']) && isset($_POST['form']))
{
	$form_info = $_POST['form'];
	$form_info['reported_time'] = isset($_POST['reported_time']) ? strtotime($_POST['reported_time']) : 0;
	$form_info['performed_by'] = $User->get('realname');
	$html_content = $HcaUIAppendixB->gen_appendix_b($form_info, $project_info);
	
	$mpdf = new \Mpdf\Mpdf();
	$mpdf->WriteHTML($html_content);
	
	$time_now = time();
	$assoc_table_name = 'hca_ui_checklist';
	//$file_path = 'uploads/'.$assoc_table_name.'/'.date('Y', $time_now).'/'.date('m', $time_now).'/';
	//$Uploader = new smUploader;
	//$Uploader->check_path($assoc_table_name);
	$file_path = $SwiftUploader->checkPath('hca_ui_checklist').'/';

	$base_filename = 'apendix_b_'.date('ymd', $time_now).'.pdf';
	$new_filename = 'apendix_b_'.date('ymd_His', $time_now).'.pdf';
	$full_file_path = SITE_ROOT . $file_path . $new_filename;
	$mpdf->Output($full_file_path, 'F');

	if (file_exists($full_file_path))
	{
		$file_size = filesize($full_file_path);
		$query = array(
			'INSERT'	=> 'user_id, user_name, file_name, base_name, file_type, file_ext, file_path, file_size, load_time, table_name, table_id',
			'INTO'		=> 'sm_uploader',
			'VALUES'	=> '\''.$DBLayer->escape($User->get('id')).'\',
				\''.$DBLayer->escape($User->get('realname')).'\',
				\''.$DBLayer->escape($new_filename).'\',
				\''.$DBLayer->escape($base_filename).'\',
				\'file\',
				\'pdf\',
				\''.$DBLayer->escape($file_path).'\',
				\''.$DBLayer->escape($file_size).'\',
				'.$time_now.',
				\''.$DBLayer->escape($assoc_table_name).'\',
				'.$id.''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		$form_data = array('appendixb' => 1);
		$DBLayer->update('hca_ui_checklist', $form_data, $id);
		
		$mail_message = [];
		$mail_message[] = 'Appendix-B was created.'."\n";
		$mail_message[] = 'Property name: '.$project_info['pro_name'];
		$mail_message[] = 'Unit #: '.$project_info['unit_number'];
		$mail_message[] = 'Submitted by: '.$User->get('realname');
		if ($form_info['action'] != '')
			$mail_message[] = 'Comment: '.$form_info['action'];
		$mail_message[] = "\n";
		$mail_message[] = 'To download Appendix-B follow this link:';
		$mail_message[] = BASE_URL .'/'. $file_path . $new_filename;

		$emails = $User->getNotifyEmails('hca_ui', 1); // 1 - appendix-b created

		if (!empty($property_info['manager_email']))
			$emails[] = $property_info['manager_email'];

		if (!empty($emails))
		{
			$SwiftMailer = new SwiftMailer;
			$SwiftMailer->send(implode(',', $emails), 'Plumbing Inspection: Appendix-B', implode("\n", $mail_message));
		}

		$flash_message = 'Appendix-B was created by '.$User->get('realname');

		$action_data = [
			'checklist_id'			=> $id,
			'submitted_by'			=> $User->get('id'),
			'time_submitted'		=> time(),
			'action'				=> $flash_message
		];
		$DBLayer->insert_values('hca_ui_actions', $action_data);

		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_ui_files', $id), $flash_message);
	}
	else
		$Core->add_error('Cannot create file.');
}

$inspector_init_arr = explode(' ', $User->get('realname'));
$inspector_init = isset($inspector_init_arr[0]) ? substr($inspector_init_arr[0], 0, 1).'.' : '';
$inspector_init .= isset($inspector_init_arr[1]) ? substr($inspector_init_arr[1], 0, 1).'.' : '';

$Core->set_page_id('hca_ui_appendixb', 'hca_ui');
require SITE_ROOT.'header.php';

?>
<style>
p{font-size:1.2em;}
.pdf-content td{background: white;padding: .3em .417em;}
.header td{padding: .5em .417em;}
.content-head{color:red;font-weight:bold;}
.pdf-title h2{text-align:center;font-weight: bold;font-size: 1.4em;}
.pdf-boxes{margin-top:10px;}
.pdf-box{display: flex;border: 1px solid #80878b;padding: 2px;width: 350px;}
.box-title{display: flex;}
.box-desc{font-weight: bold;}
.pdf-head{margin-top: 20px;}
.td2, .td3, .td4, .td5{width:30px;text-align: center;}
.td6{width: 150px;}
.td6 input{text-align: center;}
.td7{width: 250px;}
.td6 input, .td7 input, .td2-7 input{width:97%;}
.td2-4{max-width:30px;}
.td2-7{width:65%;}
.pdf-content .td-txt{padding: 8px;}
.head-title{margin-right:30px;font-size: 1.2em}
.head-title p{font-weight: bold;}
.head-info{float:right;border: 3px solid #ee0b0b;padding-left: 15px;font-style: italic;font-size: 1.2em;}
.loc-info td input{width:95%;}
</style>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Appendix B - Internal Moisture Intrusion Checklist</h6>
		</div>
		<div class="card-body">

			<div class="pdf-content">
				<div class="pdf-head">
					<p><strong>Perform as soon as possible after moisture intrusion problems are reported.</strong></p>
				</div>
				<table>
					<tbody>
						<tr class="header">
							<td>Property Name:&nbsp;&nbsp;<span class="box-desc"> <?php echo $project_info['pro_name'] ?></span></td>
							<td class="td2-4"></td>
							<td>Unit#&nbsp;&nbsp;<span class="box-desc"> <?php echo $project_info['unit_number'] ?></span></td>
						</tr>
						<tr>
							<td><span class="box-title">Date Reported:&nbsp;&nbsp;<input type="datetime-local" name="reported_time" value="<?php echo format_date($project_info['date_completed'], 'Y-m-d\TH:i') ?>"></span></td>
							<td class="td2-4"></td>
							<td>Inspector's Name:&nbsp;&nbsp;<span class="box-desc">
								<input value="<?php echo html_encode($User->get('realname')) ?>" disabled>
							</span></td>
						</tr>
					</tbody>
				</table>
				<div class="pdf-head">
					<p><strong>Type of moisture intrusion (clear, grey, black water):</strong></p>
				</div>
				
				<table>
					<tbody>
						<tr class="header">
							<td style="text-align:center" rowspan="4"><strong>Check Only One</strong></td>
							<td colspan="4"></td>
							<td class="td6">Inspector's Initials</td>
							<td class="td7">Comments/Follow-up</td>
						</tr>
						<tr>
							<td class="td2-4" colspan="3">Clear</td>
							<input type="hidden" name="form[mois_type_clear]" value="0">
							<td class="td5"><input type="checkbox" name="form[mois_type_clear]" value="1"></td>
							<td class="td6"><input type="text" name="form[mois_type_clear_init]" value="<?php echo $inspector_init ?>"></td>
							<td class="td7"><input type="text" name="form[mois_type_clear_desc]" value=""></td>
						</tr>
						<tr>
							<td class="td2-4" colspan="3">Grey</td>
							<input type="hidden" name="form[mois_type_grey]" value="0">
							<td class="td5"><input type="checkbox" name="form[mois_type_grey]" value="1"></td>
							<td class="td6"><input type="text" name="form[mois_type_grey_init]" value="<?php echo $inspector_init ?>"></td>
							<td class="td7"><input type="text" name="form[mois_type_grey_desc]" value=""></td>
						</tr>
						<tr>
							<td class="td2-4" colspan="3">Black</td>
							<input type="hidden" name="form[mois_type_black]" value="0">
							<td class="td5"><input type="checkbox" name="form[mois_type_black]" value="1"></td>
							<td class="td6"><input type="text" name="form[mois_type_black_init]" value="<?php echo $inspector_init ?>"></td>
							<td class="td7"><input type="text" name="form[mois_type_black_desc]" value=""></td>
						</tr>
					</tbody>
				</table>
				
				<div class="pdf-head">
					<p><strong>Inspection Item:</strong></p>
				</div>
				<div class="pdf-head">
					<p><strong>Staining/discoloration observed on building materials:</strong></p>
				</div>
				<table>
					<tbody>
						<tr class="header">
							<td colspan="3"></td>
							<td class="td2">Yes</td>
							<td class="td4">No</td>
							<td>Inspector's Initials</td>
							<td>Comments/Follow-up</td>
						</tr>
<?php if ($property_info['attics'] == 1) : ?>
						<tr>
							<td colspan="3">Attics</td>
							<td class="td3"><input type="checkbox" name="form[disc_bldg_attics]" value="1"></td>
							<td class="td5"><input type="checkbox" name="form[disc_bldg_attics]" value="0"></td>
							<td class="td6"><input type="text" name="form[disc_bldg_attics_init]" value="<?php echo $inspector_init ?>"></td>
							<td class="td7"><input type="text" name="form[disc_bldg_attics_desc]" value=""></td>
						</tr>
<?php endif; ?>
						<tr">
							<td colspan="3">Ceiling</td>
							<td class="td3"><input type="checkbox" name="form[disc_bldg_ceilings]" value="1"></td>
							<td class="td5"><input type="checkbox" name="form[disc_bldg_ceilings]" value="0"></td>
							<td class="td6"><input type="text" name="form[disc_bldg_ceilings_init]" value="<?php echo $inspector_init ?>"></td>
							<td class="td7"><input type="text" name="form[disc_bldg_ceilings_desc]" value=""></td>
						</tr>
						<tr>
							<td colspan="3">Walls</td>
							<td class="td3"><input type="checkbox" name="form[disc_bldg_walls]" value="1"></td>
							<td class="td5"><input type="checkbox" name="form[disc_bldg_walls]" value="0"></td>
							<td class="td6"><input type="text" name="form[disc_bldg_walls_init]" value="<?php echo $inspector_init ?>"></td>
							<td class="td7"><input type="text" name="form[disc_bldg_walls_desc]" value=""></td>
						</tr>
						<tr>
							<td colspan="3">Windows</td>
							<td class="td3"><input type="checkbox" name="form[disc_bldg_windows]" value="1"></td>
							<td class="td5"><input type="checkbox" name="form[disc_bldg_windows]" value="0"></td>
							<td class="td6"><input type="text" name="form[disc_bldg_windows_init]" value="<?php echo $inspector_init ?>"></td>
							<td class="td7"><input type="text" name="form[disc_bldg_windows_desc]" value=""></td>
						</tr>
						<tr>
							<td colspan="3">Floor/tack strips</td>
							<td class="td3"><input type="checkbox" name="form[disc_bldg_floors]" value="1"></td>
							<td class="td5"><input type="checkbox" name="form[disc_bldg_floors]" value="0"></td>
							<td class="td6"><input type="text" name="form[disc_bldg_floors_init]" value="<?php echo $inspector_init ?>"></td>
							<td class="td7"><input type="text" name="form[disc_bldg_floors_desc]" value=""></td>
						</tr>
					</tbody>
				</table>
				
				<div class="pdf-head">
					<p><strong>Staining/discoloration observed near utilities:</strong></p>
				</div>
				<table>
					<tbody>
						<tr>
							<td>Toilets</td>
							<td class="td3"><input type="checkbox" name="form[disc_utilit_toilets]" value="1"></td>
							<td class="td5"><input type="checkbox" name="form[disc_utilit_toilets]" value="0"></td>
							<td class="td6"><input type="text" name="form[disc_utilit_toilets_init]" value="<?php echo $inspector_init ?>"></td>
							<td class="td7"><input type="text" name="form[disc_utilit_toilets_desc]" value=""></td>
						</tr>
<?php if ($property_info['washers'] == 1) : ?>
						<tr>
							<td>Washers</td>
							<td class="td3"><input type="checkbox" name="form[disc_utilit_washers]" value="1"></td>
							<td class="td5"><input type="checkbox" name="form[disc_utilit_washers]" value="0"></td>
							<td class="td6"><input type="text" name="form[disc_utilit_washers_init]" value="<?php echo $inspector_init ?>"></td>
							<td class="td7"><input type="text" name="form[disc_utilit_washers_desc]" value=""></td>
						</tr>
<?php endif; ?>
<?php if ($property_info['water_heater'] == 1) : ?>
						<tr>
							<td>Water heaters</td>
							<td class="td3"><input type="checkbox" name="form[disc_utilit_heaters]" value="1"></td>
							<td class="td5"><input type="checkbox" name="form[disc_utilit_heaters]" value="0"></td>
							<td class="td6"><input type="text" name="form[disc_utilit_heaters_init]" value="<?php echo $inspector_init ?>"></td>
							<td class="td7"><input type="text" name="form[disc_utilit_heaters_desc]" value=""></td>
						</tr>
<?php endif; ?>
<?php if ($property_info['furnace'] == 1) : ?>
						<tr>
							<td>Furnace</td>
							<td class="td3"><input type="checkbox" name="form[disc_utilit_furnace]" value="1"></td>
							<td class="td5"><input type="checkbox" name="form[disc_utilit_furnace]" value="0"></td>
							<td class="td6"><input type="text" name="form[disc_utilit_furnace_init]" value="<?php echo $inspector_init ?>"></td>
							<td class="td7"><input type="text" name="form[disc_utilit_furnace_desc]" value=""></td>
						</tr>
<?php endif; ?>
						<tr>
							<td>Sinks</td>
							<td class="td3"><input type="checkbox" name="form[disc_utilit_sinks]" value="1"></td>
							<td class="td5"><input type="checkbox" name="form[disc_utilit_sinks]" value="0"></td>
							<td class="td6"><input type="text" name="form[disc_utilit_sinks_init]" value="<?php echo $inspector_init ?>"></td>
							<td class="td7"><input type="text" name="form[disc_utilit_sinks_desc]" value=""></td>
						</tr>
						<tr>
							<td>Potable water lines</td>
							<td class="td3"><input type="checkbox" name="form[disc_utilit_potable]" value="1"></td>
							<td class="td5"><input type="checkbox" name="form[disc_utilit_potable]" value="0"></td>
							<td class="td6"><input type="text" name="form[disc_utilit_potable_init]" value="<?php echo $inspector_init ?>"></td>
							<td class="td7"><input type="text" name="form[disc_utilit_potable_desc]" value=""></td>
						</tr>
						<tr>
							<td>Drain lines</td>
							<td class="td3"><input type="checkbox" name="form[disc_utilit_drain]" value="1"></td>
							<td class="td5"><input type="checkbox" name="form[disc_utilit_drain]" value="0"></td>
							<td class="td6"><input type="text" name="form[disc_utilit_drain_init]" value="<?php echo $inspector_init ?>"></td>
							<td class="td7"><input type="text" name="form[disc_utilit_drain_desc]" value=""></td>
						</tr>
<?php if ($property_info['hvac'] == 1) : ?>
						<tr>
							<td>HVAC condensate pans/lines</td>
							<td class="td3"><input type="checkbox" name="form[disc_utilit_hvac]" value="1"></td>
							<td class="td5"><input type="checkbox" name="form[disc_utilit_hvac]" value="0"></td>
							<td class="td6"><input type="text" name="form[disc_utilit_hvac_init]" value="<?php echo $inspector_init ?>"></td>
							<td class="td7"><input type="text" name="form[disc_utilit_hvac_desc]" value=""></td>
						</tr>
<?php endif; ?>
					</tbody>
				</table>
				<div class="pdf-head">
					<p><strong>Building material impacted by moisture intrusion:</strong></p>
				</div>
				<table>
					<tbody class="loc-info">
						<tr>
							<td>Location - which room / room(s) etc.</td>
<?php
$locations = $HcaUnitInspection->getLocations();

foreach($locations as $location_id => $location_name)
{
	$ident = false;
	if (!empty($checked_items))
	{
		foreach($checked_items as $cur_info)
		{
			if ($cur_info['location_id'] == $location_id)
			{
				$problem_ids = explode(',', $cur_info['problem_ids']);
				// 5 - discolored, 13 - wet
				if (in_array(5, $problem_ids) || in_array(13, $problem_ids))
					$ident = true;
			}
		}
	}

	if ($ident && ($location_id != 4 || ($location_id == 4 && $project_info['hbath'] == 1)))
		echo '<td class="td-txt"><input type="text" name="form[location'.$location_id.']" value="'.html_encode($location_name).'"></td>'."\n";
}
?>
						</tr>
						<tr>
							<td>Square Footages</td>
<?php
foreach($locations as $location_id => $location_name)
{
	$ident = false;
	if (!empty($checked_items))
	{
		foreach($checked_items as $cur_info)
		{
			if ($cur_info['location_id'] == $location_id)
			{
				$problem_ids = explode(',', $cur_info['problem_ids']);
				// 5 - discolored, 13 - wet
				if (in_array(5, $problem_ids) || in_array(13, $problem_ids))
					$ident = true;
			}
		}
	}

	if ($ident && ($location_id != 4 || ($location_id == 4 && $project_info['hbath'] == 1)))
		echo '<td class="td-txt"><input type="text" name="form[square_footages'.$location_id.']" value=""></td>'."\n";
}
?>
						</tr>
						<tr>
							<td>Wood moisture meter results</td>
<?php
foreach($locations as $location_id => $location_name)
{
	$ident = false;
	if (!empty($checked_items))
	{
		foreach($checked_items as $cur_info)
		{
			if ($cur_info['location_id'] == $location_id)
			{
				$problem_ids = explode(',', $cur_info['problem_ids']);
				// 5 - discolored, 13 - wet
				if (in_array(5, $problem_ids) || in_array(13, $problem_ids))
					$ident = true;
			}
		}
	}

	if ($ident && ($location_id != 4 || ($location_id == 4 && $project_info['hbath'] == 1)))
		echo '<td class="td-txt"><input type="text" name="form[wood_results'.$location_id.']" value=""></td>'."\n";
}
?>
						</tr>
						<tr>
							<td>Concrete moisture meter results</td>
<?php
foreach($locations as $location_id => $location_name)
{
	$ident = false;
	if (!empty($checked_items))
	{
		foreach($checked_items as $cur_info)
		{
			if ($cur_info['location_id'] == $location_id)
			{
				$problem_ids = explode(',', $cur_info['problem_ids']);
				// 5 - discolored, 13 - wet
				if (in_array(5, $problem_ids) || in_array(13, $problem_ids))
					$ident = true;
			}
		}
	}

	if ($ident && ($location_id != 4 || ($location_id == 4 && $project_info['hbath'] == 1)))
		echo '<td class="td-txt"><input type="text" name="form[concrete_results'.$location_id.']" value=""></td>'."\n";
}
?>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="mb-3">
				<label class="form-label" for="form_action">Action:</label>
				<textarea type="text" name="form[action]" class="form-control" id="form_action" placeholder="Leave your comment"></textarea>
			</div>

			<div class="mb-3">
				<button type="submit" name="create_pdf" class="btn btn-primary">Submit</button>
			</div>

		</div>
	</div>
</form>

<?php
$js = '$(\'input[type="checkbox"]\').on(\'change\', function() {
    $(\'input[name="\' + this.name + \'"]\').not(this).prop(\'checked\', false);
});';
$Loader->add_js($js, array('type' => 'inline', 'weight' => 250, 'group' => SPM_JS_GROUP_SYSTEM));

require SITE_ROOT.'footer.php';