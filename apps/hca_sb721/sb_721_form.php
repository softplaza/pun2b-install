<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_mi', 15) || $User->get('hca_5840_access') > 0) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$HcaSB721Form = new HcaSB721Form;
$SwiftUploader = new SwiftUploader;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message('Sorry, this project does not exist or has been removed.');

//Get cur project info
$query = array(
	'SELECT'	=> 'pj.*, pt.pro_name, u.realname',
	'FROM'		=> 'hca_sb721_projects AS pj',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		),
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=pj.performed_by'
		),
	),
	'WHERE'		=> 'pj.id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_info = $DBLayer->fetch_assoc($result);

if (isset($_POST['create_pdf']))
{
	$form_info = [
		'pro_name'			=> swift_trim($project_info['pro_name']),
		'unit_number'		=> swift_trim($project_info['unit_number']),
		'realname'			=> swift_trim($project_info['realname']),
		'date_performed'	=> format_date($project_info['date_performed'], 'n/j/Y'),
		'supports_check'	=> intval($_POST['supports_check']),
		'supports_text'		=> swift_trim($_POST['supports_text']),
		'railings_check'	=> intval($_POST['railings_check']),
		'railings_text'		=> swift_trim($_POST['railings_text']),
		'balconies_check'	=> intval($_POST['balconies_check']),
		'balconies_text'	=> swift_trim($_POST['balconies_text']),
		'decks_check'		=> intval($_POST['decks_check']),
		'decks_text'		=> swift_trim($_POST['decks_text']),
		'porches_check'		=> intval($_POST['porches_check']),
		'porches_text'		=> swift_trim($_POST['porches_text']),
		'stairways_check'	=> intval($_POST['stairways_check']),
		'stairways_text'	=> swift_trim($_POST['stairways_text']),
		'walkways_check'	=> intval($_POST['walkways_check']),
		'walkways_text'		=> swift_trim($_POST['walkways_text']),
		'fascia_check'		=> intval($_POST['fascia_check']),
		'fascia_text'		=> swift_trim($_POST['fascia_text']),
		'stucco_check'		=> intval($_POST['stucco_check']),
		'stucco_text'		=> swift_trim($_POST['stucco_text']),
		'flashings_check'	=> intval($_POST['flashings_check']),
		'flashings_text'	=> swift_trim($_POST['flashings_text']),
		'membranes_check'	=> intval($_POST['membranes_check']),
		'membranes_text'	=> swift_trim($_POST['membranes_text']),
		'coatings_check'	=> intval($_POST['coatings_check']),
		'coatings_text'		=> swift_trim($_POST['coatings_text']),
		'sealants_check'	=> intval($_POST['sealants_check']),
		'sealants_text'		=> swift_trim($_POST['sealants_text']),	
		'action'			=> swift_trim($_POST['action']),	
	];

	$html_content = $HcaSB721Form->genPDF($form_info);
	
	$mpdf = new \Mpdf\Mpdf();
	$mpdf->WriteHTML($html_content);
	
	$time_now = time();
	$assoc_table_name = 'hca_sb721_projects';
	$file_path = $SwiftUploader->checkPath($assoc_table_name).'/';

	$base_filename = 'sb_721_'.date('ymd', $time_now).'.pdf';
	$new_filename = 'sb_721_'.date('ymd_His', $time_now).'.pdf';
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
		
		$flash_message = 'Form has been created as PDF file.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_sb721_manage_files', $id), $flash_message);
	}
	else
		$Core->add_error('Cannot create file.');
}

$Core->set_page_id('hca_sb721_form', 'hca_sb721');
require SITE_ROOT.'header.php';
?>
<style>
.form-table td{padding: .5em 0}
.form-table tr th{ border-bottom: 1px solid #000;}
td input{font-size: 18px;}

</style>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">SB-721 Checklist</h6>
		</div>
		<div class="card-body">
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label">Property name:</label>
					<h6><?php echo $project_info['pro_name'] ?><h6>
				</div>
				<div class="col-md-3">
					<label class="form-label">Unit number:</label>
					<h6><?php echo $project_info['unit_number'] ?><h6>
				</div>
				<div class="col-md-3">
					<label class="form-label">Date performed:</label>
					<h6><?php echo format_date($project_info['date_performed'], 'n/j/Y') ?><h6>
				</div>
				<div class="col-md-3">
					<label class="form-label">Performed by:</label>
					<h6><?php echo html_encode($project_info['realname']) ?><h6>
				</div>
			</div>
		</div>

		<div class="card-body">
			<h6 class="card-title">Exterior Elevated Elements</h6>
			<hr>
			<table class="form-table mb-3">
				<thead>
					<tr>
						<th>Elements</th>
						<th>OK</th>
						<th>NO</th>
						<th>Comments</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><h6>Supports/Beams</h6></td>
						<td><input class="form-check-input" type="checkbox" name="supports_check" value="0" checked></td>
						<td><input class="form-check-input" type="checkbox" name="supports_check" value="1"></td>
						<td><textarea type="text" name="supports_text" class="form-control" rows="1"></textarea></td>
					</tr>
					<tr>
						<td><h6>Railings</h6></td>
						<td><input class="form-check-input" type="checkbox" name="railings_check" value="0" checked></td>
						<td><input class="form-check-input" type="checkbox" name="railings_check" value="1"></td>
						<td><textarea type="text" name="railings_text" class="form-control" rows="1"></textarea></td>
					</tr>
					<tr>
						<td><h6>Balconies</h6></td>
						<td><input class="form-check-input" type="checkbox" name="balconies_check" value="0" checked></td>
						<td><input class="form-check-input" type="checkbox" name="balconies_check" value="1"></td>
						<td><textarea type="text" name="balconies_text" class="form-control" rows="1"></textarea></td>
					</tr>
					<tr>
						<td><h6>Decks</h6></td>
						<td><input class="form-check-input" type="checkbox" name="decks_check" value="0" checked></td>
						<td><input class="form-check-input" type="checkbox" name="decks_check" value="1"></td>
						<td><textarea type="text" name="decks_text" class="form-control" rows="1"></textarea></td>
					</tr>
					<tr>
						<td><h6>Porches</h6></td>
						<td><input class="form-check-input" type="checkbox" name="porches_check" value="0" checked></td>
						<td><input class="form-check-input" type="checkbox" name="porches_check" value="1"></td>
						<td><textarea type="text" name="porches_text" class="form-control" rows="1"></textarea></td>
					</tr>
					<tr>
						<td><h6>Stairways</h6></td>
						<td><input class="form-check-input" type="checkbox" name="stairways_check" value="0" checked></td>
						<td><input class="form-check-input" type="checkbox" name="stairways_check" value="1"></td>
						<td><textarea type="text" name="stairways_text" class="form-control" rows="1"></textarea></td>
					</tr>
					<tr>
						<td><h6>Walkways</h6></td>
						<td><input class="form-check-input" type="checkbox" name="walkways_check" value="0" checked></td>
						<td><input class="form-check-input" type="checkbox" name="walkways_check" value="1"></td>
						<td><textarea type="text" name="walkways_text" class="form-control" rows="1"></textarea></td>
					</tr>
					<tr>
						<td><h6>Fascia</h6></td>
						<td><input class="form-check-input" type="checkbox" name="fascia_check" value="0" checked></td>
						<td><input class="form-check-input" type="checkbox" name="fascia_check" value="1"></td>
						<td><textarea type="text" name="fascia_text" class="form-control" rows="1"></textarea></td>
					</tr>
					<tr>
						<td><h6>Stucco</h6></td>
						<td><input class="form-check-input" type="checkbox" name="stucco_check" value="0" checked></td>
						<td><input class="form-check-input" type="checkbox" name="stucco_check" value="1"></td>
						<td><textarea type="text" name="stucco_text" class="form-control" rows="1"></textarea></td>
					</tr>
					<tr>
						<td><h6>Flashings</h6></td>
						<td><input class="form-check-input" type="checkbox" name="flashings_check" value="0" checked></td>
						<td><input class="form-check-input" type="checkbox" name="flashings_check" value="1"></td>
						<td><textarea type="text" name="flashings_text" class="form-control" rows="1"></textarea></td>
					</tr>
					<tr>
						<td><h6>Membranes</h6></td>
						<td><input class="form-check-input" type="checkbox" name="membranes_check" value="0" checked></td>
						<td><input class="form-check-input" type="checkbox" name="membranes_check" value="1"></td>
						<td><textarea type="text" name="membranes_text" class="form-control" rows="1"></textarea></td>
					</tr>
					<tr>
						<td><h6>Coatings</h6></td>
						<td><input class="form-check-input" type="checkbox" name="coatings_check" value="0" checked></td>
						<td><input class="form-check-input" type="checkbox" name="coatings_check" value="1"></td>
						<td><textarea type="text" name="coatings_text" class="form-control" rows="1"></textarea></td>
					</tr>
					<tr>
						<td><h6>Sealants</h6></td>
						<td><input class="form-check-input" type="checkbox" name="sealants_check" value="0" checked></td>
						<td><input class="form-check-input" type="checkbox" name="sealants_check" value="1"></td>
						<td><textarea type="text" name="sealants_text" class="form-control" rows="1"></textarea></td>
					</tr>
				</tbody>
			</table>

			<div class="mb-3">
				<label class="form-label" for="form_action">Action:</label>
				<textarea type="text" name="action" class="form-control" id="form_action" placeholder="Leave your comment"><?php echo html_encode($project_info['action']) ?></textarea>
			</div>

			<div class="mb-3">
				<button type="submit" name="create_pdf" class="btn btn-primary">Submit form</button>
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