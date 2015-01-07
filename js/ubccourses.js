function getSectionData(fuzzy,department,course,profileslug,stickywinter,stickyyear){
          jQuery.ajax({
             url: ajaxurl,
             type: 'POST',
             data: {
                action: 'ubcsections_display_ajax',
		fuzzy:fuzzy,
                department: department,
                course: course,
                profileslug: profileslug,
                stickywinter: stickywinter,
                stickyyear:stickyyear,
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