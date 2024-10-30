<?php
    /* Class to fetch values based on a "guess" (normal format) 
     * This sourcetype sets to only one column! Use title in shortcode
     * to use a custom header
    */    
    class csvtohtmlwp_guessonecol {

    private $csv_delimiter;
    private $title;
    private $headerrow_exists;

    /*
     * Constructor
     *
     * We set some some setttings in the constructor
     * because these settings are not used for all sourcetypes 
     * 
    */
    public function __construct( $delimiter = null, $headerrow_exists = "yes", $title = null ) 
    {
        if ( $delimiter === null )
        {
            throw new exception('Error');
        }
        $this->csv_delimiter = $delimiter;
        $this->headerrow_exists = $headerrow_exists;

        if ( $title === null )
        {
            $this->title = '';
        }
        else {
            $this->title = $title;
        }
    }

    /*
     *   fetch_content
     * 
     *  This function returns an array of headers and rows based 
     *  on given content
     * 
     *  @param  string $content_arr             content array to use to identify headers and rows
     *  @param  string $headerrows_start        Where headers should start from in array
     *  @param  string $cutarr_fromend          Used for fetching last items
     *  @return   array                         array of 'row_values' and 'header_values'
     *                 
     */   
    public function fetch_content( $content_arr, $headerrows_start, $cutarr_fromend ) 
    {
        
        //Skip (first) empty rows
        $new_arr = array();        
        foreach ( $content_arr as $row => $subset) 
        {
           
            foreach ( $subset as $ss ) 
            {
                $na = '';
                foreach ($ss as $subset_value) { 
                    $na .= $subset_value;
                }
                
                //Copy item fron content_arr to new arr only if there are any
                //values in this subset
                if ( strlen ( $na ) > 0) {
                    $new_arr[] = $ss;
                }
            }
        }

        //If headers isn't located on first row (in file or combined files)
        //then make a new array (sliced from the indicated headerrows start)
        if ( $headerrows_start > 1)
        {
            $new_arr = array_slice($new_arr,$headerrows_start-1); //headerrowsstart=1 means index0 in array etc
        }

        $header_values = array();
        if ( isset ( $new_arr[0] ) )
        {
            foreach ( $new_arr[0] as $hvalues) {
                    $header_values[] = $hvalues; //Add all but first value in arrya
            }
        }        
        
        $row_values = array();
        
        foreach ( $new_arr  as $row) {
            $row_values[]= $row;
        }       
        
        //Fetch last items? (eg. 2013,2014 instead of 2010,2011,2012,2013,2014)
        if ( $cutarr_fromend === 0) {$cutarr_fromend = 1;}
        
        //Get last slice of header array
        $slice_header = array_merge ( array_slice ( $header_values, 0, 1), array_slice( $header_values, $cutarr_fromend) );

        //"Recreate header values array"
        $header_values = array();
        foreach ( $slice_header as $sh) 
        {
            $header_values[] = $sh;                
        }
        
        //"Recreate" row values array
        $rvalues = array();
        $new_rowvalues = array();
        foreach( $row_values as $rv) {
            $row_arr = array_merge( array_slice( $rv, 0,1), array_slice( $rv, $cutarr_fromend ) );
            
            //Turn the array into a string but each item 
            //is appended with a new row (<span>)         
            $nstr = '';
            foreach( $row_arr as $rk=>$ra)
            {
                $nstr .= '<span>' . $ra . '</span>';
            }
            $new_rowvalues[] = [$nstr];
        }

        //If headerrow doesn't exists, then don't remove first row in row-array
        //($new_rowvalues[0] are replaced with header-values if headerrow does exist)
        if ( $this->headerrow_exists === "yes" )
        {
            unset ( $new_rowvalues[0 ] );
        }

        //Copy back to row_values
        $row_values = array_slice( $new_rowvalues,0 );
        
        //Set custom title (always one colum, use title - attribute in shortcode for this)
        $title = $this->title;
        $row3values = array();        
        $row2values = array();        
                  
        foreach($row_values as $row_key => $row_value) 
        {
            foreach ( $header_values as $hkey => $h_value) 
            {
                    $row2values[$hkey][0] = $h_value;
                    if ( isset($row_values[$row_key][$hkey]) )
                    {
                        $row2values[$hkey][1] = $row_values[$row_key][$hkey];
                    }
                    else {
                        $row2values[$hkey][1] = '';                 
                    }
            }
            $row3values[] = $row2values;        
        }

        //This must be after synchronizing headers
        //because this sets header to only one column
        $nheader_values = [$title];

        //Set correct number of header values
        foreach( $header_values as $key=>$hv )
        {
            if ( $key == 0)
            {
                continue;
            }
            $nheader_values[] = '';
        }
        $header_values = array_slice( $nheader_values,0 );
      
        //Return row and headers
        return array( 'header_values' => $header_values, 'row_values' => $row3values );
    }
    
    }