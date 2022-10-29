<?php

if (!defined('DB_CONFIG')) die();

function swift_inventory_management_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    $app_id = 'swift_inventory_management';

    $urls['swift_inventory_management_add'] = 'apps/'.$app_id.'/add.php';
    $urls['swift_inventory_management_records'] = 'apps/'.$app_id.'/records.php?id=$1';
    $urls['swift_inventory_management_warehouse'] = 'apps/'.$app_id.'/warehouse.php?id=$1';
    $urls['swift_inventory_management_ajax_get_search_results'] = 'apps/'.$app_id.'/ajax/get_search_results.php';

    $URL->add_urls($urls);
}

function swift_inventory_management_hd_menu_elements()
{
    global $User, $URL, $Config;

    if ($User->is_admmod())
    {

    }
}
