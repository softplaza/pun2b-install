<?php

if (!defined('DB_CONFIG')) die();

function hca_wom_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    // for property manager
    $urls['hca_wom_work_order_new'] = 'apps/hca_wom/work_order_new.php?id=$1';
    $urls['hca_wom_work_order'] = 'apps/hca_wom/work_order.php?id=$1';
    $urls['hca_wom_work_orders'] = 'apps/hca_wom/work_orders.php';
    $urls['hca_wom_work_orders_suggested'] = 'apps/hca_wom/work_orders_suggested.php';
    $urls['hca_wom_work_orders_report'] = 'apps/hca_wom/work_orders_report.php';
    $urls['hca_wom_print'] = 'apps/hca_wom/print.php';

    // for technician
    $urls['hca_wom_task'] = 'apps/hca_wom/task.php?id=$1';
    $urls['hca_wom_tasks'] = 'apps/hca_wom/tasks.php';
    $urls['hca_wom_work_order_suggest'] = 'apps/hca_wom/work_order_suggest.php';

    // ajax requests
    $urls['hca_wom_ajax_get_units'] = 'apps/hca_wom/ajax/get_units.php';
    $urls['hca_wom_ajax_get_items'] = 'apps/hca_wom/ajax/get_items.php';
    $urls['hca_wom_ajax_manage_task'] = 'apps/hca_wom/ajax/manage_task.php';
    $urls['hca_wom_ajax_quick_manage_task'] = 'apps/hca_wom/ajax/quick_manage_task.php';
    $urls['hca_wom_ajax_manage_tpl_task'] = 'apps/hca_wom/ajax/manage_tpl_task.php';
    $urls['hca_wom_ajax_get_wo_template'] = 'apps/hca_wom/ajax/get_wo_template.php';
    $urls['hca_wom_ajax_get_new_task'] = 'apps/hca_wom/ajax/get_new_task.php';
    $urls['hca_wom_ajax_search_dupe_wo'] = 'apps/hca_wom/ajax/search_dupe_wo.php';

    // management
    $urls['hca_wom_admin_access'] = 'apps/hca_wom/admin_access.php';
    $urls['hca_wom_admin_permissions'] = 'apps/hca_wom/admin_permissions.php';
    $urls['hca_wom_admin_notifications'] = 'apps/hca_wom/admin_notifications.php';
    $urls['hca_wom_admin_settings'] = 'apps/hca_wom/admin_settings.php';
    $urls['hca_wom_admin_items'] = 'apps/hca_wom/admin_items.php?id=$1';
    $urls['hca_wom_admin_templates'] = 'apps/hca_wom/admin_templates.php?id=$1';

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
        if ($User->checkAccess('hca_wom', 1))
            $SwiftMenu->addItem(['title' => 'Work Orders', 'link' => $URL->link('hca_wom_work_orders'), 'id' => 'hca_wom_work_orders', 'parent_id' => 'hca_fs', 'level' => 1]);

        if ($User->checkAccess('hca_wom', 2))
            $SwiftMenu->addItem(['title' => 'New Work Order', 'link' => $URL->link('hca_wom_work_order_new', 0), 'id' => 'hca_wom_work_order_new', 'parent_id' => 'hca_fs', 'level' => 2]);

        if ($User->checkAccess('hca_wom', 10))
            $SwiftMenu->addItem(['title' => 'Work Orders Report', 'link' => $URL->link('hca_wom_work_orders_report'), 'id' => 'hca_wom_work_orders_report', 'parent_id' => 'hca_fs', 'level' => 3]);

        // Technician section
        if ($User->checkAccess('hca_wom', 5))
        {
            $SwiftMenu->addItem(['title' => 'To-Do List', 'link' => $URL->genLink('hca_wom_tasks', ['section' => 'active']), 'id' => 'hca_wom_tasks_active', 'parent_id' => 'hca_fs', 'level' => 3]);

            $SwiftMenu->addItem(['title' => 'Completed', 'link' => $URL->genLink('hca_wom_tasks', ['section' => 'completed']), 'id' => 'hca_wom_tasks_active', 'parent_id' => 'hca_fs', 'level' => 4]);
        }

        if ($User->checkAccess('hca_wom', 4))
            $SwiftMenu->addItem(['title' => 'Suggest New WO', 'link' => $URL->link('hca_wom_work_order_suggest'), 'id' => 'hca_wom_work_order_suggest', 'parent_id' => 'hca_fs', 'level' => 5]);

        if ($User->checkAccess('hca_wom', 3))
            $SwiftMenu->addItem(['title' => 'Suggested Work Orders', 'link' => $URL->link('hca_wom_work_orders_suggested'), 'id' => 'hca_wom_work_orders_suggested', 'parent_id' => 'hca_fs', 'level' => 5]);

        if ($User->checkAccess('hca_wom', 50) || $User->checkAccess('hca_wom', 53) || $User->checkAccess('hca_wom', 54) || $User->checkAccess('hca_wom', 55))
        {
            $SwiftMenu->addItem(['title' => 'WO Setup', 'link' => '#', 'id' => 'hca_wom_management', 'parent_id' => 'hca_fs', 'level' => 10]);

            if ($User->checkAccess('hca_wom', 50))
                $SwiftMenu->addItem(['title' => 'Access', 'link' => $URL->link('hca_wom_admin_access'), 'id' => 'hca_wom_admin_access', 'parent_id' => 'hca_wom_management', 'level' => 50]);

            if ($User->checkAccess('hca_wom', 53))
                $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('hca_wom_admin_settings'), 'id' => 'hca_wom_admin_settings', 'parent_id' => 'hca_wom_management', 'level' => 53]);

            if ($User->checkAccess('hca_wom', 54))
                $SwiftMenu->addItem(['title' => 'Work Order Templates', 'link' => $URL->link('hca_wom_admin_templates', 0), 'id' => 'hca_wom_admin_templates', 'parent_id' => 'hca_wom_management', 'level' => 54]);

            if ($User->checkAccess('hca_wom', 55))
                $SwiftMenu->addItem(['title' => 'Work Order Items', 'link' => $URL->link('hca_wom_admin_items', 0), 'id' => 'hca_wom_admin_items', 'parent_id' => 'hca_wom_management', 'level' => 55]);
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

    public function ReportBody()
    {
        global $DBLayer, $URL;

        $query = [
            'SELECT'	=> 't.*, p.pro_name, tp.type_name, pb.problem_name',
            'FROM'		=> 'hca_wom_tasks AS t',
            'JOINS'		=> [
                [
                    'INNER JOIN'	=> 'hca_wom_work_orders AS w',
                    'ON'			=> 'w.id=t.work_order_id'
                ],
                [
                    'INNER JOIN'	=> 'sm_property_db AS p',
                    'ON'			=> 'p.id=w.property_id'
                ],
                [
                    'LEFT JOIN'		=> 'hca_wom_items AS i',
                    'ON'			=> 'i.id=t.item_id'
                ],
                [
                    'LEFT JOIN'		=> 'hca_wom_types AS tp',
                    'ON'			=> 'tp.id=i.item_type'
                ],
                [
                    'LEFT JOIN'		=> 'hca_wom_problems AS pb',
                    'ON'			=> 'pb.id=t.task_action'
                ],
            ],
            'ORDER BY'	=> 't.dt_completed',
            'WHERE'     => 't.task_status!=4'
        ];
        //if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
        $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
        $column_data = $pie_data = [];
        while ($row = $DBLayer->fetch_assoc($result))
        {
            if ($row['pro_name'] != '')
            {
                $pro_name = '"'.$row['pro_name'].'"';
                $column_data[$pro_name] = (isset($column_data[$pro_name])) ? ++$column_data[$pro_name] : 1;
            }
        }
?>
     <div class="col-xxl-4 col-xl-6 mb-3">
        <h4 class="card-title"><a href="<?=$URL->link('hca_wom_work_orders')?>">Property Work Orders</a></h4>
        <hr class="my-2">
        <div id="chart_hca_wom"></div>
    </div>

<script>
var options5 = {
    series: [{
        name: 'Num tasks',
        data: [<?php echo implode(',', array_values($column_data)) ?>]
    }],
        chart: {
        type: 'bar',
        height: 265,
        width: '100%',
        toolbar: {
            show: false
        }
    },
    plotOptions: {
        bar: {
        horizontal: false,
        }
    },
    dataLabels: {
        enabled: false
    },
    title: {
        text: 'Number of opened property tasks'
    },
    dataLabels: {
        enabled: true,
        
        style: {
        fontSize: '12px',
        colors: ['#fff']
        }
    },
    stroke: {
        show: true,
        width: 1,
        colors: ['#fff']
    },
    legend: {
        position: 'top',
        horizontalAlign: 'left',
        offsetX: 50
    },
    xaxis: {
        categories: [<?php echo implode(',', array_keys($column_data)) ?>],
    }
};

var chart = new ApexCharts(document.querySelector("#chart_hca_wom"), options5);
chart.render();
</script>

<?php
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAdminAccess', ['HcaWOMHooks', 'ProfileAdminAccess']);

Hook::addAction('ReportBody', ['HcaWOMHooks', 'ReportBody']);
