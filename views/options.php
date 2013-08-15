<script type="text/javascript">var __namespace = '<?php echo $namespace; ?>';</script>

<div class="wrap" style="padding:20px;">


    <h2><?php echo $page_title; ?></h2>


     <?php 
      if( isset( $_GET['message'] ) ){
       if( $_GET['message'] == 1  )
        echo '<div id="message" class="updated below-h2"><p>Options successfully updated!</p></div>';
       if( $_GET['message'] == 2  )
        echo '<div id="message" class="error below-h2"><p>Not Updated - Size limit Exceeded</p></div>';
       }
      ?> 

<h4>A simple OOP WordPress plugin that allows the listing of UBC courses, sections and instructors with data from the UBC calendar.</h4>

<p>In order to collect instructors from a particular course code, please choose the course code from the drop down selection list and click the "Get Instructors" button. Once the operation is complete, click the "Save Changes" button.</p>

<p>To delete instructors from the list, click on the red delete button (the list item should fade) and click on "Save Changes".</p>

<p>If you make a mistake in deleting or click once too many, do not panic - just click "Clear" and then "Save Changes" and begin again - Remember the data is just being gathered from UBC Calendar and is being cached so, you are not calling the remote Calendar server over and over again. </p>

<i>Hint: For instructors in multiple disciplines, select course code and click the "Get Instructors" button again. Continue to build the list of instructors to suit your website.</i><br><br>

<i>Hint: For instructors that wish to use a different name than that which appears in the calendar, double-click on the name in the list, type in the preferred name, click change and make sure to save the changes - note: these name changes are NOT persistent and will need to be re-done if the instructor is removed from the list and is brought in again.</i>

<style>#ubcinstructorsmodal,#ubccoursesmodal{display:none;} .ui-widget-header .ui-icon{} </style>

<p>In order to view course lists within your content, use the <a href="#" id="opens_ubccourses">ubccourses shortcode.</a> and to view courses that an instructor teaches use the <a href="#" id="opens_ubcinstructors">ubcinstructors shortcode</a></p>

<div id="ubccoursesmodal">
    <p>In its basic form the [ubccourses department=x] shortcode allows the listing of UBC courses with data obtained from the UBC Calendar. Some of the allowed parameters are as below:</p>

<ul>
<li><strong>department</strong>  - **required – if left empty will show example of usage.</li>
<li><strong>course</strong> - if department filled and course empty then you get all departmental courses listed for the session</li>
<li><strong>tabs</strong> - default false – if true, the data is setup with tabs for each year level of courses.</li>
<li><strong>pills</strong> - if true, the data is setup with pills instead of tabs</li>
<li><strong>tabcount</strong> - default 4 – if set, the data is truncated at the year level of courses. If set to 'u', levels 100 through 400 are displayed. If set to 'g', levels 500 and 600 are displayed. To display courses for a single year level, use 'n*' (e.g. n3 shows 300 level courses only). Note: tabcount parameter can be used without tabs or pills as a list filter.</li>
<li><strong>parentslug</strong> - if entered, any page title (of the form e.g. “ANTH201A” that matches and has a parent equal to the slug will be linked to from the course list with a “Details” button</li>
<li><strong>desc_category</strong> - the category slug - if entered, any post title (of the form e.g. “ANTH201A” that has the category equal to the slug will shown in an accordion format below the calendar description with the accordion header being set to the category name (not slug).</li>
<li><strong>opentab</strong> - default 1 – has to be between 1 and tabcount – if entered will auto open at that tab/pill</li>
<li><strong>profileslug</strong> - if entered and a profile exists on the website, shows a link next to instructors name in the sections listing.</li>
<li><strong>stickywinter</strong> - if true session remains as Winter even if Summer term has begun.</li>
<li><strong>stickyyear</strong> - if true session year is forced to current year.</li>
<li><strong>instructors</strong> - if true and plugin configured (via the settings panel), will list instructors on the main course listing page (without users having to click on the "sections" button to see them).<i> Note: If the calendar name differs from the profile name, the cross match can be made on the plugin admin page by double clicking the instructor name on the list and setting it to be the same as the profile name.</i></li>
</ul>
</div>

<div id="ubcinstructorsmodal">
<p>In its basic form the [ubcinstructors instructorname="lastname, firstname"] shortcode allows the listing of UBC courses that the instructor teaches in the current session. The instructor name has to match exactly (no fuzzy matching in this version) with the name in the UBC Calendar. Some of the allowed parameters are as below:</p> 


<ul>
<li><strong>instructorname</strong> - if entered and plugin configured (via the settings panel), will list all courses that an instructor teaches within the current session.
<li><strong>parentslug</strong> - if entered, any page title (of the form e.g. “ANTH201A” that matches and has a parent page equal to the slug will be linked to from the course list with a “Details” button</li>
<li><strong>profileslug</strong> - if entered and a profile exists on the website, shows a link next to instructors name in the sections listing.</li>
<li><strong>stickywinter</strong> - if true session remains as Winter even if Summer term has begun.</li>
<li><strong>stickyyear</strong> - if true session year is forced to current year.</li>
<li><strong>instructors</strong> - if true and plugin configured (via the settings panel), will list instructors on the main course listing page (without users having to click on the "sections" button to see them). <i>Note: If the calendar name differs from the profile name, the cross match can be made on the plugin admin page by double clicking the instructor name on the list and setting it to be the same as the profile name.</i></li>
</ul>

<i>**If used without the instructorname parameter and on a profile singular page, will show courses taught by that instructor. <i>Hint: Can be placed in "if empty" container in a profile field</i>.
</div>

<p></p>


   <div id="progressbar" style="display:none;height:20px;margin-top:5px;width:303px;">
     <div class="progress-label" style="margin:45%;margin-top:1px;font-weight:normal;">
     </div>
   </div>

<div id="getinst">
<h4>Select course code below to build Instructor list:</h4>
<?php echo $this->getDeptCodes();?>


<div class="btn-group" style="display:inline-block;">
   <button class="button-primary" style="border-radius:4px;margin-right:4px;" onclick="sticky=false;if (stickyyear.checked == 1) sticky = true;param = document.getElementById('department').value.toUpperCase();getDepartmentData(param,sticky)">Get Instructors</button>
   <button id="clearBtn" class="button-primary" style="border-radius:4px;margin-right:4px;" >Clear</button>
   <button id="revertBtn" class="button-primary" style="border-radius:4px;margin-right:4px;" >Revert</button>
   <button id="saveBtn" class="button-primary" style="display:none;border-radius:4px;margin-right:4px;" >Save Changes</button>
   <input type="checkbox" id="stickyyear" name="stickyyear" value="1">stickyyear?<br>
</div>
<div class='instructor-list'><div id='status'></div><ul><?php echo $this->get_option( 'option_2',true );?></ul></div>
</div>
        
    <form class="settings-form" id="settings-form" action="" method="post" id="<?php echo $namespace; ?>-form">
        <?php wp_nonce_field( $namespace . "-update-options" ); ?>
        
         <p>
            <label><input type="hidden" id="instructordata" type="text" name="data[option_2]" value="<?php echo $this->get_option( 'option_2',false ); ?>" /></label> 
        </p>
        
        <!--<p class="isubmit">
            <input type="submit" name="Submit" class="button-primary" value="<?php _e( "Save Changes", $namespace ) ?>" />-->
        </p>
    </form>
</div>

<div id="revert" style="display:none;"><?php echo $this->get_option( 'option_2',true );?></div>