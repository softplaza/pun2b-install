<?php

define('SITE_ROOT', './');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

// Load the userlist.php language file
require SITE_ROOT.'lang/'.$User->get('language').'/userlist.php';
require SITE_ROOT.'lang/'.$User->get('language').'/admin_common.php';

// Miscellaneous setup
$page_param['show_post_count'] = ($Config->get('o_show_post_count') == '1' || $User->is_admmod()) ? true : false;

$page_param['show_group'] = (!isset($_GET['show_group']) || intval($_GET['show_group']) < -1 && intval($_GET['show_group']) > 2) ? -1 : intval($_GET['show_group']);

$search_by_realname = (isset($_GET['realname']) && is_string($_GET['realname'])) ? swift_trim($_GET['realname']) : '';

// Create any SQL for the WHERE clause
$where_sql = array();
$like_command = ($db_type == 'pgsql') ? 'ILIKE' : 'LIKE';

if ($search_by_realname != '')
	$where_sql[] = 'u.realname '.$like_command.' \''.$DBLayer->escape('%'.$search_by_realname.'%').'\'';

if ($page_param['show_group'] > 0)
	$where_sql[] = 'u.group_id='.$page_param['show_group'];

// Fetch user count
$query = array(
	'SELECT'	=> 'COUNT(u.id)',
	'FROM'		=> 'users AS u',
	'WHERE'		=> 'u.group_id > 2'
);
if (!empty($where_sql))
	$query['WHERE'] .= ' AND '.implode(' AND ', $where_sql);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

// Setup form
$page_param['group_count'] = $page_param['item_count'] = $page_param['fld_count'] = 0;

$Core->set_page_id('userlist', 'profile');
require SITE_ROOT.'header.php';

$query = array(
	'SELECT'	=> 'g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'WHERE'		=> 'g.g_id > 2',
	'ORDER BY'	=> 'g.g_title'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$groups_info = array();
while ($cur_group = $DBLayer->fetch_assoc($result)) {
	$groups_info[] = $cur_group;
}

// Grab the users
$query = array(
	'SELECT'	=> 'u.*, g.g_id, g.g_user_title',
	'FROM'		=> 'users AS u',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'groups AS g',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'u.group_id > 2',
	'ORDER BY'	=> 'u.realname',
	'LIMIT'		=> $PagesNavigator->limit(),
);

if (!empty($where_sql))
	$query['WHERE'] .= ' AND '.implode(' AND ', $where_sql);

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$founded_user_datas = array();
while ($user_data = $DBLayer->fetch_assoc($result))
{
	$founded_user_datas[] = $user_data;
}
$PagesNavigator->num_items($founded_user_datas);

$page_param['item_count'] = 0;

$page_param['table_header'] = array();

$page_param['table_header']['realname'] = '<th class="tc'.count($page_param['table_header']).'" scope="col"><strong>Full name</strong></th>';
$page_param['table_header']['email'] = '<th class="tc'.count($page_param['table_header']).'" scope="col"><strong>Email</strong></th>';
$page_param['table_header']['phone'] = '<th class="tc'.count($page_param['table_header']).'" scope="col"><strong>Phone number</strong></th>';
$page_param['table_header']['group'] = '<th class="tc'.count($page_param['table_header']).'" scope="col"><strong>Group</strong></th>';

?>

<div class="main-content main-frm">
	<div class="ct-group">
		<div class="search-box">
			<form method="get" accept-charset="utf-8" action="<?php echo $URL->link('userlist') ?>">
				<select name="show_group"><option value="">Show all grous</option>
<?php
	foreach ($groups_info as $cur_group)
	{
		if ($cur_group['g_id'] == $page_param['show_group'])
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.html_encode($cur_group['g_title']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.html_encode($cur_group['g_title']).'</option>'."\n";
	}
?>
				</select>
				<input name="realname" type="text" value="<?php echo isset($_GET['realname']) ? html_encode($_GET['realname']) : '' ?>" placeholder="User Name"/>
				<input type="submit" value="Search" />
			</form>
		</div>
		
<?php
if (!empty($founded_user_datas))
{
?>
		<table>
			<thead>
				<tr>
					<?php echo implode("\n\t\t\t\t\t\t", $page_param['table_header'])."\n" ?>
				</tr>
			</thead>
			<tbody>
<?php

	foreach ($founded_user_datas as $user_data)
	{
		$page_param['table_row'] = array();
		
		$page_param['table_row']['realname'] = '<td class="tc'.count($page_param['table_row']).'"><strong><a href="'.$URL->link('user', $user_data['id']).'">'.html_encode($user_data['realname']).'</a></strong></td>';
		
		$page_param['table_row']['email'] = '<td class="tc'.count($page_param['table_row']).'"><a href="mailto:'.html_encode($user_data['email']).'">'.html_encode($user_data['email']).'</a></td>';
		
		if ($user_data['work_phone'] != '')
			$page_param['table_row']['work_phone'] = '<td class="tc'.count($page_param['table_row']).'">Work: <strong><a href="tel:'.html_encode($user_data['work_phone']).'">'.html_encode($user_data['work_phone']).'</a></strong></td>';
		else if ($user_data['cell_phone'] != '')
			$page_param['table_row']['cell_phone'] = '<td class="tc'.count($page_param['table_row']).'">Cell: <strong><a href="tel:'.html_encode($user_data['cell_phone']).'">'.html_encode($user_data['cell_phone']).'</a></strong></td>';
		else if ($user_data['home_phone'] != '')
			$page_param['table_row']['home_phone'] = '<td class="tc'.count($page_param['table_row']).'">Home: <strong><a href="tel:'.html_encode($user_data['home_phone']).'">'.html_encode($user_data['home_phone']).'</a></strong></td>';
		else
			$page_param['table_row']['phone'] = '<td class="tc'.count($page_param['table_row']).'">n/a</td>';
		
		$page_param['table_row']['group'] = '<td class="tc'.count($page_param['table_row']).'">'.get_title($user_data).'</td>';
		
		++$page_param['item_count'];
?>
			<tr class="<?php echo ($page_param['item_count'] % 2 != 0) ? 'odd' : 'even' ?><?php if ($page_param['item_count'] == 1) echo ' row1'; ?>">
				<?php echo implode("\n\t\t\t\t\t\t", $page_param['table_row'])."\n" ?>
			</tr>
<?php
	}
?>
			</tbody>
		</table>
<?php
} else {
?>
	<div class="ct-box info-box">
		<p><strong><?php echo $lang_ul['No users found'] ?></strong></p>
	</div>
<?php
}
?>	
	</div>
</div>

<?php
require SITE_ROOT.'footer.php';