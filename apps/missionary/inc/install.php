<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'				=> $DBLayer->dt_serial(),
		'question'			=> $DBLayer->dt_varchar(),
		'answer' 			=> $DBLayer->dt_varchar(),
		'version1' 			=> $DBLayer->dt_varchar(),
		'version2' 			=> $DBLayer->dt_varchar(),
		'version3' 			=> $DBLayer->dt_varchar(),
		'description'		=> $DBLayer->dt_varchar(),
		'level'				=> $DBLayer->dt_int('TINYINT(1)'),
		'num_unanswered'	=> $DBLayer->dt_int(),
		'approved'			=> $DBLayer->dt_int('TINYINT(1)'),
		'updated'			=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$forum_db->create_table('game_missionary', $schema);
