<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('categories');
$DBLayer->drop_table('forums');
$DBLayer->drop_table('forum_perms');
$DBLayer->drop_table('topics');
$DBLayer->drop_table('posts');
$DBLayer->drop_table('categories');

$DBLayer->drop_table('punbb_categories');
$DBLayer->drop_table('punbb_forums');
$DBLayer->drop_table('punbb_forum_perms');
$DBLayer->drop_table('punbb_topics');
$DBLayer->drop_table('punbb_posts');
$DBLayer->drop_table('punbb_categories');

