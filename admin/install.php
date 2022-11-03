<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

define('MIN_PHP_VERSION', '5.3.0');
define('MIN_MYSQL_VERSION', '4.1.2');

define('SITE_ROOT', '../');
define('DB_CONFIG', 1);
define('SPM_DEBUG', 1);

if (file_exists(SITE_ROOT.'config.php'))
	exit('The file \'config.php\' already exists which would mean that SwiftManager is already installed. You should go <a href="'.SITE_ROOT.'index.php">here</a> instead.');

// Make sure we are running at least MIN_PHP_VERSION
if (!function_exists('version_compare') || version_compare(PHP_VERSION, MIN_PHP_VERSION, '<'))
	exit('You are running PHP version '.PHP_VERSION.'. Core requires at least PHP '.MIN_PHP_VERSION.' to run properly. You must upgrade your PHP installation before you can continue.');

// Disable error reporting for uninitialized variables
error_reporting(E_ALL);

// Turn off PHP time limit
@set_time_limit(0);

require SITE_ROOT.'include/constants.php';
// We need some stuff from functions.php
require SITE_ROOT.'include/functions.php';

// Load UTF-8 functions
require SITE_ROOT.'include/utf8/utf8.php';
require SITE_ROOT.'include/utf8/ucwords.php';
require SITE_ROOT.'include/utf8/trim.php';

// Strip out "bad" UTF-8 characters
remove_bad_characters();

//
// Generate output to be used for config.php
//
function generate_config_file()
{
	global $db_type, $db_host, $db_name, $db_username, $db_password, $db_prefix, $base_url, $cookie_name;

	$config_body = '<?php'."\n\n".'$db_type = \''.$db_type."';\n".'$db_host = \''.$db_host."';\n".'$db_name = \''.addslashes($db_name)."';\n".'$db_username = \''.addslashes($db_username)."';\n".'$db_password = \''.addslashes($db_password)."';\n".'$db_prefix = \''.addslashes($db_prefix)."';\n".'$p_connect = false;'."\n\n".'$base_url = \''.$base_url.'\';'."\n\n".'$cookie_name = '."'".$cookie_name."';\n".'$cookie_domain = '."'';\n".'$cookie_path = '."'/';\n".'$cookie_secure = 0;'."\n\ndefine('DB_CONFIG', 1);\n\n";
	
	// Add constants
	$config_body .= 'define(\'DB_TYPE\', \''.$db_type.'\');'."\n";
	$config_body .= 'define(\'DB_HOST\', \''.$db_host.'\');'."\n";
	$config_body .= 'define(\'DB_NAME\', \''.addslashes($db_name).'\');'."\n";
	$config_body .= 'define(\'DB_USER\', \''.addslashes($db_username).'\');'."\n";
	$config_body .= 'define(\'DB_PASS\', \''.addslashes($db_password).'\');'."\n";
	$config_body .= 'define(\'DB_PREFIX\', \''.addslashes($db_prefix).'\');'."\n";
	$config_body .= 'define(\'P_CONNECT\', \'false\');'."\n\n";
	
	$config_body .= 'define(\'BASE_URL\', \''.$base_url.'\');'."\n";
	$config_body .= 'define(\'COOKIE_NAME\', \''.$cookie_name.'\');'."\n";
	$config_body .= 'define(\'COOKIE_DOMAIN\', \'\');'."\n";
	$config_body .= 'define(\'COOKIE_PATH\', \'/\');'."\n";
	$config_body .= 'define(\'COOKIE_SECURE\', \'0\');'."\n\n";
	
	// Add options
	$config_body .= "\n\n// Enable DEBUG mode by removing // from the following line\ndefine('SPM_DEBUG', 1);";
	$config_body .= "\n\n// Enable show DB Queries mode by removing // from the following line\n//define('SPM_SHOW_QUERIES', 1);";
	$config_body .= "\n\n// Enable IDNA support by removing // from the following line\n//define('SPM_ENABLE_IDNA', 1);";
	$config_body .= "\n\n// DisableCSRF checking by removing // from the following line\n//define('SPM_DISABLE_CSRF_CONFIRM', 1);";
	$config_body .= "\n\n// Disable hooks (extensions) by removing // from the following line\n//define('SPM_DISABLE_HOOKS', 1);";
	$config_body .= "\n\n// Disable output buffering by removing // from the following line\n//define('SPM_DISABLE_BUFFERING', 1);";
	$config_body .= "\n\n// Disable async JS loader by removing // from the following line\n//define('SPM_DISABLE_ASYNC_JS_LOADER', 1);";
	$config_body .= "\n\n// Disable extensions version check by removing // from the following line\n//define('SPM_DISABLE_EXTENSIONS_VERSION_CHECK', 1);";
	$config_body .= "\n\n// SQLite3 busy timeout -> after waiting for that time we get 'db is locked' error (in msec)\n//define('SPM_SQLITE3_BUSY_TIMEOUT', 10000);";
	$config_body .= "\n\n// SQLite3 WAL mode has better control over concurrency. Source: https://www.sqlite.org/wal.html\n//define('SPM_SQLITE3_WAL_ON', 1);";


	return $config_body;
}

$language = isset($_GET['lang']) ? $_GET['lang'] : (isset($_POST['req_language']) ? swift_trim($_POST['req_language']) : 'English');
$language = preg_replace('#[\.\\\/]#', '', $language);
if (!file_exists(SITE_ROOT.'lang/'.$language.'/install.php'))
	exit('The language pack you have chosen doesn\'t seem to exist or is corrupt. Please recheck and try again.');

// Load the language files
require SITE_ROOT.'lang/'.$language.'/install.php';
require SITE_ROOT.'lang/'.$language.'/admin_settings.php';

if (isset($_POST['generate_config']))
{
	header('Content-Type: text/x-delimtext; name="config.php"');
	header('Content-disposition: attachment; filename=config.php');

	$db_type = $_POST['db_type'];
	$db_host = $_POST['db_host'];
	$db_name = $_POST['db_name'];
	$db_username = $_POST['db_username'];
	$db_password = $_POST['db_password'];
	$db_prefix = $_POST['db_prefix'];
	$base_url = $_POST['base_url'];
	$cookie_name = $_POST['cookie_name'];

	echo generate_config_file();
	exit;
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: cache-control: no-store', false);

if (!isset($_POST['form_sent']))
{
	// Determine available database extensions
	$db_extensions = array();

	if (function_exists('mysqli_connect'))
	{
		$db_extensions[] = array('mysqli', 'MySQL Improved');
		$db_extensions[] = array('mysqli_innodb', 'MySQL Improved (InnoDB)');
	}

	if (function_exists('mysql_connect'))
	{
		$db_extensions[] = array('mysql', 'MySQL Standard');
		$db_extensions[] = array('mysql_innodb', 'MySQL Standard (InnoDB)');
	}

	if (function_exists('sqlite_open'))
		$db_extensions[] = array('sqlite', 'SQLite');

	if (class_exists('SQLite3'))
		$db_extensions[] = array('sqlite3', 'SQLite3');

	if (function_exists('pg_connect'))
		$db_extensions[] = array('pgsql', 'PostgreSQL');

	if (empty($db_extensions))
		error($lang_install['No database support']);

	// Make an educated guess regarding base_url
	$base_url_guess = ((!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') ? 'https://' : 'http://').preg_replace('/:80$/', '', $_SERVER['HTTP_HOST']).substr(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), 0, -6);
	if (substr($base_url_guess, -1) == '/')
		$base_url_guess = substr($base_url_guess, 0, -1);

	// Check for available language packs
	$languages = get_language_packs();

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
	<meta charset="utf-8" />
	<title>SwiftManager Installation</title>
	<link rel="stylesheet" type="text/css" href="<?php echo SITE_ROOT ?>style/main.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo SITE_ROOT ?>vendor/bootstrap/css/bootstrap.min.css" />
</head>
<body>

	<div class="card mb-3">
		<div class="card-body">
			<div class="alert alert-info" role="alert">
				<h1 class="alert-heading"><?php printf($lang_install['Install PunBB'], SPM_VERSION) ?></h1>
				<hr class="my-1">
				<p><?php echo $lang_install['Install intro'] ?></p>
			</div>
		</div>
	</div>
<?php
	if (count($languages) > 1)
	{
?>
	<form method="get" accept-charset="utf-8" action="install.php" class="was-validated">
		<div class="card mb-3">
			<div class="card-header">
				<h6 class="card-title mb-0"><?php echo $lang_install['Choose language'] ?></h6>
			</div>
			<div class="card-body">
				<label class="form-label" for="fld_lang"><?php echo $lang_install['Installer language'] ?></label>
				<select name="lang" id="fld_lang" class="form-select">
<?php
		foreach ($languages as $lang)
			echo "\t\t\t\t\t".'<option value="'.$lang.'"'.($language == $lang ? ' selected="selected"' : '').'>'.$lang.'</option>'."\n";
?>
				</select>
				<label class="text-muted"><?php echo $lang_install['Choose language help'] ?></label>

				<button type="submit" name="changelangt" class="btn btn-sm btn-primary"><?php echo $lang_install['Choose language'] ?></button>

			</div>
		</div>
	</form>
<?php
	}
?>
	<form method="post" accept-charset="utf-8" action="install.php" class="was-validated">
		<input type="hidden" name="form_sent" value="1" />

		<div class="card mb-3">
			<div class="card-body">

				<div class="alert alert-warning" role="alert">
					<h2 class="alert-heading"><?php echo $lang_install['Part1'] ?></h2>
					<hr class="my-1">
					<p><?php echo $lang_install['Part1 intro'] ?></p>
					<ul class="spaced list-clean">
						<li><span><strong><?php echo $lang_install['Database type'] ?></strong> <?php echo $lang_install['Database type info']; if (count($db_extensions) > 1) echo ' '.$lang_install['Mysql type info'] ?></span></li>
						<li><span><strong><?php echo $lang_install['Database server'] ?></strong> <?php echo $lang_install['Database server info'] ?></span></li>
						<li><span><strong><?php echo $lang_install['Database name'] ?></strong> <?php echo $lang_install['Database name info'] ?></span></li>
						<li><span><strong><?php echo $lang_install['Database user pass'] ?></strong> <?php echo $lang_install['Database username info'] ?></span></li>
						<li><span><strong><?php echo $lang_install['Table prefix'] ?></strong> <?php echo $lang_install['Table prefix info'] ?></span></li>
					</ul>
				</div>

				<div class="mb-3">
					<label class="form-label" for="req_db_type"><?php echo $lang_install['Database type'] ?></label>
					<select name="req_db_type" class="form-select" id="req_db_type">
<?php
	foreach ($db_extensions as $db_type)
		echo "\t\t\t\t\t".'<option value="'.$db_type[0].'">'.$db_type[1].'</option>'."\n";
?>
					</select>
					<label class="text-muted"><?php echo $lang_install['Database type help'] ?></label>
				</div>

				<div class="mb-3">
					<label class="form-label" for="fld_req_db_host"><?php echo $lang_install['Database server'] ?></label>
					<input id="fld_req_db_host" class="form-control" type="text" name="req_db_host" value="localhost" required>
					<label class="text-muted"><?php echo $lang_install['Database server help'] ?></label>
				</div>
				<div class="mb-3">
					<label class="form-label" for="fld_req_db_name"><?php echo $lang_install['Database name'] ?></label>
					<input id="fld_req_db_name" class="form-control" type="text" name="req_db_name" required>
					<label class="text-muted"><?php echo $lang_install['Database name help'] ?></label>
				</div>
				<div class="mb-3">
					<label class="form-label" for="fld_db_username"><?php echo $lang_install['Database username'] ?></label>
					<input id="fld_db_username" class="form-control" type="text" name="db_username" required>
					<label class="text-muted"><?php echo $lang_install['Database username help'] ?></label>
				</div>
				<div class="mb-3">
					<label class="form-label" for="fld_db_password"><?php echo $lang_install['Database password'] ?></label>
					<input id="fld_db_password" class="form-control" type="text" name="db_password" autocomplete="off">
					<label class="text-muted"><?php echo $lang_install['Database password help'] ?></label>
				</div>
				<div class="mb-3">
					<label class="form-label" for="fld_db_prefix"><?php echo $lang_install['Table prefix'] ?></label>
					<input id="fld_db_prefix" class="form-control" type="text" name="db_prefix" maxlength="30">
					<label class="text-muted"><?php echo $lang_install['Table prefix help'] ?></label>
				</div>
			</div>
		</div>

		<div class="card mb-3">
			<div class="card-body">
				<div class="alert alert-warning" role="alert">
					<h2 class="alert-heading"><?php echo $lang_install['Part2'] ?></h2>
					<hr class="my-1">
					<p><?php echo $lang_install['Part2 intro'] ?></p>
				</div>
				<div class="mb-3">
					<label class="form-label" for="fld_req_email"><?php echo $lang_install['Admin e-mail'] ?></label>
					<input id="fld_req_email" class="form-control" type="email" name="req_email" required>
					<label class="text-muted"><?php echo $lang_install['E-mail address help'] ?></label>
				</div>
				<div class="mb-3">
					<label class="form-label" for="fld_req_username"><?php echo $lang_install['Admin username'] ?></label>
					<input id="fld_req_username" class="form-control" type="text" name="req_username" value="Admin" required>
					<label class="text-muted"><?php echo $lang_install['Username help'] ?></label>
				</div>
				<div class="mb-3">
					<label class="form-label" for="fld_req_password1"><?php echo $lang_install['Admin password'] ?></label>
					<input id="fld_req_password1" class="form-control" type="text" name="req_password1" required autocomplete="off">
					<label class="text-muted"><?php echo $lang_install['Password help'] ?></label>
				</div>
			</div>
		</div>

		<div class="card mb-3">
			<div class="card-body">
				<div class="alert alert-warning" role="alert">
					<h2 class="alert-heading"><?php echo $lang_install['Part3'] ?></h2>
					<hr class="my-1">
					<p><?php echo $lang_install['Part3 intro'] ?></p>
					<ul class="spaced list-clean">
						<li><span><strong><?php echo $lang_install['Base URL'] ?></strong> <?php echo $lang_install['Base URL info'] ?></span></li>
					</ul>
				</div>

				<div class="mb-3">
					<label class="form-label" for="fld_req_base_url"><?php echo $lang_install['Base URL'] ?></label>
					<input id="fld_req_base_url" class="form-control" type="url" name="req_base_url" required>
					<label class="text-muted"><?php echo $lang_install['Base URL help'] ?></label>
				</div>
<?php
	if (count($languages) > 1)
	{
?>
				<div class="mb-3">
					<label class="form-label" for="fld_req_language"><?php echo $lang_install['Default language'] ?></label>
					<select id="fld_req_language" name="req_language" class="form-select">
<?php
		foreach ($languages as $lang)
			echo "\t\t\t\t\t".'<option value="'.$lang.'"'.($language == $lang ? ' selected="selected"' : '').'>'.$lang.'</option>'."\n";
?>
					</select>
					<label class="text-muted"><?php echo $lang_install['Default language help'] ?></label>
				</div>
<?php
	}
	else
	{
?>
			<input type="hidden" name="req_language" value="<?php echo $languages[0] ?>">
<?php
	}
?>
				<button type="submit" name="start" class="btn btn-sm btn-primary"><?php echo $lang_install['Start install'] ?></button>

			</div>
		</div>
	</form>
</body>
</html>
<?php
}
else
{
	$db_type = $_POST['req_db_type'];
	$db_host = swift_trim($_POST['req_db_host']);
	$db_name = swift_trim($_POST['req_db_name']);
	$db_username = swift_trim($_POST['db_username']);
	$db_password = swift_trim($_POST['db_password']);
	$db_prefix = swift_trim($_POST['db_prefix']);
	$username = swift_trim($_POST['req_username']);
	$email = strtolower(swift_trim($_POST['req_email']));
	$password1 = swift_trim($_POST['req_password1']);
	$default_lang = preg_replace('#[\.\\\/]#', '', swift_trim($_POST['req_language']));
	$install_pun_repository = !empty($_POST['install_pun_repository']);

	// Make sure base_url doesn't end with a slash
	if (substr($_POST['req_base_url'], -1) == '/')
		$base_url = substr($_POST['req_base_url'], 0, -1);
	else
		$base_url = $_POST['req_base_url'];
	
	define('BASE_URL', $base_url);
	
	// Validate form
	if (utf8_strlen($db_name) == 0)
		error($lang_install['Missing database name']);
	if (utf8_strlen($username) < 2)
		error($lang_install['Username too short']);
	if (utf8_strlen($username) > 25)
		error($lang_install['Username too long']);
	if (utf8_strlen($password1) < 4)
		error($lang_install['Pass too short']);
	if (strtolower($username) == 'guest')
		error($lang_install['Username guest']);
	if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $username) || preg_match('/((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))/', $username))
		error($lang_install['Username IP']);
	if ((strpos($username, '[') !== false || strpos($username, ']') !== false) && strpos($username, '\'') !== false && strpos($username, '"') !== false)
		error($lang_install['Username reserved chars']);
	if (preg_match('/(?:\[\/?(?:b|u|i|h|colou?r|quote|code|img|url|email|list)\]|\[(?:code|quote|list)=)/i', $username))
		error($lang_install['Username BBCode']);

	// Validate email
	if (!is_valid_email($email))
		error($lang_install['Invalid email']);

	// Make sure board title and description aren't left blank
	$board_title = 'SwiftManager';
	$board_descrip = 'Easy to use - fast to work.';

	if (utf8_strlen($base_url) == 0)
		error($lang_install['Missing base url']);

	if (!file_exists(SITE_ROOT.'lang/'.$default_lang.'/common.php'))
		error($lang_install['Invalid language']);

	// Load the appropriate DB layer class
	switch ($db_type)
	{
		case 'mysql':
			require SITE_ROOT.'include/dblayer/mysql.php';
			break;

		case 'mysql_innodb':
			require SITE_ROOT.'include/dblayer/mysql_innodb.php';
			break;

		case 'mysqli':
			require SITE_ROOT.'include/dblayer/mysqli.php';
			break;

		case 'mysqli_innodb':
			require SITE_ROOT.'include/dblayer/mysqli_innodb.php';
			break;

		case 'pgsql':
			require SITE_ROOT.'include/dblayer/pgsql.php';
			break;

		case 'sqlite':
			require SITE_ROOT.'include/dblayer/sqlite.php';
			break;

		case 'sqlite3':
			require SITE_ROOT.'include/dblayer/sqlite3.php';
			break;

		default:
			error(sprintf($lang_install['No such database type'], html_encode($db_type)));
	}

	// Create the database object (and connect/select db)
	$DBLayer = new DBLayer($db_host, $db_username, $db_password, $db_name, $db_prefix, false);


	// If MySQL, make sure it's at least 4.1.2
	if (in_array($db_type, array('mysql', 'mysqli', 'mysql_innodb', 'mysqli_innodb')))
	{
		$mysql_info = $DBLayer->get_version();
		if (version_compare($mysql_info['version'], MIN_MYSQL_VERSION, '<'))
			error(sprintf($lang_install['Invalid MySQL version'], html_encode($mysql_info['version']), MIN_MYSQL_VERSION));

		// Check InnoDB support in DB
		if (in_array($db_type, array('mysql_innodb', 'mysqli_innodb')))
		{
			$found_innodb = false;
			$result = $DBLayer->query("SHOW ENGINES");
			while ($row = $DBLayer->fetch_assoc($result))
			{
				if ($row['Engine'] == 'InnoDB' && in_array($row['Support'], array('YES', 'DEFAULT')))
					$found_innodb = true;
			}
			if (!$found_innodb)
				error($lang_install['InnoDB Not Supported']);
		}
	}

	// Validate prefix
	if (strlen($db_prefix) > 0 && (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $db_prefix) || strlen($db_prefix) > 40))
		error(sprintf($lang_install['Invalid table prefix'], $db_prefix));

	// Check SQLite prefix collision
	if (in_array($db_type, array('sqlite', 'sqlite3')) && strtolower($db_prefix) == 'sqlite_')
		error($lang_install['SQLite prefix collision']);


	// Make sure SwiftManager isn't already installed
	if ($DBLayer->table_exists('users'))
	{
		$query = array(
			'SELECT'	=> 'COUNT(id)',
			'FROM'		=> 'users',
			'WHERE'		=> 'id=1'
		);

		$result = $DBLayer->query_build($query);
		if ($DBLayer->result($result) > 0)
			error('SwiftManager already installed');
	}

	// Start a transaction
	$DBLayer->start_transaction();


	// Create all tables
	$schema = array(
		'FIELDS'		=> array(
			'id'				=> $DBLayer->dt_varchar(),
			'title'				=> $DBLayer->dt_varchar(),
			'version'			=> $DBLayer->dt_varchar(),
			'description'		=> $DBLayer->dt_text(),
			'author'			=> $DBLayer->dt_varchar(),
			'disabled'			=> $DBLayer->dt_int('TINYINT(1)'),
		),
		'PRIMARY KEY'	=> array('id')
	);
	$DBLayer->create_table('applications', $schema);

	$schema = array(
		'FIELDS'		=> array(
			'id'			=> $DBLayer->dt_serial(),
			'username'		=> $DBLayer->dt_varchar('VARCHAR(200)', true),
			'ip'			=> $DBLayer->dt_varchar('VARCHAR(255)', true),
			'email'			=> $DBLayer->dt_varchar('VARCHAR(80)', true),
			'message'		=> $DBLayer->dt_varchar('VARCHAR(255)', true),
			'expire'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'ban_creator'	=> $DBLayer->dt_int()
		),
		'PRIMARY KEY'	=> array('id')
	);
	$DBLayer->create_table('bans', $schema);

	$schema = array(
		'FIELDS'		=> array(
			'conf_name'		=> $DBLayer->dt_varchar(),
			'conf_value'	=> $DBLayer->dt_text(),
		),
		'PRIMARY KEY'	=> array('conf_name')
	);
	$DBLayer->create_table('config', $schema);

	$schema = array(
		'FIELDS'		=> array(
			'id'				=> $DBLayer->dt_varchar(),
			'title'				=> $DBLayer->dt_varchar(),
			'version'			=> $DBLayer->dt_varchar(),
			'description'		=> $DBLayer->dt_text(),
			'author'			=> $DBLayer->dt_varchar(),
			'uninstall'			=> $DBLayer->dt_text(),
			'uninstall_note'	=> $DBLayer->dt_text(),
			'disabled'			=> $DBLayer->dt_int('TINYINT(1)'),
			'dependencies'		=> $DBLayer->dt_varchar()
		),
		'PRIMARY KEY'	=> array('id')
	);
	$DBLayer->create_table('extensions', $schema);

	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'VARCHAR(150)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'extension_id'	=> array(
				'datatype'		=> 'VARCHAR(50)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'code'			=> $DBLayer->dt_text(),
			'installed'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'priority'		=> array(
				'datatype'		=> 'TINYINT(1) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '5'
			)
		),
		'PRIMARY KEY'	=> array('id', 'extension_id')
	);
	$DBLayer->create_table('extension_hooks', $schema);

	$schema = array(
		'FIELDS'		=> array(
			'g_id'						=> $DBLayer->dt_serial(),
			'g_title'					=> $DBLayer->dt_varchar(),
			'g_user_title'				=> $DBLayer->dt_varchar('VARCHAR(50)', true),
			'g_moderator'				=> $DBLayer->dt_int('TINYINT(1)'),
			'g_mod_edit_users'			=> $DBLayer->dt_int('TINYINT(1)'),
			'g_mod_rename_users'		=> $DBLayer->dt_int('TINYINT(1)'),
			'g_mod_change_passwords'	=> $DBLayer->dt_int('TINYINT(1)'),
			'g_mod_ban_users'			=> $DBLayer->dt_int('TINYINT(1)'),
			'g_read_board'				=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
			'g_view_users'				=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
			'g_post_replies'			=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
			'g_post_topics'				=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
			'g_edit_posts'				=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
			'g_delete_posts'			=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
			'g_delete_topics'			=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
			'g_set_title'				=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
			'g_search'					=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
			'g_search_users'			=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
			'g_send_email'				=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
			'g_post_flood'				=> $DBLayer->dt_int('SMALLINT(6)', false, '30'),
			'g_search_flood'			=> $DBLayer->dt_int('SMALLINT(6)', false, '30'),
			'g_email_flood'				=> $DBLayer->dt_int('SMALLINT(6)', false, '60'),
		),
		'PRIMARY KEY'	=> array('g_id')
	);
	$DBLayer->create_table('groups', $schema);

	$schema = array(
		'FIELDS'		=> array(
			'user_id'		=> $DBLayer->dt_int('INT(10) UNSIGNED', false, '1'),
			'ident'			=> $DBLayer->dt_varchar(),
			'logged'		=> $DBLayer->dt_int(),
			'idle'			=> $DBLayer->dt_int('TINYINT(1)', false, '0'),
			'csrf_token'	=> $DBLayer->dt_varchar(),
			'prev_url'		=> $DBLayer->dt_varchar('VARCHAR(255)', true),
			'last_post'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'last_search'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
		),
		'UNIQUE KEYS'	=> array(
			'user_id_ident_idx'	=> array('user_id', 'ident')
		),
		'INDEXES'		=> array(
			'ident_idx'		=> array('ident'),
			'logged_idx'	=> array('logged')
		),
		'ENGINE'		=> 'HEAP'
	);

	if (in_array($db_type, array('mysql', 'mysqli', 'mysql_innodb', 'mysqli_innodb')))
	{
		$schema['UNIQUE KEYS']['user_id_ident_idx'] = array('user_id', 'ident(40)');
		$schema['INDEXES']['ident_idx'] = array('ident(40)');
	}
	$DBLayer->create_table('online', $schema);

	$schema = array(
		'FIELDS'		=> array(
			'id'			=> $DBLayer->dt_serial(),
			'post_id'		=> $DBLayer->dt_int(),
			'topic_id'		=> $DBLayer->dt_int(),
			'forum_id'		=> $DBLayer->dt_int(),
			'reported_by'	=> $DBLayer->dt_int(),
			'created'		=> $DBLayer->dt_int(),
			'message'		=> $DBLayer->dt_text(),
			'zapped'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'zapped_by'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			)
		),
		'PRIMARY KEY'	=> array('id'),
		'INDEXES'		=> array(
			'zapped_idx'	=> array('zapped')
		)
	);
	$DBLayer->create_table('reports', $schema);

	$schema = array(
		'FIELDS'		=> array(
			'id'			=> $DBLayer->dt_int(),
			'ident'			=> $DBLayer->dt_varchar(),
			'search_data'	=> $DBLayer->dt_text()
		),
		'PRIMARY KEY'	=> array('id'),
		'INDEXES'		=> array(
			'ident_idx'	=> array('ident')
		)
	);

	if (in_array($db_type, array('mysql', 'mysqli', 'mysql_innodb', 'mysqli_innodb')))
		$schema['INDEXES']['ident_idx'] = array('ident(8)');

	$DBLayer->create_table('search_cache', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'post_id'		=> $DBLayer->dt_int(),
			'word_id'		=> $DBLayer->dt_int(),
			'subject_match'	=> $DBLayer->dt_int('TINYINT(1)'),
		),
		'INDEXES'		=> array(
			'word_id_idx'	=> array('word_id'),
			'post_id_idx'	=> array('post_id')
		)
	);
	$DBLayer->create_table('search_matches', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> $DBLayer->dt_serial(),
			'word'			=> array(
				'datatype'		=> 'VARCHAR(20)',
				'allow_null'	=> false,
				'default'		=> '\'\'',
				'collation'		=> 'bin'
			)
		),
		'PRIMARY KEY'	=> array('word'),
		'INDEXES'		=> array(
			'id_idx'	=> array('id')
		)
	);

	if ($db_type == 'sqlite' || $db_type == 'sqlite3')
	{
		$schema['PRIMARY KEY'] = array('id');
		$schema['UNIQUE KEYS'] = array('word_idx'	=> array('word'));
	}
	$DBLayer->create_table('search_words', $schema);

	$schema = array(
		'FIELDS'		=> array(
			'id'				=> $DBLayer->dt_serial(),
			'group_id'			=> $DBLayer->dt_int('INT(10) UNSIGNED', false, '3'),
			'username'			=> $DBLayer->dt_varchar(),
			'password'			=> $DBLayer->dt_varchar(),
			'salt'				=> $DBLayer->dt_varchar('VARCHAR(12)', true),
			'email'				=> $DBLayer->dt_varchar(),
			'title'				=> $DBLayer->dt_varchar('VARCHAR(50)', true),
			'realname'			=> $DBLayer->dt_varchar('VARCHAR(50)', true),
			'first_name'		=> $DBLayer->dt_varchar('VARCHAR(50)', true),
			'last_name'			=> $DBLayer->dt_varchar('VARCHAR(50)', true),
			'work_phone'		=> $DBLayer->dt_varchar('VARCHAR(50)', true),
			'cell_phone'		=> $DBLayer->dt_varchar('VARCHAR(50)', true),
			'home_phone'		=> $DBLayer->dt_varchar('VARCHAR(50)', true),
			'url'				=> $DBLayer->dt_varchar('VARCHAR(100)', true),
			'location'			=> $DBLayer->dt_varchar('VARCHAR(30)', true),
			'signature_on'		=> $DBLayer->dt_int('TINYINT(1)'),
			'signature'			=> $DBLayer->dt_text(),
			'num_items_on_page'	=> $DBLayer->dt_int('TINYINT(3) UNSIGNED', false, '25'),
			'email_setting'		=> $DBLayer->dt_int('TINYINT(1)'),
			'notify_with_post'	=> $DBLayer->dt_int('TINYINT(1)'),
			'auto_notify'		=> $DBLayer->dt_int('TINYINT(1)'),
			'show_smilies'		=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
			'show_img'			=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
			'show_img_sig'		=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
			'show_avatars'		=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
			'show_sig'			=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
			'access_keys'		=> $DBLayer->dt_int('TINYINT(1)'),
			'timezone'			=> array(
				'datatype'		=> 'FLOAT',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'dst'				=> $DBLayer->dt_int('TINYINT(1)'),
			'time_format'		=> $DBLayer->dt_int('INT(10) UNSIGNED', false, '4'),
			'date_format'		=> $DBLayer->dt_int(),
			'language'			=> array(
				'datatype'		=> 'VARCHAR(25)',
				'allow_null'	=> false,
				'default'		=> '\'English\''
			),
			'style'				=> array(
				'datatype'		=> 'VARCHAR(25)',
				'allow_null'	=> false,
				'default'		=> '\'Default\''
			),
			'num_posts'			=> $DBLayer->dt_int(),
			'last_post'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'last_search'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'last_email_sent'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'registered'		=> $DBLayer->dt_int(),
			'registration_ip'	=> array(
				'datatype'		=> 'VARCHAR(39)',
				'allow_null'	=> false,
				'default'		=> '\'0.0.0.0\''
			),
			'last_visit'		=> $DBLayer->dt_int(),
			'admin_note'		=> $DBLayer->dt_varchar('VARCHAR(30)', true),
			'activate_string'	=> $DBLayer->dt_varchar('VARCHAR(80)', true),
			'activate_key'		=> $DBLayer->dt_varchar('VARCHAR(8)', true),
			'avatar'			=> $DBLayer->dt_int('TINYINT(3) UNSIGNED', false, '0'),
			'avatar_width'		=> $DBLayer->dt_int('TINYINT(3) UNSIGNED', false, '0'),
			'avatar_height'		=> $DBLayer->dt_int('TINYINT(3) UNSIGNED', false, '0'),
			'users_sort_by'		=> $DBLayer->dt_int('TINYINT(1)')
		),
		'PRIMARY KEY'	=> array('id'),
		'INDEXES'		=> array(
			'registered_idx'	=> array('registered'),
			'username_idx'		=> array('username')
		)
	);

	if (in_array($db_type, array('mysql', 'mysqli', 'mysql_innodb', 'mysqli_innodb')))
		$schema['INDEXES']['username_idx'] = array('username(8)');

	$DBLayer->create_table('users', $schema);

	$schema = [
		'FIELDS'	=> [
			'id'			=> $DBLayer->dt_serial(),
			'a_gid'			=> $DBLayer->dt_int(),
			'a_uid'			=> $DBLayer->dt_int(),
			'a_to'			=> $DBLayer->dt_varchar(),
			'a_key'			=> $DBLayer->dt_int('TINYINT(3)'),
			'a_value'		=> $DBLayer->dt_int('TINYINT(1)')
		],
		'PRIMARY KEY'	=> ['id']
	];
	$DBLayer->create_table('user_access', $schema);
	
	// Set Permissions for actions
	$schema = [
		'FIELDS'	=> [
			'id'			=> $DBLayer->dt_serial(),
			'p_gid'			=> $DBLayer->dt_int(),
			'p_uid'			=> $DBLayer->dt_int(),
			'p_to'			=> $DBLayer->dt_varchar(),
			'p_key'			=> $DBLayer->dt_int('TINYINT(3)'),
			'p_value'		=> $DBLayer->dt_int('TINYINT(1)')
		],
		'PRIMARY KEY'	=> ['id']
	];
	$DBLayer->create_table('user_permissions', $schema);
	
	// Set notifications for projects
	$schema = [
		'FIELDS'	=> [
			'id'			=> $DBLayer->dt_serial(),
			'n_gid'			=> $DBLayer->dt_int(),
			'n_uid'			=> $DBLayer->dt_int(),
			'n_to'			=> $DBLayer->dt_varchar(),
			'n_key'			=> $DBLayer->dt_int('TINYINT(3)'),
			'n_value'		=> $DBLayer->dt_int('TINYINT(1)')
		],
		'PRIMARY KEY'	=> ['id']
	];
	$DBLayer->create_table('user_notifications', $schema);

	$now = time();

	// Insert the four preset groups
	$query = array(
		'INSERT'	=> 'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
		'INTO'		=> 'groups',
		'VALUES'	=> '\'Administrators\', \'Administrator\', 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0'
	);

	if ($db_type != 'pgsql')
	{
		$query['INSERT'] .= ', g_id';
		$query['VALUES'] .= ', 1';
	}

	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'INSERT'	=> 'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
		'INTO'		=> 'groups',
		'VALUES'	=> '\'Guest\', NULL, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 0, 60, 30, 0'
	);

	if ($db_type != 'pgsql')
	{
		$query['INSERT'] .= ', g_id';
		$query['VALUES'] .= ', 2';
	}

	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'INSERT'	=> 'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
		'INTO'		=> 'groups',
		'VALUES'	=> '\'Employees\', NULL, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 60, 30, 60'
	);

	if ($db_type != 'pgsql')
	{
		$query['INSERT'] .= ', g_id';
		$query['VALUES'] .= ', 3';
	}

	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'INSERT'	=> 'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
		'INTO'		=> 'groups',
		'VALUES'	=> '\'Moderators\', \'Moderator\', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0'
	);

	if ($db_type != 'pgsql')
	{
		$query['INSERT'] .= ', g_id';
		$query['VALUES'] .= ', 4';
	}

	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Insert guest and first admin user
	$query = array(
		'INSERT'	=> 'group_id, username, password, email',
		'INTO'		=> 'users',
		'VALUES'	=> '2, \'Guest\', \'Guest\', \'Guest\''
	);

	if ($db_type != 'pgsql')
	{
		$query['INSERT'] .= ', id';
		$query['VALUES'] .= ', 1';
	}

	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	$salt = random_key(12);

	$query = array(
		'INSERT'	=> 'group_id, username, password, email, language, num_posts, last_post, registered, registration_ip, last_visit, salt',
		'INTO'		=> 'users',
		'VALUES'	=> '1, \''.$DBLayer->escape($username).'\', \''.spm_hash($password1, $salt).'\', \''.$DBLayer->escape($email).'\', \''.$DBLayer->escape($default_lang).'\', 1, '.$now.', '.$now.', \'127.0.0.1\', '.$now.', \''.$DBLayer->escape($salt).'\''
	);

	$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$new_uid = $DBLayer->insert_id();

	// Enable/disable avatars depending on file_uploads setting in PHP configuration
	$avatars = in_array(strtolower(@ini_get('file_uploads')), array('on', 'true', '1')) ? 1 : 0;

	// Enable/disable automatic check for updates depending on PHP environment (require cURL, fsockopen or allow_url_fopen)
	$check_for_updates = (function_exists('curl_init') || function_exists('fsockopen') || in_array(strtolower(@ini_get('allow_url_fopen')), array('on', 'true', '1'))) ? 1 : 0;

	// Insert config data
	$config = array(
		'o_cur_version'				=> "'".SPM_VERSION."'",
		'o_database_revision'		=> "'".SPM_DB_REVISION."'",
		'o_board_title'				=> "'".$DBLayer->escape($board_title)."'",
		'o_board_desc'				=> "'".$DBLayer->escape($board_descrip)."'",
		'o_default_timezone'		=> "'0'",
		'o_time_format'				=> "'H:i:s'",
		'o_date_format'				=> "'Y-m-d'",
		'o_check_for_updates'		=> "'$check_for_updates'",
		'o_check_for_versions'		=> "'$check_for_updates'",
		'o_timeout_visit'			=> "'5400'",
		'o_timeout_online'			=> "'300'",
		'o_redirect_delay'			=> "'0'",
		'o_show_version'			=> "'0'",
		'o_show_user_info'			=> "'1'",
		'o_show_post_count'			=> "'1'",
		'o_signatures'				=> "'1'",
		'o_smilies'					=> "'1'",
		'o_smilies_sig'				=> "'1'",
		'o_make_links'				=> "'1'",
		'o_default_lang'			=> "'".$DBLayer->escape($default_lang)."'",
		'o_default_style'			=> "'Default'",
		'o_default_user_group'		=> "'3'",
		'o_topic_review'			=> "'15'",
		'o_num_items_on_page'		=> "'25'",
		'o_max_items_on_page'		=> "'100'",
		'o_indent_num_spaces'		=> "'4'",
		'o_quote_depth'				=> "'3'",
		'o_quickpost'				=> "'1'",
		'o_users_online'			=> "'1'",
		'o_censoring'				=> "'0'",
		'o_ranks'					=> "'1'",
		'o_show_dot'				=> "'0'",
		'o_topic_views'				=> "'1'",
		'o_quickjump'				=> "'1'",
		'o_gzip'					=> "'0'",
		'o_additional_navlinks'		=> "''",
		'o_report_method'			=> "'0'",
		'o_regs_report'				=> "'0'",
		'o_default_email_setting'	=> "'1'",
		'o_mailing_list'			=> "'".$DBLayer->escape($email)."'",
		'o_avatars'					=> "'$avatars'",
		'o_avatars_dir'				=> "'img/avatars'",
		'o_avatars_width'			=> "'60'",
		'o_avatars_height'			=> "'60'",
		'o_avatars_size'			=> "'15360'",
		'o_search_all_forums'		=> "'1'",
		'o_sef'						=> "'Default'",
		'o_admin_email'				=> "'".$DBLayer->escape($email)."'",
		'o_webmaster_email'			=> "'".$DBLayer->escape($email)."'",
		'o_subscriptions'			=> "'1'",
		'o_email_mode'				=> "'1'",
		'o_smtp_host'				=> "''",
		'o_smtp_port'				=> "'25'",
		'o_smtp_user'				=> "''",
		'o_smtp_pass'				=> "''",
		'o_smtp_ssl'				=> "'0'",
		'o_regs_allow'				=> "'1'",
		'o_regs_verify'				=> "'0'",
		'o_announcement'			=> "'0'",
		'o_announcement_heading'	=> "'".$lang_install['Default announce heading']."'",
		'o_announcement_message'	=> "'".$lang_install['Default announce message']."'",
		'o_rules'					=> "'0'",
		'o_rules_message'			=> "'".$lang_install['Default rules']."'",
		'o_maintenance'				=> "'0'",
		'o_maintenance_message'		=> "'".$lang_admin_settings['Maintenance message default']."'",
		'o_default_dst'				=> "'0'",
		'p_message_bbcode'			=> "'1'",
		'p_message_img_tag'			=> "'1'",
		'p_message_all_caps'		=> "'1'",
		'p_subject_all_caps'		=> "'1'",
		'p_sig_all_caps'			=> "'1'",
		'p_sig_bbcode'				=> "'1'",
		'p_sig_img_tag'				=> "'0'",
		'p_sig_length'				=> "'400'",
		'p_sig_lines'				=> "'4'",
		'p_allow_banned_email'		=> "'0'",
		'p_allow_dupe_email'		=> "'0'",
		'p_force_guest_email'		=> "'1'",
		'o_show_moderators'			=> "'0'",
		'o_mask_passwords'			=> "'1'"
	);

	foreach ($config as $conf_name => $conf_value)
	{
		$query = array(
			'INSERT'	=> 'conf_name, conf_value',
			'INTO'		=> 'config',
			'VALUES'	=> '\''.$conf_name.'\', '.$conf_value.''
		);

		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	}

	$DBLayer->end_transaction();


	$alerts = array();

	// Check if the cache directory is writable and clear cache dir
	if (is_writable(SITE_ROOT.'cache/'))
	{
		$cache_dir = dir(SITE_ROOT.'cache/');
		if ($cache_dir)
		{
			while (($entry = $cache_dir->read()) !== false)
			{
				if (substr($entry, strlen($entry)-4) == '.php')
					@unlink(SITE_ROOT.'cache/'.$entry);
			}
			$cache_dir->close();
		}
	}
	else
	{
		$alerts[] = '<li><span>'.$lang_install['No cache write'].'</span></li>';
	}

	// Check if default avatar directory is writable
	if (!is_writable(SITE_ROOT.'img/avatars/'))
		$alerts[] = '<li><span>'.$lang_install['No avatar write'].'</span></li>';

	// Check if we disabled uploading avatars because file_uploads was disabled
	if ($avatars == '0')
		$alerts[] = '<li><span>'.$lang_install['File upload alert'].'</span></li>';

	// Add some random bytes at the end of the cookie name to prevent collisions
	$cookie_name = 'site_cookie_'.random_key(6, false, true);

	/// Generate the config.php file data
	$config = generate_config_file();

	// Attempt to write config.php and serve it up for download if writing fails
	$written = false;
	if (is_writable(SITE_ROOT))
	{
		$fh = @fopen(SITE_ROOT.'config.php', 'wb');
		if ($fh)
		{
			fwrite($fh, $config);
			fclose($fh);

			$written = true;
		}
	}
	
	define('SM_INSTALL', 1);
	require SITE_ROOT.'include/xml.php';

?>
<!DOCTYPE html>
 <html lang="en" dir="ltr">
<head>
	<meta charset="utf-8" />
	<title>SwiftManager Installation</title>
	<link rel="stylesheet" type="text/css" href="<?php echo SITE_ROOT ?>style/main.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo SITE_ROOT ?>vendor/bootstrap/css/bootstrap.min.css" />
</head>
<body>

	<div class="card mb-3">
		<div class="card-body">

<?php if (!empty($alerts)): ?>
			<div class="alert alert-danger" role="alert">
				<h5 class="alert-heading">Errors</h5>
				<hr class="my-1">
				<p class="warn"><strong><?php echo $lang_install['Warning'] ?></strong></p>
				<ul><?php echo implode("\n\t\t\t\t", $alerts)."\n" ?></ul>
			</div>
<?php endif;

if (!$written)
{
?>
			<div class="alert alert-danger" role="alert">
				<h5 class="alert-heading">Warning</h5>
				<hr class="my-1">
				<p class="warn"><?php printf($lang_install['No write info 2'], '<a href="'.SITE_ROOT.'index.php">'.$lang_install['Go to index'].'</a>') ?></p>
			</div>
			<form method="post" accept-charset="utf-8" action="install.php">
				<div class="hidden">
					<input type="hidden" name="generate_config" value="1" />
					<input type="hidden" name="db_type" value="<?php echo $db_type ?>" />
					<input type="hidden" name="db_host" value="<?php echo $db_host ?>" />
					<input type="hidden" name="db_name" value="<?php echo html_encode($db_name) ?>" />
					<input type="hidden" name="db_username" value="<?php echo html_encode($db_username) ?>" />
					<input type="hidden" name="db_password" value="<?php echo html_encode($db_password) ?>" />
					<input type="hidden" name="db_prefix" value="<?php echo html_encode($db_prefix) ?>" />
					<input type="hidden" name="base_url" value="<?php echo html_encode($base_url) ?>" />
					<input type="hidden" name="cookie_name" value="<?php echo html_encode($cookie_name) ?>" />
				</div>
				<button type="submit" class="btn btn-sm btn-primary"><?php echo $lang_install['Download config'] ?></button>
			</form>
<?php
}
else
{
?>
			<div class="alert alert-success" role="alert">
				<h5 class="alert-heading">Install completed!</h5>
				<hr class="my-1">
				<p class="warn"><?php printf($lang_install['Write info'], '') ?></p>
				<p><a href="<?php echo SITE_ROOT ?>index.php" class="btn btn-sm btn-primary text-white">Go to home page</a></p>
			</div>
<?php
}
?>
		</div>
	</div>
</body>
</html>
<?php
}
