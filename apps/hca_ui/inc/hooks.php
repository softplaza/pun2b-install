<?php

if (!defined('DB_CONFIG')) die();

function hca_ui_co_modify_url_scheme()
{
    global $URL;

    $app_id = 'hca_ui';
    $urls = [];

    $urls['hca_ui_checklist'] = 'apps/'.$app_id.'/checklist.php?id=$1';
    $urls['hca_ui_work_order'] = 'apps/'.$app_id.'/work_order.php?id=$1';
    $urls['hca_ui_buildings'] = 'apps/'.$app_id.'/buildings.php?id=$1';
    $urls['hca_ui_inspections'] = 'apps/'.$app_id.'/inspections.php';
    
    $urls['hca_ui_appendixb'] = 'apps/'.$app_id.'/appendixb.php?id=$1';
    $urls['hca_ui_files'] = 'apps/'.$app_id.'/files.php?id=$1';

    $urls['hca_ui_summary_report'] = 'apps/'.$app_id.'/summary_report.php';
    $urls['hca_ui_property_report'] = 'apps/'.$app_id.'/property_report.php';

    $urls['hca_ui_print'] = 'apps/'.$app_id.'/print.php';

    $urls['hca_ui_water_pressure'] = 'apps/'.$app_id.'/water_pressure.php?id=$1';
    $urls['hca_ui_water_pressure_report'] = 'apps/'.$app_id.'/water_pressure_report.php?id=$1';

    $urls['hca_ui_ajax_get_units'] = 'apps/'.$app_id.'/ajax/get_units.php';
    $urls['hca_ui_ajax_get_checklist_items'] = 'apps/'.$app_id.'/ajax/get_checklist_items.php';
    $urls['hca_ui_ajax_edit_checklist_item'] = 'apps/'.$app_id.'/ajax/edit_checklist_item.php';
    $urls['hca_ui_ajax_edit_work_order_item'] = 'apps/'.$app_id.'/ajax/edit_work_order_item.php';
    $urls['hca_ui_ajax_get_property_info'] = 'apps/'.$app_id.'/ajax/get_property_info.php';
    //$urls['hca_ui_ajax_update_checklist'] = 'apps/'.$app_id.'/ajax/update_checklist.php';
    $urls['hca_ui_ajax_reassign_project'] = 'apps/'.$app_id.'/ajax/reassign_project.php';
    $urls['hca_ui_ajax_get_water_pressure'] = 'apps/'.$app_id.'/ajax/get_water_pressure.php';

    $urls['hca_ui_ajax_get_uploaded_images'] = 'apps/'.$app_id.'/ajax/get_uploaded_images.php';
    $urls['hca_ui_ajax_upload_files'] = 'apps/'.$app_id.'/ajax/upload_files.php';

    //Management
    $urls['hca_ui_items'] = 'apps/'.$app_id.'/items.php?id=$1';
    $urls['hca_ui_settings'] = 'apps/'.$app_id.'/settings.php';

    $URL->add_urls($urls);
}

function hca_ui_IncludeCommon()
{
    global $User, $SwiftMenu, $URL;
    
    if ($User->checkAccess('hca_ui') || $User->checkAccess('hca_unit_inspections') || $User->checkAccess('hca_hvac_inspections'))
        $SwiftMenu->addItem(['title' => 'Property Inspections', 'link' => '#', 'id' => 'hca_ui', 'icon' => '<i class="fas fa-check-double"></i>', 'level' => 20]);

    if ($User->checkAccess('hca_ui'))
    {
        $SwiftMenu->addItem(['title' => 'Plumbing Inspections', 'link' => '#', 'id' => 'hca_pi', 'parent_id' => 'hca_ui', 'level' => 5]);

        if ($User->checkAccess('hca_ui', 1))
            $SwiftMenu->addItem(['title' => '+ New inspection', 'link' => $URL->link('hca_ui_checklist', 0), 'id' => 'hca_ui_checklist', 'parent_id' => 'hca_pi', 'level' => 1]);

        if ($User->checkAccess('hca_ui', 4))
            $SwiftMenu->addItem(['title' => 'Inspections/Work Orders', 'link' => $URL->link('hca_ui_inspections', 0), 'id' => 'hca_ui_inspections', 'parent_id' => 'hca_pi', 'level' => 3]);

        if ($User->checkAccess('hca_ui', 6))
            $SwiftMenu->addItem(['title' => 'Summary Report', 'link' => $URL->link('hca_ui_summary_report'), 'id' => 'hca_ui_summary_report', 'parent_id' => 'hca_pi', 'level' => 7]);

        // MANAGEMENT
        //$SwiftMenu->addItem(['title' => 'Management', 'link' => '#', 'id' => 'hca_ui_management', 'parent_id' => 'hca_pi', 'level' => 20]);
        if ($User->checkAccess('hca_ui', 20))
            $SwiftMenu->addItem(['title' => 'Items', 'link' => $URL->link('hca_ui_items', 0), 'id' => 'hca_ui_items', 'parent_id' => 'hca_pi', 'level' => 22]);
        if ($User->checkAccess('hca_ui', 20))
            $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('hca_ui_settings'), 'id' => 'hca_ui_settings', 'parent_id' => 'hca_pi', 'level' => 25]);
    }

    // WATER PRESSURE
    if ($User->checkAccess('hca_ui'))
    {
        $SwiftMenu->addItem(['title' => 'Building Water Pressure', 'link' => '#', 'id' => 'hca_ui_bwp', 'parent_id' => 'hca_ui', 'level' => 20]);

        if ($User->checkAccess('hca_ui', 3))
            $SwiftMenu->addItem(['title' => '+ New Water Pressure', 'link' => $URL->link('hca_ui_water_pressure', 0), 'id' => 'hca_ui_water_pressure', 'parent_id' => 'hca_ui_bwp', 'level' => 1]);

        if ($User->checkAccess('hca_ui', 5))
            $SwiftMenu->addItem(['title' => 'Water Pressure Report', 'link' => $URL->link('hca_ui_water_pressure_report', 0), 'id' => 'hca_ui_water_pressure_report', 'parent_id' => 'hca_ui_bwp', 'level' => 5]);
    }
}

class HcaUIHooks
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
            1 => 'New CheckList',
            2 => 'Work Order',
            3 => 'Add Water Pressure',
            4 => 'List of Work Orders',
            5 => 'Water Pressure Report',
            6 => 'Summary Report',
            7 => 'Edit Water Pressure',
            8 => 'Edit item in Checklist',
            9 => 'Edit item in itemslist',
        
            11 => 'Edit CheckList',
            12 => 'Edit Work Order',
            13 => 'Delete CheckList',
            14 => 'Delete Work Order',
            15 => 'Reassign projects',
            16 => 'Delete Water Pressure',
            17 => 'View list of actions',
            18 => 'Upload images',
            19 => 'Delete images',
        ];

        if (check_app_access($access_info, 'hca_ui'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h5 class="h5 card-title mb-0">Plubming Inspections</h5>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_ui'))
                    echo '<span class="badge badge-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge badge-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }

    
    public function ProfileAboutMyProjects()
    {
        global $DBLayer, $URL, $User, $user;

        $user_id = isset($user['id']) ? intval($user['id']) : $User->get('id');
        $period = date('Y-m-d', strtotime('- 6 month'));

        $search_query = [];
        $search_query[] = '(ch.inspected_by='.$user_id.' OR ch.owned_by='.$user_id.' OR ch.completed_by='.$user_id.' OR ch.updated_by='.$user_id.')';
        $search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($period).'\'';

        $query = [
            'SELECT'	=> 'ch.*, p.pro_name, un.unit_number',
            'FROM'		=> 'hca_ui_checklist as ch',
            'JOINS'		=> [
                [
                    'INNER JOIN'	=> 'sm_property_db AS p',
                    'ON'			=> 'p.id=ch.property_id'
                ],
                [
                    'INNER JOIN'	=> 'sm_property_units AS un',
                    'ON'			=> 'un.id=ch.unit_id'
                ],
            ],
        ];
        if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
        $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
        $projects = [];
        while ($row = $DBLayer->fetch_assoc($result)) {
            $projects[] = $row;
        }

        if (!empty($projects))
        {
            $total = $due_projects = $completed = $pending_inspections = $pending_wo = 0;
            $time_now = time() - 2592000;
            foreach($projects as $project)
            {
                // Overdue WO only
                if ($project['work_order_completed'] == 1 && $project['inspection_completed'] == 2 && $project['num_problem'] > 0 && strtotime($project['date_inspected']) > $time_now)
                    ++$due_projects;

                if ($project['inspection_completed'] == 1)
                    ++$pending_inspections;

                if ($project['work_order_completed'] == 1 && $project['inspection_completed'] == 2 && $project['num_problem'] > 0)
                    ++$pending_wo;

                if ($project['work_order_completed'] == 2 && $project['inspection_completed'] == 2 && $project['completed_by'] == $user_id)
                    ++$completed;

                ++$total;
            }

            $date = date('Y-m-d', strtotime('- 1 month'));
            $output = [];

            if ($due_projects > 0)
                $output[] = '<a href="'.$URL->genLink('hca_ui_inspections', ['status' => 2, 'user_id' => $user_id, 'date_from' => $date]).'" class="badge bg-danger text-white me-1">Overdue Work Orders <span class="badge badge-secondary fw-bolder">'.$due_projects.'</span></a>';

            if ($pending_inspections > 0)
                $output[] = '<a href="'.$URL->genLink('hca_ui_inspections', ['status' => 1, 'user_id' => $user_id]).'" class="badge bg-secondary text-white me-1">Pending inspections <span class="badge badge-secondary fw-bolder">'.$pending_inspections.'</span></a>';
            
            if ($pending_wo > 0)
                $output[] = '<a href="'.$URL->genLink('hca_ui_inspections', ['status' => 2, 'user_id' => $user_id]).'" class="badge bg-warning text-white me-1">Pending WO <span class="badge badge-secondary fw-bolder">'.$pending_wo.'</span></a>';

            if ($completed > 0)
                $output[] = '<a href="'.$URL->genLink('hca_ui_inspections', ['status' => 3, 'user_id' => $user_id]).'" class="badge bg-success text-white me-1">Completed <span class="badge badge-secondary fw-bolder">'.$completed.'</span></a>';

            if (empty($output))
                $output[] = '<span class="badge badge-warning me-1">No project activity in the last six months.</span>';

            $main_css = 'callout-success bd-callout-success';
            if ($due_projects > 0)
                $main_css = 'callout-danger bd-callout-danger';
            else if ($pending_inspections > 0)
                $main_css = 'callout-secondary bd-callout-secondary';
            else if ($pending_wo > 0)
                $main_css = 'callout-warning bd-callout-warning';
?>
        <div class="callout <?=$main_css?> mb-3">
            <h4 class="alert-heading">Plumbing Inspections</h4>
            <hr class="my-1">
            <p class="mb-0"> <?php echo implode($output) ?></p>
        </div>
<?php
        }
    }

    public function IndexBody()
    {
        global $DBLayer, $URL, $User, $user;

        if ($User->checkAccess('hca_ui'))
        {
            //$user_id = isset($user['id']) ? intval($user['id']) : $User->get('id');
            $period = date('Y-m-d', strtotime('- 6 month'));

            $search_query = [];
            //$search_query[] = '(ch.inspected_by='.$user_id.' OR ch.owned_by='.$user_id.' OR ch.completed_by='.$user_id.' OR ch.updated_by='.$user_id.')';
            $search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($period).'\'';

            $query = [
                'SELECT'	=> 'ch.*, p.pro_name, un.unit_number',
                'FROM'		=> 'hca_ui_checklist as ch',
                'JOINS'		=> [
                    [
                        'INNER JOIN'	=> 'sm_property_db AS p',
                        'ON'			=> 'p.id=ch.property_id'
                    ],
                    [
                        'INNER JOIN'	=> 'sm_property_units AS un',
                        'ON'			=> 'un.id=ch.unit_id'
                    ],
                ],
            ];
            if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
            $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
            $projects = [];
            while ($row = $DBLayer->fetch_assoc($result)) {
                $projects[] = $row;
            }

            if (!empty($projects))
            {
                $total = $due_projects = $completed = $pending_inspections = $pending_wo = 0;
                $time_now = time() - 2592000;
                foreach($projects as $project)
                {
                    // Overdue WO only
                    if ($project['work_order_completed'] == 1 && $project['inspection_completed'] == 2 && $project['num_problem'] > 0 && strtotime($project['date_inspected']) > $time_now)
                        ++$due_projects;

                    if ($project['inspection_completed'] == 1)
                        ++$pending_inspections;

                    if ($project['work_order_completed'] == 1 && $project['inspection_completed'] == 2 && $project['num_problem'] > 0)
                        ++$pending_wo;

                    if ($project['work_order_completed'] == 2 && $project['inspection_completed'] == 2)
                        ++$completed;

                    ++$total;
                }

                $date = date('Y-m-d', strtotime('- 1 month'));
                $output = [];

                if ($due_projects > 0)
                    $output[] = '<a href="'.$URL->genLink('hca_ui_inspections', ['status' => 2, 'date_from' => $date]).'" class="badge bg-danger text-white me-1">Overdue Work Orders <span class="badge badge-secondary fw-bolder">'.$due_projects.'</span></a>';

                if ($pending_inspections > 0)
                    $output[] = '<a href="'.$URL->genLink('hca_ui_inspections', ['status' => 1]).'" class="badge bg-secondary text-white me-1">Pending inspections <span class="badge badge-secondary fw-bolder">'.$pending_inspections.'</span></a>';
                
                if ($pending_wo > 0)
                    $output[] = '<a href="'.$URL->genLink('hca_ui_inspections', ['status' => 2]).'" class="badge bg-warning text-white me-1">Pending WO <span class="badge badge-secondary fw-bolder">'.$pending_wo.'</span></a>';

                if ($completed > 0)
                    $output[] = '<a href="'.$URL->genLink('hca_ui_inspections', ['status' => 3]).'" class="badge bg-success text-white me-1">Completed <span class="badge badge-secondary fw-bolder">'.$completed.'</span></a>';

                if (empty($output))
                    $output[] = '<span class="badge badge-warning me-1">No project activity in the last six months.</span>';

                $main_css = 'callout-success bd-callout-success';
                if ($due_projects > 0)
                    $main_css = 'callout-danger bd-callout-danger';
                else if ($pending_inspections > 0)
                    $main_css = 'callout-secondary bd-callout-secondary';
                else if ($pending_wo > 0)
                    $main_css = 'callout-warning bd-callout-warning';
?>
        <div class="callout <?=$main_css?> mb-3">
            <h4 class="alert-heading">Plumbing Inspections</h4>
            <hr class="my-1">
            <p class="mb-0"> <?php echo implode($output) ?></p>
        </div>

        <div class="col-md-3">
            <div id="chart"></div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
var options = {
  chart: {
    type: 'bar'
  },
  series: [{
    name: 'sales',
    data: [30,40,45,50,49,60,70,91,125]
  }],
  xaxis: {
    categories: [1991,1992,1993,1994,1995,1996,1997, 1998,1999]
  }
}

var chart = new ApexCharts(document.querySelector("#chart"), options);

chart.render();
        </script>
<?php
            }
        }
    }
}


//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAdminAccess', ['HcaUIHooks', 'ProfileAdminAccess']);

Hook::addAction('ProfileAboutMyProjects', ['HcaUIHooks', 'ProfileAboutMyProjects']);

Hook::addAction('IndexBody', ['HcaUIHooks', 'IndexBody']);