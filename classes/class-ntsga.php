<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('NTSGA')){
    
    class NTSGA {
        
        static $instance;
        
        public $api_client; //API Client object
        
        protected $settings;
        
        
        static function instance(){
            
            if( is_null(self::$instance) ){
                self::$instance = new self();
            }
            
            return self::$instance;
        }
        
        //Constructor
        function __construct() {

            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            spl_autoload_register( array($this, 'autoload') );
            $this->api_client = new NTSGA_Google_Api_Client();
            $this->settings = get_option('ntsga_options');
            new NTSGA_Settings();
        }
        
        /*
         * Autoload function for classes
         */
        function autoload($class){

            if(file_exists(NTSGA_BASE.'/classes/'.$class.'.php' ) ){
                include NTSGA_BASE.'/classes/'.$class.'.php';
            }
        }
        
        /*
         * Get settings for plugins.
         */
        function get_setting($key){

            if(!empty($this->settings) && is_array($this->settings) && array_key_exists($key, $this->settings))
                return $this->settings[$key];
            
            return null;
        }

        function admin_scripts(){
            wp_register_script('ntsga-charts', 'https://www.gstatic.com/charts/loader.js', array(), false, true);
            wp_register_script('ntsga_script', trailingslashit(NTSGA_BASE_URL).'assets/js/ntsga.js', array('jquery', 'ntsga-charts'), '1.0', true );
            wp_enqueue_style('ntsga_style', trailingslashit(NTSGA_BASE_URL).'assets/css/ntsga.css', array(), false);
        }
    }
}