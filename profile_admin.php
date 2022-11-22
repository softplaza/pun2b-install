<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

if (!$User->is_admin() && ($User->get('g_moderator') != '1' || $User->get('g_mod_ban_users') == '0' || $User->get('id') == $id))
	message($lang_common['Bad request']);

$page_param['hidden_fields'] = array(
	'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
	'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token().'" />'
);

if (!$page_param['own_profile'])
{
?>

<div class="col-md-8">
	<form method="post" accept-charset="utf-8" action="">
		<?php echo implode("\n\t\t\t\t", $page_param['hidden_fields'])."\n" ?>
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0">Privilege of <?php echo html_encode($user['realname']) ?></h6>
			</div>
			<div class="card-body">
				<div class="mb-3">
					<label class="form-label" for="field_group_id">Group</label>
					<select id="field_group_id" name="group_id" class="form-select">
<?php
	$query = array(
		'SELECT'	=> 'g.g_id, g.g_title',
		'FROM'		=> 'groups AS g',
		'ORDER BY'	=> 'g.g_title'
	);

	if (!$User->is_admin())
		$query['WHERE'] = 'g.g_id!='.USER_GROUP_ADMIN;
	else if ($User->get('g_moderator') != '1')
		$query['WHERE'] = 'g.g_id!='.USER_GROUP_ADMIN.' AND g.g_moderator!=1';

	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($cur_group = $DBLayer->fetch_assoc($result))
	{
		if ($cur_group['g_id'] == $user['g_id'] || ($cur_group['g_id'] == $Config->get('o_default_user_group') && $user['g_id'] == ''))
			echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.html_encode($cur_group['g_title']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.html_encode($cur_group['g_title']).'</option>'."\n";
	}
?>
					</select>
				</div>
				<div class="mb-3">
					<button type="submit" name="update_group_membership" class="btn btn-sm btn-primary">Update group</button>
				</div>
			</div>
		</div>
	</form>


<?php
	$query = [
		'SELECT'	=> 'a.*, u.group_id, u.realname',
		'FROM'		=> 'user_access AS a',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'u.id=a.a_uid'
			],
		],
		'WHERE'		=> 'a.a_uid='.$id,
	];
	$result = $DBLayer->query_build($query) or db_error(__FILE__, __LINE__);
	$access_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$access_info[] = $row;
	}

	if (!empty($access_info))
	{
?>
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0">Access to projects and features</h6>
			</div>

<?php
	Hook::doAction('ProfileAdminAccess');
?>

		</div>
<?php
	}
	else
	{
	?>
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0">Access to projects and features</h6>
			</div>
			<div class="card-body">
				<div class="alert alert-warning mb-0" role="alert">No permissions found in projects.</div>
			</div>
		</div>
	<?php
	}

	$query = [
		'SELECT'	=> 'n.*, u.group_id, u.realname',
		'FROM'		=> 'user_notifications AS n',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'u.id=n.n_uid'
			],
		],
		'WHERE'		=> 'n.n_uid='.$id,
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$notifications_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$notifications_info[] = $row;
	}
	
	if (!empty($notifications_info))
	{
?>
			<div class="card mb-3">
				<div class="card-header">
					<h6 class="card-title mb-0">Notifications for projects</h6>
				</div>
	
	<?php Hook::doAction('ProfileAdminNotifications'); ?>
	
			</div>
	<?php
	}
	else
	{
	?>
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0">Notifications for projects</h6>
			</div>
			<div class="card-body">
				<div class="alert alert-warning mb-0" role="alert">No available project notifications.</div>
			</div>
		</div>
	<?php
	}

?>

</div>

<?php
}