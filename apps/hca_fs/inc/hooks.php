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

    public function ReportBody()
    {
        global $DBLayer, $URL, $Config;

        $this_week_tasks = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 0 => 0];
        $next_week_tasks = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 0 => 0];
        $time_this_week = strtotime('Monday this week');
        $time_next_week = strtotime('Monday this week') + 604800;
        $query = array(
            'SELECT'	=> 'r.*',
            'FROM'		=> 'hca_fs_requests AS r',
            'WHERE'     => '(r.template_type<4 OR r.template_type=7) AND r.start_date >= '.$time_this_week, // exclude DayOff and Sick and Vacations
            'ORDER BY'  => 'r.start_date'
        );
        $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
        while ($row = $DBLayer->fetch_assoc($result))
        {
            $week_of  = strtotime('Monday this week', $row['start_date']);
            $day_of_week = date('w', $row['start_date']);

            if ($week_of == $time_this_week)
            {
                if (isset($this_week_tasks[$day_of_week]))
                    ++$this_week_tasks[$day_of_week];
                else
                    $this_week_tasks[$day_of_week] = 1;
            }
            else if ($week_of == $time_next_week)
            {
                if (isset($next_week_tasks[$day_of_week]))
                    ++$next_week_tasks[$day_of_week];
                else
                    $next_week_tasks[$day_of_week] = 1;
            }
        }
?>
     <div class="col-xxl-4 col-xl-6 mb-3">
        <div class="card">
            <div class="card-body my-0 pt-0">
                <h4 class="card-title"><a href="<?=$URL->link('hca_fs_weekly_schedule', array($Config->get('o_hca_fs_maintenance'), date('Y-m-d')))?>">Facility Schedule</a></h4>
                <hr class="my-2">
                <div id="chart_hca_fs_pie"></div>
            </div>
        </div>
    </div>

<script>
    var options = {
        series: [{
            name: 'This Week',
            data: [
            <?=$this_week_tasks[1]?>, <?=$this_week_tasks[2]?>, <?=$this_week_tasks[3]?>, <?=$this_week_tasks[4]?>, <?=$this_week_tasks[5]?>, <?=$this_week_tasks[6]?>]
        }, {
            name: 'Next Week',
            data: [
            <?=$next_week_tasks[1]?>, <?=$next_week_tasks[2]?>, <?=$next_week_tasks[3]?>, <?=$next_week_tasks[4]?>, <?=$next_week_tasks[5]?>, <?=$next_week_tasks[6]?>]
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
          text: 'Num tasks of this week & next week'
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
          categories: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        }
        };

    var chart = new ApexCharts(document.querySelector("#chart_hca_fs_pie"), options);
    chart.render();
    </script>
<?php
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAdminAccess', ['HcaFacilityHooks', 'ProfileAdminAccess']);
//Hook::addAction('ProfileAboutNewPermissions', ['HcaFacilityHooks', 'ProfileAboutNewPermissions']);
//Hook::addAction('ProfileAboutNewNotifications', ['HcaFacilityHooks', 'ProfileAboutNewNotifications']);

Hook::addAction('ReportBody', ['HcaFacilityHooks', 'ReportBody']);
