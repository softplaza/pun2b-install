<?php

class HcaComlianceCalendar
{

    function search_by_month()
    {
        $search_by_month = isset($_GET['month']) ? date('Y-m', strtotime($_GET['month'])) : 0;
?>
		<div class="search-box">
			<form method="get" accept-charset="utf-8" action="">
				<input name="month" type="month" value="<?php echo ($search_by_month > 0) ? $search_by_month : date('Y-m') ?>" size="10"/>
				<input type="submit" value="Search" />
			</form>
		</div>
<?php
    }

    function display_months_table($main_info)
    {
?>
			<table>
				<thead>
					<tr class="sticky-under-menu">
						<th class="th1">Frequency</th>
						<th>Department</th>
						<th>Action Owner</th>
						<th>Item</th>
						<th>Property</th>
						<th>Description</th>
						<th>Required By</th>
						<th>Date Last Completed</th>
						<th>Due Date</th>
						<th>Date Completed</th>
						<th>Notes</th>
					</tr>
				</thead>
				<tbody>
<?php
		foreach($main_info as $cur_info)
		{
			$td = [];
			$td[] = '<td><p>'.html_encode($cur_info['frequency']).'</p><p>'.date('F, Y', strtotime($cur_info['date_project'])).'</p></td>';
            $td[] = '<td><p>'.html_encode($cur_info['department']).'</p></td>';
			$td[] = '<td><p>'.html_encode($cur_info['action_owner']).'</p></td>';
            $td[] = '<td><p>'.html_encode($cur_info['item']).'</p></td>';
			$td[] = '<td><p>'.html_encode($cur_info['property_name']).'</p></td>';
            $td[] = '<td><p>'.html_encode($cur_info['description']).'</p></td>';
			$td[] = '<td><p>'.html_encode($cur_info['required_by']).'</p></td>';
            $td[] = '<td><p>'.$this->strtodate($cur_info['date_last_completed'], 'F, Y').'</p></td>';
			$td[] = '<td><p>'.$this->strtodate($cur_info['date_due'], 'F, Y').'</p></td>';
            $td[] = '<td><p>'.$this->strtodate($cur_info['date_completed'], 'F, Y').'</p></td>';
			$td[] = '<td><p>'.html_encode($cur_info['notes']).'</p></td>';
?>
            <tr id="row<?php echo $cur_info['id'] ?>">
                <?php echo implode('', $td) ?>
            </tr>
<?php
		}
 ?>
        </tbody>
    </table>
<?php
    }

    function strtodate($str, $format = 'F, Y')
    {
        $timestamp = strtotime($str);

        return ($timestamp > 0) ? date($format, $timestamp) : '';
    }

}