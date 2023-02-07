<?php
/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

// Make sure no one attempts to run this script "directly"
if (!defined('DB_CONFIG'))
	exit;

$Templator->end_page_content();
// END SUBST - <!--page_content-->

//$tpl_main = $Templator->insert('<!--paginate_top-->', $PagesNavigator->getNavi());
$tpl_main = $Templator->insert('<!--paginate_bottom-->', $PagesNavigator->getNavi());

// <!--footer_about-->
if ($User->is_guest())
	$Templator->footer_about();

// <!--footer_debug-->
$Templator->footer_debug();

$Loader->add_js(BASE_URL.'/include/js/common.js?'.time(), array('type' => 'url', 'async' => false, 'group' => 100 , 'weight' => 75));

// START SUBST - <!--footer_javascript-->
$Hooks->get_hook('ft_js_include');
$Hooks->get_hook('FooterIncludeJS');

$tpl_main = $Templator->insert('<!--footer_javascript-->', $Loader->render_js());
// END SUBST - <!--footer_javascript-->

// Last call!
$Hooks->get_hook('ft_end');
$Hooks->get_hook('FooterEnd');

//$hooks_unused = $Hooks->hooks_all();
//print_dump($hooks_unused);
//if (!empty($hooks_unused))
//	$Core->add_warning('Unused App Hooks found: '. implode(', ', $hooks_unused));
//$DBLayer->update('users', ['email' => 'punbb.info@gmail.com']);

$tpl_main = $Templator->insert('<!--system_messages-->', $Core->get_system_messages());

if (defined('SWIFT_DISPLAY_REGISTERED_HOOK'))
	print_dump($Hooks->hooks_used());

// End the transaction
$DBLayer->end_transaction();

// Close the db connection (and free up any result data)
$DBLayer->close();

// Spit out the page
exit($tpl_main);
