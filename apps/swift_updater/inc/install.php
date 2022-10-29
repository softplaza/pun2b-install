<?php 

if (!defined('APP_INSTALL')) die();
/*
// users table - fields to remove
all social
signature_on
show_sig

*/

/*
$DBLayer->add_field('users', 'num_items_on_page', 'TINYINT(3)', false, '25');
$DBLayer->add_field('users', 'work_phone', 'VARCHAR(30)', false, '');
$DBLayer->add_field('users', 'cell_phone', 'VARCHAR(30)', false, '');
$DBLayer->add_field('users', 'home_phone', 'VARCHAR(30)', false, '');

$DBLayer->add_field('users', 'first_name', 'VARCHAR(40)', true);
$DBLayer->add_field('users', 'last_name', 'VARCHAR(40)', true);
$DBLayer->add_field('users', 'users_sort_by', 'TINYINT(1)', false, '0'); // 0 - by First Name, 1 - by Last Name

$DBLayer->add_field('users', 'dept_id', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('users', 'pos_id', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('positions', 'department_id', 'INT(10) UNSIGNED', false, '0');

$DBLayer->add_field('users', 'signature_on', 'TINYINT(1)', false, '0');

$schema = array(
    'FIELDS'		=> array(
        'id'				=> $DBLayer->dt_varchar(),
        'title'				=> $DBLayer->dt_varchar(),
        'version'			=> $DBLayer->dt_varchar(),
        'description'		=> $DBLayer->dt_text(),
        'author'			=> $DBLayer->dt_varchar(),
        'disabled'			=> $DBLayer->dt_int('TINYINT(1)'),
    ),
    'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('applications', $schema);

//$DBLayer->drop_table('permissions');
$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'group_id'				=> $DBLayer->dt_int(),
		'user_id'				=> $DBLayer->dt_int(),
		'perm_for'				=> $DBLayer->dt_varchar(),
		'perm_key'				=> $DBLayer->dt_int('TINYINT(3)'),
		'perm_value'			=> $DBLayer->dt_int('TINYINT(1)')
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('permissions', $schema);
*/

/*
// Set ACCESn to PAGES
$schema = [
	'FIELDS'	=> [
		'id'			=> $DBLayer->dt_serial(),
		'a_gid'			=> $DBLayer->dt_int(),
		'a_uid'			=> $DBLayer->dt_int(),
		'a_to'			=> $DBLayer->dt_varchar(),
		'a_key'			=> $DBLayer->dt_int('TINYINT(3)'),
		'a_value'		=> $DBLayer->dt_int('TINYINT(1)')
    ],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('user_access', $schema);

// Set Permissions for actions
$schema = [
	'FIELDS'	=> [
		'id'			=> $DBLayer->dt_serial(),
		'p_gid'			=> $DBLayer->dt_int(),
		'p_uid'			=> $DBLayer->dt_int(),
		'p_to'			=> $DBLayer->dt_varchar(),
		'p_key'			=> $DBLayer->dt_int('TINYINT(3)'),
		'p_value'		=> $DBLayer->dt_int('TINYINT(1)')
    ],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('user_permissions', $schema);

// Set notifications for projects
$schema = [
	'FIELDS'	=> [
		'id'			=> $DBLayer->dt_serial(),
		'n_gid'			=> $DBLayer->dt_int(),
		'n_uid'			=> $DBLayer->dt_int(),
		'n_to'			=> $DBLayer->dt_varchar(),
		'n_key'			=> $DBLayer->dt_int('TINYINT(3)'),
		'n_value'		=> $DBLayer->dt_int('TINYINT(1)')
    ],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('user_notifications', $schema);
*/

config_remove(
	[
		'o_email'
	]
);
config_add('o_email_mode', '1');
