<?php

if (!defined('DB_CONFIG')) die();

function territory_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    $app_id = 'territory';

    $urls['territory_new'] = 'apps/'.$app_id.'/new.php';
    $urls['territory_territories'] = 'apps/'.$app_id.'/territories.php';
    $urls['territory_assignments'] = 'apps/'.$app_id.'/assignments.php';

    $urls['territory_ajax_get_territory'] = 'apps/'.$app_id.'/ajax/get_territory.php';

    $urls['territory_settings'] = 'apps/'.$app_id.'/settings.php';

    $URL->add_urls($urls);
}

function territory_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;
    
    if ($User->checkAccess('territory'))
        $SwiftMenu->addItem(['title' => 'Territory', 'link' => '#', 'id' => 'territory', 'icon' => '<i class="fas fa-map-marked-alt"></i>', 'level' => 200]);

    $SwiftMenu->addItem(['title' => '+ New territory', 'link' => $URL->link('territory_new'), 'id' => 'territory_new', 'parent_id' => 'territory']);

    $SwiftMenu->addItem(['title' => 'Territories', 'link' => $URL->link('territory_territories'), 'id' => 'territory_territories', 'parent_id' => 'territory']);

    $SwiftMenu->addItem(['title' => 'Assignments', 'link' => $URL->link('territory_assignments'), 'id' => 'territory_assignments', 'parent_id' => 'territory']);

    if ($User->checkAccess('territory', 20))
        $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('territory_settings'), 'id' => 'territory_settings', 'parent_id' => 'territory']);
}
