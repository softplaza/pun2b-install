<?php

define('SITE_ROOT', '../../');

require SITE_ROOT.'include/common.php';

$hash = isset($_GET['hash']) ? intval($_GET['hash']) : 0;

if (isset($_POST['new_question']))
{
	$question = isset($_POST['question']) ? swift_trim($_POST['question']) : '';
	$answer = isset($_POST['answer']) ? swift_trim($_POST['answer']) : '';
	$version1 = isset($_POST['version1']) ? swift_trim($_POST['version1']) : '';
	$version2 = isset($_POST['version2']) ? swift_trim($_POST['version2']) : '';
	$version3 = isset($_POST['version3']) ? swift_trim($_POST['version3']) : '';
	$description = isset($_POST['description']) ? swift_trim($_POST['description']) : '';
	$level = isset($_POST['level']) ? intval($_POST['level']) : 1;
	
	if ($question == '' || $answer == '' || $version1 == '' || $version2 == '' || $version3 == '' || $description == '')
		$Core->add_error('Заполните все поля');
	
	if (empty($Core->errors))
	{
		$query = array(
			'INSERT'	=> 'question, answer, version1, version2, version3, description, level, updated',
			'INTO'		=> 'game_missionary',
			'VALUES'	=> 
				'\''.$DBLayer->escape($question).'\',
				\''.$DBLayer->escape($answer).'\',
				\''.$DBLayer->escape($version1).'\',
				\''.$DBLayer->escape($version2).'\',
				\''.$DBLayer->escape($version3).'\',
				\''.$DBLayer->escape($description).'\',
				'.$level.',
				'.time()
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		// Add flash message
		$flash_message = 'Вопрос предоставлен на рассмотрение.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$forum_page['fld_count'] = $forum_page['group_count'] = $forum_page['item_count'] = 0;

$Core->set_page_id('missionary_new_question', 'missionary');
require SITE_ROOT.'header.php';

if ($hash == '0')
{
?>
	<style>
	
	</style>
	
		<div class="main-subhead">
			<h2 class="hn"><span class="a-project">Добавление нового вопроса</span></h2>
		</div>
		
		<div class="main-content main-frm">
	
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
				
				<div class="frm-form">
					<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
						<legend class="group-legend"><strong></strong></legend>
						
						<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
							<div class="txt-box textarea required">
								<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span>Вопрос</span><small>Напишите ваш вопрос</small></label>
								<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="question" rows="2" cols="95" required><?php echo isset($_POST['question']) ? swift_trim($_POST['question']) : '' ?></textarea></span></div>
							</div>
						</div>
						
						<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
							<div class="sf-box text required">
								<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span>Правильный ответ</span> <small></small></label><br />
								<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="answer" value="<?php echo isset($_POST['answer']) ? swift_trim($_POST['answer']) : '' ?>" size="55" maxlength="100" required /></span>
							</div>
						</div>
	
						<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
							<div class="sf-box text required">
								<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span>Версия 1</span> <small></small></label><br />
								<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="version1" value="<?php echo isset($_POST['version1']) ? swift_trim($_POST['version1']) : '' ?>" size="55" maxlength="100" required /></span>
							</div>
						</div>
	
						<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
							<div class="sf-box text required">
								<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span>Версия 2</span> <small></small></label><br />
								<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="version2" value="<?php echo isset($_POST['version2']) ? swift_trim($_POST['version2']) : '' ?>" size="55" maxlength="100" required /></span>
							</div>
						</div>
	
						<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
							<div class="sf-box text required">
								<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span>Версия 3</span> <small></small></label><br />
								<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="version3" value="<?php echo isset($_POST['version3']) ? swift_trim($_POST['version3']) : '' ?>" size="55" maxlength="100" required /></span>
							</div>
						</div>
						
						<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
							<div class="txt-box textarea required">
								<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span>Источник</span><small>Ссылка на источник или краткое объяснение</small></label>
								<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="description" rows="5" cols="95" required><?php echo isset($_POST['description']) ? swift_trim($_POST['description']) : '' ?></textarea></span></div>
							</div>
						</div>
	
						<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
							<div class="sf-box select required">
								<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span>Уровень сложности</span><small>Чем больше число, тем сложнее вопрос</small></label><br />
								<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="level" required>
<?php
		
	$levels_arr = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15);
	foreach($levels_arr as $level)
	{
		if(isset($_POST['level']) && $_POST['level'] == $level)
			echo '<option value="'.$level.'" selected="selected">'.$level.'</option>';
		else
			echo '<option value="'.$level.'">'.$level.'</option>';
	}
?>
								</select></span>
							</div>
						</div>
						
					</fieldset>
	
					<div class="frm-buttons">
						<span class="submit primary"><input type="submit" name="new_question" value="Добавить" /></span>
					</div>
	
				</div>
			</form>
				
		</div>
<?php
}
else
{
	if ($hash != '')
		$errors[] = 'Не верный пароль. Пожалуйста обратитесь к администратору: punbb.info@gmail.com';
?>
		<div class="main-subhead">
			<h2 class="hn"><span>Для добавления вопроса требуется пароль</span></h2>
		</div>
		
		<div class="main-content main-frm">

			<form method="get" accept-charset="utf-8" action="<?php echo forum_link($forum_url['game_missionary_new_question']) ?>">

				<div class="frm-form">
					<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
						<legend class="group-legend"><strong></strong></legend>
						
						<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
							<div class="sf-box text required">
								<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span>Пароль</span> <small>Чтобы предложить новый вопрос, введите пароль</small></label><br />
								<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="hash" value="" size="25" maxlength="100" required /></span>
							</div>
						</div>
	
					</fieldset>
	
					<div class="frm-buttons">
						<span class="submit primary"><input type="submit" value="Подтвердить" /></span>
					</div>
	
				</div>
			</form>
				
		</div>
<?php
}

require SITE_ROOT.'footer.php';