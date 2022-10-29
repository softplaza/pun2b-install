<?php
	$Core->set_page_id('admin_settings_setup', 'settings');
	require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<input type="hidden" name="form_sent" value="1" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Main site settings</h6>
		</div>
		<div class="card-body">		
			<div class="mb-3">
				<label class="form-label" for="fld_board_title">Site title</label>
				<input type="text" name="form[board_title]" value="<?php echo html_encode($Config->get('o_board_title')) ?>" class="form-control" id="fld_board_title">
			</div>
			<div class="mb-3">
				<label class="form-label" for="fld_board_desc">Site description</label>
				<input type="text" name="form[board_desc]" value="<?php echo html_encode($Config->get('o_board_desc')) ?>" class="form-control" id="fld_board_desc">
			</div>
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_default_style">Default style</label>
					<select name="form[default_style]" id="fld_default_style" class="form-select">
<?php
$styles = get_style_packs();
foreach ($styles as $style)
{
	if ($Config->get('o_default_style') == $style)
		echo "\t\t\t\t\t\t\t\t".'<option value="'.$style.'" selected>'.str_replace('_', ' ', $style).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t\t".'<option value="'.$style.'">'.str_replace('_', ' ', $style).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-3">
					<label class="form-label" for="fld_default_lang">Default language</label>
					<select name="form[default_lang]" id="fld_default_lang" class="form-select">
<?php
$languages = get_language_packs();
foreach ($languages as $lang)
{
	if ($Config->get('o_default_lang') == $lang)
		echo "\t\t\t\t\t\t\t\t".'<option value="'.$lang.'" selected>'.$lang.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t\t".'<option value="'.$lang.'">'.$lang.'</option>'."\n";
}
?>
					</select>
				</div>
			</div>
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_time_format">Time format</label>
					<input type="text" name="form[time_format]" value="<?php echo html_encode($Config->get('o_time_format')) ?>" class="form-control" id="fld_time_format">
				</div>
				<div class="col-md-3">
					<label class="form-label" for="fld_date_format">Date format</label>
					<input type="text" name="form[date_format]" value="<?php echo html_encode($Config->get('o_date_format')) ?>" class="form-control" id="fld_date_format">
				</div>
			</div>
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label" for="fld_num_items_on_page">Default items per page</label>
					<select name="form[num_items_on_page]" id="fld_num_items_on_page" class="form-select">
<?php
$disp_topics_arr = array(15,25,50,75,100);
foreach ($disp_topics_arr as $topic_num)
{
	if ($Config->get('o_num_items_on_page') == $topic_num)
		echo "\t\t\t\t\t\t".'<option value="'.$topic_num.'" selected>'.$topic_num.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$topic_num.'">'.$topic_num.'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-3">
					<label class="form-label" for="fld_max_items_on_page">Limit items per page</label>
					<input type="text" name="form[max_items_on_page]" value="<?php echo html_encode($Config->get('o_max_items_on_page')) ?>" class="form-control" id="fld_max_items_on_page">
				</div>
			</div>
			<hr>
			<button type="submit" name="save" class="btn btn-primary">Save changes</button>
		</div>
	</div>
</form>

<?php
