<?php

class ubcCalendarAPI {

    private $urlBase = 'http://courses.students.ubc.ca/cs/servlets/SRVCourseSchedule?';
    var $XMLData = '';
    var $profileData;
    var $fromTransient = true;
    var $dataURL = '';
    var $currentYear = '';
    var $currentSession ='';
    var $isSection = false;
    var $department = '';
    var $course = 0;
    var $req = 0;
	
     public function __construct($department,$course,$isSection){
           $this->department = $department;
           $this->course = $course;
           $this->isSection = $isSection;
           $this->getCurrentSession();
           $this->getCurrentYear();
           $this->getReq();
           $this->getDataURL();
           $this->getCalendarData();
           $this->getProfileData();
     }

//###########MOD###########
      private function getProfileData(){
          //Set Unique key using department code
          $key = 'ubccc_profiles'.md5($this->department);    //chk
        
          //Get transient value 
          $profileDataSerialized = get_transient($key);      //chk

          //If the transient does not exist 
          if (empty($profileDataSerialized)){                //chk

             //setup query set limit and type
             query_posts("showposts=200&post_type=profile_cct");    
  
             // the loop
             if (have_posts()) {                            //chk
                 $count = 0;
                 while (have_posts()){                      //chk
                    the_post();                             //chk

                    //Get meta data and create the data

                    $custom_fields = get_post_custom($post->ID);                       //chk
                    $profile_custom_field = $custom_fields['profile_cct'];             //chk
                    $profiledataArray = maybe_unserialize($profile_custom_field[0]);   //chk

                    //Create the dataArray
                    $firstname = trim($profiledataArray['name']['first']);             //chk
                    $lastname = trim($profiledataArray['name']['last']);               //chk
                    $dataArray[$count] = $lastname.', '.$firstname;
                    $count++;
                  }
             }
             else{
               $dataArray[0] = "no profiles";
             }
             wp_reset_query();                                       //chk

             //serialize this before putting it back into transients
             $profileDataSerialized = maybe_serialize($dataArray);   //chk

             //set the transients and timeout
             set_transient($key,$profileDataSerialized,180);          //chk

             //set the class var
             $this->profileData = $dataArray;                        //chk
          }  
          else{ //transient data exists!! Could be crap!!!

             //set the class var
             $this->profileData = maybe_unserialize($profileDataSerialized); //chk
          }     
     }
//###########MOD###########

     private function getCurrentSession() {
       $currentMonth = date('n');
       if (($currentMonth > 5)&&($currentMonth < 9)) $this->currentSession = "S";  else  $this->currentSession = "W";
     }

     private function getCurrentYear() {
       $currentYear = date('Y');  $currentMonth = date('n');
       if (( $this->currentSession = "W" ) && ($currentMonth > 0)) $this -> currentYear = $currentYear-1; else $this -> currentYear = $currentYear;
    }

     private function getReq() {
        if ($this->isSection) $this->req = 4;
        else if ($this->course > 0) $this->req = 3; else $this->req = 2;
     }

     private function getDataURL() {
        $this->dataURL = $this->urlBase.'sessyr='.$this->currentYear.'&sesscd='.$this->currentSession.'&req='.$this->req.'&dept='.$this->department.'&course='.$this->course.'&output=3'; 
     }

     private function getCalendarData(){
          $key = 'ubccc'.md5($this->dataURL);            //Set Unique key using url
          $this->XMLData = get_transient($key);          //Get transient value
          if (empty($this->XMLData)){          //If the transient does not exist 
             if ($this->get_file_contents_from_calendar()){  //return is true
                $this->fromTransient = false;
                set_transient($key,$this->XMLData,180);
             }
          }       
     }

      private function get_file_contents_from_calendar(){
                    $ctx = stream_context_create(array('http'=>array('timeout'=> 10)));          //Set timeout using stream context - 10secs??
                    $value = @file_get_contents($this->dataURL,0,$ctx); 
                    echo $value;
                    if (!$value) {
                        $this->XMLData = '<p>Server Timeout</p><br>';
                        return false;
                    }
                    else{  //Check if XML returns
                        if ( (strpos($value,'courses') <= 0) && (strpos($value,'sections') <= 0) ) {          //if value doesn't contain "courses" or "sections"
                              $this->XMLData = '<p>No XML - Empty file</p><br>';
                              return false;
                        }
                        else{ //Clean up UBC's XML returns
                              $value = preg_replace_callback('#[\\xA1-\\xFF](?![\\x80-\\xBF]{2,})#', 'utf8_encode_callback', $value);
                               $this->XMLData = utf8_encode($value);
                               return true;
                        }
                    }
      }


}