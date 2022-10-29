<?php 

if (!defined('APP_INSTALL')) die();

if (!$DBLayer->table_exists('sm_messenger_topics'))
{
	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'subject'			=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'last_short_message' => array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'last_posted'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'last_sender_id'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'last_sender_name'			=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'last_receiver_id'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'last_receiver_name' => array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'last_post_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),

// will use for projects ?? 
			'page_section'			=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'project_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
// ??? 
		),
		'PRIMARY KEY'	=> array('id')
	);
	$DBLayer->create_table('sm_messenger_topics', $schema);
}

if (!$DBLayer->table_exists('sm_messenger_posts'))
{
	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'p_topic_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'p_message'			=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> false
			),
			'p_sender_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'p_sender_name'		=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'p_posted'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
		),
		'PRIMARY KEY'	=> array('id')
	);
	$DBLayer->create_table('sm_messenger_posts', $schema);
}

if (!$DBLayer->table_exists('sm_messenger_users'))
{
	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'user_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'topic_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'viewed'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
		),
		'PRIMARY KEY'	=> array('id')
	);
	$DBLayer->create_table('sm_messenger_users', $schema);
}
