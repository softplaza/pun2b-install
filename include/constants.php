<?php

// Define the version and database revision that this code was written for
define('SPM_NAME', 'Swift Project Manager');
define('SPM_VERSION', '1.4.4');
define('SPM_DB_REVISION', 5);
define('SPM_MYSQLI_MIN_VERSION', '10.4.25');
define('SPM_MYSQLI_MAX_VERSION', '10.4.27');
define('SPM_PHP_MIN_VERSION', '7.1');
define('SPM_PHP_MAX_VERSION', '8.0');

// Define avatars type
define('USER_AVATAR_NONE', 0);
define('USER_AVATAR_GIF', 1);
define('USER_AVATAR_JPG', 2);
define('USER_AVATAR_PNG', 3);

define('SUBJECT_MAXIMUM_LENGTH', 70);
define('DATABASE_QUERY_MAXIMUM_LENGTH', 140000);
define('SEARCH_MIN_WORD', 3);
define('SEARCH_MAX_WORD', 20);

define('USER_GROUP_UNVERIFIED', 0);
define('USER_GROUP_ADMIN', 1);
define('USER_GROUP_GUEST', 2);
