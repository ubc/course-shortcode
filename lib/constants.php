<?php
/**
 * Constants used by this plugin
 * 
 */

// The current version of this plugin
if( !defined( 'PLUGINTEMPLATE_VERSION' ) ) define( 'PLUGINTEMPLATE_VERSION', '1.0.0' );

// The directory the plugin resides in
if( !defined( 'PLUGINTEMPLATE_DIRNAME' ) ) define( 'PLUGINTEMPLATE_DIRNAME', dirname( dirname( __FILE__ ) ) );

// The URL path of this plugin
if( !defined( 'PLUGINTEMPLATE_URLPATH' ) ) define( 'PLUGINTEMPLATE_URLPATH', WP_PLUGIN_URL . "/" . plugin_basename( PLUGINTEMPLATE_DIRNAME ) );

if( !defined( 'IS_AJAX_REQUEST' ) ) define( 'IS_AJAX_REQUEST', ( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) );