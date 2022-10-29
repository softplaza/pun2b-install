<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$permission = ($User->is_admmod()) ? true : false;
if (!$permission)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$query = array(
	'SELECT'	=> 'a.id, a.user_name, a.user_id, a.sender_name, a.project_id, a.action_time, a.subject, a.message, a.msg_status, r.property_name, r.action_date',
	'FROM'		=> 'sm_special_projects_actions AS a',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_special_projects_records AS r',
			'ON'			=> 'r.id=a.project_id'
		)
	),
	'WHERE'		=> 'user_id='.$User->get('id'),
	'ORDER BY'	=> 'a.action_time DESC'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$fetch_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$fetch_info[] = $fetch_assoc;
}

// Setup the form
$page_param['fld_count'] = $page_param['group_count'] = $page_param['item_count'] = 0;

$Core->set_page_title('List of actions');
$Core->set_page_id('sm_special_projects_actions', 'hca_sp');
require SITE_ROOT.'header.php';
?>

<style>
table {table-layout: initial;}
.main-content th {
	padding: 5px;
	border: 1px solid #adafc7;
	background: #c0e6fc;
	text-align: center;
	font-weight: bold !important;
	text-transform: uppercase;
}
tbody td {
	padding: 3px 5px;
	border: 1px solid #adafc7;
	background: #fef9e2 !important;
	vertical-align: top;
}

input[type="submit"], input[type="button"], input[type="reset"], button {margin-left: 10px;}
.schedule-link {text-align: center;}
.unreaded-msgs{font-weight: bold;}
</style>
	
<div class="main-content main-frm">
<?php
if (!empty($fetch_info)) 
{
?>
	<div class="ct-group">
		<table>
			<tbody>
				<thead class="thead-list">
					<tr>
						<th style="width:15%;">Submited by</th>
						<th style="width:65%;">Subject</th>
						<th style="width:15%;">Time</th>
					</tr>
				</thead>
<?php
	foreach ($fetch_info as $info)
	{
		if ($id > 0 && $id == $info['id'])
			$unreaded_msgs = '';
		else
			$unreaded_msgs = ($info['msg_status'] == 0) ? ' unreaded-msgs' : '';
		
		$subject = ($info['subject'] != '') ? html_encode($info['subject']) : '[no subject]';
		
		if ($id > 0 && $id == $info['id'])
		{
			$info_message = sm_html_parser($info['message']);
			$message = '<span class="'.$unreaded_msgs.'"><span style="text-decoration: underline;">Subject:</span> '.$subject.'</span> <p class="message"><span style="text-decoration: underline;">Message:</span> '.$info_message.'</p>';
			//MARK MESSAGE AS READ
			$query = array(
				'UPDATE'	=> 'sm_special_projects_actions',
				'SET'		=> 'msg_status=1',
				'WHERE'		=> 'id='.$info['id'],
			);
			if($info['msg_status'] == 0)
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
		} else {
			$unreaded_msgs = ($info['msg_status'] == 0) ? ' unreaded-msgs' : '';
			$message = '<span class="'.$unreaded_msgs.'"><a href="'.$URL->link('sm_special_projects_actions', $info['id']).'">'.$subject.'</a></span> <span class="message">'.substr(html_encode($info['message']), 0, 50).'...'.'</span>';
		}
?>
				<tr>
					<td><span class="<?php echo $unreaded_msgs ?>"><?php echo $info['sender_name'] ?></span></td>
					<td><?php echo $message ?></span></td>
					<td><?php echo format_time($info['action_time']) ?></td>
				</tr>
<?php
	}
?>
			</tbody>
		</table>
	</div>
<?php

} else {
	
?>
	<div class="ct-box warn-box">
		<p>You do not have new messages.</p>
	</div>
<?php
}
?>
</div>

<?php
require SITE_ROOT.'footer.php';