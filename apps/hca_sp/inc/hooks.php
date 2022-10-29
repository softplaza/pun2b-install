<?php

if (!defined('DB_CONFIG')) die();

function hca_sp_es_essentials()
{
    require SITE_ROOT.'apps/hca_sp/inc/functions.php';
}

function hca_sp_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    $app_id = 'hca_sp';

    $urls['sm_special_projects'] = 'apps/'.$app_id.'/';
    $urls['sm_special_projects_settings'] = 'apps/'.$app_id.'/settings.php';
    $urls['hca_sp_new'] = 'apps/'.$app_id.'/new.php';
    $urls['sm_special_projects_active'] = 'apps/'.$app_id.'/active.php?pid=$1';
    $urls['sm_special_projects_completed'] = 'apps/'.$app_id.'/completed.php?pid=$1';
    $urls['sm_special_projects_report'] = 'apps/'.$app_id.'/report.php';
    $urls['sm_special_projects_chart'] = 'apps/'.$app_id.'/chart.php?id=$1&property_id=$2&user_id=$3&start=$4&end=$5&work_status=$6';

    $urls['sm_special_projects_wish_list'] = 'apps/'.$app_id.'/wish_list.php';
    $urls['sm_special_projects_invoices'] = 'apps/'.$app_id.'/invoices_list.php';
    $urls['sm_special_projects_invoices_print'] = 'apps/'.$app_id.'/invoices_list.php?action=print';
    $urls['sm_special_projects_events'] = 'apps/'.$app_id.'/events.php?id=$1';
    $urls['sm_special_projects_events_by_date'] = 'apps/'.$app_id.'/events.php?id=$1&week_of=$2';
    $urls['sm_special_projects_actions'] = 'apps/'.$app_id.'/messages.php?id=$1';
    $urls['sm_special_projects_admin_form'] = 'apps/'.$app_id.'/admin_form.php?id=$1';
    $urls['sm_special_projects_view'] = 'apps/'.$app_id.'/view_project.php?id=$1';
    
    $urls['sm_special_projects_manage'] = 'apps/'.$app_id.'/manage_project.php?id=$1';
    $urls['sm_special_projects_manage_invoice'] = 'apps/'.$app_id.'/manage_invoice.php?id=$1';
    $urls['sm_special_projects_manage_invoice_print'] = 'apps/'.$app_id.'/manage_invoice.php?id=$1&action=print';
    $urls['sm_special_projects_manage_files'] = 'apps/'.$app_id.'/manage_files.php?id=$1';
    $urls['sm_special_projects_manage_follow_up'] = 'apps/'.$app_id.'/manage_follow_up.php?id=$1';
    $urls['sm_special_projects_manage_recommendations'] = 'apps/'.$app_id.'/manage_recommendations.php?id=$1';
    
    $urls['hca_sp_ajax_get_units'] = 'apps/'.$app_id.'/ajax/get_units.php';
    $urls['hca_sp_ajax_get_events'] = 'apps/'.$app_id.'/ajax/get_events.php';

    $URL->add_urls($urls);
}

function hca_sp_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($User->checkAccess('hca_sp'))
        $SwiftMenu->addItem(['title' => 'Project & Construction', 'link' =>  $URL->link('sm_special_projects_active', 0), 'id' => 'hca_sp', 'icon' => '<i class="fas fa-comments-dollar"></i>', 'level' => 13]);

    if ($User->checkAccess('hca_sp', 11))
        $SwiftMenu->addItem(['title' => '+ New Project', 'link' => $URL->link('hca_sp_new'), 'id' => 'hca_sp_new', 'parent_id' => 'hca_sp']);

    if ($User->checkAccess('hca_sp', 1))
        $SwiftMenu->addItem(['title' => 'Active Projects', 'link' => $URL->link('sm_special_projects_active', 0), 'id' => 'sm_special_projects_active', 'parent_id' => 'hca_sp']);

    if ($User->checkAccess('hca_sp', 2))
        $SwiftMenu->addItem(['title' => 'Completed Projects', 'link' => $URL->link('sm_special_projects_completed', 0), 'id' => 'sm_special_projects_completed', 'parent_id' => 'hca_sp']);
        
    if ($User->checkAccess('hca_sp', 3))
        $SwiftMenu->addItem(['title' => 'Report', 'link' => $URL->link('sm_special_projects_chart', [0,0,0,'','',0]), 'id' => 'sm_special_projects_chart', 'parent_id' => 'hca_sp']);

    if ($id > 0 && $User->checkAccess('hca_sp', 12))
    {
        $SwiftMenu->addItem(['title' => 'Project Management', 'link' => '#', 'id' => 'hca_sp_management', 'parent_id' => 'hca_sp']);

        $SwiftMenu->addItem(['title' => 'Edit Project', 'link' => $URL->link('sm_special_projects_manage', $id), 'id' => 'sm_special_projects_manage', 'parent_id' => 'hca_sp_management']);
        $SwiftMenu->addItem(['title' => 'Uploaded Files', 'link' => $URL->link('sm_special_projects_manage_files', $id), 'id' => 'sm_special_projects_manage_files', 'parent_id' => 'hca_sp_management']);
        $SwiftMenu->addItem(['title' => 'Invoice', 'link' => $URL->link('sm_special_projects_manage_invoice', $id), 'id' => 'sm_special_projects_manage_invoice', 'parent_id' => 'hca_sp_management']);
        $SwiftMenu->addItem(['title' => 'Follow-Up Dates', 'link' => $URL->link('sm_special_projects_manage_follow_up', $id), 'id' => 'sm_special_projects_manage_follow_up', 'parent_id' => 'hca_sp_management']);
        $SwiftMenu->addItem(['title' => 'Recommendations', 'link' => $URL->link('sm_special_projects_manage_recommendations', $id), 'id' => 'sm_special_projects_manage_recommendations', 'parent_id' => 'hca_sp_management']);
    }

    if ($User->checkAccess('hca_sp', 20))
        $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('sm_special_projects_settings'), 'id' => 'sm_special_projects_settings', 'parent_id' => 'hca_sp']);
}

function hca_sp_FooterEnd()
{
    sm_special_projects_check_all_events();
}

function hca_sp_HcaVendorsDepartmentsTableHead()
{
    global $URL;
    echo '<th>Special Projects <a href="'.$URL->link('sm_vendors_edit_project', 'hca_sp').'"><i class="fas fa-edit"></i></a></th>';
}

function hca_sp_HcaVendorsDepartmentsTableBody()
{
    global $cur_info;

    if ($cur_info['hca_sp'] == 1)
        echo '<td><span class="badge bg-success ms-1">ON</span></td>';
    else
        echo '<td><span class="badge bg-secondary ms-1">OFF</span></td>';
}

class HcaSPHooks
{
    private static $singleton;

    public static function getInstance(){
        return self::$singleton = new self;
    }

    public static function singletonMethod(){
        return self::getInstance();
    }

    public function ProfileChangeDetailsSettingsValidation(){
        global $form;
        if (isset($_POST['form']['sm_special_projects_notify_time']))
            $form['sm_special_projects_notify_time'] = intval($_POST['form']['sm_special_projects_notify_time']);
    }

    public function ProfileChangeDetailsSettingsEmailFieldsetEnd()
    {
        global $User, $user, $id;

        if ($User->checkAccess('hca_sp') && ($User->is_admmod() || $id == $User->get('id')))
        {
        ?>
            <div class="card-header">
                <h6 class="card-title mb-0">Special Projects settings</h6>
            </div>
            <div class="card-body">	
                <div class="mb-3">
                    <label class="form-label" for="input_sm_special_projects_notify_time">Notification time: Set how many hours left before showing and sending a notification</label>
                    <input type="text" name="form[sm_special_projects_notify_time]" value="<?php echo $user['sm_special_projects_notify_time'] ?>" class="form-control" id="input_sm_special_projects_notify_time">
                </div>
            </div>
        <?php
        }
    }

    public function ProfileAboutNewAccess()
    {
        global $access_info;

        $access_options = [
            // Pages
            1 => 'Active Projects',
            2 => 'Completed Projects',
            3 => 'Report',

            // Actions
            11 => 'Create Projects',
            12 => 'Edit Projects',
            13 => 'Remove Projects',
            14 => 'Show in the list of project managers',
            
            // Admin Settings
            20 => 'Settings'
        ];

        if (check_app_access($access_info, 'hca_sp'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h6 class="h6 card-title mb-0">Project Construction Department</h6>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_sp'))
                    echo '<span class="badge bg-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge bg-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }

    public function HcaVendorsEditUpdateValidation()
    {
        global $form_data;
        $form_data['hca_sp'] = isset($_POST['hca_sp']) ? intval($_POST['hca_sp']) : '0';
    }

    public function HcaVendorsEditPreSumbit()
    {
        global $edit_info;
?>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="hca_sp" value="1" id="field_hca_sp" <?php if ($edit_info['hca_sp'] == '1') echo 'checked' ?>>
                <label class="form-check-label" for="field_hca_sp">Special Projects</label>
            </div>
<?php
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAboutNewAccess', ['HcaSPHooks', 'ProfileAboutNewAccess']);

Hook::addAction('ProfileChangeDetailsSettingsValidation', ['HcaSPHooks', 'ProfileChangeDetailsSettingsValidation']);
Hook::addAction('ProfileChangeDetailsSettingsEmailFieldsetEnd', ['HcaSPHooks', 'ProfileChangeDetailsSettingsEmailFieldsetEnd']);

Hook::addAction('HcaVendorsEditUpdateValidation', ['HcaSPHooks', 'HcaVendorsEditUpdateValidation']);
Hook::addAction('HcaVendorsEditPreSumbit', ['HcaSPHooks', 'HcaVendorsEditPreSumbit']);
