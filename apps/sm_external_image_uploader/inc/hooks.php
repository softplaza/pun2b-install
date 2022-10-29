<?php

if (!defined('DB_CONFIG')) die();

function sm_external_image_uploader_ft_js_include()
{
    global $Loader;
 
    if (PAGE_ID == 'sm_messenger_new')
        $Loader->add_js('https://imgbb.com/upload.js', array('type' => 'url', 'async' => true, 'weight' => 75, 'group' => SPM_JS_GROUP_SYSTEM));  
}
