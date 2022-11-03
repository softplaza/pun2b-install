<?php

if (!defined('DB_CONFIG')) die();

function swift_events_co_modify_url_scheme()
{
    global $URL;

    $app_id = 'swift_events';
    $urls = [];

    $urls['swift_events_calendar'] = 'apps/'.$app_id.'/calendar.php';
    $urls['swift_events_report'] = 'apps/'.$app_id.'/report.php';

    $urls['swift_events_ajax_get_events'] = 'apps/'.$app_id.'/ajax/get_events.php';
    $urls['swift_events_ajax_edit_event'] = 'apps/'.$app_id.'/ajax/edit_event.php';

    //Management
    $urls['swift_events_settings'] = 'apps/'.$app_id.'/settings.php';

    $URL->add_urls($urls);
}

function swift_events_IncludeCommon()
{
    global $User, $SwiftMenu, $URL, $Config;
    
    if ($User->checkAccess('swift_events', 1))
    {
        $SwiftMenu->addItem(['title' => 'Calendar', 'link' => '#', 'id' => 'swift_events', 'icon' => '<i class="far fa-calendar-alt"></i>', 'level' => 20]);

        $SwiftMenu->addItem(['title' => date('Y'), 'link' => $URL->genLink('swift_events_calendar', ['date' => date('Y-m-d')]), 'id' => 'swift_events_calendar', 'parent_id' => 'swift_events', 'level' => 1]);
    
        $SwiftMenu->addItem(['title' => date('F'), 'link' => $URL->genLink('swift_events_calendar', ['type' => 'month', 'date' => date('Y-m-d')]), 'id' => 'swift_events_calendar', 'parent_id' => 'swift_events', 'level' => 1]);

        $SwiftMenu->addItem(['title' => 'Report', 'link' => $URL->link('swift_events_report'), 'id' => 'swift_events_report', 'parent_id' => 'swift_events', 'level' => 25]);

        if ($User->checkAccess('swift_events', 20))
            $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('swift_events_settings'), 'id' => 'swift_events_settings', 'parent_id' => 'swift_events', 'level' => 25]);
    }
}

class SwiftEventsHooks
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
            1 => 'View Calendar',
            2 => 'Add events',
            3 => 'Edit events',
        ];

        if (check_app_access($access_info, 'swift_events'))
        {
?>
        <div class="card-body pt-1 pb-1">
            <h6 class="h6 card-title mb-0">Calendar</h6>
<?php
            foreach($access_options as $key => $title)
            {
                if (check_access($access_info, $key, 'swift_events'))
                    echo '<span class="badge bg-success ms-1">'.$title.'</span>';
                else
                    echo '<span class="badge bg-secondary ms-1">'.$title.'</span>';
            }
            echo '</div>';
        }
    }
}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
Hook::addAction('ProfileAboutNewAccess', ['SwiftEventsHooks', 'ProfileAboutNewAccess']);

