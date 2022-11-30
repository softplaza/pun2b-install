<?php

if (!defined('DB_CONFIG')) die();

function hca_wom_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    // for property manager
    $urls['hca_wom_work_orders'] = 'apps/hca_wom/work_orders.php';
    $urls['hca_wom_work_order'] = 'apps/hca_wom/work_order.php?id=$1';

    // for technician
    $urls['hca_wom_tasks'] = 'apps/hca_wom/tasks.php';
    $urls['hca_wom_task'] = 'apps/hca_wom/task.php?id=$1';

    // ajax updates
    $urls['hca_wom_ajax_get_units'] = 'apps/hca_wom/ajax/get_units.php';
    $urls['hca_wom_ajax_add_task'] = 'apps/hca_wom/ajax/add_task.php';//replaced on manage_task.php
    $urls['hca_wom_ajax_manage_task'] = 'apps/hca_wom/ajax/manage_task.php';
    $urls['hca_wom_ajax_get_items'] = 'apps/hca_wom/ajax/get_items.php';

    // management
    $urls['hca_wom_items'] = 'apps/hca_wom/items.php?id=$1';
    $urls['hca_wom_settings'] = 'apps/hca_wom/settings.php';

    $URL->add_urls($urls);
}

function hca_wom_IncludeCommon()
{
    global $User, $SwiftMenu, $URL;

    if ($User->checkAccess('hca_wom'))
    {
        // Display main menu item
        //$SwiftMenu->addItem(['title' => 'Facility 2', 'link' => '#', 'id' => 'hca_wom', 'icon' => '<i class="fas fa-landmark"></i>', 'level' => 10]);

        if ($User->checkAccess('hca_wom', 3))
            $SwiftMenu->addItem(['title' => 'Work Orders', 'link' => $URL->link('hca_wom_work_orders'), 'id' => 'hca_wom_work_orders', 'parent_id' => 'hca_fs', 'level' => 1]);

        if ($User->checkAccess('hca_wom', 1))
            $SwiftMenu->addItem(['title' => 'Quick Task Entry', 'link' => $URL->link('hca_wom_work_order', 0), 'id' => 'hca_wom_work_order', 'parent_id' => 'hca_fs', 'level' => 2]);

        // Technician
        if ($User->checkAccess('hca_wom', 4))
            $SwiftMenu->addItem(['title' => 'To-Do List', 'link' => $URL->link('hca_wom_tasks'), 'id' => 'hca_wom_tasks', 'parent_id' => 'hca_fs', 'level' => 3]);

        if ($User->checkAccess('hca_wom', 90) || $User->checkAccess('hca_wom', 100))
        {
            $SwiftMenu->addItem(['title' => 'WO Management', 'link' => '#', 'id' => 'hca_wom_management', 'parent_id' => 'hca_fs', 'level' => 90]);

            if ($User->checkAccess('hca_wom', 90))
                $SwiftMenu->addItem(['title' => 'WO Items', 'link' => $URL->link('hca_wom_items', 0), 'id' => 'hca_wom_items', 'parent_id' => 'hca_wom_management', 'level' => 90]);

            if ($User->checkAccess('hca_wom', 100))
                $SwiftMenu->addItem(['title' => 'WO Settings', 'link' => $URL->link('hca_wom_settings'), 'id' => 'hca_wom_settings', 'parent_id' => 'hca_wom_management', 'level' => 100]);
        }
    }
}

class HcaWOMHooks
{
    private static $singleton;

    public static function getInstance(){
        return self::$singleton = new self;
    }

    public static function singletonMethod(){
        return self::getInstance();
    }

    public function ProfileAdminAccess()
    {
        global $access_info;

        $access_options = [
            1 => 'Quick Task Entry',
            2 => 'Edit WO',
            3 => 'Work Orders',
            4 => 'To-Do List',
            90 => 'WO Items'
        ];

        if (check_app_access($access_info, 'hca_wom'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h5 class="h5 card-title mb-0">Work Orders Management</h5>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_wom'))
                    echo '<span class="badge badge-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge badge-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAdminAccess', ['HcaWOMHooks', 'ProfileAdminAccess']);
