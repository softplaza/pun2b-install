<?php
/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

define('SITE_ROOT', './');
require SITE_ROOT.'include/common.php';

if ($User->get('g_read_board') == '0')
	message($lang_common['No view']);

// Load the help.php language file
require SITE_ROOT.'lang/'.$User->get('language').'/help.php';

$section = isset($_GET['section']) ? $_GET['section'] : null;
if (!$section)
	message($lang_common['Bad request']);

$Core->set_page_title($lang_help['Help']);
$Core->set_page_id('help');
require SITE_ROOT.'header.php';
?>

<div class="main-content main-frm">
<?php
if ($section == 'manual')
{
?>
	<div class="ct-box help-box">
		<p class="hn"><?php echo $lang_help['Smilies info'] ?></p>
		<div class="entry-content">

		</div>
	</div>
<?php
}

?>
</div>

<?php
require SITE_ROOT.'footer.php';