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
    
    $urls['hca_5840_form'] = 'apps/'.$app_id.'/form.php?id=$1&hash=$2';
    $urls['hca_5840_forms_mailed'] = 'apps/'.$app_id.'/forms_mailed.php';
    $urls['hca_5840_forms_submitted'] = 'apps/'.$app_id.'/forms_submitted.php';
    $urls['hca_5840_forms_confirmed'] = 'apps/'.$app_id.'/forms_confirmed.php';
    
    $urls['hca_5840_vendors'] = 'apps/'.$app_id.'/vendors.php';
    $urls['hca_5840_settings'] = 'apps/'.$app_id.'/settings.php';
    
    $urls['hca_5840_ajax_update_invoice'] = 'apps/'.$app_id.'/ajax/update_invoice.php';
    $urls['hca_5840_ajax_get_units'] = 'apps/'.$app_id.'/ajax/get_units.php';
    $urls['hca_5840_ajax_get_events'] = 'apps/'.$app_id.'/ajax/get_events.php';
    $urls['hca_5840_ajax_send_project_info_by_email'] = 'apps/'.$app_id.'/ajax/send_project_info_by_email.php';
    $urls['hca_5840_ajax_get_unit_positions'] = 'apps/'.$app_id.'/ajax/get_unit_positions.php';

    $URL->add_urls($urls);
}

function hca_mi_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($User->checkAccess('hca_mi'))
        $SwiftMenu->addItem(['title' => 'Moisture Inspections', 'link' =>  $URL->link('hca_5840_projects', ['active', 0]), 'id' => 'hca_mi', 'icon' => '<i class="fas fa-tint-slash"></i>', 'level' => 12]);

    if ($User->checkAccess('hca_mi', 11))
        $SwiftMenu->addItem(['title' => '+ New Project', 'link' => $URL->link('hca_5840_new_project'), 'id' => 'hca_5840_new_project', 'parent_id' => 'hca_mi', 'level' => 1]);

    if ($User->checkAccess('hca_mi', 1))
    {
        $SwiftMenu->addItem(['title' => 'Projects', 'link' => $URL->link('hca_5840_projects', ['active', 0]), 'id' => 'hca_5840_projects', 'parent_id' => 'hca_mi', 'level' => 2]);
        $SwiftMenu->addItem(['title' => 'In progress', 'link' => $URL->link('hca_5840_projects', ['active', 0]), 'id' => 'hca_5840_projects_active', 'parent_id' => 'hca_5840_projects']);
        $SwiftMenu->addItem(['title' => 'On Hold', 'link' => $URL->link('hca_5840_projects', ['on_hold', 0]), 'id' => 'hca_5840_projects_on_hold', 'parent_id' => 'hca_5840_projects']);
        $SwiftMenu->addItem(['title' => 'Completed', 'link' => $URL->link('hca_5840_projects', ['completed', 0]), 'id' => 'hca_5840_projects_completed', 'parent_id' => 'hca_5840_projects']);
    }

    if ($User->checkAccess('hca_mi', 2))
    {
        $SwiftMenu->addItem(['title' => 'Report', 'link' => $URL->link('hca_5840_projects_report', 'view'), 'id' => 'hca_5840_projects_report', 'parent_id' => 'hca_mi', 'level' => 3]);

        $SwiftMenu->addItem(['title' => 'Property Report', 'link' => $URL->link('hca_mi_property_report'), 'id' => 'hca_mi_property_report', 'parent_id' => 'hca_mi', 'level' => 3]);
    }
        

    if ($User->checkAccess('hca_mi', 3))
    {
        $SwiftMenu->addItem(['title' => 'Messages', 'link' => $URL->link('hca_5840_forms_submitted'), 'id' => 'hca_5840_forms', 'parent_id' => 'hca_mi', 'level' => 4]);
        $SwiftMenu->addItem(['title' => 'Sent', 'link' => $URL->link('hca_5840_forms_mailed'), 'id' => 'hca_5840_forms_mailed', 'parent_id' => 'hca_5840_forms']);
        $SwiftMenu->addItem(['title' => 'Submitted', 'link' => $URL->link('hca_5840_forms_submitted'), 'id' => 'hca_5840_forms_submitted', 'parent_id' => 'hca_5840_forms']);
        $SwiftMenu->addItem(['title' => 'Completed', 'link' => $URL->link('hca_5840_forms_confirmed'), 'id' => 'hca_5840_forms_confirmed', 'parent_id' => 'hca_5840_forms']);
    }

    if ($id > 0)
    {
        $SwiftMenu->addItem(['title' => 'Project Management', 'link' => '#', 'id' => 'hca_5840_management', 'parent_id' => 'hca_mi', 'level' => 5]);
        if ($User->checkAccess('hca_mi', 12))
            $SwiftMenu->addItem(['title' => 'Edit Project', 'link' => $URL->link('hca_5840_manage_project', $id), 'id' => 'hca_5840_manage_project', 'parent_id' => 'hca_5840_management']);
        if ($User->checkAccess('hca_mi', 13))
            $SwiftMenu->addItem(['title' => 'Edit Invoice', 'link' => $URL->link('hca_5840_manage_invoice', $id), 'id' => 'hca_5840_manage_invoice', 'parent_id' => 'hca_5840_management']);
        if ($User->checkAccess('hca_mi', 14))
            $SwiftMenu->addItem(['title' => 'Uploaded Files', 'link' => $URL->link('hca_5840_manage_files', $id), 'id' => 'hca_5840_manage_files', 'parent_id' => 'hca_5840_management']);
        if ($User->checkAccess('hca_mi', 15))
            $SwiftMenu->addItem(['title' => 'Appendix-B', 'link' => $URL->link('hca_5840_manage_appendixb', $id), 'id' => 'hca_5840_manage_appendixb', 'parent_id' => 'hca_5840_management']);
    }

    if ($User->checkAccess('hca_mi', 20))
        $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('hca_5840_settings'), 'id' => 'hca_5840_settings', 'parent_id' => 'hca_mi', 'level' => 20]);
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

    public function ProfileAboutNewAccess()
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
        
            20 => 'Settings'
        ];

        if (check_app_access($access_info, 'hca_mi'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h6 class="h6 card-title mb-0">Moisture Inspections</h6>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_mi'))
                    echo '<span class="badge bg-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge bg-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }

    public function ProfileAboutNewNotifications()
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
            <h6 class="h6 card-title mb-0">Moisture Inspections</h6>
<?php
        foreach($notifications_options as $key => $title)
        {
            if (check_notification($notifications_info, $key, 'hca_mi'))
                echo '<span class="badge bg-success ms-1">'.$title.'</span>';
            else
                echo '<span class="badge bg-secondary ms-1">'.$title.'</span>';
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
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAboutNewAccess', ['HcaMoistureInspections', 'ProfileAboutNewAccess']);
Hook::addAction('ProfileAboutNewPermissions', ['HcaMoistureInspections', 'ProfileAboutNewPermissions']);
Hook::addAction('ProfileAboutNewNotifications', ['HcaMoistureInspections', 'ProfileAboutNewNotifications']);

Hook::addAction('HcaVendorsEditUpdateValidation', ['HcaMoistureInspections', 'HcaVendorsEditUpdateValidation']);
Hook::addAction('HcaVendorsEditPreSumbit', ['HcaMoistureInspections', 'HcaVendorsEditPreSumbit']);