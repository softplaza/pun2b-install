<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'				=> $DBLayer->dt_serial(),
		'user_id'			=> $DBLayer->dt_int(),
		'user_name'			=> $DBLayer->dt_varchar('VARCHAR(100)'),
		'base_name'			=> $DBLayer->dt_varchar(),
		'file_name'			=> $DBLayer->dt_varchar(),
		'file_type'			=> $DBLayer->dt_varchar('VARCHAR(50)'),
		'file_ext'			=> $DBLayer->dt_varchar('VARCHAR(50)'),
		'file_size'			=> $DBLayer->dt_int(),
		'file_path'			=> $DBLayer->dt_varchar(),
		'file_status'		=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
		'load_time'			=> $DBLayer->dt_int(),
		'table_name'		=> $DBLayer->dt_varchar(),
		'table_id'			=> $DBLayer->dt_int(),
		// in progress...
		'preview_name'		=> $DBLayer->dt_varchar(),
	),
	'PRIMARY KEY'	=> array('id'),
);
$DBLayer->create_table('sm_uploader', $schema);

$DBLayer->add_field('sm_uploader', 'file_status', 'TINYINT(1)', false, '1');

config_add('o_sm_uploader_image_types', 'jpg,jpeg,png,bmp');
config_add('o_sm_uploader_file_types', 'pdf,doc,docx,xls,xlsx,txt');
config_add('o_sm_uploader_media_types', 'avi,mp3,mp4,mpeg');
config_add('o_sm_uploader_structure', '0');
