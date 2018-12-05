<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('NTSGA_Google_Api_Client')){

    class NTSGA_Google_Api_Client {

        private $config;
        
        private $client;
        
        public $request_handler;

        //Constructor
        function __construct() {

            if( !class_exists('Google_Client') ){
                include NTSGA_BASE. '/packages/google-api/vendor/autoload.php';
            }
            
            $this->request_handler = new NTSGA_Request_Handler();

            //Set configuration for client object.
            $this->config = array(
                'client_id' => '425358816682-57no2259pju1khb2nr73ia19qb184593.apps.googleusercontent.com',
                'client_secret' => 'SGWsp2CNKUKm7HLey91PeJZz',
                'redirect_uri' => add_query_arg(array('page'=>'ntsga_overview'), admin_url('admin.php'))
            );

            //Prepare the client object
            $this->prepare_client();
            $this->hooks();
        }
        
        function hooks(){
            add_action('admin_init', array($this, 'setAuth'));
            add_action('wp_ajax_get_overview_data'.$action, array( $this->request_handler, 'get_overview_data'));
        }

        /*
         * Prepare client object.
         */
        function prepare_client(){

            if( !empty($this->client) ){
                return;
            }

            $this->client = new Google_Client();
            $this->client->setAuthConfig($this->config);
            $this->client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);
            $this->client->setAccessType("offline");
            $this->client->setConfig('approval_prompt', 'auto');
            $this->client->setRedirectUri(add_query_arg(array( 'page'=>'ntsga_overview', 'ntsgaauthreq'=>1 ), admin_url('admin.php')));

            if( $token = $this->getAccessToken() ){
                $this->client->setAccessToken($token);
            }
        }
        
        /*
         * Checks if authentication is suucessful
         * Sets accessToken
         */
        function setAuth(){
            
            if( !empty($_GET['ntsgaauthreq']) && !empty($_GET['code']) ){
                
                try{
                    
                    $this->client->authenticate($_GET['code']);
                    $access_token = $this->client->getAccessToken();
                    $this->setAccessToken($access_token);
                    
                    $refresh_token = $this->client->getRefreshToken();
                    if($refresh_token){
                        $this->setRefreshToken($refresh_token);
                    }
                    
                    wp_redirect(add_query_arg(array( 'page'=>'ntsga_overview'), admin_url('admin.php')));
                    exit;
                    
                }catch(Exception $e){
                    
                }
            }
        }

        /*
         * Get client object
         */
        function get_client(){
            return $this->client;
        }

        /*
         * Set access token and save it in transient
         */
        function setAccessToken($token){
            set_transient('ntsga_access_token', $token, HOUR_IN_SECONDS ); //Set transient for access token.
        }

        /*
         * Get access token stored in transient.
         */
        function getAccessToken(){

            $token = get_transient('ntsga_access_token');
            $refresh_token = $this->getRefreshToken();

            if(!$this->client->isAccessTokenExpired()){
                return $token;
            }

            if( $this->client->isAccessTokenExpired() && !empty($refresh_token) ){
                $this->client->refreshToken($refresh_token);
                $token = $this->client->getAccessToken();
                
                //If this is invalid, then apps access is removed and we have to authorize it again.
                if(!$this->client->isAccessTokenExpired()){
                    $this->client->setAccessToken($token);
                    return $token;
                }else{
                    delete_transient('ntsga_refresh_token');
                    delete_transient('ntsga_access_token');
                }
            }

            return false;
        }
        
        function setRefreshToken($token){
            set_transient('ntsga_refresh_token', $token, 0); //Set transient for access token.
        }
        
        function getRefreshToken(){
            $token = get_transient('ntsga_refresh_token');
            
            if(!empty($token)){
                return $token;
            }
            
            return false;
        }
        
        function hasAccess(){
            
            return $this->getAccessToken();
        }

        /*
         * Get configuration
         */
        function get_config(){
            return $this->config;
        }
    }
}