<?php
/**
 * Plugin Name: Google Analytics Plugin
 * Plugin URI: http://newstechnologyservices.com
 * Description: A comprehensive platform to see audience overview along with demographic data.
 * Version: 1.0.0
 * Author: Ankit Gade
 * Author URI: https://sharethingz.com/
 * Text Domain: ntsga
 * Domain Path: /i18n/languages/
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if( !defined( 'NTSGA_BASE' ) ){
    define( 'NTSGA_BASE', dirname(__FILE__) );
}

if( !defined( 'NTSGA_BASE_FILE' ) ){
    define( 'NTSGA_BASE_FILE', __FILE__ );
}

if( !defined( 'NTSGA_BASE_URL' ) ){
    define( 'NTSGA_BASE_URL', plugins_url( basename( dirname(__FILE__) ) ) );
}

//Include main class
if( !class_exists('NTSGA') ) {
    include_once dirname( __FILE__ ) . '/classes/class-ntsga.php';
}

function _NTSGA(){
    return NTSGA::instance();
}

$GLOBALS['ntsga'] = _NTSGA();