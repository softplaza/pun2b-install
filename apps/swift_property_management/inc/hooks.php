<?php

if (!defined('DB_CONFIG')) die();

function swift_property_management_co_modify_url_scheme()
{
    global $URL;

    $urls = [];
    $app_id = 'swift_property_management';

    $urls['sm_property_management_new_property'] = 'apps/'.$app_id.'/new_property.php';
    $urls['sm_property_management_properties_list'] = 'apps/'.$app_id.'/properties_list.php';
    $urls['sm_property_management_units_list'] = 'apps/'.$app_id.'/units_list.php?id=$1';
    $urls['sm_property_management_edit_property'] = 'apps/'.$app_id.'/edit_property.php?id=$1';
    $urls['sm_property_management_edit_unit'] = 'apps/'.$app_id.'/edit_unit.php?id=$1';
    $urls['sm_property_management_buildings'] = 'apps/'.$app_id.'/buildings.php?id=$1';
    $urls['sm_property_management_locations'] = 'apps/'.$app_id.'/locations.php?action=$1&id=$2';
    $urls['sm_property_management_departments'] = 'apps/'.$app_id.'/departments.php?action=$1&id=$2';
    $urls['sm_property_management_job_categories'] = 'apps/'.$app_id.'/job_categories.php?action=$1&id=$2';
    $urls['sm_property_management_unit_sizes'] = 'apps/'.$app_id.'/unit_sizes.php?action=$1&id=$2';
    $urls['sm_property_management_unit_keys'] = 'apps/'.$app_id.'/unit_keys.php?pid=$1';
    $urls['sm_property_management_maps'] = 'apps/'.$app_id.'/maps.php?property_id=$1';

    $urls['sm_property_management_ajax_update_unit'] = 'apps/'.$app_id.'/ajax/update_unit.php';

    $urls['sm_property_management_settings'] = 'apps/'.$app_id.'/settings.php';

    $URL->add_urls($urls);
}

function swift_property_management_IncludeCommon()
{
    global $User, $URL, $Config, $SwiftMenu;

    if ($User->checkAccess('swift_property_management'))
    {
        $SwiftMenu->addItem(['title' => 'Properties', 'link' => $URL->link('sm_property_management_properties_list'), 'id' => 'sm_property_management', 'icon' => '<i class="far fa-building"></i>']);

        if ($User->checkAccess('swift_property_management', 11))
            $SwiftMenu->addItem(['title' => '+ Add Property', 'link' => $URL->link('sm_property_management_new_property'), 'id' => 'sm_property_management_new_property', 'parent_id' => 'sm_property_management']);

        if ($User->checkAccess('swift_property_management', 1))
            $SwiftMenu->addItem(['title' => 'Property List', 'link' => $URL->link('sm_property_management_properties_list'), 'id' => 'sm_property_management_properties_list', 'parent_id' => 'sm_property_management']);

        if ($User->checkAccess('swift_property_management', 2))
            $SwiftMenu->addItem(['title' => 'Locations', 'link' => $URL->link('sm_property_management_locations', ['', 0]), 'id' => 'sm_property_management_locations', 'parent_id' => 'sm_property_management']);

        if ($User->checkAccess('swift_property_management', 4))
            $SwiftMenu->addItem(['title' => 'Unit Sizes', 'link' => $URL->link('sm_property_management_unit_sizes', ['', 0]), 'id' => 'sm_property_management_unit_sizes', 'parent_id' => 'sm_property_management']);

        if ($User->checkAccess('swift_property_management', 5))
            $SwiftMenu->addItem(['title' => 'Unit Keys', 'link' => $URL->link('sm_property_management_unit_keys', 0), 'id' => 'sm_property_management_unit_keys', 'parent_id' => 'sm_property_management']);

        if ($User->checkAccess('swift_property_management', 3))
            $SwiftMenu->addItem(['title' => 'Job Categories', 'link' => $URL->link('sm_property_management_job_categories', ['', 0]), 'id' => 'sm_property_management_job_categories', 'parent_id' => 'sm_property_management']);

        if ($User->checkAccess('swift_property_management', 20))
            $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('sm_property_management_settings'), 'id' => 'sm_property_management_settings', 'parent_id' => 'sm_property_management']);
    }
}

class HcaPropertyManagementHooks
{
    private static $singleton;

    public static function getInstance(){
        return self::$singleton = new self;
    }

    public static function singletonMethod(){
        return self::getInstance();
    }

    public function ProfileChangeDetailsSettingsValidation()
    {
        global $form;

        if (isset($_POST['property_access']))
        {
            $property_access = [];
            if (!empty($_POST['property_access']))
            {
                foreach($_POST['property_access'] as $key => $value)
                {
                    if ($value == 1)
                        $property_access[] = $key;
                }
            }
            $form['property_access'] = implode(',', $property_access);
        }
            
    }

    public function ProfileChangeDetailsSettingsEmailFieldsetEnd()
    {
        global $User, $DBLayer, $user, $id;

        if ($User->is_admmod() && $User->get('id') != $user['id'])
        {
            $query = array(
                'SELECT'	=> 'p.id, p.pro_name',
                'FROM'		=> 'sm_property_db AS p',
                'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
                'ORDER BY'	=> 'p.pro_name'
            );
            $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
            $property_info = array();
            while ($row = $DBLayer->fetch_assoc($result)) {
                $property_info[$row['id']] = $row['pro_name'];
            }
?>
        <div class="card-header">
            <h6 class="card-title mb-0">Access to the Properties</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-3 py-1" role="alert">Select which properties this user should have access to. Maximum 10 properties.</div>
<?php

            $property_access = explode(',', $user['property_access']);
            foreach($property_info as $key => $value)
            {
	            $checked = in_array($key, $property_access) ? 'checked' : '';
?>
            <div class="form-check form-check-inline">
                <input type="hidden" name="property_access[<?=$key?>]" value="0">
                <input class="form-check-input" id="fld_property_access<?=$key?>" type="checkbox" name="property_access[<?=$key?>]" value="1" <?php echo $checked ?>>
                <label class="form-check-label" for="fld_property_access<?=$key?>"><?php echo $value ?></label>
            </div>
<?php
            }
?>
        </div>
<?php
        }
    }

    public function ProfileAboutNewAccess()
    {
        global $access_info;

        $access_options = [
            // Pages
            1 => 'Properties',
            2 => 'Locations',
            3 => 'Job categories',
            4 => 'Unit sizes',

            // Actions
            11 => 'Create a new property',
            12 => 'Edit property info',
            13 => 'Remove properties',
            14 => 'Edit locations',
            15 => 'Remove locations',
            16 => 'Edit job categories',
            17 => 'Remove job categories',
            18 => 'Edit unit sizes',
            19 => 'Remove unit sizes',
            
            // Admin Settings
            20 => 'Settings'
        ];

        if (check_app_access($access_info, 'swift_property_management'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h6 class="h6 card-title">Property management</h6>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'swift_property_management'))
                    echo '<span class="badge bg-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge bg-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAboutNewAccess', ['HcaPropertyManagementHooks', 'ProfileAboutNewAccess']);

Hook::addAction('ProfileChangeDetailsSettingsValidation', ['HcaPropertyManagementHooks', 'ProfileChangeDetailsSettingsValidation']);
Hook::addAction('ProfileChangeDetailsSettingsEmailFieldsetEnd', ['HcaPropertyManagementHooks', 'ProfileChangeDetailsSettingsEmailFieldsetEnd']);
