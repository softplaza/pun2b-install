<?php

define('SITE_ROOT', '../');
require SITE_ROOT.'include/common.php';

if (!defined('SPM_XML_FUNCTIONS_LOADED'))
	require SITE_ROOT.'include/xml.php';

if (!$User->is_admin())
	message($lang_common['No permission']);

// Load the admin.php language file
require SITE_ROOT.'lang/'.$User->get('language').'/admin_common.php';
require SITE_ROOT.'lang/'.$User->get('language').'/admin_ext.php';

// Make sure we have XML support
if (!function_exists('xml_parser_create'))
	message($lang_admin_ext['No XML support']);

$section = isset($_GET['section']) ? $_GET['section'] : null;

// Install an extension
if (isset($_GET['install']))
{
	// User pressed the cancel button
	if (isset($_POST['install_cancel']))
		redirect(isset($_GET['install']) ? $URL->link('admin_extensions_manage') : $URL->link('admin_extensions_hotfixes'), $lang_admin_common['Cancel redirect']);

	$id = preg_replace('/[^0-9a-z_]/', '', isset($_GET['install']) ? $_GET['install'] : $_GET['install_hotfix']);

	// Load manifest (either locally or from punbb.informer.com updates service)
	if (isset($_GET['install']))
		$manifest = is_readable(SITE_ROOT.'extensions/'.$id.'/manifest.xml') ? file_get_contents(SITE_ROOT.'extensions/'.$id.'/manifest.xml') : false;
	else
	{
		$remote_file = get_remote_file('http://punbb.informer.com/update/manifest/'.$id.'.xml', 16);
		if (!empty($remote_file['content']))
			$manifest = $remote_file['content'];
	}

	// Parse manifest.xml into an array and validate it
	$ext_data = xml_to_array($manifest);
	$errors = validate_manifest($ext_data, $id);

	/*
	 * Errors must be fully specified instead "bad request" message only
	 */
	if (!empty($errors)) {
		foreach ($errors as $i => $cur_error) {
			$errors[$i] = '<li class="warn"><span>' . $cur_error . '</span></li>';
		}
		$msg_errors =
			'<div class="ct-box error-box"><h2 class="warn hn">'
				. $lang_admin_ext['Install ext errors']
				. '<ul class="error-list">'
					. implode("\n", $errors)
				. '</ul>'
			. '</div>';
		message(isset($_GET['install'])? $msg_errors : $lang_admin_ext['Hotfix download failed']);
	}

	// Get core amd major versions
	if (!defined('SPM_DISABLE_EXTENSIONS_VERSION_CHECK'))
	{
		list($core_version, $major_version) = explode('.', clean_version($Config->get('o_cur_version')));
		list($extension_maxtestedon_version_core, $extension_maxtestedon_version_major) = explode('.', clean_version($ext_data['extension']['maxtestedon']));

		if (version_compare($core_version.'.'.$major_version, $extension_maxtestedon_version_core.'.'.$extension_maxtestedon_version_major, '>'))
			message($lang_admin_ext['Maxtestedon error']);
	}

	// Make sure we have an array of dependencies
	if (!isset($ext_data['extension']['dependencies']['dependency']))
		$ext_data['extension']['dependencies'] = array();
	else if (!is_array(current($ext_data['extension']['dependencies'])))
		$ext_data['extension']['dependencies'] = array($ext_data['extension']['dependencies']['dependency']);
	else
		$ext_data['extension']['dependencies'] = $ext_data['extension']['dependencies']['dependency'];

	$query = array(
		'SELECT'	=> 'e.id, e.version',
		'FROM'		=> 'extensions AS e',
		'WHERE'		=> 'e.disabled=0'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

	$installed_ext = array();
	while ($row = $DBLayer->fetch_assoc($result))
		$installed_ext[$row['id']] = $row;

	foreach ($ext_data['extension']['dependencies'] as $dependency)
	{

		$ext_dependancy_id = is_array($dependency) ? $dependency['content'] : $dependency;

	    if (!array_key_exists($ext_dependancy_id, $installed_ext))
	    {
		   $Core->add_error(sprintf($lang_admin_ext['Missing dependency'], $ext_dependancy_id));
	    }
	    else if (is_array($dependency) && isset($dependency['attributes']['minversion']) && version_compare($dependency['attributes']['minversion'], $installed_ext[$ext_dependancy_id]['version']) > 0)
	    {
	    	$Core->add_error(sprintf($lang_admin_ext['Version dependency error'], $dependency['content'], $dependency['attributes']['minversion']));
	    }
	}

	if (isset($_POST['install_comply']) && empty($Core->errors))
	{
		// $ext_info contains some information about the extension being installed
		$ext_info = array(
			'id'			=> $id,
			'path'			=> SITE_ROOT.'extensions/'.$id,
			'url'			=> BASE_URL.'/extensions/'.$id,
			'dependencies'	=> array()
		);

		foreach ($ext_data['extension']['dependencies'] as $dependency)
		{
			$ext_info['dependencies'][$dependency] = array(
				'id'	=> $dependency,
				'path'	=> SITE_ROOT.'extensions/'.$dependency,
				'url'	=> BASE_URL.'/extensions/'.$dependency,
			);
		}

		// Is there some uninstall code to store in the db?
		$uninstall_code = (isset($ext_data['extension']['uninstall']) && swift_trim($ext_data['extension']['uninstall']) != '') ? '\''.$DBLayer->escape(swift_trim($ext_data['extension']['uninstall'])).'\'' : 'NULL';

		// Is there an uninstall note to store in the db?
		$uninstall_note = 'NULL';
		foreach ($ext_data['extension']['note'] as $cur_note)
		{
			if ($cur_note['attributes']['type'] == 'uninstall' && swift_trim($cur_note['content']) != '')
				$uninstall_note = '\''.$DBLayer->escape(swift_trim($cur_note['content'])).'\'';
		}

		$notices = array();

		// Is this a fresh install or an upgrade?
		$query = array(
			'SELECT'	=> 'e.version',
			'FROM'		=> 'extensions AS e',
			'WHERE'		=> 'e.id=\''.$DBLayer->escape($id).'\''
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$ext_version = $DBLayer->result($result);

		if (!is_null($ext_version) && $ext_version !== false)
		{
			// EXT_CUR_VERSION will be available to the extension install routine (to facilitate extension upgrades)
			define('EXT_CUR_VERSION', $ext_version);

			// Run the author supplied install code
			if (isset($ext_data['extension']['install']) && swift_trim($ext_data['extension']['install']) != '')
				eval($ext_data['extension']['install']);

			// Update the existing extension
			$query = array(
				'UPDATE'	=> 'extensions',
				'SET'		=> 'title=\''.$DBLayer->escape($ext_data['extension']['title']).'\', version=\''.$DBLayer->escape($ext_data['extension']['version']).'\', description=\''.$DBLayer->escape($ext_data['extension']['description']).'\', author=\''.$DBLayer->escape($ext_data['extension']['author']).'\', uninstall='.$uninstall_code.', uninstall_note='.$uninstall_note.', dependencies=\'|'.implode('|', $ext_data['extension']['dependencies']).'|\'',
				'WHERE'		=> 'id=\''.$DBLayer->escape($id).'\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);

			// Delete the old hooks
			$query = array(
				'DELETE'	=> 'extension_hooks',
				'WHERE'		=> 'extension_id=\''.$DBLayer->escape($id).'\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			// Add flash message
			$flash_message = $id.' Extension updated';
		}
		else
		{
			// Run the author supplied install code
			if (isset($ext_data['extension']['install']) && swift_trim($ext_data['extension']['install']) != '')
				eval($ext_data['extension']['install']);

			// Add the new extension
			$query = array(
				'INSERT'	=> 'id, title, version, description, author, uninstall, uninstall_note, dependencies',
				'INTO'		=> 'extensions',
				'VALUES'	=> '\''.$DBLayer->escape($ext_data['extension']['id']).'\', \''.$DBLayer->escape($ext_data['extension']['title']).'\', \''.$DBLayer->escape($ext_data['extension']['version']).'\', \''.$DBLayer->escape($ext_data['extension']['description']).'\', \''.$DBLayer->escape($ext_data['extension']['author']).'\', '.$uninstall_code.', '.$uninstall_note.', \'|'.implode('|', $ext_data['extension']['dependencies']).'|\'',
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			// Add flash message
			$flash_message = $id.' '.$lang_admin_ext['Extension installed'];
		}

		// Now insert the hooks
		if (isset($ext_data['extension']['hooks']['hook']))
		{
			foreach ($ext_data['extension']['hooks']['hook'] as $ext_hook)
			{
				$cur_hooks = explode(',', $ext_hook['attributes']['id']);
				foreach ($cur_hooks as $cur_hook)
				{
					$query = array(
						'INSERT'	=> 'id, extension_id, code, installed, priority',
						'INTO'		=> 'extension_hooks',
						'VALUES'	=> '\''.$DBLayer->escape(swift_trim($cur_hook)).'\', \''.$DBLayer->escape($id).'\', \''.$DBLayer->escape(swift_trim($ext_hook['content'])).'\', '.time().', '.(isset($ext_hook['attributes']['priority']) ? $ext_hook['attributes']['priority'] : 5)
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				}
			}
		}

		// Empty the PHP cache
		$Cachinger->clear();

		// Regenerate the hooks cache
		$Cachinger->gen_hooks();

		// Display notices if there are any
		if (!empty($notices))
		{
			$Core->set_page_title(html_encode($ext_data['extension']['title']));
			$Core->set_page_id('admin_extensions_manage', 'management');
			require SITE_ROOT.'header.php';

?>
	<div class="main-content main-frm">
		<div class="ct-box info-box">
			<p><?php echo $lang_admin_ext['Extension installed info'] ?></p>
			<ul class="data-list">
<?php

			foreach ($notices as $cur_notice)
				echo "\t\t\t\t".'<li><span>'.$cur_notice.'</span></li>'."\n";

?>
			</ul>
			<p><a href="<?php echo $URL->link('admin_extensions_manage') ?>"><?php echo $lang_admin_common['Manage extensions'] ?></a></p>
		</div>
	</div>
<?php
			require SITE_ROOT.'footer.php';
		}
		else
		{
			$FlashMessenger->add_info($flash_message);
			redirect($URL->link('admin_extensions_manage'), $flash_message);
		}
	}

	$Core->set_page_title($ext_data['extension']['title']);
	$Core->set_page_id('admin_extensions_manage', 'management');
	require SITE_ROOT.'header.php';
?>
	<div class="main-content main-frm">
		<form method="post" accept-charset="utf-8" action="<?php echo BASE_URL.'/admin/extensions.php'.(isset($_GET['install']) ? '?install=' : '?install_hotfix=').$id ?>">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(BASE_URL.'/admin/extensions.php'.(isset($_GET['install']) ? '?install=' : '?install_hotfix=').$id) ?>" />
			<div class="ct-group data-group">
				<div class="ct-set data-set set1">
					<div class="ct-box data-box">
						<h6 class="ct-legend hn"><span><?php echo html_encode($ext_data['extension']['title']) ?></span></h6>
						<p><?php echo ((strpos($id, 'hotfix_') !== 0) ? sprintf($lang_admin_ext['Version'], $ext_data['extension']['version']) : $lang_admin_ext['Hotfix']) ?></p>
						<p><?php printf($lang_admin_ext['Extension by'], html_encode($ext_data['extension']['author'])) ?></p>
						<p><?php echo html_encode($ext_data['extension']['description']) ?></p>
					</div>
				</div>
			</div>
<?php

	// Setup an array of warnings to display in the form
	$form_warnings = array();
	$page_param['num_items'] = 0;

	foreach ($ext_data['extension']['note'] as $cur_note)
	{
		if ($cur_note['attributes']['type'] == 'install')
			$form_warnings[] = '<li>'.html_encode($cur_note['content']).'</li>';
	}

	if (version_compare(clean_version($Config->get('o_cur_version')), clean_version($ext_data['extension']['maxtestedon']), '>'))
		$form_warnings[] = '<li>'.$lang_admin_ext['Maxtestedon warning'].'</li>';

	if (!empty($form_warnings))
	{

?>			<div class="ct-box warn-box">
				<p class="important"><strong><?php echo $lang_admin_ext['Install note'] ?></strong></p>
				<ol class="info-list">
<?php

		echo implode("\n\t\t\t\t\t", $form_warnings)."\n";

?>
				</ol>
			</div>
<?php

	}

?>			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="install_comply" value="<?php echo ((strpos($id, 'hotfix_') !== 0) ? $lang_admin_ext['Install extension'] : $lang_admin_ext['Install hotfix']) ?>" /></span>
				<span class="cancel"><input type="submit" name="install_cancel" value="<?php echo $lang_admin_common['Cancel'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	require SITE_ROOT.'footer.php';
}

// Uninstall an extension
else if (isset($_GET['uninstall']))
{
	// User pressed the cancel button
	if (isset($_POST['uninstall_cancel']))
		redirect($URL->link('admin_extensions_manage'), $lang_admin_common['Cancel redirect']);

	$id = preg_replace('/[^0-9a-z_]/', '', $_GET['uninstall']);

	// Fetch info about the extension
	$query = array(
		'SELECT'	=> 'e.title, e.version, e.description, e.author, e.uninstall, e.uninstall_note',
		'FROM'		=> 'extensions AS e',
		'WHERE'		=> 'e.id=\''.$DBLayer->escape($id).'\''
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$ext_data = $DBLayer->fetch_assoc($result);

	if (!$ext_data)
	{
		message($lang_common['Bad request']);
	}

	// Check dependancies
	$query = array(
		'SELECT'	=> 'e.id',
		'FROM'		=> 'extensions AS e',
		'WHERE'		=> 'e.dependencies LIKE \'%|'.$DBLayer->escape($id).'|%\''
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$dependency = $DBLayer->fetch_assoc($result);

	if (!is_null($dependency) && $dependency !== false)
	{
		message(sprintf($lang_admin_ext['Uninstall dependency'], $dependency['id']));
	}

	// If the user has confirmed the uninstall
	if (isset($_POST['uninstall_comply']))
	{
		$ext_info = array(
			'id'			=> $id,
			'path'			=> SITE_ROOT.'extensions/'.$id,
			'url'			=> BASE_URL.'/extensions/'.$id
		);

		$notices = array();

		// Run uninstall code
		eval($ext_data['uninstall']);

		// Now delete the extension and its hooks from the db
		$query = array(
			'DELETE'	=> 'extension_hooks',
			'WHERE'		=> 'extension_id=\''.$DBLayer->escape($id).'\''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$query = array(
			'DELETE'	=> 'extensions',
			'WHERE'		=> 'id=\''.$DBLayer->escape($id).'\''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Empty the PHP cache
		$Cachinger->clear();

		// Regenerate the hooks cache
		$Cachinger->gen_hooks();

		// Display notices if there are any
		if (!empty($notices))
		{
			$Core->set_page_title(html_encode($ext_data['title']));
			$Core->set_page_id('admin_extensions_manage', 'management');
			require SITE_ROOT.'header.php';
?>
	<div class="main-content main-frm">
		<div class="ct-box info-box">
			<p><?php echo $lang_admin_ext['Extension uninstalled info'] ?></p>
			<ul class="info-list">
<?php

			foreach ($notices as $cur_notice)
				echo "\t\t\t\t".'<li><span>'.$cur_notice.'</span></li>'."\n";

?>
			</ul>
			<p><a href="<?php echo $URL->link('admin_extensions_manage') ?>"><?php echo $lang_admin_common['Manage extensions'] ?></a></p>
		</div>
	</div>
<?php
			require SITE_ROOT.'footer.php';
		}
		else
		{
			// Add flash message
			$flash_message = html_encode($ext_data['title']).' '.$lang_admin_ext['Extension uninstalled'];
			$FlashMessenger->add_info($flash_message);

			redirect($URL->link('admin_extensions_manage'), $flash_message);
		}
	}
	else	// If the user hasn't confirmed the uninstall
	{
		$Core->set_page_title(html_encode($ext_data['title']));
		$Core->set_page_id('admin_extensions_manage', 'management');
		require SITE_ROOT.'header.php';
?>
	<div class="main-content main-frm">
		<form method="post" accept-charset="utf-8" action="<?php echo BASE_URL ?>/admin/extensions.php?section=manage&amp;uninstall=<?php echo $id ?>">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(BASE_URL.'/admin/extensions.php?section=manage&amp;uninstall='.$id) ?>" />
			<div class="ct-group data-group">
				<div class="ct-set data-set set1">
					<div class="ct-box data-box">
						<h6 class="ct-legend hn"><span><?php echo html_encode($ext_data['title']) ?></span></h6>
						<p><?php echo ((strpos($id, 'hotfix_') !== 0) ? sprintf($lang_admin_ext['Version'], $ext_data['version']) : $lang_admin_ext['Hotfix']) ?></p>
						<p><?php printf($lang_admin_ext['Extension by'], html_encode($ext_data['author'])) ?></p>
						<p><?php echo html_encode($ext_data['description']) ?></p>
					</div>
				</div>
			</div>
<?php if ($ext_data['uninstall_note'] != ''): ?>
			<div class="ct-box warn-box">
				<p class="important"><strong><?php echo $lang_admin_ext['Uninstall note'] ?></strong></p>
				<p><?php echo html_encode($ext_data['uninstall_note']) ?></p>
			</div>
<?php endif; ?>
<?php if (strpos($id, 'hotfix_') !== 0): ?>
			<div class="ct-box warn-box">
				<p class="warn"><?php echo $lang_admin_ext['Installed extensions warn'] ?></p>
			</div>
<?php endif; ?>
			<div class="frm-buttons">
				<span class="submit primary caution"><input type="submit" name="uninstall_comply" value="<?php echo $lang_admin_ext['Uninstall'] ?>" /></span>
				<span class="cancel"><input type="submit" name="uninstall_cancel" value="<?php echo $lang_admin_common['Cancel'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

		require SITE_ROOT.'footer.php';
	}
}


// Enable or disable an extension
else if (isset($_GET['flip']))
{
	$id = preg_replace('/[^0-9a-z_]/', '', $_GET['flip']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('flip'.$id)))
		csrf_confirm_form();

	// Fetch the current status of the extension
	$query = array(
		'SELECT'	=> 'e.disabled',
		'FROM'		=> 'extensions AS e',
		'WHERE'		=> 'e.id=\''.$DBLayer->escape($id).'\''
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$ext_status = $DBLayer->result($result);

	// No rows
	if (is_null($ext_status) || $ext_status === false)
	{
		message($lang_common['Bad request']);
	}

	// Are we disabling or enabling?
	$disable = $ext_status == '0';

	// Check dependancies
	if ($disable)
	{
		$query = array(
			'SELECT'	=> 'e.id',
			'FROM'		=> 'extensions AS e',
			'WHERE'		=> 'e.disabled=0 AND e.dependencies LIKE \'%|'.$DBLayer->escape($id).'|%\''
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$dependency = $DBLayer->fetch_assoc($result);

		if (!is_null($dependency) && $dependency !== false)
		{
			message(sprintf($lang_admin_ext['Disable dependency'], $dependency['id']));
		}
	}
	else
	{
		$query = array(
			'SELECT'	=> 'e.dependencies',
			'FROM'		=> 'extensions AS e',
			'WHERE'		=> 'e.id=\''.$DBLayer->escape($id).'\''
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$dependencies = $DBLayer->fetch_assoc($result);
		$dependencies = explode('|', substr($dependencies['dependencies'], 1, -1));

		$query = array(
			'SELECT'	=> 'e.id',
			'FROM'		=> 'extensions AS e',
			'WHERE'		=> 'e.disabled=0'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$installed_ext = array();
		while ($row = $DBLayer->fetch_assoc($result))
			$installed_ext[] = $row['id'];

		foreach ($dependencies as $dependency)
		{
			if (!empty($dependency) && !in_array($dependency, $installed_ext))
				message(sprintf($lang_admin_ext['Disabled dependency'], $dependency));
		}
	}

	$query = array(
		'UPDATE'	=> 'extensions',
		'SET'		=> 'disabled='.($disable ? '1' : '0'),
		'WHERE'		=> 'id=\''.$DBLayer->escape($id).'\''
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Regenerate the hooks cache
	$Cachinger->gen_hooks();

	// Add flash message
	$flash_message = $id.' '.($disable ? $lang_admin_ext['Extension disabled'] : $lang_admin_ext['Extension enabled']);
	$FlashMessenger->add_info($flash_message);
	//$FlashMessenger->add_info(($disable ? $lang_admin_ext['Extension disabled'] : $lang_admin_ext['Extension enabled']));
	
	redirect($URL->link('admin_extensions_manage'), $flash_message);
}

// Generate an array of installed extensions
$inst_exts = array();
$query = array(
	'SELECT'	=> 'e.*',
	'FROM'		=> 'extensions AS e',
	'ORDER BY'	=> 'e.title'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
while ($cur_ext = $DBLayer->fetch_assoc($result))
	$inst_exts[$cur_ext['id']] = $cur_ext;

if ($Config->get('o_check_for_versions') == 1)
{
	// Check for the new versions of the extensions istalled
	$repository_urls = array(SPM_SPM_REPOSITORY_URL);

	$repository_url_by_extension = array();
	//foreach (array_keys($inst_exts) as $id)

	if (is_readable(SPM_CACHE_DIR.'cache_ext_version_notifications.php'))
		include SPM_CACHE_DIR.'cache_ext_version_notifications.php';

	// Get latest timestamp in cache
	if (isset($repository_extensions))
	{
		$min_timestamp = 10000000000;
		foreach ($repository_extensions as $rep)
			$min_timestamp = min($min_timestamp, $rep['timestamp']);
	}

	$update_hour = (isset($ext_versions_update_cache) && (time() - $ext_versions_update_cache > 60 * 60));

	// Update last versions if there is no cahe or some extension was added/removed or one day has gone since last update
	$update_new_versions_cache = !defined('SPM_EXT_VERSIONS_LOADED') || (isset($ext_last_versions) && array_diff(array_keys($inst_exts), array_keys($ext_last_versions)) != array()) || $update_hour || ($update_hour && isset($min_timestamp) && (time() - $min_timestamp > 60*60*24));

	if ($update_new_versions_cache)
	{
		$Cachinger->generate_ext_versions_cache($inst_exts, $repository_urls, $repository_url_by_extension);
		include SPM_CACHE_DIR.'cache_ext_version_notifications.php';
	}
}

$Core->set_page_title($lang_admin_ext['Extensions available']);
$Core->set_page_id('admin_extensions_manage', 'management');
require SITE_ROOT.'header.php';

?>
	<div class="main-content main-extensions">
<?php

$num_exts = 0;
$num_failed = 0;
$page_param['item_num'] = 1;
$page_param['ext_item'] = array();
$page_param['ext_error'] = array();

$d = dir(SITE_ROOT.'extensions');
while (($entry = $d->read()) !== false)
{
	if ($entry[0] != '.' && is_dir(SITE_ROOT.'extensions/'.$entry))
	{
		if (preg_match('/[^0-9a-z_]/', $entry))
		{
			$page_param['ext_error'][] = '<div class="ext-error databox db'.++$page_param['item_num'].'">'."\n\t\t\t\t".'<h6 class="legend"><span>'.sprintf($lang_admin_ext['Extension loading error'], html_encode($entry)).'</span></h6>'."\n\t\t\t\t".'<p>'.$lang_admin_ext['Illegal ID'].'</p>'."\n\t\t\t".'</div>';
			++$num_failed;
			continue;
		}
		else if (!file_exists(SITE_ROOT.'extensions/'.$entry.'/manifest.xml'))
		{
			$page_param['ext_error'][] = '<div class="ext-error databox db'.++$page_param['item_num'].'">'."\n\t\t\t\t".'<h6 class="legend"><span>'.sprintf($lang_admin_ext['Extension loading error'], html_encode($entry)).'<span></h6>'."\n\t\t\t\t".'<p>'.$lang_admin_ext['Missing manifest'].'</p>'."\n\t\t\t".'</div>';
			++$num_failed;
			continue;
		}

		// Parse manifest.xml into an array
		$ext_data = is_readable(SITE_ROOT.'extensions/'.$entry.'/manifest.xml') ? xml_to_array(file_get_contents(SITE_ROOT.'extensions/'.$entry.'/manifest.xml')) : '';
		if (empty($ext_data))
		{
			$page_param['ext_error'][] = '<div class="ext-error databox db'.++$page_param['item_num'].'">'."\n\t\t\t\t".'<h6 class="legend"><span>'.sprintf($lang_admin_ext['Extension loading error'], html_encode($entry)).'<span></h6>'."\n\t\t\t\t".'<p>'.$lang_admin_ext['Failed parse manifest'].'</p>'."\n\t\t\t".'</div>';
			++$num_failed;
			continue;
		}

		// Validate manifest
		$errors = validate_manifest($ext_data, $entry);
		if (!empty($errors))
		{
			foreach ($errors as $i => $cur_error) {
				$errors[$i] = '<li class="warn"><span>' . $cur_error . '</span></li>';
			}
			$page_param['ext_error'][] =
				'<div class="ext-error databox db' . ++$page_param['item_num'] . '">'
					. "\n\t\t\t\t"
					. '<h6 class="legend"><span>' . sprintf($lang_admin_ext['Extension loading error'], html_encode($entry)) . '</span></h6>'
					. "\n\t\t\t\t"
					. '<p><ul class="error-list">' . implode(' ', $errors) . '</ul></p>'
					. "\n\t\t\t"
				. '</div>';
			++$num_failed;
		}
		else
		{
			if (!array_key_exists($entry, $inst_exts) || version_compare($inst_exts[$entry]['version'], $ext_data['extension']['version'], '!='))
			{
				$page_param['ext_item'][] = '<div class="ct-box info-box available '.(isset($inst_exts[$entry]['version']) ? '' : 'extension').'">'."\n\t\t\t".'<h6 class="ct-legend hn">'.html_encode($ext_data['extension']['title']).' <em>'.$ext_data['extension']['version'].'</em></h6>'."\n\t\t\t".'<ul class="data-list">'."\n\t\t\t\t".'<li><span>'.sprintf($lang_admin_ext['Extension by'], html_encode($ext_data['extension']['author'])).'</span></li>'.(($ext_data['extension']['description'] != '') ? "\n\t\t\t\t".'<li><span>'.html_encode($ext_data['extension']['description']).'</span></li>' : '')."\n\t\t\t".'</ul>'."\n\t\t\t".'<p class="options"><span class="first-item"><a href="'.BASE_URL.'/admin/extensions.php?install='.urlencode($entry).'">'.(isset($inst_exts[$entry]['version']) ? $lang_admin_ext['Upgrade extension'] : $lang_admin_ext['Install extension']).'</a></span></p>'."\n\t\t".'</div>';
				++$num_exts;
			}
		}
	}
}
$d->close();

if ($num_exts)
	echo "\t\t".implode("\n\t\t", $page_param['ext_item'])."\n";
else
{
?>
		<div class="ct-box info-box">
			<p><?php echo $lang_admin_ext['No available extensions'] ?></p>
		</div>
<?php
}

// If any of the extensions had errors
if ($num_failed)
{
?>
		<div class="ct-box data-box">
			<p class="important"><?php echo $lang_admin_ext['Invalid extensions'] ?></p>
			<?php echo implode("\n\t\t\t", $page_param['ext_error'])."\n" ?>
		</div>
<?php
}
?>
	</div>

	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_admin_ext['Installed extensions'] ?></span></h2>
	</div>
	<div class="main-content main-extensions">
<?php

$installed_count = 0;
$page_param['ext_item'] = array();
foreach ($inst_exts as $id => $ext)
{
	if (strpos($id, 'hotfix_') === 0)
		continue;

	$page_param['ext_actions'] = array(
		'flip'		=> '<span class="first-item"><a href="'.BASE_URL.'/admin/extensions.php?section=manage&amp;flip='.$id.'&amp;csrf_token='.generate_form_token('flip'.$id).'">'.($ext['disabled'] != '1' ? $lang_admin_ext['Disable'] : $lang_admin_ext['Enable']).'</a></span>',
		'uninstall'	=> '<span><a href="'.BASE_URL.'/admin/extensions.php?section=manage&amp;uninstall='.$id.'">'.$lang_admin_ext['Uninstall'].'</a></span>'
	);

	if ($Config->get('o_check_for_versions') == 1 && isset($ext_last_versions[$id]) && version_compare($ext['version'], $ext_last_versions[$id]['version'], '<'))
		$page_param['ext_actions']['latest_ver'] = '<span><a href="'.$ext_last_versions[$id]['repo_url'].'/'.$id.'/'.$id.'.zip">'.$lang_admin_ext['Download latest version'].'</a></span>';

	if ($ext['disabled'] == '1')
		$page_param['ext_item'][] = '<div class="ct-box info-box extension disabled">'."\n\t\t".'<h6 class="ct-legend hn">'.html_encode($ext['title']).' <em>'.$ext['version'].'</em> ('.$lang_admin_ext['Extension disabled'].')</h6>'."\n\t\t".'<ul class="data-list">'."\n\t\t\t".'<li><span>'.sprintf($lang_admin_ext['Extension by'], html_encode($ext['author'])).'</span></li>'."\n\t\t\t".(($ext['description'] != '') ? '<li><span>'.html_encode($ext['description']).'</span></li>' : '')."\n\t\t\t".'</ul>'."\n\t\t".'<p class="options">'.implode(' ', $page_param['ext_actions']).'</p>'."\n\t".'</div>';
	else
		$page_param['ext_item'][] = '<div class="ct-box info-box extension enabled">'."\n\t\t".'<h6 class="ct-legend hn">'.html_encode($ext['title']).' <em>'.$ext['version'].'</em></h6>'."\n\t\t".'<ul class="data-list">'."\n\t\t\t".'<li><span>'.sprintf($lang_admin_ext['Extension by'], html_encode($ext['author'])).'</span></li>'."\n\t\t\t".(($ext['description'] != '') ? '<li><span>'.html_encode($ext['description']).'</span></li>' : '')."\n\t\t".'</ul>'."\n\t\t".'<p class="options">'.implode(' ', $page_param['ext_actions']).'</p>'."\n\t".'</div>';

	$installed_count++;
}

if ($installed_count > 0)
{
	echo "\t".implode("\n\t", $page_param['ext_item'])."\n";
}
else
{
?>
		<div class="ct-box info-box">
			<p><?php echo $lang_admin_ext['No installed extensions'] ?></p>
		</div>
<?php
}
?>
	</div>
<?php

require SITE_ROOT.'footer.php';
