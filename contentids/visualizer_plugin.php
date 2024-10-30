<?php
    /* Class to fetch values that are related to Visualizer Plugin */    
    class csvtohtmlwp_visualizer_plugin {
    
    /*
     *   fetch_content
     * 
     *  This function returns an array of headers and rows based 
     *  on given content
     * 
     *  @param  string $content_arr             content array to use to identify headers and rows
     *  @param  string $headerrows_start        Where headers should start from in array
     *  @param  string $cutarr_fromend          Used for fetching last items
     *  @return array                         array of 'row_values' and 'header_values'
     *                 
     */   
    public function fetch_content( $content_arr, $headerrows_start, $cutarr_fromend ) {
        
        
        //Fetch row and header-values
        foreach ( $content_arr as $row => $subset) {
            
            //Make a new subset array
            $new_subset = array();
            $row_newsubset = 0;
            foreach( $subset as $sa) {
               if ( $row_newsubset !== 1) { //Skip row 1 (in subset)
                    $new_subset[] = $sa;
               }
                $row_newsubset++;
            }
            
           $row_values[] = $new_subset;
            
            //First time in this loop, grab headers on startrow (default 0)
            if ( $row === 0) {
                foreach( $new_subset as $subset_arr) {
                    $header_values[] = $subset_arr[0];                    
                }
            }

        }
        
        //If headers isn't located on first row (in file or combined files)
        //then make a new array (sliced from the indicated headerrows start)
        if ( $headerrows_start > 1)
        {
            $row_values = array_slice($row_values,$headerrows_start-1); //headerrowsstart=1 means index0 in array etc
        }

        //Fetch last items? (eg. 2013,2014 instead of 2010,2011,2012,2013,2014)

        //Get last slice of header array
        if ( $cutarr_fromend === 0) {$cutarr_fromend = 1;}
        
        if ( count ( $header_values ) > 0 ) {
            $slice_header = array_merge ( array_slice ( $header_values, 0, 1), array_slice( $header_values, $cutarr_fromend) );
        }
        else {
            $slice_header = [];
            $row_values = [];
        }

        //"Recreate header values array"
        $header_values = array();
        foreach ( $slice_header as $sh) {
            $header_values[] = $sh;                
        }

        //"Recreate" row valus array
        $rvalues = array();
        foreach( $row_values as $rv) {
            $rvalues[] = array_merge( array_slice( $rv, 0,1), array_slice( $rv, $cutarr_fromend ) );
        }

        $row_values = array();
        foreach ( $rvalues as $rv) {
            $row_values[] = $rv;
        }
        
        //Return row and headers
        return array( 'header_values' => $header_values, 'row_values' => $row_values );
    }
    
    }