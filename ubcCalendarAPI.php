<?php

class ubcCalendarAPI {

    private $urlBase = 'http://courses.students.ubc.ca/cs/servlets/SRVCourseSchedule?';
    var $XMLData = '';
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
     }
	
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