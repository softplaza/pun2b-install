<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftConfig
 */

class Config
{
	// Configurations information
	private $config = [];
	
	function __construct()
	{
		global $Cachinger;

		// Load cached config
		if (file_exists(SPM_CACHE_DIR.'config_info.php'))
			include SPM_CACHE_DIR.'config_info.php';

		if (!defined('CACHE_CONFIG_INFO_LOADED'))
		{
			$Cachinger->gen_config();

			require SPM_CACHE_DIR.'config_info.php';	
		}

		if (isset($config_info))
			$this->setAll($config_info);
		else
			$this->setFromDB();
	}

	// Set configuration info as key => val
    function add($key, $value) {
        $this->config[$key] = $value;
    }

	// Get config information by key
    function get($key) {
        return isset( $this->config[$key] ) ? $this->config[$key] : null;
    }

	// Set config as array
    function setAll($array) {
        $this->config = $array;
    }

	// Check if key exists
    function key_exists($key) {
		return (array_key_exists($key, $this->config) ? true : false);
    }

	// Get the forum config from the DB
	function setFromDB() {
		$query = array(
			'SELECT'	=> 'c.*',
			'FROM'		=> 'config AS c'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$output = array();
		while ($cur_config_item = $DBLayer->fetch_assoc($result))
			$output[$cur_config_item['conf_name']] = $cur_config_item['conf_value'];
		
		$this->setAll($output);
	}

	// Update option using $_POST['form'] as key => val
	// as form use name="form[custom_option_key]"
	function update($form = '', $prefix = 'o_')
	{
		global $DBLayer, $Cachinger;
		
		$form = ($form != '') ? array_map('trim', $form) : array_map('trim', $_POST['form']);
		foreach ($form as $key => $input)
		{
			// Only update option values that have changed
			if ($this->key_exists($prefix.$key) && $this->get($prefix.$key) != $input)
			{
				if ($input != '' || is_int($input))
					$value = '\''.$DBLayer->escape($input).'\'';
				else
					$value = 'NULL';
	
				$query = array(
					'UPDATE'	=> 'config',
					'SET'		=> 'conf_value='.$value,
					'WHERE'		=> 'conf_name=\''.$prefix.$DBLayer->escape($key).'\''
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				
				// Regenerate the Config cache
				$Cachinger->clear('config_info.php');
				$Cachinger->gen_config();
			}
		}
	}
}
