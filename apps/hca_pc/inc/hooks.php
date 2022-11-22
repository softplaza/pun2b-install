<?php

if (!defined('DB_CONFIG')) die();

function hca_pc_es_essentials()
{
    require SITE_ROOT.'apps/hca_pc/inc/functions.php';
}

function hca_pc_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    $app_id = 'hca_pc';

    $urls['sm_pest_control_new'] = 'apps/'.$app_id.'/new.php';
    $urls['sm_pest_control_manage_project'] = 'apps/'.$app_id.'/manage_project.php?id=$1';
    $urls['sm_pest_control_active'] = 'apps/'.$app_id.'/active.php?id=$1';
    $urls['sm_pest_control_records'] = 'apps/'.$app_id.'/records.php';
    $urls['sm_pest_control_events'] = 'apps/'.$app_id.'/events.php?id=$1';
    $urls['sm_pest_control_form'] = 'apps/'.$app_id.'/form.php?id=$1&hash=$2';
    $urls['sm_pest_control_recycle'] = 'apps/'.$app_id.'/recycle.php';
    $urls['sm_pest_control_settings'] = 'apps/'.$app_id.'/settings.php';

    $urls['sm_pc_forms'] = 'apps/'.$app_id.'/forms.php?action=$1';

    $urls['hca_pc_ajax_get_units'] = 'apps/'.$app_id.'/ajax/get_units.php';
    $urls['hca_pc_ajax_get_events'] = 'apps/'.$app_id.'/ajax/get_events.php';

    $URL->add_urls($urls);
}

function hca_pc_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;

    if ($User->checkAccess('hca_pc'))
    {
        $SwiftMenu->addItem(['title' => 'Pest Control', 'link' =>  $URL->link('sm_pest_control_active', 0), 'id' => 'hca_pc', 'icon' => '<i class="fas fa-bug"></i>', 'level' => 14]);

        if ($User->checkAccess('hca_pc', 11))
            $SwiftMenu->addItem(['title' => '+ New Project', 'link' => $URL->link('sm_pest_control_new'), 'id' => 'sm_pest_control_new', 'parent_id' => 'hca_pc']);
    
        if ($User->checkAccess('hca_pc', 1))
            $SwiftMenu->addItem(['title' => 'Active Projects', 'link' => $URL->link('sm_pest_control_active', 0), 'id' => 'sm_pest_control_active', 'parent_id' => 'hca_pc']);
    
        if ($User->checkAccess('hca_pc', 2))
            $SwiftMenu->addItem(['title' => 'Report', 'link' => $URL->link('sm_pest_control_records', 0), 'id' => 'sm_pest_control_records', 'parent_id' => 'hca_pc']);

        if ($User->checkAccess('hca_pc', 3))
            $SwiftMenu->addItem(['title' => 'Recycle', 'link' => $URL->link('sm_pest_control_recycle'), 'id' => 'sm_pest_control_recycle', 'parent_id' => 'hca_pc']);

        if ($User->checkAccess('hca_pc', 3))
        {
            $SwiftMenu->addItem(['title' => 'Messages', 'link' => $URL->link('sm_pc_forms', 'submitted'), 'id' => 'sm_pc_forms', 'parent_id' => 'hca_pc']);
            $SwiftMenu->addItem(['title' => 'Sent to Manager', 'link' => $URL->link('sm_pc_forms', 'mailed'), 'id' => 'sm_pc_forms_mailed', 'parent_id' => 'sm_pc_forms']);
            $SwiftMenu->addItem(['title' => 'New from Manager', 'link' => $URL->link('sm_pc_forms', 'submitted'), 'id' => 'sm_pc_forms_submitted', 'parent_id' => 'sm_pc_forms']);
            $SwiftMenu->addItem(['title' => 'Confirmed', 'link' => $URL->link('sm_pc_forms', 'confirmed'), 'id' => 'sm_pc_forms_confirmed', 'parent_id' => 'sm_pc_forms']);
        }

        if ($User->checkAccess('hca_pc', 20))
            $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('sm_pest_control_settings'), 'id' => 'sm_pest_control_settings', 'parent_id' => 'hca_pc']);
    }
}

function hca_pc_FooterIncludeJS()
{
    global $Config;
    if ($Config->get('o_sm_pest_control_manager_period_notify') > 0)
	    sm_pest_control_check_unconfirmed_mngr_forms();
}

function hca_pc_HcaVendorsDepartmentsTableHead()
{
    global $URL;
    echo '<th>Pest Control <a href="'.$URL->link('sm_vendors_edit_project', 'sm_pest_control').'"><i class="fas fa-edit"></i></a></th>';
}

function hca_pc_HcaVendorsDepartmentsTableBody()
{
    global $cur_info;

    if ($cur_info['sm_pest_control'] == 1)
        echo '<td><span class="badge bg-success ms-1">ON</span></td>';
    else
        echo '<td><span class="badge bg-secondary ms-1">OFF</span></td>';
}

class HcaPCHooks
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
        if (isset($_POST['form']['sm_pest_control_notify_time']))
            $form['sm_pest_control_notify_time'] = intval($_POST['form']['sm_pest_control_notify_time']);
       
        if (isset($_POST['form']['sm_pc_notify_by_email']))
            $form['sm_pc_notify_by_email'] = intval($_POST['form']['sm_pc_notify_by_email']); 
    }

    public function ProfileChangeDetailsSettingsEmailFieldsetEnd()
    {
        global $User, $user, $id;

        if ($user['sm_pc_access'] > 0 && ($User->is_admmod() || $id == $User->get('id')))
        {
        ?>
            <div class="card-header">
                <h6 class="card-title mb-0">Pest Control settings</h6>
            </div>
            <div class="card-body">	
                <div class="mb-3">
                    <label class="form-label" for="sm_pest_control_notify_time">Notification period</label>
                    <input type="text" name="form[sm_pest_control_notify_time]" value="<?php echo $user['sm_pest_control_notify_time'] ?>" class="form-control" id="sm_pest_control_notify_time">
                </div>
                <div class="mb-3">
                    <input type="hidden" name="form[sm_pc_notify_by_email]" value="0">
                    <label class="form-label" for="sm_pc_notify_by_email">Email notifications</label>
                    <input type="checkbox" id="sm_pc_notify_by_email" name="form[sm_pc_notify_by_email]" value="1" <?php echo ($user['sm_pc_notify_by_email'] == 1) ? 'checked="checked"' : '' ?>>
                </div>  
            </div>
        <?php
        }
    }

    public function ProfileAdminAccess()
    {
        global $access_info;

        $access_options = [
            // Pages
            1 => 'Active Projects',
            2 => 'Report',
            3 => 'Recycle',
            4 => 'Messages',

            // Actions
            11 => 'Create Projects',
            12 => 'Edit Projects',
            13 => 'Remove Projects',
            14 => 'Send emails to managers',
            
            // Admin Settings
            //20 => 'Settings'
        ];

        if (check_app_access($access_info, 'hca_pc'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h5 class="h5 card-title mb-0">Pest Control Projects</h5>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_pc'))
                    echo '<span class="badge badge-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge badge-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }

    public function HcaVendorsEditUpdateValidation()
    {
        global $form_data;
        $form_data['sm_pest_control'] = isset($_POST['sm_pest_control']) ? intval($_POST['sm_pest_control']) : '0';
    }

    public function HcaVendorsEditPreSumbit()
    {
        global $edit_info;
?>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="sm_pest_control" value="1" id="field_sm_pest_control" <?php if ($edit_info['sm_pest_control'] == '1') echo 'checked' ?>>
                <label class="form-check-label" for="field_sm_pest_control">Pest Control</label>
            </div>
<?php
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAdminAccess', ['HcaPCHooks', 'ProfileAdminAccess']);

Hook::addAction('ProfileChangeDetailsSettingsValidation', ['HcaPCHooks', 'ProfileChangeDetailsSettingsValidation']);
Hook::addAction('ProfileChangeDetailsSettingsEmailFieldsetEnd', ['HcaPCHooks', 'ProfileChangeDetailsSettingsEmailFieldsetEnd']);

Hook::addAction('HcaVendorsEditUpdateValidation', ['HcaPCHooks', 'HcaVendorsEditUpdateValidation']);
Hook::addAction('HcaVendorsEditPreSumbit', ['HcaPCHooks', 'HcaVendorsEditPreSumbit']);
