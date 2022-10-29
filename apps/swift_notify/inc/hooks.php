<?php

if (!defined('DB_CONFIG')) die();

function swift_notify_co_modify_url_scheme()
{
    global $URL;

    $URL->add('swift_notify_ajax', 'apps/swift_notify/js/ajax.php');
}

function swift_notify_hd_head()
{
    global $Loader;
    
    $Loader->add_css(BASE_URL.'/apps/swift_notify/css/style.css?v='.time(), array('type' => 'url', 'media' => 'screen'));  
}

function swift_notify_FooterIncludeJS()
{
    global $Loader, $URL, $User;

    $allowed_pages = (!in_array(PAGE_SECTION_ID, ['login'])) ? true : false;
    
    $jquery_notify = '
    function getNotify(){
        var token = "'.generate_form_token($URL->link('swift_notify_ajax')).'";
        jQuery.ajax({
            url: "'.$URL->link('swift_notify_ajax').'",
            type:	"POST",
            dataType: "json",
            cache: false,
            data: ({csrf_token:token}),
            success: function(re){
                objToArray(re);
            },
            error: function(re){
                document.getElementById("#brd-messages").innerHTML = re;
            }
        });
    }
    function objToArray(obj){
        $.each(obj, function( key, value) {
            if (document.getElementById(key))
                $("" + value +"").insertAfter("#" + key);
        });
    }
    getNotify();
    ';
    
    if ($allowed_pages && !$User->is_guest())
        $Loader->add_js($jquery_notify, array('type' => 'inline', 'weight' => 250, 'group' => SPM_JS_GROUP_SYSTEM));
}
