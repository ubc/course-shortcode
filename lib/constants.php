<?php
/**
 * Constants used by this plugin
 * 
 */

// The current version of this plugin
if( !defined( 'UBC_COURSES_VERSION' ) ) define( 'UBC_COURSES_VERSION', '1.0.0' );

// The directory the plugin resides in
if( !defined( 'UBC_COURSES_DIRNAME' ) ) define( 'UBC_COURSES_DIRNAME', dirname( dirname( __FILE__ ) ) );

// The URL path of this plugin
if( !defined( 'UBC_COURSES_URLPATH' ) ) define( 'UBC_COURSES_URLPATH', WP_PLUGIN_URL . "/" . plugin_basename( UBC_COURSES_DIRNAME ) );