<?php

if (!defined('DB_CONFIG')) die();

function hca_mi_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    $app_id = 'hca_mi';

    $urls['hca_5840_new_project'] = 'apps/'.$app_id.'/new_project.php';
    $urls['hca_5840_projects'] = 'apps/'.$app_id.'/projects.php?section=$1&id=$2';
    $urls['hca_5840_projects_report'] = 'apps/'.$app_id.'/projects_report.php?section=view';
    $urls['hca_mi_property_report'] = 'apps/'.$app_id.'/property_report.php';
    
    $urls['hca_5840_manage_project'] = 'apps/'.$app_id.'/manage_project.php?id=$1';
    $urls['hca_5840_manage_files'] = 'apps/'.$app_id.'/manage_files.php?id=$1';
    $urls['hca_5840_manage_invoice'] = 'apps/'.$app_id.'/manage_invoice.php?id=$1';
    $urls['hca_5840_manage_appendixb'] = 'apps/'.$app_id.'/manage_appendixb.php?id=$1';
    $urls['hca_mi_project_tracking'] = 'apps/'.$app_id.'/project_tracking.php?id=$1';
    
    $urls['hca_5840_form'] = 'apps/'.$app_id.'/form.php?id=$1&hash=$2';
    $urls['hca_5840_forms_mailed'] = 'apps/'.$app_id.'/forms_mailed.php';
    $urls['hca_5840_forms_submitted'] = 'apps/'.$app_id.'/forms_submitted.php';
    $urls['hca_5840_forms_confirmed'] = 'apps/'.$app_id.'/forms_confirmed.php';
    
    // Admin
    $urls['hca_5840_admin_settings'] = 'apps/'.$app_id.'/admin_settings.php';
    $urls['hca_5840_admin_vendors'] = 'apps/'.$app_id.'/admin_vendors.php';
    $urls['hca_5840_admin_access'] = 'apps/'.$app_id.'/admin_access.php';
    $urls['hca_5840_admin_permissions'] = 'apps/'.$app_id.'/admin_permissions.php';
    $urls['hca_5840_admin_notifications'] = 'apps/'.$app_id.'/admin_notifications.php';

    // Ajax
    $urls['hca_5840_ajax_update_invoice'] = 'apps/'.$app_id.'/ajax/update_invoice.php';
    $urls['hca_5840_ajax_get_units'] = 'apps/'.$app_id.'/ajax/get_units.php';
    $urls['hca_5840_ajax_get_events'] = 'apps/'.$app_id.'/ajax/get_events.php';
    $urls['hca_5840_ajax_send_project_info_by_email'] = 'apps/'.$app_id.'/ajax/send_project_info_by_email.php';
    $urls['hca_5840_ajax_get_unit_positions'] = 'apps/'.$app_id.'/ajax/get_unit_positions.php';
    $urls['hca_5840_ajax_update_default_vendor'] = 'apps/'.$app_id.'/ajax/update_default_vendor.php';

    $URL->add_urls($urls);
}

function hca_mi_IncludeCommon()
{
    global $User, $SwiftMenu, $URL;

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // Main menu item
    if ($User->checkAccess('hca_mi'))
        $SwiftMenu->addItem(['title' => 'Moisture Inspections', 'link' => '#', 'id' => 'hca_mi', 'icon' => '<i class="fas fa-tint-slash"></i>', 'level' => 12]);

    if ($User->checkPermissions('hca_mi', 2))
        $SwiftMenu->addItem(['title' => '+ New Project', 'link' => $URL->link('hca_5840_new_project'), 'id' => 'hca_mi_new_project', 'parent_id' => 'hca_mi', 'level' => 1]);

    if ($User->checkAccess('hca_mi', 4))
    {
        $SwiftMenu->addItem(['title' => 'Projects', 'link' => $URL->link('hca_5840_projects', ['active', 0]), 'id' => 'hca_mi_projects', 'parent_id' => 'hca_mi', 'level' => 2]);
        
        $SwiftMenu->addItem(['title' => 'In-Progress', 'link' => $URL->link('hca_5840_projects', ['active', 0]), 'id' => 'hca_mi_projects_active', 'parent_id' => 'hca_mi_projects']);
        $SwiftMenu->addItem(['title' => 'On Hold', 'link' => $URL->link('hca_5840_projects', ['on_hold', 0]), 'id' => 'hca_mi_projects_on_hold', 'parent_id' => 'hca_mi_projects']);
        $SwiftMenu->addItem(['title' => 'Completed', 'link' => $URL->link('hca_5840_projects', ['completed', 0]), 'id' => 'hca_mi_projects_completed', 'parent_id' => 'hca_mi_projects']);
    }

    if ($User->checkAccess('hca_mi', 5))
        $SwiftMenu->addItem(['title' => 'Report', 'link' => $URL->link('hca_5840_projects_report', 'view'), 'id' => 'hca_mi_projects_report', 'parent_id' => 'hca_mi', 'level' => 3]);

    if ($User->checkAccess('hca_mi', 6))
        $SwiftMenu->addItem(['title' => 'Property Report', 'link' => $URL->link('hca_mi_property_report'), 'id' => 'hca_mi_property_report', 'parent_id' => 'hca_mi', 'level' => 3]);
    
    if ($User->checkAccess('hca_mi', 7))
    {
        $SwiftMenu->addItem(['title' => 'Messages', 'link' => $URL->link('hca_5840_forms_submitted'), 'id' => 'hca_mi_forms', 'parent_id' => 'hca_mi', 'level' => 4]);
        $SwiftMenu->addItem(['title' => 'Sent', 'link' => $URL->link('hca_5840_forms_mailed'), 'id' => 'hca_mi_forms_mailed', 'parent_id' => 'hca_mi_forms']);
        $SwiftMenu->addItem(['title' => 'Submitted', 'link' => $URL->link('hca_5840_forms_submitted'), 'id' => 'hca_mi_forms_submitted', 'parent_id' => 'hca_mi_forms']);
        $SwiftMenu->addItem(['title' => 'Completed', 'link' => $URL->link('hca_5840_forms_confirmed'), 'id' => 'hca_mi_forms_confirmed', 'parent_id' => 'hca_mi_forms']);
    }

    /*
    if ($id > 0)
    {
        $SwiftMenu->addItem(['title' => 'Current Project', 'link' => '#', 'id' => 'hca_mi_management', 'parent_id' => 'hca_mi', 'level' => 5]);

        $SwiftMenu->addItem(['title' => 'Edit Project', 'link' => $URL->link('hca_5840_manage_project', $id), 'id' => 'hca_mi_manage_project', 'parent_id' => 'hca_mi_management']);

        $SwiftMenu->addItem(['title' => 'Files', 'link' => $URL->link('hca_5840_manage_files', $id), 'id' => 'hca_mi_manage_files', 'parent_id' => 'hca_mi_management']);

        $SwiftMenu->addItem(['title' => 'Invoice', 'link' => $URL->link('hca_5840_manage_invoice', $id), 'id' => 'hca_mi_manage_invoice', 'parent_id' => 'hca_mi_management']);

        if ($User->is_admin())
            $SwiftMenu->addItem(['title' => 'Project Tracking', 'link' => $URL->link('hca_mi_project_tracking', $id), 'id' => 'hca_mi_project_tracking', 'parent_id' => 'hca_mi_management']);
    }
*/

    if ($User->checkAccess('hca_mi', 20) || $User->checkAccess('hca_mi', 21) || $User->checkAccess('hca_mi', 22) || $User->checkAccess('hca_mi', 23) || $User->checkAccess('hca_mi', 24))
    {
        $SwiftMenu->addItem(['title' => 'Setup', 'link' => '#', 'id' => 'hca_mi_admin', 'parent_id' => 'hca_mi', 'level' => 20]);

        if ($User->checkAccess('hca_mi', 20))
            $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('hca_5840_admin_settings'), 'id' => 'hca_mi_admin_settings', 'parent_id' => 'hca_mi_admin', 'level' => 0]);

        if ($User->checkAccess('hca_mi', 21))
            $SwiftMenu->addItem(['title' => 'Access', 'link' => $URL->link('hca_5840_admin_access'), 'id' => 'hca_mi_admin_access', 'parent_id' => 'hca_mi_admin', 'level' => 1]);

        if ($User->checkAccess('hca_mi', 22))
            $SwiftMenu->addItem(['title' => 'Permissions', 'link' => $URL->link('hca_5840_admin_permissions'), 'id' => 'hca_mi_admin_permissions', 'parent_id' => 'hca_mi_admin', 'level' => 2]);

        if ($User->checkAccess('hca_mi', 23))
            $SwiftMenu->addItem(['title' => 'Notifications', 'link' => $URL->link('hca_5840_admin_notifications'), 'id' => 'hca_mi_admin_notifications', 'parent_id' => 'hca_mi_admin', 'level' => 3]);

        if ($User->checkAccess('hca_mi', 24))
            $SwiftMenu->addItem(['title' => 'Vendors', 'link' => $URL->link('hca_5840_admin_vendors'), 'id' => 'hca_mi_admin_vendors', 'parent_id' => 'hca_mi_admin', 'level' => 4]);

        if ($User->checkAccess('hca_mi', 21))
            $SwiftMenu->addItem(['title' => 'Missing Projects', 'link' => $URL->link('hca_5840_projects', ['missing', 0]), 'id' => 'hca_mi_projects_missing', 'parent_id' => 'hca_mi_admin']);
    }
}

function hca_mi_HcaVendorsDepartmentsTableHead()
{
    global $URL;
    echo '<th>Moisture Inspections <a href="'.$URL->link('sm_vendors_edit_project', 'hca_5840').'"><i class="fas fa-edit"></i></a></th>';
}

function hca_mi_HcaVendorsDepartmentsTableBody()
{
    global $cur_info;

    if ($cur_info['hca_5840'] == 1)
        echo '<td><span class="badge bg-success ms-1">ON</span></td>';
    else
        echo '<td><span class="badge bg-secondary ms-1">OFF</span></td>';
}

function hca_mi_swift_notify_ajax()
{
    global $DBLayer, $User, $SwiftNotify;

    $count = 0;
    $query = array(
        'SELECT'	=> 'mois_inspection_date, asb_test_date, rem_start_date, rem_end_date, cons_start_date, cons_end_date',
        'FROM'		=> 'hca_5840_projects',
        'WHERE'		=> 'job_status=1'
        //'WHERE'		=> 'job_status=1 AND mois_performed_by=\''.$DBLayer->escape($User->get('realname')).'\''
    );
    $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
    while ($row = $DBLayer->fetch_assoc($result))
    {
        if (sm_is_today($row['mois_inspection_date']) || sm_is_today($row['asb_test_date']) || sm_is_today($row['rem_start_date']) || sm_is_today($row['rem_end_date']) || sm_is_today($row['cons_start_date']) || sm_is_today($row['cons_end_date']))
            ++$count;
    }
    
    if ($count > 0)
    {
        $SwiftNotify->addInfo('menu_item_hca_mi', $count, 'top-0 start-100 translate-middle badge rounded-pill bg-red');
        $SwiftNotify->addInfo('menu_item_hca_5840_projects_active', $count, 'position-absolute top-50 start-50 translate-middle badge rounded-pill bg-orange');
    }
}

class HcaMoistureInspections
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
            1 => 'List of Projects',
            2 => 'View Report',
            3 => 'Messages of Property Manager',
        
            11 => 'Create new projects',
            12 => 'Edit projects',
            13 => 'Edit Invoice',
            14 => 'Upload Files',
            15 => 'Create Appendix-B',
            16 => 'Send project info to Email',
            17 => 'Change project status',
            18 => 'Remove projects',
        
            //20 => 'Settings'
        ];

        if (check_app_access($access_info, 'hca_mi'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h5 class="h5 card-title mb-0">Moisture Inspections</h5>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_mi'))
                    echo '<span class="badge badge-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge badge-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }

    public function ProfileAdminNotifications()
    {
        global $notifications_info;

        $notifications_options = [
            1 => 'Budget over $5000',
            2 => 'Project was created',
            3 => 'Project was completed',
            4 => 'Project was removed',
        ];
?>
        <div class="card-body pt-1 pb-1">
            <h5 class="h5 card-title mb-0">Moisture Inspections</h5>
<?php
        foreach($notifications_options as $key => $title)
        {
            if (check_notification($notifications_info, $key, 'hca_mi'))
                echo '<span class="badge badge-success ms-1">'.$title.'</span>';
            else
                echo '<span class="badge badge-secondary ms-1">'.$title.'</span>';
        }
        echo '</div>';
    }

    public function HcaVendorsEditUpdateValidation()
    {
        global $form_data;
        $form_data['hca_5840'] = isset($_POST['hca_5840']) ? intval($_POST['hca_5840']) : '0';
    }

    public function HcaVendorsEditPreSumbit()
    {
        global $edit_info;
?>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="hca_5840" value="1" id="field_hca_5840" <?php if ($edit_info['hca_5840'] == '1') echo 'checked' ?>>
                <label class="form-check-label" for="field_hca_5840">Moisture Inspections</label>
            </div>
<?php
    }

    public function ReportBody()
    {
        global $DBLayer, $URL;

        $projects_ids = [];
        $num_active = $num_on_hold = $num_today_event = 0;
        $query = array(
            'SELECT'	=> 'pj.*',
            'FROM'		=> 'hca_5840_projects AS pj',
            'WHERE'     => 'pj.job_status=1 OR pj.job_status=2',
            //'ORDER BY'	=> 'pt.pro_name, LENGTH(pj.unit_number)',
        );
        $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
        while ($row = $DBLayer->fetch_assoc($result))
        {
            if ($row['job_status'] == 1)
                ++$num_active;
            else if ($row['job_status'] == 2)
                ++$num_on_hold;

            if (sm_is_today($row['asb_test_date']) || sm_is_today($row['rem_start_date']) || sm_is_today($row['rem_end_date']) || sm_is_today($row['cons_start_date']) || sm_is_today($row['cons_end_date']) || sm_is_today($row['afcc_date']) || sm_is_today($row['delivery_equip_date']) || sm_is_today($row['pickup_equip_date']))
                ++$num_today_event;

            $projects_ids[] = $row['id'];
        }

        $query = array(
            'SELECT'	=> 'e.id, e.project_id, e.time, e.message',
            'FROM'		=> 'sm_calendar_events AS e',
            'WHERE'		=> 'e.project_id IN('.implode(',', $projects_ids).') AND project_name=\'hca_5840\'',
            'ORDER BY'	=> 'e.time DESC'
        );
        $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
        while ($row = $DBLayer->fetch_assoc($result)) {
            if (sm_is_today($row['time']))
                ++$num_today_event;
        }

?>
     <div class="col-xxl-4 col-xl-6 mb-3">
        <div class="card">
            <div class="card-body my-0 pt-0">
                <h4 class="card-title"><a href="<?=$URL->link('hca_5840_projects', ['active', 0])?>">Moisture Inspection</a></h4>
                <hr class="my-2">
                <div id="chart_hca_mi"></div>
            </div>
        </div>
    </div>

<script>

var options = {
        series: [{
        data: [<?=$num_active?>, <?=$num_on_hold?>, <?=$num_today_event?>]
    }],
    chart: {
        type: 'bar',
        height: 265,
        width: 300,
        toolbar: {
            show: false
        }
    },
    plotOptions: {
        bar: {
        barHeight: '100%',
        distributed: true,
        horizontal: true,
        dataLabels: {
            position: 'bottom'
        },
        }
    },
    colors: ['#33b2df', '#546E7A', '#d4526e'],
    dataLabels: {
        enabled: true,
        textAnchor: 'start',
        style: {
            colors: ['#fff'],
            fontSize: '18px',
        },
        formatter: function (val, opt) {
            return val
        },
        offsetX: 0,
        dropShadow: {
        enabled: true
        }
    },
    stroke: {
        width: 5,
        colors: ['#fff']
    },
    xaxis: {
        categories: ['Active Projects', 'On Hold', 'Today Follow Up Dates'],
    },
    yaxis: {
        labels: {
        show: false
        }
    },
/*
    title: {
        text: 'Custom DataLabels',
        align: 'center',
        floating: true
    },
    subtitle: {
        text: 'Category Names as DataLabels inside bars',
        align: 'center',
    },
*/
    tooltip: {
        theme: 'dark',
        x: {
        show: false
        },
        y: {
        title: {
            formatter: function () {
            return ''
            }
        }
        }
    }
};

var chart = new ApexCharts(document.querySelector("#chart_hca_mi"), options);
chart.render();
    </script>
<?php
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAdminAccess', ['HcaMoistureInspections', 'ProfileAdminAccess']);
//Hook::addAction('ProfileAdminPermissions', ['HcaMoistureInspections', 'ProfileAdminPermissions']);
Hook::addAction('ProfileAdminNotifications', ['HcaMoistureInspections', 'ProfileAdminNotifications']);

Hook::addAction('HcaVendorsEditUpdateValidation', ['HcaMoistureInspections', 'HcaVendorsEditUpdateValidation']);
Hook::addAction('HcaVendorsEditPreSumbit', ['HcaMoistureInspections', 'HcaVendorsEditPreSumbit']);

Hook::addAction('ReportBody', ['HcaMoistureInspections', 'ReportBody']);
