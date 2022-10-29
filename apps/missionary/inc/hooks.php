<?php

if (!defined('DB_CONFIG')) die();

function missionary_co_modify_url_scheme()
{
    global $URL;

    $app_id = 'missionary';

    $urls = [];
    $urls['missionary'] = 'apps/'.$app_id.'/';
    $urls['missionary_start'] = 'apps/'.$app_id.'/index.php?hash=$1';
    $urls['missionary_questions_list'] = 'apps/'.$app_id.'/questions_list.php?level=$1';
    $urls['missionary_new_question'] = 'apps/'.$app_id.'/new_question.php?hash=$1';
    $urls['missionary_unapproved_questions'] = 'apps/'.$app_id.'/unapproved_questions.php';

    $urls['missionary_ajax'] = 'apps/'.$app_id.'/ajax.php';

    $URL->add_urls($urls);
}

function missionary_IncludeCommon()
{
    global $User, $URL, $Config, $SwiftMenu;

    $SwiftMenu->addItem(['title' => 'Missionary', 'link' => '#', 'id' => 'missionary', 'icon' => '<i class="fas fa-coins"></i>']);

    $SwiftMenu->addItem(['title' => 'Start game', 'link' => $URL->link('missionary_start', 0), 'id' => 'missionary_start', 'parent_id' => 'missionary']);

    $SwiftMenu->addItem(['title' => 'Offer a question', 'link' => $URL->link('missionary_new_question', 0), 'id' => 'missionary_new_question', 'parent_id' => 'missionary']);

    if ($User->is_admin())
    {
        $SwiftMenu->addItem(['title' => 'Question list', 'link' => $URL->link('missionary_questions_list', 0), 'id' => 'missionary_questions_list', 'parent_id' => 'missionary']);

        $SwiftMenu->addItem(['title' => 'Unaproved questions', 'link' => $URL->link('missionary_unapproved_questions'), 'id' => 'missionary_unapproved_questions', 'parent_id' => 'missionary']);

        $SwiftMenu->addItem(['title' => 'Settings', 'link' => $URL->link('sm_property_management_settings'), 'id' => 'sm_property_management_settings', 'parent_id' => 'missionary']);
    }
}
