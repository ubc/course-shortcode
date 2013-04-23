<?php
/*
Plugin Name: UBC Courses
Plugin URI: https://github.com/ubc/course-shortcode
Description: Allows the listing of UBC courses and sections with data from the UBC calendar.
Version: 1.0.0
Author: Michael Ha (CTLT) and Shaffiq Rahemtulla (ArtsISIT)
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

class PluginTemplate {
    var $namespace = "ubccourses";
    var $friendly_name = "UBC Courses";
    var $version = "1.0.0";
    var $maxcharlimit = 10000; //limit for option size
    
    // Default plugin options
    var $defaults = array(
        'option_2' => ""
    );
    
    /**
     * Instantiation construction
     * 
     * @uses add_action()
     * @uses PluginTemplate::wp_register_scripts()
     * @uses PluginTemplate::wp_register_styles()
     */
    function __construct() {
        // Name of the option_value to store plugin options in
        $this->option_name = '_' . $this->namespace . '--options';
		
        // Load all library files used by this plugin
        $libs = glob( PLUGINTEMPLATE_DIRNAME . '/lib/*.php' );
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
        wp_enqueue_script('{$this->namespace}-bootstrap', PLUGINTEMPLATE_URLPATH.'/js/bootstrap-modal.js',array('jquery'),'', true );
        wp_enqueue_script('{$this->namespace}-shortcode', PLUGINTEMPLATE_URLPATH.'/js/ubccourses.js');
        wp_enqueue_style('{$this->namespace}-shortstyle', PLUGINTEMPLATE_URLPATH.'/css/style.css');
        
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

        // Add shortcodes
	add_shortcode('ubccourses', array($this, 'courses_shortcode'));
	add_shortcode('ubcinstructors', array($this, 'instructors_shortcode'));
    }
    
    /**
     * Process update page form submissions
     * 
     * @uses PluginTemplate::sanitize()
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
        
        include( PLUGINTEMPLATE_DIRNAME . "/views/options.php" );
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
        delete_option($this->option_name);
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
                 if (!empty($instrName)){
                    $instID = trim(preg_replace('/[ ,]+/','', $instrName));
                    $instrCourseArray = explode(",",$instrPieces[1]);
                    $htmlstr .= '<li onclick="update(this);" class="active '.$instID.'"><span id="iname">'.trim($instrName).'</span>';
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
    

   private function get_courseInstructors( $option_name, $ubcCourse, $ubccalendarAPI, $profileslug ) {
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
                        $urlslugs = explode(', ',strtolower($instrName));
                        $profile_url = '/'.$profileslug.'/'.$urlslugs[1].'-'.$urlslugs[0].'/';
                        if (in_array(trim($instrName),$ubccalendarAPI->profileData))
                            $profileHTML = '<a href="'.$profile_url.'">'.$instrName.'</a>';
                        else 
                            $profileHTML = $instrName;
                     }
                     else 
                         $profileHTML = $instrName;

                     if (strpos($instrPieces[1],$ubcCourse) !== false) {
                             $instrCount++;
                             $instID = trim(preg_replace('/[ ,]+/','', $instrName));
                             $instrCourseArray = explode(",",$instrPieces[1]);
                             $htmlstr .= '<span id="iname">'.$profileHTML.'</span>';
                     }
                 }
                 if ($instrCount > 0) $htmlstr = '<div id="instrstr"><span>Instructor(s): </span>'.$htmlstr.'</div>';
                 return $htmlstr;
        } elseif( isset( $this->defaults[$option_name] ) ) {
            return '';
        }
        return false;
    }

    private function get_instructorCourses( $option_name, $profileName, $parentslug, $profileslug, $stickywinter,$instructors ) {

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
                     $instrName = trim($instrPieces[0]);
                     if (($profileName == $instrName)||((strpos($instrName, $lname) !== false) && (strpos($instrName, $fname) !== false))){
                             $instID = trim(preg_replace('/[ ,]+/','', $instrName));
                             $instrCourseArray = explode(",",$instrPieces[1]);
                             foreach ($instrCourseArray as $course) {
                                 $htmlstr .= $this->getList( substr($course, 0, 4), substr($course, 4), false, false, 4, $parentslug, 1, $profileslug, $stickywinter,$instructors);
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
        global $PluginTemplate;
        
        // Only instantiate the Class if it hasn't been already
        if( !isset( $PluginTemplate ) ) $PluginTemplate = new PluginTemplate();
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
		if( $file == plugin_basename( PLUGINTEMPLATE_DIRNAME . '/' . basename( __FILE__ ) ) ) {
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
     * @uses PluginTemplate::_admin_options_update()
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

    //Ajax function - dynamically get sections of a course
    public function ubcsections_display_ajax () {
           //get post parameters    
           $department = $_POST['department'];
           $course = $_POST['course'];
           $profileslug = $_POST['profileslug'];
           $stickywinter = $_POST['stickywinter'];
           //return to js
           echo $this->show_section_table($department,$course,$profileslug,$stickywinter);  
           die();
    }

    //Ajax function - dynamically get courses of a department
    public function ubcdepartment_display_ajax () {
        $output = array();
        $department = $_POST['department'];
        //validate in case
        if ((preg_match("/^[A-Z]*$/", $department))&&(strlen($department) < 5)) {
	  $ubccalendarAPI = new ubcCalendarAPI($department, '', true, false);
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
           //return to js
           $result = $this->enumerate_course($department,$course); 
           //if ($result.count == 0)
           echo json_encode(array('data'=>$result));
           die();
    }

	private function getList($department, $course, $pills, $tabs, $tabcount, $parentslug, $opentab, $profileslug, $stickywinter, $instructors){
		include_once 'ubcCalendarAPI.php';

                //Need to validate parameters
                if (($tabcount > 6)||($tabcount < 1)) $tabcount = 4;
                if (($opentab > $tabcount)||($opentab < 1)) $opentab = 1;

		$ubccalendarAPI = new ubcCalendarAPI($department, $course, $stickywinter, false);
                $xml = simplexml_load_string($ubccalendarAPI->XMLData);
                if($ubccalendarAPI->fromTransient)
                   $fserver_label = '<i style="margin-left:4px;color:#dfdfdf;" class="icon-calendar"></i>';
                else
                   $fserver_label = '<i style="margin-left:4px;color:gray;" class="icon-calendar"></i>';
                $count = 0;
                foreach ($xml->course as $courses) { 
                   if ($instructors){
                       $instrstr = $this->get_courseInstructors('option_2',$department.$courses[key],$ubccalendarAPI, $profileslug);
                   }
                   $detailsbtn = $this->getDetailsBtn($department.$courses['key'],$parentslug);
                   //$params = "'".$department."', '".$courses['key']."' "; 
                   $params = "'".$department."','".$courses['key']."','".$profileslug."','".$stickywinter."'"; 
                   $section = '<a onclick="getSectionData('.$params.');" href="#myModal" role="button" class="btn btn-mini modalbox" data-toggle="modal">Sections</a>';
                   if (empty($course)&&($pills)||empty($course)&&($tabs)){
                       $cindex = substr($courses['key'], 0, 1);
                       $coursetabs[$cindex] .= '<p><strong>'.$department.$courses['key'].' '.$courses['title'].' '.$section.$detailsbtn.'</strong></p><p class="pdesc">'.$courses['descr'].'</p>'.$instrstr;
                   }
                   else{
                       $output .= '<p><strong>'.$department.$courses['key'].' '.$courses['title'].' '.$section.$detailsbtn.'</strong></p><p class="pdesc">'.$courses['descr'].'</p>'.$instrstr;
                }
                $count++;
              }
              if( $count == 0 )
                 $output = '<strong>Course(s) Not Found: Example [ubccourses department=ANTH] <br>(default is ALL courses add e.g. courses ="100A" for specific courses)</strong>';
              if (empty($course)&&($pills)||empty($course)&&($tabs)){
                 $tabnum = 0;
                 if ($pills)
                    $tabhead = '<ul class="nav nav-pills btn-mini" id="tabs" data-tabs="tabs">';
                 else
                    $tabhead = '<ul class="nav nav-tabs btn-mini" id="tabs" data-tabs="tabs">';
                 foreach ($coursetabs as $coursetab){
                    $tabnum++;
                    if ($tabnum <= $tabcount){
                       if ($tabnum == $opentab){
                           $tabhead .= '<li class="active"><a data-toggle="tab" href="#'.$department.$tabnum.'">'.$tabnum.'00 level courses</a></li>';
                           $output .= '<div class="tab-pane active" id="'.$department.$tabnum.'">'.$coursetab.'</div>';
                       }
                    else{
                       $tabhead .= '<li><a data-toggle="tab" href="#'.$department.$tabnum.'">'.$tabnum.'00 level courses</a></li>';
                       $output .= '<div class="tab-pane" id="'.$department.$tabnum.'">'.$coursetab.'</div>';
                    }
                  }
                }
                $output = $tabhead.'</ul><div class="tab-content">'.$output.'</div>';
            }
            return $fserver_label.$output.$this->display_modal();
	}

        public function show_section_table($department,$course,$profileslug,$stickywinter) {  
		include_once 'ubcCalendarAPI.php';
		$ubccalendarAPI = new ubcCalendarAPI($department, $course,$stickywinter,true);
                $xml = simplexml_load_string($ubccalendarAPI->XMLData);
                if($ubccalendarAPI->fromTransient)
                   $fserver_label = '<i style="margin-left:4px;color:#dfdfdf;" class="icon-calendar"></i>';
                else
                   $fserver_label = '<i style="margin-left:4px;color:gray;" class="icon-calendar"></i>';
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
                   $instructor_name = $sections->instructors->instructor['name'];
                   if ($profileslug){
                     $urlslugs = explode(', ',strtolower($instructor_name));
                     $profile_url = '/'.$profileslug.'/'.$urlslugs[1].'-'.$urlslugs[0].'/';
                     if (in_array(trim($instructor_name),$ubccalendarAPI->profileData))
                       $profileHTML = '<td><a style="line-height:11px;" class="btn btn-mini btn-danger" href="'.$profile_url.'">profile<a></td>';
                   }
                   $output .= '<td><a target="_blank" href="'.$inst_link.'">'.$instructor_name.'</a></td>'.$profileHTML.'</tr>';
                   $count ++;
                 }
                 $output .= '</table>';
                 if( $count == 0 ) 
	           $output = '<br><strong>This course ('.$department.$course.') is not offered in '.$ubccalendarAPI->currentSession.$ubccalendarAPI->currentYear.'.</strong>';
                 return $fserver_label.$output;
        }


        public function enumerate_course($department,$course){
           $instructorArr = array();
           $output = array();
	   $subccalendarAPI = new ubcCalendarAPI($department, $course,true,true);
           $section_xml = simplexml_load_string($subccalendarAPI->XMLData);
           foreach ($section_xml->section as $sections) {           
              $instructor_name = $sections->instructors->instructor['name'];
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
           return $output;
           die();
        }

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

        private function display_modal(){
             $output = '<!-- Modal --><div id="myModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button><p style="font-weight:bold;" id="myModalLabel">Modal header</p></div><div class="modal-body"><p>One fine body…</p></div><div class="modal-footer"></div></div>';
             return $output;
        }

	public function courses_shortcode($atts){
             //Handle double call if Jetpack is installed
             if (!in_the_loop()) return;
             
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
             "stickywinter" => false
             ), $atts));
		
             //Get Ajax url and setup js vars
             $ajaxurl = admin_url('admin-ajax.php' );
             return '<script> var ajaxurl = "'.$ajaxurl.'"; </script>'.$this->getList( $department, $course, $pills, $tabs, $tabcount, $parentslug, $opentab, $profileslug, $stickywinter,$instructors);
	}

	public function instructors_shortcode($atts){
             //Handle double call if Jetpack is installed
             
             // Params and Defaults
             extract(shortcode_atts(array(
             "instructorname" => '',
             "parentslug" => '',
             "profileslug" => '',
             "instructors" => false,
             "stickywinter" => true
             ), $atts));
	
             //Get Ajax url and setup js vars
             $ajaxurl = admin_url('admin-ajax.php' );
             return '<script> var ajaxurl = "'.$ajaxurl.'"; </script>'.$this->get_instructorCourses('option_2',$instructorname, $parentslug, $profileslug, $stickywinter,$instructors);
	}


    /**
     * Register scripts used by this plugin for enqueuing elsewhere
     * 
     * @uses wp_register_script()
     */
    function wp_register_scripts() {
        // Admin JavaScript
        wp_register_script( "{$this->namespace}-admin", PLUGINTEMPLATE_URLPATH . "/js/admin.js", array( 'jquery' ), $this->version, true );
        wp_register_script( "{$this->namespace}-chosen", PLUGINTEMPLATE_URLPATH . "/js/chosen.jquery.min.js", array( 'jquery', ), $this->version, true );
    }
    
    /**
     * Register styles used by this plugin for enqueuing elsewhere
     * 
     * @uses wp_register_style()
     */
    function wp_register_styles() {
        // Admin Stylesheet
        wp_register_style( "{$this->namespace}-admin", PLUGINTEMPLATE_URLPATH . "/css/admin.css", array(), $this->version, 'screen' );
        wp_register_style( "{$this->namespace}-chosen", PLUGINTEMPLATE_URLPATH . "/css/chosen.css", array(), $this->version, 'screen' );
        wp_register_style( "{$this->namespace}-jqueryui", PLUGINTEMPLATE_URLPATH . "/css/jquery-ui.css", array(), $this->version, 'screen' );
    }
}
if( !isset( $PluginTemplate ) ) {
	PluginTemplate::instance();
}

register_activation_hook( __FILE__, array( 'PluginTemplate', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PluginTemplate', 'deactivate' ) );