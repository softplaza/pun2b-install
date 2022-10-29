<?php

if (!defined('DB_CONFIG')) die();

function punbb_IncludeEssentials()
{
    define('FORUM', 1);

    require SITE_ROOT.'apps/punbb/inc/functions.php';
}

function punbb_co_modify_url_scheme()
{
    global $URL;

    $app_id = 'punbb';
    $urls = [];

    $urls['punbb_forums'] = 'apps/'.$app_id.'/forums.php';
    $urls['punbb_topics'] = 'apps/'.$app_id.'/topics.php?id=$1';
    $urls['punbb_posts'] = 'apps/'.$app_id.'/posts.php?id=$1';

    $urls['punbb_new_post'] = 'apps/'.$app_id.'/post.php?tid=$1';
    $urls['punbb_new_topic'] = 'apps/'.$app_id.'/post.php?fid=$1';
    
    $urls['punbb_edit'] = 'apps/'.$app_id.'/edit.php?id=$1';
    $urls['punbb_delete_post'] = 'apps/'.$app_id.'/delete.php?pid=$1';
    $urls['punbb_delete_topic'] = 'apps/'.$app_id.'/delete.php?tid=$1';

    $urls['punbb_settings'] = 'apps/'.$app_id.'/settings.php';

    $URL->add_urls($urls);
}

function punbb_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;

    if ($User->checkAccess('punbb'))
    {
        $SwiftMenu->addItem(['title' => 'Forum', 'link' =>  $URL->link('punbb_forums'), 'id' => 'punbb', 'icon' => '<i class="fas fa-comments"></i>', 'level' => 10]);

        if ($User->checkAccess('punbb', 1))
            $SwiftMenu->addItem(['title' => 'Forums', 'link' => $URL->link('punbb_forums'), 'id' => 'punbb_forums', 'parent_id' => 'punbb', 'level' => 1]);

        if ($User->checkAccess('punbb', 2))
            $SwiftMenu->addItem(['title' => 'Topics', 'link' => $URL->link('punbb_topics', 0), 'id' => 'punbb_topics', 'parent_id' => 'punbb', 'level' => 1]);


        if ($User->checkAccess('punbb', 20))
            $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('punbb_settings'), 'id' => 'punbb_settings', 'parent_id' => 'punbb', 'level' => 20]);
    }
}

function punbb_hd_head()
{
    global $Loader;

    if (PAGE_SECTION_ID == 'punbb')
        $Loader->add_css(BASE_URL.'/apps/punbb/css/style.css?'.time());
}