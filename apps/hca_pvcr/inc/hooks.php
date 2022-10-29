<?php

if (!defined('DB_CONFIG')) die();

function hca_pvcr_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    $urls['hca_pvcr_new_project'] = 'apps/hca_pvcr/new_project.php';
    $urls['hca_pvcr_projects'] = 'apps/hca_pvcr/projects.php?section=$1';
    $urls['hca_pvcr_settings'] = 'apps/hca_pvcr/settings.php';
    $urls['hca_pvcr_ajax_get_units'] = 'apps/hca_pvcr/ajax/get_units.php';
    $urls['hca_pvcr_ajax_get_cell'] = 'apps/hca_pvcr/ajax/get_cell.php';
    $URL->add_urls($urls);
}

function hca_pvcr_hd_menu_elements()
{
    global $User, $URL, $Config;
    
}

function hca_pvcr_hd_head()
{
    global $Loader;

    if (PAGE_SECTION_ID == 'hca_pvcr')
        $Loader->add_css(BASE_URL.'/apps/hca_pvcr/css/style.css?'.time());
}

function hca_pvcr_ft_js_include()
{
    global $Loader;

    if (PAGE_SECTION_ID == 'hca_pvcr')
        $Loader->add_js(BASE_URL.'/apps/hca_pvcr/js/main.js?'.time(), array('type' => 'url', 'async' => false, 'group' => -100 , 'weight' => 75));
}
