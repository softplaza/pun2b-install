<?php 

if (!defined('APP_INSTALL')) die();
/*
$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'cat_name'		=> array(
			'datatype'		=> 'VARCHAR(80)',
			'allow_null'	=> false,
			'default'		=> '\'New Category\''
		),
		'disp_position'				=> $DBLayer->dt_int()
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punbb_categories', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'forum_name'	=> array(
			'datatype'		=> 'VARCHAR(80)',
			'allow_null'	=> false,
			'default'		=> '\'New forum\''
		),
		'forum_desc'				=> $DBLayer->dt_text(),
		'redirect_url'	=> array(
			'datatype'		=> 'VARCHAR(100)',
			'allow_null'	=> true
		),
		'moderators'				=> $DBLayer->dt_text(),
		'num_topics'	=> array(
			'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> '0'
		),
		'num_posts'		=> array(
			'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> '0'
		),
		'last_post'		=> array(
			'datatype'		=> 'INT(10) UNSIGNED',
			'allow_null'	=> true
		),
		'last_post_id'	=> array(
			'datatype'		=> 'INT(10) UNSIGNED',
			'allow_null'	=> true
		),
		'last_poster'	=> array(
			'datatype'		=> 'VARCHAR(200)',
			'allow_null'	=> true
		),
		'sort_by'					=> $DBLayer->dt_int('TINYINT(1)'),
		'disp_position'				=> $DBLayer->dt_int(),
		'cat_id'					=> $DBLayer->dt_int()
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punbb_forums', $schema);

$schema = array(
	'FIELDS'		=> array(
		'group_id'					=> $DBLayer->dt_int(),
		'forum_id'					=> $DBLayer->dt_int(),
		'read_forum'	=> array(
			'datatype'		=> 'TINYINT(1)',
			'allow_null'	=> false,
			'default'		=> '1'
		),
		'post_replies'	=> array(
			'datatype'		=> 'TINYINT(1)',
			'allow_null'	=> false,
			'default'		=> '1'
		),
		'post_topics'	=> array(
			'datatype'		=> 'TINYINT(1)',
			'allow_null'	=> false,
			'default'		=> '1'
		)
	),
	'PRIMARY KEY'	=> array('group_id', 'forum_id')
);
$DBLayer->create_table('punbb_forum_perms', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'			=> $DBLayer->dt_serial(),
		'poster'		=> $DBLayer->dt_varchar(),
		'subject'		=> $DBLayer->dt_varchar(),
		'posted'		=> $DBLayer->dt_int(),
		'first_post_id'	=> $DBLayer->dt_int(),
		'last_post'		=> $DBLayer->dt_int(),
		'last_post_id'	=> $DBLayer->dt_int(),
		'last_poster'	=> array(
			'datatype'		=> 'VARCHAR(200)',
			'allow_null'	=> true
		),
		'num_views'		=> array(
			'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> '0'
		),
		'num_replies'	=> array(
			'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> '0'
		),
		'closed'		=> $DBLayer->dt_int('TINYINT(1)'),
		'sticky'		=> $DBLayer->dt_int('TINYINT(1)'),
		'moved_to'		=> array(
			'datatype'		=> 'INT(10) UNSIGNED',
			'allow_null'	=> true
		),
		'forum_id'		=> $DBLayer->dt_int()
	),
	'PRIMARY KEY'	=> array('id'),
	'INDEXES'		=> array(
		'forum_id_idx'		=> array('forum_id'),
		'moved_to_idx'		=> array('moved_to'),
		'last_post_idx'		=> array('last_post'),
		'first_post_id_idx'	=> array('first_post_id')
	)
);
$DBLayer->create_table('punbb_topics', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'			=> array(
			'datatype'		=> 'SERIAL',
			'allow_null'	=> false
		),
		'poster'		=> array(
			'datatype'		=> 'VARCHAR(200)',
			'allow_null'	=> false,
			'default'		=> '\'\''
		),
		'poster_id'		=> array(
			'datatype'		=> 'INT(10) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> '1'
		),
		'poster_ip'		=> array(
			'datatype'		=> 'VARCHAR(39)',
			'allow_null'	=> true
		),
		'poster_email'	=> array(
			'datatype'		=> 'VARCHAR(80)',
			'allow_null'	=> true
		),
		'message'		=> array(
			'datatype'		=> 'TEXT',
			'allow_null'	=> true
		),
		'hide_smilies'	=> array(
			'datatype'		=> 'TINYINT(1)',
			'allow_null'	=> false,
			'default'		=> '0'
		),
		'posted'		=> array(
			'datatype'		=> 'INT(10) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> '0'
		),
		'edited'		=> array(
			'datatype'		=> 'INT(10) UNSIGNED',
			'allow_null'	=> true
		),
		'edited_by'		=> array(
			'datatype'		=> 'VARCHAR(200)',
			'allow_null'	=> true
		),
		'topic_id'		=> array(
			'datatype'		=> 'INT(10) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> '0'
		)
	),
	'PRIMARY KEY'	=> array('id'),
	'INDEXES'		=> array(
		'topic_id_idx'	=> array('topic_id'),
		'multi_idx'		=> array('poster_id', 'topic_id'),
		'posted_idx'	=> array('posted')
	)
);

$DBLayer->create_table('punbb_posts', $schema);

// Insert some other default data
$query = array(
	'INSERT'	=> 'cat_name, disp_position',
	'INTO'		=> 'punbb_categories',
	'VALUES'	=> '\'Category\', 1'
);
$DBLayer->query_build($query) or error(__FILE__, __LINE__);

$now = time();
$query = array(
	'INSERT'	=> 'forum_name, forum_desc, num_topics, num_posts, last_post, last_post_id, last_poster, disp_position, cat_id',
	'INTO'		=> 'punbb_forums',
	'VALUES'	=> '\'Forum\', \'Forum description\', 1, 1, '.$now.', 1, \''.$DBLayer->escape($User->get('username')).'\', 1, '.$DBLayer->insert_id().''
);
$DBLayer->query_build($query) or error(__FILE__, __LINE__);

$query = array(
	'INSERT'	=> 'poster, subject, posted, first_post_id, last_post, last_post_id, last_poster, forum_id',
	'INTO'		=> 'punbb_topics',
	'VALUES'	=> '\''.$DBLayer->escape($User->get('username')).'\', \'Topic subject\', '.$now.', 1, '.$now.', 1, \''.$DBLayer->escape($User->get('username')).'\', '.$DBLayer->insert_id().''
);
$DBLayer->query_build($query) or error(__FILE__, __LINE__);

$query = array(
	'INSERT'	=> 'poster, poster_id, poster_ip, message, posted, topic_id',
	'INTO'		=> 'punbb_posts',
	'VALUES'	=> '\''.$DBLayer->escape($User->get('username')).'\', '.$User->get('id').', \'127.0.0.1\', \'Post content\', '.$now.', '.$DBLayer->insert_id().''
);
$DBLayer->query_build($query) or error(__FILE__, __LINE__);
*/