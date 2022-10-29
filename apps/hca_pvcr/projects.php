<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->is_admmod() || $User->get('sm_pm_property_id') > 0 || $User->get('hca_pvcr_access') > 0) ? true : false;

if (!$access)
	message($lang_common['No permission']);

$row_id = isset($_GET['row']) ? intval($_GET['row']) : 0;
$property_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
$hash = isset($_GET['hash']) ? swift_trim($_GET['hash']) : '';
$action = isset($_GET['action']) ? swift_trim($_GET['action']) : '';
$section = isset($_GET['section']) ? swift_trim($_GET['section']) : 'active';
$statuses = array(0 => 'ACTIVE', 1 => 'COMPLETED', 2 => 'ON HOLD', 5 => 'DELETE');
$week_of = isset($_GET['week_of']) && $_GET['week_of'] != '' ? strtotime($_GET['week_of']) : 0;
$first_day_of_this_week = ($week_of > 0) ? strtotime('Monday this week', $week_of) : strtotime('Monday this week');
$first_day_of_next_week = ($week_of > 0) ? strtotime('Monday next week', $week_of) : strtotime('Monday next week');
$yesterday = strtotime(date('Y-m-d\T00:00:00', time())) - 3601; // If daylight changes

$search_by_property_id = isset($_GET['property_id']) ? swift_trim($_GET['property_id']) : '';
$search_by_unit_number = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';
$search_by = isset($_GET['search_by']) ? intval($_GET['search_by']) : 0;

$query = array(
	'SELECT'	=> 'id, pro_name, manager_id',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
}

$query = array(
	'SELECT'	=> 'COUNT(pj.id)',
	'FROM'		=> 'hca_pvcr_projects AS pj',
);
if ($User->get('sm_pm_property_id') > 0)
	$query['WHERE'] = 'pj.property_id='.intval($User->get('sm_pm_property_id'));
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

// GET DATA
$query = array(
	'SELECT'	=> 'pj.*, pt.pro_name',
	'FROM'		=> 'hca_pvcr_projects AS pj',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		),
	),
	'ORDER BY'	=> 'pt.pro_name, LENGTH(pj.unit_number), pj.unit_number',
	'LIMIT'		=> $PagesNavigator->limit(),
);
if ($User->get('sm_pm_property_id') > 0)
	$query['WHERE'] = 'pj.property_id='.intval($User->get('sm_pm_property_id'));
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
}
$PagesNavigator->num_items($main_info);

if (isset($_POST['update']))
{
	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

	$form_data = [];
	if (isset($_POST['move_out_date'])) $form_data['move_out_date'] = strtotime($_POST['move_out_date']);
	if (isset($_POST['move_out_comment'])) $form_data['move_out_comment'] = $_POST['move_out_comment'];


	if (empty($Core->errors) && $id > 0)
	{
		$DBLayer->update_values('hca_pvcr_projects', $id, $form_data);
		
		$flash_message = 'Project #'.$id.' has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect(get_cur_url('row='.$id), $flash_message);
	}
}

if ($section == 'completed')
{
	$Core->set_page_title('Completed projects', 'VCR');
	$Core->set_page_id('hca_pvcr_projects', 'hca_pvcr');
}
else
{
	$Core->set_page_title('Active projects', 'VCR');
	$Core->set_page_id('hca_pvcr_projects', 'hca_pvcr');
}

require SITE_ROOT.'header.php';
?>

<div class="main-content main-frm">
	<div class="ct-group">
		<div class="search-box">
			<form method="get" accept-charset="utf-8" action="">
				<input type="hidden" name="section" value="<?php echo $section ?>"/>
				<input name="unit_number" type="text" value="<?php echo isset($_GET['unit_number']) ? $_GET['unit_number'] : '' ?>" placeholder="Enter Unit #"/>
				<input type="submit" value="Search" />
			</form>
		</div>
<?php
if (!empty($main_info))
{
	$HcaPvcrTableFormat = new HcaPvcrTableFormat;
?>
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<table class="hl-cell">

				<?=$HcaPvcrTableFormat->getHeader()?>

				<tbody>
<?php
	foreach ($main_info as $cur_info)
	{	
		echo $HcaPvcrTableFormat->getTbodyRow($cur_info);
	}
?>
						</tbody>
					</table>
				</form>
<?php
} else {
?>
		<div class="ct-box warn-box">
			<p>You have no active projects on this page.</p>
		</div>
<?php
}
?>
	</div>
</div>

<div class="pop-up-window" id="window_edit_cell">
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?=generate_form_token()?>" />
		<div class="head">
			<p class="close"><img src="<?=BASE_URL?>/img/close.png" onclick="closePopUpWindows()"></p>
			<p class="title">Pre Walk Editor</p>
		</div>
		<div class="fields"></div>
	</form>
</div>

<script>
function editCell(row,col){
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_pvcr_ajax_get_cell')) ?>";
	var pos = $("#row"+row).position();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_pvcr_ajax_get_cell') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({csrf_token:csrf_token,row:row,col:col}),
		success: function(re){
			closePopUpWindows();
			$(".pop-up-window .title").empty().html(re.title);
			$(".pop-up-window .fields").empty().html(re.fields);

			$("#window_edit_cell").css("top", pos.top + "px");
			//$("#window_edit_cell").css("left", pos.left + "px");
			$("#window_edit_cell").slideDown("2000");
		},
		error: function(re){
			$("#brd-messages").empty().html(re.error);
		}
	});
}
</script>
<?php
require SITE_ROOT.'footer.php';