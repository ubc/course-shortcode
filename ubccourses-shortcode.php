<?php
/*
Plugin Name: UBC Courses
Plugin URI: https://github.com/ubc/course-shortcode
Description: Allows the listing of UBC courses and sections with data from the UBC calendar.
Version: 1.0.2
Author: Michael Ha/Enej Bajgoric/Shaffiq Rahemtulla/Navid Fattahi
Author URI: http://isit.arts.ubc.ca
License: GPL3

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Include constants file
require_once( dirname( __FILE__ ) . '/lib/constants.php' );

class UBC_Courses {
    var $namespace = "ubccourses";
    var $friendly_name = "UBC Courses";
    var $version = "1.0.2";
    var $maxcharlimit = 10000; //limit for option size
    
    // Default plugin options
    var $defaults = array(
        'option_2' => ""
    );
    
    /**
     * Instantiation construction
     * 
     * @uses add_action()
     * @uses UBC_Courses::wp_register_scripts()
     * @uses UBC_Courses::wp_register_styles()
     */
    function __construct() {

//error_reporting(E_ERROR | E_WARNING | E_PARSE);
//ini_set('display_errors', 'On');

        // Name of the option_value to store plugin options in
        $this->option_name = '_' . $this->namespace . '--options';
		
        // Load all library files used by this plugin
        $libs = glob( UBC_COURSES_DIRNAME . '/lib/*.php' );
        foreach( $libs as $lib ) {
            include_once( $lib );
        }
        
	// Add all action, filter and shortcode hooks
	$this->_add_hooks();
    }
    
    /**
     * Add in various hooks
     * 
     * Place all add_action, add_filter, add_shortcode hook-ins here
     */
    private function _add_hooks() {
        // Options page for configuration
        add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
        // Route requests for form processing
        add_action( 'init', array( &$this, 'route' ) );
        
        // Add a settings link next to the "Deactivate" link on the plugin listing page
        add_filter( 'plugin_action_links', array( &$this, 'plugin_action_links' ), 10, 2 );
        // Register front-end scripts for this plugin
        
        add_action('wp_enqueue_scripts', array( &$this, 'add_styles' ) );
        
        
        // Register all JavaScripts for this plugin
        add_action( 'init', array( &$this, 'wp_register_scripts' ), 1 );
        // Register all Stylesheets for this plugin
        add_action( 'init', array( &$this, 'wp_register_styles' ), 1 );

        //Add Ajax actions
        add_action('wp_ajax_ubcsections_display_ajax',array(&$this, 'ubcsections_display_ajax'));
        add_action('wp_ajax_nopriv_ubcsections_display_ajax',array(&$this, 'ubcsections_display_ajax'));

        //Add Admin Ajax actions
        add_action('wp_ajax_ubcinstructors_display_ajax',array(&$this, 'ubcinstructors_display_ajax'));
        add_action('wp_ajax_ubcdepartment_display_ajax',array(&$this, 'ubcdepartment_display_ajax'));
        add_action('wp_ajax_deptcodelist_display_ajax',array(&$this, 'deptcodelist_display_ajax'));

        // Add shortcodes
		add_shortcode('ubccourses', array($this, 'courses_shortcode'));
		add_shortcode('ubcinstructors', array($this, 'instructors_shortcode'));
    }
    
    /**
     * add_styles function.
     * only add the style.css file on the front end side
     * @access public
     * @return void
     */
    public function add_styles(){
    	wp_enqueue_style('{$this->namespace}-shortstyle', UBC_COURSES_URLPATH.'/css/style.css');
    }
    /**
     * Process update page form submissions
     * 
     * @uses UBC_Courses::sanitize()
     * @uses wp_redirect()
     * @uses wp_verify_nonce()
     */
    private function _admin_options_update() {
        // Verify submission for processing using wp_nonce
        if( wp_verify_nonce( $_REQUEST['_wpnonce'], "{$this->namespace}-update-options" ) ) {
            $data = array();
            $overlimit = false;
            foreach( $_POST['data'] as $key => $val ) {
                if (strlen($val) > $this->maxcharlimit){
                   $overlimit = true;
                   break;
                }
                else
                   $data[$key] = $this->_sanitize( $val );
            }
                        
            // Redirect back to the options page with the message flag to show the saved message
            if ($overlimit)
               wp_safe_redirect( $_REQUEST['_wp_http_referer'] . '&message=2' );
            else{
               // Update the options value with the data submitted
               update_option( $this->option_name, $data );
               wp_safe_redirect( $_REQUEST['_wp_http_referer'] . '&message=1' );
            }
            exit;
        }
    }
    
    /**
     * Sanitize data
     * 
     * @param mixed $str The data to be sanitized
     * @uses wp_kses()
     * @return mixed The sanitized version of the data
     */
    private function _sanitize( $str ) {
        if ( !function_exists( 'wp_kses' ) ) {
            require_once( ABSPATH . 'wp-includes/kses.php' );
        }
        global $allowedposttags;
        global $allowedprotocols;
        
        if ( is_string( $str ) ) {
            $str = wp_kses( $str, $allowedposttags, $allowedprotocols );
        } elseif( is_array( $str ) ) {
            $arr = array();
            foreach( (array) $str as $key => $val ) {
                $arr[$key] = $this->_sanitize( $val );
            }
            $str = $arr;
        }
        
        return $str;
    }

    /**
     * Hook into register_activation_hook action
     * 
     * Put code here that needs to happen when your plugin is first activated (database
     * creation, permalink additions, etc.)
     */
    static function activate() {
        // Do activation actions
    }
	
    /**
     * Define the admin menu options for this plugin
     * 
     * @uses add_action()
     * @uses add_options_page()
     */
    function admin_menu() {
        $page_hook = add_options_page( $this->friendly_name, $this->friendly_name, 'administrator', $this->namespace, array( &$this, 'admin_options_page' ) );
        
        // Add print scripts and styles action based off the option page hook
        add_action( 'admin_print_scripts-' . $page_hook, array( &$this, 'admin_print_scripts' ) );
        add_action( 'admin_print_styles-' . $page_hook, array( &$this, 'admin_print_styles' ) );
    }
    
    
    /**
     * The admin section options page rendering method
     * 
     * @uses current_user_can()
     * @uses wp_die()
     */
    function admin_options_page() {
        if( !current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have sufficient permissions to access this page' );
        }
        
        $page_title = $this->friendly_name . ' Options';
        $namespace = $this->namespace;
        
        include( UBC_COURSES_DIRNAME . "/views/options.php" );
    }
    
    /**
     * Load JavaScript for the admin options page
     * 
     * @uses wp_enqueue_script()
     */
    function admin_print_scripts() {
        wp_enqueue_script( "{$this->namespace}-chosen" );
        $ajaxurl = admin_url('admin-ajax.php' );
        wp_enqueue_script( "{$this->namespace}-admin" );
        wp_enqueue_script( 'jquery-ui-progressbar');
        wp_enqueue_script( 'jquery-ui-dialog');
        $params = array(
            'ajaxurl' => $ajaxurl
        );
        wp_localize_script( "{$this->namespace}-admin", 'JSParams', $params );
    }
    
    /**
     * Load Stylesheet for the admin options page
     * 
     * @uses wp_enqueue_style()
     */
    function admin_print_styles() {
        wp_enqueue_style( "{$this->namespace}-admin" );
        wp_enqueue_style( "{$this->namespace}-chosen" );
        wp_enqueue_style( "{$this->namespace}-jqueryui" );
    }
    
    /**
     * Hook into register_deactivation_hook action
     * 
     * Put code here that needs to happen when your plugin is deactivated
     */
    static function deactivate() {
        delete_option('_ubccourses--options');
    }
    
    /**
     * Retrieve the stored plugin option or the default if no user specified value is defined
     * 
     * @param string $option_name The name of the TrialAccount option you wish to retrieve 
     * @uses get_option()
     * @return mixed Returns the option value or false(boolean) if the option is not found
     */
    function get_option( $option_name, $display_flag ) {
        // Load option values if they haven't been loaded already
        if( !isset( $this->options ) || empty( $this->options ) ) {
            $this->options = get_option( $this->option_name, $this->defaults );
        }
        if( isset( $this->options[$option_name] ) ) {
            if ($display_flag){
              $htmlstr = '';
              $instrArray = explode(":", $this->options[$option_name]);
              foreach ($instrArray as $instrData) {
                 $instrPieces = explode("*", $instrData);
                 $instrName = trim($instrPieces[0]);
                 if (count($instrPieces) == 2) 
                   $cinstrName = trim($instrPieces[0]);
                 else
                   $cinstrName = trim($instrPieces[2]);
                 if (!empty($instrName)){
                    $instID = trim(preg_replace('/[ ,]+/','', $cinstrName));
                    $instrCourseArray = explode(",",$instrPieces[1]);
                    $htmlstr .= '<li  class="active '.$instID.'" title="'.trim($cinstrName).'"><span class="delbtn" onclick="update(this.parentNode);"></span><span id="iname" class="editable">'.trim($instrName).'</span>';
                    foreach ($instrCourseArray as $course) {
                      $htmlstr .= '<span class="icourse '.$course.'">'.$course.'</span>';
                    }
                    $htmlstr .= '</li>';
                 }
              }
              return $htmlstr;
            }
            else{
               return $this->options[$option_name];    // Return user's specified option value
            }
        } elseif( isset( $this->defaults[$option_name] ) ) {
            return $this->defaults[$option_name];   // Return default option value
        }
        return false;
    }
    
	
    /**
     * profile_exists function
     * 
     * @uses in_array()
     * @param mixed $ubccalendarAPI
     * @returns post slug name to be used in url
     */
    function profile_exists($fuzzy,$ubccalendarAPI,$profileslug,$instrName,$flag=true) {
	$urlslugs = explode(', ',strtoupper($instrName));
	$shortest=-1;
	$numchar = 0;
	$count = 0;
	foreach($ubccalendarAPI->profileData[0] as $word){
		$lev=levenshtein(trim($instrName),$word);
		if($lev==0){
			$numchar = strlen($word);
			$mindex = $count;
			$closest=strtoupper($word);
			$shortest=0;
			break;
		}
		if($lev<=$shortest||$shortest<0){
			$numchar = strlen($word);
			$mindex = $count;
			$closest=strtoupper($word);
			$shortest=$lev;
		}
		$count ++;
	}
	$percentage_match = (($numchar-$shortest)/$numchar * 100);
	if ($percentage_match > $fuzzy){
           if ($flag)
	     return $ubccalendarAPI->profileData[1][$mindex];
	   else 
	     return '<a href="'.$ubccalendarAPI->profileData[1][$mindex].'">'.$ubccalendarAPI->profileData[2][$mindex].'</a>';
	}
        else
            return false;
    }
	


   /**
    * get_courseInstructors function.
    * 
    * @access private
    * @param mixed $option_name

    * @param mixed $ubcCourse

    * @param mixed $ubccalendarAPI

    * @param mixed $profileslug
    * @return void
    */
   private function get_courseInstructors($fuzzy, $option_name, $ubcCourse, $ubccalendarAPI, $profileslug ) {
        if( !isset( $this->options ) || empty( $this->options ) ) {
            $this->options = get_option( $this->option_name, $this->defaults );
        }
        if( isset( $this->options[$option_name] ) ) {
                 $htmlstr = '';
                 $instrArray = explode(":", $this->options[$option_name]);
                 $instrCount = 0;
                 foreach ($instrArray as $instrData) {
                     $instrPieces = explode("*", $instrData);
                     $instrName = trim($instrPieces[0]);

                     if ($profileslug){
//MOD
			$profile_link = $this->profile_exists($fuzzy, $ubccalendarAPI,$profileslug,$instrName,false);

                        if ($profile_link)
                            $profileHTML = $profile_link;
                        else 
                            $profileHTML = $instrName;
                     }
                     else 
                         $profileHTML = $instrName;

                     if (strpos($instrPieces[1],$ubcCourse) !== false) {
                             $instrCount++;
                             $instID = trim(preg_replace('/[ ,]+/','', $instrName));
                             $instrCourseArray = explode(",",$instrPieces[1]);
                             $htmlstr .= '<span id="iname"> '.$profileHTML.'</span>';
                     }
                 }
                 if ($instrCount > 0) $htmlstr = '<div id="instrstr"><span>Instructor(s): </span>'.$htmlstr.'</div>';
                 return $htmlstr;
        } elseif( isset( $this->defaults[$option_name] ) ) {
            return '';
        }
        return false;
    }
	
    /**
     * get_instructorCourses function.
     * 
     * @access private
     * @param mixed $option_name

     * @param mixed $profileName

     * @param mixed $parentslug

     * @param mixed $profileslug

     * @param mixed $stickywinter

     * @param mixed $instructors
     * @return void
     */
    private function get_instructorCourses( $fuzzy,$option_name, $profileName, $parentslug, $profileslug, $stickywinter,$instructors, $stickyyear, $desc_category ) {

        //if profile name is empty AND your are on a profile AND singular page
        if ((empty($profileName))&&('profile_cct' == get_post_type($post->ID))&&(is_single())){
           //Get meta data and unserialize
           $custom_fields = get_post_custom($post->ID);
           $profile_custom_field = $custom_fields['profile_cct'];
           $dataArray = maybe_unserialize($profile_custom_field[0]);
           $fname = trim($dataArray['name']['first']);
           $lname = trim($dataArray['name']['last']);
        }

        if( !isset( $this->options ) || empty( $this->options ) ) {
            $this->options = get_option( $this->option_name, $this->defaults );
        }
        if( isset( $this->options[$option_name] ) ) {
                 $instrArray = explode(":", $this->options[$option_name]);
                 foreach ($instrArray as $instrData) {
                     $instrPieces = explode("*", $instrData);
                     $instrName = strtoupper(trim($instrPieces[0]));
//MOD
		     if (empty($profileName))
			$word=strtoupper($lname.', '.$fname);
		     else
			$word=strtoupper($profileName);

		     $numchar = strlen($word);
		     $lev = levenshtein($instrName,$word);
		     $percentage_match = (($numchar-$lev)/$numchar * 100);

                     if ($percentage_match > $fuzzy){
                             $instID = trim(preg_replace('/[ ,]+/','', $instrName));
                             $instrCourseArray = explode(",",$instrPieces[1]);
                             foreach ($instrCourseArray as $course) {
                                 $htmlstr .= $this->getList($fuzzy, substr($course, 0, 4), substr($course, 4), false, false, 4, $parentslug, 1, $profileslug, $stickywinter,$instructors,$stickyyear, $desc_category);
                             }
                             return $htmlstr;
                      }
                 }
        } elseif( isset( $this->defaults[$option_name] ) ) {
            return '';
        }
        return false;
    }




    /**
     * Initialization function to hook into the WordPress init action
     * 
     * Instantiates the class on a global variable and sets the class, actions
     * etc. up for use.
     */
    static function instance() {
        global $UBC_Courses;
        
        // Only instantiate the Class if it hasn't been already
        if( !isset( $UBC_Courses ) ) $UBC_Courses = new UBC_Courses();
    }
	
	/**
	 * Hook into plugin_action_links filter
	 * 
	 * Adds a "Settings" link next to the "Deactivate" link in the plugin listing page
	 * when the plugin is active.
	 * 
	 * @param object $links An array of the links to show, this will be the modified variable
	 * @param string $file The name of the file being processed in the filter
	 */
	function plugin_action_links( $links, $file ) {
		if( $file == plugin_basename( UBC_COURSES_DIRNAME . '/' . basename( __FILE__ ) ) ) {
            $old_links = $links;
            $new_links = array(
                "settings" => '<a href="options-general.php?page=' . $this->namespace . '">' . __( 'Settings' ) . '</a>'
            );
            $links = array_merge( $new_links, $old_links );
		}
		
		return $links;
	}
    
    /**
     * Route the user based off of environment conditions
     * 
     * This function will handling routing of form submissions to the appropriate
     * form processor.
     * 
     * @uses UBC_Courses::_admin_options_update()
     */
    function route() {
        $uri = $_SERVER['REQUEST_URI'];
        $protocol = isset( $_SERVER['HTTPS'] ) ? 'https' : 'http';
        $hostname = $_SERVER['HTTP_HOST'];
        $url = "{$protocol}://{$hostname}{$uri}";
        $is_post = (bool) ( strtoupper( $_SERVER['REQUEST_METHOD'] ) == "POST" );
        
        // Check if a nonce was passed in the request
        if( isset( $_REQUEST['_wpnonce'] ) ) {
            $nonce = $_REQUEST['_wpnonce'];
            
            // Handle POST requests
            if( $is_post ) {
                if( wp_verify_nonce( $nonce, "{$this->namespace}-update-options" ) ) {
                    $this->_admin_options_update();
                }
            } 
            // Handle GET requests
            else {
                
            }
        }
    }

    //Ajax function - dynamically get department codes
    public function deptcodelist_display_ajax () {
           $stickyyear = false;
           if($_POST['stickyyear'] === 'true') $stickyyear = true;
           echo $this->getDeptCodes($stickyyear);
           die();
    }

    //Ajax function - dynamically get sections of a course
    public function ubcsections_display_ajax () {
           //get post parameters   
	   $fuzzy = $_POST['fuzzy']; 
           $department = $_POST['department'];
           $course = $_POST['course'];
           $profileslug = $_POST['profileslug'];
           $stickywinter = $_POST['stickywinter'];
           $stickyyear = false;
           if($_POST['stickyyear'] === "true") $stickyyear = true;
           //return to js
           echo $this->show_section_table($fuzzy,$department,$course,$profileslug,$stickywinter,$stickyyear);  
           die();
    }

    //Ajax function - dynamically get courses of a department
    public function ubcdepartment_display_ajax () {
        $output = array();
        $department = $_POST['department'];
        $stickyyear = false;
        if($_POST['stickyyear'] === "true") $stickyyear = true;
        //validate in case
        if ((preg_match("/^[A-Z]*$/", $department))&&(strlen($department) < 5)) {
	  $ubccalendarAPI = new ubcCalendarAPI($department, '', true, $stickyyear, false);
          $xml = simplexml_load_string($ubccalendarAPI->XMLData);
          foreach ($xml->course as $courses) { 
            //create array of coursecodes to send to js
            $output[] = array('code'=>trim($courses['key']));
          }
          echo json_encode(array('data'=>$output));
        }
        else echo 'error1';
        die();
    }

    //Ajax function - dynamically get instructors of a course
    public function ubcinstructors_display_ajax () {
           //get post parameters    
           $department = $_POST['department'];
           $course = $_POST['course'];
           $stickyyear = false;
           if($_POST['stickyyear'] === "true") $stickyyear = true;
           //return to js
           $result = $this->enumerate_course($department,$course,$stickyyear); 
           //if ($result.count == 0)
           echo json_encode(array('data'=>$result));
           die();
    }
	
	/**
	 * getList function.
	 * 
	 * @access private
	 * @param mixed $department

	 * @param mixed $course

	 * @param mixed $pills

	 * @param mixed $tabs

	 * @param mixed $tabcount

	 * @param mixed $parentslug

	 * @param mixed $opentab

	 * @param mixed $profileslug

	 * @param mixed $stickywinter

	 * @param mixed $instructors
	 
	 * @param mixed $stickyyear
	 
	 * @param mixed $desc_category
	 * @return void
	 */
	private function getList($fuzzy,$department, $course, $pills, $tabs, $tabcount, $parentslug, $opentab, $profileslug, $stickywinter, $instructors, $stickyyear, $desc_category){
		//include_once 'ubcCalendarAPI.php';

		//Need to validate parameters
		// if input to tabcount is not one of the special commands (g,u, n*) ...
		if (strcasecmp($tabcount, 'g') != 0 && strcasecmp($tabcount, 'u') != 0 && strcasecmp(substr($tabcount,0,1), 'n') != 0){
		  if (($tabcount > 6)||($tabcount < 1)) $tabcount = 4;
		  if (($opentab > $tabcount)||($opentab < 1)) $opentab = 1;
		}
		// if input to tabcount is the special commands n* ...
		elseif (strcasecmp(substr($tabcount,0,1), 'n') == 0){
		  if ((intval(substr($tabcount,1,2)) > 6)||(intval(substr($tabcount,1,2)) < 1)) $tabcount = "n1";
		}

                //Validate stickywinter - 
                if ((strtoupper($stickywinter) == 'S')||( strtoupper($stickywinter) == 'W'))
                     $stickywinter = strtoupper($stickywinter);
                else{
                   if (is_bool($stickywinter) == false)
                        $stickywinter = false;
                }

		$ubccalendarAPI = new ubcCalendarAPI($department, $course, $stickywinter,$stickyyear, false);
                $xml = simplexml_load_string($ubccalendarAPI->XMLData);
                if($ubccalendarAPI->fromTransient)
                   $fserver_label = '<i style="margin-left:4px;color:#dfdfdf;" class="icon-calendar"></i>';
                else
                   $fserver_label = '<i style="margin-left:4px;color:gray;" class="icon-calendar"></i>';
				
				// Put current sessions's label
				$ubccalendarAPI->getCurrentSession();
				$ubccalendarAPI->getCurrentYear;		
				if ($ubccalendarAPI->currentSession == "W")
					$fserver_label .= '<span style="font-size:10px;color:grey;margin-left:4px;">Winter '.$ubccalendarAPI->currentYear.'</span>';
				else
					$fserver_label .= '<span style="font-size:10px;color:grey;margin-left:4px;">Summer '.$ubccalendarAPI->currentYear.'</span>';
				
                $count = 0;
				$offset = 1;
                foreach ($xml->course as $courses) { 
                   if ($instructors){
                       $instrstr = $this->get_courseInstructors($fuzzy,'option_2',$department.$courses[key],$ubccalendarAPI, $profileslug);
                   }
                   $detailsbtn = $this->getDetailsBtn($department.$courses['key'],$parentslug);
				   
				   $descaccordion = $this->getDescAccordion($department.$courses['key'],$desc_category);
				   
                   //$params = "'".$department."', '".$courses['key']."' "; 
                   $params = "'".$fuzzy."','".$department."','".$courses['key']."','".$profileslug."','".$stickywinter."','".$stickyyear."'"; 
                   $section = '<a onclick="getSectionData('.$params.');" href="#myModal" role="button" class="btn btn-mini modalbox" data-toggle="modal">Sections</a>';
                   if (empty($course)&&($pills)||empty($course)&&($tabs)){
                       $cindex = substr($courses['key'], 0, 1);
                       $coursetabs[$cindex] .= '<p><strong>'.$department.$courses['key'].' '.$courses['title'].' '.$section.$detailsbtn.'</strong></p><p class="pdesc">'.$courses['descr'].'</p>'.$instrstr.$descaccordion;
                   }
                   else{	// if tabs or pills are not enabled, we still want to make "tabcount" accessible to user
						$cindex = substr($courses['key'], 0, 1);
						// if tabcount is 'g' or 'G', show tabs 500 and 600 only
						if (strcasecmp($tabcount, 'g') == 0){
							if ($cindex == 5 || cindex == 6)
								$output .= '<p><strong>'.$department.$courses['key'].' '.$courses['title'].' '.$section.$detailsbtn.'</strong></p><p class="pdesc">'.$courses['descr'].'</p>'.$instrstr.$descaccordion;
						}
						// if tabcount is 'u' or 'U', show tabs 100 through 400
						elseif (strcasecmp($tabcount, 'u') == 0){
							if ($cindex == 1 || $cindex == 2 || $cindex == 3 || $cindex == 4)
								$output .= '<p><strong>'.$department.$courses['key'].' '.$courses['title'].' '.$section.$detailsbtn.'</strong></p><p class="pdesc">'.$courses['descr'].'</p>'.$instrstr.$descaccordion;
						}
						// if tabcount is 'n*' or 'N*', show tab *00 only (eg: n2 -> only 200 tab is displayed)
						else if (strcasecmp(substr($tabcount,0,1), 'n') == 0){
							if ($cindex == intval(substr($tabcount,1,2)))
								$output .= '<p><strong>'.$department.$courses['key'].' '.$courses['title'].' '.$section.$detailsbtn.'</strong></p><p class="pdesc">'.$courses['descr'].'</p>'.$instrstr.$descaccordion;
						}
                                             else{
                       $output .= '<p><strong>'.$department.$courses['key'].' '.$courses['title'].' '.$section.$detailsbtn.'</strong></p><p class="pdesc">'.$courses['descr'].'</p>'.$instrstr.$descaccordion;
                                             }
                   }
                   $count++;
                }
              if( $count == 0 )
                 $output = '<br/><strong>No '.$department.' course(s) were found for '.$ubccalendarAPI->currentSession.$ubccalendarAPI->currentYear.' term.</strong>';
              if (empty($course)&&($pills)||empty($course)&&($tabs)){
                 $tabnum = 0;
                 if ($pills)
                    $tabhead = '<ul class="nav nav-pills btn-mini" id="tabs" data-tabs="tabs">';
                 else
                    $tabhead = '<ul class="nav nav-tabs btn-mini" id="tabs" data-tabs="tabs">';
				
				 // if tabcount is 'g' or 'G', show tabs 500 and 600 only
				 if (strcasecmp($tabcount, 'g') == 0){
					$tabnum = 4;
					$opentab = 5;
					$tabcount = 6;
					$offset = 5;
				 }
				 // if tabcount is 'u' or 'U', show tabs 100 through 400
				 elseif (strcasecmp($tabcount, 'u') == 0){
					$tabnum = 0;
					$opentab = 1;
					$tabcount = 4;
				 }
				 // if tabcount is 'n*' or 'N*', show tab *00 only (eg: n2 -> only 200 tab is displayed)
				 else if (strcasecmp(substr($tabcount,0,1), 'n') == 0){
				    $opentab = intval(substr($tabcount,1,2));
					$tabnum = $opentab - 1;
					$tabcount = $opentab;
					$offset = $opentab;
				 }
				 $tab_id = md5(uniqid());
				 for(; $offset <= count($coursetabs); $offset++) {
                    $tabnum++;
                    if ($tabnum <= $tabcount){
                       if ($tabnum == $opentab){
                           $tabhead .= '<li class="active"><a data-toggle="tab" href="#'.$department.$tabnum.$tab_id.'">'.$tabnum.'00 level courses</a></li>';
                           $output .= '<div class="tab-pane active" id="'.$department.$tabnum.$tab_id.'">'.$coursetabs[$offset].'</div>';
                       }
                    else{
                       $tabhead .= '<li><a data-toggle="tab" href="#'.$department.$tabnum.$tab_id.'">'.$tabnum.'00 level courses</a></li>';
                       $output .= '<div class="tab-pane" id="'.$department.$tabnum.$tab_id.'">'.$coursetabs[$offset].'</div>';
                    }
                  }
                }
                $output = $tabhead.'</ul><div class="tab-content">'.$output.'</div>';
            }
            return $fserver_label.$output.$this->display_modal();
	}
	
    /**
     * show_section_table function.
     * 
     * @access public
     * @param mixed $department

     * @param mixed $course

     * @param mixed $profileslug

     * @param mixed $stickywinter
     * @return void
     */
    public function show_section_table($fuzzy,$department,$course,$profileslug,$stickywinter,$stickyyear) {  
			//include_once 'ubcCalendarAPI.php';
			$ubccalendarAPI = new ubcCalendarAPI($department, $course,$stickywinter,$stickyyear,true);
                $xml = simplexml_load_string($ubccalendarAPI->XMLData);
                if($ubccalendarAPI->fromTransient)
                   $fserver_label = '<i style="margin-left:4px;color:#dfdfdf;" class="icon-calendar"></i>';
                else
                   $fserver_label = '<i style="margin-left:4px;color:gray;" class="icon-calendar"></i>';
				
				$ubccalendarAPI->getCurrentSession();
				$ubccalendarAPI->getCurrentYear;		
				if ($ubccalendarAPI->currentSession == "W")
					$fserver_label .= '<span style="font-size:10px;color:grey;margin-left:4px;">Winter '.$ubccalendarAPI->currentYear.'</span>';
				else
					$fserver_label .= '<span style="font-size:10px;color:grey;margin-left:4px;">Summer '.$ubccalendarAPI->currentYear.'</span>';
					
                $count = 0; 
                $output = '<table id="ubccsections"><td><strong>Sec</strong></td><td><strong>Activity</strong></td><td><strong>Term</strong></td><td><strong>Day</strong></td><td><strong>Bld</strong></td><td><strong>Instructor</strong></td>'; 
                //$output .= '<td><strong>p</strong></td>';
                foreach ($xml->section as $sections) {
                   $ssc_link = "https://courses.students.ubc.ca/cs/main?"."pname=subjarea&tname=subjareas&req=5&dept=".$department."&course=".$course."&section=".$sections['key']."&sessyr=".$ubccalendarAPI->currentYear."&sesscd=".$ubccalendarAPI->currentSession;
                   $inst_link = "https://courses.students.ubc.ca/cs/main?pname=inst&ubcid=".$sections->instructors->instructor['ubcid'];
                   $output .= '<tr><td><a target="_blank" href="'.$ssc_link.'">'.$sections['key'].'</a></td><td>'.$sections['activity'].'</td>';
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

                   $profileHTML = ''; 
                   $instructors =  $sections->instructors->instructor; 
                   if(is_array($instructors) || $instructors instanceof Traversable):               
                   foreach ($instructors as $instructor){ //-added
                   		$instructor_name = $instructor['name']; //-added
				
                   		if ($profileslug){
					$profile_url = $this->profile_exists($fuzzy,$ubccalendarAPI,$profileslug,$instructor_name);
                     			if ($profile_url)
                       				$profileHTML = '<td><a style="line-height:11px;" class="btn btn-mini btn-danger" href="'.$profile_url.'">profile<a></td>';
                   		}
                   		$output .= '<td><a target="_blank" href="'.$inst_link.'">'.$instructor_name.'</a></td>'.$profileHTML;
                   } // - addedfor each instructor
                   endif;
                   $output .= '</tr>';
                   $count ++;
                 }
                 $output .= '</table>';
                 if( $count == 0 ) 
	           $output = '<br><strong>This course ('.$department.$course.') is not offered in '.$ubccalendarAPI->currentSession.$ubccalendarAPI->currentYear.'.</strong>';
                 return $fserver_label.$output;
        }

	
	/**
	 * getDeptCodes function.
	 * 
	 * @access private
	 * @return void
	 */
	private function getDeptCodes($stickyyear){
		$ubccalendarAPI = new ubcCalendarAPI('', '', true,$stickyyear, false);
        $xml = simplexml_load_string($ubccalendarAPI->XMLData);
        if($ubccalendarAPI->fromTransient)
           $fserver_label = '<span style="color:green;"></span>';
        else
           $fserver_label = '<span style="color:red;">*</span>';
        $count = 0; 
        $output = '<select id="department" onkeypress="handleEnter(this.value, event);"  class="chzn-select" data-placeholder="Choose a Department Course Code..." style="width:350px;margin-top:-4px;" tabindex="1"><option value=""></option>'; 
        foreach ($xml->dept as $dept) {
                   $output .= '<option value="'.$dept['key'].'">'.$dept['key'].' - '.$dept['title'].$fserver_label.'</option>';
                   $count ++;
        }
        $output .= '</select>';
        if( $count == 0 ) 
	        $output = '<select id="department" onkeypress="handleEnter(this.value, event);"  class="chzn-select" data-placeholder="Unable to Retrieve Codes..." style="width:350px;margin-top:-4px;" tabindex="1"><option value=""></option></select>';
        return $output;
    }


	/**
	 * enumerate_course function.
	 * 
	 * @access public
	 * @param mixed $department

	 * @param mixed $course
	 * @return void
	 */
	public function enumerate_course($department,$course,$stickyyear){
           $instructorArr = array();
           $output = array();
           $subccalendarAPI = new ubcCalendarAPI($department, $course,true,$stickyyear,true);
           $section_xml = simplexml_load_string($subccalendarAPI->XMLData);
               if (!empty($section_xml)) {
                   foreach ($section_xml->section as $sections) {
                        if (!empty($sections->instructors)) {
                             foreach ($sections->instructors->instructor as $instructor) {           
                                  //$instructor_name = $sections->instructors->instructor['name'];
                                  $instructor_name = $instructor['name'];
                                  if (empty($instructor_name)||(trim($instructor_name) == "TBA")){
                                  }
                                  else{
                                         if (array_key_exists(trim($instructor_name),$instructorArr)){
                                         }
                                         else{ //New Instructor 
                                             $instructorArr[trim($instructor_name)] = $department.$course;
                                             array_push($output,array('name'=>trim($instructor_name),  'course'=>$department.$course));
                                         }
                                  }
                             }
                        }
                   }
                }
           return $output;
           die();
	}
	
    /**
     * getDetailsBtn function.
     * 
     * @access private
     * @param mixed $pageName

     * @param mixed $parentslug
     * @return void
     */
    private function getDetailsBtn($pageName,$parentslug){
           $btnHTML = '';
           $page = get_page_by_title($pageName);
           if($page){
              $pageLink = get_page_uri($page->ID);
              if (basename(dirname($pageLink)) == $parentslug){
                $btnHTML = '<a href="'.$pageLink.'" role="button" style="margin-left:5px;" class="btn btn-info btn-mini">Details</a>';
              }
           }
           return $btnHTML;
     }
	
    /**
     * display_modal function.
     * 
     * @access private
     * @return void
     */
	 
    /**
     * getDescAccordion function.
     * 
     * @access private
     * @param mixed $postName

     * @param mixed $desc_category
     * @return void
     */
    private function getDescAccordion($postName,$desc_category){
			$divHTML = '';
			
			if (get_category_by_slug( $desc_category ) == 0)
				return $divHTML;

            $cat_obj = get_category_by_slug( $desc_category );
            $cat_id = $cat_obj->term_id;
            $cat_name = get_cat_name( $cat_id );
			
			$args=array(
			  'post_type' => 'post',
			  'name' => $postName,
			  'posts_per_page' => 1
			);
			
			$postlist = get_posts($args);
			$post_cats = get_the_category($postlist[0]->ID);
                        
			$filtered_post;
			
			foreach(($post_cats) as $category) {
                if ($category->cat_ID == $cat_id)
					$filtered_post = $postlist[0];
			} 
			
		   if($filtered_post){
				$acrdn_ID = rand(0, 999);	//generate accordions with random ID's
				$divHTML = $divHTML.'<div class="accordion coursesacc" id="accordion'.$acrdn_ID.'"><div class="accordion-group"><div class="accordion-heading">
				<a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#accordion'.$acrdn_ID.'" href="#collapse'.$acrdn_ID.'">'.$cat_name.'</a></div>
				<div id="collapse'.$acrdn_ID.'" class="accordion-body collapse"><div class="accordion-inner">';
				if ($postlist[0]->post_excerpt)
				{
					$divHTML = $divHTML.$postlist[0]->post_excerpt.'<br/><a href="'.$postlist[0]->guid.'">Read More...</a></div></div></div></div>';
				}
				else	// if excerpt is empty...
				{
					// clean up the HTML
					$post_content = strip_tags ( $postlist[0]->post_content , '<a><p><strong><br/><br><b>' );
					// strip out the first 150 words
					$phrase_array = explode(' ',$post_content);
					if(count($phrase_array) > 150)
						$post_content = implode(' ',array_slice($phrase_array, 0, 150)).'...';
					// put it inside the generated accordion
					$divHTML = $divHTML.$post_content.'<br/><a href="'.$postlist[0]->guid.'">Read More...</a></div></div></div></div>';
				}	
		   }
		   
           return $divHTML;
     }	 
	 
	 
    private function display_modal(){
         $output = '<!-- Modal --><div id="myModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button><p style="font-weight:bold;" id="myModalLabel">Modal header</p></div><div class="modal-body"><p>One fine body…</p></div><div class="modal-footer"></div></div>';
         return $output;
    }
	
	/**
	 * courses_shortcode function.
	 * 
	 * @access public
	 * @param mixed $atts
	 * @return void
	 */
	public function courses_shortcode($atts) {
             
             // load the script
             wp_enqueue_script( 'bootstrap-model', UBC_COURSES_URLPATH.'/js/bootstrap-modal.js',array('jquery'),'2.2.2', true );
             wp_enqueue_script( 'ubc-courses', UBC_COURSES_URLPATH.'/js/ubccourses.js',array(),UBC_COURSES_VERSION, true );
             
             // Params and Defaults
             extract(shortcode_atts(array(
	             "department" => '',
	             "course" => '',
	             "pills" => false,
	             "tabs" => false,
	             "tabcount" => 4,
	             "opentab" => 1,
	             "parentslug" => '',
	             "profileslug" => '',
	             "instructors" => '',
	             "stickywinter" => false,
	             "stickyyear" => false,
		     "fuzzy" => 80,
				 "desc_category" => ''
             ), $atts));
		
             //Get Ajax url and setup js vars
             $ajaxurl = admin_url('admin-ajax.php' );
             return '<script> var ajaxurl = "'.$ajaxurl.'"; </script>'.$this->getList($fuzzy,$department, $course, $pills, $tabs, $tabcount, $parentslug, $opentab, $profileslug, $stickywinter,$instructors,$stickyyear, $desc_category);
	}
	
	/**
	 * instructors_shortcode function.
	 * 
	 * @access public
	 * @param mixed $atts
	 * @return void
	 */
	public function instructors_shortcode($atts){
             
             wp_enqueue_script( 'bootstrap-model', UBC_COURSES_URLPATH.'/js/bootstrap-modal.js',array('jquery'),'2.2.2', true );
             wp_enqueue_script( 'ubc-courses', UBC_COURSES_URLPATH.'/js/ubccourses.js',array(),UBC_COURSES_VERSION, true );
             
             // Params and Defaults
             extract(shortcode_atts(array(
	             "instructorname" => '',
	             "parentslug" => '',
	             "profileslug" => '',
	             "instructors" => false,
	             "stickywinter" => true,
	             "stickyyear" => false,
                 "desc_category" => '',
		 "fuzzy" => 80
             ), $atts));
	
             //Get Ajax url and setup js vars
             $ajaxurl = admin_url('admin-ajax.php' );
             return '<script> var ajaxurl = "'.$ajaxurl.'"; </script>'.$this->get_instructorCourses($fuzzy,'option_2',$instructorname, $parentslug, $profileslug, $stickywinter,$instructors,$stickyyear,$desc_category);
             
	}


    /**
     * Register scripts used by this plugin for enqueuing elsewhere
     * 
     * @uses wp_register_script()
     */
    function wp_register_scripts() {
        // Admin JavaScript
        wp_register_script( "{$this->namespace}-admin", UBC_COURSES_URLPATH . "/js/admin.js", array( 'jquery' ), $this->version, true );
        wp_register_script( "{$this->namespace}-chosen", UBC_COURSES_URLPATH . "/js/chosen.jquery.min.js", array( 'jquery', ), $this->version, true );
    }
    
    /**
     * Register styles used by this plugin for enqueuing elsewhere
     * 
     * @uses wp_register_style()
     */
    function wp_register_styles() {
        // Admin Stylesheet
        wp_register_style( "{$this->namespace}-admin", UBC_COURSES_URLPATH . "/css/admin.css", array(), $this->version, 'screen' );
        wp_register_style( "{$this->namespace}-chosen", UBC_COURSES_URLPATH . "/css/chosen.css", array(), $this->version, 'screen' );
        wp_register_style( "{$this->namespace}-jqueryui", UBC_COURSES_URLPATH . "/css/jquery-ui.css", array(), $this->version, 'screen' );
    }
}
if( !isset( $UBC_Courses ) ) {
	UBC_Courses::instance();
}

register_activation_hook( __FILE__, array( 'UBC_Courses', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'UBC_Courses', 'deactivate' ) );
