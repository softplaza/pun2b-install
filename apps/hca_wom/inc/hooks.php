<?php

if (!defined('DB_CONFIG')) die();

function hca_wom_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    // for property manager
    $urls['hca_wom_work_order_new'] = 'apps/hca_wom/work_order_new.php';
    $urls['hca_wom_work_order'] = 'apps/hca_wom/work_order.php?id=$1';
    $urls['hca_wom_work_orders'] = 'apps/hca_wom/work_orders.php';
    $urls['hca_wom_work_orders_suggested'] = 'apps/hca_wom/work_orders_suggested.php';
    $urls['hca_wom_work_orders_report'] = 'apps/hca_wom/work_orders_report.php';

    // for technician
    $urls['hca_wom_task'] = 'apps/hca_wom/task.php?id=$1';
    $urls['hca_wom_tasks'] = 'apps/hca_wom/tasks.php';
    $urls['hca_wom_work_order_suggest'] = 'apps/hca_wom/work_order_suggest.php';

    // ajax updates
    $urls['hca_wom_ajax_get_units'] = 'apps/hca_wom/ajax/get_units.php';
    $urls['hca_wom_ajax_add_task'] = 'apps/hca_wom/ajax/add_task.php';//replaced on manage_task.php
    $urls['hca_wom_ajax_manage_task'] = 'apps/hca_wom/ajax/manage_task.php';
    $urls['hca_wom_ajax_get_items'] = 'apps/hca_wom/ajax/get_items.php';
    $urls['hca_wom_ajax_get_images'] = 'apps/hca_wom/ajax/get_images.php';

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
        //$SwiftMenu->addItem(['title' => 'Facility 2', 'link' => '#', 'id' => 'hca_fs', 'icon' => '<i class="fas fa-landmark"></i>', 'level' => 10]);

        // Manager section
        if ($User->checkAccess('hca_wom', 3))
            $SwiftMenu->addItem(['title' => 'Work Orders', 'link' => $URL->link('hca_wom_work_orders'), 'id' => 'hca_wom_work_orders', 'parent_id' => 'hca_fs', 'level' => 1]);

        if ($User->checkAccess('hca_wom', 1))
            $SwiftMenu->addItem(['title' => 'Quick Task Entry', 'link' => $URL->link('hca_wom_work_order_new'), 'id' => 'hca_wom_work_order_new', 'parent_id' => 'hca_fs', 'level' => 2]);

        if ($User->checkAccess('hca_wom', 4))
            $SwiftMenu->addItem(['title' => 'Suggested Work Orders', 'link' => $URL->link('hca_wom_work_orders_suggested'), 'id' => 'hca_wom_work_orders_suggested', 'parent_id' => 'hca_fs', 'level' => 2]);

        //if ($User->checkAccess('hca_wom', 5))
        //    $SwiftMenu->addItem(['title' => 'Work Order Templates', 'link' => $URL->link('hca_wom_work_orders_suggested'), 'id' => 'hca_wom_work_orders_suggested', 'parent_id' => 'hca_fs', 'level' => 2]);

        //if ($User->checkAccess('hca_wom', 6))
        //    $SwiftMenu->addItem(['title' => 'Recurring Work Orders', 'link' => $URL->link('hca_wom_work_orders_suggested'), 'id' => 'hca_wom_work_orders_suggested', 'parent_id' => 'hca_fs', 'level' => 2]);

        if ($User->checkAccess('hca_wom', 7))
            $SwiftMenu->addItem(['title' => 'Work Orders Report', 'link' => $URL->link('hca_wom_work_orders_report'), 'id' => 'hca_wom_work_orders_report', 'parent_id' => 'hca_fs', 'level' => 3]);

        // Technician section
        if ($User->checkAccess('hca_wom', 11))
            $SwiftMenu->addItem(['title' => 'To-Do List', 'link' => $URL->genLink('hca_wom_tasks', ['section' => 'active']), 'id' => 'hca_wom_tasks_active', 'parent_id' => 'hca_fs', 'level' => 3]);
            
        if ($User->checkAccess('hca_wom', 12))
            $SwiftMenu->addItem(['title' => 'Unassigned', 'link' => $URL->genLink('hca_wom_tasks', ['section' => 'unassigned']), 'id' => 'hca_wom_tasks_unassigned', 'parent_id' => 'hca_fs', 'level' => 3]);

        if ($User->checkAccess('hca_wom', 13))
            $SwiftMenu->addItem(['title' => 'Completed', 'link' => $URL->genLink('hca_wom_tasks', ['section' => 'completed']), 'id' => 'hca_wom_tasks_completed', 'parent_id' => 'hca_fs', 'level' => 3]);
            
        if ($User->checkAccess('hca_wom', 14))
            $SwiftMenu->addItem(['title' => 'Suggest New WO', 'link' => $URL->link('hca_wom_work_order_suggest'), 'id' => 'hca_wom_work_order_suggest', 'parent_id' => 'hca_fs', 'level' => 3]);

        if ($User->checkAccess('hca_wom', 15))
            $SwiftMenu->addItem(['title' => 'View Suggested WO', 'link' => $URL->link('hca_wom_work_orders_suggested'), 'id' => 'hca_wom_work_orders_suggested', 'parent_id' => 'hca_fs', 'level' => 3]);


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
