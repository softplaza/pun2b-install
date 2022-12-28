<?php

if (!defined('DB_CONFIG')) die();

function hca_fs_es_essentials()
{
    require SITE_ROOT.'apps/hca_fs/inc/functions.php';
}

function hca_fs_co_modify_url_scheme()
{
    global $URL;

    $urls = [];

    $urls['hca_fs_new_request'] = 'apps/hca_fs/new_request.php?id=$1';
    $urls['hca_fs_requests'] = 'apps/hca_fs/requests.php?section=$1&id=$2';
    $urls['hca_fs_report'] = 'apps/hca_fs/report.php';
    $urls['hca_fs_weekly_schedule'] = 'apps/hca_fs/weekly_schedule.php?gid=$1&week_of=$2';
    $urls['hca_fs_vacations'] = 'apps/hca_fs/vacations.php';

    $urls['hca_fs_worker_schedule'] = 'apps/hca_fs/worker_schedule.php?id=$1';
    $urls['hca_fs_edit_work_order'] = 'apps/hca_fs/edit_work_order.php?id=$1';
    $urls['hca_fs_weekly_technician_schedule'] = 'apps/hca_fs/weekly_technician_schedule.php?gid=$1&uid=$2&week_of=$3';

    $urls['hca_fs_property_schedule'] = 'apps/hca_fs/property_schedule.php?id=$1&week_of=$2';
    $urls['hca_fs_permanently_assignments'] = 'apps/hca_fs/permanently_assignments.php?gid=$1';
    $urls['hca_fs_property_assignments'] = 'apps/hca_fs/property_assignments.php?week_of=$1';
    $urls['hca_fs_settings'] = 'apps/hca_fs/settings.php';
    $urls['hca_fs_new_user'] = 'apps/hca_fs/new_user.php';
    
    $urls['hca_fs_work_order_report'] = 'apps/hca_fs/work_order_report.php';

    $urls['hca_fs_emergency_schedule'] = 'apps/hca_fs/emergency_schedule.php?date=$1';
    $urls['hca_fs_emergency_property'] = 'apps/hca_fs/emergency_property.php';
    $urls['hca_fs_emergency_zones'] = 'apps/hca_fs/emergency_zones.php?sort_by=2';

    $urls['hca_fs_ajax_get_weekly_shedule_request'] = 'apps/hca_fs/ajax/get_weekly_shedule_request.php';
    // ajax requests
    $urls['hca_fs_ajax_assign_property_request'] = 'apps/hca_fs/ajax/assign_property_request.php';
    $urls['hca_fs_ajax_get_units'] = 'apps/hca_fs/ajax/get_units.php';
    $urls['hca_fs_ajax_get_workers'] = 'apps/hca_fs/ajax/get_workers.php';
    $urls['hca_fs_ajax_get_time_slots'] = 'apps/hca_fs/ajax/get_time_slots.php';
    $urls['hca_fs_ajax_get_properties'] = 'apps/hca_fs/ajax/get_properties.php';
    $urls['hca_fs_ajax_get_emergency_users'] = 'apps/hca_fs/ajax/get_emergency_users.php';
    $urls['hca_fs_ajax_get_work_order_info'] = 'apps/hca_fs/ajax/get_work_order_info.php';
    $urls['hca_fs_ajax_get_request_info'] = 'apps/hca_fs/ajax/get_request_info.php';
    $urls['hca_fs_ajax_get_available_technician'] = 'apps/hca_fs/ajax/get_available_technician.php';

    $URL->add_urls($urls);
}

function hca_fs_hd_head()
{
    global $Loader;

    if (PAGE_SECTION_ID == 'hca_fs' || PAGE_ID == 'hca_fs_worker_schedule')
        $Loader->add_css(BASE_URL.'/apps/hca_fs/css/style.css?'.time());

    if (PAGE_SECTION_ID == 'hca_fs' || PAGE_ID == 'print')
        $Loader->add_css(BASE_URL.'/apps/hca_fs/css/print.css?'.time());  
}

function hca_fs_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;

    if ($User->checkAccess('hca_fs') || $User->get('sm_pm_property_id') > 0)
    {
        $hca_fs_group = ($User->checkAccess('hca_fs', 5)) ? $Config->get('o_hca_fs_painters') : $Config->get('o_hca_fs_maintenance');
        $week_of = isset($_GET['week_of']) ? strtotime($_GET['week_of']) : time();

        // Display main menu item
        $SwiftMenu->addItem(['title' => 'Facility', 'link' => '#', 'id' => 'hca_fs', 'icon' => '<i class="fas fa-landmark"></i>', 'level' => 11]);

        //if ($User->get('sm_pm_property_id') > 0 || $User->checkAccess('hca_fs', 2))
        //   $SwiftMenu->addItem(['title' => '+ Make Request', 'link' => $URL->link('hca_fs_new_request', 0), 'id' => 'hca_fs_new_request', 'parent_id' => 'hca_fs', 'level' => 0]);

        /*
        // Property Managers & Facility Managers
        if ($User->get('sm_pm_property_id') > 0 || $User->checkAccess('hca_fs', 3))
        {
            $SwiftMenu->addItem(['title' => 'Property requests', 'link' => $URL->link('hca_fs_requests', ['new', 0]), 'id' => 'hca_fs_requests', 'parent_id' => 'hca_fs', 'level' => 1]);

            $SwiftMenu->addItem(['title' => 'Pending', 'link' => $URL->link('hca_fs_requests', ['new', 0]), 'id' => 'hca_fs_requests_new', 'parent_id' => 'hca_fs_requests', 'level' => 1]);
            $SwiftMenu->addItem(['title' => 'In-progress', 'link' => $URL->link('hca_fs_requests', ['active', 0]), 'id' => 'hca_fs_requests_active', 'parent_id' => 'hca_fs_requests', 'level' => 2]);
            $SwiftMenu->addItem(['title' => 'Completed', 'link' => $URL->link('hca_fs_requests', ['completed', 0]), 'id' => 'hca_fs_requests_active', 'parent_id' => 'hca_fs_requests', 'level' => 3]);
            //$SwiftMenu->addItem(['title' => 'Completed', 'link' => $URL->link('hca_fs_report'), 'id' => 'hca_fs_report', 'parent_id' => 'hca_fs_requests', 'level' => 3]);
        }
        // Technician
        else if ($User->get('group_id') == $Config->get('o_hca_fs_painters') || $User->get('group_id') == $Config->get('o_hca_fs_maintenance'))
        {
            $SwiftMenu->addItem(['title' => 'Work orders', 'link' => $URL->link('hca_fs_worker_schedule', $User->get('id')), 'id' => 'hca_fs_worker_schedule', 'parent_id' => 'hca_fs', 'level' => 2]);

            // Current Schedule $urls['hca_fs_weekly_technician_schedule']
            $SwiftMenu->addItem(['title' => 'Weekly schedule', 'link' => $URL->link('hca_fs_weekly_technician_schedule', [$hca_fs_group, $User->get('id'), date('Y-m-d')]), 'id' => 'hca_fs_weekly_technician_schedule', 'parent_id' => 'hca_fs', 'level' => 3]);
        }
*/
        // For other who has access
        if ($User->checkAccess('hca_fs', 1))
        {
            $SwiftMenu->addItem(['title' => 'Maintenance schedule', 'link' => $URL->link('hca_fs_weekly_schedule', array($Config->get('o_hca_fs_maintenance'), date('Y-m-d', $week_of))), 'id' => 'hca_fs_weekly_schedule_'.$Config->get('o_hca_fs_maintenance'), 'parent_id' => 'hca_fs', 'level' => 4]);

            $SwiftMenu->addItem(['title' => 'Painter schedule', 'link' => $URL->link('hca_fs_weekly_schedule', array($Config->get('o_hca_fs_painters'), date('Y-m-d', $week_of))), 'id' => 'hca_fs_weekly_schedule_'.$Config->get('o_hca_fs_painters'), 'parent_id' => 'hca_fs', 'level' => 4]);
        }
        // For maint manager only
        else if ($User->checkAccess('hca_fs', 4))
            $SwiftMenu->addItem(['title' => 'Maintenance schedule', 'link' => $URL->link('hca_fs_weekly_schedule', array($Config->get('o_hca_fs_maintenance'), date('Y-m-d', $week_of))), 'id' => 'hca_fs_weekly_schedule_'.$Config->get('o_hca_fs_maintenance'), 'parent_id' => 'hca_fs', 'level' => 4]);
        // For paint manager only
        else if ($User->checkAccess('hca_fs', 5))
            $SwiftMenu->addItem(['title' => 'Painter schedule', 'link' => $URL->link('hca_fs_weekly_schedule', array($Config->get('o_hca_fs_painters'), date('Y-m-d', $week_of))), 'id' => 'hca_fs_weekly_schedule_'.$Config->get('o_hca_fs_painters'), 'parent_id' => 'hca_fs', 'level' => 4]);


        if ($User->checkAccess('hca_fs', 6))
        {
            $SwiftMenu->addItem(['title' => 'Monthly emergency schedule', 'link' => $URL->link('hca_fs_emergency_schedule', date('Y-m-d', time())), 'id' => 'hca_fs_emergency', 'parent_id' => 'hca_fs', 'level' => 5]);
            $SwiftMenu->addItem(['title' => 'Covering weekends', 'link' => $URL->link('hca_fs_emergency_schedule', date('Y-m-d', time())), 'id' => 'hca_fs_emergency_schedule', 'parent_id' => 'hca_fs_emergency']);
            $SwiftMenu->addItem(['title' => 'Covering weekdays', 'link' => $URL->link('hca_fs_emergency_property'), 'id' => 'hca_fs_emergency_property', 'parent_id' => 'hca_fs_emergency']);
            $SwiftMenu->addItem(['title' => 'Zone Assignments', 'link' => $URL->link('hca_fs_emergency_zones'), 'id' => 'hca_fs_emergency_zones', 'parent_id' => 'hca_fs_emergency']);
        }

        // REGULAR TECHNICIAN
        if ($User->checkAccess('hca_fs', 8)) // regular assignments
        {
            if ($User->checkAccess('hca_fs', 4))
                $SwiftMenu->addItem(['title' => 'Regular maintenance', 'link' => $URL->link('hca_fs_permanently_assignments', $Config->get('o_hca_fs_maintenance')), 'id' => 'hca_fs_permanently_assignments_'.$Config->get('o_hca_fs_maintenance'), 'parent_id' => 'hca_fs', 'level' => 6]);

            if ($User->checkAccess('hca_fs', 4))
                $SwiftMenu->addItem(['title' => 'Regular painters', 'link' => $URL->link('hca_fs_permanently_assignments', $Config->get('o_hca_fs_painters')), 'id' => 'hca_fs_permanently_assignments_'.$Config->get('o_hca_fs_painters'), 'parent_id' => 'hca_fs', 'level' => 6]);
        }

        if ($User->checkAccess('hca_fs', 7))
            $SwiftMenu->addItem(['title' => 'Report', 'link' => $URL->link('hca_fs_work_order_report'), 'id' => 'hca_fs_work_order_report', 'parent_id' => 'hca_fs', 'level' => 5]);

        if ($User->checkAccess('hca_fs', 11))
            $SwiftMenu->addItem(['title' => 'Properties schedule', 'link' => $URL->link('hca_fs_property_assignments', date('Y-m-d', time())), 'id' => 'hca_fs_property_assignments', 'parent_id' => 'hca_fs', 'level' => 6]);

        if ($User->checkAccess('hca_fs', 9))
            $SwiftMenu->addItem(['title' => 'Vacations', 'link' => $URL->link('hca_fs_vacations'), 'id' => 'hca_fs_vacations', 'parent_id' => 'hca_fs', 'level' => 7]);

        if ($User->checkAccess('hca_fs', 20))
            $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('hca_fs_settings'), 'id' => 'hca_fs_settings', 'parent_id' => 'hca_fs', 'level' => 20]);
    }
}

function hca_fs_ft_js_include()
{
    global $Loader;

    if (PAGE_SECTION_ID == 'hca_fs' || PAGE_ID == 'hca_fs_worker_schedule')
    {
        $Loader->add_js(BASE_URL.'/apps/hca_fs/js/javascript.js?'.time(), array('type' => 'url', 'async' => false, 'group' => -100 , 'weight' => 75));
    }
}

/*
function hca_fs_swift_notify_ajax()
{
    global $DBLayer, $User, $Config, $SwiftNotify;

    $notify_counter = [
        'hca_fs_requests_new'       => 0,
        'hca_fs_requests_active'    => 0,
        'hca_fs_requests_on_hold'   => 0
    ];

    $query = array(
        'SELECT'	=> 'property_id, work_status, new_start_date',
        'FROM'		=> 'hca_fs_requests',
        'WHERE'		=> 'work_status < 2',
    );

    if ($User->get('hca_fs_group') > 0)
        $query['WHERE'] .= ' AND group_id='.$User->get('hca_fs_group');
    else if ($User->get('sm_pm_property_id') > 0)
        $query['WHERE'] .= ' AND property_id='.$User->get('sm_pm_property_id');
    else if ($User->get('group_id') == $Config->get('o_hca_fs_painters') || $User->get('group_id') == $Config->get('o_hca_fs_maintenance'))
        $query['WHERE'] .= ' AND employee_id='.$User->get('id');

    $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
    while ($row = $DBLayer->fetch_assoc($result))
    {
        if ($row['work_status'] == 0)
            ++$notify_counter['hca_fs_requests_new'];
        else if ($row['work_status'] == 1)
            ++$notify_counter['hca_fs_requests_active'];
        else if ($row['work_status'] == -1)
            ++$notify_counter['hca_fs_requests_on_hold'];
    }
    
    if ($User->get('group_id') == $Config->get('o_hca_fs_painters') || $User->get('group_id') == $Config->get('o_hca_fs_maintenance'))
    {
        if ($notify_counter['hca_fs_requests_active'] > 0)
        {
            $SwiftNotify->addInfo('menu_item_hca_fs', $notify_counter['hca_fs_requests_active'], 'top-0 start-100 translate-middle badge rounded-pill bg-red');

            $SwiftNotify->addInfo('menu_item_hca_fs_worker_schedule', $notify_counter['hca_fs_requests_active'], 'position-absolute top-50 start-50 translate-middle badge rounded-pill bg-blue');
        }
    }
    else
    {
        if ($notify_counter['hca_fs_requests_new'] > 0)
            $SwiftNotify->addInfo('menu_item_hca_fs', $notify_counter['hca_fs_requests_new'], 'top-0 start-100 translate-middle badge rounded-pill bg-red');

        if ($notify_counter['hca_fs_requests_new'] > 0)
            $SwiftNotify->addInfo('menu_item_hca_fs_requests_new', $notify_counter['hca_fs_requests_new'], 'position-absolute top-50 start-50 translate-middle badge rounded-pill bg-orange');

        if ($notify_counter['hca_fs_requests_active'] > 0)
            $SwiftNotify->addInfo('menu_item_hca_fs_requests_active', $notify_counter['hca_fs_requests_active'], 'position-absolute top-50 start-50 translate-middle badge rounded-pill bg-blue');
    }
}
*/

class HcaFacilityHooks
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
            2 => 'Make request',
            3 => 'Property Requests',
            1 => 'Manage Technician Schedule', // View bouth
            4 => 'Manage Maintenance Schedule only', // View maint only
            5 => 'Manage Painter Schedule only', // View paint only
            6 => 'Monthly Emergency Schedule',
            7 => 'Work Order Report',
            8 => 'Permanently Assignments',
            9 => 'Vacations',
            10 => 'Technician Work Orders',
            14 => 'Technician Schedule',
            11 => 'Properties Schedule',
            12 => 'Approve requests',
            13 => 'Add technician',

            //20 => 'Settings'
        ];

        if (check_app_access($access_info, 'hca_fs'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h5 class="h5 card-title mb-0">Facility</h5>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_fs'))
                    echo '<span class="badge badge-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge badge-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAdminAccess', ['HcaFacilityHooks', 'ProfileAdminAccess']);
//Hook::addAction('ProfileAboutNewPermissions', ['HcaFacilityHooks', 'ProfileAboutNewPermissions']);
//Hook::addAction('ProfileAboutNewNotifications', ['HcaFacilityHooks', 'ProfileAboutNewNotifications']);

