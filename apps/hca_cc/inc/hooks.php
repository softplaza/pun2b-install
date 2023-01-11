<?php

if (!defined('DB_CONFIG')) die();

function hca_cc_co_modify_url_scheme()
{
    global $URL;

    $app_id = 'hca_cc';
    $urls = [];

    $urls['hca_cc_item'] = 'apps/'.$app_id.'/item.php?id=$1';
    $urls['hca_cc_items'] = 'apps/'.$app_id.'/items.php?id=$1';

    $urls['hca_cc_chart'] = 'apps/'.$app_id.'/chart.php?id=$1';
    $urls['hca_cc_projects'] = 'apps/'.$app_id.'/projects.php?id=$1';
    $urls['hca_cc_report'] = 'apps/'.$app_id.'/report.php';

    $urls['hca_cc_settings'] = 'apps/'.$app_id.'/settings.php';

    $urls['hca_cc_ajax_get_item'] = 'apps/'.$app_id.'/ajax/get_item.php';
    $urls['hca_cc_ajax_get_action'] = 'apps/'.$app_id.'/ajax/get_action.php';

    $URL->add_urls($urls);
}

function hca_cc_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;

    if ($User->checkAccess('hca_cc'))
        $SwiftMenu->addItem(['title' => 'Compliance Calendar', 'link' => '#', 'id' => 'hca_cc', 'icon' => '<i class="far fa-calendar-check"></i>', 'level' => 20]);

    $SwiftMenu->addItem(['title' => 'Items Tracking', 'link' => $URL->link('hca_cc_projects', 0), 'id' => 'hca_cc_projects', 'parent_id' => 'hca_cc', 'level' => 1]);

    $SwiftMenu->addItem(['title' => 'Report', 'link' => $URL->link('hca_cc_report'), 'id' => 'hca_cc_report', 'parent_id' => 'hca_cc', 'level' => 2]);

    $SwiftMenu->addItem(['title' => 'Chart', 'link' => $URL->link('hca_cc_chart', 0), 'id' => 'hca_cc_chart', 'parent_id' => 'hca_cc', 'level' => 3]);

    $SwiftMenu->addItem(['title' => '+ Add an item', 'link' => $URL->link('hca_cc_item', 0), 'id' => 'hca_cc_item', 'parent_id' => 'hca_cc', 'level' => 3]);

    if ($User->checkAccess('hca_cc', 50))
    {
        //$SwiftMenu->addItem(['title' => 'Management', 'link' => '#', 'id' => 'hca_cc_management', 'parent_id' => 'hca_cc', 'level' => 20]);

        //$SwiftMenu->addItem(['title' => '+ Add item', 'link' => $URL->link('hca_cc_item', 0), 'id' => 'hca_cc_item', 'parent_id' => 'hca_cc_management', 'level' => 1]);

        //$SwiftMenu->addItem(['title' => 'List of items', 'link' => $URL->link('hca_cc_items', 0), 'id' => 'hca_cc_items', 'parent_id' => 'hca_cc_management', 'level' => 2]);

        $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('hca_cc_settings'), 'id' => 'hca_cc_settings', 'parent_id' => 'hca_cc', 'level' => 30]);
    }
}

function hca_cc_FooterEnd()
{
	global $DBLayer, $SwiftMailer, $User;
/*
    //
    if (!$SwiftMailer->sent && $User->is_guest())
    {
        $DateTime = new DateTime();
        $DateTime->modify('+1 month');
        $next_month = $DateTime->format('Y-m-d');
    
        // Getting Upcoming and expired items 1 month ahead or older
        $query = [
            'SELECT'	=> 'i.*',
            'FROM'		=> 'hca_cc_items AS i',
            'WHERE'		=> 'i.date_due < \''.$next_month.'\'',
            'ORDER BY'	=> 'i.last_notified'
        ];
        $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
        $main_info = [];
        while ($row = $DBLayer->fetch_assoc($result))
        {
            $main_info[] = $row;
        }

        if (!empty($main_info))
        {
            $time_now = time();
            $yesterday = $time_now - 86400;
            $week = $time_now - 604800;
            foreach($main_info as $cur_info)
            {
                if (
                    // if expired project -> every day
                    (compare_dates(date('Y-m-d'), $cur_info['date_due'], 1) && $cur_info['last_notified'] < $yesterday)
                    // if upcoming -> every week
                    || (compare_dates($cur_info['date_due'], date('Y-m-d'), 1) && $cur_info['last_notified'] < $week)
                    )
                {
                    $emails = $realnames = [];
                    $query = [
                        'SELECT'	=> 'o.item_id, u.realname, u.email',
                        'FROM'		=> 'hca_cc_owners AS o',
                        'JOINS'		=> [
                            [
                                'INNER JOIN'	=> 'users AS u',
                                'ON'			=> 'u.id=o.user_id'
                            ],
                        ],
                        'WHERE'		=> 'o.item_id='.$cur_info['id'],
                    ];
                    $result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
                    while ($row = $DBLayer->fetch_assoc($result))
                    {
                        $realnames[] = $row['realname'];
                        $emails[] = $row['email'];
                    }
            
                    // Send email to owners
                    if (!empty($emails))
                    {
                        // get last project and notify them
                        $mail_message = [];
        
                        // 0 is '=', 1 is '>'
                        if (compare_dates(date('Y-m-d'), $cur_info['date_due'], 1))
                            $mail_message[] = 'You have an expired project.'."\n";
                        else
                            $mail_message[] = 'You have an upcoming project.'."\n";
        
                        $mail_message[] = 'Due date: '.format_date($cur_info['date_due'], 'F n, Y');
                        $mail_message[] = 'Item name: '.html_encode($cur_info['item_name']);
                        $mail_message[] = 'Description: '.html_encode($cur_info['item_desc']);
                        $mail_message[] = 'Action owners: '.implode(', ', $realnames);
        
                        $SwiftMailer = new SwiftMailer;
                        $SwiftMailer->send(implode(',', $emails), 'Compliance Calendar', implode("\n", $mail_message));
        
                        $DBLayer->update('hca_cc_items', ['last_notified' => $time_now], $cur_info['id']);
                        
                        break;
                    }
                }
            }
        }
    }
*/
}

class HcaCCHooks
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
            1 => 'Add item',
            2 => 'Items Tracking',
        
            11 => 'Complete item',
            12 => 'Edit item',
            13 => 'Delete item',
        
           // 20 => 'Settings'
        ];

        if (check_app_access($access_info, 'hca_cc'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h5 class="h5 card-title mb-0">Compliance Calendar</h5>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'hca_cc'))
                    echo '<span class="badge badge-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge badge-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAdminAccess', ['HcaCCHooks', 'ProfileAdminAccess']);

