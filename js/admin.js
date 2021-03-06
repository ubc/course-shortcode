/**
 * Admin Control Panel JavaScripts
 * 
 * @version 1.0.0
 * @since 1.0.0
 */

var numcourses;
var currentcount;


  jQuery(".chzn-select").chosen({no_results_text: "No results matched"});


  //Does not work for now as chosen plugin does a preventdefault()
  function handleEnter(dept, e) {
        var key = window.event ? e.keyCode : e.which;
        if (key == '13') {
            getDepartmentData(dept.toUpperCase());
        }
  }
  
  function update(obj){
    if (jQuery(obj).hasClass("active"))
      jQuery(obj).removeClass("active").addClass("marked");
    else 
      jQuery(obj).addClass("active").removeClass("marked");
    if (jQuery("div.instructor-list li.marked").length > 0)
      jQuery("#saveBtn").fadeIn('slow');
    else 
      if (!jQuery("#saveBtn").hasClass('newlist'))
           jQuery("#saveBtn").fadeOut('slow');
  }

jQuery(document).ready(function(jQuery) {doedit();}); 

function doedit(){
jQuery(".editable").live({mouseenter:function(){jQuery(this).addClass("editHover");},mouseleave:function(){jQuery(this).removeClass("editHover");}});jQuery(".editable").live("dblclick", replaceHTML);jQuery(".btnSave, .btnDiscard").live("click", handler);
function handler(){
  var selector="";
  if (jQuery(this).hasClass("btnSave")){ 
    selector = "editBox";
    var inputText = jQuery(this).siblings("form").children("."+selector).val();
    inputText = inputText.replace(/(<([^>]+)>)/ig, '');
 jQuery(this).parent().html(inputText).removeClass("noPad editHover").live("dblclick", replaceHTML);
    jQuery("#saveBtn").fadeIn('slow');
  }
  else{  
    selector = "buffer";
    jQuery(this).parent().html(jQuery(this).parent().parent().attr("title")).removeClass("noPad editHover").live("dblclick", replaceHTML);
    jQuery("#saveBtn").fadeIn('slow');
  }
  return false;
} 
function replaceHTML(){var buffer = jQuery(this).html().replace(/"/g, "&quot;");buffer = buffer.replace(/</gi, "");buffer = buffer.replace(/>/gi, "");jQuery(this).addClass("noPad").html("").html("<form class=\"editor\"><input type=\"text\" name=\"value\" class=\"editBox\" 				value=\"" + buffer + "\" /> <input type=\"hidden\" name=\"buffer\" class=\"buffer\" value=\"" + buffer + "\" /><input type=\"hidden\" name=\"field\" class=\"record\" value=\"" + jQuery(this).attr("id") + "\" /></form><a href=\"#\" class=\"btnSave\">Change</a> <a href=\"#\" class=\"btnDiscard\">Revert to Orig.</a>").unbind('dblclick', replaceHTML);}
}


jQuery(document).ready(function($){

  $(document).on('submit', 'form.settings-form' ,function () {
      alert('submitting'); //Add warning.
      storeData(false);      
  });

  $("#clearBtn").click(function() {
     $('div.instructor-list ul').html('');
     $("#saveBtn").fadeIn('slow');
  });

  $("#revertBtn").click(function() {
     revertstr = $('div#revert').html();
     $('div.instructor-list ul').html(revertstr);
     $("#saveBtn").fadeOut('slow');
  });

  $("#saveBtn").click(function() {
       storeData(true); 
  });

  $("#stickyyear").click(function() {
       if (this.checked)
           getdeptcodeList('true');
       else
           getdeptcodeList('false');
  });

  $("#opens_ubccourses").click(function(e) {
      e.preventDefault();       
      $('#ubccoursesmodal').dialog({
        modal: true,
        closeText: "hide",
        width: 400,
        title: "[ubccourses] shortcode help.",
        buttons: [ { text: "Ok", click: function() { $( this ).dialog( "close" ); } } ]
      }).dialog("widget")
      .next(".ui-widget-overlay")
      .css("background", "#000000");
  });

  $("#opens_ubcinstructors").click(function(e) {
      e.preventDefault();       
      $('#ubcinstructorsmodal').dialog({
        modal: true,
        closeText: "hide",
        width: 400,
        title: "[ubcinstructors] shortcode help",
        buttons: [ { text: "Ok", click: function() { $( this ).dialog( "close" ); } } ]
      }).dialog("widget")
      .next(".ui-widget-overlay")
      .css("background", "#000000");
  });

});

function getdeptcodeList(stickyyear){
          jQuery.ajax({
             url: JSParams.ajaxurl,
             type: 'POST',
             data: {
                action: 'deptcodelist_display_ajax',
                stickyyear: stickyyear
             },
             dataType: 'html', 
             success: function(response){     
               jQuery( "#department_chzn" ).remove(); 
               jQuery( "#department" ).replaceWith( response );
               jQuery(".chzn-select").chosen({no_results_text: "No results matched"});
             }
          });
     return false;
 }

function getInstructorData(department,course,stickyyear){
          jQuery.ajax({
             url: JSParams.ajaxurl,
             type: 'POST',
             data: {
                action: 'ubcinstructors_display_ajax',
                department: department,
                course: course,
                stickyyear: stickyyear
             },
             beforeSend: function(){
             },
             dataType: 'html', 
             success: function(response){
               currentcount++;          
               jQuery( "#progressbar" ).progressbar({value: Math.round((currentcount/numcourses)*100)});
               jQuery(".progress-label").text(Math.round((currentcount/numcourses)*100) +"%");
               var obj = jQuery.parseJSON(response);
               if ((obj) && !(typeof obj.data[0] === "undefined")){
                jQuery.each( obj.data, function( key, value ) {
                   var instrid = jQuery.trim(value.name.replace(/[\s,.]/g, '').replace(/'/g, ''));
                   var courseid = jQuery.trim(value.course);
                   if (jQuery("li." + instrid).length == 0) {
                      jQuery('div.instructor-list ul').append('<li  class="active '+instrid+'" title="'+value.name+'"><span class="delbtn" onclick="update(this.parentNode);"></span><span id="iname" class="editable">'+value.name+'</span><span class="icourse '+courseid+'"> '+value.course+'</span></li>');
                   }
                   else{  //instructor exists - check if course code exists
                      if (jQuery("li." + instrid + " span." + courseid).length == 0) {
                        jQuery("li." + instrid + ' span:last-child').after('<span class="icourse '+courseid+'"> '+value.course+'</span>');
                      }
                   }
                });
               }
             }
             //timeout: 10000
          });
     return false;
 }


function storeData(andSubmit){
  dataString = '';
  jQuery('div.instructor-list li.active').each( function(index) {
    if (jQuery(this).find("#iname form").length == 0)  //contains no form
        dataString += jQuery.trim(jQuery(this).find("#iname").text())+'*';
     else
        dataString += jQuery.trim(jQuery(this).attr("title"))+'*';
     jQuery(this).find(".icourse").each( function(index) {
       dataString += jQuery.trim(jQuery(this).text()) + ',';
     });
     dataString = dataString.replace(/,$/,'*');
     dataString += jQuery.trim(jQuery(this).attr("title")) + ':';
  });
  dataString = dataString.replace(/:$/,'');
  jQuery("input#instructordata").val(dataString);
  if (andSubmit) document.forms["settings-form"].submit();
}


function getDepartmentData(department,stickyyear){

//
// Validate department code here!!!!!
// all alpha 3-4 char uppercase not empty
// 

  if ((department.match(/^[A-Z]*$/)) && ((department.length >= 3)&&(department.length < 5))) {
          jQuery.ajax({
             url: JSParams.ajaxurl,
             type: 'POST',
             data: {
                action: 'ubcdepartment_display_ajax',
                department: department,
                stickyyear: stickyyear,
             },
             beforeSend: function(){
	        //alert('before send:'+JSParams.ajaxurl);
             },
             dataType: 'html', 
             success: function(response){
               if (response == 'error1')
                alert('problem');
               else{
                jQuery("#progressbar" ).progressbar({
                   value: 0,
                   complete: function() {
                        jQuery("#saveBtn").addClass('newlist').fadeIn('slow');
                        jQuery(".progress-label").text( "Complete!" );
                        jQuery(this).fadeOut('slow');
                        storeData(false);
                   }
                }).fadeIn('slow');
                numcourses = 0;
                currentcount = 0;
                var obj = jQuery.parseJSON(response);
                jQuery.each( obj.data, function( key, value ) {
                      numcourses++;
                      getInstructorData(department,value.code,stickyyear);
                });
                jQuery('div.instructor-list div#status').html('total courses: '+numcourses);
               }
             },
             timeout: 3000
          });
  }
  else
    alert('Sorry cannot find course code in list - please select from list.');
 }
