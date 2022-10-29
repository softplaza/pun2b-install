<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$table_name = isset($_GET['table_name']) ? swift_trim($_GET['table_name']) : '';

if (!$User->is_admin())
	message($lang_common['No permission']);

// Load the admin.php language files
require SITE_ROOT.'lang/'.$User->get('language').'/admin_common.php';

if (file_exists('lang/'.$User->get('language').'.php'))
	require 'lang/'.$User->get('language').'.php';
else
	require 'lang/English.php';

$date_fields_arr = array(
	// Pest Control
	'created','reported','inspection_date','start_date','completion_date', 
	// 5840 Inspection
	'mois_report_date', 'mois_inspection_date', 'delivery_equip_date', 'pickup_equip_date', 'afcc_date', 'asb_test_date', 'rem_start_date', 'rem_end_date', 'cons_start_date', 'cons_end_date', 'moveout_date', 'movein_date', 'maintenance_date', 'final_performed_date',
	//Trees Projects
	'noticed_date', 'end_date', 'completion_date'
);

if (isset($_POST['upload_table']))
{
	$table_name = isset($_POST['table_name']) ? $_POST['table_name'] : '';
	$base_filename = basename($_FILES['csv_file']['name']);
	$file_ext = strtolower(strrchr($_FILES['csv_file']['name'], '.'));
	
	//check mime type
	if ($file_ext == '.csv')
	{
		if(move_uploaded_file($_FILES['csv_file']['tmp_name'], $base_filename))
		{
			if($db_type == 'sqlite3')
			{
				$i = 0;
				$result2 = $DBLayer->query("SELECT * FROM '".$table_name."'");
				$table_names_db = array();
				while ($table = $result2->columnName($i)) {
					$table_names_db[] = $table;
					$i++;
				}
			}
			else if ($db_type == 'mysqli') 
			{
				$results = $DBLayer->query("SELECT * FROM ".$table_name);
				$fields = $results->fetch_fields();
				$fields_count = count($fields);
				$table_names_db = array();
				for($i=0; $i < $fields_count; $i++){
					$table_names_db[] = $fields[$i]->name;
				}
			}
			else
				$Core->add_error('Can not supported type database');
			
			$csv_array = array_map('str_getcsv', file($base_filename));
			if (isset($csv_array[0]))
			{
				foreach($csv_array[0] as $key => $column)
				{
					$column = swift_trim(preg_replace(array('/[^a-z0-9_\s]/', '/[\s]+/'), array('', '_'), $column), '_');
					
					if(!in_array($column, $table_names_db))
					{
						$Core->add_error('The name of column <strong>'.$column.'</strong> in the database does not match the name of the column in the table. Please correct column name of your table and try upload again.');
					}
				}
			}
			
			if (empty($Core->errors))
			{
				if(isset($query['INSERT']))
					unset($query['INSERT']);
				
				$query = array();
				$query['INTO'] = $table_name;
				foreach($csv_array as $row => $rows)
				{
					//skeep header
					if ($row > 0) 
					{
						$first_col = true;
						foreach($rows as $col_id => $col_val)
						{
							$col_val = swift_trim($col_val);
							$cur_column = swift_trim(preg_replace(array('/[^a-z0-9_\s]/', '/[\s]+/'), array('', '_'), $csv_array[0][$col_id]), '_');
							if(in_array(strtolower($cur_column), $table_names_db) && $cur_column != 'id')
							{
								if (in_array($cur_column, $date_fields_arr))
									$col_val = strtotime($col_val);
									
								//for PEST CONTROL create hash
								if ($cur_column == 'link_hash') {
									$salt = random_key(12);
									$col_val = spm_hash(time(), $salt);;
								}
								
								// DRIVER SECTION for sm_territory
								if (in_array($cur_column, array('created', 'modified', 'contacted')) && $cur_column != '')
									$col_val = strtotime($col_val);
								else if ($cur_column == 'geocoded' && $col_val == 'Geocoded')
									$col_val = 1;
								
								// HCA Compliance Calendar
								if ($table_name == 'hca_cc_items')
								{
									if ($cur_column == 'frequency')
									{
										if ($col_val == 'Quarterly') $col_val = 1;
										else if ($col_val == 'Bi-Annual') $col_val = 2;
										else if ($col_val == 'Annual') $col_val = 3;
										else if ($col_val == 'Every 2 Years') $col_val = 4;
										else if ($col_val == 'Every 3 Years') $col_val = 5;
										else if ($col_val == 'Every 4 Years') $col_val = 6;
										else if ($col_val == 'Every 5 Years') $col_val = 7;
										else if ($col_val == 'Every 6 Years') $col_val = 8;
										else if ($col_val == 'Every 10 Years') $col_val = 12;
										else $col_val = 0;
									}
									else if ($cur_column == 'required_by')
									{
										if ($col_val == 'HCA') $col_val = 1;
										else if ($col_val == 'City') $col_val = 2;
										else if ($col_val == 'State') $col_val = 3;
										else $col_val = 0;
									}
									else if ($cur_column == 'department')
									{
										if ($col_val == 'Admin') $col_val = 1;
										else if ($col_val == 'Accounting') $col_val = 2;
										else if ($col_val == 'Compliance') $col_val = 3;
										else if ($col_val == 'HR') $col_val = 4;
										else if ($col_val == 'Landscaping') $col_val = 5;
										else if ($col_val == 'Maintenance') $col_val = 6;
										else if ($col_val == 'Marketing') $col_val = 7;
										else if ($col_val == 'Permits') $col_val = 8;
										else if ($col_val == 'Pest Control') $col_val = 9;
										else $col_val = 0;
									}
									else if (in_array($cur_column, array('date_last_completed', 'date_completed', 'date_due')))
									{
										if ($col_val != '')
											$col_val = format_date($col_val, 'Y-m-d');
									}
								}

								// for 5840 Projects
								if ($cur_column == 'email_status')
								{
									if ($col_val == 'SENT')
										$col_val = '1';
									else
										$col_val = '0';
								}
								else if ($cur_column == 'job_status')
								{
									if ($col_val == 'YES')
										$col_val = '3';
									else if ($col_val == 'ON HOLD')
										$col_val = '2';
									else
										$col_val = '1';
								}
								
								if ($first_col) {
									$query['INSERT'] = ''.$cur_column;
									$query['VALUES'] = '\''.$DBLayer->escape($col_val).'\'';
									$first_col = false;
								} else {
									$query['INSERT'] .= ', '.$cur_column;
									$query['VALUES'] .= ', \''.$DBLayer->escape($col_val).'\'';
								}
							}
						}
					}
					
					if (!empty($query['INSERT']) && !empty($query['VALUES']))
					{
						//add query
						$DBLayer->query_build($query) or error(__FILE__, __LINE__);
					}
				}
				
				// Add flash message
				$flash_message = 'Table uploaded';
				$FlashMessenger->add_info($flash_message);
				redirect($URL->link('sm_backup_tables'), $flash_message);
			}
			
			if (file_exists($base_filename))
				unlink($base_filename);
		}
		else
		{
			$Core->add_error('Can not upload or read .CSV file.');
		}
	
	}
	else
	{
		$Core->add_error('You are trying to upload a unsupported file type.');
	}

}

$tables = array();
if ($db_type == 'sqlite3')
{
	$result = $DBLayer->query("SELECT name FROM sqlite_master WHERE type='table';");
	while ($table = $result->fetchArray(SQLITE3_ASSOC)) {
		$tables[] = $table['name'];
	}
}
else if ($db_type == 'mysqli')
{
	$result = $DBLayer->query("SHOW TABLES");
	while($row = $result->fetch_array()){
//		if (!in_array($row[0], $params['db_exclude_tables'])){	// excluded tables
			$tables[] = $row[0];
//		}
	}
}

$Core->set_page_id('sm_backup_tables', 'management');
require SITE_ROOT.'header.php';
?>

<form method="get" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<div class="card mb-3">
		<div class="card-header">
			<h6 class="card-title mb-0">Upload CSV data to DataBase</h6>
		</div>
		<div class="card-body">
			<div class="alert alert-info" role="alert"><?php echo $lang_sm_backup_tables['wellcome_info'] ?></div>
			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_table_name">Available tables</label>
				<select id="fld_table_name" name="table_name" class="form-select" required>
					<option value="">Select table</option>
<?php
foreach($tables as $key => $table)
{
	if (isset($lang_sm_backup_tables[$table]))
	{
		if ($table == $table_name)
			echo '<option value="'.$table.'" selected>'.$table.'</option>';
		else
			echo '<option value="'.$table.'">'.$table.'</option>';
	}
}
?>
				</select>
			</div>
			<div class="mb-3">
				<button type="submit" class="btn btn-primary">Show columns</button>
				<a href="<?php echo $URL->link('sm_backup_tables') ?>" class="btn btn-secondary text-white">Reset</a>
			</div>
		</div>
	</div>
</form>


<?php
if ($table_name != '')
{
	$columns = [];
	if ($db_type == 'mysqli')
	{
		$results = $DBLayer->query("SELECT * FROM ".$table_name);
		$fields = $results->fetch_fields();
		$fields_count = count($fields);
		for($i=0; $i < $fields_count; $i++){
			$columns[] = $fields[$i]->name;
		}
	}
?>
<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<input type="hidden" name="table_name" value="<?php echo $table_name ?>">
	<div class="card mb-3">
		<div class="card-header">
			<h6 class="card-title mb-0">Available columns</h6>
		</div>

		<table class="table table-sm table-striped table-bordered">
			<tbody>		
<?php
	foreach($columns as $key => $col_name)
	{
		echo '<tr><td>'.$col_name.'</td></tr>';
	}
?>		
			</tbody>
		</table>

		<div class="card-header">
			<h6 class="card-title mb-0">Upload CSV file</h6>
		</div>
		<div class="card-body">
			<div class="col-md-4 mb-3">
				<input type="file" name="csv_file" class="form-control" required>
			</div>
			<div class="mb-3">
				<button type="submit" name="upload_table" class="btn btn-primary">Upload Table</button>
			</div>	
		</div>
	</div>
</form>
<?php
}

require SITE_ROOT.'footer.php';