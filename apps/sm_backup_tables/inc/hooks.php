<?php

if (!defined('DB_CONFIG')) die();

function sm_backup_tables_co_modify_url_scheme()
{
    global $URL;
    $URL->add('sm_backup_tables', 'apps/sm_backup_tables/index.php');
}

function sm_backup_tables_IncludeCommon()
{
    global $User, $URL, $Config, $SwiftMenu;

    if ($User->is_admin())
        $SwiftMenu->addItem(['title' => 'BackUp Tables', 'link' => $URL->link('sm_backup_tables'), 'id' => 'sm_backup_tables', 'parent_id' => 'admin']);
}
