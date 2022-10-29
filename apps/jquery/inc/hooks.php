<?php

if (!defined('DB_CONFIG')) die();

function jquery_es_essentials()
{
    define('JQUERY_INCLUDE_METHOD_JQUERY_CDN', 0);
    define('JQUERY_INCLUDE_METHOD_GOOGLE_CDN', 1);
    define('JQUERY_INCLUDE_METHOD_MICROSOFT_CDN', 2);
    define('JQUERY_INCLUDE_METHOD_LOCAL', 3);
}

function jquery_ft_js_include()
{
    global $Config, $Loader;

    if ($Config->get('o_jquery_version') == 2)
    {
        $j_version = '1.7.1';
    }
    else
    {
        if ($Config->get('o_jquery_version') == 0) { $cur_jv = 2; } elseif ($Config->get('o_jquery_version') == 1) { $cur_jv = 1; }
        $j_version = $Config->get('o_jquery_'.$cur_jv.'x_version_number');
    }
    
    switch ($Config->get('o_jquery_include_method'))
    {
        case JQUERY_INCLUDE_METHOD_GOOGLE_CDN:
            if (@file_get_contents('http://ajax.googleapis.com/ajax/libs/jquery/'.$j_version.'/jquery.min.js'))
            {
                $app_jquery_url = '//ajax.googleapis.com/ajax/libs/jquery/'.$j_version.'/jquery.min.js';
            } else {
                $app_jquery_url = BASE_URL.'/apps/jquery/js/jquery-'.$j_version.'.min.js';
            }
            break;
    
        case JQUERY_INCLUDE_METHOD_MICROSOFT_CDN:
            if ($data = @file_get_contents('http://ajax.aspnetcdn.com/ajax/jQuery/jquery-'.$j_version.'.min.js'))
            {
                $app_jquery_url = '//ajax.googleapis.com/ajax/libs/jquery/'.$j_version.'/jquery.min.js';
            } else {
                $app_jquery_url = BASE_URL.'/apps/jquery/js/jquery-'.$j_version.'.min.js';
            }
            break;
    
        case JQUERY_INCLUDE_METHOD_LOCAL:
            $app_jquery_url = BASE_URL.'/apps/jquery/js/jquery-'.$j_version.'.min.js';
            break;
            
        case JQUERY_INCLUDE_METHOD_JQUERY_CDN:
            default:
            if (@file_get_contents('http://code.jquery.com/jquery-'.$j_version.'.min.js'))
            {
                $app_jquery_url = '//ajax.googleapis.com/ajax/libs/jquery/'.$j_version.'/jquery.min.js';
            } else {
                $app_jquery_url = BASE_URL.'/apps/jquery/js/jquery-'.$j_version.'.min.js';
            }
            break;
    }
    
    $Loader->add_js($app_jquery_url, array('type' => 'url', 'async' => false, 'group' => -100 , 'weight' => 75));
}

function jquery_aop_features_gzip_fieldset_end()
{
    global $User, $Config, $Loader, $page_param;

    if (!isset($lang_jquery)) {
		if (file_exists(SITE_ROOT.'apps/jquery/lang/'.$User->get('language').'/lang.php')) {
			require SITE_ROOT.'apps/jquery/lang/'.$User->get('language').'/lang.php';
	    } else {
			require SITE_ROOT.'apps/jquery/lang/English/lang.php';
		}
	}

	if ($Config->get('o_jquery_version') == 0 || $Config->get('o_jquery_version') == 1)
	{
		if ($Config->get('o_jquery_version') == 0) { $cur_jv = 2; } else { $cur_jv = 1; }
		if ($jquery_latest_content = @file_get_contents('http://cdn.jsdelivr.net/jquery/'.$cur_jv.'/jquery.min.js')) {
            preg_match('/v(\d+\.\d+\.\d+)/', $jquery_latest_content, $matches);
            $jquery_latest_version = $matches[1];

            if (version_compare($jquery_latest_version, $Config->get('o_jquery_'.$cur_jv.'x_version_number'), '>'))
            {              
			    $jquery_new_version = $matches[1];
            }
		}
	}
?>

<?php if (isset($jquery_new_version)) { ?>
            <div class="alert alert-info" role="alert"><?php echo sprintf($lang_jquery['New version is available'], $jquery_new_version) ?></div>
            <button type="submit" class="btn btn-primary" onClick="jQ.update_version(); return false;">Update</button>
<?php } ?>

            <h6 class="card-title mb-0">jQuery settings</h6>
            <hr class="my-1">
            <label class="form-label my-2">Version jQuery</label>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="form[jquery_version]" value="0" id="fld_jquery_version1" <?php if ($Config->get('o_jquery_version') == 0) echo ' checked' ?>>
                <label class="form-check-label" for="fld_jquery_version1">jQuery 2.2.4</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="form[jquery_version]" value="1" id="fld_jquery_version2" <?php if ($Config->get('o_jquery_version') == 1) echo ' checked' ?>>
                <label class="form-check-label" for="fld_jquery_version2">jQuery 1.12.4 (support for older browsers)</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="form[jquery_version]" value="2" id="fld_jquery_version3" <?php if ($Config->get('o_jquery_version') == 2) echo ' checked' ?>>
                <label class="form-check-label" for="fld_jquery_version3">jQuery 1.7.1 (outdated)</label>
            </div>

            <label class="form-label my-2">Include method</label>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="form[jquery_include_method]" value="0" id="fld_jquery_include_method1" <?php if ($Config->get('o_jquery_include_method') == 0) echo ' checked' ?>>
                <label class="form-check-label" for="fld_jquery_include_method1">jQuery CDN</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="form[jquery_include_method]" value="1" id="fld_jquery_include_method2" <?php if ($Config->get('o_jquery_include_method') == 1) echo ' checked' ?>>
                <label class="form-check-label" for="fld_jquery_include_method2">Google Ajax API CDN</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="form[jquery_include_method]" value="2" id="fld_jquery_include_method3" <?php if ($Config->get('o_jquery_include_method') == 2) echo ' checked' ?>>
                <label class="form-check-label" for="fld_jquery_include_method3">Microsoft CDN</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="form[jquery_include_method]" value="3" id="fld_jquery_include_method4" <?php if ($Config->get('o_jquery_include_method') == 3) echo ' checked' ?>>
                <label class="form-check-label" for="fld_jquery_include_method4">Local</label>
            </div>

<?php
}

function jquery_aop_features_validation()
{
    global $Config, $DBLayer;

    if ($Config->get('o_jquery_version') == 0) { $cur_jv = 2; } else { $cur_jv = 1; }
    $jquery_latest_content = @file_get_contents('http://cdn.jsdelivr.net/jquery/'.$cur_jv.'/jquery.min.js');
    preg_match('/v(\d+\.\d+\.\d+)/', $jquery_latest_content, $matches);
    $jquery_latest_version = $matches[1];

    $jquery_dir = SITE_ROOT.'apps/jquery/js/';
    @chmod($jquery_dir, 0777);
    $result = @file_put_contents($jquery_dir.'jquery-'.$jquery_latest_version.'.min.js', $jquery_latest_content);
    if ($result)
    {
        if (file_exists($jquery_dir.'jquery-'.$Config->get('o_jquery_'.$cur_jv.'x_version_number').'.min.js'))
            unlink($jquery_dir.'jquery-'.$Config->get('o_jquery_'.$cur_jv.'x_version_number').'.min.js');
        
        $query = array(
            'UPDATE'	=> 'config',
            'SET'		=> 'conf_value=\''.$DBLayer->escape($jquery_latest_version).'\'',
            'WHERE'		=> 'conf_name=\''.$DBLayer->escape('o_jquery_'.$cur_jv.'x_version_number').'\''
        );
        $DBLayer->query_build($query) or error(__FILE__, __LINE__);
    }
}
