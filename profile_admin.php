<?php

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
		'WHERE'		=> 'g.g_id!='.USER_GROUP_GUEST,
		'ORDER BY'	=> 'g.g_title'
	);
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
					<button type="submit" name="update_group_membership" class="btn btn-sm btn-primary">Update groups</button>
				</div>
			</div>
		</div>
	</form>
</div>

<?php
}