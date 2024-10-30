<?php
    /* Class to fetch values based on a json format */    
    class csvtohtmlwp_json {
    private $json_startlevel;

    public function __construct( $startlevel ) 
    {
        $this->json_startlevel = $startlevel - 2;
    }

    /*
     *   fetch_content and expect json - format
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
    public function fetch_content( $content_arr, $headerrows_start, $cutarr_fromend ) 
    {
        $index_startlevel = $this->json_startlevel ;
        
        //This should be an array and not null.
        if ( $content_arr[$index_startlevel] == null )
        {
            return;
        }

        $content_arr = array_slice( $content_arr[$index_startlevel], 0);
        $carr = array_slice( $content_arr, 0, 1 );
        $header_values = array_keys($carr[0]);  

        //Skip (first) empty rows
        $new_arr = array();        
        foreach ( $content_arr as $row => $row_value) {
            foreach ( $header_values as $hkey => $hvalue )
            {                
                $new_arr[$row][$hkey][0] = $hvalue;

                if (is_array($row_value[$hvalue])) {
                    $na = implode(',',$row_value[$hvalue]);
                }
                else {
                    $na = $row_value[$hvalue];
                }
                $new_arr[$row][$hkey][1] = $na;                      
            }
        }

        $row_values = array();
        unset ( $new_arr[0] );
        
        foreach ( $new_arr  as $row) {
            $row_values[]= $row;
        }

        //If headers isn't located on first row (in file or combined files)
        //then make a new array (sliced from the indicated headerrows start)
        if ( $headerrows_start > 1)
        {
            $row_values = array_slice($row_values,$headerrows_start-1); //headerrowsstart=1 means index0 in array etc
        }        
                
        //Fetch last items? (eg. 2013,2014 instead of 2010,2011,2012,2013,2014)
        if ( $cutarr_fromend === 0) {$cutarr_fromend = 1;}
        
        //Get last slice of header array
        $slice_header = array_merge ( array_slice ( $header_values, 0, 1), array_slice( $header_values, $cutarr_fromend) );

        //"Recreate header values array"
        $header_values = array();
        foreach ( $slice_header as $sh) {
            $header_values[] = $sh;                
        }

        //"Recreate" row values array
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