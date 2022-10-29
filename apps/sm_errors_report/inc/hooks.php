<?php

if (!defined('DB_CONFIG')) die();

function sm_errors_report_co_modify_url_scheme()
{
    global $URL;
 
    $URL->add('sm_errors_report', 'apps/sm_errors_report/reports.php');
}

function sm_errors_report_IncludeCommon()
{
    global $User, $URL, $Config, $SwiftMenu;

    if ($User->is_admin())
        $SwiftMenu->addItem(['title' => 'Errors Report', 'link' => $URL->link('sm_errors_report'), 'id' => 'sm_errors_report', 'parent_id' => 'admin']);
}

function sm_errors_report_IncludeFunctionsMessageOutputEnd()
{
	global $DBLayer, $User, $Config, $flash_message;
	
	$user_id = isset($User) ? $User->get('id') : 0;

    if ($user_id > 2)
    {
        $time_now = time();
        $flash_message = isset($flash_message) ? $flash_message : 'no message';

        $query = array(
            'INSERT'	=> 'user_id, user_ip, error_time, error_type, cur_url, message',
            'INTO'		=> 'sm_errors_reports',
            'VALUES'	=> 
                '\''.$DBLayer->escape($user_id).'\',
                \''.$DBLayer->escape(get_remote_address()).'\',
                '.$time_now.',
                \'Message\',
                \''.$DBLayer->escape(get_current_url()).'\',
                \''.$DBLayer->escape($flash_message).'\'',
        );
        $DBLayer->query_build($query);
    }
}

// Custom functions
function spm_error_handler($code, $description, $file = null, $line = null, $context = null)
{
    $displayErrors = ini_get("display_errors");
    $displayErrors = strtolower($displayErrors);
    if (error_reporting() === 0 || $displayErrors === "on") {
        return false;
    }
    list($error, $log) = spm_map_error_code($code);
    $data = array(
        'type' => $log,
        'code' => $code,
        'error' => $error,
        'description' => $description,
        'file' => $file,
        'line' => $line,
        'context' => $context,
        'path' => $file,
        'message' => $error . ' (' . $code . '): ' . $description . ' in [' . $file . ', line ' . $line . ']'
    );
    
    spm_record_log($data);

    echo $data['message'];
}

function spm_record_log($data)
{
	global $DBLayer, $User, $Config;
	
	$user_id = isset($User) ? $User->get('id') : 0;
	$time_now = time();
	$query = array(
		'INSERT'	=> 'user_id, user_ip, error_time, error_type, cur_url, message',
		'INTO'		=> 'sm_errors_reports',
		'VALUES'	=> 
			'\''.$DBLayer->escape($user_id).'\',
			\''.$DBLayer->escape(get_remote_address()).'\',
			'.$time_now.',
			\''.$DBLayer->escape($data['type']).'\',
			\''.$DBLayer->escape($data['path']).'\',
			\''.$DBLayer->escape($data['message']).'\'',
	);

    if (!defined('SWIFT_ERRORS_REPORT'))
    {
        $DBLayer->query_build($query);
        define('SWIFT_ERRORS_REPORT', 1);
    }
	
	if ($data['type'] == 1)
    {
        $SwiftMailer = new SwiftMailer;
        $SwiftMailer->send($Config->get('o_admin_email'), 'Fatal Error', $data['message']);
    }
}

function spm_map_error_code($code) {
    $error = $log = null;
    switch ($code) {
        case E_PARSE:
            $error = 'Parse';
            $log = LOG_ERR;
            break;
        case E_ERROR:
            $error = 'Error';
            $log = LOG_ERR;
            break;
        case E_CORE_ERROR:
            $error = 'Code Error';
            $log = LOG_ERR;
            break;
        case E_COMPILE_ERROR:
            $error = 'Compile Error';
            $log = LOG_ERR;
            break;
        case E_USER_ERROR:
            $error = 'Fatal Error';
            $log = LOG_ERR;
            break;
        case E_WARNING:
            $error = 'Warning';
            $log = LOG_ERR;
            break;
        case E_USER_WARNING:
            $error = 'Warning';
            $log = LOG_ERR;
            break;
        case E_COMPILE_WARNING:
            $error = 'Warning';
            $log = LOG_ERR;
            break;
        case E_RECOVERABLE_ERROR:
            $error = 'Warning';
            $log = LOG_WARNING;
            break;
        case E_NOTICE:
            $error = 'Notice';
            $log = LOG_ERR;
            break;
        case E_USER_NOTICE:
            $error = 'Notice';
            $log = LOG_NOTICE;
            break;
        case E_STRICT:
            $error = 'Strict';
            $log = LOG_NOTICE;
            break;
        case E_DEPRECATED:
            $error = 'Deprecated';
            $log = LOG_ERR;
            break;
        case E_USER_DEPRECATED:
            $error = 'Deprecated';
            $log = LOG_NOTICE;
            break;
        default :
            $error = 'Default';
            $log = LOG_NOTICE;
            break;
    }
    
    return array($error, $log);
}

function spm_shutdown()
{
    $error = error_get_last();
    if (!empty($error) && $error['type'] === E_ERROR)
    {
        // fatal error has occured
        $data = [
        	'type' => $error['type'],
        	'path' => $error['file'],
			'file' => $error['file'],
			'line' => $error['line'],
        	'message' => $error['message']
        ];

        spm_record_log($data);
    }
}


function sm_errors_report_es_essentials()
{
    //ini_set('error_reporting', E_ALL);
    //ini_set('display_errors', 1);
    //ini_set('display_startup_errors', 1);

    //error_reporting(E_ALL);
    //ini_set("display_errors", "off");
    ini_set('display_errors', false);

    register_shutdown_function('spm_shutdown');

    //calling custom error handler
    //set_error_handler("spm_error_handler");
    
    //set_error_handler('spm_error_handler',E_ALL & ~E_NOTICE & ~E_USER_NOTICE);
    // Get all errors
    set_error_handler('spm_error_handler',-1 & ~E_NOTICE & ~E_USER_NOTICE);
}

// Catch system errors
function sm_errors_report_FooterEnd()
{
	global $DBLayer, $User, $Core;
	
    if (!empty($Core->errors))
    {
        $error_data = [
            'user_id'       => isset($User) ? $User->get('id') : 0,
            'user_ip'       => get_remote_address(),
            'error_time'    => time(),
            'error_type'    => 'System error',
            'cur_url'       => get_current_url(),
            'message'       => implode("\n", $Core->errors),
        ];
        $DBLayer->insert('sm_errors_reports', $error_data);
    }
}