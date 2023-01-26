<?php

if (!defined('DB_CONFIG')) die();

function hca_hvac_inspections_co_modify_url_scheme()
{
    global $URL;

    $app_id = 'hca_hvac_inspections';
    $urls = [];

    $urls['hca_hvac_inspections_checklist'] = 'apps/'.$app_id.'/checklist.php?id=$1';
    $urls['hca_hvac_inspections_checklist2'] = 'apps/'.$app_id.'/checklist2.php?id=$1';
    $urls['hca_hvac_inspections_work_order'] = 'apps/'.$app_id.'/work_order.php?id=$1';
    $urls['hca_hvac_inspections_buildings'] = 'apps/'.$app_id.'/buildings.php?id=$1';
    $urls['hca_hvac_inspections_inspections'] = 'apps/'.$app_id.'/inspections.php';
    $urls['hca_hvac_inspections_alarms'] = 'apps/'.$app_id.'/alarms.php';

    $urls['hca_hvac_inspections_appendixb'] = 'apps/'.$app_id.'/appendixb.php?id=$1';
    $urls['hca_hvac_inspections_files'] = 'apps/'.$app_id.'/files.php?id=$1';
    $urls['hca_hvac_inspections_summary_report'] = 'apps/'.$app_id.'/summary_report.php';
    $urls['hca_hvac_inspections_property_report'] = 'apps/'.$app_id.'/property_report.php';
    $urls['hca_hvac_inspections_print'] = 'apps/'.$app_id.'/print.php';

    //$urls['hca_hvac_inspections_water_pressure'] = 'apps/'.$app_id.'/water_pressure.php?id=$1';
    //$urls['hca_hvac_inspections_water_pressure_report'] = 'apps/'.$app_id.'/water_pressure_report.php?id=$1';

    $urls['hca_hvac_inspections_ajax_get_units'] = 'apps/'.$app_id.'/ajax/get_units.php';
    $urls['hca_hvac_inspections_ajax_get_checklist_items'] = 'apps/'.$app_id.'/ajax/get_checklist_items.php';
    $urls['hca_hvac_inspections_ajax_edit_checklist_item'] = 'apps/'.$app_id.'/ajax/edit_checklist_item.php';
    $urls['hca_hvac_inspections_ajax_edit_work_order_item'] = 'apps/'.$app_id.'/ajax/edit_work_order_item.php';
    $urls['hca_hvac_inspections_ajax_get_property_info'] = 'apps/'.$app_id.'/ajax/get_property_info.php';
    //$urls['hca_hvac_inspections_ajax_update_checklist'] = 'apps/'.$app_id.'/ajax/update_checklist.php';
    $urls['hca_hvac_inspections_ajax_reassign_project'] = 'apps/'.$app_id.'/ajax/reassign_project.php';
    $urls['hca_hvac_inspections_ajax_get_water_pressure'] = 'apps/'.$app_id.'/ajax/get_water_pressure.php';

    $urls['hca_hvac_inspections_ajax_get_uploaded_images'] = 'apps/'.$app_id.'/ajax/get_uploaded_images.php';
    $urls['hca_hvac_inspections_ajax_upload_files'] = 'apps/'.$app_id.'/ajax/upload_files.php';

    //Management
    $urls['hca_hvac_inspections_items'] = 'apps/'.$app_id.'/items.php?id=$1';
    $urls['hca_hvac_inspections_po_numbers'] = 'apps/'.$app_id.'/po_numbers.php?id=$1';
    $urls['hca_hvac_inspections_filters'] = 'apps/'.$app_id.'/filters.php?id=$1';
    $urls['hca_hvac_inspections_settings'] = 'apps/'.$app_id.'/settings.php';

    $URL->add_urls($urls);
}

function hca_hvac_inspections_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;
    
    if ($User->checkAccess('hca_hvac_inspections'))
    {
        // Main item of Menu
        $SwiftMenu->addItem(['title' => 'HVAC Inspections', 'link' => '#', 'id' => 'hca_hvac_inspections', 'parent_id' => 'hca_ui', 'level' => 10]);

        //if ($User->checkAccess('hca_hvac_inspections', 1))
            //$SwiftMenu->addItem(['title' => '+ New inspection OLD', 'link' => $URL->link('hca_hvac_inspections_checklist', 0), 'id' => 'hca_hvac_inspections_checklist', 'parent_id' => 'hca_hvac_inspections', 'level' => 1]);

        if ($User->checkAccess('hca_hvac_inspections', 1))
            $SwiftMenu->addItem(['title' => '+ New inspection', 'link' => $URL->link('hca_hvac_inspections_checklist2', 0), 'id' => 'hca_hvac_inspections_checklist2', 'parent_id' => 'hca_hvac_inspections', 'level' => 2]);

        if ($User->checkAccess('hca_hvac_inspections', 4))
            $SwiftMenu->addItem(['title' => 'Inspections/Work Orders', 'link' => $URL->link('hca_hvac_inspections_inspections', ''), 'id' => 'hca_hvac_inspections_inspections', 'parent_id' => 'hca_hvac_inspections', 'level' => 4]);

        if ($User->checkAccess('hca_hvac_inspections', 5))
            $SwiftMenu->addItem(['title' => 'CO Test Log', 'link' => $URL->link('hca_hvac_inspections_alarms', 0), 'id' => 'hca_hvac_inspections_alarms', 'parent_id' => 'hca_hvac_inspections', 'level' => 5]);

         if ($User->checkAccess('hca_hvac_inspections', 6))
         //if ($User->is_admmod())
            $SwiftMenu->addItem(['title' => 'Summary Report', 'link' => $URL->link('hca_hvac_inspections_summary_report', ''), 'id' => 'hca_hvac_inspections_summary_report', 'parent_id' => 'hca_hvac_inspections', 'level' => 6]);

        if ($User->checkAccess('hca_hvac_inspections', 20))
        {
            $SwiftMenu->addItem(['title' => 'Items', 'link' => $URL->link('hca_hvac_inspections_items', 0), 'id' => 'hca_hvac_inspections_items', 'parent_id' => 'hca_hvac_inspections', 'level' => 22]);

            $SwiftMenu->addItem(['title' => 'P.O. Numbers', 'link' => $URL->link('hca_hvac_inspections_po_numbers', 0), 'id' => 'hca_hvac_inspections_po_numbers', 'parent_id' => 'hca_hvac_inspections', 'level' => 23]);

            $SwiftMenu->addItem(['title' => 'Filters', 'link' => $URL->link('hca_hvac_inspections_filters', 0), 'id' => 'hca_hvac_inspections_filters', 'parent_id' => 'hca_hvac_inspections', 'level' => 24]);

            $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('hca_hvac_inspections_settings'), 'id' => 'hca_hvac_inspections_settings', 'parent_id' => 'hca_hvac_inspections', 'level' => 25]);
        }
    }
}

class HcaHVACInspectionsHooks
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
            1 => 'CheckList',
            4 => 'List of Inspections',
            5 => 'Smoke Alarms & CO Test',
            6 => 'Summary Report',
            9 => 'Add/Edit items in Itemslist',
            10 => 'Add/Edit filter sizes',
            11 => 'Edit CheckList',
            12 => 'Edit Work Order',
            13 => 'Delete CheckList',
            15 => 'Reassign projects',
            17 => 'View list of actions',
            18 => 'Upload images',
            19 => 'Delete images',
        ];

        if (check_app_access($access_info, 'hca_hvac_inspections'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h5 class="h5 card-title mb-0">HVAC Inspections</h5>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_hvac_inspections'))
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
        $search_query[] = 'DATE(ch.datetime_inspection_start) > \''.$DBLayer->escape($period).'\'';
        $query = [
            'SELECT'	=> 'ch.*, p.pro_name, un.unit_number',
            'FROM'		=> 'hca_hvac_inspections_checklist as ch',
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
            $time_now = time() - 2592000; // 30 days
            foreach($projects as $project)
            {
                if ($project['inspection_completed'] == 2 && $project['work_order_completed'] == 1 && strtotime($project['datetime_inspection_start']) > $time_now)
                    ++$due_projects;

                if ($project['inspection_completed'] == 1)
                    ++$pending_inspections;

                if ($project['inspection_completed'] == 2 && $project['work_order_completed'] == 1)
                    ++$pending_wo;

                if ($project['inspection_completed'] == 2 && $project['work_order_completed'] == 2 || $project['inspection_completed'] == 2 && $project['work_order_completed'] == 0 && $project['completed_by'] == $user_id)
                    ++$completed;

                ++$total;
            }

            $date_from = date('Y-m-d', strtotime('- 1 month'));
            $output = [];
            
            if ($due_projects > 0)
                $output[] = '<a href="'.$URL->genLink('hca_hvac_inspections_inspections', ['status' => 2, 'user_id' => $user_id, 'date_from' => $date_from]).'" class="badge bg-danger text-white me-1">Overdue Work Orders <span class="badge badge-secondary fw-bolder">'.$due_projects.'</span></a>';

            if ($pending_inspections > 0)
                $output[] = '<a href="'.$URL->genLink('hca_hvac_inspections_inspections', ['status' => 1, 'user_id' => $user_id]).'" class="badge bg-secondary text-white me-1">Pending inspections <span class="badge badge-secondary fw-bolder">'.$pending_inspections.'</span></a>';

            if ($pending_wo > 0)
                $output[] = '<a href="'.$URL->genLink('hca_hvac_inspections_inspections', ['status' => 2, 'user_id' => $user_id]).'" class="badge bg-warning text-white me-1">Pending WO  <span class="badge badge-secondary fw-bolder">'.$pending_wo.'</span></a>';

            if ($completed > 0)
                $output[] = '<a href="'.$URL->genLink('hca_hvac_inspections_inspections', ['status' => 3, 'user_id' => $user_id]).'" class="badge bg-success text-white me-1">Completed <span class="badge badge-secondary fw-bolder">'.$completed.'</span></a>';

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
            <h4 class="alert-heading">HVAC Inspections</h4>
            <hr class="my-1">
            <p class="mb-0"><?php echo implode($output) ?></p>
        </div>
<?php
        }
    }

    public function ReportBody()
    {
        global $DBLayer, $URL, $User, $user;

        $search_by_period = isset($_GET['period']) ? intval($_GET['period']) : 12;
        $search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;

        $num_never_inspected = 0;

        $DateTime = new DateTime();

        $search_query = [];

        if ($search_by_period == 1)
        {
            $DateTime->modify('-1 month');
            $search_query[] = 'DATE(ch.datetime_inspection_start) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
        }
        else if ($search_by_period == 3)
        {
            $DateTime->modify('-3 months');
            $search_query[] = 'DATE(ch.datetime_inspection_start) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
        }
        else if ($search_by_period == 6)
        {
            $DateTime->modify('-6 months');
            $search_query[] = 'DATE(ch.datetime_inspection_start) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
        }
        else
        {
            $DateTime->modify('-12 months');
            $search_query[] = 'DATE(ch.datetime_inspection_start) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
        }

        if ($search_by_property_id > 0)
            $search_query[] = 'ch.property_id='.$search_by_property_id;
        $query = [
            'SELECT'	=> 'ch.*, p.pro_name, un.unit_number',
            'FROM'		=> 'hca_hvac_inspections_checklist as ch',
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
            $time_now = time(); // 30 days
            foreach($projects as $project)
            {
                $time_inspection_start = strtotime($project['datetime_inspection_start']) + 2592000;
                if (($project['inspection_completed'] < 2 || $project['work_order_completed'] == 1) && $time_now > $time_inspection_start)
                    ++$due_projects;

                if ($project['inspection_completed'] < 2)
                    ++$pending_inspections;

                if ($project['inspection_completed'] == 2 && $project['work_order_completed'] == 1)
                    ++$pending_wo;

                if ($project['inspection_completed'] == 2 && $project['work_order_completed'] =! 1)
                    ++$completed;

                ++$total;
            }

            $date_from = date('Y-m-d', strtotime('- 1 month'));
            $output = [];
            
/*
            if ($due_projects > 0)
                $output[] = '<a href="'.$URL->genLink('hca_hvac_inspections_inspections', ['date_from' => $date_from]).'" class="d-flex justify-content-between badge bg-danger text-white me-1">Overdue Work Orders <span class="badge badge-secondary fw-bolder">'.$due_projects.'</span></a>';

            if ($pending_inspections > 0)
                $output[] = '<a href="'.$URL->genLink('hca_hvac_inspections_inspections', ['status' => 1]).'" class="d-flex justify-content-between badge bg-warning text-white me-1">Pending inspections <span class="badge badge-secondary fw-bolder">'.$pending_inspections.'</span></a>';

            if ($pending_wo > 0)
                $output[] = '<a href="'.$URL->genLink('hca_hvac_inspections_inspections', ['status' => 2]).'" class="d-flex justify-content-between badge bg-primary text-white me-1">Pending WO  <span class="badge badge-secondary fw-bolder">'.$pending_wo.'</span></a>';

            if ($completed > 0)
                $output[] = '<a href="'.$URL->genLink('hca_hvac_inspections_inspections', ['status' => 3]).'" class="d-flex justify-content-between badge bg-success text-white me-1">Completed <span class="badge badge-secondary fw-bolder">'.$completed.'</span></a>';

            if (empty($output))
                $output[] = '<span class="badge badge-warning me-1">No project activity in the last six months.</span>';
*/
?>
     <div class="col-xxl-4 col-xl-6 mb-3">

        <h4 class="card-title"><a href="<?=$URL->genLink('hca_hvac_inspections_inspections')?>">HVAC Inspections</a></h4>
        <hr class="my-2">
        <div id="chart_hca_hvac_pie"></div>

        <ul class="list-group list-group-flush border-dashed mb-0">

            <li class="list-group-item px-0 pb-0">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle fa-lg text-danger"></i>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <h6 class="mb-1"><a href="<?=$URL->genLink('hca_hvac_inspections_inspections', ['date_from' => $date_from])?>">Overdue Work Orders</a></h6>
                        <p class="fs-12 mb-0 text-muted">Overdue Work Orders</p>
                    </div>
                    <div class="flex-shrink-0 text-end">
                        <h5 class="mb-1"><?=$due_projects?></h5>
                        <p class="text-success fs-12 mb-0"></p>
                    </div>
                </div>
            </li><!-- end -->

            <li class="list-group-item px-0 pb-0">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle fa-lg text-warning"></i>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <h6 class="mb-1"><a href="<?=$URL->genLink('hca_hvac_inspections_inspections', ['status' => 1])?>">Pending Inspections</a></h6>
                        <p class="fs-12 mb-0 text-muted">Pending Inspections</p>
                    </div>
                    <div class="flex-shrink-0 text-end">
                        <h5 class="mb-1"><?=$pending_inspections?></h5>
                        <p class="text-success fs-12 mb-0"></p>
                    </div>
                </div>
            </li><!-- end -->

            <li class="list-group-item px-0 pb-0">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle fa-lg text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <h6 class="mb-1"><a href="<?=$URL->genLink('hca_hvac_inspections_inspections', ['status' => 2])?>">Pending WO</a></h6>
                        <p class="fs-12 mb-0 text-muted">Pending Work Orders</p>
                    </div>
                    <div class="flex-shrink-0 text-end">
                        <h5 class="mb-1"><?=$pending_wo?></h5>
                        <p class="text-success fs-12 mb-0"></p>
                    </div>
                </div>
            </li><!-- end -->

            <li class="list-group-item px-0 pb-0">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle fa-lg text-success"></i>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <h6 class="mb-1"><a href="<?=$URL->genLink('hca_hvac_inspections_inspections', ['status' => 3])?>">Completed</a></h6>
                        <p class="fs-12 mb-0 text-muted">Number of completed projects</p>
                    </div>
                    <div class="flex-shrink-0 text-end">
                        <h5 class="mb-1"><?=$completed?></h5>
                        <p class="text-success fs-12 mb-0"></p>
                    </div>
                </div>
            </li><!-- end -->
        </ul>

    </div>
<?php
        }
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAdminAccess', ['HcaHVACInspectionsHooks', 'ProfileAdminAccess']);

Hook::addAction('ProfileAboutMyProjects', ['HcaHVACInspectionsHooks', 'ProfileAboutMyProjects']);

Hook::addAction('ReportBody', ['HcaHVACInspectionsHooks', 'ReportBody']);
