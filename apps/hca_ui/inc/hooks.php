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

    function ReportBody()
    {
        global $DBLayer, $URL;

        $search_by_period = isset($_GET['period']) ? intval($_GET['period']) : 12;
        $search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;

        $num_never_inspected = 0;

        $DateTime = new DateTime();

        $search_query = [];

        if ($search_by_period == 1)
        {
            $DateTime->modify('-1 month');
            $search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
        }
        else if ($search_by_period == 3)
        {
            $DateTime->modify('-3 months');
            $search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
        }
        else if ($search_by_period == 6)
        {
            $DateTime->modify('-6 months');
            $search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
        }
        else
        {
            $DateTime->modify('-12 months');
            $search_query[] = 'DATE(ch.date_inspected) > \''.$DBLayer->escape($DateTime->format('Y-m-d')).'\'';
        }

        if ($search_by_property_id > 0)
            $search_query[] = 'ch.property_id='.$search_by_property_id;

        $query = [
            'SELECT'	=> 'ch.*',
            'FROM'		=> 'hca_ui_checklist as ch',
        ];
        if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
        $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
        $projects = $inspected_properties_ids = $inspected_units_ids = [];
        while ($row = $DBLayer->fetch_assoc($result)) {
            $projects[] = $row;

            $inspected_properties_ids[$row['property_id']] = $row['property_id'];
            $inspected_units_ids[$row['unit_id']] = $row['unit_id'];
        }

        if (!empty($projects))
        {
            $query = array(
                'SELECT'	=> 'un.id, un.unit_number, un.property_id',
                'FROM'		=> 'sm_property_units AS un',
            );
            if (!empty($inspected_properties_ids))
                $query['WHERE'] = 'un.property_id IN ('.implode(',', $inspected_properties_ids).')';
            $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
            while ($row = $DBLayer->fetch_assoc($result))
            {
                if (!in_array($row['id'], $inspected_units_ids))
                    ++$num_never_inspected;	
            }

            $total = $due_projects = $completed = $pending_inspections = $pending_wo = 0;
            $time_now = time() - 2592000; // 2592000 = 30 days
            foreach($projects as $project)
            {
                // Overdue WO only
                if (($project['work_order_completed'] < 2 || $project['inspection_completed'] == 1) && $time_now < strtotime($project['date_inspected']))
                    ++$due_projects;

                if ($project['inspection_completed'] < 2)
                    ++$pending_inspections;

                if ($project['work_order_completed'] == 1 && $project['inspection_completed'] == 2 && $project['num_problem'] > 0)
                    ++$pending_wo;

                if ($project['work_order_completed'] == 2 && $project['inspection_completed'] == 2)
                    ++$completed;

                ++$total;
            }

            $due_date = date('Y-m-d', strtotime('- 1 month'));
            $output = [];
/*
            if ($due_projects > 0)
                $output[] = '<a href="'.$URL->genLink('hca_ui_inspections', ['date_from' => $due_date]).'" class="d-flex justify-content-between badge bg-danger text-white me-1">Overdue Work Orders <span class="badge badge-secondary fw-bolder">'.$due_projects.'</span></a>';

            if ($pending_inspections > 0)
                $output[] = '<a href="'.$URL->genLink('hca_ui_inspections', ['status' => 1]).'" class="d-flex justify-content-between badge bg-warning text-white me-1">Pending Inspections <span class="badge badge-secondary fw-bolder">'.$pending_inspections.'</span></a>';
            
            if ($pending_wo > 0)
                $output[] = '<a href="'.$URL->genLink('hca_ui_inspections', ['status' => 2]).'" class="d-flex justify-content-between badge bg-primary text-white me-1">Pending WO <span class="badge badge-secondary fw-bolder">'.$pending_wo.'</span></a>';

            if ($num_never_inspected > 0)
                $output[] = '<a href="'.$URL->genLink('hca_ui_inspections', ['status' => 1]).'" class="d-flex justify-content-between badge bg-secondary text-white me-1">Not Inspected Units <span class="badge badge-secondary fw-bolder">'.$num_never_inspected.'</span></a>';

            if ($completed > 0)
                $output[] = '<a href="'.$URL->genLink('hca_ui_inspections', ['status' => 4]).'" class="d-flex justify-content-between badge bg-success text-white me-1">Completed <span class="badge badge-secondary fw-bolder">'.$completed.'</span></a>';
*/
?>

    <div class="col-xxl-4 col-xl-6 mb-3">
        <div class="card flex-fill">
            <div class="card-body my-0 pt-0">
                <h4 class="card-title"><a href="<?=$URL->genLink('hca_ui_inspections')?>">Plumbing Inspections</a></h4>
                <hr class="my-2">

                <div id="chart_hca_ui_pie"></div>

                    <ul class="list-group list-group-flush border-dashed mb-0">

                        <li class="list-group-item px-0 pb-0">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle fa-lg text-danger"></i>
                                </div>
                                <div class="flex-grow-1 ms-2">
                                    <h6 class="mb-1"><a href="<?=$URL->genLink('hca_ui_inspections', ['status' => 1])?>">Overdue Work Orders</a></h6>
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
                                    <h6 class="mb-1"><a href="<?=$URL->genLink('hca_ui_inspections', ['status' => 1])?>">Pending Inspections</a></h6>
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
                                    <h6 class="mb-1"><a href="<?=$URL->genLink('hca_ui_inspections', ['status' => 2])?>">Pending WO</a></h6>
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
                                    <i class="fas fa-times-circle fa-lg text-secondary"></i>
                                </div>
                                <div class="flex-grow-1 ms-2">
                                    <h6 class="mb-1">Not Inspected Units</h6>
                                    <p class="fs-12 mb-0 text-muted">Not Inspected Units</p>
                                </div>
                                <div class="flex-shrink-0 text-end">
                                    <h5 class="mb-1"><?=$num_never_inspected?></h5>
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
                                    <h6 class="mb-1"><a href="<?=$URL->genLink('hca_ui_inspections', ['status' => 4])?>">Completed</a></h6>
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
        </div>
    </div>

<?php
        }
    }
}


//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAdminAccess', ['HcaUIHooks', 'ProfileAdminAccess']);

Hook::addAction('ProfileAboutMyProjects', ['HcaUIHooks', 'ProfileAboutMyProjects']);

Hook::addAction('ReportBody', ['HcaUIHooks', 'ReportBody']);