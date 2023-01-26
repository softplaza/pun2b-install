<?php

/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Swift Project Manager
 */

define('SITE_ROOT', './');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	redirect($URL->link('login'), 'You are not loged in.');
else if (!$User->is_admmod())
	redirect($URL->link('user', $User->get('id')), 'You are already loged in.');

$search_by_period = isset($_GET['period']) ? intval($_GET['period']) : 12;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;

//require SITE_ROOT.'include/classes/SwiftReport.php';
$SwiftReport = new SwiftReport;

$Core->set_page_id('report', 'index');
require SITE_ROOT.'header.php';

Hook::doAction('ReportStart');
?>

<nav class="navbar alert-info mb-1">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col-md-auto pe-0 mb-1">
					<select name="period" class="form-select form-select-sm">
<?php
foreach($SwiftReport->getPeriods() as $key => $value)
{
	if ($search_by_period == $key)
		echo '<option value="'.$key.'" selected="selected">'.$value.'</option>';
	else
		echo '<option value="'.$key.'">'.$value.'</option>';
}
?>
					</select>
				</div>
                <div class="col-md-auto pe-0 mb-1">
                    <select name="property_id" class="form-select-sm">
						<option value="">All Properties</option>
<?php
$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
	'ORDER BY'	=> 'p.pro_name'
);
if ($User->get('property_access') != '' && $User->get('property_access') != 0)
{
	$property_ids = explode(',', $User->get('property_access'));
	$query['WHERE'] .= ' AND p.id IN ('.implode(',', $property_ids).')';
}
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $fetch_assoc;
}
foreach ($property_info as $val)
{
	if ($search_by_property_id == $val['id'])
		echo '<option value="'.$val['id'].'" selected>'.$val['pro_name'].'</option>';
	else
		echo '<option value="'.$val['id'].'">'.$val['pro_name'].'</option>';
}
?>
					</select>
				</div>
				<div class="col-md-auto">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
					<a href="<?php echo BASE_URL ?>/report.php" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</div>
	</form>
</nav>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<div class="card">
	<div class="card-header">
		<h6 class="card-title mb-0">Summary of projects</h6>
	</div>
	<div class="card-body">
        <div class="row">
            <?php Hook::doAction('ReportBody'); ?>
        </div>
	</div>
</div>

<?php

Hook::doAction('ReportEnd');

require SITE_ROOT.'footer.php';

