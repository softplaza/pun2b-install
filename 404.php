<?php

/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

define('SITE_ROOT', './');
require SITE_ROOT.'include/common.php';

// Set 404 for header
header("HTTP/1.1 404 Not Found");

$Core->set_page_id('404', 'index');
require SITE_ROOT.'header.php';

Hook::doAction('404Start');
?>

<div class="card">
	<div class="card-header">
		<h6 class="card-title mb-0">404 - Page not found</h6>
	</div>
	<div class="card-body">
		<div class="alert alert-danger py-2" role="alert">Sorry! The page could not be loaded.</div>
	</div>
</div>

<?php
Hook::doAction('404End');

require SITE_ROOT.'footer.php';
