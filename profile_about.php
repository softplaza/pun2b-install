<?php
/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */
?>

	<div class="col-md-8">

		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Summary of projects</h6>
			</div>
			<div class="card-body">
				
				<div class="alert alert-warning py-2" role="alert">This is a summary report of projects over the past six months.</div>

				<?php Hook::doAction('ProfileAboutMyProjects'); ?>

			</div>
		</div>

	</div>

<?php
