<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('NTSGA_Request_Handler')){
    
    class NTSGA_Request_Handler {

        /*
         * Get report data for secified period and view.
         */
        function get_overview_data(){

            global $ntsga;

            $overview_request = $this->prepare_request_data($_POST['api_data']['metric']['label'], $_POST['api_data']['dimension']['label']);
            
            $error_msg = array();
            
            if( !is_wp_error($overview_request) ){

                //User's overview
                $body_ov = new Google_Service_AnalyticsReporting_GetReportsRequest();
                $body_ov->setReportRequests( array( $overview_request) );
                $ov_analyticsreporting = new Google_Service_AnalyticsReporting($ntsga->api_client->get_client());
                
                try{
                    $response_ov = $ov_analyticsreporting->reports->batchGet( $body_ov );
                    $response_data_ov = $this->get_response_data($response_ov->reports);
                    $response = array('responseData'=>$response_data_ov, 'title'=>$_POST['api_data']['title']);
                    wp_send_json_success($response);

                }catch(Exception $e){
                    $exceptionMsg = json_decode($e->getMessage());
                    if( NULL !== $exceptionMsg ){

                        if(!empty($exceptionMsg->error->errors) && is_array($exceptionMsg->error->errors)){
                            
                            foreach($exceptionMsg->error->errors as $err){
                                array_push($error_msg, $err->message);
                            }
                        }
                    }
                }
            }
            
            //If we are here, then we do have errors. Handle them.
            if(is_wp_error($overview_request) ){
                $msgs = $overview_request->get_error_messages();
                foreach($msgs as $msg){
                    array_push($error_msg,$msg);
                }
            }
            
            $this->handle_errors($error_msg);
        }

        /*
         * Prepares the data for request.
         * Returns WP_Error on any failure.
         */
        function prepare_request_data($metric, $dimension) {
            
            global $ntsga;            
            $viewID = $ntsga->get_setting('ntsga_viewid');
            
            if(empty($viewID)){
                return new WP_Error('empty_view_id', __('Empty View ID.'));
            }

            // Create the DateRange object.
            $dateRange = new Google_Service_AnalyticsReporting_DateRange();
            $dateRange->setStartDate($this->get_start_date());
            $dateRange->setEndDate($this->get_end_date());

            $dimension_object = new Google_Service_AnalyticsReporting_Dimension();
            $dimension_object->setName($dimension);
            
            // Create the Metrics object.
            $metric_object = new Google_Service_AnalyticsReporting_Metric();
            $metric_object->setExpression($metric);
            
            // Create the ReportRequest object.
            $request = new Google_Service_AnalyticsReporting_ReportRequest();
            $request->setViewId($viewID);
            $request->setDateRanges($dateRange);
            $request->setMetrics(array($metric_object));
            $request->setDimensions(array($dimension_object));

            return $request;
        }

        /*
         * Get response data for analytics report.
         */
        function get_response_data($reports){

            for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
                $report = $reports[ $reportIndex ];
                $header = $report->getColumnHeader();
                $dimensionHeaders = $header->getDimensions();
                $rows = $report->getData()->getRows();

                $data['Dimension'] = array();
                $data['Metric'] = array();


              for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[ $rowIndex ];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();
                for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
                    
                    if('date' == $_POST['api_data']['dimension']['format'])
                        $d = date('d M', strtotime($dimensions[$i]) );
                    else
                        $d = $dimensions[$i];
                    
                  array_push($data['Dimension'], $d );
                }

                for ($j = 0; $j < count($metrics); $j++) {
                  $values = $metrics[$j]->getValues();
                  for ($k = 0; $k < count($values); $k++) {
                    $entry = $metricHeaders[$k];
                    array_push($data['Metric'], $values[$k]);
                  }
                }
              }
            }

            return $data;
        }
        
        /*
         * Get end date for dateranges object
         * It will always be "Today"
         */
        function get_end_date(){
            
            return apply_filters('ntsga_get_end_date', 'today');
        }
        
        /*
         * Get start date for report.
         */
        function get_start_date(){
            
            $start_date = '';
            $period = !empty($_POST['period']) ? $_POST['period'] : 'today';
            
            switch($period){
                
                case 'today':
                    $start_date = 'today';
                    break;
                
                case 'yesterday':
                    $start_date = 'yesterday';
                    break;
                
                case 'weekly';
                    $start_date = '7daysago';
                    break;
                
                case 'monthly':
                    $start_date = '30daysago';
                    break;
                
                default:
                    $start_date = 'weekly';
                    break;
            }
            
            return apply_filters('ntsga_get_start_date', $start_date);
        }
        
        /*
         * Handles the errors and send response.
         */
        function handle_errors($errors){
            
            ob_start();
            
            foreach( $errors as $error ){?>
                <p class="ntsga-error"><?php echo $error; ?></p><?php
            }
            
            $errorRes = ob_get_clean();
            
            wp_send_json_error($errorRes);
        }
    }
}