<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

//DO NOT REMOVE UNTIL CONFIGURATE TIME-ZONE
date_default_timezone_set('America/Los_Angeles');

$db_type = 'mysqli';
$db_host = 'localhost';
$db_name = 'test';
$db_username = 'root';
$db_password = '';
$db_prefix = '';
$p_connect = false;

define('BASE_URL', 'https://swiftmanager.localhost/dev');

$cookie_name = 'forum_cookie_0aafcc';
$cookie_domain = '';
$cookie_path = '/';
$cookie_secure = 0;

//define('FORUM', 1);
define('DB_CONFIG', 1);

// Enable DEBUG mode by removing // from the following line
define('SPM_DEBUG', 1);

// Enable show DB Queries mode by removing // from the following line
//define('SPM_SHOW_QUERIES', 1);

// Disable email from server
//define('SWIFT_DISABLE_EMAIL', 1);

// Display all registered hooks
//define('SWIFT_DISPLAY_REGISTERED_HOOK', 1);

// Enable IDNA support by removing // from the following line
//define('SPM_ENABLE_IDNA', 1);

// Disable CSRF checking by removing // from the following line
//define('SPM_DISABLE_CSRF_CONFIRM', 1);

// Disable hooks (extensions) by removing // from the following line
//define('SPM_DISABLE_HOOKS', 1);

// Disable output buffering by removing // from the following line
//define('SPM_DISABLE_BUFFERING', 1);

// Disable async JS loader by removing // from the following line
//define('SPM_DISABLE_ASYNC_JS_LOADER', 1);

// Disable extensions version check by removing // from the following line
//define('SPM_DISABLE_EXTENSIONS_VERSION_CHECK', 1);

// SQLite3 busy timeout -> after waiting for that time we get 'db is locked' error (in msec)
//define('SPM_SQLITE3_BUSY_TIMEOUT', 10000);

// SQLite3 WAL mode has better control over concurrency. Source: https://www.sqlite.org/wal.html
//define('SPM_SQLITE3_WAL_ON', 1);