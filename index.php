<?php

/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

define('SITE_ROOT', './');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	redirect($URL->link('login'), 'You are not loged in.');
else
	redirect($URL->link('user', $User->get('id')), 'You are already loged in.');

$Core->set_page_id('index', 'index');
require SITE_ROOT.'header.php';

Hook::doAction('IndexStart');
?>

<div class="card">
	<div class="card-header">
		<h6 class="card-title mb-0">Summary of projects</h6>
	</div>
	<div class="card-body">
		<div class="alert alert-warning py-2" role="alert">This is a summary report of projects over the past six months.</div>
		<?php Hook::doAction('IndexBody'); ?>
	</div>
</div>

<?php
Hook::doAction('IndexEnd');

require SITE_ROOT.'footer.php';
