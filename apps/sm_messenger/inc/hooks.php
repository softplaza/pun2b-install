<?php

if (!defined('DB_CONFIG')) die();

function sm_messenger_co_modify_url_scheme()
{
    global $URL;
    $urls = [];
    $urls['sm_messenger'] = 'apps/sm_messenger/';
    $urls['sm_messenger_new'] = 'apps/sm_messenger/new.php?params=$1';
    $urls['sm_messenger_inbox'] = 'apps/sm_messenger/inbox.php';
    $urls['sm_messenger_outbox'] = 'apps/sm_messenger/outbox.php';
    $urls['sm_messenger_reply'] = 'apps/sm_messenger/reply.php?tid=$1';
    $URL->add_urls($urls);
}

function sm_messenger_IncludeCommon()
{
    global $User, $URL, $Config, $SwiftMenu;

    if (!$User->is_guest())
    {
        $SwiftMenu->addItem(['title' => 'Messenger', 'link' => $URL->link('sm_messenger_inbox'), 'id' => 'sm_messenger', 'icon' => '<i class="far fa-envelope"></i>']);

        $SwiftMenu->addItem(['title' => 'Inbox', 'link' => $URL->link('sm_messenger_inbox'), 'id' => 'sm_messenger_inbox', 'parent_id' => 'sm_messenger']);
        $SwiftMenu->addItem(['title' => 'Outbox', 'link' => $URL->link('sm_messenger_outbox'), 'id' => 'sm_messenger_outbox', 'parent_id' => 'sm_messenger']);
        $SwiftMenu->addItem(['title' => 'Compose', 'link' => $URL->link('sm_messenger_new', ''), 'id' => 'sm_messenger_new', 'parent_id' => 'sm_messenger']);
    }
}


