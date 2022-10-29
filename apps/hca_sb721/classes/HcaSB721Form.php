<?php

/**
 * @author SwiftManager.Org
 * @copyright (C) 2021 SwiftManager license GPL
 * @package HcaSB721Form
**/

class HcaSB721Form
{
	// Generate Appendix-B PDF file
	function genPDF($form_info)
	{
		$box_not_checked = '';
		$box_checked = '<span style="font-family:helvetica;font-size: 16px;">&#10004;</span>';

		$output = '';
		$output .= '<p style="text-align:center;font-weight: bold;font-size: 1.6em;"><span>SB-721 Checklist</p>';

		$output .= '<table cellpadding="0" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;border:1px solid black;font-size:16px">';
		$output .= '<tbody>';

		$output .= '<tr>';
		$output .= '<td style="border-bottom: 1px solid #6a6565;">Property name:</td>';
		$output .= '<td style="border-bottom: 1px solid #6a6565;border-right: 1px solid #6a6565;"><strong>'.$form_info['pro_name'].'</strong></td>';
		$output .= '<td style="border-bottom: 1px solid #6a6565;">Performed by:</td>';
		$output .= '<td style="border-bottom: 1px solid #6a6565;"><strong>'.$form_info['realname'].'</strong></td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td>Unit number:</td>';
		$output .= '<td style="border-right: 1px solid #6a6565;"><strong>'.$form_info['unit_number'].'</strong></td>';
		$output .= '<td>Date performed:</td>';
		$output .= '<td><strong>'.$form_info['date_performed'].'</strong></td>';
		$output .= '</tr>';

		$output .= '</tbody>';
		$output .= '</table>';
		
		$output .= '<p style="font-size:1em;margin-bottom:.2em;font-weight: bold;">Exterior Elevated Elements</p>';
		
		$output .= '<table cellpadding="0" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;font-size:14px">';
		$output .= '<thead>';
		$output .= '<tr>';
		$output .= '<td style="border: 1px solid #6a6565;width:120px;text-align:center">Elements</td>';
		$output .= '<td style="border: 1px solid #6a6565;width:40px;text-align:center">OK</td>';
		$output .= '<td style="border: 1px solid #6a6565;width:40px;text-align:center">NO</td>';
		$output .= '<td style="border: 1px solid #6a6565;text-align:center">Comments</td>';
		$output .= '</tr>';
		$output .= '</thead>';

		$output .= '<tbody>';

		$output .= '<tr>';
		$output .= '<td style="">Supports/Beams</td>';
		$output .= '<td style="text-align:center">'.(($form_info['supports_check'] == 0) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="text-align:center">'.(($form_info['supports_check'] == 1) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="">'.html_encode($form_info['supports_text']).'</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td style="">Railings</td>';
		$output .= '<td style="text-align:center">'.(($form_info['railings_check'] == 0) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="text-align:center">'.(($form_info['railings_check'] == 1) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="">'.html_encode($form_info['railings_text']).'</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td style="">Balconies</td>';
		$output .= '<td style="text-align:center">'.(($form_info['balconies_check'] == 0) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="text-align:center">'.(($form_info['balconies_check'] == 1) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="">'.html_encode($form_info['balconies_text']).'</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td style="">Decks</td>';
		$output .= '<td style="text-align:center">'.(($form_info['decks_check'] == 0) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="text-align:center">'.(($form_info['decks_check'] == 1) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="">'.html_encode($form_info['decks_text']).'</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td style="">Porches</td>';
		$output .= '<td style="text-align:center">'.(($form_info['porches_check'] == 0) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="text-align:center">'.(($form_info['porches_check'] == 1) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="">'.html_encode($form_info['porches_text']).'</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td style="">Stairways</td>';
		$output .= '<td style="text-align:center">'.(($form_info['stairways_check'] == 0) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="text-align:center">'.(($form_info['stairways_check'] == 1) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="">'.html_encode($form_info['stairways_text']).'</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td style="">Walkways</td>';
		$output .= '<td style="text-align:center">'.(($form_info['walkways_check'] == 0) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="text-align:center">'.(($form_info['walkways_check'] == 1) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="">'.html_encode($form_info['walkways_text']).'</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td style="">Fascia</td>';
		$output .= '<td style="text-align:center">'.(($form_info['fascia_check'] == 0) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="text-align:center">'.(($form_info['fascia_check'] == 1) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="">'.html_encode($form_info['fascia_text']).'</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td style="">Stucco</td>';
		$output .= '<td style="text-align:center">'.(($form_info['stucco_check'] == 0) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="text-align:center">'.(($form_info['stucco_check'] == 1) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="">'.html_encode($form_info['stucco_text']).'</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td style="">Flashings</td>';
		$output .= '<td style="text-align:center">'.(($form_info['flashings_check'] == 0) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="text-align:center">'.(($form_info['flashings_check'] == 1) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="">'.html_encode($form_info['flashings_text']).'</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td style="">Membranes</td>';
		$output .= '<td style="text-align:center">'.(($form_info['membranes_check'] == 0) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="text-align:center">'.(($form_info['membranes_check'] == 1) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="">'.html_encode($form_info['membranes_text']).'</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td style="">Coatings</td>';
		$output .= '<td style="text-align:center">'.(($form_info['coatings_check'] == 0) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="text-align:center">'.(($form_info['coatings_check'] == 1) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="">'.html_encode($form_info['coatings_text']).'</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td style="">Sealants</td>';
		$output .= '<td style="text-align:center">'.(($form_info['sealants_check'] == 0) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="text-align:center">'.(($form_info['sealants_check'] == 1) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="">'.html_encode($form_info['sealants_text']).'</td>';
		$output .= '</tr>';

		$output .= '</tbody>';
		$output .= '</table>';
		
		$action = html_encode($form_info['action']);
		$output .= '<div style="margin-top:15px;">';
		$output .= '<strong>Action:</strong>';
		$output .= '<div style="width:100%;white-space:pre-line;padding:5px;font-weight:bold;border: 2px solid #0f33df;font-size: 1.2em;color:#0f33df;font-style: italic;">';
		$output .= nl2br($action);
		$output .= '</div>';
		$output .= '</div>';
		
		return $output;
	}
}
