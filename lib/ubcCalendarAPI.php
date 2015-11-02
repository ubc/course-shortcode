<?php
class ubcCalendarAPI {

	private $urlBase = 'http://courses.students.ubc.ca/cs/servlets/SRVCourseSchedule?';
	var $XMLData = '';
	var $profileData;
	var $fromTransient = true;
	var $dataURL = '';
	var $currentYear = '';
	var $currentSession = '';
	var $isSection = false;
	var $stickywinter = false;
	var $stickyyear = false;
	var $department = '';
	var $course = 0;
	var $req = 0;

	public function __construct( $department, $course, $stickywinter, $stickyyear, $isSection ) {
		   $this->department = $department;
		   $this->course = $course;
		   $this->isSection = $isSection;
		   $this->stickywinter = $stickywinter;
		   $this->stickyyear = $stickyyear;
		   $this->getCurrentSession();
		   $this->getCurrentYear();
		   $this->getReq();
		   $this->getDataURL();
		   $this->getCalendarData();
		   $this->getProfileData();
	}

	 //###########MOD###########
	private function getProfileData() {
		//Set Unique key using department code
		$key = 'ubccc_profiles'.$this->department;    //chk

		//Get transient value
		$profileDataSerialized = get_transient( $key );      //chk

		//If the transient does not exist
		if ( false === $profileDataSerialized ) {                //chk

			//setup query set limit and type
			//query_posts("showposts=200&post_type=profile_cct");
			$profiles = new WP_Query( array(
					 'showposts' => 200,
					 'post_type' => 'profile_cct',
					)
			);
				  // the loop
			if ( $profiles->have_posts( ) ) {                            //chk
				$count = 0;
				while ( $profiles->have_posts( ) ) {                      //chk
					$profiles->the_post();                             //chk
					//Get meta data and create the data
					$custom_fields = get_post_custom( $profiles->$post->ID );                       //chk
					$profile_custom_field = $custom_fields['profile_cct'];             //chk
					$profiledataArray = maybe_unserialize( $profile_custom_field[0] );   //chk
					//Create the dataArray
					$firstname = trim( $profiledataArray['name']['first'] );             //chk
					$lastname = trim( $profiledataArray['name']['last'] );               //chk
					$dataArray[0][ $count ] = strtoupper( $lastname ).', '.strtoupper( $firstname );
					$dataArray[1][ $count ] = get_permalink( $profiles->$post->ID );
					$dataArray[2][ $count ] = $lastname.', '.$firstname;
					$count++;
				}
			} else {
					$dataArray[0] = 'no profiles';
			}
			wp_reset_query();                                       //chk

			//serialize this before putting it back into transients
			$profileDataSerialized = maybe_serialize( $dataArray );   //chk

			//set the transients and timeout
			set_transient( $key, $profileDataSerialized,60 );          //chk

			//set the class var
			$this->profileData = $dataArray;                        //chk
				//print_r($dataArray);
		} else { //transient data exists!! Could be crap!!!
				  //set the class var
				  $this->profileData = maybe_unserialize( $profileDataSerialized ); //chk
		}
	}

	function getCurrentSession() {
		switch ( $this->stickywinter ) {
			case 'W':
				$this->currentSession = 'W';
				break;
			case 'S':
				$this->currentSession = 'S';
				break;
			case true:
				$this->currentSession = 'W';
				break;
			default:
				$currentMonth = date( 'n' );
				if ( ( $currentMonth >= 5 ) && ( $currentMonth < 9 ) ) {
					$this->currentSession = 'S';
				} else {
					$this->currentSession = 'W';
				}
		}
	}


	function getCurrentYear() {
		$currentYear = date( 'Y' );
		$currentMonth = date( 'n' );
		if ( ( ( $this->currentSession == 'W' ) && ( $currentMonth > 8 )) || ( $this->stickyyear ) ) {
			$this -> currentYear = $currentYear;
		} else {
			$this -> currentYear = $currentYear - 1;
		}
	}

	private function getReq() {
		if ( $this->isSection ) {
			$this->req = 4;
		} else {
			if ( $this->course > 0 ) {
				$this->req = 3;
			} else {
				if ( $this->department == '' ) {
					$this->req = 0;
				} else {
					$this->req = 2;
				}
			}
		}
	}

	private function getDataURL() {
		$this->dataURL = $this->urlBase.'sessyr='.$this->currentYear.'&sesscd='.$this->currentSession.'&req='.$this->req.'&dept='.$this->department.'&course='.$this->course.'&output=3';
	}

	private function getCalendarData() {
		$key = 'ubccc'.md5( $this->dataURL );            //Set Unique key using url
		$this->XMLData = get_transient( $key );          //Get transient value
		if ( empty( $this->XMLData ) ) {          //If the transient does not exist
			if ( $this->get_file_contents_from_calendar() ) {  //return is true
				$this->fromTransient = false;
				set_transient( $key,$this->XMLData, 180 );
			}
		}
	}

	private function get_file_contents_from_calendar() {
		$ctx = stream_context_create( array( 'http' => array( 'timeout' => 10 ) ) );          //Set timeout using stream context - 10secs??
		$value = @file_get_contents( $this->dataURL,0,$ctx );
		//echo $value;
		if ( ! $value ) {
			$this->XMLData = '<p>Server Timeout</p><br>';
			return false;
		} else {  //Check if XML returns
			if ( ( strpos( $value, 'courses' ) <= 0 ) && ( strpos( $value, 'sections' ) <= 0 ) && ( strpos( $value, 'depts' ) <= 0 ) ) {           //if value doesn't contain "courses" or "sections"
				$this->XMLData = '<p>No XML - Empty file</p><br>';
				return false;
			} else { //Clean up UBC's XML returns
				$value = preg_replace( '/[\x80-\xFF]/', '', $value );
				$value = $this->stripInvalidXml( $value );
				$this->XMLData = utf8_encode( $value );
				return true;
			}
		}
	}

	/* @access public
	 * @param string $value
	 * @return string
	 */
	private function stripInvalidXml( $value ) {
		$ret = '';
		$current;
		if ( empty( $value ) ) {
			return $ret;
		}
		$length = strlen( $value );
		for ( $i = 0; $i < $length; $i++ ) {
			$current = ord( $value{$i} );
			if ( ( $current == 0x9 ) ||
				( $current == 0xA ) ||
				( $current == 0xD ) ||
				( ( $current >= 0x20 ) && ( $current <= 0xD7FF ) ) ||
				( ( $current >= 0xE000 ) && ( $current <= 0xFFFD ) ) ||
				( ( $current >= 0x10000 ) && ( $current <= 0x10FFFF ) ) ) {
				$ret .= chr( $current );
			} else {
				$ret .= ' ';
			}
		}
		return $ret;
	}

}
