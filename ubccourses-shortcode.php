<?php
/*
Plugin Name: UBC Courses Shortcode
Plugin URI: 
Description: Allows the listing of UBC courses and sections with data from the UBC calendar.
Version: .8 (beta)
Author: Michael Ha (CTLT) extended by Shaffiq Rahemtulla (ArtsISIT)
Licence: GPLv2
Author URI: http://isit.arts.ubc.ca
*/

class UBCCourses {
	
	public function __construct(){
     
                //Add the javascript only once per page
                wp_enqueue_script('modal', plugin_dir_url(__FILE__).'js/bootstrap-modal.js',array('jquery'),'', true );
                wp_enqueue_script('myscript2', plugin_dir_url(__FILE__).'js/ubccourses.js');
                wp_enqueue_style('mystyle', plugin_dir_url(__FILE__).'css/style.css');

                //Add Ajax actions
                add_action('wp_ajax_ubcsections_display_ajax',array($this, 'ubcsections_display_ajax'));
                add_action('wp_ajax_nopriv_ubcsections_display_ajax',array($this, 'ubcsections_display_ajax'));

                // Add shortcode
		add_shortcode('ubccourses', array($this, 'shortcode'));
	}
	
	private function getList($department, $course, $pills, $pillcount, $tabs, $tabcount){
		include_once 'ubcCalendarAPI.php';
		$ubccalendarAPI = new ubcCalendarAPI($department, $course, false);
                $xml = simplexml_load_string($ubccalendarAPI->XMLData);
                if(!$ubccalendarAPI->fromTransient)
                   $fserver_label = '<button class="btn btn-mini btn-danger status" type="button">from Server</button>';
                else 
                   $fserver_label = '<button class="btn btn-mini btn-success status" type="button">from Transients</button>';
                $count = 0;
                foreach ($xml->course as $courses) { 
                   $params = "'".$department."', '".$courses['key']."' ";  
                   $section = '<a onclick="getSectionData('.$params.');" href="#myModal" role="button" class="btn btn-mini modalbox" data-toggle="modal">Sections</a>';
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
            return $fserver_label.$output.$this->display_modal();
	}

        //Ajax function - dynamically get sections of a course
        public function ubcsections_display_ajax () {
           //get post parameters    
           $department = $_POST['department'];
           $course = $_POST['course'];
           //return to js
           echo $this->show_section_table($department,$course);  
           die();
        }

        public function show_section_table($department,$course) {  
		include_once 'ubcCalendarAPI.php';
		$ubccalendarAPI = new ubcCalendarAPI($department, $course, true);
                $xml = simplexml_load_string($ubccalendarAPI->XMLData);
                if(!$ubccalendarAPI->fromTransient)
                    $fserver_label = '<button class="btn btn-mini btn-danger status" type="button">from Server</button>';
                else 
                    $fserver_label = '<button class="btn btn-mini btn-success status" type="button">from Transients</button>';
                $count = 0; 
                $output = '<table id="ubccsections"><td><strong>Sec</strong></td><td><strong>Activity</strong></td><td><strong>Term</strong></td><td><strong>Day</strong></td><td><strong>Bld</strong></td><td><strong>Instructor</strong></td>'; 
                foreach ($xml->section as $sections) {           
                   $ssc_link = "https://courses.students.ubc.ca/cs/main?"."pname=subjarea&tname=subjareas&req=5&dept=".$department."&course=".$course."&section=".$sections['key']."&sessyr=".$ubccalendarAPI->currentYear."&sesscd=".$ubccalendarAPI->currentSession;
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
	           $output = '<br><strong>This course ('.$department.$course.') is not offered in '.$ubccalendarAPI->currentSession.$ubccalendarAPI->currentYear.'.</strong>';
                 return $fserver_label.$output;
        }

        private function display_modal(){
             $output = '<!-- Modal --><div id="myModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button><p style="font-weight:bold;" id="myModalLabel">Modal header</p></div><div class="modal-body"><p>One fine body…</p></div><div class="modal-footer"></div></div>';
             return $output;
        }
	
	public function shortcode($atts){
             //Handle double call if Jetpack is installed
             if (!in_the_loop()) return;
             
             // Params and Defaults
             extract(shortcode_atts(array(
             "department" => '',
             "course" => '',
             "pills" => false,
             "pillcount" => 4,
             "tabs" => false,
             "tabcount" => 4
             ), $atts));
		
             //Get Ajax url and setup js vars
             $ajaxurl = admin_url('admin-ajax.php' );
             return '<script> var ajaxurl = "'.$ajaxurl.'"; </script>'.$this->getList( $department, $course, $pills, $pillcount, $tabs, $tabcount);
	}
}

$ubccourses = new UBCCourses;

