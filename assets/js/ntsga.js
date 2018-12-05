jQuery(document).ready(function(){
    
    /**/
    jQuery('.ntsga-ov-period').on('click', function(e){

        e.preventDefault();

        if( !jQuery(this).hasClass('disabled') ){
            
            var period = jQuery(this).data('period');
            
            var data = {
                api_data: NTSGA_API_Data,
                period : period,
                action: 'get_overview_data'
            };
            
            jQuery('.ntsga-response-msg').html('');
            jQuery('.ntsga-response-msg').removeClass('error');
            jQuery("#ntsga-loader").css({visibility: 'visible'});

            //Fire ajax request
            jQuery.post(ajaxurl, data, function(response){
                
                //Hide the loader once ajax is done
                jQuery("#ntsga-loader").css({visibility: 'hidden'});

                if(response.success == true){

                    let data = response.data.responseData;
                    google.charts.load('current', {'packages':['corechart', 'table']});
                    google.charts.setOnLoadCallback(function(){
                        
                        var dataTable = new Array();
                        dataTable.push([NTSGA_API_Data.dimension.header, NTSGA_API_Data.metric.header]);
                        
                        for(var i=0; i< data.Dimension.length; i++){
                            dataTable.push([data.Dimension[i], parseInt(data.Metric[i])]);
                        }

                        var dataGraph = google.visualization.arrayToDataTable(dataTable);

                        if( 'line' == NTSGA_API_Data.chartType ){
                            var chart = new google.visualization.LineChart(document.getElementById('ntsga-overview-chart'));
                            var options = {
                              title: response.data.title,
                              curveType: 'function',
                              width: '95%',
                              height: 450,
                              pointSize: 5
                            };

                        }

                        if( 'table' == NTSGA_API_Data.chartType ){
                            var chart = new google.visualization.Table(document.getElementById('ntsga-overview-chart'));
                            var options = {
                              title: response.data.title,
                              curveType: 'function',
                              width: '100%',
                              height: 'auto',
                              pointSize: 5,
                              page:'enable',
                              'pageSize': 10,
                              pagingButtons: 'both'
                            };
                        }

                        chart.draw(dataGraph, options);
                    });
                    
                }else{
                    
                    jQuery('.ntsga-response-msg').html(response.data);
                    jQuery('.ntsga-response-msg').addClass('error');
                }

            });
        }
    });
    
});