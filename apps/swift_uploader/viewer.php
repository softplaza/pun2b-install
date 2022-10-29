<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('swift_uploader', 2) || $User->checkAccess('swift_uploader', 3) || $User->checkAccess('swift_uploader', 4)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$type = isset($_GET['type']) ? $_GET['type'] : 'image';
$search_by_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$search_by_table_name = isset($_GET['table_name']) ? swift_trim($_GET['table_name']) : '';

$query = array(
	'SELECT'	=> 'COUNT(f.id)',
	'FROM'		=> 'sm_uploader as f',
	'WHERE'		=> 'f.file_type=\'image\'',
);
if ($type == 'media')
	$query['WHERE'] = 'f.file_type=\'media\'';
else if ($type == 'file')
	$query['WHERE'] = 'f.file_type=\'file\'';

if ($search_by_user_id > 2)
	$query['WHERE'] .= ' AND f.user_id='.$search_by_user_id;
if ($search_by_table_name != '')
	$query['WHERE'] .= ' AND f.table_name=\''.$DBLayer->escape($search_by_table_name).'\'';
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 'f.*, u.realname',
	'FROM'		=> 'sm_uploader AS f',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=f.user_id'
		)
	),
	'WHERE'		=> 'f.file_type=\'image\'',
	'ORDER BY'	=> 'f.load_time DESC',
	'LIMIT'		=> $PagesNavigator->limit(),
);
if ($type == 'media')
	$query['WHERE'] = 'f.file_type=\'media\'';
else if ($type == 'file')
	$query['WHERE'] = 'f.file_type=\'file\'';

if ($search_by_user_id > 2)
	$query['WHERE'] .= ' AND f.user_id='.$search_by_user_id;
if ($search_by_table_name != '')
	$query['WHERE'] .= ' AND f.table_name=\''.$DBLayer->escape($search_by_table_name).'\'';
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
}
$PagesNavigator->num_items($main_info);

$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'group_id > 2',
	'ORDER BY'	=> 'g.g_id, u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = $assigned_users = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $fetch_assoc;
}

$page_param['fld_count'] = $page_param['group_count'] = $page_param['item_count'] = 0;

if ($type == 'media') {
	$Core->set_page_title('Uploaded media files');
	$Core->set_page_id('swift_uploader_media', 'swift_uploader');
} else if ($type == 'file') {
	$Core->set_page_title('Uploaded documents and other files');
	$Core->set_page_id('swift_uploader_files', 'swift_uploader');
} else {
	$Core->set_page_title('Uploaded images');
	$Core->set_page_id('swift_uploader_images', 'swift_uploader');
}

require SITE_ROOT.'header.php';
?>

<style>
.brd .frm-group {border-color: #ffffff;}
.required{color:red;}
.cur-img, .cur-video{vertical-align: top;display: inline-block;padding: 1.5em;max-width: 260px;}
.cur-img img{height: 200px;}
.cur-file{width:80px;display: inline-block;padding: 1.5em;vertical-align: top;}
.cur-file p{ word-break: break-all;}
.holder_default {width:500px; height:150px; border: 3px dashed #ccc;}
.main-frm .delete{height: 20px;}
.main-frm .send-file{height: 20px;margin-left: 20px;}
</style>

<div class="main-content main-frm">
	<div class="ct-group">
		<div class="search-box">
			<form method="get" accept-charset="utf-8" action="">
				<input type="hidden" name="type" value="<?php echo $type ?>" />
				<select name="user_id">
<?php

$optgroup = 0;
echo "\t\t\t\t\t\t".'<option value="0" '.($search_by_user_id == 0 ? 'selected' : '').'>All Users</option>'."\n";
foreach ($users_info as $cur_user)
{
	if ($cur_user['group_id'] != $optgroup) {
		if ($optgroup) {
			echo '</optgroup>';
		}
		echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
		$optgroup = $cur_user['group_id'];
	}
	
	if ($search_by_user_id == $cur_user['id'] && $search_by_user_id != 2)
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'" selected>'.html_encode($cur_user['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
}
?>
				</select>
				<select name="table_name">
<?php 
$table_list = array(
	'sm_special_projects_records'		=> 'Special Projects',
	'hca_vcr_projects'					=> 'VCR Projects',
	'hca_5840_projects'					=> 'Moisture Inspections',
	'hca_fs_requests'					=> 'In-House Facilities',
	'sm_pest_control_records'			=> 'Pest Control',
	'hca_vcr_turn_over_inspections'		=> 'TurnOver Inspections',
	'hca_trees_projects'				=> 'Trees Projects',
);

echo "\t\t\t\t\t\t".'<option value="" selected="selected">All Projects</option>'."\n";
foreach ($table_list as $key => $val)
{
	if ($search_by_table_name == $key)
		echo "\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
}
?>
				</select>
				<input type="submit" value="Search" />
			</form>
		</div>

		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			
<?php
if (!empty($main_info))
{
?>
			<div class="uploaded-images">
<?php
	foreach($main_info as $cur_file)
	{
		$cur_link = BASE_URL.'/'.$cur_file['file_path'].'/'.$cur_file['file_name'];
		$doc_icon = ($cur_file['file_ext'] == 'pdf') ? 'pdf.png' : 'doc.png';
		$project_name = isset($table_list[$cur_file['table_name']]) ? $table_list[$cur_file['table_name']] : '';
		//$action = '<span style="margin-right:5px;"><input type="checkbox" name="file_path['.$cur_file['id'].']" value="'.$cur_file['file_path'].$cur_file['file_name'].'" /></span>';
		
		$file_view = [];
		$file_view[] = '<div class="cur-img">';
		
		if ($cur_file['file_type'] == 'image')
			$file_view[] = '<a data-fancybox="single" href="'.$cur_link.'" target="_blank"><img src="'.$cur_link.'"/></a>';
		else if ($cur_file['file_type'] == 'media')
		{
			$file_view[] = '<video width="320" height="240" controls><source src="'.$cur_link.'" type="video/mp4">Your browser does not support the video tag.</video>';
		}
		else
			$file_view[] = '<a href="'.$cur_link.'" target="_blank"><img src="'.BASE_URL.'/img/'.$doc_icon.'" style="width:80px;height:auto;"/></a>';
		
		$file_view[] = '<p><strong>'.$project_name.' #'.$cur_file['table_id'].'</strong></p>';
		$file_view[] = '<p>'.$cur_file['base_name'].'</p>';
		$file_view[] = '</div>';
		
		echo "\n\t".implode("\n\t\t", $file_view);
	}
?>
			</div>
<?php
}
else
{
?>
			<div class="ct-box info-box">
				<p>You don't have any uploaded images associated with this project yet.</p>
			</div>
<?php
}
?>
		</form>
	</div>
</div>

<?php
require SITE_ROOT.'footer.php';