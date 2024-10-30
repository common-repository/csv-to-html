<?php
/**
*
* Debugging class for CSV to html plugin
* This separation is done because of unnecessary code is executed if debugging mode is off (debug_mode="no")
*
*/
defined( 'ABSPATH' ) or die( 'No access allowed!' );

if( !class_exists('debug') ) {

    class debug extends csvtohtmlwp {
        private $csvtohtml_msg;
        
        public function __construct($args = null) 
        {
            if ($args === null || !is_array($args)) 
            {
                echo __('No arguments supplied or arguments are not an array', 'csv-to-html');
                return true;
            }

            extract ( $args );

            echo '<div>';
            $this->show_msg ( __('<b>Debugging functionality initiated. (CSV to HTML plugin)</b>', 'csv-to-html') );
            $this->show_msg ( __('(Debugging info is shown even if the shortcode is working. The result of shortcode is always shown at the bottom of this page)', 'csv-to-html') );
            
            $this->show_msg( __ ('<br>','csv-to-html') );
            $this->show_msg ( __('The most common error is not specifying source_type to "guess". (Default is visualizer_plugin but most common type is guess)', 'csv-to-html') );
            $this->show_msg ( __('Another common error is to use wrong form of quote-sign when specifying attributes (<b>”</b> instead of <b>"</b>).', 'csv-to-html') );
            
            //Automatically create "simplest possible" shortcode 
            //based on given source_files and path
            $sc = '[csvtohtml_create debug_mode="no" source_type="guess"';
            if ( mb_strlen( $path ) > 0 )
            {
                $sc .= ' path="' . $path . '"';
            }
            $sc .= ' source_files="' . $source_files . '"]';

            $this->show_msg ( __ ('<br>','csv-to-html') );
            $this->show_msg ( __( 'If you can\'t figure out why your shotrcode is not working, try this shortcode:' , 'csv-to-html') );            
            $this->show_msg ( __( $sc , 'csv-to-html') );
            $this->show_msg ( __( 'If you get this to work, just add attributes needed (besides attributes given in above shortcode).' , 'csv-to-html') );            
            $this->show_msg ( __( 'There is a <strong>shortcode generator</strong> available in the Tools/CSV to HTML menu (here it is possible to get a preview of the layout. It also give suggestions if needed.)' , 'csv-to-html') );            

            
            $this->show_msg ( __ ('<br>','csv-to-html') );
            $this->show_msg ( __('When having several tables it\'s easy to just copy and paste shortcode and sometimes', 'csv-to-html') );
            $this->show_msg ( __('you also get the same html_id on several shortcodes which could mess things up', 'csv-to-html') );
            $this->show_msg ( __('(same ID on a html-element is not allowed), especially if you\'re using search or pagination functionality.', 'csv-to-html') );
         
            $this->show_msg ( __ ('<br>','csv-to-html') );
            $this->show_msg ( __('For <b>demos and examples</b> using this plugin check out <a href="https://wibergsweb.se/plugins/csvtohtml">CSV To HTML demos</a>', 'csv-to-html') );
            $this->show_msg ( __('<b>Note!</b> A shortcode must be on ONE line to work!', 'csv-to-html') );

            $this->show_msg ( __ ('<br>','csv-to-html') );
            $this->show_msg ( __('If it still doesn\'t work, please send a mail till info@wibergseweb.se and I will try to get back to you ASAP!', 'csv-to-html') );
            $this->show_msg ( __('If it works, a donation to info@wibergsweb.se (PayPal) and/or a review of the plugin is very much appreciated!', 'csv-to-html') );

            echo '</div>';

            $upload_dir = wp_upload_dir();
            $upload_basedir = $upload_dir['basedir'];

            //Errors
            if ( $fetch_interval !== null && !$this->fetch_interval_validate( $fetch_interval) )
            {
                $this->csvtohtml_msg = __('You have specified a fetch_interval that is not valid.', 'csv-to-html');
                $this->csvtohtml_msg .= '<br>Valid fetch intervals are: ' . implode( ',', $this->fetch_interval_validate( $fetch_interval, true ) );
                $this->show_msg();
                return true;
            }

            if ( $source_files === null) 
            {
                $this->csvtohtml_msg = __('No source file(s) given. At least one file (or link) must be given', 'csv-to-html');
                $this->show_msg();
                return true;
            }

            if ( strlen($float_divider) >1 ) 
            {
                $this->csvtohtml_msg = __('Float divider can only contain one character.', 'csv-to-html');
                $this->show_msg();
                return true;
            }

            //Warnings
            if ( $fetch_interval !== null && $html_id == null )
            {
                $this->show_msg( __('Fetch interval is set but you must also specify html_id to make this fetch functionality work.', 'csv-to-html') );
            }
            else if ( $fetch_interval !== null )
            {
                $this->show_msg( __('Files for fast access (possible with fetch_interval) are stored in ' . $upload_basedir  . $this->get_tablestoragefolder(), 'csv-to-html' ) );
            }
            

            if ( $source_type !== 'guess')
            {

                $this->show_msg( __('If you do not get expected output of data, try adding source_type="guess" to your shortcode.', 'csv-to-html') );
            }
            
            if (stristr($source_files,'docs.google.com') !== false && stristr($source_files,'pub?output=csv') === false) 
            {
                $this->show_msg( __('It seems like you are trying to access the csv file through google drive. You have to publish that document to the web chosing csv as an option.','csv-to-html') );
                $this->show_msg( __('<br>The link should end with pub?output=csv (copy the link first time you try to publish the csv file on the web)','csv-to-html') );
            }     

            if (stristr($source_files,'docs.google.com') !== false && $add_ext_auto === 'yes') 
            {
                $this->show_msg( __('In the shortcode you might try to add_ext_auto="no" for this to work.', 'csv-to-html') );                    
            }

            //Not directly errors/warnings but some settings may have to change to show expected result(s)
            //
            if ( strlen( $filter_data ) > 0 )
            {
                if ( $filter_col === null) 
                {
                    $this->show_msg( __('Results when filtering and it\'s numeric chars<br>with a prefix or suffix (like a percent sign), then you could try with the attribute filter_removechars="{actual character(s)}','csv-to-html') );
                    $this->show_msg( __('You must specify column to apply the filter on','csv-to-html') );
                }

                if ( strlen( $filter_operator ) > 0 )
                {
                    $this->show_msg( __('If you get unexpected results when filtering and it\'s numeric chars<br>with a prefix or suffix (like a percent sign), then you could try with the attribute filter_removechars="{actual character(s)}','csv-to-html') );
                }

                if ($filter_operator == 'between') 
                {
                    $filter_data = explode( '-', $filter_data );
                    if ( count ($filter_data) !== 2 ) 
                    {
                        $this->show_msg( __('You have set between in filter_operator but have not provided a hyphen in your filter_data','csv-to-html') );
                    }
                    else 
                    {   
                        //Hyphen is set but value nr 2 is not
                        if ( strlen($filter_data[0]) == 0 || strlen($filter_data[1]) == 0 )  
                        {
                            $this->show_msg( __('You have set between in filter_operator but you have to set a value before the hyphen (-) and one value after the hyphen (-).','csv-to-html') );
                        }
                    }
                }      
                else if ( strlen( $filter_operator ) > 0 ) 
                {
                    if ( strpos( $filter_data, '-') !== false ) 
                    {
                        $this->show_msg( __('You have a hyphen (-) in your filter_data that does not have affect on the filter_operator ' . $filter_operator, 'csv-to-html') );
                    }                
                }

            }

            
            //Stuff regardring to total percentage
            if ( $total_percentage_checkvalue === 'yes' )
            {
                if ( $total_percentage_col === null) 
                {
                    $this->show_msg( __('Total percentage value is set, but you must specify a column to search in with total_percentage_col', 'csv-to-html') );
                }
            }
   
            if ( $total_percentage_above_table === 'no' && $total_percentage_below_table === 'no')
            {
                $this->show_msg( __('Total percentage above or below has no effect (desired text/percentage will not be shown) because you have not set total_pecentage_checkvalue.', 'csv-to-html') );
            }


            echo '<hr><pre>';
            print_r ( $args );            
            echo '</pre><hr>';

        }


        /**
        * Show error or message when debugging plugin
        * If an array is sent to this function then show values of the array nicely
        * 
        * @param N/A
        * @return N/A
        *                 
        */                 
        public function show_msg($custom_message = null) 
        {               
            if ($custom_message === null) 
            {
                $custom_message = $this->csvtohtml_msg;
            }

            echo '<pre style="margin:0;padding:0;">';

            if (is_array($custom_message)) 
            {
                print_r($custom_message);
            }
            else {
                echo "{$custom_message}"; 
            }
            echo '</pre>';
            return;
        }


        /**
        * Check encoding given in csv file (if it's possible to use and if all necessary values set)
        *
        * @param $convert_encoding_from     What characterset to encode from? (If not set uses default)
        * @param $convert_encoding_to       What characterset to encode to?   (must be set)
        *
        */
        public function check_encoding( $convert_encoding_from, $convert_encoding_to )
        {
            $encoding_error = false;
            if ( $convert_encoding_from !== null && $convert_encoding_to === null)
            {
                $this->show_msg( '<strong>' . __('You must tell what encoding to convert to', 'csv-to-html') . '</strong><br>' );   
                $encoding_error = true;
            }

            if ( $convert_encoding_from == $convert_encoding_to) {
                $this->show_msg( __('Encoding from and encoding to are the same. This works but does slow does slow performance.') . '<br>');                
            }
            
            if ( $convert_encoding_from !== null && strtolower( $convert_encoding_from ) !== 'windows-1255' ) 
            {
                if (in_array( $convert_encoding_from, mb_list_encodings() ) === false) 
                {                        
                    $this->show_msg( __('Convert FROM encoding (' . $convert_encoding_from . ') is not supported (make sure upper/lower case is correct)', 'csv-to-html') );   
                    $encoding_error = true;
                }
            }

            if ( $convert_encoding_to !== null && strtolower( $convert_encoding_from ) !== 'windows-1255' )
            {
            if (in_array( $convert_encoding_to, mb_list_encodings() ) === false)
            {
                $this->show_msg( __('Convert TO encoding (' . $convert_encoding_to . ') is not supported (make sure upper/lower case is correct)', 'csv-to-html') );      
                $encoding_error = true;
            }
            }
            
            if ( $encoding_error === true )
            {
                $this->show_msg( __('Supported encodings:') );
                $this->show_msg( mb_list_encodings() );   
                return true;                  
            }

        }

    }

}