<?php
/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

// Make sure we have built in support for MySQL
if (!function_exists('mysqli_connect'))
	exit('This PHP environment doesn\'t have Improved MySQL (mysqli) support built in. Improved MySQL support is required if you want to use a MySQL 4.1 (or later) database to run this forum. Consult the PHP documentation for further assistance.');


class DBLayer
{
	var $prefix;
	var $link_id;
	var $query_result;

	var $saved_queries = array();
	var $num_queries = 0;

	var $datatype_transformations = array(
		'/^SERIAL$/'	=>	'INT(10) UNSIGNED AUTO_INCREMENT'
	);

	var $quotes = '`';

	function __construct($db_host, $db_username, $db_password, $db_name, $db_prefix, $foo)
	{
		$this->prefix = $db_prefix;

		// Was a custom port supplied with $db_host?
		if (strpos($db_host, ':') !== false)
			list($db_host, $db_port) = explode(':', $db_host);

		if (isset($db_port))
			$this->link_id = @mysqli_connect($db_host, $db_username, $db_password, $db_name, $db_port);
		else
			$this->link_id = @mysqli_connect($db_host, $db_username, $db_password, $db_name);

		if (!$this->link_id)
			error('Unable to connect to MySQL and select database.<br />MySQL reported: '.mysqli_connect_error(), __FILE__, __LINE__);

		// Setup the client-server character set (UTF-8)
		if (!defined('SPM_NO_SET_NAMES'))
			$this->set_names('utf8');
	}

	function start_transaction(){return;}

	function end_transaction(){return;}

	function query($sql, $unbuffered = false)
	{
		if (strlen($sql) > DATABASE_QUERY_MAXIMUM_LENGTH)
			exit('Insane query. Aborting.');

		if (defined('SPM_SHOW_QUERIES') || defined('SPM_DEBUG'))
			$q_start = get_microtime();

		$this->query_result = @mysqli_query($this->link_id, $sql);

		if ($this->query_result)
		{
			if (defined('SPM_SHOW_QUERIES') || defined('SPM_DEBUG'))
				$this->saved_queries[] = array($sql, sprintf('%.5f', get_microtime() - $q_start));

			++$this->num_queries;

			return $this->query_result;
		}
		else
		{
			if (defined('SPM_SHOW_QUERIES') || defined('SPM_DEBUG'))
				$this->saved_queries[] = array($sql, 0);

			return false;
		}
	}

	function query_build($query, $return_query_string = false, $unbuffered = false)
	{
		$sql = '';

		if (isset($query['SELECT']))
		{
			$sql = 'SELECT '.$query['SELECT'].' FROM '.(isset($query['PARAMS']['NO_PREFIX']) ? '' : $this->prefix).$query['FROM'];

			if (isset($query['JOINS']))
			{
				foreach ($query['JOINS'] as $cur_join)
					$sql .= ' '.key($cur_join).' '.(isset($query['PARAMS']['NO_PREFIX']) ? '' : $this->prefix).current($cur_join).' ON '.$cur_join['ON'];
			}

			if (!empty($query['WHERE']))
				$sql .= ' WHERE '.$query['WHERE'];
			if (!empty($query['GROUP BY']))
				$sql .= ' GROUP BY '.$query['GROUP BY'];
			if (!empty($query['HAVING']))
				$sql .= ' HAVING '.$query['HAVING'];
			if (!empty($query['ORDER BY']))
				$sql .= ' ORDER BY '.$query['ORDER BY'];
			if (!empty($query['LIMIT']))
				$sql .= ' LIMIT '.$query['LIMIT'];
		}
		else if (isset($query['INSERT']))
		{
			$sql = 'INSERT INTO '.(isset($query['PARAMS']['NO_PREFIX']) ? '' : $this->prefix).$query['INTO'];

			if (!empty($query['INSERT']))
				$sql .= ' ('.$query['INSERT'].')';

			if (is_array($query['VALUES']))
				$sql .= ' VALUES('.implode('),(', $query['VALUES']).')';
			else
				$sql .= ' VALUES('.$query['VALUES'].')';
		}
		else if (isset($query['UPDATE']))
		{
			$query['UPDATE'] = (isset($query['PARAMS']['NO_PREFIX']) ? '' : $this->prefix).$query['UPDATE'];

			$sql = 'UPDATE '.$query['UPDATE'].' SET '.$query['SET'];

			if (!empty($query['WHERE']))
				$sql .= ' WHERE '.$query['WHERE'];
		}
		else if (isset($query['DELETE']))
		{
			$sql = 'DELETE FROM '.(isset($query['PARAMS']['NO_PREFIX']) ? '' : $this->prefix).$query['DELETE'];

			if (!empty($query['WHERE']))
				$sql .= ' WHERE '.$query['WHERE'];
		}
		else if (isset($query['REPLACE']))
		{
			$sql = 'REPLACE INTO '.(isset($query['PARAMS']['NO_PREFIX']) ? '' : $this->prefix).$query['INTO'];

			if (!empty($query['REPLACE']))
				$sql .= ' ('.$query['REPLACE'].')';

			$sql .= ' VALUES('.$query['VALUES'].')';
		}

		return ($return_query_string) ? $sql : $this->query($sql, $unbuffered);
	}

	function result($query_id = 0, $row = 0, $col = 0)
	{
		if ($query_id)
		{
			if ($row)
				@mysqli_data_seek($query_id, $row);

			$cur_row = @mysqli_fetch_row($query_id);

			return isset($cur_row[$col]) ? $cur_row[$col] : false;
		}
		else
			return false;
	}

	function fetch_assoc($query_id = 0)
	{
		return ($query_id) ? @mysqli_fetch_assoc($query_id) : false;
	}

	function fetch_row($query_id = 0)
	{
		return ($query_id) ? @mysqli_fetch_row($query_id) : false;
	}

	function num_rows($query_id = 0)
	{
		return ($query_id) ? @mysqli_num_rows($query_id) : false;
	}

	function affected_rows()
	{
		return ($this->link_id) ? @mysqli_affected_rows($this->link_id) : false;
	}

	function insert_id()
	{
		return ($this->link_id) ? @mysqli_insert_id($this->link_id) : false;
	}

	function get_num_queries()
	{
		return $this->num_queries;
	}

	function get_saved_queries()
	{
		return $this->saved_queries;
	}

	function free_result($query_id = false)
	{
		return ($query_id) ? @mysqli_free_result($query_id) : false;
	}

	function escape($str)
	{
		return is_array($str) ? '' : mysqli_real_escape_string($this->link_id, $str);
	}

	function error()
	{
		$result['error_sql'] = @current(@end($this->saved_queries));
		$result['error_no'] = @mysqli_errno($this->link_id);
		$result['error_msg'] = @mysqli_error($this->link_id);

		return $result;
	}

	function close()
	{
		if ($this->link_id)
		{
			if ($this->query_result instanceof mysqli_result)
				@mysqli_free_result($this->query_result);

			$result = @mysqli_close($this->link_id);

			$this->link_id = false;

			return $result;
		}
		else
			return false;
	}

	function set_names($names)
	{
		return mysqli_set_charset($this->link_id, $names);
	}

	function get_version()
	{
		$result = $this->query('SELECT VERSION()');

		return array(
			'name'		=> 'MySQL Improved',
			'version'	=> preg_replace('/^([^-]+).*$/', '\\1', $this->result($result))
		);
	}

	function table_exists($table_name, $no_prefix = false)
	{
		$result = $this->query('SHOW TABLES LIKE \''.($no_prefix ? '' : $this->prefix).$this->escape($table_name).'\'');
		return $this->num_rows($result) > 0;
	}

	function field_exists($table_name, $field_name, $no_prefix = false)
	{
		$result = $this->query('SHOW COLUMNS FROM '.($no_prefix ? '' : $this->prefix).$table_name.' LIKE \''.$this->escape($field_name).'\'');
		return $this->num_rows($result) > 0;
	}

	function index_exists($table_name, $index_name, $no_prefix = false)
	{
		$exists = false;

		$result = $this->query('SHOW INDEX FROM '.($no_prefix ? '' : $this->prefix).$table_name);
		while ($cur_index = $this->fetch_assoc($result))
		{
			if ($cur_index['Key_name'] == ($no_prefix ? '' : $this->prefix).$table_name.'_'.$index_name)
			{
				$exists = true;
				break;
			}
		}

		return $exists;
	}

	function create_table($table_name, $schema, $no_prefix = false)
	{
		if ($this->table_exists($table_name, $no_prefix))
			return;

		$query = 'CREATE TABLE '.'`'.($no_prefix ? '' : $this->prefix).$table_name."` (\n";

		// Go through every schema element and add it to the query
		foreach ($schema['FIELDS'] as $field_name => $field_data)
		{
			$field_data['datatype'] = preg_replace(array_keys($this->datatype_transformations), array_values($this->datatype_transformations), $field_data['datatype']);

			$query .= '`'.$field_name.'` '.$field_data['datatype'];

			if (isset($field_data['collation']))
				$query .= 'CHARACTER SET utf8 COLLATE utf8_'.$field_data['collation'];

			if (!$field_data['allow_null'])
				$query .= ' NOT NULL';

			if (isset($field_data['default']))
				$query .= ' DEFAULT '.$field_data['default'];

			$query .= ",\n";
		}

		// If we have a primary key, add it
		if (isset($schema['PRIMARY KEY']))
			$query .= 'PRIMARY KEY ('.$this->repl_array($schema['PRIMARY KEY']).'),'."\n";

		// Add unique keys
		if (isset($schema['UNIQUE KEYS']))
		{
			foreach ($schema['UNIQUE KEYS'] as $key_name => $key_fields)
				$query .= 'UNIQUE KEY '.($no_prefix ? '' : $this->prefix).$table_name.'_'.$key_name.'('.$this->repl_array($key_fields).'),'."\n";
		}

		// Add indexes
		if (isset($schema['INDEXES']))
		{
			foreach ($schema['INDEXES'] as $index_name => $index_fields)
				$query .= 'KEY '.($no_prefix ? '' : $this->prefix).$table_name.'_'.$index_name.'('.$this->repl_array($index_fields).'),'."\n";
		}

		// We remove the last two characters (a newline and a comma) and add on the ending
		$query = substr($query, 0, strlen($query) - 2)."\n".') ENGINE = '.(isset($schema['ENGINE']) ? $schema['ENGINE'] : 'MyISAM').' CHARACTER SET utf8';

		$this->query($query) or error(__FILE__, __LINE__);
	}

	function drop_table($table_name, $no_prefix = false)
	{
		if (!$this->table_exists($table_name, $no_prefix))
			return;

		$this->query('DROP TABLE '.'`'.($no_prefix ? '' : $this->prefix).$table_name.'`') or error(__FILE__, __LINE__);
	}

	function add_field($table_name, $field_name, $field_type, $allow_null, $default_value = null, $after_field = null, $no_prefix = false)
	{
		if ($this->field_exists($table_name, $field_name, $no_prefix))
			return;

		$field_type = preg_replace(array_keys($this->datatype_transformations), array_values($this->datatype_transformations), $field_type);

		if ($default_value !== null && !is_int($default_value) && !is_float($default_value))
			$default_value = '\''.$this->escape($default_value).'\'';

		$this->query('ALTER TABLE '.'`'.($no_prefix ? '' : $this->prefix).$table_name.'` ADD `'.$field_name.'` '.$field_type.($allow_null ? ' ' : ' NOT NULL').($default_value !== null ? ' DEFAULT '.$default_value : ' ').($after_field !== null ? ' AFTER `'.$after_field.'`' : '')) or error(__FILE__, __LINE__);
	}

	function alter_field($table_name, $field_name, $field_type, $allow_null, $default_value = null, $after_field = null, $no_prefix = false)
	{
		if (!$this->field_exists($table_name, $field_name, $no_prefix))
			return;

		$field_type = preg_replace(array_keys($this->datatype_transformations), array_values($this->datatype_transformations), $field_type);

		if ($default_value !== null && !is_int($default_value) && !is_float($default_value))
			$default_value = '\''.$this->escape($default_value).'\'';

		$this->query('ALTER TABLE '.'`'.($no_prefix ? '' : $this->prefix).$table_name.'` MODIFY `'.$field_name.'` '.$field_type.($allow_null ? ' ' : ' NOT NULL').($default_value !== null ? ' DEFAULT '.$default_value : ' ').($after_field !== null ? ' AFTER `'.$after_field.'`' : '')) or error(__FILE__, __LINE__);
	}

	function drop_field($table_name, $field_name, $no_prefix = false)
	{
		if (!$this->field_exists($table_name, $field_name, $no_prefix))
			return;

		$this->query('ALTER TABLE '.'`'.($no_prefix ? '' : $this->prefix).$table_name.'` DROP `'.$field_name.'`') or error(__FILE__, __LINE__);
	}

	function add_index($table_name, $index_name, $index_fields, $unique = false, $no_prefix = false)
	{
		if ($this->index_exists($table_name, $index_name, $no_prefix))
			return;

		$this->query('ALTER TABLE '.'`'.($no_prefix ? '' : $this->prefix).$table_name.'` ADD '.($unique ? 'UNIQUE ' : '').'INDEX '.($no_prefix ? '' : $this->prefix).$table_name.'_'.$index_name.' ('.$this->repl_array($index_fields).')') or error(__FILE__, __LINE__);
	}

	function drop_index($table_name, $index_name, $no_prefix = false)
	{
		if (!$this->index_exists($table_name, $index_name, $no_prefix))
			return;

		$this->query('ALTER TABLE '.'`'.($no_prefix ? '' : $this->prefix).$table_name.'` DROP INDEX '.($no_prefix ? '' : $this->prefix).$table_name.'_'.$index_name) or error(__FILE__, __LINE__);
	}

	function repl_array($arr)
	{
		foreach ($arr as &$value) {
			if (false !== strpos($value, '(') && preg_match('%^(.*)\s*(\(\d+\))$%', $value, $matches)) {
				$value = "`{$matches[1]}`{$matches[2]}";
			} else {
				$value = "`{$value}`";
			}
			unset($value);
		}
		return implode(',', $arr);
	}
	
	// START SwiftManager functions
	// Add config value to config table
	// Warning!
	// This function dont refresh config cache - use "$Cachinger->clear()" if
	// call this function outside install/uninstall extension manifest section

	function next_id()
	{
		// in-progress
	}

	function config_add($name, $value)
	{
		global $Config;

		if (!$Config->key_exists($name))
		{
			$query = array(
				'INSERT'	=> 'conf_name, conf_value',
				'INTO'		=> 'config',
				'VALUES'	=> '\''.$name.'\', \''.$value.'\''
			);
			$this->query_build($query) or error(__FILE__, __LINE__);
		}
	}

	// Remove config value from config table
	// Warning!
	// This function dont refresh config cache - use "$Cachinger->clear()" if
	// call this function outside install/uninstall extension manifest section
	function config_remove($name)
	{
		if (is_array($name) && count($name) > 0)
		{
			if (!function_exists('clean_conf_names'))
			{
				function clean_conf_names($n)
				{
					return '\''.$this->escape($n).'\'';
				}
			}

			$name = array_map('clean_conf_names', $name);

			$query = array(
				'DELETE'	=> 'config',
				'WHERE'		=> 'conf_name IN ('.implode(',', $name).')',
			);
			$this->query_build($query) or error(__FILE__, __LINE__);
		}
		else if (!empty($name))
		{
			$query = array(
				'DELETE'	=> 'config',
				'WHERE'		=> 'conf_name=\''.$this->escape($name).'\''
			);
			$this->query_build($query) or error(__FILE__, __LINE__);
		}
	}

	function configUpdate($array)
	{
		
	}

	function select($table_name, $where = '')
	{
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> $table_name,
		);
		if ($where != '' && is_numeric($where))
			$query['WHERE'] = 'id='.$where;
		else
			$query['WHERE'] = $where;
		
		$result = $this->query_build($query) or error(__FILE__, __LINE__);
		return $this->fetch_assoc($result);
	}
	
	function select_all($table_name, $where = null, $order_by = null)
	{
		$output = [];
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> $table_name,
		);

		if ($where !== null)
			$query['WHERE'] = $where;
		if ($order_by !== null)
			$query['ORDER BY'] = $order_by;
		
		$result = $this->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $this->fetch_assoc($result)) {
			$output[] = $row;
		}

		return $output;
	}

	function insert($table_name, $data)
	{
		$new_id = 0;
		if ($table_name != '' && !empty($data))
		{
			$keys = $values = array();
			foreach($data as $key => $val)
			{
				$keys[] = $key;
				$values[] = (is_numeric($val) ? $val : '\''.$this->escape($val).'\'');
			}
			
			if (!empty($keys) && !empty($values))
			{
				$query = array(
					'INSERT'	=> implode(',', $keys),
					'INTO'		=> $table_name,
					'VALUES'	=> implode(',', $values));
				$this->query_build($query) or error(__FILE__, __LINE__);
				$new_id = $this->insert_id();
			}
		}
		
		if ($new_id > 0)
			return $new_id;
	}

	// Deprecated  insert_values
	function insert_values($table_name, $data)
	{
		$new_id = 0;
		if ($table_name != '' && !empty($data))
		{
			$keys = $values = array();
			foreach($data as $key => $val)
			{
				$keys[] = $key;
				$values[] = '\''.$this->escape($val).'\'';
			}
			
			if (!empty($keys) && !empty($values))
			{
				$query = array(
					'INSERT'	=> implode(',', $keys),
					'INTO'		=> $table_name,
					'VALUES'	=> implode(',', $values));
				$this->query_build($query) or error(__FILE__, __LINE__);
				$new_id = $this->insert_id();
			}
		}
		
		if ($new_id > 0)
			return $new_id;
	}
	
	function update($table_name, $data, $where = '')
	{
		if (($table_name != '') && !empty($data))
		{
			$set_str = '';
			if (is_array($data))
			{
				foreach($data as $key => $val)
				{
					if ($set_str == '')
						$set_str = $key.'=\''.$this->escape($val).'\'';
					else
						$set_str .= ', '.$key.'=\''.$this->escape($val).'\'';
				}
			}
			else
				$set_str = $data;

			if ($set_str != '')
			{
				$query = array(
					'UPDATE'	=> $table_name,
					'SET'		=> $set_str,
				);
				if ($where != '' && is_numeric($where))
					$query['WHERE'] = 'id='.$where;
				else
					$query['WHERE'] = $where;
				
				$this->query_build($query) or error(__FILE__, __LINE__);
			}
		}
	}

	function update_values($table_name, $id, $data)
	{
		if (($table_name != '') && ($id > 0) && !empty($data))
		{
			$set_str = '';
			if (is_array($data))
			{
				foreach($data as $key => $val)
				{
					if ($set_str == '')
						$set_str = $key.'=\''.$this->escape($val).'\'';
					else
						$set_str .= ', '.$key.'=\''.$this->escape($val).'\'';
				}
			}
			else
				$set_str = $data;
			
			if ($set_str != '')
			{
				$query = array(
					'UPDATE'	=> $table_name,
					'SET'		=> $set_str,
					'WHERE'		=> 'id='.$id
				);
				$this->query_build($query) or error(__FILE__, __LINE__);
			}
		}
	}
	
	function delete($table_name, $where = '')
	{
		if ($where != '')
		{
			$query = array(
				'DELETE'	=> $table_name,
			);

			if (is_numeric($where))
				$query['WHERE'] = 'id='.$where;
			else
				$query['WHERE'] = $where;

			$this->query_build($query) or error(__FILE__, __LINE__);
		}
	}
	
	// Heplpers
	function getNumRows($table_name, $where = '')
	{
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> $table_name,
		);
		if ($where != '' && is_numeric($where))
			$query['WHERE'] = 'id='.$where;
		else
			$query['WHERE'] = $where;
		
		$result = $this->query_build($query) or error(__FILE__, __LINE__);
		return $this->num_rows($result);
	}

	// DATA TYPES 
	function dt_serial($null = false)
	{
		$array = array(
			'datatype'		=> 'SERIAL',
			'allow_null'	=> $null
		);
		
		return $array;
	}
	
	function dt_int($datatype = 'INT(10) UNSIGNED', $null = false, $default = '0')
	{
		$array = array(
			'datatype'		=> $datatype,
			'allow_null'	=> $null,
			'default'		=> $default
		);
		
		return $array;
	}
	
	function dt_varchar($datatype = 'VARCHAR(255)', $null = false, $default = '\'\'')
	{
		$array = array(
			'datatype'		=> $datatype,
			'allow_null'	=> $null,
//			'default'		=> $default // replace to:
		);
		
		if (!$null)
			$array['default'] = $default;
		
		return $array;
	}
	
	function dt_text($datatype = 'TEXT', $null = false)
	{
		$array = array(
			'datatype'		=> $datatype,
			'allow_null'	=> $null
		);
		
		return $array;
	}
	
	function dt_date($datatype = 'DATE', $null = false, $default = '\'1000-01-01\'')
	{
		$array = array(
			'datatype'		=> $datatype,
			'allow_null'	=> $null,
			'default'		=> $default
		);
		
		return $array;
	}

	function dt_time($datatype = 'TIME', $null = false, $default = '\'00:00:00\'')
	{
		$array = array(
			'datatype'		=> $datatype,
			'allow_null'	=> $null,
			'default'		=> $default
		);
		
		return $array;
	}
	
	function dt_datetime($datatype = 'DATETIME', $null = false, $default = '\'1000-01-01 00:00:00\'')
	{
		$array = array(
			'datatype'		=> $datatype,
			'allow_null'	=> $null,
			'default'		=> $default
		);
		
		return $array;
	}

	/* Additional functions */
	// From -> To
	function copy_table($table_from, $table_to)
	{
		$from_data = $this->select_all($table_from);
	
		foreach($from_data as $row => $data)
		{
			$new_data = [];
			foreach($data as $key => $val)
			{
				if ($this->field_exists($table_to, $key))
					$new_data[$key] = $val;
			}
			if (!empty($new_data))
				$this->insert_values($table_to, $new_data);
		}
	}

}
