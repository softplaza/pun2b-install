<?php

/**
 * @author SwiftProjectManager.Com
 * @copyright (C) 2022 SwiftManager license GPL
 * @package SwiftSettings
 * 
 * USE FOLLOVING FUNCTIONS TO CHECK ACCESS / PERMISSIONS / NOTIFICATIONS
 * $User->checkAccess(app_id, key);
 * $User->checkPermissions(app_id, key);
 * 
**/

class SwiftSettings
{
	var $rules_to = '';
	var $access_options = [];
	var $permissions_options = [];
	var $notifications_options = [];

	function setId($id){
		$this->rules_to = $id;
	}

	function addAccessOption($key, $val){
		$this->access_options[$key] = $val;
	}

	function addPermissionOption($key, $val){
		$this->permissions_options[$key] = $val;
	}

	function addNotifyOption($key, $val){
		$this->notifications_options[$key] = $val;
	}

	function POST()
	{
		global $DBLayer, $FlashMessenger, $Core, $Config;

		if (isset($_POST['create']))
		{
			$type = isset($_POST['type']) ? intval($_POST['type']) : 0;
			$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
			$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
		
			if ($user_id > 0 && $type == 1)
			{
				$num_rows = $DBLayer->getNumRows('user_access', 'a_to=\''.$DBLayer->escape($this->rules_to).'\' AND a_uid='.$user_id);

				foreach($this->access_options as $key => $title)
				{
					$data = [
						'a_uid'		=> $user_id,
						'a_to'		=> $this->rules_to,
						'a_key'		=> $key,
						'a_value'	=> 0
					];
					if ($num_rows < 1)
						$DBLayer->insert('user_access', $data);
				}

				if ($num_rows > 0)
					$Core->add_error('The selected user is already in the list.');
			}
			else if ($user_id > 0 && $type == 2)
			{
				$num_rows = $DBLayer->getNumRows('user_permissions', 'p_to=\''.$DBLayer->escape($this->rules_to).'\' AND p_uid='.$user_id);

				foreach($this->permissions_options as $key => $title)
				{
					$data = [
						'p_uid'		=> $user_id,
						'p_to'		=> $this->rules_to,
						'p_key'		=> $key,
						'p_value'	=> 0
					];
					if ($num_rows < 1)
						$DBLayer->insert('user_permissions', $data);
				}

				if ($num_rows > 0)
					$Core->add_error('The selected user is already in the list.');
			}
			else if ($user_id > 0 && $type == 3)
			{
				$num_rows = $DBLayer->getNumRows('user_notifications', 'n_to=\''.$DBLayer->escape($this->rules_to).'\' AND n_uid='.$user_id);

				foreach($this->notifications_options as $key => $title)
				{
					$data = [
						'n_uid'		=> $user_id,
						'n_to'		=> $this->rules_to,
						'n_key'		=> $key,
						'n_value'	=> 0
					];
					if ($num_rows < 1)
						$DBLayer->insert('user_notifications', $data);
				}

				if ($num_rows > 0)
					$Core->add_error('The selected user is already in the list.');
			}
			else if ($group_id > 0 && $type == 4)
			{
				$num_rows = $DBLayer->getNumRows('user_access', 'a_to=\''.$DBLayer->escape($this->rules_to).'\' AND a_gid='.$group_id);

				foreach($this->access_options as $key => $title)
				{
					$data = [
						'a_gid'		=> $group_id,
						'a_to'		=> $this->rules_to,
						'a_key'		=> $key,
						'a_value'	=> 0
					];
					if ($num_rows < 1)
						$DBLayer->insert('user_access', $data);
				}

				if ($num_rows > 0)
					$Core->add_error('The selected group is already in the list.');
			}
			else if ($group_id > 0 && $type == 5)
			{
				$num_rows = $DBLayer->getNumRows('user_permissions', 'p_to=\''.$DBLayer->escape($this->rules_to).'\' AND p_gid='.$group_id);

				foreach($this->permissions_options as $key => $title)
				{
					$data = [
						'p_gid'		=> $group_id,
						'p_to'		=> $this->rules_to,
						'p_key'		=> $key,
						'p_value'	=> 0
					];
					if ($num_rows < 1)
						$DBLayer->insert('user_permissions', $data);
				}

				if ($num_rows > 0)
					$Core->add_error('The selected group is already in the list.');
			}
			else if ($group_id > 0 && $type == 6)
			{
				$num_rows = $DBLayer->getNumRows('user_notifications', 'n_to=\''.$DBLayer->escape($this->rules_to).'\' AND n_gid='.$group_id);

				foreach($this->notifications_options as $key => $title)
				{
					$data = [
						'n_gid'		=> $group_id,
						'n_to'		=> $this->rules_to,
						'n_key'		=> $key,
						'n_value'	=> 0
					];
					if ($num_rows < 1)
						$DBLayer->insert('user_notifications', $data);
				}

				if ($num_rows > 0)
					$Core->add_error('The selected group is already in the list.');
			}
			else
				$Core->add_error('Select an user or group');
		
			if (empty($Core->errors))
			{
				// Add flash message
				$flash_message = 'Rules created';
				$FlashMessenger->add_info($flash_message);
				redirect('', $flash_message);
			}
		}
		
		else if (isset($_POST['delete_access']))
		{
			$user_id = intval(key($_POST['delete_access']));
		
			$query = array(
				'DELETE'	=> 'user_access',
				'WHERE'		=> 'a_to=\''.$DBLayer->escape($this->rules_to).'\' AND a_uid='.$user_id,
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
			// Add flash message
			$flash_message = 'Access deleted.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}
		else if (isset($_POST['delete_group_access']))
		{
			$gid = intval(key($_POST['delete_group_access']));
		
			$query = array(
				'DELETE'	=> 'user_access',
				'WHERE'		=> 'a_to=\''.$DBLayer->escape($this->rules_to).'\' AND a_gid='.$gid,
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
			// Add flash message
			$flash_message = 'Access deleted.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}
		else if (isset($_POST['delete_permissions']))
		{
			$user_id = intval(key($_POST['delete_permissions']));
		
			$query = array(
				'DELETE'	=> 'user_permissions',
				'WHERE'		=> 'p_to=\''.$DBLayer->escape($this->rules_to).'\' AND p_uid='.$user_id,
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
			// Add flash message
			$flash_message = 'Permissions deleted.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}
		else if (isset($_POST['delete_group_permissions']))
		{
			$gid = intval(key($_POST['delete_group_permissions']));
		
			$query = array(
				'DELETE'	=> 'user_permissions',
				'WHERE'		=> 'p_to=\''.$DBLayer->escape($this->rules_to).'\' AND p_gid='.$gid,
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
			// Add flash message
			$flash_message = 'Permissions for group #'.$gid.' have been deleted.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}
		else if (isset($_POST['delete_notifications']))
		{
			$user_id = intval(key($_POST['delete_notifications']));
		
			$query = array(
				'DELETE'	=> 'user_notifications',
				'WHERE'		=> 'n_to=\''.$DBLayer->escape($this->rules_to).'\' AND n_uid='.$user_id,
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
			// Add flash message
			$flash_message = 'Notifications deleted.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}
		else if (isset($_POST['delete_group_notifications']))
		{
			$gid = intval(key($_POST['delete_group_notifications']));
		
			$query = array(
				'DELETE'	=> 'user_notifications',
				'WHERE'		=> 'n_to=\''.$DBLayer->escape($this->rules_to).'\' AND n_gid='.$gid,
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
			// Add flash message
			$flash_message = 'Group notifications have been deleted.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}

		else if (isset($_POST['fix_group_access']))
		{
			$group_id = intval(key($_POST['fix_group_access']));
			$access_keys = isset($_POST['access_keys'][$group_id]) ? $_POST['access_keys'][$group_id] : [];
		
			foreach($this->access_options as $key => $title)
			{
				$data = [
					'a_gid'		=> $group_id,
					'a_to'		=> $this->rules_to,
					'a_key'		=> $key,
					'a_value'	=> 0
				];
				if (!empty($access_keys) && !in_array($key, $access_keys))
					$DBLayer->insert('user_access', $data);
			}
			
			// Add flash message
			$flash_message = 'Group permissions fixed.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}

		else if (isset($_POST['fix_extra_group_access']))
		{
			$group_id = intval(key($_POST['fix_extra_group_access']));
			$access_keys = isset($_POST['access_keys'][$group_id]) ? $_POST['access_keys'][$group_id] : [];
		
			$keys = [];
			foreach($this->access_options as $key => $title)
			{
					$keys[] = $key;
			}
			
			$query = array(
				'DELETE'	=> 'user_access',
				'WHERE'		=> 'a_key NOT IN ('.implode(',', $keys).') AND a_to=\''.$DBLayer->escape($this->rules_to).'\' AND a_gid='.$group_id,
			);
			if (!empty($keys))
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);

			// Add flash message
			$flash_message = 'User extra access fixed.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}


		else if (isset($_POST['fix_missing_access']))
		{
			$user_id = intval(key($_POST['fix_missing_access']));
			$access_keys = isset($_POST['access_keys'][$user_id]) ? $_POST['access_keys'][$user_id] : [];
		
			foreach($this->access_options as $key => $title)
			{
				$data = [
					'a_uid'		=> $user_id,
					'a_to'		=> $this->rules_to,
					'a_key'		=> $key,
					'a_value'	=> 0
				];
				if (!in_array($key, $access_keys))
					$DBLayer->insert('user_access', $data);
			}
			
			// Add flash message
			$flash_message = 'User access fixed.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}
		
		else if (isset($_POST['fix_extra_access']))
		{
			$user_id = intval(key($_POST['fix_extra_access']));
			$access_keys = isset($_POST['access_keys'][$user_id]) ? $_POST['access_keys'][$user_id] : [];
		
			$keys = [];
			foreach($this->access_options as $key => $title)
			{
				//if (!in_array($key, $access_keys))
					$keys[] = $key;
			}
			
			$query = array(
				'DELETE'	=> 'user_access',
				'WHERE'		=> 'a_key NOT IN ('.implode(',', $keys).') AND a_to=\''.$DBLayer->escape($this->rules_to).'\' AND a_uid='.$user_id,
			);
			if (!empty($keys))
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);

			// Add flash message
			$flash_message = 'User extra access fixed.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}

		else if (isset($_POST['fix_permissions']))
		{
			$user_id = intval(key($_POST['fix_permissions']));
			$permissions_keys = isset($_POST['permissions_keys'][$user_id]) ? $_POST['permissions_keys'][$user_id] : [];
		
			foreach($this->permissions_options as $key => $title)
			{
				$data = [
					'p_uid'		=> $user_id,
					'p_to'		=> $this->rules_to,
					'p_key'		=> $key,
					'p_value'	=> 0
				];
				if (!in_array($key, $permissions_keys))
					$DBLayer->insert('user_permissions', $data);
			}
			
			// Add flash message
			$flash_message = 'Permissions updated.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}

		else if (isset($_POST['fix_group_permissions']))
		{
			$group_id = intval(key($_POST['fix_group_permissions']));
			$permissions_keys = isset($_POST['permissions_keys'][$group_id]) ? $_POST['permissions_keys'][$group_id] : [];
		
			foreach($this->permissions_options as $key => $title)
			{
				$data = [
					'p_gid'		=> $group_id,
					'p_to'		=> $this->rules_to,
					'p_key'		=> $key,
					'p_value'	=> 0
				];
				if (!in_array($key, $permissions_keys))
					$DBLayer->insert('user_permissions', $data);
			}
			
			// Add flash message
			$flash_message = 'Permissions updated.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}

		// Add User Notifications
		else if (isset($_POST['fix_notifications']))
		{
			$user_id = intval(key($_POST['fix_notifications']));
			$notifications_keys = isset($_POST['notifications_keys'][$user_id]) ? $_POST['notifications_keys'][$user_id] : [];
		
			foreach($this->notifications_options as $key => $title)
			{
				$data = [
					'n_uid'		=> $user_id,
					'n_to'		=> $this->rules_to,
					'n_key'		=> $key,
					'n_value'	=> 0
				];
				if (!in_array($key, $notifications_keys))
					$DBLayer->insert('user_notifications', $data);
			}
			
			// Add flash message
			$flash_message = 'Notifications updated.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}

		// Add Group Notifications
		else if (isset($_POST['fix_group_notifications']))
		{
			$group_id = intval(key($_POST['fix_group_notifications']));

			$notifications_keys = isset($_POST['notifications_keys'][$group_id]) ? $_POST['notifications_keys'][$group_id] : [];

			foreach($this->notifications_options as $key => $title)
			{
				$data = [
					'n_gid'		=> $group_id,
					'n_to'		=> $this->rules_to,
					'n_key'		=> $key,
					'n_value'	=> 0
				];
				if (!in_array($key, $notifications_keys))
					$DBLayer->insert('user_notifications', $data);
			}
			
			// Add flash message
			$flash_message = 'Group notifications updated.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}
	}

	function createRule()
	{
		global $DBLayer;

		$query = array(
			'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
			'FROM'		=> 'groups AS g',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'g.g_id=u.group_id'
				)
			),
			'WHERE'		=> 'group_id > 2',
			'ORDER BY'	=> 'g.g_id, u.realname',
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$users_info = [];
		while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
			$users_info[] = $fetch_assoc;
		}
?>
	
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0">Setup rights for:</h6>
			</div>
			<div class="card-body">
	
				<div class="row mb-3">
					<div class="col-md-12">
	
					<?php if (!empty($this->access_options)) : ?>
						<div class="form-check form-check-inline">
							<input class="form-check-input" type="radio" name="type" id="radio1" value="1" required onchange="switchSection(1)">
							<label class="form-check-label fw-bold" for="radio1">User access</label>
						</div>
					<?php endif; ?>
	
					<?php if (!empty($this->permissions_options)) : ?>
						<div class="form-check form-check-inline">
							<input class="form-check-input" type="radio" name="type" id="radio2" value="2" required onchange="switchSection(1)">
							<label class="form-check-label fw-bold" for="radio2">User permissions</label>
						</div>
					<?php endif; ?>
	
					<?php if (!empty($this->notifications_options)) : ?>
						<div class="form-check form-check-inline">
							<input class="form-check-input" type="radio" name="type" id="radio3" value="3" required onchange="switchSection(1)">
							<label class="form-check-label fw-bold" for="radio3">User notifications</label>
						</div>
					<?php endif; ?>
	
					<?php if (!empty($this->access_options)) : ?>
						<div class="form-check form-check-inline">
							<input class="form-check-input" type="radio" name="type" id="radio4" value="4" required onchange="switchSection(2)">
							<label class="form-check-label fw-bold" for="radio4">Group access</label>
						</div>
					<?php endif; ?>
	
					<?php if (!empty($this->permissions_options)) : ?>
						<div class="form-check form-check-inline">
							<input class="form-check-input" type="radio" name="type" id="radio5" value="5" required onchange="switchSection(2)">
							<label class="form-check-label fw-bold" for="radio5">Group permissions</label>
						</div>
					<?php endif; ?>
	
					<?php if (!empty($this->notifications_options)) : ?>
						<div class="form-check form-check-inline">
							<input class="form-check-input" type="radio" name="type" id="radio6" value="6" required onchange="switchSection(2)">
							<label class="form-check-label fw-bold" for="radio6">Group notifications</label>
						</div>
					<?php endif; ?>
	
					</div>	
				</div>

				<div class="row" id="users_list">
					<div class="col-md-4 mb-3">
						<select name="user_id" class="form-select form-select-sm">
<?php
		$optgroup = 0;
		echo "\t\t\t\t\t\t".'<option value="" selected disabled>Select an user</option>'."\n";
		foreach ($users_info as $cur_user)
		{
			if ($cur_user['group_id'] != $optgroup) {
				if ($optgroup) {
					echo '</optgroup>';
				}
				echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
				$optgroup = $cur_user['group_id'];
			}
			
			echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
		}
?>
						</select>
					</div>
				</div>
				<div class="row" id="group_list" style="display:none">
					<div class="col-md-4 mb-3">
						<select name="group_id" class="form-select form-select-sm">
<?php
		$query = array(
			'SELECT'	=> 'g.g_id, g.g_title',
			'FROM'		=> 'groups AS g',
			'WHERE'		=> 'g.g_id > 2',
			'ORDER BY'	=> 'g.g_title'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$groups_info = array();
		while ($cur_group = $DBLayer->fetch_assoc($result))
			$groups_info[] = $cur_group;
	
		$optgroup = 0;
		echo "\t\t\t\t\t\t".'<option value="" selected disabled>Select a group</option>'."\n";
		foreach ($groups_info as $cur_group)
		{
			echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.html_encode($cur_group['g_title']).'</option>'."\n";
		}
?>
						</select>
					</div>
				</div>
				<div class="mb-3">
					<button type="submit" name="create" class="btn btn-primary btn-sm">Submit</button>
				</div>
			</div>
		</div>
	</form>
<?php
	}


	function getGroupAccess()
	{
		global $DBLayer;

		$query = [
			'SELECT'	=> 'a.*, g.g_id, g.g_title',
			'FROM'		=> 'user_access AS a',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'groups AS g',
					'ON'			=> 'g.g_id=a.a_gid'
				],
			],
			'WHERE'		=> 'a.a_to=\''.$DBLayer->escape($this->rules_to).'\'',
			'ORDER BY'	=> 'g.g_title',
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$access_info = $access_for_groups = [];
		while ($row = $DBLayer->fetch_assoc($result)) {
			$access_info[] = $row;
		
			if (!isset($access_for_groups[$row['g_id']]))
				$access_for_groups[$row['g_id']] = $row['g_title'];
		}
		
		if (!empty($access_for_groups))
		{
?>
		<div class="card-header">
			<h6 class="card-title mb-0">List of groups who have access to pages</h6>
		</div>
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<table class="table table-sm table-striped">
				<thead>
					<tr>
						<th>Group name</th>
<?php
			foreach($this->access_options as $key => $title)
				echo '<th>'.$title.'</th>';
?>
						<th></th>
					</tr>
				</thead>
				<tbody>
<?php
			$num_options = count($this->access_options);
			foreach($access_for_groups as $gid => $username)
			{
				echo '<tr>';
				echo '<td><h6>'.html_encode($username).'</h6></td>';
		
				$i = 0;
				$output = [];
				foreach($this->access_options as $key => $title)
				{
					$cur_info = $this->get_group_access($access_info, $gid, $key);
		
					if (!empty($cur_info) && $cur_info['a_key'] == $key)
					{
						$output[] = '<input type="hidden" name="access_keys['.$gid.'][]" value="'.$key.'">';
		
						$checked = ($cur_info['a_value'] == 1) ? 'checked' : '';
						$output[] = '<td><div class="form-check form-switch"><input type="checkbox" class="form-check-input start-50" onchange="updateRules(1, '.$cur_info['id'].')" id="access_'.$cur_info['id'].'" '.$checked.'></div></td>';
		
						++$i;
					}
					else
						$output[] = '<td><div class="form-check form-switch"><input type="checkbox" class="form-check-input start-50 bg-secondary" disabled></div></td>';
				}

				echo implode("\n", $output);

				if ($this->checkExtraGroupKeys($access_info, 'a_key', $gid))
					echo '<td><button type="submit" name="fix_extra_group_access['.$gid.']" class="badge bg-success ms-1">Fix extra</button></td>';
				else if ($num_options > $i)
					echo '<td><button type="submit" name="fix_group_access['.$gid.']" class="badge bg-success ms-1">Fix missing</button></td>';
				else
					echo '<td><button type="submit" name="delete_group_access['.$gid.']" class="badge bg-danger float-end" onclick="return confirm(\'Are you sure you want to delete all permissions for this user?\')">Delete</button></td>';
		
				echo '</tr>';
			}
?>
				</tbody>
			</table>
		</form>
<?php
		}
	}


	function getUserAccess()
	{
		global $DBLayer, $URL;
		
		$query = [
			'SELECT'	=> 'a.*, u.group_id, u.realname',
			'FROM'		=> 'user_access AS a',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'u.id=a.a_uid'
				],
			],
			'WHERE'		=> 'a.a_to=\''.$DBLayer->escape($this->rules_to).'\'',
			'ORDER BY'	=> 'u.realname',
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$access_info = $user_ids = [];
		while ($row = $DBLayer->fetch_assoc($result)) {
			$access_info[] = $row;

			if (!isset($user_ids[$row['a_uid']]))
				$user_ids[$row['a_uid']] = $row['realname'];
		}

		if (!empty($user_ids))
		{
?>
		<div class="card-header">
			<h6 class="card-title mb-0">List of users who have access to pages</h6>
		</div>
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<table class="table table-sm table-striped">
				<thead>
					<tr>
						<th>Username</th>
						
<?php
			foreach($this->access_options as $key => $title)
				echo '<th>'.$title.'</th>';
?>
						<th></th>
					</tr>
				</thead>
				<tbody>
<?php
			$num_options = count($this->access_options);
			foreach($user_ids as $user_id => $username)
			{
				echo '<tr>';
				echo '<td><h6><a href="'.$URL->link('user', $user_id).'">'.html_encode($username).'</a></h6></td>';

				$i = 0;
				$output = [];
				foreach($this->access_options as $key => $title)
				{
					$cur_info = $this->get_user_access($access_info, $user_id, $key);

					if (!empty($cur_info) && $cur_info['a_key'] == $key)
					{
						$output[] = '<input type="hidden" name="access_keys['.$user_id.'][]" value="'.$key.'">';

						$checked = ($cur_info['a_value'] == 1) ? 'checked' : '';
						$output[] = '<td><div class="form-check form-switch"><input type="checkbox" class="form-check-input start-50" onchange="updateRules(1, '.$cur_info['id'].')" id="access_'.$cur_info['id'].'" '.$checked.'></div></td>';

						++$i;
					}
					else
						$output[] = '<td><div class="form-check form-switch"><input type="checkbox" class="form-check-input start-50 bg-secondary" disabled></div></td>';
				}

				echo implode("\n", $output);

				if ($this->checkExtraUserKeys($access_info, 'a_key', $user_id))
					echo '<td><button type="submit" name="fix_extra_access['.$user_id.']" class="badge bg-success ms-1">Fix extra</button></td>';
				else if ($num_options > $i)
					echo '<td><button type="submit" name="fix_missing_access['.$user_id.']" class="badge bg-success ms-1">Fix missing</button></td>';
				else
				{
					echo '<td><button type="submit" name="delete_access['.$user_id.']" class="badge bg-danger float-end" onclick="return confirm(\'Are you sure you want to delete all permissions for this user?\')">Delete</button></td>';
				}

				echo '</tr>';
			}
?>
				</tbody>
			</table>
		</form>
<?php
		}
	}

	// Returns TRUE if key not exists
	// 1- array of DB, 2 - 'a_key'
	function checkExtraUserKeys($access_info, $key, $uid)
	{
		foreach($access_info as $cur_info)
		{
			$keys = array_keys($this->access_options);
			if (isset($cur_info[$key]) && !in_array($cur_info[$key], $keys) && $uid == $cur_info['a_uid'])
				return true;
		}
		return false;
	}

	// Returns TRUE if key not exists
	// 1- array of DB, 2 - 'a_key'
	function checkExtraGroupKeys($access_info, $key, $gid)
	{
		foreach($access_info as $cur_info)
		{
			$keys = array_keys($this->access_options);
			if (isset($cur_info[$key]) && !in_array($cur_info[$key], $keys) && $gid == $cur_info['a_gid'])
				return true;
		}
		return false;
	}

	// Group Prms
	function getGroupPermissions()
	{
		global $DBLayer, $URL;
		
		$query = [
			'SELECT'	=> 'p.*, g.g_id, g.g_title',
			'FROM'		=> 'user_permissions AS p',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'groups AS g',
					'ON'			=> 'g.g_id=p.p_gid'
				],
			],
			'WHERE'		=> 'p.p_to=\''.$DBLayer->escape($this->rules_to).'\'',
			'ORDER BY'	=> 'g.g_title',
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$main_info = $group_ids = [];
		while ($row = $DBLayer->fetch_assoc($result)) {
			$main_info[] = $row;

			if (!isset($group_ids[$row['g_id']]))
				$group_ids[$row['g_id']] = $row['g_title'];
		}

		if (!empty($group_ids))
		{
?>
		<div class="card-header">
			<h6 class="card-title mb-0">Available Group Permissions</h6>
		</div>
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<table class="table table-sm table-striped">
				<thead>
					<tr>
						<th>Group name</th>
						
<?php
			foreach($this->permissions_options as $key => $title)
				echo '<th>'.$title.'</th>';
?>
						<th></th>
					</tr>
				</thead>
				<tbody>
<?php
			$num_options = count($this->permissions_options);
			foreach($group_ids as $id => $name)
			{
				echo '<tr>';
				echo '<td><h6><a href="'.$URL->link('user', $id).'">'.html_encode($name).'</a></h6></td>';

				$a = 0;
				foreach($this->permissions_options as $key => $title)
				{
					$cur_info = $this->get_group_permission($main_info, $id, $key);

					if (!empty($cur_info) && $cur_info['p_key'] == $key)
					{
						echo '<input type="hidden" name="permissions_keys['.$id.'][]" value="'.$key.'">';

						$checked = ($cur_info['p_value'] == 1) ? 'checked' : '';
						echo '<td><div class="form-check form-switch"><input type="checkbox" class="form-check-input start-50" onchange="updateRules(2, '.$cur_info['id'].')" id="permission_'.$cur_info['id'].'" '.$checked.'></div></td>';

						++$a;
					}
				}

				if ($num_options > $a)
					echo '<td><button type="submit" name="fix_group_permissions['.$id.']" class="badge bg-success ms-1">Update</button></td>';
				else
					echo '<td><button type="submit" name="delete_group_permissions['.$id.']" class="badge bg-danger float-end" onclick="return confirm(\'Are you sure you want to delete all permissions for this user?\')">Delete</button></td>';

				echo '</tr>';
			}
?>
				</tbody>
			</table>
		</form>
<?php
		}
	}



	function getUserPermissions()
	{
		global $DBLayer, $URL;
		
		$query = [
			'SELECT'	=> 'p.*, u.group_id, u.realname',
			'FROM'		=> 'user_permissions AS p',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'u.id=p.p_uid'
				],
			],
			'WHERE'		=> 'p.p_to=\''.$DBLayer->escape($this->rules_to).'\'',
			'ORDER BY'	=> 'u.realname',
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$main_info = $user_ids = [];
		while ($row = $DBLayer->fetch_assoc($result)) {
			$main_info[] = $row;

			if (!isset($user_ids[$row['p_uid']]))
				$user_ids[$row['p_uid']] = $row['realname'];
		}

		if (!empty($user_ids))
		{
?>
		<div class="card-header">
			<h6 class="card-title mb-0">Available user permissions</h6>
		</div>
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<table class="table table-sm table-striped">
				<thead>
					<tr>
						<th>Username</th>
						
<?php
			foreach($this->permissions_options as $key => $title)
				echo '<th>'.$title.'</th>';
?>
						<th></th>
					</tr>
				</thead>
				<tbody>
<?php
			$num_options = count($this->permissions_options);
			foreach($user_ids as $user_id => $username)
			{
				echo '<tr>';
				echo '<td><h6><a href="'.$URL->link('user', $user_id).'">'.html_encode($username).'</a></h6></td>';

				$a = 0;
				foreach($this->permissions_options as $key => $title)
				{
					$cur_info = $this->get_user_permission($main_info, $user_id, $key);

					if (!empty($cur_info) && $cur_info['p_key'] == $key)
					{
						echo '<input type="hidden" name="permissions_keys['.$user_id.'][]" value="'.$key.'">';

						$checked = ($cur_info['p_value'] == 1) ? 'checked' : '';
						echo '<td><div class="form-check form-switch"><input type="checkbox" class="form-check-input start-50" onchange="updateRules(2, '.$cur_info['id'].')" id="permission_'.$cur_info['id'].'" '.$checked.'></div></td>';

						++$a;
					}
				}

				if ($num_options > $a)
					echo '<td><button type="submit" name="fix_permissions['.$user_id.']" class="badge bg-success ms-1">Update</button></td>';
				else
					echo '<td><button type="submit" name="delete_permissions['.$user_id.']" class="badge bg-danger float-end" onclick="return confirm(\'Are you sure you want to delete all permissions for this user?\')">Delete</button></td>';

				echo '</tr>';
			}
?>
				</tbody>
			</table>
		</form>
<?php
		}
	}


	function getGroupNotifications()
	{
		global $DBLayer, $URL;

		$query = [
			'SELECT'	=> 'n.*, g.g_id, g.g_title',
			'FROM'		=> 'user_notifications AS n',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'groups AS g',
					'ON'			=> 'g.g_id=n.n_gid'
				],
			],
			'WHERE'		=> 'n.n_to=\''.$DBLayer->escape($this->rules_to).'\'',
			'ORDER BY'	=> 'g.g_title',
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$notifications_info = $notifications_for_groups = [];
		while ($row = $DBLayer->fetch_assoc($result)) {
			$notifications_info[] = $row;
		
			if (!isset($notifications_for_groups[$row['g_id']]))
				$notifications_for_groups[$row['g_id']] = $row['g_title'];
		}
		
		if (!empty($notifications_for_groups))
		{
?>
		<div class="card-header">
			<h6 class="card-title mb-0">Available Group notifications</h6>
		</div>
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<table class="table table-sm table-striped">
				<thead>
					<tr>
						<th>Group name</th>
<?php
			foreach($this->notifications_options as $key => $title)
				echo '<th>'.$title.'</th>';
?>
						<th></th>
					</tr>
				</thead>
				<tbody>
<?php
			$num_options = count($this->notifications_options);
			foreach($notifications_for_groups as $group_id => $name)
			{
				echo '<tr>';
				echo '<td><h6><a href="'.$URL->link('user', $group_id).'">'.html_encode($name).'</a></h6></td>';
		
				$n = 0;
				foreach($this->notifications_options as $key => $title)
				{
					$cur_info = $this->get_group_notification($notifications_info, $group_id, $key);
		
					if (!empty($cur_info) && $cur_info['n_key'] == $key)
					{
						echo '<input type="hidden" name="notifications_keys['.$group_id.'][]" value="'.$key.'">';
		
						$checked = ($cur_info['n_value'] == 1) ? 'checked' : '';
						echo '<td><div class="form-check form-switch"><input type="checkbox" class="form-check-input start-50" onchange="updateRules(3, '.$cur_info['id'].')" id="notification_'.$cur_info['id'].'" '.$checked.'></div></td>';
		
						++$n;
					}
				}
		
				if ($num_options > $n)
					echo '<td><button type="submit" name="fix_group_notifications['.$group_id.']" class="badge bg-success ms-1">Update</button></td>';
		
				echo '<td><button type="submit" name="delete_group_notifications['.$group_id.']" class="badge bg-danger float-end" onclick="return confirm(\'Are you sure you want to delete all notifications for this user?\')">Delete</button></td>';
		
				echo '</tr>';
			}
?>
				</tbody>
			</table>
		</form>
<?php
		}
	}

	function getUserNotifications()
	{
		global $DBLayer, $URL;

		$query = [
			'SELECT'	=> 'n.*, u.group_id, u.realname',
			'FROM'		=> 'user_notifications AS n',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'u.id=n.n_uid'
				],
			],
			'WHERE'		=> 'n.n_to=\''.$DBLayer->escape($this->rules_to).'\'',
			'ORDER BY'	=> 'u.realname',
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$notifications_info = $notifications_for_users = [];
		while ($row = $DBLayer->fetch_assoc($result)) {
			$notifications_info[] = $row;
		
			if (!isset($notifications_for_users[$row['n_uid']]))
				$notifications_for_users[$row['n_uid']] = $row['realname'];
		}
		
		if (!empty($notifications_for_users))
		{
		?>
		<div class="card-header">
			<h6 class="card-title mb-0">Available User notifications</h6>
		</div>
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<table class="table table-sm table-striped">
				<thead>
					<tr>
						<th>Username</th>
		<?php
			foreach($this->notifications_options as $key => $title)
				echo '<th>'.$title.'</th>';
		?>
						<th></th>
					</tr>
				</thead>
				<tbody>
		<?php
			$num_options = count($this->notifications_options);
			foreach($notifications_for_users as $user_id => $username)
			{
				echo '<tr>';
				echo '<td><h6><a href="'.$URL->link('user', $user_id).'">'.html_encode($username).'</a></h6></td>';
		
				$n = 0;
				foreach($this->notifications_options as $key => $title)
				{
					$cur_info = $this->get_user_notification($notifications_info, $user_id, $key);
		
					if (!empty($cur_info) && $cur_info['n_key'] == $key)
					{
						echo '<input type="hidden" name="notifications_keys['.$user_id.'][]" value="'.$key.'">';
		
						$checked = ($cur_info['n_value'] == 1) ? 'checked' : '';
						echo '<td><div class="form-check form-switch"><input type="checkbox" class="form-check-input start-50" onchange="updateRules(3, '.$cur_info['id'].')" id="notification_'.$cur_info['id'].'" '.$checked.'></div></td>';
		
						++$n;
					}
				}
		
				if ($num_options > $n)
					echo '<td><button type="submit" name="fix_notifications['.$user_id.']" class="badge bg-success ms-1">Update</button></td>';
		
				echo '<td><button type="submit" name="delete_notifications['.$user_id.']" class="badge bg-danger float-end" onclick="return confirm(\'Are you sure you want to delete all notifications for this user?\')">Delete</button></td>';
		
				echo '</tr>';
			}
		?>
				</tbody>
			</table>
		</form>
		<?php
		}
	}

	function getJS()
	{
		global $URL;

?>
		<div class="toast-container" id="toast_container"></div>

		<script>
		function switchSection(id){
			if (id == 1){
				$('#users_list').css('display', 'block');
				$('#group_list').css('display', 'none');
			}else{
				$('#users_list').css('display', 'none');
				$('#group_list').css('display', 'block');
			}
		}
		// Move this function in CORE & use it for json updates
		function showToastMessage()
		{
			const toastLiveExample = document.getElementById('liveToast');
			const toast = new bootstrap.Toast(toastLiveExample);
			toast.show();
		}
		function updateRules(type,id){
			var val = 0;

			if (type == 1){
				if($('#access_'+id).prop("checked") == true){val = 1;}
				else if($('#access_'+id).prop("checked") == false){val = 0;}
			}else if(type == 2){
				if($('#permission_'+id).prop("checked") == true){val = 1;}
				else if($('#permission_'+id).prop("checked") == false){val = 0;}
			}else if(type == 3){
				if($('#notification_'+id).prop("checked") == true){val = 1;}
				else if($('#notification_'+id).prop("checked") == false){val = 0;}
			}

			var csrf_token = "<?php echo generate_form_token($URL->link('inc_ajax_update_apn')) ?>";
			jQuery.ajax({
				url:	"<?php echo $URL->link('inc_ajax_update_apn') ?>",
				type:	"POST",
				dataType: "json",
				cache: false,
				data: ({type:type,id:id,val:val,csrf_token:csrf_token}),
				success: function(re){
					$("#toast_container").empty().html(re.toast_message);
					showToastMessage();
				},
				error: function(re){
					var msg = '<div id="liveToast" class="toast position-fixed bottom-0 end-0 m-2" role="alert" aria-live="assertive" aria-atomic="true">';
					msg += '<div class="toast-header toast-danger">';
					msg += '<strong class="me-auto">Error</strong>';
					msg += '</div>';
					msg += '<div class="toast-body toast-danger">Failed to update settings.</div>';
					msg += '</div>';
					$("#toast_container").empty().html(msg);
					showToastMessage();
				}
			});	
		}
		</script>

<?php
	}

	// Helpers
	function get_group_access($info, $gid, $key)
	{
		$output = [];
		foreach($info as $cur_info)
		{
			if ($cur_info['a_gid'] == $gid && $cur_info['a_key'] == $key)
				$output = $cur_info;
		}
		return $output;
	}
	function get_user_access($info, $user_id, $key)
	{
		$output = [];
		foreach($info as $cur_info)
		{
			if ($cur_info['a_uid'] == $user_id && $cur_info['a_key'] == $key)
				$output = $cur_info;
		}
		return $output;
	}
	
	function get_group_permission($info, $gid, $key){
		foreach($info as $cur_info)
		{
			if ($cur_info['p_gid'] == $gid && $cur_info['p_key'] == $key)
				return $cur_info;
		}
	}
	function get_user_permission($info, $user_id, $key){
		foreach($info as $cur_info)
		{
			if ($cur_info['p_uid'] == $user_id && $cur_info['p_key'] == $key)
				return $cur_info;
		}
	}

	function get_group_notification($info, $gid, $key)
	{
		$output = [];
		foreach($info as $cur_info)
		{
			if ($cur_info['n_gid'] == $gid && $cur_info['n_key'] == $key)
				$output = $cur_info;
		}
		return $output;
	}
	function get_user_notification($info, $user_id, $key)
	{
		$output = [];
		foreach($info as $cur_info)
		{
			if ($cur_info['n_uid'] == $user_id && $cur_info['n_key'] == $key)
				$output = $cur_info;
		}
		return $output;
	}
}
