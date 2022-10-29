<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$pid = isset($_GET['pid']) ? swift_trim($_GET['pid']) : '';

$access = ($User->checkAccess('hca_vendors', 2)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

if (isset($_POST['update']))
{
	if (isset($_POST['vendor']) && !empty($_POST['vendor']))
	{
		foreach($_POST['vendor'] as $id => $value)
		{
			$DBLayer->update('sm_vendors', [$pid => $value], $id);
		}
		// Add flash message
		$flash_message = 'Vendor list has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('sm_vendors_departments'), $flash_message);
	}
}

else if (isset($_POST['cancel']))
{
	$flash_message = 'Action canceled';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('sm_vendors_departments'), $flash_message);
}

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_vendors',
	'ORDER BY'	=> 'vendor_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
}

$Core->set_page_id('hca_vendors_edit_project', 'hca_vendors');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Display vendors in project</h6>
		</div>
		<div class="card-body">

			<div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" value="1" id="checkAll">
                <label class="form-check-label" for="checkAll">Check All</label>
            </div>

			<hr class="my-2">
<?php
if (!empty($main_info))
{
	foreach($main_info as $cur_info)
	{
?>
            <div class="form-check mb-3">
				<input type="hidden" name="vendor[<?php echo $cur_info['id'] ?>]" value="0">
                <input class="form-check-input" type="checkbox" name="vendor[<?php echo $cur_info['id'] ?>]" value="1" id="field[<?php echo $cur_info['id'] ?>]" <?php if ($cur_info[$pid] == '1') echo 'checked' ?>>
                <label class="form-check-label" for="field[<?php echo $cur_info['id'] ?>]"><?php echo html_encode($cur_info['vendor_name']) ?></label>
            </div>
<?php
	}
}
?>
			<hr class="my-4">

			<button type="submit" name="update" class="btn btn-primary">Update</button>
			<button type="submit" name="cancel" class="btn btn-secondary" formnovalidate>Cancel</button>
		</div>
	</div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function()
{
	$("#checkAll").click(function () {
		$(".form-check-input").prop('checked', $(this).prop('checked'));
	});
}, false);
</script>

<?php
require SITE_ROOT.'footer.php';