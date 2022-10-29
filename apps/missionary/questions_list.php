<?php

if (!defined('SITE_ROOT'))
	define('SITE_ROOT', '../../');

require SITE_ROOT.'include/common.php';

if (!$User->is_admin())
	message($lang_common['No permission']);

if (isset($_POST['update']))
{
	$qid = isset($_POST['qid']) ? $_POST['qid'] : '';
	
	if (!empty($qid))
	{
		foreach($qid as $key => $val)
		{
			$question = isset($_POST['question'][$key]) ? forum_trim($_POST['question'][$key]) : '';
			$answer = isset($_POST['answer'][$key]) ? forum_trim($_POST['answer'][$key]) : '';
			$version1 = isset($_POST['version1'][$key]) ? forum_trim($_POST['version1'][$key]) : '';
			$version2 = isset($_POST['version2'][$key]) ? forum_trim($_POST['version2'][$key]) : '';
			$version3 = isset($_POST['version3'][$key]) ? forum_trim($_POST['version3'][$key]) : '';
			$description = isset($_POST['description'][$key]) ? forum_trim($_POST['description'][$key]) : '';
			$level = isset($_POST['level'][$key]) ? intval($_POST['level'][$key]) : 1;
			
			$query = array(
				'UPDATE'	=> 'game_missionary',
				'SET'		=> 
					'question=\''.$DBLayer->escape($question).'\',
					answer=\''.$DBLayer->escape($answer).'\',
					version1=\''.$DBLayer->escape($version1).'\',
					version2=\''.$DBLayer->escape($version2).'\',
					version3=\''.$DBLayer->escape($version3).'\',
					description=\''.$DBLayer->escape($description).'\', 
					level=\''.$DBLayer->escape($level).'\', 
					approved=1',
				'WHERE'		=> 'id='.$key
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}

		// Add flash message
		$flash_message = 'Список вопросов обновлен.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$level = isset($_GET['level']) ? intval($_GET['level']) : 0;
$sort_by = isset($_GET['sort_by']) ? forum_trim($_GET['sort_by']) : '';
$sort_by_updated = isset($_GET['updated']) ? intval($_GET['updated']) : 0;
$query = array(
	'SELECT'	=> 'id, question, answer, version1, version2, version3, description, level, num_unanswered, updated',
	'FROM'		=> 'game_missionary',
	'WHERE'		=> 'approved=1',
	'ORDER BY'	=> 'level'
);
if ($level > 0)
	$query['WHERE'] .= ' AND level='.$level;

if ($sort_by == 'errors')
	$query['ORDER BY'] = 'num_unanswered DESC';
else if ($sort_by == 'levels')
	$query['ORDER BY'] = 'level DESC';
else if ($sort_by == 'updated')
	$query['ORDER BY'] = 'updated DESC';

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$question_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$question_info[] = $row;
}

$Core->set_page_id('missionary_questions_list', 'missionary');
require SITE_ROOT.'header.php';
?>

<style>
table{table-layout: initial;}
td textarea{width: 97%;}
.tc1{min-width:350px;}
</style>

	<div class="main-subhead">
		
		<form method="get" accept-charset="utf-8" action="<?php echo forum_link($forum_url['game_missionary_questions_list']) ?>">
			<h2 class="hn"><span>
				Показать  
				<select name="level"><option value="0">Все уровни</option>
<?php

$levels_arr = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15);
foreach($levels_arr as $level) {
	if(isset($_GET['level']) && $_GET['level'] == $level)
		echo '<option value="'.$level.'" selected="selected">'.$level.'</option>';
	else
		echo '<option value="'.$level.'">'.$level.'</option>';
}
?>
				</select>
				Сортировать по 
				<select name="sort_by">
					<option value="">По умолчанию</option>
					<option value="levels">По уровням</option>
					<option value="errors">По ошибкам</option>
					<option value="updated">По обновлению</option>
				</select>
				<input type="submit" value="Go" />
				<?php echo count($question_info) ?> вопросов найдено
			</span></h2>
		</form>
	</div>
	
	<div class="main-content main-frm">
<?php
if (!empty($question_info)) 
{
?>
		<div class="ct-group">
			
			<form method="post" accept-charset="utf-8" action="<?php echo forum_link($forum_url['game_missionary_questions_list']) ?>">
				<div class="hidden">
					<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link($forum_url['game_missionary_questions_list'])) ?>" />
				</div>
				
					<table class="table-list">
					
						<thead class="thead-list">
							<tr>
								<th>id#</th>
								<th class="tc1">Questions</th>
								<th>Answer</th>
								<th>Version-1</th>
								<th>Version-2</th>
								<th>Version-3</th>
								<th>Description</th>
								<th>Level</th>
								<th>Errors</th>
								<th>Updated</th>
							</tr>
						</thead>
						
						<tbody>
<?php
	$levels_counter = array();
	foreach ($question_info as $cur_info)
	{
?>
					<tr>
						<input type="hidden" name="qid[<?php echo $cur_info['id'] ?>]" value="<?php echo $cur_info['id'] ?>">
						<td><?php echo $cur_info['id'] ?></td>
						
						<td><textarea name="question[<?php echo $cur_info['id'] ?>]" rows="2"><?php echo forum_htmlencode($cur_info['question']) ?></textarea></td>
						
						<td><input type="text" name="answer[<?php echo $cur_info['id'] ?>]" value="<?php echo forum_htmlencode($cur_info['answer']) ?>" size="15"></td>
						<td><input type="text" name="version1[<?php echo $cur_info['id'] ?>]" value="<?php echo forum_htmlencode($cur_info['version1']) ?>" size="15"></td>
						<td><input type="text" name="version2[<?php echo $cur_info['id'] ?>]" value="<?php echo forum_htmlencode($cur_info['version2']) ?>" size="15"></td>
						<td><input type="text" name="version3[<?php echo $cur_info['id'] ?>]" value="<?php echo forum_htmlencode($cur_info['version3']) ?>" size="15"></td>
						<td><input type="text" name="description[<?php echo $cur_info['id'] ?>]" value="<?php echo forum_htmlencode($cur_info['description']) ?>" size="25"></td>
						<td><select name="level[<?php echo $cur_info['id'] ?>]">
<?php
		foreach($levels_arr as $level) {
			if($cur_info['level'] == $level)
				echo '<option value="'.$level.'" selected="selected">'.$level.'</option>';
			else
				echo '<option value="'.$level.'">'.$level.'</option>';
		}
?>
							</select>
						</td>
						<td><?php echo $cur_info['num_unanswered'] ?></td>
						<td><?php echo format_time($cur_info['updated']) ?></td>
					</tr>
<?php
		if (!isset($levels_counter[$cur_info['level']]))
			$levels_counter[$cur_info['level']] = 1;
		else
			++$levels_counter[$cur_info['level']];
	}
?>
					</tbody>
				</table>
				
				<div class="frm-buttons">
					<span class="submit primary"><input type="submit" name="update" value="Обновить список" /></span>
				</div>
			</form>
			
		</div>
		
		<div class="ct-box warn-box">
			<p><strong>Статистика: <?php print_r($levels_counter) ?></strong></p>
		</div>
<?php

} else {
	
?>
		<div id="admin-alerts" class="ct-set warn-set">
			<div class="ct-box warn-box">
				<h3 class="ct-legend hn warn"><span>Information:</span></h3>
				<p>No questions there.</p>
			</div>
		</div>
<?php
}
?>
	</div>
<?php

require SITE_ROOT.'footer.php';