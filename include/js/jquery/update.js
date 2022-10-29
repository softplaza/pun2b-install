
var jQ = new Object({
	update_version:function() {
		$.get( PUNBB.env.base_url+'/include/js/functions.php',function(){
            window.location.reload();
	    });
    }
});