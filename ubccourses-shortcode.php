?php
/*
Plugin Name: UBC Courses Shortcode
Plugin URI: 
Description: Allows the listing of UBC courses and sections with data from the UBC calendar.
Version: .8 (beta)
Author: Michael Ha (CTLT) extended by Shaffiq Rahemtulla (ArtsISIT)
Licence: GPLv2
Author URI: http://isit.arts.ubc.ca
*/

// Register a new shortcode: [ubccourses]
add_shortcode("ubccourses", "ubccourses_shortcode");

function ubccourses_shortcode($atts) {

       //Handle double call if Jetpack is installed
       if ( !in_the_loop() )
          return;

       // Defaults
       extract(shortcode_atts(array(
          "department" => '',
          "course" => '',
          "pills" => false,
          "pillcount" => 4,
          "tabs" => false,
          "tabcount" => 4
       ), $atts));

       //Define paths to JS folder
       define( 'ubccourses_INSERTJS', plugin_dir_url(__FILE__).'js' );
       define( 'ubccourses_INSERTCSS', plugin_dir_url(__FILE__).'css' );

       //Add the javascript only once per page
       wp_enqueue_script('myscript', ubccourses_INSERTJS.'/ubccourses.js');
       wp_enqueue_style('mystyle', ubccourses_INSERTCSS.'/style.css');

       //Get current year and session
       $curr_y = date('Y');
       $curr_m = date('n');
       $c_sessyr = $curr_y;
       if (($curr_m > 5)&&($curr_m < 9)) {
           $c_sesscd = "S";
           $c_sessyr = $curr_y;
       } 
       else{
           if (($curr_m >= 9)||($curr_m <=5)) {
               $c_sesscd = "W";
               if ($curr_m > 0)
                 $c_sessyr = $curr_y-1;
           }
       }

       //Get Ajax url and setup js vars
       $ajaxurl = admin_url('admin-ajax.php' );
       $output .= '<script> var ajaxurl = "'.$ajaxurl.'"; </script>';

       //Get Courses Data and display
       $output .= show_dept_table($c_sessyr, $c_sesscd, $department ,$course, $pills, $pillcount, $tabs, $tabcount);
       return $output;  
}

function get_XML_data($url){

       //Set boolean whether from transients
       $from_server = 0;

       //set Default 
       $trans_has_data = true;

       //Set Unique key using url
       $key = 'ubcc'.md5($url);

       //Get transient value
       $value = get_transient($key);

       //Server provides no exceptions so need to check data for errors
       //if value doesn't contain data
       if (trim($value) == '') {$trans_has_data = false;}

       //if value doesn't contain "courses" or "sections"
       if ( (strpos($value,'courses') <= 0) || (strpos($value,'sections') <= 0) ) {
          $trans_has_data = false;
       }

       //If the transient does not exist or has expired or has no data, refresh it
       if (empty($value) || ($trans_has_data)){
          $value = get_file_contents_from_calendar($url);
          if (!value){
          }
          else{
             $from_server = 1;
             set_transient($key,$value,180);
          }
       }
       return $from_server.$value;
}

function get_file_contents_from_calendar($url){
       //Set timeout using stream context - 10secs??
       $ctx = stream_context_create(array(
           'http'=>array(
                    'timeout'=> 10
                   )
       ));
       
       //Suppress errors - 
       $value = @file_get_contents($url,0,$ctx); 
       if (!$value)
          return $value;
       else{

          //Clean up UBC's XML returns
          $value = preg_replace_callback('#[\\xA1-\\xFF](?![\\x80-\\xBF]{2,})#', 'utf8_encode_callback', $value);
          return utf8_encode($value);
       }
}

function display_modal(){
       $output = '
        <!-- Modal -->
        <div id="myModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
          <p style="font-weight:bold;" id="myModalLabel">Modal header</p>
        </div>
        <div class="modal-body">
          <p>One fine body…</p>
        </div>
        <div class="modal-footer">
        </div>
        </div>';
       return $output;
}

function show_dept_table($c_sessyr, $c_sesscd, $department, $course, $pills, $pillcount,$tabs, $tabcount) {
       if ($course>0){$req = 3;}else{$req = 2;}
       $xml_src = 'http://courses.students.ubc.ca/cs/servlets/SRVCourseSchedule?'.'sessyr='.$c_sessyr.'&sesscd='.$c_sesscd.'&req='.$req.'&dept='.$department.'&course='.$course.'&output=3';    
       $XMLPayload = get_XML_data($xml_src);
       $xml = simplexml_load_string(substr($XMLPayload,1));
       $from_server = $XMLPayload[0];
       if ($from_server==1)
          $fserver_label = '<button class="btn btn-mini btn-danger status" type="button">from Server</button>';
       else 
          $fserver_label = '<button class="btn btn-mini btn-success status" type="button">from Transients</button>';
       $count = 0;
       foreach ($xml->course as $courses) { 
         $params = "'".$c_sessyr."', '".$c_sesscd."', '".$department."', '".$courses['key']."' ";  
         $section = '<a onclick="getSectionData('.$params.');" href="#myModal" role="button" class="btn btn-mini" data-toggle="modal">Sections</a>';
         if (empty($course)&&($pills)||empty($course)&&($tabs)){
            $cindex = substr($courses['key'], 0, 1);
            $coursetabs[$cindex] .= '<p><strong>'.$department.$courses['key'].' '.$courses['title'].' '.$section.'</strong></p><p>'.$courses['descr'].'</p>';
         }
         else{
            $output .= '<p><strong>'.$department.$courses['key'].' '.$courses['title'].' '.$section.'</strong></p><p>'.$courses['descr'].'</p>';
         }
         $count++;
       }
       if( $count == 0 )
         $output = '<strong>Course(s) Not Found: Example [ubccourses department=ANTH] <br>(default is ALL courses add e.g. courses ="100A" for specific courses)</strong>';
       if (empty($course)&&($pills)||empty($course)&&($tabs)){
          $tabcount = 0;
          if ($pills)
            $tabhead = '<ul class="nav nav-pills btn-mini" id="tabs" data-tabs="tabs">';
          else
            $tabhead = '<ul class="nav nav-tabs btn-mini" id="tabs" data-tabs="tabs">';
          foreach ($coursetabs as $coursetab){
            $tabcount++;
            if ($tabcount <= $pillcount){
             if ($tabcount == 1){
              $tabhead .= '<li class="active"><a data-toggle="tab" href="#'.$department.$tabcount.'">'.$tabcount.'00 level courses</a></li>';
              $output .= '<div class="tab-pane active" id="'.$department.$tabcount.'">'.$coursetab.'</div>';
             }
             else{
              $tabhead .= '<li><a data-toggle="tab" href="#'.$department.$tabcount.'">'.$tabcount.'00 level courses</a></li>';
              $output .= '<div class="tab-pane" id="'.$department.$tabcount.'">'.$coursetab.'</div>';
             }
            }
          }
          $output = $tabhead.'</ul><div class="tab-content">'.$output.'</div>';
       }
       return $fserver_label.$output.display_modal();
} 

//Add Ajax actions
add_action('wp_ajax_ubcsections_display_ajax','ubcsections_display_ajax');
add_action('wp_ajax_nopriv_ubcsections_display_ajax','ubcsections_display_ajax');

//Ajax function - dynamically get sections of a course
function ubcsections_display_ajax () {
      //get post parameters    
      $department = $_POST['department'];
      $course = $_POST['course'];
      $sessyr = $_POST['sessyr'];
      $sesscd = $_POST['sesscd'];
      //return to js
      echo show_section_table($sessyr, $sesscd, $department,$course);  
      die();
}

function show_section_table($sessyr, $sesscd, $department,$course) {  
       $xml_src = 'http://courses.students.ubc.ca/cs/servlets/SRVCourseSchedule?'.'sessyr='.$sessyr.'&sesscd='.$sesscd.'&req=4&dept='.$department.'&course='.$course.'&output=3';
       $XMLPayload = get_XML_data($xml_src);
       $xml = simplexml_load_string(substr($XMLPayload,1));
       $from_server = $XMLPayload[0];
       if ($from_server==1)
          $fserver_label = '<button class="btn btn-mini btn-danger status" type="button">from Server</button>';
       else 
          $fserver_label = '<button class="btn btn-mini btn-success status" type="button">from Transients</button>';
       $count = 0; 
       $output = '<table id="ubccsections"><td><strong>Sec</strong></td><td><strong>Activity</strong></td><td><strong>Term</strong></td><td><strong>Day</strong></td><td><strong>Bld</strong></td><td><strong>Instructor</strong></td>'; 
       foreach ($xml->section as $sections) {           
                   $ssc_link = "https://courses.students.ubc.ca/cs/main?"."pname=subjarea&tname=subjareas&req=5&dept=".$department."&course=".$course."&section=".$sections['key']."&sessyr=".$sessyr."&sesscd=".$sesscd;
                   $inst_link = "https://courses.students.ubc.ca/cs/main?pname=inst&ubcid=".$sections->instructors->instructor['ubcid'];
                   $output .= '<tr><td><a href="'.$ssc_link.'">'.$sections['key'].'</a></td><td>'.$sections['activity'].'</td>';
                   $meetings = $sections->teachingunits->teachingunit->meetings->meeting;
                   $term =array();$day =array();$bld =array();
                   foreach ($meetings as $meeting){
                      if (!in_array(trim($meeting['term']), $term))
	                 array_push($term,trim($meeting['term']));
                      if (!in_array(trim($meeting['day']), $day))
	                 array_push($day,trim($meeting['day']));
                      if (!in_array(trim($meeting['buildingcd']), $bld))
	                 array_push($bld,trim($meeting['buildingcd']));
                   }
                   $output .= '<td>'.implode(" ",$term).'</td><td>'.implode(" ",$day).'</td><td>'.implode(" ",$bld).'</td>';
                   $output .= '<td><a href="'.$inst_link.'">'.$sections->instructors->instructor['name'].'</a></td></tr>';
				   $count ++;
        }
        $output .= '</table>';
        if( $count == 0 ) 
	           $output = '<br><strong>This course ('.$department.$course.') is not offered in '.$sessyr.$sesscd.'.</strong>';
        return $fserver_label.$output;
}
?>