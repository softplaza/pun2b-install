<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$hash = isset($_GET['hash']) ? $_GET['hash'] : '';

$main_info = [];
if ($hash != '')
{
	parse_str(base64_decode($hash), $url_param);
	$table_name = isset($url_param['project']) ? swift_trim($url_param['project']) : '';
	$table_ids = isset($url_param['ids']) ? explode(',', $url_param['ids']) : [];

	if (!empty($table_ids) && $table_name != '')
	{
		$query = array(
			'SELECT'	=> 'f.*, u.realname',
			'FROM'		=> 'sm_uploader AS f',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'		=> 'users AS u',
					'ON'			=> 'u.id=f.user_id'
				)
			),
			'ORDER BY'	=> 'f.load_time DESC',
			'LIMIT'		=> 30,
		);
		$query['WHERE'] = 'f.id IN('.implode(',', $table_ids).') AND f.table_name=\''.$DBLayer->escape($table_name).'\'';
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result)) {
			$main_info[] = $row;
		}
	}
}

$Core->set_page_title('Uploaded images');
$Core->set_page_id('swift_uploader_view', 'swift_uploader');

require SITE_ROOT.'header.php';
?>
<style>
.cur-img, .cur-video{vertical-align: top;display: inline-block;padding: 1.5em;max-width: 260px;}
.cur-img img{height: 200px;}
.cur-file{width:80px;display: inline-block;padding: 1.5em;vertical-align: top;}
.cur-file p{ word-break: break-all;}

.thumbnail {
    display: block;
    padding: 4px;
    margin-bottom: 20px;
    line-height: 1.42857143;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    -webkit-transition: border .2s ease-in-out;
    -o-transition: border .2s ease-in-out;
    transition: border .2s ease-in-out;
}
</style>

<div class="main-content main-frm">

	<div class="row">
	
<?php
if (!empty($main_info))
{
	foreach($main_info as $cur_file)
	{
		$cur_link = BASE_URL.'/'.$cur_file['file_path'].'/'.$cur_file['file_name'];
		$file_icon = ($cur_file['file_ext'] == 'pdf') ? 'pdf.png' : 'doc.png';
		$project_name = isset($table_list[$cur_file['table_name']]) ? $table_list[$cur_file['table_name']] : '';

		$file_frame = $file_view = [];
		if ($cur_file['file_type'] == 'image')
		{
			$file_view[] = '<a data-fancybox="single" href="'.$cur_link.'">';
			$file_view[] = '<img src="'.$cur_link.'" style="width:100%">';
			$file_view[] = '<div class="caption"><p>'.$cur_file['base_name'].'</p></div>';
			$file_view[] = '</a>';
		}
		else if ($cur_file['file_type'] == 'media')
		{
			$file_view[] = '<video width="100%" height="240" controls><source src="'.$cur_link.'" type="video/mp4">Your browser does not support the video tag.</video>';
			$file_view[] = '<div class="caption"><p>'.$cur_file['base_name'].'</p></div>';
		}
		else
		{
			$file_view[] = '<a href="'.$cur_link.'" target="_blank">';
			$file_view[] = '<img src="'.BASE_URL.'/img/'.$file_icon.'" style="width:100%"/>';
			$file_view[] = '<div class="caption"><p>'.$cur_file['base_name'].'</p></div>';
			$file_view[] = '</a>';
		}
			

		$file_frame[] = '<div class="col-md-3">';
		$file_frame[] = '<div class="thumbnail">';
		$file_frame[] = implode("\n", $file_view);
		$file_frame[] = '</div>';
		$file_frame[] = '</div>';

		echo "\n\t".implode("\n\t\t", $file_frame);
	}
}
else
{
?>
			<div class="ct-box info-box">
				<p>You don't have any shared files.</p>
			</div>
<?php
}
?>

	</div>

</div>

<?php
require SITE_ROOT.'footer.php';