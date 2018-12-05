<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('NTSGA_Settings')){
    
    class NTSGA_Settings {

        public $settings_helper;

        //Constructor
        function __construct() {
            $this->settings_helper = new NTSGA_Settings_Helper();
            add_action( 'admin_init', array($this, 'ntsga_settings_init') );
            add_action( 'admin_menu', array($this, 'ntsga_options_page') );
            add_action('admin_enqueue_scripts', array($this, 'analytics_data'), 20);
        }
        
        /*
         * Settings API
         */
        function ntsga_settings_init(){
            register_setting( 'ntsga', 'ntsga_options' );
            add_settings_section( 'ntsga_section', __( 'Google Analytics API Settings Data', 'ntsga' ), function(){}, 'ntsga' );
            add_settings_field( 'ntsga_viewid', __('Enter View ID', 'ntsga'), array($this, 'ntsga_field_cb'), 'ntsga', 'ntsga_section', array( 'label_for'=>'ntsga_viewid', 'type'=>'text', 'help_text'=>'<p>'. sprintf( __('Click <a target="_blank" href="%s">here</a> to check how to find view ID.', 'ntsga'), 'https://keyword-hero.com/documentation/finding-your-view-id-in-google-analytics' ) .'</p>' ) );
        }
        
        /*
         * Google Analytics data parameters related to different views.
         */
        function analytics_data($pagenow){

            switch($pagenow){

                case 'nts-google-analytics_page_ntsga_overview':
                    $data = array(
                            'chartType'=>'line',
                            'title'=>__("Users Overview", 'ntsga'),
                            'metric'=>array( 'header'=>__('Users', 'ntsga'),'format'=>'number', 'label'=>'ga:sessions' ),
                            'dimension'=>array( 'header'=>__('Date', 'ntsga'),  'format'=>'date', 'label'=>'ga:date' )
                        );
                    break;

                case 'nts-google-analytics_page_ntsga_dgoverview_country':
                    $data = array(
                            'chartType'=>'table',
                            'title'=>__('Demographic Overview - Country', 'ntsga'),
                            'metric'=>array( 'header'=>__('Users', 'ntsga'), 'type'=>'number', 'format'=>'number','label'=>'ga:users' ),
                            'dimension'=>array( 'header'=>__('Country', 'ntsga'), 'type'=>'string', 'format'=>'string','label'=>'ga:country' )
                        );
                    break;

                case 'nts-google-analytics_page_ntsga_dgoverview_city':
                    $data = array(
                            'chartType'=>'table',
                            'title'=>__('Demographic Overview - City', 'ntsga'),
                            'metric'=>array( 'header'=>__('Users', 'ntsga'), 'type'=>'number', 'format'=>'number','label'=>'ga:users' ),
                            'dimension'=>array( 'header'=>__('City', 'ntsga'), 'type'=>'string', 'format'=>'string','label'=>'ga:city' )
                        );
                    break;

                case 'nts-google-analytics_page_ntsga_osoverview':
                    $data = array(
                                 'chartType'=>'table',
                                 'title'=>__('Organic Search Overview - Keywords', 'ntsga'),
                                 'metric'=>array( 'header'=>__('Users', 'ntsga'), 'type'=>'number', 'format'=>'number','label'=>'ga:users' ),
                                 'dimension'=>array( 'header'=>__('Keyword', 'ntsga'), 'type'=>'string', 'format'=>'string','label'=>'ga:keyword' )
                             );
                    break;
            }

            wp_localize_script( 'ntsga_script', 'NTSGA_API_Data', $data);
            wp_enqueue_script('ntsga_script');
        }
        
        /*
         * Settings field callback
         */
        function ntsga_field_cb($args){

            $options = get_option( 'ntsga_options' );

            switch($args['type']){

                case 'text':?>
                    <input autocomplete="off" type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="ntsga_options[<?php echo esc_attr( $args['label_for'] ); ?>]" value="<?php echo !empty($options[$args['label_for']]) ? $options[$args['label_for']] : ''; ?>" /><?php
                    if(!empty($args['help_text']))
                        echo $args['help_text'];
                break;
            
                default :
                break;
            }
        }

        /*
         * Options page for plugin.
         */
        function ntsga_options_page(){
            add_menu_page( __('NTS Google Analytics', 'ntsga'), __('NTS Google Analytics', 'ntsga'), 'manage_options', 'ntsga', array($this, 'ntsga_admin_markup') );
            add_submenu_page( 'ntsga', __('Audience Overview', 'ntsga'), __('Audience Overview', 'ntsga'), 'manage_options', 'ntsga_overview', array($this, 'ntsga_overview') );
            add_submenu_page( 'ntsga', __('Demographics - Country', 'ntsga'), __('Demographics - Country', 'ntsga'), 'manage_options', 'ntsga_dgoverview_country', array($this, 'ntsga_overview') );
            add_submenu_page( 'ntsga', __('Demographics - Cities', 'ntsga'), __('Demographics - Cities', 'ntsga'), 'manage_options', 'ntsga_dgoverview_city', array($this, 'ntsga_overview') );
            add_submenu_page( 'ntsga', __('Organic Search Overview', 'ntsga'), __('Organic Search Overview', 'ntsga'), 'manage_options', 'ntsga_osoverview', array($this, 'ntsga_overview') );
        }
        
        /*
         * Callback function for option pages.
         */
        function ntsga_overview(){

            global $ntsga;?>

            <h2><?php _e('Audience Overview', 'ntsga') ?></h2><?php

            if( $ntsga->api_client->hasAccess() ){?>
                <div class="ntsga-response-msg"></div>
                <?php echo $this->settings_helper->overview_periods(); ?>
                <?php echo $this->settings_helper->loader(); ?>
                <div id="ntsga-overview-chart">
                    <?php _e('Select a period to overview', 'ntsga'); ?>
                </div>
                <?php
            }else{?>
                <p><?php _e('Please authenticate NTS Google Analytics to see the report.', 'ntsga') ?></p>
                <a class="btn button button-primary" href="<?php echo $ntsga->api_client->get_client()->createAuthUrl() ?>"><?php _e('Authenticate', 'ntsga'); ?></a><?php
            }
        }
        
        /*
         * Settings API markup
         */
        function ntsga_admin_markup(){
            
            if(!current_user_can('manage_options')){
                return;
            }
            
            // check if the user have submitted the settings
            // wordpress will add the "settings-updated" $_GET parameter to the url
            if ( isset( $_GET['settings-updated'] ) ) {
                // add settings saved message with the class of "updated"
                add_settings_error( 'ntsga_messages', 'ntsga_message', __( 'Settings Saved', 'ntsga' ), 'updated' );
            }
            
            settings_errors( 'ntsga_messages' );?>
            
            <div class="wrap">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <form action="options.php" method="post"><?php
                    settings_fields( 'ntsga' );
                    
                    do_settings_sections( 'ntsga' );
                    
                    submit_button( 'Save Settings' );
                ?>    
                </form>
            </div><?php
        }

    }
}