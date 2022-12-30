<?php
/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

define('SITE_ROOT', '../');
require SITE_ROOT.'include/common.php';

if (!$User->is_admin())
	message($lang_common['No permission']);

if (isset($_POST['update_group_perms']))
{
	redirect('', (($_POST['mode'] == 'edit') ? $lang_admin_groups['Group edited'] : $lang_admin_groups['Group added']));
}

$query = [
	'SELECT'	=> 'p.*, u.group_id, u.realname',
	'FROM'		=> 'permissions AS p',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=p.user_id'
		],
	],
//	'WHERE'		=> 'p.perm_key=\'punch_list_management\'',
	'ORDER BY'	=> 'u.realname',
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
}

$Core->set_page_title('Permissions');
$Core->set_page_id('admin_permissions', 'users');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">User Permissions</h6>
		</div>
		<table class="table table-striped my-0">
			<thead>
				<th>Username</th>
				<th>Project</th>
				<th>Permission Description</th>
				<th></th>
			</thead>
			<tbody>
<?php
if (!empty($main_info))
{
	foreach($main_info as $cur_info)
	{
		$td = [];
		$td[] = '<tr>';
		$td[] = '<td>'.html_encode($cur_info['realname']).'</td>';
		$td[] = '<td>'.html_encode($cur_info['realname']).'</td>';
		$td[] = '<td>'.$User->getPerm($cur_info['perm_key'], $cur_info['perm_value']).'</td>';
		$td[] = '<td>Delete</td>';
		$td[] = '</tr>';
		echo implode("\n", $td);
	}
}
?>
			</tbody>
		</table>
	</div>
</form>
	
<?php
require SITE_ROOT.'footer.php';
