<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

define('SITE_ROOT', '../');
require SITE_ROOT.'include/common.php';

if (!$User->is_admmod())
	message($lang_common['No permission']);

$errors = array();
if (isset($_POST['new_position']))
{
	$pos_name = isset($_POST['new_pos_name']) ? swift_trim($_POST['new_pos_name']) : '';
	$pos_desc = isset($_POST['new_pos_desc']) ? swift_trim($_POST['new_pos_desc']) : '';
	
	if ($pos_name != '')
	{
		$query = array(
			'INSERT'	=> 'pos_name, pos_desc',
			'INTO'		=> 'positions',
			'VALUES'	=> '\''.$DBLayer->escape($pos_name).'\', \''.$DBLayer->escape($pos_desc).'\''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	}
	else
		$Core->add_error('Position name cannot be empty.');
	
	// Add flash message
	$flash_message = 'Position '.$pos_name.' has been added';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}
else if (isset($_POST['update']))
{
	if (isset($_POST['pos_name']) && !empty($_POST['pos_name']))
	{
		foreach($_POST['pos_name'] as $key => $val)
		{
			$pos_name = swift_trim($_POST['pos_name'][$key]);
			$pos_desc = swift_trim($_POST['pos_desc'][$key]);
			$department_id = intval($_POST['department_id'][$key]);
			
			$query = array(
				'UPDATE'	=> 'positions',
				'SET'		=> 'pos_name=\''.$DBLayer->escape($pos_name).'\', pos_desc=\''.$DBLayer->escape($pos_desc).'\', department_id=\''.$DBLayer->escape($department_id).'\'',
				'WHERE'		=> 'id='.$key
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
		
		// Add flash message
		$flash_message = 'Positions has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

// Remove a rank
else if (isset($_POST['delete']))
{
	$id = intval(key($_POST['delete']));

	$query = array(
		'DELETE'	=> 'positions',
		'WHERE'		=> 'id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'UPDATE'	=> 'users',
		'SET'		=> 'pos_id=0',
		'WHERE'		=> 'pos_id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Add flash message
	$flash_message = 'Position #'.$id.' has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$query = array(
	'SELECT'	=> 'd.*',
	'FROM'		=> 'departments AS d',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$depts_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$depts_info[] = $row;
}

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'positions AS p',
	'ORDER BY'	=> 'p.pos_name',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($row = $DBLayer->fetch_assoc($result))
{
	$main_info[] = $row;
}

$page_param['item_count'] = 0;

$Core->set_page_title('Position List');
$Core->set_page_id('admin_positions', 'users');
require SITE_ROOT.'header.php';
?>

<style>
.search-box input[name="new_pos_name"]{width:250px;}
.search-box input[name="new_pos_desc"]{width:350px;}
.input input{width:97%;}
td{text-align:center;}
</style>

<div class="main-content main-frm">
	<div class="ct-group">
		<form id="afocus" method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<div class="search-box">
				<input type="text" name="new_pos_name" value="" placeholder="Enter Position Name">
				<input type="text" name="new_pos_desc" value="" placeholder="Enter Position Description">
				<input type="submit" name="new_position" value="New Position">
			</div>
<?php
if (!empty($main_info))
{
	$page_param['th'] = array();
	$page_param['th'][] = '<th><strong>Position Name</strong></th>';
	$page_param['th'][] = '<th><strong>Position Description</strong></th></strong>';
	$page_param['th'][] = '<th><strong>Department</strong></th></strong>';
	$page_param['th'][] = '<th><strong>Actions</strong></th>';
?>
			<table>
				<thead>
					<tr>
						<?php echo implode("\n\t\t\t\t\t\t", $page_param['th'])."\n" ?>
					</tr>
				</thead>
				<tbody>
<?php

	foreach ($main_info as $cur_info)
	{
		$page_param['td'] = array();
		$page_param['td'][] = '<td class="input"><input type="text" name="pos_name['.$cur_info['id'].']" value="'.html_encode($cur_info['pos_name']).'"></td>';
		$page_param['td'][] = '<td class="input"><input type="text" name="pos_desc['.$cur_info['id'].']" value="'.html_encode($cur_info['pos_desc']).'"></td>';
//		$page_param['td'][] = '<td><span class="submit primary caution"><input type="submit" name="delete['.$cur_info['id'].']" value="x" /></span></td>';
		
		++$page_param['item_count'];
?>
			<tr class="<?php echo ($page_param['item_count'] % 2 != 0) ? 'odd' : 'even' ?><?php if ($page_param['item_count'] == 1) echo ' row1'; ?>">
				<?php echo implode("\n\t\t\t\t\t\t", $page_param['td'])."\n" ?>
				<td><select name="department_id[<?php echo $cur_info['id'] ?>]">
					<option value="0">No Department</option>
<?php
foreach($depts_info as $dept_info) {
	if ($dept_info['id'] == $cur_info['department_id'])
		echo '<option value="'.$dept_info['id'].'" selected>'.html_encode($dept_info['dept_name']).'</option>';
	else
		echo '<option value="'.$dept_info['id'].'">'.html_encode($dept_info['dept_name']).'</option>';
}
?>	
				</td>
				<td><span class="submit primary caution"><input type="submit" name="delete[<?php echo $cur_info['id'] ?>]" value="x" /></span></td>
			</tr>
<?php
	}
?>
				</tbody>
			</table>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="update" value="Update" /></span>
			</div>
<?php
}
else
{
?>
			<div class="ct-box info-box">
				<p><strong>No positions found</strong></p>
			</div>
<?php
}
?>
		</form>
	</div>
</div>

<?php
require SITE_ROOT.'footer.php';