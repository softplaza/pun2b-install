<?php

if (!defined('SITE_ROOT'))
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';

$form_data = array();
$form_data['id'] = isset($_POST['id']) ? intval($_POST['id']) : 0;
$form_data['project_id'] = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
$form_data['vendor'] = isset($_POST['vendor']) ? swift_trim($_POST['vendor']) : '';
$form_data['po_number'] = isset($_POST['po_number']) ? swift_trim($_POST['po_number']) : '';
$form_data['price'] = isset($_POST['price']) ? swift_trim($_POST['price']) : '';

if (isset($_POST['update_row']) && $_POST['update_row'] == 1)
{
	if (!empty($form_data) && $form_data['project_id'] > 0)
	{
		$query = array(
			'UPDATE'	=> 'hca_5840_invoices',
			'SET'	=> 
				'vendor=\''.$DBLayer->escape($form_data['vendor']).'\',
				po_number=\''.$DBLayer->escape($form_data['po_number']).'\',
				price=\''.$DBLayer->escape($form_data['price']).'\'',
			'WHERE'		=> 'id='.$form_data['id']
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> 'hca_5840_invoices',
			'WHERE'		=> 'id='.$form_data['id'],
			'ORDER BY'	=> 'vendor DESC'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$invoice_info = $DBLayer->fetch_assoc($result);
		
		$query = array(
			'SELECT'	=> 'price',
			'FROM'		=> 'hca_5840_invoices',
			'WHERE'		=> 'project_id='.$form_data['project_id'],
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$total_cost = 0;
		while ($row = $DBLayer->fetch_assoc($result))
			$total_cost = $total_cost + intval($row['price']);
		
		echo json_encode(array(
			'vendor' => $invoice_info['vendor'],
			'po_number' => $invoice_info['po_number'],
			'price' => $invoice_info['price'],
			'total_cost' => $total_cost,
			'flash_message' => '<span class="success">Rows updated.</span>'
		));
		
		$query = array(
			'UPDATE'	=> 'hca_5840_projects',
			'SET'	=> 'total_cost=\''.$DBLayer->escape($total_cost).'\'',
			'WHERE'		=> 'id='.$form_data['project_id']
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	}
	else
		echo json_encode(array('flash_message' => '<span class="error">Something wrong.</span>'));
}
else if (isset($_POST['insert_row']) && $_POST['insert_row'] == 1)
{
	if (!empty($form_data) && $form_data['project_id'] > 0)
	{
		$new_pid = $DBLayer->insert_values('hca_5840_invoices', $form_data);
		
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> 'hca_5840_invoices',
			'WHERE'		=> 'project_id='.$form_data['project_id'],
			'ORDER BY'	=> 'vendor DESC'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$invoice_info = array();
		while ($row = $DBLayer->fetch_assoc($result))
		{
			$invoice_info[] = '<tr id="row'.$row['id'].'" class="row" onclick="editRow('.$row['id'].')">';
			$invoice_info[] .= '<td class="vendor"><span class="input"><input type="text" name="vendor" value="'.html_encode($row['vendor']).'" onchange="onChangeRow('.$row['id'].')"/></span>';
			$invoice_info[] .= '<span class="text">'.html_encode($row['vendor']).'</span></td>';
			$invoice_info[] .= '<td class="po_number"><span class="input"><input type="text" name="po_number" value="'.html_encode($row['po_number']).'" onchange="onChangeRow('.$row['id'].')"/></span>';
			$invoice_info[] .= '<span class="text">'.html_encode($row['po_number']).'</span></td>';
			$invoice_info[] .= '<td class="price"><span class="input"><input type="text" name="price" value="'.html_encode($row['price']).'" onchange="onChangeRow('.$row['id'].')"/></span>';
			$invoice_info[] .= '<span class="text">'.html_encode($row['price']).'</span></td>';
			$invoice_info[] .= '<td class="action"><span class="submit primary caution"><input type="button" value="x" onclick="deleteRow('.$row['id'].')"/></span></td>';
			$invoice_info[] .= '</tr>';
			
			
		}
		
		echo json_encode(array(
			'rows' => implode('', $invoice_info),
			'flash_message' => '<span class="success">The row has been added.</span>'
		));
	}
	else
		echo json_encode(array('flash_message' => '<span class="error">Something wrong.</span>'));
}
else if (isset($_POST['delete_row']) && $_POST['delete_row'] == 1)
{
	if ($form_data['id'] > 0 && $form_data['project_id'] > 0)
	{
		$query = array(
			'DELETE'	=> 'hca_5840_invoices',
			'WHERE'		=> 'id='.$form_data['id'],
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> 'hca_5840_invoices',
			'WHERE'		=> 'project_id='.$form_data['project_id'],
			'ORDER BY'	=> 'vendor DESC'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$invoice_info = array();
		while ($row = $DBLayer->fetch_assoc($result))
		{
			$invoice_info[] = '<tr id="row'.$row['id'].'" class="row" onclick="editRow('.$row['id'].')">';
			$invoice_info[] .= '<td class="vendor"><span class="input"><input type="text" name="vendor" value="'.html_encode($row['vendor']).'" onchange="onChangeRow('.$row['id'].')"/></span>';
			$invoice_info[] .= '<span class="text">'.html_encode($row['vendor']).'</span></td>';
			$invoice_info[] .= '<td class="po_number"><span class="input"><input type="text" name="po_number" value="'.html_encode($row['po_number']).'" onchange="onChangeRow('.$row['id'].')"/></span>';
			$invoice_info[] .= '<span class="text">'.html_encode($row['po_number']).'</span></td>';
			$invoice_info[] .= '<td class="price"><span class="input"><input type="text" name="price" value="'.html_encode($row['price']).'" onchange="onChangeRow('.$row['id'].')"/></span>';
			$invoice_info[] .= '<span class="text">'.html_encode($row['price']).'</span></td>';
			$invoice_info[] .= '<td class="action"><span class="submit primary caution"><input type="button" value="x" onclick="deleteRow('.$row['id'].')"/></span></td>';
			$invoice_info[] .= '</tr>';
		}
		
		echo json_encode(array(
			'rows'			=> implode('', $invoice_info),
			'flash_message' => '<span class="success">The row has been removed.</span>'
		));
	}
	else
		echo json_encode(array('flash_message' => '<span class="error">Something wrong.</span>'));
}

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();