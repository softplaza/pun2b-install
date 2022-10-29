function clearSelectedFiles() {
	document.getElementById('preview_files').innerHTML = "";
}
function previewImages() {
	var preview = document.querySelector('#preview_files');
	if (this.files) {
		[].forEach.call(this.files, readAndPreview);
	}
	function readAndPreview(file) {
		document.getElementById('preview_files').innerHTML = "";
    // Make sure `file.name` matches our extensions criteria
 //   if (!/\.(jpe?g|png|gif)$/i.test(file.name)) {
 //     return alert(file.name + " is not an image");
//    } // else...
		var reader = new FileReader();
		reader.addEventListener("load", function() {
			var image = new Image();
			image.height = 100;
			image.title  = file.name;
			image.src    = this.result;
			preview.appendChild(image);
		});
		reader.readAsDataURL(file);
	}
}
document.querySelector('#form_files').addEventListener("change", previewImages);

$(document).ready(function() { 
    $('form').submit(function(e) {   
        if($('#form_files').val()) {
 //           e.preventDefault();
 //           $('#loader-icon').show();
            $(this).ajaxSubmit({ 
 //               target:   '#targetLayer', 
                beforeSubmit: function() {
                    $(".progress-bar").width('0%');
                },
                uploadProgress: function (event, position, total, percentComplete){ 
                    $(".progress-bar").width(percentComplete + '%');
                    $(".progress-bar").html('<div id="progress-status">' + percentComplete +' %</div>');
                },
                success:function (){
//                    $('#loader-icon').hide();
                },
                //resetForm: true
            }); 
            return true; 
        }
    });
});

