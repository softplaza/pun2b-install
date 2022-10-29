<?php 

define('SITE_ROOT', '../');
require SITE_ROOT.'include/common.php';

if (!$User->is_admin())
	message($lang_common['No permission']);

$action = isset($_GET['action']) ? $_GET['action'] : null;
$app_id = isset($_GET['id']) ? $_GET['id'] : null;

$AppsManager = new AppsManager;

if ($app_id !== null)
	$app_info = $AppsManager->get_app_info($app_id);

$apps_uploaded = $AppsManager->get_uploaded();
$apps_installed = $AppsManager->get_installed();
$apps_available = $AppsManager->get_available();
$apps_updates = $AppsManager->get_updates();

if (isset($_POST['install']))
{
	if ($AppsManager->is_installed($app_id))
		$Core->add_error('The application already installed.');

	if (!$Core->has_errors())
	{
		define('APP_INSTALL', 1);

		if (file_exists(SITE_ROOT.'apps/'.$app_id.'/inc/install.php'))
			require SITE_ROOT.'apps/'.$app_id.'/inc/install.php';

		// Install the new application
		$query = array(
			'INSERT'	=> 'id, title, version, description, author',
			'INTO'		=> 'applications',
			'VALUES'	=> 
			'\''.$DBLayer->escape($app_info['id']).'\', 
			\''.$DBLayer->escape($app_info['title']).'\', 
			\''.$DBLayer->escape($app_info['version']).'\', 
			\''.$DBLayer->escape($app_info['description']).'\',
				\''.$DBLayer->escape($app_info['author']).'\'',
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Regenerate the hooks cache
		$Cachinger->gen_apps();
		$Cachinger->gen_config();

		$flash_message = 'Applications installed';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('apps_management'), $flash_message);
	}
}
else if (isset($_POST['uninstall']))
{
	if ($app_info['id'] != '')
	{
		define('APP_UNINSTALL', 1);

		if (file_exists(SITE_ROOT.'apps/'.$app_id.'/inc/uninstall.php'))
			require SITE_ROOT.'apps/'.$app_id.'/inc/uninstall.php';

		// Uninstall the application
		$query = array(
			'DELETE'		=> 'applications',
			'WHERE'			=> 'id=\''.$DBLayer->escape($app_info['id']).'\''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Regenerate the hooks cache
		$Cachinger->gen_apps();
		$Cachinger->gen_config();

		$flash_message = 'Applications uninstalled';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('apps_management'), $flash_message);
	}
}
else if (isset($_POST['disable']))
{
	if ($app_info['id'] != '')
	{
		$query = array(
			'UPDATE'	=> 'applications',
			'SET'		=> 'disabled=1',
			'WHERE'		=> 'id=\''.$DBLayer->escape($app_info['id']).'\''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Regenerate the hooks cache
		$Cachinger->gen_apps();

		$flash_message = 'Applications disabled';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('apps_management'), $flash_message);
	}
}
else if (isset($_POST['enable']))
{
	if ($app_info['id'] != '')
	{
		$query = array(
			'UPDATE'	=> 'applications',
			'SET'		=> 'disabled=0',
			'WHERE'		=> 'id=\''.$DBLayer->escape($app_info['id']).'\''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Regenerate the hooks cache
		$Cachinger->gen_apps();

		$flash_message = 'Applications enabled';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('apps_management'), $flash_message);
	}
}

else if (isset($_POST['update']))
{
    define('APP_INSTALL', 1);

	if (empty($app_info))
		$Core->add_error('Wrong manifest.php.');

	if (!$Core->has_errors())
    {
		if (file_exists(SITE_ROOT.'apps/'.$app_id.'/inc/install.php'))
			require SITE_ROOT.'apps/'.$app_id.'/inc/install.php';

        // Update the app information
        $query = array(
            'UPDATE'	=> 'applications',
            'SET'		=> 'title=\''.$DBLayer->escape($app_info['title']).'\', 
                            version=\''.$DBLayer->escape($app_info['version']).'\', 
                            description=\''.$DBLayer->escape($app_info['description']).'\',
                            author=\''.$DBLayer->escape($app_info['author']).'\'',
            'WHERE'		=> 'id=\''.$DBLayer->escape($app_info['id']).'\''
        );
        $DBLayer->query_build($query) or error(__FILE__, __LINE__);

        // Regenerate the app cache
        $Cachinger->gen_apps();
		$Cachinger->gen_config();

        $flash_message = 'Applications updated';
        $FlashMessenger->add_info($flash_message);
        redirect($URL->link('apps_management'), $flash_message);
    }
}

else if (isset($_POST['delete']))
{
	// Delete all app files

	
	$flash_message = 'Applications deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('apps_management'), $flash_message);
}
else if (isset($_POST['cancel']))
{
	$flash_message = 'Action has been canceled';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('apps_management'), $flash_message);
}

$Core->set_page_id('apps_management', 'management');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />

<?php
if ($action == 'install')
{
?>

	<div class="modal-dialog">
		<div class="modal-content">
			<div class="card-header">
				<h5 class="modal-title">Install application</h5>
			</div>
			<div class="modal-body">
				<h6 class=""><span><?php echo $app_info['title'] ?></span></h6>
				<p><?php echo $app_info['description'] ?></p>
				<p>Version <?php echo $app_info['version'] ?></p>
				<p>Created by <?php echo $app_info['author'] ?></p>
			</div>
			<div class="modal-footer">
				<button type="submit" name="install" class="btn btn-primary">Install application</button>
				<button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
			</div>
		</div>
	</div>

<?php
}
else if ($action == 'uninstall')
{
?>

	<div class="modal-dialog">
		<div class="modal-content">
			<div class="card-header">
				<h5 class="modal-title">Uninstall application</h5>
			</div>
			<div class="modal-body">
				<div class="alert alert-danger" role="alert"><strong>Attention!</strong> This action will delete all data used by this application.</div>
				<h6 class=""><span><?php echo $app_info['title'] ?></span></h6>
				<p><?php echo $app_info['description'] ?></p>
				<p>Version <?php echo $app_info['version'] ?></p>
				<p>Created by <?php echo $app_info['author'] ?></p>
			</div>
			<div class="modal-footer">
				<button type="submit" name="uninstall" class="btn btn-danger">Uninstall application</button>
				<button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
			</div>
		</div>
	</div>

<?php
}
else if ($action == 'disable')
{
?>

	<div class="modal-dialog">
		<div class="modal-content">
			<div class="card-header">
				<h5 class="modal-title">Disable application</h5>
			</div>
			<div class="modal-body">
				<div class="alert alert-warning" role="alert"><strong>Attention!</strong> This action makes the application unavailable. Perhaps it will affect other related applications.</div>
				<h6 class=""><span><?php echo $app_info['title'] ?></span></h6>
				<p><?php echo $app_info['description'] ?></p>
				<p>Version <?php echo $app_info['version'] ?></p>
				<p>Created by <?php echo $app_info['author'] ?></p>
			</div>
			<div class="modal-footer">
				<button type="submit" name="disable" class="btn btn-warning">Disable application</button>
				<button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
			</div>
		</div>
	</div>

<?php
}
else if ($action == 'enable')
{
?>

	<div class="modal-dialog">
		<div class="modal-content">
			<div class="card-header">
				<h5 class="modal-title">Enable application</h5>
			</div>
			<div class="modal-body">
				<h6 class=""><span><?php echo $app_info['title'] ?></span></h6>
				<p><?php echo $app_info['description'] ?></p>
				<p>Version <?php echo $app_info['version'] ?></p>
				<p>Created by <?php echo $app_info['author'] ?></p>
			</div>
			<div class="modal-footer">
				<button type="submit" name="enable" class="btn btn-primary">Enable application</button>
				<button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
			</div>
		</div>
	</div>

<?php
}
else if ($action == 'update')
{
?>

	<div class="modal-dialog">
		<div class="modal-content">
			<div class="card-header">
				<h5 class="modal-title">Update application</h5>
			</div>
			<div class="modal-body">
				<h6 class=""><span><?php echo $app_info['title'] ?></span></h6>
				<p><?php echo $app_info['description'] ?></p>
				<p>Version <?php echo $app_info['version'] ?></p>
				<p>Created by <?php echo $app_info['author'] ?></p>
			</div>
			<div class="modal-footer">
				<button type="submit" name="update" class="btn btn-primary">Update application</button>
				<button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
			</div>
		</div>
	</div>

<?php
}
else if ($action == 'delete')
{
?>

	<div class="modal-dialog">
		<div class="modal-content">
			<div class="card-header">
				<h5 class="modal-title">Delete application</h5>
			</div>
			<div class="modal-body">
				<div class="alert alert-danger" role="alert"><strong>Attention!</strong> This action will remove the application from the server permanently.</div>
				<h6 class=""><span><?php echo $app_info['title'] ?></span></h6>
				<p><?php echo $app_info['description'] ?></p>
				<p>Version <?php echo $app_info['version'] ?></p>
				<p>Created by <?php echo $app_info['author'] ?></p>
			</div>
			<div class="modal-footer">
				<button type="submit" name="delete" class="btn btn-danger">Delete application</button>
				<button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
			</div>
		</div>
	</div>

<?php
}
else
{
?>

	<div class="card-header mb-1">
		<h6 class="card-title mb-0">Available applications to install</h6>
	</div>
	<div class="mb-3">

<?php
	if (!empty($apps_available))
	{
		foreach($apps_available as $app_id => $app_info)
		{
			if (file_exists($app_id.'/icon.png'))
				$app_icon = $app_id.'/icon.png';
			else
				$app_icon = BASE_URL.'/img/swift_icon.png';
?>

		<div class="card-body mb-1 rounded border alert-primary border-primary">
			<div class="row">
				<div class="col-md-1 mb-1">
					<img src="<?php echo $app_icon ?>" class="img-thumbnail">
				</div>
				<div class="col-md-11">
					<div class="">
						<h6 class="mb-0"><?php echo $app_info['title'] ?> • v.<?php echo $app_info['version'] ?></h6>
						<p class="card-text pb-0"><?php echo $app_info['description'] ?></p>
						<p class="card-text pb-0"><small class="text-muted">Created by <?php echo $app_info['author'] ?></small></p>
					</div>
					<div class="float-end">
						<a href="<?php echo $URL->link('apps_management_action', ['install', $app_info['id']]) ?>" class="badge bg-primary text-white">Install</a>
						<a href="<?php echo $URL->link('apps_management_action', ['delete', $app_info['id']]) ?>" class="badge bg-danger text-white">Delete</a>
					</div>
				</div>
			</div>
		</div>

<?php
		}
?>
	</div>
<?php
	}
	else
		echo '<div class="alert alert-warning" role="alert">There are no apps available to install.</div>';

	if (!empty($apps_updates))
	{
?>

	<div class="card-header mb-1">
		<h6 class="card-title mb-0">Available updates</h6>
	</div>
	<div class="mb-3">

<?php
		foreach($apps_updates as $app_id => $app_info)
		{
			if (file_exists($app_id.'/icon.png'))
				$app_icon = $app_id.'/icon.png';
			else
				$app_icon = BASE_URL.'/img/swift_icon.png';
?>

		<div class="card-body mb-1 rounded border alert-warning border-warning">
			<div class="row">
				<div class="col-md-1 mb-1">
					<img src="<?php echo $app_icon ?>" class="img-thumbnail">
				</div>
				<div class="col-md-11">
					<div class="">
						<h6 class="mb-0"><?php echo $app_info['title'] ?> • v.<?php echo $app_info['version'] ?></h6>
						<p class="card-text pb-0"><?php echo $app_info['description'] ?></p>
						<p class="card-text pb-0"><small class="text-muted">Created by <?php echo $app_info['author'] ?></small></p>
					</div>
					<div class="float-end">
						<a href="<?php echo $URL->link('apps_management_action', ['update', $app_id]) ?>" class="badge bg-primary text-white">Update</a>
						<a href="<?php echo $URL->link('apps_management_action', ['uninstall', $app_info['id']]) ?>" class="badge bg-danger text-white">Uninstall</a>
					</div>
				</div>
			</div>
		</div>

<?php
		}
?>
	</div>
<?php
	}
?>

	<div class="card-header mb-1">
		<h6 class="card-title mb-0">Installed applications</h6>
	</div>
	<div class="mb-3">
<?php
	if (!empty($apps_installed))
	{
		foreach($apps_installed as $app_id => $app_info)
		{
			if (file_exists($app_id.'/icon.png'))
				$app_icon = $app_id.'/icon.png';
			else
				$app_icon = BASE_URL.'/img/swift_icon.png';
?>
	<div class="card-body mb-1 rounded border <?php echo ($app_info['disabled'] == '0') ? 'alert-success border-success' : 'alert-danger border-danger' ?>">
		<div class="row">
			<div class="col-md-1 mb-1">
				<img src="<?php echo $app_icon ?>" class="img-thumbnail">
			</div>
			<div class="col-md-11">
				<div class="">
					<h6 class="mb-0"><?php echo $app_info['title'] ?> • v.<?php echo $app_info['version'] ?></h6>
					<p class="card-text pb-0"><?php echo $app_info['description'] ?></p>
					<p class="card-text pb-0"><small class="text-muted">Created by <?php echo $app_info['author'] ?></small></p>
				</div>
				<div class="float-end">
					<a href="<?php echo $URL->link('apps_management_action', ['uninstall', $app_info['id']]) ?>" class="badge bg-danger text-white">Uninstall</a>
<?php if ($app_info['disabled'] == '0') : ?>
					<a href="<?php echo $URL->link('apps_management_action', ['disable', $app_info['id']]) ?>" class="badge bg-warning text-white">Disable</a>
<?php else: ?>
					<a href="<?php echo $URL->link('apps_management_action', ['enable', $app_info['id']]) ?>" class="badge bg-success text-white">Enable</a>
<?php endif; ?>
				</div>
			</div>

		</div>
	</div>

<?php
		}
?>
	</div>
<?php
	}
	else
		echo '<div class="alert alert-warning" role="alert">No apps installed.</div>';
}
?>

</form>

<?php
require SITE_ROOT.'footer.php';