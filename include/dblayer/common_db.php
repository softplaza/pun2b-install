<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

// Make sure no one attempts to run this script "directly"
if (!defined('DB_CONFIG'))
	exit;

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
		error('\''.$db_type.'\' is not a valid database type. Please check settings in config.php.', __FILE__, __LINE__);
		break;
}

// Create the database adapter object (and open/connect to/select db)
$forum_db = $DBLayer = new DBLayer($db_host, $db_username, $db_password, $db_name, $db_prefix, $p_connect);
