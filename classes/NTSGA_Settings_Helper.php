<?php

if(!defined('ABSPATH')){
    exit;
}

if( !class_exists('NTSGA_Settings_Helper') ){
    
    class NTSGA_Settings_Helper {
        
        function overview_periods() {
            
            $periods = apply_filters( 'ntsga_ov_periods', array(
                'today' => __('Today', 'ntsga'),
                'yesterday' => __('Yesterday', 'ntsga'),
                'weekly' => __('Week', 'ntsga'),
                'monthly' => __('Month', 'ntsga'),
            ));
            
            ob_start();?>
            
                <ul>
                    <?php foreach($periods as $key=>$period){?>
                        <li data-period="<?php echo $key; ?>" class="button btn ntsga-ov-period"><?php printf('%s', $period) ?></li><?php
                    } ?>
                </ul><?php
                
            return apply_filters('overview_period_html', ob_get_clean());
        }
        
        function loader(){?>
            <div id="ntsga-loader"><img src="<?php echo trailingslashit(NTSGA_BASE_URL).'assets/images/ajax-loader.gif'; ?>" alt="<?php _e('Loader', 'ntsga') ?>" /></div><?php
        }
    }
}