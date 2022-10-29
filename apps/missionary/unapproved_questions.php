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
			$approved = isset($_POST['approved'][$key]) ? intval($_POST['approved'][$key]) : 0;
			
			if ($approved > 0)
			{
				$query = array(
					'UPDATE'	=> 'game_missionary',
					'SET'		=> 
						'question=\''.$forum_db->escape($question).'\',
						answer=\''.$forum_db->escape($answer).'\',
						version1=\''.$forum_db->escape($version1).'\',
						version2=\''.$forum_db->escape($version2).'\',
						version3=\''.$forum_db->escape($version3).'\',
						description=\''.$forum_db->escape($description).'\', 
						level=\''.$forum_db->escape($level).'\',
						approved=1',
					'WHERE'		=> 'id='.$key
				);
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
		}

		// Add flash message
		$flash_message = 'Question has been approved.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'id, question, answer, version1, version2, version3, description, level',
	'FROM'		=> 'game_missionary',
	'WHERE'		=> 'approved=0',
	'ORDER BY'	=> 'level'
);
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
$question_info = array();
while ($row = $forum_db->fetch_assoc($result)) {
	$question_info[] = $row;
}

$Core->set_page_id('missionary_unapproved_questions', 'missionary');
require SITE_ROOT.'header.php';

if (!empty($question_info)) 
{
?>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<table class="table table-sm table-striped table-bordered">	
		<thead>
			<tr>
				<th>id#</th>
				<th class="tc1">Questions</th>
				<th>Answer</th>
				<th>Version-1</th>
				<th>Version-2</th>
				<th>Version-3</th>
				<th>Description</th>
				<th>Level</th>
				<th>Approve</th>
			</tr>
		</thead>
		<tbody>
<?php
	$levels_arr = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15);
	foreach ($question_info as $cur_info)
	{
?>
			<tr>
				<input type="hidden" name="qid[<?php echo $cur_info['id'] ?>]" value="<?php echo $cur_info['id'] ?>">
				<td><?php echo $cur_info['id'] ?></td>
				
				<td><textarea name="question[<?php echo $cur_info['id'] ?>]" rows="2"><?php echo html_encode($cur_info['question']) ?></textarea></td>
				
				<td><input type="text" name="answer[<?php echo $cur_info['id'] ?>]" value="<?php echo html_encode($cur_info['answer']) ?>" size="15"></td>
				<td><input type="text" name="version1[<?php echo $cur_info['id'] ?>]" value="<?php echo html_encode($cur_info['version1']) ?>" size="15"></td>
				<td><input type="text" name="version2[<?php echo $cur_info['id'] ?>]" value="<?php echo html_encode($cur_info['version2']) ?>" size="15"></td>
				<td><input type="text" name="version3[<?php echo $cur_info['id'] ?>]" value="<?php echo html_encode($cur_info['version3']) ?>" size="15"></td>
				<td><input type="text" name="description[<?php echo $cur_info['id'] ?>]" value="<?php echo html_encode($cur_info['description']) ?>" size="25"></td>
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
				<td><input type="checkbox" name="approved[<?php echo $cur_info['id'] ?>]" value="1"></td>
			</tr>
<?php
	}
?>
		</tbody>
	</table>

	<button type="submit" name="update" class="btn btn-primary">Approve</button>

</form>
			
<?php

} else {
	
?>
	<div class="alert alert-warning mt-3" role="alert">You have no items on this page or not found within your search criteria.</div>
<?php
}
require SITE_ROOT.'footer.php';