<?php

if ($gid == $Config->get('o_hca_fs_painters'))
	$pdf_link = 'painter_schedule';
else
	$pdf_link = 'maintenance_schedule';

$GenPDF = new GenPDF($work_orders_info, $assignments_info);
$GenPDF->users_list = $users_info;
$GenPDF->property_info = $property_info;
//$GenPDF->work_orders = $work_orders_info;
$GenPDF->group_id = $gid;
$GenPDF->first_day_of_week = $first_day_of_week;
//$GenPDF->GenWholeShedule(); // Whole PDF
$GenPDF->GenSeparateShedule(); // Whole PDF with separated pages
//$GenPDF->GenSeparateSheduleColumns(); // Separated Shedule in One File by 4 columns
$GenPDF->GenSeparatedUserShedule(); // Separated PDF for each one users

?>

<style>
#iframe_pdf_view{width:100%; height:400px; zoom: 2;}
</style>

<div class="main-subhead">
	<h2 class="hn"><span>Schedule for the Week of <?php echo date('F, jS', $first_day_of_week) ?></span></h2>
</div>

<div class="main-content main-frm">
	<div class="ct-group">
		<div class="main-subhead">
			<h2 class="hn"><span>Current Schedule</span></h2>
		</div>
<?php
		echo '<iframe name="weekly_schedule" id="iframe_pdf_view" src="files/'.$pdf_link.'.pdf?'.time().'"></iframe>';
?>
	</div>
</div>

<?php
require SITE_ROOT.'footer.php';