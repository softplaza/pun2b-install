<?php

function hca_sp_update_invoice_pdf()
{
	global $DBLayer, $main_info;
	$search_by_vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;

	$output = false;
	if (!empty($main_info))
	{
		$html_table = '<p style="text-align:center"><strong>HCA: Invoice of Special Projects</strong></p>';
		if ($search_by_vendor_id > 0)
		{
			$query = array(
				'SELECT'	=> '*',
				'FROM'		=> 'sm_vendors',
				'WHERE'		=> 'id='.$search_by_vendor_id
			);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$vendor_info = $DBLayer->fetch_assoc($result);
			
			$html_table .= '<p>Vendor Name: <strong>'.html_encode($vendor_info['vendor_name']).'</strong></p>';
			$html_table .= '<p>Phone Number: <strong>'.html_encode($vendor_info['phone_number']).'</strong></p>';
			$html_table .= '<p>Email: <strong>'.html_encode($vendor_info['vendor_email']).'</strong></p>';
		}
		
		$html_table .= '<table cellpadding="2px" autosize="1" width="100%" style="overflow: wrap;border:1px solid black;">';
		$html_table .= '<tr><td><strong>VENDOR</strong></td><td><strong>PROPERTY</strong></td><td><strong>P.O. #</strong></td><td><strong>WORK PERFORMED</strong></td></tr>';
		
		foreach($main_info as $cur_info)
		{
			$html_table .= '<tr><td>'.html_encode($cur_info['vendor']).'</td>';
			$html_table .= '<td>'.html_encode($cur_info['property_name']).'</td>';
			$html_table .= '<td>'.html_encode($cur_info['po_number']).'</td>';
			$html_table .= '<td>'.html_encode($cur_info['work_performed']).'</td></tr>';
		}
		
		$html_table .= '</table>';
		
		$mPDF = new \Mpdf\Mpdf();
		$mPDF->WriteHTML($html_table);
		
		$mPDF->Output('last_invoice.pdf', 'F');
		$output = true;
	}
	
	return $output;
}
