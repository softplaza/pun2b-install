<?php

if (!defined('FORUM_ROOT') )
	define('FORUM_ROOT', '../../');

require FORUM_ROOT.'include/common.php';

$finish_quotes = array(
	1 => '',
	2 => '',
	3 => '',
	4 => '',
	5 => '',
	6 => '',
	7 => '',
	8 => '',
	9 => '',
	10 => 'Не что сказать? Все мы несовершенны...',
	11 => 'Наверное, этот вопрос стоит глубже исследовать',
	12 => 'Хороший повод провести личное изучение',
	13 => 'Даа, кажется этот вопрос был не из легких...',
	14 => 'Вы были так близки к цели...',
	15 => 'Ну не расстраивайтесь. В следующий раз у вас должно получиться.'
);

$answer_quotes = array(
	1 => 'И это правильный ответ!',
	2 => 'Совершенно верно!',
	3 => 'Замечательный ответ!',
	4 => 'Почему то я был уверен, что вы знали ответ на этот вопрос!',
	5 => 'Просто великолепный ответ!',
	6 => 'Верно! Ну кто бы сомневался, что вы знали это.',
	7 => 'Отлично! Желаете продолжить?',
	8 => 'Прекрасно! Вы уже на полпути к достижению цели!',
	9 => 'И это правильный ответ! Скажите честно, вы же знали? Знали?',
	10 => 'Замечательно! Сразу видно кто регулярно читает Библию.',
	11 => 'Правильно! Продолжайте в том же духе!',
	12 => 'Превосходно! Или же вы сомневались?',
	13 => 'Отлично! Но кажется этот вопрос был не из легких...',
	14 => 'Вы уже почти у цели! Хотите забрать таланты или продолжим?',
	15 => 'Поздравляем! У вас глубокое личное изучение. Так держать!'
);

$level = isset($_POST['level']) ? intval($_POST['level']) + 1 : 1;

$query = array(
	'SELECT'	=> 'id',
	'FROM'		=> 'game_missionary',
	'WHERE'		=> 'approved=1 AND level='.$level
);
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
$qids = array();
while($row = $forum_db->fetch_assoc($result))
	$qids[$row['id']] = $row;

$rand_id = array_rand($qids, 1);

if (isset($_POST['answer']))
{
	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	$answer = isset($_POST['answer']) ? forum_trim($_POST['answer']) : '';

	$query = array(
		'SELECT'	=> 'id, question, answer, version1, version2, version3, description, level',
		'FROM'		=> 'game_missionary',
		'WHERE'		=> 'approved=1 AND id='.$id
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$db_info = $forum_db->fetch_assoc($result);
	
	if (isset($db_info['answer']) && $db_info['answer'] == $answer)
	{
		echo json_encode(array(
//			'question'		=> 'Правильно!',
			'question'		=> $answer_quotes[$db_info['level']],//current level
			'result'		=> '1',
			'level'			=> $db_info['level'],
			'text_start'	=> 'ПРОДОЛЖИТЬ',
			'end'			=> '0',
		));
	}
	else
	{
		echo json_encode(array(
//			'question'		=> 'Вы проиграли! ('.$db_info['description'].')',
			'question'		=>  $finish_quotes[$db_info['level']].' ('.$db_info['description'].')',
			'result'		=> '0',
			'level'			=> '0',
			'text_start'	=> 'НАЧАТЬ СНОВА',
			'end'			=> '1',
		));
		
		$query = array(
			'UPDATE'	=> 'game_missionary',
			'SET'		=> 
				'num_unanswered = num_unanswered + 1, updated='.time(),
			'WHERE'		=> 'id='.$db_info['id']
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}
}
else if (isset($_POST['next']))
{
	$query = array(
		'SELECT'	=> 'id, question, answer, version1, version2, version3, description, level',
		'FROM'		=> 'game_missionary',
		'WHERE'		=> 'id='.$rand_id.' AND approved=1 AND level='.$level
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$db_info = $forum_db->fetch_assoc($result);
	
	$json_array = array();
	if (!empty($db_info))
	{
		$version_arr = array($db_info['version1'], $db_info['version2'], $db_info['version3'], $db_info['answer']);
		shuffle($version_arr);
		
		if ($db_info['answer'] == $version_arr[0])
			$answer = 1;
		else if ($db_info['answer'] == $version_arr[1])
			$answer = 2;
		else if ($db_info['answer'] == $version_arr[2])
			$answer = 3;
		else if ($db_info['answer'] == $version_arr[3])
			$answer = 4;
		
		echo json_encode(array(
			'id'			=> $db_info['id'],
			'question'		=> $db_info['question'],
			'version1'		=> $version_arr[0],
			'version2'		=> $version_arr[1],
			'version3'		=> $version_arr[2],
			'version4'		=> $version_arr[3],
			'level'			=> $db_info['level'],
			'result'		=> '1',
			'end'			=> '0',
			'text_start'	=> '60 секунд',
			'true_v'		=> $answer,
		));
	}

}

// End the transaction
$forum_db->end_transaction();
// Close the db connection (and free up any result data)
$forum_db->close();
