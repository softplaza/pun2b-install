<?php
/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

define('SPM_QUIET_VISIT', 1);
define('SITE_ROOT', './');
require SITE_ROOT.'include/common.php';

$section = isset($_GET['section']) ? $_GET['section'] : null;

Hook::doAction('MiscNewSection');

message($lang_common['Bad request']);
