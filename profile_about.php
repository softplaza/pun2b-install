<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

// Private information
if ($User->is_admin())
{
	$page_param['private'][] = '<p>Login: <strong>'.html_encode($user['username']).'</strong></p>';
	$page_param['private'][] = '<p>IP: <a href="'.$URL->link('get_host', html_encode($user['registration_ip'])).'">'.html_encode($user['registration_ip']).'</a></p>';
	$page_param['private'][] = '<p>'.$lang_profile['Registered'].': <strong>'.format_time($user['registered'], 1).'</strong></p>';
	$page_param['private'][] = '<p>'.$lang_profile['Last visit'].': <strong>'.format_time($user['last_visit']).'</strong></p>';
	$page_param['private'][] = '<p>View now: <a href="'.$user['prev_url'].'">'.html_encode($user['prev_url']).'</a></p>';
}

$Hooks->get_hook('ProfileAboutPreHeader');
Hook::doAction('ProfileAboutPreHeader');

?>

	<div class="col-md-8">
	
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
				<h6 class="card-title mb-0">Project permissions</h6>
			</div>

<?php
Hook::doAction('ProfileAboutNewAccess');
$Hooks->get_hook('ProfileAboutPreHeader');
?>

		</div>
<?php
}
else
{
?>
	<div class="card mb-3">
		<div class="card-header">
			<h6 class="card-title mb-0">Project permissions</h6>
		</div>
		<div class="card-body">
			<div class="alert alert-warning mb-0" role="alert">No permissions found in projects.</div>
		</div>
	</div>
<?php
}

$query = [
	'SELECT'	=> 'p.*, u.group_id, u.realname',
	'FROM'		=> 'user_permissions AS p',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=p.p_uid'
		],
	],
	'WHERE'		=> 'p.p_uid='.$id,
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$permissions_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$permissions_info[] = $row;
}

if (!empty($permissions_info))
{
?>
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0">Permissions to manage projects</h6>
			</div>

<?php Hook::doAction('ProfileAboutNewPermissions'); ?>

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

<?php Hook::doAction('ProfileAboutNewNotifications'); ?>

		</div>
<?php
}
?>

<?php $Hooks->get_hook('ProfileAboutNewSection'); ?>

<?php Hook::doAction('ProfileAboutPrePrivateInfo'); ?>

<?php if (!empty($page_param['private'])) : ?>
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0">Private information</h6>
			</div>
			<div class="card-body">
				<?php echo implode("\n\t\t\t\t\t\t", $page_param['private'])."\n" ?>
			</div>
		</div>
<?php endif; ?>

	</div>
<?php


