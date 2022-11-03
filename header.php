<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

// Make sure no one attempts to run this script "directly"
if (!defined('DB_CONFIG'))
	exit;

$Templator->setTPL();

// START SUBST - <!--head_elements-->
$head_elements = [];
// Add CSS and META here
$Hooks->get_hook('hd_head');

// Create Menu object
$Hooks->get_hook('hd_menu_elements');

$Templator->gen_head_elements($head_elements);

// Setup array of general elements
$main_elements = [];
// Page id and classes
$main_elements['<!--page_id-->'] = 'id="brd-'.PAGE_ID.'" class="brd-page basic-page"';
// Site Title
$main_elements['<!--site_title-->'] = '<p id="brd-title"><a href="'.$URL->link('index').'">'.html_encode($Config->get('o_board_title')).'</a></p>';
// Site Description
$main_elements['<!--site_desc-->'] = ($Config->get('o_board_desc') != '') ? '<p id="brd-desc">'.html_encode($Config->get('o_board_desc')).'</p>' : '';
// Flash messages
$main_elements['<!--flash_messages-->'] = '<div id="brd-messages">'.$FlashMessenger->show(true).'</div>'."\n";

// Maintenance Warning
if ($Config->get('o_maintenance') == '1')
	$main_elements['<!--announcement-->'] = '<div class="alert alert-warning" role="alert">'.("\n\t".'<h5 class="mb-1">'.$Config->get('o_announcement_heading').'</h5><hr class="my-0">')."\n\t".'<p>'.sprintf($lang_common['Maintenance warning'], $lang_common['Maintenance mode']).'</p>'."\n".'</div>'."\n";
// Announcement
else if ($Config->get('o_announcement') == '1')
	$main_elements['<!--announcement-->'] = '<div class="alert alert-warning" role="alert">'.("\n\t".'<h5 class="mb-1">'.$Config->get('o_announcement_heading').'</h5><hr class="my-0">')."\n\t".'<p>'.$Config->get('o_announcement_message').'</p>'."\n".'</div>'."\n";

// Generate main menu
$main_elements['<!--main_top_menu-->'] = $SwiftMenu->getSlideMenu();

// Paginate top
//$main_elements['<!--paginate_top-->'] = $PagesNavigator->show_top();
// Paginate bottom
//$main_elements['<!--paginate_bottom-->'] = $PagesNavigator->show_end();

// Get system messages - Errors / Warnings / Notifications
//$main_elements['<!--system_messages-->'] = $Core->get_system_messages();

$Hooks->get_hook('hd_main_elements');

$Templator->gen_main_elements($main_elements);
unset($main_elements);

// START SUBST - <!--page_content-->
$Templator->start_page_content();

if (!defined('SPM_HEADER'))
	define('SPM_HEADER', 1);
