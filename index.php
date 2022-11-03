<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

define('SITE_ROOT', './');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
{
	redirect($URL->link('login'), 'You are not loged in.');
}
else
{
	redirect($URL->link('user', $User->get('id')), 'You are already loged in.');
}
?>





