<?php

if (!defined('DB_CONFIG')) die();

function hca_inventory_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    $app_id = 'hca_inventory';

    $urls['hca_inventory_add'] = 'apps/'.$app_id.'/add.php';
    $urls['hca_inventory_edit'] = 'apps/'.$app_id.'/edit.php?id=$1';
    $urls['hca_inventory_warehouse'] = 'apps/'.$app_id.'/warehouse.php?id=$1';

    $urls['hca_inventory_sign_out'] = 'apps/'.$app_id.'/sign_out.php?id=$1';
    $urls['hca_inventory_reassign'] = 'apps/'.$app_id.'/reassign.php?id=$1';
    $urls['hca_inventory_records'] = 'apps/'.$app_id.'/records.php?id=$1';
    $urls['hca_inventory_report'] = 'apps/'.$app_id.'/report.php';
    $urls['hca_inventory_settings'] = 'apps/'.$app_id.'/settings.php';

    $urls['hca_inventory_ajax_update_permissions'] = 'apps/'.$app_id.'/ajax/update_permissions.php';

    $URL->add_urls($urls);
}

function hca_inventory_IncludeCommon()
{
    global $User, $URL, $Config, $SwiftMenu;

    if ($User->checkAccess('hca_inventory'))
    {
        $SwiftMenu->addItem(['title' => 'Inventory', 'link' =>  $URL->link('hca_inventory_warehouse', 0), 'id' => 'hca_inventory', 'icon' => '<i class="fas fa-warehouse"></i>']);

        // TOP SUB MENU START
        if ($User->checkAccess('hca_inventory', 10))
            $SwiftMenu->addItem(['title' => '+ Add', 'link' =>  $URL->link('hca_inventory_add', 0), 'id' => 'hca_inventory_add', 'parent_id' => 'hca_inventory']);

        if ($User->checkAccess('hca_inventory', 3))
            $SwiftMenu->addItem(['title' => 'Warehouse', 'link' =>  $URL->link('hca_inventory_warehouse', 0), 'id' => 'hca_inventory_warehouse', 'parent_id' => 'hca_inventory']);

        if ($User->checkAccess('hca_inventory', 4))
            $SwiftMenu->addItem(['title' => 'Report', 'link' =>  $URL->link('hca_inventory_report'), 'id' => 'hca_inventory_report', 'parent_id' => 'hca_inventory']);

        if ($User->checkAccess('hca_inventory', 20))
            $SwiftMenu->addItem(['title' => 'Settings', 'link' =>  $URL->link('hca_inventory_settings', 0), 'id' => 'hca_inventory_settings', 'parent_id' => 'hca_inventory']);
    }
}

class HcaInventoryHooks
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
            3 => 'Warehouse',
            4 => 'Report',
        
            10 => 'Add equipments',
            11 => 'Edit equipment info',
            12 => 'Delete equipments',
            13 => 'Return equipments',
            14 => 'Reassign equipments',
            15 => 'Sign-Out equipment',

           // 20 => 'Settings',
        ];

        if (check_app_access($access_info, 'hca_inventory'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h5 class="h5 card-title mb-0">Inventory</h5>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_inventory'))
                    echo '<span class="badge badge-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge badge-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAdminAccess', ['HcaInventoryHooks', 'ProfileAdminAccess']);

