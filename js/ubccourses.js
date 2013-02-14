function getSectionData(department,course){
          jQuery.ajax({
             url: ajaxurl,
             type: 'POST',
             data: {
                action: 'ubcsections_display_ajax',
                department: department,
                course: course
             },
             error: function(jqXHR, textStatus) {
                if (textStatus == 'timeout') {
                  jQuery('#sections.'+department + course).html('Unable to get data - please try again').show();
                } 
             },
             beforeSend: function(){
	        jQuery('div.modal-body').html('<div class="ajaxprogress"></div>');
                jQuery('p#myModalLabel').html('<h5>Sections for '+department+course+'</h5>');
             },
             dataType: 'html', 
             success: function(response){
                jQuery('div.modal-body').html(response);
             },
             timeout: 3000
          });
     return false;
 }

