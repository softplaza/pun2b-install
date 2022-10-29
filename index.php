<?php

define('SITE_ROOT', './');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
{
	redirect($URL->link('login'), 'You are not loged in.');
}
else
{
	redirect($URL->link('user', $User->get('id')), 'You are already loged in.');
}
?>





