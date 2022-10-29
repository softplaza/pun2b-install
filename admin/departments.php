<?php

define('SITE_ROOT', '../');
require SITE_ROOT.'include/common.php';

if (!$User->is_admin())
	message($lang_common['No permission']);

// Add a rank
if (isset($_POST['add_department']))
{
	$dept_name = swift_trim($_POST['new_dept_name']);
	$dept_desc = swift_trim($_POST['new_dept_desc']);
	
	if ($dept_name == '')
		message('Enter name of Department');

	$query = array(
		'INSERT'	=> 'dept_name, dept_desc',
		'INTO'		=> 'departments',
		'VALUES'	=> '\''.$DBLayer->escape($dept_name).'\', \''.$DBLayer->escape($dept_desc).'\''
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Add flash message
	$flash_message = 'Department '.$dept_name.' has been added';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

// Update a rank
else if (isset($_POST['update']))
{
	if (isset($_POST['dept_name']) && !empty($_POST['dept_name']))
	{
		foreach($_POST['dept_name'] as $key => $val)
		{
			$dept_name = swift_trim($_POST['dept_name'][$key]);
			$dept_desc = swift_trim($_POST['dept_desc'][$key]);
			
			$query = array(
				'UPDATE'	=> 'departments',
				'SET'		=> 'dept_name=\''.$DBLayer->escape($dept_name).'\', dept_desc=\''.$DBLayer->escape($dept_desc).'\'',
				'WHERE'		=> 'id='.$key
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
		
		// Add flash message
		$flash_message = 'Departments has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

// Remove a dept
else if (isset($_POST['delete']))
{
	$id = intval(key($_POST['delete']));

	$query = array(
		'DELETE'	=> 'departments',
		'WHERE'		=> 'id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'UPDATE'	=> 'users',
		'SET'		=> 'dept_id=0',
		'WHERE'		=> 'dept_id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Add flash message
	$flash_message = 'Department #'.$id.' has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$query = array(
	'SELECT'	=> 'd.*',
	'FROM'		=> 'departments AS d',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
}

// Setup the form
$page_param['fld_count'] = $page_param['item_count'] = $page_param['group_count'] = 0;

$Core->set_page_title('Positions List');
$Core->set_page_id('admin_departments', 'users');
require SITE_ROOT.'header.php';

?>
<style>
.search-box input[name="new_dept_name"]{width:250px;}
.search-box input[name="new_dept_desc"]{width:350px;}
.input input{width:97%;}
td{text-align:center;}
</style>


<div class="main-content main-frm">
	<div class="ct-group">
		<form id="afocus" method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<div class="search-box">
				<input type="text" name="new_dept_name" value="" placeholder="Department Name">
				<input type="text" name="new_dept_desc" value="" placeholder="Department Description">
				<input type="submit" name="add_department" value="New Department">
			</div>
<?php
if (!empty($main_info))
{
	$page_param['th'] = array();
	$page_param['th'][] = '<th><strong>Department Name</strong></th>';
	$page_param['th'][] = '<th><strong>Department Description</strong></th></strong>';
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
		$page_param['td'][] = '<td class="input"><input type="text" name="dept_name['.$cur_info['id'].']" value="'.html_encode($cur_info['dept_name']).'"></td>';
		$page_param['td'][] = '<td class="input"><input type="text" name="dept_desc['.$cur_info['id'].']" value="'.html_encode($cur_info['dept_desc']).'"></td>';
		$page_param['td'][] = '<td><span class="submit primary caution"><input type="submit" name="delete['.$cur_info['id'].']" value="x" /></span></td>';
		
		++$page_param['item_count'];
?>
			<tr class="<?php echo ($page_param['item_count'] % 2 != 0) ? 'odd' : 'even' ?><?php if ($page_param['item_count'] == 1) echo ' row1'; ?>">
				<?php echo implode("\n\t\t\t\t\t\t", $page_param['td'])."\n" ?>
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
				<p><strong>No departments found</strong></p>
			</div>
<?php
}
?>
		</form>
		
	</div>
</div>

<?php
require SITE_ROOT.'footer.php';
