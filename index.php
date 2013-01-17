<?php
add_shortcode('more-course-info-2', 'display_course_info');
 
function display_course_info () {
       $curr_y = date('Y');
       $curr_m = date('n');
       $c_sessyr = $curr_y;
       //$curr_m = 4;
       if ( $curr_m > 5 ) {
               $c_sesscd = "W";
               $c_sessyr = $curr_y;
               show_section_table($c_sessyr, $c_sesscd);
       } elseif ($curr_m < 1 ) {
               $c_sesscd = "W";
               $c_sessyr = $curr_y-1;
               $n_sessyr = $curr_y;
               $n_sesscd = "S";
               show_section_table($c_sessyr, $c_sesscd);
               //show_section_table($n_sessyr, $n_sesscd);
       } else {
               $c_sesscd = "S";
               $c_sessyr = $curr_y;
               $n_sessyr = $curr_y;
               $n_sesscd = "W";
               show_section_table($c_sessyr, $c_sesscd);
               //show_section_table($n_sessyr, $n_sesscd);
       }
}
 
function show_section_table($c_sessyr, $c_sesscd) {
       $c_id = get_the_ID();
       $c_title = get_the_title($c_id);
       //echo $c_title;
       $c_title_pieces = explode(" ", $c_title);
       $c_dept = $c_title_pieces[0];
       $c_course = $c_title_pieces[1];
       $c_req = "4";   
       $xml_src = 'http://courses.students.ubc.ca/cs/servlets/SRVCourseSchedule?'.'sessyr='.$c_sessyr.'&sesscd='.$c_sesscd.'&req='.$c_req.'&dept='.$c_dept.'&course='.$c_course.'&output=3';
       
       //var_dump($xml_src);
       
       $xml = simplexml_load_file($xml_src);
       //var_dump($xml_src);
       //echo $xml_src;
       //var_dump($xml);
       $count = 0;
       foreach ($xml->section as $sections) {
               if ( $sections['activity'] == 'Distance Education') { $count++; }
       }
       //var_dump($count);
       if( $count > 0 ) {      
               echo '<strong>Sections available for '.$c_sessyr.$c_sesscd.'</strong>';
               echo '<table class="subject-table"><tbody>';
               echo '<tr>';
               echo '<th>Status</th>';
               echo '<th>Section</th>';
               echo '<th>Start Date</th>';
               echo '<th>End Date</th>';
               echo '<th>Instructor</th>';
               echo '</tr>';
               foreach ($xml->section as $sections) {
                       if ( $sections['activity'] == 'Distance Education') {           
                               $ssc_link = "https://courses.students.ubc.ca/cs/main?".
                               
"pname=subjarea&tname=subjareas&req=5&dept=".$c_dept."&course=".$c_course."&section=".$sections['key']."&sessyr=".$c_sessyr."&sesscd=".$c_sesscd;
                               $inst_link =
"https://courses.students.ubc.ca/cs/main?pname=inst&ubcid=".
                               $sections->instructors->instructor['ubcid'];
                               echo '<tr>';
                               echo '<td>'.$sections['status'].'</td>';
                               echo '<td><a href="'.$ssc_link.'">'.
                               $c_dept.$c_course.' '.$sections['key'].'</a></td>';
                               echo
'<td>'.$sections->teachingunits->teachingunit['startwk'].'</td>';
                               echo
'<td>'.$sections->teachingunits->teachingunit['endwk'].'</td>';
                               echo '<td><a href="'.$inst_link.'">'.
                               $sections->instructors->instructor['name'].'</a></td>';
                               echo '</tr>';
                       }
               }
               echo '</tbody></table>';
       } else {
                       echo '<strong>This course is not offered in '.$c_sessyr.$c_sesscd.'.</strong>';
                }
}
