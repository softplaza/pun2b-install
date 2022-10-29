<?php

if (!defined('DB_CONFIG')) die();

function swift_messenger_es_essentials()
{
    global $Config;

    $app_id = 'swift_messenger';
    
    require SITE_ROOT.'apps/'.$app_id.'/class/SwiftMessenger.php';

    if (file_exists(SITE_ROOT.'apps/'.$app_id.'/vendor/'.$Config->get('o_swift_messenger_app').'/autoload.php'))
    {
        require SITE_ROOT.'apps/'.$app_id.'/vendor/'.$Config->get('o_swift_messenger_app').'/autoload.php';
    }
}

function swift_messenger_co_modify_url_scheme()
{
    global $URL;

    $app_id = 'swift_messenger';
    $urls = [];

    $urls['swift_messenger_settings'] = 'apps/'.$app_id.'/settings.php';

    $URL->add_urls($urls);
}

function swift_messenger_IncludeCommon()
{
    global $User, $URL, $Config, $SwiftMenu;

    if ($User->is_admin())
    {
        //$SwiftMenu->addItem(['title' => 'Messenger', 'link' => '#', 'id' => 'swift_messenger', 'icon' => '<i class="far fa-envelope"></i>']);

        $SwiftMenu->addItem(['title' => 'Messenger', 'link' => $URL->link('swift_messenger_settings'), 'id' => 'swift_messenger_settings', 'parent_id' => 'settings']);
    }
}

class SwiftMessengerHooks
{
    private static $singleton;

    public static function getInstance(){
        return self::$singleton = new self;
    }

    public static function singletonMethod(){
        return self::getInstance();
    }


}

//Hook::addAction('HookName', ['AppClass', 'MethodOfAppClass']);
//
