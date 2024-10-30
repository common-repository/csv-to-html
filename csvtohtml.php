<?php
/*
Plugin Name: CSV to html
Plugin URI: http://www.wibergsweb.se/plugins/csvtohtml
Description:Display/edit/synchronize csv-file(s) dynamically into a html-table
Version: 3.04
Author: Wibergs Web
Author URI: http://www.wibergsweb.se/
Text Domain: csv-to-html
Domain Path: /lang
License: GPLv2
*/
defined( 'ABSPATH' ) or die( 'No access allowed!' );
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory; //Used for reading Excel-file(s)

if( !class_exists('csvtohtmlwp') ) {
    //Main class
    class csvtohtmlwp
    {
    private $csv_delimit; //Used when using anonymous function in array_map when loading file(s) into array(s)    
    private $title; //Used together with sourcetype guessonecol
    private $headerrow_exists; //Used when there are only data in file(s) but no header row
    private $default_eol = "\r\n"; //Default - use this as this has been default in previous version of the plugin
    private $encoding_to = null;
    private $encoding_from = null;
    private $sorting_on_columns = null; //Should contain an array
    private $tablestoragefolder = 'csvtohtml-arrs'; //Where to store temporary files (used together with fetch interval)
    private $org_altvalues = null; //Used for handling complexity of alt-values when containing cleantext and values from column(s)
    private $header_values; //Used for is_row_applicable and multifilters
    private $filter_operators; //Used for is_row_applicable and multfilters

    /**
    *  Constructor
    *
    *  This function will construct all the neccessary actions, filters and functions for the sourcetotable plugin to work
    *
    *  @param	N/A
    *  @return	N/A
    */	
    public function __construct() 
    {                              
        add_action( 'init', array( $this, 'loadlanguage' ) );
    }
    

    /**
     * loadlanguage
     * 
     * This function load translations (if there are any)
     *  
     *  @param	N/A
     *  @return	N/A
     *                 
     */    
    public function loadlanguage() 
    {                     
        //Load (if there are any) translations
        $loaded_translation = load_plugin_textdomain( 'csv-to-html', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );
        $this->init();
    }      

   /**
    *   uploadfile_ajax()
    *   
    *   Used when uploading a file. Used for csv or json-files. 
    *
    *  @param	N/A
    *  @return	N/A
    *
    */
    public function uploadfile_ajax() 
    {
        if (!empty($_FILES['csv_file'])) 
        {
            $file = $_FILES['csv_file'];
    
            if ($file['error'] === UPLOAD_ERR_OK) 
            {
                $file_name = $file['name'];
                $temp_file = $file['tmp_name'];
    
                //Move uploaded file to Wordpress upload folder
                $upload_dir = wp_upload_dir();

                //This copies files into plugin examples folder
                //These files will not be there after an updatet of the plugin
                if ( $_POST['frm_path'] == '%temp%' )
                {
                    $upload_dir['basedir'] =  WP_PLUGIN_DIR . '/csv-to-html/examples';
                    $upload_dir['baseurl'] = 'file uploaded into plugin examples folder';
                }

                $destination = $upload_dir['basedir'] . '/' . $file_name;                
                if (move_uploaded_file($temp_file, $destination)) 
                {
                    $file_url = $upload_dir['baseurl'] . '/' . $file_name;    
                    echo json_encode( __('File uploaded successfully', 'csv-to-html') . ': ' . $file_url);
                } 
                else 
                {
                    echo json_encode( __('Error moving uploaded file.', 'csv-to-html') );
                }
            } 
            else 
            {
                echo json_encode( __('Error uploading file: ', 'csv-to-html') );
            }
        } 
        else 
        {
            echo json_encode( __('No file selected.', 'csv-to-html') );
        }

        wp_die();
    }


    /**
     * get_defaults_json
     * 
     * @param void
     * @return $json    json-format     json-format for key/pair
     */
    public function get_defaults_json() {      
        $defaults = $this->get_defaults();        
        echo json_encode( $defaults );
        wp_die();
    }
    

   /**
    *   add_settings_link()
    *   
    *   Handling of "settings-link" on the plugin list page 
    *
    *  @param	array   $links              Array of links
    *  @param   string  $file               file and path to the plugin
    *  @return	array   $links              Array of links
    *
    */    
    public function add_settings_link( $links, $file )
    {
        //This setting is only applied to this plugin
        if ( $file === 'csv-to-html/csvtohtml.php' && current_user_can( 'manage_options' ) ) 
        {
            $settings_link = '<b><a href="tools.php?page=csv-to-html">' . __( 'Shortcode generator', 'csv-to-html' ) . '</a></b>';
            $links[] = $settings_link;
            return $links;
        }
        else 
        {
            //This is need to return links as they were (otherwise it affects other plugin's links!?)
            return $links;
        }
    }

    
    /**
     *  init
     * 
     *  This function initiates the actual shortcodes etc
     * 
     *  @param N/A
     *  @return N/A
     *                 
     */        
    public function init() 
    {        
        add_action('wp_ajax_upload_file', array($this, 'uploadfile_ajax') );

        add_filter( 'plugin_action_links', array( $this, 'add_settings_link' ), 10, 2 );               
        add_action('wp_ajax_savecsvfile', array($this,'savecsvfile_ajax'));

        add_action('wp_ajax_fetchtable', array($this,'fetchtable_ajax'));
        add_action('wp_ajax_nopriv_fetchtable', array($this,'fetchtable_ajax'));
 
        wp_register_style( 'csvtohtml-css', plugins_url('/css/wibergsweb188.css', __FILE__), false );
        wp_enqueue_style( 'csvtohtml-css' );
        wp_register_style( 'csvtohtml-templates', plugins_url('/css/templates5.css', __FILE__), false );
        wp_enqueue_style( 'csvtohtml-templates' );

        wp_enqueue_script( 'jquery' );    
        wp_enqueue_script(
            'csvtohtml-js',
            plugins_url( '/js/wibergsweb197.js' , __FILE__, array('jquery') )
        );           
        wp_localize_script( 'csvtohtml-js', 'my_ajax_object', array( 
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            ) 
        );         
       
        add_action('wp_ajax_getdefaults', array($this,'get_defaults_json'));
        add_action('wp_ajax_refreshform', array($this,'dynamic_form'));
        add_action('wp_ajax_nopriv_getdefaults', array($this,'get_defaults_json'));
 
        add_shortcode( 'csvtohtml_create', array ( $this, 'source_to_table') );

        add_action( 'admin_menu', array( $this, 'help_page') );
        
    }


    /**
     * savecsvfile_ajax
     * 
     *  This function saves savecontent into a file
     *  seemlinglessly without any interaction from user
     * 
     *  @param N/A
     *  @return N/A 
     * 
     */
    public function savecsvfile_ajax() 
    {
        $csv_content = $_POST['csvcontent'];
        $csv_file = $_POST['csvfile'];
        $file_row = $_POST['filerow'];
        $csv_headers = $_POST['csvheaders'];
        $content_arr = explode('**', $_POST['allcontent']);  //All content for specific csv-file intended (is set in the generated all-rowcontent div)
        //Remove last item (because it's empty because ** exists at the end of array)
        $content_arr = array_slice($content_arr,0,-1);

        //Make explode BEFORE doing urldecoding (because & is used for exploding and 
        //if you have ?output=csv&type=gid etc then this think out and type are keys and would therefore fail)
        $attributes =  explode('&', ( $_POST['attrs'] ) );
        $new_arr = [];
        foreach( $attributes as $v) 
        {
            $split = explode('=' , $v);
            $key = $split[0];
            $value = $split[1];
            $new_arr[$key] = urldecode($value);
        }

        //Change row in array based on filerow given from ajax call
        //This array is used for saving down below        
        $content_arr[$file_row] = $csv_content[$file_row];
        
        //Save file         
        $f = fopen($csv_file, 'w'); 
        foreach($csv_headers as $header) 
        {          
            fwrite($f, $header . PHP_EOL ); 
        }
    
        $cnt = count($content_arr);
        foreach($content_arr as $index=>$row) 
        {
            if (!empty( $content_arr[$index]))
            {
                //Don't write newline on last row
                if ( intval($index)< intval($cnt) )
                {                   
                    $whole_row = $row . PHP_EOL;
                    $content_arr[$index] = $row . '**'; //Used for saving back into htmldiv row-allcontent
                }
                else 
                {
                    $whole_row = $row;
                    $content_arr[$index] = $row;        //Used for saving back into htmldiv row-allcontent
                }

                fwrite($f, $whole_row);
            }
            else {
                unset($content_arr[$index]);
            }

            
        }        
       

        fclose($f);
                
        //Return
        $data = ['allcontent' => $content_arr, 'filerow' => $file_row, 'cnt' => $cnt, 'index' => $index];
        echo json_encode($data);  
        wp_die();             
    }


    /**
     *  fetchtable_ajax
     * 
     *  This function fetches html based on shortcode values (given from javascript and indirectly from a 
     *  hidden form under the table in the tablewrapper). This is used for ajax pagination and search.
     * 
     *  @param N/A
     *  @return N/A
     *                 
     */       
    public function fetchtable_ajax() 
    {        
        $data = [];      
        $html = '';

        if (isset( $_POST) ) 
        {     
            //Make explode BEFORE doing urldecoding (because & is used for exploding and 
            //if you have ?output=csv&type=gid etc then this think out and type are keys and would therefore fail)
            $attributes =  explode('&', ( $_POST['attrs'] ) );
            $new_arr = [];
            foreach( $attributes as $v) 
            {
                $split = explode('=' , $v);
                $key = $split[0];
                $value = $split[1];
                $new_arr[$key] = urldecode($value);
            }

            if ( isset( $new_arr['org_altvalues'] ) )
            {
                $this->org_altvalues = explode(",", $new_arr['org_altvalues']);
                $new_arr['htmltags_autoconvert_imagealt'] = implode(",", $this->org_altvalues);
               
            }

            //Don't hide row(s) anymore if this is set to yes before (this is only applicable at first load)...
            $new_arr['hidetable_load'] = 'no'; 

            //Don't care about fetch interval. Pull the data you want directly
            $new_arr['fetch_interval'] = null;
                    
            //Pagination
            if ( !empty( $_POST['pagination_start']) ) 
            {
                $new_arr['pagination_start'] = intval( $_POST['pagination_start'] );                  
            }

            if ( !empty( $_POST['reset']) ) 
            {                
                $new_arr['doing_search'] = 'no'; 
               
                if ( $new_arr['preservefilter_search'] !== "yes" )
                {
                    if ( isset ( $new_arr['filter_data']) ) 
                    {       
                        unset (  $new_arr['filter_data'] );
                    }
                    if ( isset ( $new_arr['filter_col']) )
                    {
                        unset ( $new_arr['filter_col'] );
                    }
                
                    if ( isset ( $new_arr['filter_operator']) )
                    {
                        unset ( $new_arr['filter_operator'] );
                    }
                    if ( isset ( $new_arr['filter_criterias']) )
                    {
                        unset ( $new_arr['filter_criterias'] );
                    }  
                }     
                

                $new_arr['pagination_start'] = 1;               

                //If you added hidetable_reset, then hide table at each reset 
                //It's the same functionality, so reuse same attribute (hidetable_load) for doing this               
                if ( $new_arr['hidetable_reset'] === 'yes' )
                {
                    $new_arr['hidetable_load'] = 'yes';        
                }

                //So not custom message that result not found is shown when clicking on resetbutton
                $new_arr['found_search'] = 1; 
                
                $html = $this->source_to_table( $new_arr );    
                $data['tabledata'] = $html;      
                echo json_encode($data);                
                wp_die();   
            }

            //Do sort on specific column given from js
            if ( isset( $_POST['dosort']) )
            {   
                if ( $new_arr['preservefilter_search'] !== "yes" )
                {
                    if ( isset ( $new_arr['filter_data']) ) 
                    {                 
                        unset (  $new_arr['filter_data'] );
                    }
                    if ( isset ( $new_arr['filter_col']) )
                    {
                        unset ( $new_arr['filter_col'] );
                    }
                    if ( isset ( $new_arr['filter_operator']) )
                    {
                        unset ( $new_arr['filter_operator'] );
                    }     
                    if ( isset ( $new_arr['filter_criterias']) )
                    {
                        unset ( $new_arr['filter_criterias'] );
                    }     
                }
                
                $column = $_POST['column'];
                $direction = $_POST['direction'];
                $new_arr['user_sort'] = "yes"; //Need so sorting direction is applied on table header
                $new_arr['pagination_start'] = 1;   
                $new_arr['sort_cols'] = intval($column);
                $new_arr['sort_cols_order'] = mb_strtolower($direction);

                $html = $this->source_to_table( $new_arr );    
                $data['tabledata'] = $html;      
                echo json_encode($data);                
                wp_die();     
            }


            //Search. Use filter_data and filter_col to achieve the search functionality
            if ( isset( $_POST['search']) ) 
            {
                $search_str = ltrim( $_POST['search'] );  //If extra spaces in beginning, ignore them
                $new_arr['doing_search'] = 'no'; 

                //Search in all rows (even if user has filtered out with include rows)
                if ( !empty( $new_arr['search_excludedrows']) )
                {
                    if ( $new_arr['search_excludedrows'] === "yes" )
                    {
                        if ( isset($new_arr['include_rows']))
                        {
                            unset( $new_arr['include_rows'] );
                        }
                    }
                }

                if ( isset( $new_arr['found_search']) )
                {
                    unset( $new_arr['found_search'] );
                }

                //These must be unset before search so this works if pagination is active or not
                if ( $new_arr['preservefilter_search'] !== "yes" )
                {
                    if ( isset ( $new_arr['filter_data']) ) 
                    {                 
                        unset (  $new_arr['filter_data'] );
                    }
                    if ( isset ( $new_arr['filter_col']) )
                    {
                        unset ( $new_arr['filter_col'] );
                    }
                    if ( isset ( $new_arr['filter_operator']) )
                    {
                        unset ( $new_arr['filter_operator'] );
                    }                  
                    if ( isset ( $new_arr['filter_criterias']) )
                    {
                        unset ( $new_arr['filter_criterias'] );
                    }   
                
                }

                if ( !isset ( $new_arr['search_caseinsensitive'])) 
                {
                    $new_arr['search_caseinsensitive'] = 'yes'; //Default
                }


                $temp_pagination = 'no';
                if ( isset( $new_arr['pagination']) ) {
                    $temp_pagination = $new_arr['pagination'];
                }
                $new_arr['pagination'] = 'no'; //Remove pagination when doing the search
                
                //If amount of chars required at search, then show what is required
                //for the searchresults to show
                if ( $search_str !== null )
                {
                    if ( mb_strlen( $search_str ) < $new_arr['search_requiredchars'] )
                    {
                        if ( mb_strlen( $new_arr['search_requiredchars_message'] ) > 0 )
                        {
                            $message = $new_arr['search_requiredchars_message'];
                        }
                        else 
                        {
                            //Default if no specific message is given
                            $message = __('You must specifiy at least', 'csv-to-html') . ' ' . $new_arr['search_requiredchars'] . ' ' . __('characters when doing a search', 'csv-to-html') . '!';
                        }
                        $new_arr['result_message'] = $message;
                        $new_arr['found_search'] = 0;     
                        $html = $this->source_to_table( $new_arr );        
                        $data['tabledata'] = $html;        
                        echo json_encode($data);                
                        wp_die();               
                    }
                }
               
                //Creating settings so correct columns are included in the final search
                $temp_includecols = null;
                $temp_excludecols = null;
                
                if ( isset ( $new_arr['include_cols'] ) )
                {
                    $temp_includecols = $new_arr['include_cols'];                    
                    unset ( $new_arr['include_cols'] );
                }                
                else if ( isset ( $new_arr['exclude_cols'] ) )
                {
                    $temp_excludecols = $new_arr['exclude_cols'];
                    $remove_cols = $this->adjust_columns( $temp_excludecols );  
                    unset ( $new_arr['exclude_cols'] );
                }   

                //Get data array (not html)
                $new_arr['return_rows_array'] = 'yes';
                $rows_arr =  $this->source_to_table( $new_arr );
                $get_cols = count( $rows_arr[0] );
                $get_rows = count ( $rows_arr );
                $new_arr['return_rows_array'] = 'no';
                if ( $temp_includecols !== null )
                {
                    $search_cols = $this->adjust_columns( $temp_includecols );                        
                }
                //Go through all rows and check column for column if search-criteria is matched  
                //Only include the columns in search that are shown in table  
                else if ( $temp_excludecols !== null )
                {

                    $last_col = count ( $rows_arr[0] );                  
                    $exclude_cols = str_replace('last', $last_col, $temp_excludecols );
                    $remove_cols = $this->adjust_columns( $exclude_cols );

                    $search_cols = [];
                    foreach( $rows_arr[0] as $colkey=>$value) {
                        if ( !in_array( $colkey, $remove_cols) ) 
                        {
                            $search_cols[] = $colkey;
                        }
                    }
                                     
                }  
                else {
                    //Neither include or exclude col is given, include all columns
                    $search_cols = [];
                    foreach( $rows_arr[0] as $colkey=>$value) {                       
                        $search_cols[] = $colkey;                       
                    }                    
                }              
               
                //Do the actual search

                if ( $new_arr['preservefilter_search'] !== "yes" )
                {              
                    //If user is using search_cols this overrides include, exclude cols
                    //and it would be these columns that are searched in
                    if ( isset( $new_arr['search_cols']) )
                    {
                        $search_cols = $this->adjust_columns( $new_arr['search_cols'] ); 
                    }
                                    
                    if ( $new_arr['search_exactmatch'] === 'yes') 
                    {
                        if ( $new_arr['search_caseinsensitive'] === 'yes') 
                        {
                            //Exact match search not case insensitve (e.g. ABC = abc)  
                            $foperator = 'equals_caseinsensitive'; 
                        }
                        else {
                            //Exact match search  (e.g. ABC != abc)
                            $foperator = 'equals';   
                        }
                    }
                    else {
                        //Wildcard search
                        $foperator = 'wildcard';
                    }

                    $fcols = [];
                    $hcols = $this->adjust_columns( $new_arr['hide_cols'] );                
                    foreach($search_cols as $sc)
                    {
                        //If this column is in hide columns, then don't include it in search
                        if ( in_array( $sc, $hcols) !== false )
                        {
                            continue;
                        }
                        $fcols[] = intval($sc)+1; //$sc = index, these values in fcols are the actual column (e.g. 1 instead 0, 2 instead of 1 etc)
                    }

                    
                    $new_arr['filter_col'] = implode(",", $fcols);
                    $new_arr['filter_operator'] = $foperator;
                    $new_arr['filter_data'] = $search_str;
                               
                }

                //return_found returns number of (visible) rows
                $new_arr['return_found'] = 'yes';
                $nrrows_found =  $this->source_to_table( $new_arr );   
                $new_arr['return_found'] = 'no'; 
     
                if ( intval( $nrrows_found ) == 0 ) 
                {
                    //Action when result not found, customized message if set
                    if ( !empty($new_arr['notfound_message']) && $new_arr['notfound_message'] !== "no") 
                    {
                        $new_arr['result_message'] = $new_arr['notfound_message']; 
                    }   
                    else 
                    {
                        $new_arr['result_message'] = __('Nothing was found', 'csv-to-html');                                       
                    }

                    $new_arr['found_search'] = 0;
                }      

                if ($temp_includecols !== null) 
                {
                    $new_arr['include_cols'] = $temp_includecols; //Restore to settings before search
                }
                else if ($temp_excludecols !== null) 
                {
                    $new_arr['exclude_cols'] = $temp_excludecols; //Restore to settings before search
                }

                $new_arr['pagination'] = $temp_pagination; //Restore to settings before search
            }       
            
            $new_arr['doing_search'] = 'yes';
            //Generate html (for pagination or search)            
            $html = $this->source_to_table( $new_arr );
        }

        $data['tabledata'] = $html;        
        echo json_encode($data);                
        wp_die();        
    }
	
    public function help_page() 
    {
        add_management_page( 'CSV to HTML', 'CSV to HTML', 'manage_options', 'csv-to-html', array( $this, 'start_page') );                                 
    }
    

    /**
     * create_select_encoding()
     * 
     * helpfunction for creating select element with supported encodings
     * 
     * @param $select_name    name of the select-element
     * @param $name           name of the value to compare with (so set selected or not)
     * @return $html
     * 
     */
    private function create_select_encoding( $select_name, $name ) {
        $list_encodings = mb_list_encodings();
        $list_encodings[] = 'Windows-1255'; //Special because this is not included as standard in supported encodings
        sort($list_encodings);

        //Include these two at beginning of array
        unset( $list_encodings['auto'] );
        unset( $list_encodings['pass'] );        
        array_unshift($list_encodings, "", "auto", "pass"); //Prepend to array
        $list_encodings = array_unique( $list_encodings );

        $html = '<select name="' . $select_name . '">';            
        foreach ( $list_encodings as $encoding )
        {
            $selected_encoding = '';
            if ( $name == $encoding ) 
            {
                $selected_encoding = ' selected';
            }
            $html .= '<option' . $selected_encoding . ' value="' . $encoding . '">' . $encoding . '</option>';
        }
        $html .= '</select>';
        return $html;
    }
    
    
    /**
     * start_page()
     *
     * This is shown when going into CSV to HTML menu
     * 
     * @param void          
     * @return $html        string
     * 
     */
    public function start_page() 
    {   
        echo '<h1>CSV to HTML Shortcode Generator</h1>';
        echo '<p style="font-size:1.3em;padding:0.5em;"><strong>CSV to HTML</strong> is a fast lightweight plugin that creates html tables "on the fly" (dynamically) directly from specificed csv file(s). Change in files shows directtly in the html table! ';
        echo ' This is done by using a <i>shortcode</i>. <strong>A shortcode is just a snippet of settings you put into your page/post or widget</strong>. There is a plugin demo-site here that shows some things you can do with the plugin: ';
        echo '<a target="_blank" href="https://wibergsweb.se/plugins/csvtohtml/">Here is a lot of examples and livedemos for inspiration</a>';
        echo '<br><br>If you need any help please don\'t hesitate to contact me (Gustav Wiberg, <a href="mailto:info@wibergsweb.se">info@wibergsweb.se</a>).';
        echo ' To able to give good support and great development all donations are welcome.<br><span style="font-weight:bold;color:darkgreen;">If you like the plugin, please donate to Paypal:<a href="https://www.paypal.com/donate?hosted_button_id=8JHZ495S839LQ" rel="nofollow ugc">
        Donate to this plugin</a>. If you live in Sweden, please use Swish 072-525 51 12. How much should you donate? Just add a dollar if you dont know.</span> ';        
        echo 'I will also appreciate if you give a review of this plugin <a target="_blank" href="https://wordpress.org/support/plugin/csv-to-html/reviews/">here</a>. Thanks in advance!';
        echo '</p><hr>';
        echo '<form id="csvtohtml-upload-form" action="' . esc_url(admin_url('admin-post.php')) . '" method="post" enctype="multipart/form-data">
        <p style="font-size:1.3em;padding:0.5em;margin:0;color:darkgreen;">        
        <strong>Get started!</strong><br>        
        * <strong>Select source file(s).</strong> 
        Local file\'s root are in wp-content/uploads: <strong>Do this by clicking on General section down below and modify path and file(s).</strong><br>
        * If you don\'t have access to the uploads-folder, then upload your file to the upload folder here: 
        <input type="hidden" name="action" value="upload_file">
        <input type="file" name="csv_file" accept=".csv,.json,.xlsx">
        <input type="submit" value="Upload file">
        <div id="upload-result"><b>Note! <span style="color:red;">If you\'re using Wordpress Playground</span> (Live Preview from plugin page), then <span style="color:red;">set %temp% in path</span> and delimiter to semicolon (;) to view an example csv-file and do a lot of testing with different settings. <span style="color:red;">Uploading files in the Playground environment does not work at the moment</span>.<br><br>When using IMPORT-functionality ALL files with the same name will be overwritten!</div>
 
        <p style="font-size:1.3em;padding:0.5em;margin:0;color:darkgreen;">     
        * Click on different sections down below and <strong>make changes of settings and then click on Update/Preview</strong> button<br>
        * <strong>Copy shortcode below and paste it into a page,post or widget</sttrong>.
        </p>
        </form>';

        echo '<form><input type="button" id="update_shortcode" value="Update/Preview"></form>';
 
        echo '<div class="flexcontainer shortcodegenerator-csvtohtml">';
        echo '<div class="flexitem shortcodegenerator-csvtohtml">';
        $this->dynamic_form();
        echo '</div>'; 
            echo '<div class="flexitem shortcodegenerator-csvtohtml" id="shortcode_preview">';
        echo '</div>';  
        echo '</div>';  //end flexcontainer
    }

    
    /**
     * dynamic_form()
     *
     * Dynamic form based on a shortcode. If no shortcode given a default shortcode is used
     * 
     * @param void          
     * @return $html        string
     * 
     */
    public function dynamic_form() 
    {         
        $defaults = $this->get_defaults();
        $js_generated = false;
        $html = '<div id="dynamic_form">'; 

        if ( isset( $_GET['shortcode']) ) 
        {
            $shortcode = stripslashes( $_GET['shortcode']); 
            $js_generated = true;
        }
        else 
        {
            //If there are a shortcode-string transient, then use it
            if ( get_transient('csvtohtml_shortcode') ) {
                $shortcode = get_transient('csvtohtml_shortcode');
            }            
            else {
                //If no transient exists, use default shortcode                
                $shortcode = '[csvtohtml_create source_type="guess" source_files="*.csv"]';
            }
        }

        
        $html .= '<h2>Shortcode:</h2>';
        $html .= '<h3><span id="new_shortcode">' . $shortcode . '</h3>';

        /** 
         * Explode by spaces but not when spaces are within quotes (when having filename that has spaces)
         * @source https://stackoverflow.com/questions/66163283/how-to-explode-a-string-but-not-within-quotes-in-php/66163363?noredirect=1#66163363
        */
        $args = preg_split('/"[^"]+"(*SKIP)(*F)|\h+/', $shortcode);
        $attrs = [];

        foreach( $args as $item )
        {
            //This is important so no extra brackets messes shortcode up
            $item = str_replace(']', '', $item);
            
            //Only use those items in array $args with equal sign
            //Divide key and value based on FIRST equal-sign
            $pos = strpos( $item, '=' );
            if ( $pos !== false )
            {
                $key = mb_substr( $item, 0, $pos);
                $value = mb_substr( $item, $pos+1);          
                $attrs[$key] = str_replace( '"', '', $value );                
            }
        }

        $sc_attributes = wp_parse_args( $attrs, $defaults );
        $sc_attributes['debug_mode'] = 'no';
        $sc_attributes['debug'] = 'no';

        $shortcode = str_replace('debug_mode', '', $shortcode); //Ignore debugmode. 
        $shortcode = str_replace('debug', '', $shortcode); //Ignore debugmode. 

        extract( $sc_attributes );

        //Base upload-dir
        $upload_dir = wp_upload_dir();
        $upload_basedir = $upload_dir['basedir'];

        if ( $path == '%temp%' )
        {
            $upload_basedir = WP_PLUGIN_DIR . '/csv-to-html';
        }

        //Copy attributes to another array and add return_rows_array to get
        //total number of columns and rows in file(s) fetched
        $temp_attribs = array_slice( $sc_attributes , 0);
        $temp_attribs['return_rows_array'] = 'yes';

        //remove inclusion/exclusion to make sure all cols/rows are included
        if ( isset ( $temp_attribs['include_cols']) ) 
        {
            $temp_attribs['include_cols'] = null;
        }
        if ( isset ( $temp_attribs['exclude_cols']) ) 
        {
            $temp_attribs['exclude_cols'] = null;
        }        

        $rows_arr =  $this->source_to_table( $temp_attribs );
        
        //Calculate cols and rows
        $nr_cols = 0;
        if ( isset( $rows_arr[0])) 
        {
            $nr_cols = count( $rows_arr[0] );
        }
        if ( $temp_attribs['source_type'] === 'guessonecol')
        {
            unset($rows_arr[0]);
        }

        $nr_rows = 0;
        if ( isset( $rows_arr) )
        {
            $nr_rows = count ( $rows_arr );        
        }
                
        
        
        /* Debugging */
        //
        $debug_info = [];

        if ( $source_type == 'guessonecol' )
        {
            $nr_cols = 1;
        }

        if ( $nr_cols == 0 || $nr_rows == 0) {
            $debug_info[] = '<b>No data found in file(s).</b><br>Probably you\'re pointing to incorrect source/file.<br>If it is an external file, make sure you could access the file directly (test the url in browser).';
            $debug_info[] = 'If this is your first time here, you will probably get this message because no csv-files exists in the uploads-folder of your wordpress-installation.';
            $debug_info[] = 'If this is your first time here and you get this error, the recommendation is to create a folder in your uploads-folder (e.g. uploads/csvfiles) and then copy a csv-file into that folder. Change path under general-section (path is folder relative to uploads-folder), click update/preview-button and you will probably see a preview of your csv-file.';
        }

        if ( $skip_headerrow === "yes" && $headerrow_exists === "no" )
        {
            $debug_info[] = 'If you set headerrow_exists to no and skip_headerrow to yes, skip_headerrow will be ignored.';
        }

        if ( $skip_headerrow === 'yes' && mb_strlen( $title ) > 0 )
        {
            $debug_info[] = 'If you set skip_header to yes, then title will not be shown.';
        }

        $local_file = true;
        if ( stristr( $source_files, 'http') )
        {
            $local_file = false;
        }

        if ( stristr( $source_files, 'http' ) && $editable === 'yes' ) 
        {
            $debug_info[] = 'You can not edit files when fetching external files.';            
        }

        //Using local files
        if ( $local_file === true )
        {
            $ex = explode(';', $source_files);
            $incorrect_csv = [];
            foreach($ex as $item) 
            {
                if ( substr($item,-4,4) === '.csv' ) 
                {
                }
                else 
                {
                    $incorrect_csv[] = $item;
                }
            }


            $first = true;
            foreach( $incorrect_csv as $csvitem) {
                
                //.csv not end of string. found .csv and end it after that                
                $s = stripos( $csvitem, '.csv');               
                if ( $s >0 ) {
                    if ( $s != mb_strlen($csvitem)-4 ) {
                        if ( $first === true )
                        {
                            $debug_info[] = '<span style="display:block;font-weight:bold;padding:0;margin:0;">File(s) below seems to be incorrect</span>';
                            $first = false;
                        }                  
                        $new_csvitem = substr($csvitem, 0, $s) . '.csv';
                        if ( $csvitem == '.csv') {$new_csvitem = '*.csv';}                    
                        $debug_info[] = '<div class="adjustspelling-wrapper"> Did you mean <b><span data-file="' . $csvitem . '" class="adjustspelling">' . $new_csvitem . '</span>? <button class="changefile">Click here to change spelling. The page will update after this change so make sure you have changed all settings before trying this.</button></div>';               
                    }
                }
                else {
                    //.csv not found at all
                    if ( $first === true )
                    {
                        $debug_info[] = '<span style="display:block;font-weight:bold;padding:0;margin:0;">File(s) below seems to be incorrect</span>';
                        $first = false;
                    }
                    $s = stripos( $csvitem, '.');                        
                    if ( $s > 0) {
                        $new_csvitem = substr($csvitem, 0, $s) . '.csv';
                    }
                    else {
                        $new_csvitem = $csvitem . '.csv';
                    }                
                    if ( $new_csvitem == '.csv') {$new_csvitem = '*.csv';}   
                    $debug_info[] = '<div class="adjustspelling-wrapper"> Did you mean <b><span data-file="' . $csvitem . '" class="adjustspelling">' . $new_csvitem . '</span>? <button class="changefile">Click here to change spelling. The page will update after this change so make sure you have changed all settings before trying this.</button></div>';                    
                }
            
            }

        }
        //End Using local file(s)
       
        if ( $nr_cols > $nr_rows ) 
        {
            if ( $source_type !== 'guess') {
                $debug_info[] = ' Sourcetype could be right but most of cases it is not. If you don\'t get expected output you could try changing type (source_type) to guess.';
            }
        }

        if ( $nr_cols == 1 && $source_type !== 'guessonecol' && $local_file === true ) //If using guessonecol sourcetyp there are only one column as result 
        {
            $debug_info[] = 'You\'re <i>probably</i> using incorrect delimiter because only one column is retrieved. Change it down below and click on the update/preview button.';
        }

        if ( isset ( $html_id) && $html_id !== null)
        {
            if ( mb_strlen( $html_id ) >0 ) 
            {
                for($i=1;$i<10;$i++) 
                {
                    if ( intval(substr($html_id,0,1)) === intval($i) ) {
                        $debug_info[] = 'Your <b>html id</b> would not validate and would not be available for js etc if you\'re not starting with a letter. Begin html id with a letter.';
                        break;
                    }
                }
            }
        }

        if ( $html_class !== null)
        {        
            if ( mb_strlen( $html_class ) >0 )
            {
                for($i=1;$i<10;$i++) 
                {
                    if ( intval(substr($html_class,0,1)) === intval($i) ) {
                        $debug_info[] = 'Your <b>html class</b> would not validate and would not be available for js etc if you\'re not starting with a letter. Begin html id with a letter.';
                        break;
                    }   
                }        
            }
        }

        if ( $table_in_cell_wrapperclass !== null )
        {
            if ( mb_strlen( $table_in_cell_wrapperclass ) >0 )
            {
                for($i=1;$i<10;$i++)
                {
                    if ( intval(substr($table_in_cell_wrapperclass,0,1)) === intval($i) ) {
                        $debug_info[] = 'Your <b>table in cell wrapper class</b> would not validate and would not be available for js etc if you\'re not starting with a letter. Begin html id with a letter.';
                        break;
                    }
                }        
            }
        }

        if ( $convert_encoding_to !== null && $convert_encoding_from !== null )
        {
            if ( mb_strlen( $convert_encoding_to ) > 0 && mb_strlen( $convert_encoding_from ) == 0 )
            {
                $debug_info[] = 'If you don\'t get expected result, you should try set current encoding <b>from</b> to the actual encoding of the csv-file. It <i>might</i> work.';
            }

            if ( mb_strlen( $convert_encoding_to ) > 0 && mb_strlen( $convert_encoding_from ) > 0 )
            {
                $debug_info[] = 'Sometimes it\'s easy to think that everything you add will make it better. In some cases removing character encodings might make it better. <button id="removeencodings">Remove encodings. Make sure you have changed all other settings before trying this because the page will update.</button>';
            }
        }
        
        if ( $filter_data !== null )
        {
            if ( mb_strlen ( $filter_data ) > 0 )
            {
                if ( mb_strlen( $filter_col) == 0 )
                {
                    $debug_info[] = 'You have set a filter but you have not specified which column(s) to apply the filter on. Do that under Filter-section.';
                }
                else {
                    $debug_info[] = 'If you dont get any results <b>' . $filter_data . '</b> may not exist in <b>column ' . $filter_col . '</b> of your table.';
                }
            }
        
            if ( mb_strlen ( $filter_col) > 0 && mb_strlen ( $filter_data ) == 0 )
            {
                $debug_info[] = 'You have specified which filter to apply filter on but you have not set any data in filter data. If your intention is not to have any filter: <button id="removefilter">Remove filter now. Make sure you have changed all settings because this will update page.</button>';
            }
        }


        //fetchinterval must have html_id to work
        if ( empty($html_id) && $fetch_interval !== null) 
        {
            $debug_info[] = 'html_id (set under section styling) must be set for fetch_interval to work!';
        }

        if ( $api_cdn === 'no') {
            $debug_info[] = 'You are using Wordpress native API to request external sources. Some services like Cloudflare might not work with this setting. If having issues set Stream content from external sources to yes (in section General).';
        }

        if ( $nr_rows > 1000 && $pagination == 'no' ) {
            $debug_info[] = 'We recommend you putting pagination to on because you will have more than 1000 rows generated (It will work anyway but user will have to wait).';
        }

        //Presentation of warnings/errors and possible solutions
        if ( count($debug_info) > 0 ) 
        {
            $html .= '<div class="csvtohtml-p admin debugging">';
            $html .= '<h2><span style="color:#ff0000;">Debugging</span> (Click here if having issues. Known problems and issues could be solved easily).</h2>';
            $html .= '<table><tr><td>';

            foreach( $debug_info as $ditem) {
                $html .= '<p>' . $ditem . '</p>';       
            }

            $html .= '</td></tr></table>';
            $html .= '</div>';
        }


        /* General */
        $html .= '<div class="csvtohtml-p admin general">';
        $html .= '<h2>General</h2>';
        $html .= '<table>';
    
        $html .= '<tr><td>Number of rows:</td><td>' . $nr_rows . '</td></tr>';
        $html .= '<tr><td>Number of columns:</td><td>' . $nr_cols . '</td></tr>';        
        $html .= '<tr><td>Location of <i>local</i> file(s):</td><td>'. $upload_basedir . '/';
        $html .= '<span id="csvtohtmlsettings-path">' . str_replace( '%temp%','examples',$path ) . '</span></td></tr>';
        $html .= '<tr><td>Path (local):</td><td><input type="text" name="frm_path" value="' . $path . '">&nbsp;<button id="pathviewer">...</button></tr>';
              
        //Make a list of all directorys under uploads-directory
        $use_uploadbasedir = $upload_basedir;
        
        if ( $path == '%temp%' )
        {
            $use_uploadbasedir .= '/examples';            
        }

        $dir_iterator = new DirectoryIterator($use_uploadbasedir);
       
        $html .= '<tr id="uploadpaths"><td>&nbsp;</td><td>';
        foreach ($dir_iterator as $fileinfo) 
        {
            if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                $html .= '<a class="pathlink" href="#">' . $fileinfo->getFilename().'</a>';
            }
        }
        $html .= '</td></tr>';       
        $html .= '<tr><td>File(s) combined to this table (if you have external files, just put in the whole url here):</td><td><input type="text" name="frm_source_files" class="textlong" value="' . $source_files . '">&nbsp;<button id="fileviewer">...</button></td></tr>';
        $html .= '<tr id="fileview"><td>&nbsp;</td><td>';       
        foreach ($dir_iterator as $fileinfo) {
            if (!$fileinfo->isDir()) {
                $html .= '<a data-basename="' . $fileinfo->getBaseName() . '" class="filelink" href="#">' . $fileinfo->getPathName().'</a>';
            }
        }
        $html .= '<td></tr>';
        $html .= '<tr><td colspan="2"><hr></td></tr>';
             
        if (isset($debug)) 
        {
            unset($debug);
        }

        $html .= '<input style="display:none;" name="frm_debug_mode" value="no">';  
        $html .= '<input style="display:none;" name="frm_debug" value="no">';
        $html .= '</td></tr>';

        //General / editable
        $html .= $this->rowselect_yesno( 'Headerow exists:', 'frm_headerrow_exists',$headerrow_exists );        
        $html .= $this->rowselect_yesno( 'Skip headerrow:', 'frm_skip_headerrow',$skip_headerrow );        
        $html .= $this->rowselect_yesno('Editable (applies to local files only):','frm_editable',$editable );
        $html .= $this->rowselect_yesno('Show table only when logged in:', 'frm_show_onlyloggedin', $show_onlyloggedin );
        $html .= $this->rowselect_yesno('Add extension automatically:','frm_add_ext_auto', $add_ext_auto );
                
        $html .= '<tr><td>Type:</td><td>';    
        $html .= '<select name="frm_source_type">';
        if ( mb_strlen($source_type) == 0 )
        {
            $source_type = 'guess';
        }
        $sourcetypes = $this->valid_sourcetypes( $source_type, true);
        foreach($sourcetypes as $item) 
        {
            $selected_sourcetype = '';
            if ( $source_type == $item) 
            {
                $selected_sourcetype = ' selected';
            }
            $html .= '<option value="' . $item . '"' . $selected_sourcetype . '>' . $item . '</option>';
        }        
        $html .='</select></td></tr>';

        $html .= '<tr><td>Selected sheet(s) in format sheetnr(1-2,3 etc) or sheet name (sheet1, sheet2, sheet3 etc)<br>(applicable when using Excel-files):</td><td><input type="text" length="1" name="frm_selected_sheets" value="' . $selected_sheets . '"</td></tr>';
        $html .= '<tr><td>JSON start level:</td>';
        $html .= '<td><input type="text" name="frm_json_startlevel" value="' . trim($json_startlevel) . '"></td></tr>';        
        $html .= '</td></tr>';
        $html .= '<tr><td>End of line detection:</td><td><input type="text" name="frm_eol_detection" value="' . $eol_detection . '"></td></tr>';       
        $html .= '<tr><td colspan="2"><hr></td></tr>';               
        $html .= '<tr><td>Custom title (topmost left):</td><td><input type="text" name="frm_title" value="'. $title . '"></td></tr>';
        $html .= '<tr><td>Delimiter (between values in file):</td><td><input type="text" length="5" name="frm_csv_delimiter" value="'. $csv_delimiter . '"></td></tr>';
        $html .= '<tr><td>Float divider (for numeric usage):</td><td><input type="text" length="1" name="frm_float_divider" value="' . $float_divider . '"</td></tr>';
        $html .= '<tr><td>Fetch last headers:</td><td><select name="frm_fetch_lastheaders">';

        $html .= '<option value="">Not set</option>';

        for($i=1;$i<$nr_cols+1;$i++) 
        {
            $fetch_selected = '';
            if ( intval($fetch_lastheaders)>0 ) {
                if ( $i == $fetch_lastheaders) 
                {
                    $fetch_selected = ' selected';
                }
            }
            $html .= '<option' . $fetch_selected . ' value="' . $i . '">' . $i . '</option>';
        }
        $html .='</select></td></tr>';
        
        //General / api_cdn
        $html .= $this->rowselect_yesno('Stream content from external sources:', 'frm_api_cdn',$api_cdn );

        //General / fetch intervals
        $f_intervals = $this->fetch_interval_validate('', true);

        $html .= '<tr><td>Fetch interval:</td>';
        $html .= '<td><select name="frm_fetch_interval">';
        $html .= '<option value="">Not set</option>';

        foreach($f_intervals as $f_item) {
            $selected_fetch = '';
            if ( $fetch_interval == $f_item) {
                $selected_fetch = ' selected';
            }
            $html .= '<option value="' . $f_item . '"' . $selected_fetch . '>' . $f_item . '</option>';
        }
        
        $html .='</select></td></tr>';        

        $html .= '<tr><td>Large files (use when having memory issues on server):</td>';
        $html .= '<td><select name="frm_large_files">';
 
        $yn = ['yes','no'];
        foreach($yn as $item) {
            $selected = '';
            if ( $large_files == $item) {
                $selected = ' selected';
            }
            $html .= '<option value="' . $item . '"' . $selected . '>' . $item . '</option>';
        }
        
        $html .='</select></td></tr>';
        
        $html .= '</table>';
        $html .= '</div>';
      
        /* Columns and rows */
        $html .= '<div class="csvtohtml-p admin columns_and_rows">';
        $html .= '<h2>Columns and rows</h2>';
        $html .= '<table>';
        $html .= '<tr><td style="width:100px;padding-right:2em;">Number of rows:</td><td>' . $nr_rows . '</td><td colspan="' . ($nr_cols-2) . '">&nbsp;</td></tr>';
        $html .= '<tr><td style="width:100px;padding-right:2em;">Number of columns:</td><td>' . $nr_cols . '</td><td colspan="' . ($nr_cols-2) . '">&nbsp;</td></tr>';
        $html .= '<tr><td style="width:100px;padding-right:2em;">Include rows:</td><td><input type="text" name="frm_include_rows" value="' . $include_rows . '"></td><td colspan="' . ($nr_cols-2) . '">&nbsp;</td></tr>';
        $html .= '<tr><td style="width:100px;padding-right:2em;">Header start rows:</td><td><input type="text" name="frm_headerrows_start" value="' . $headerrows_start . '"></td></tr>';

        $column_nr = 1;
        $html .= '<tr><td colspan="2"><table style="margin-left:-2px;">';

        $html .= '<tr>';
        $html .= '<th style="padding-right:4em;">Include/exclude/hide columns</th>';
        for($i=1;$i<$nr_cols+1;$i++) 
        {
            $html .= '<th style="text-align:left;">' . $i . '</th>';
        }

        $html .= '</tr><tr>';
     
        $use_includecols = $this->adjust_columns( $include_cols ); 
        $use_excludecols = $this->adjust_columns( $exclude_cols );
        $use_hidecols = $this->adjust_columns( $hide_cols );
        
        $html .= '<td>&nbsp;</td>';

        if ( $totals_cols_bottom !== null )
        {
            $check_selected_totals = explode( ',' ,$totals_cols_bottom );
        }
        else 
        {
            $check_selected_totals = [];
        }


        for($i=1;$i<$nr_cols+1;$i++) 
        {
            $selected_include = '';
            $selected_exclude = '';
            $selected_hide = '';
            if ( in_array( $i-1, $use_includecols) !== false ) 
            {
                $selected_include = ' checked="checked"';
            }
            if ( in_array( $i-1, $use_excludecols) !== false ) 
            {
                $selected_exclude = ' checked="checked"';
            }   
            if ( in_array( $i-1, $use_hidecols) !== false ) 
            {
                $selected_hide = ' checked="checked"';
            }               
            $selected_ignore = '';
            if ( $selected_exclude == '' && $selected_include == '' && $selected_hide == '' ) {
                $selected_ignore = '  checked="checked"';
            }         
            $selected_total = '';
            if ( in_array ( $i, $check_selected_totals) !== false )
            {
                $selected_total = ' checked="checked"';
            }

            //inclusion/exclusion column clude[col]
            $html .= '<td>
            <div class="check"><input type="radio" name="clude[' . $column_nr . ']" data-num="ignore"' . $selected_ignore . '>None</div>         
            <div class="check"><input type="radio" name="clude[' . $column_nr . ']" data-num="include"'  . $selected_include . '>Include</div>
            <div class="check"><input type="radio" name="clude[' . $column_nr . ']" data-num="exclude"' . $selected_exclude . '>Exclude</div>
            <div class="check"><input type="radio" name="clude[' . $column_nr . ']" data-num="hide"' . $selected_hide . '>Hide</div>            
            <div class="check"><input type="checkbox" name="totalcl[' . $column_nr . ']" data-num="total"' . $selected_total . '>Total</div>
            </td>';
            $column_nr++;
        }
        $html .= '</td></table></tr>';

        $html .= '<tr><td colspan="2">';
        $html .= '<input type="text" name="frm_include_cols" id="include_shortcode_str" value="' . $include_cols . '">';
        $html .= '<input type="text" name="frm_exclude_cols" id="exclude_shortcode_str" value="' . $exclude_cols . '">';
        $html .= '<input type="text" name="frm_hide_cols" id="hide_shortcode_str" value="' . $hide_cols . '">';
        $html .= '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';


        //Styling
        $html .= '<div class="csvtohtml-p admin styling">';
        $html .= '<h2>Styling</h2>';
        $html .= '<table>';

        if (!isset($html_id))
        {
            $html_id = '';
        }
        $html .= '<tr><td>html id:</td><td><input type="text" name="frm_html_id" value="' . $html_id . '">';
        $html .= '<tr><td>html class:</td><td><input type="text" name="frm_html_class" value="' . $html_class . '">';
        $html .= $this->rowselect_yesno( 'Fixed layout:', 'frm_table_fixedlayout', $table_fixedlayout );
                      
        $html .= '<tr><td>Header type:</td>';
        $html .= '<td><select name="frm_header_type">';
        $html .= '<option value="">Not set</option>';

        $header_types = ['sticky','fixed'];
        foreach($header_types as $item) {
            $selected = '';
            if ( $header_type == $item) {
                $selected = ' selected';
            }
            $html .= '<option value="' . $item . '"' . $selected . '>' . $item . '</option>';
        }

        $html .='</select></td></tr>';               

        $html .= '<tr><td>Tableheader background color :</td><td><input type="text" name="frm_header_backgroundcolor" value="' . $header_backgroundcolor . '"</td></tr>';
        $html .= '<tr><td>Tableheader background color left column :</td><td><input type="text" name="frm_header_backgroundcolor_left" value="' . $header_backgroundcolor_left . '"</td></tr>';
        $html .= '<tr><td>Tableheader textcolor :</td><td><input type="text" name="frm_header_textcolor" value="' . $header_textcolor . '"</td></tr>';
        $html .= '<tr><td>Tableheader textcolor left column :</td><td><input type="text" name="frm_header_textcolor_left" value="' . $header_textcolor_left . '"</td></tr>';
        
        
        $html .= '<tr><td>Table height (only used for fixed header):</td><td><input type="text" name="frm_table_height" value="' . $table_height . '"</td></tr>';
        $html .= '<tr><td>Table offset header  (default top of screen):</td><td><input type="text" name="frm_table_offset_header" value="' . $table_offset_header . '"</td></tr>';       
        $html .= $this->rowselect_yesno( 'Fixed left column:', 'frm_fixed_leftcol', $fixed_leftcol );
        $html .= '<tr><td>Table width (Used for fixed header and/or fixed_leftcol):</td><td><input type="text" name="frm_table_width" value="' . $table_width . '"</td></tr>';
        $html .= $this->rowselect_yesno( 'Responsive:', 'frm_responsive', $responsive );
        
        $html .= '<tr><td>CSS max width:</td><td><input type="text" name="frm_css_max_width" value="' . $css_max_width . '"</td></tr>';
        $html .= '<tr><td>CSS min devicewidth:</td><td><input type="text" name="frm_css_min_devicewidth" value="' . $css_min_devicewidth . '"</td></tr>';
        $html .= '<tr><td>CSS max devicewidth:</td><td><input type="text" name="frm_css_max_devicewidth" value="' . $css_max_devicewidth . '"</td></tr>';

        $html .= '<tr><td colspan="2"><hr></td></tr>';
        $html .= '<tr><td>Design template:</td>';
        $html .= '<td><select name="frm_design_template">';
        $html .= '<option value="">Not set</option>';

        $design_templates = $this->design_template_validate('', true);
        foreach($design_templates as $d_item) {
            $selected_template = '';
            if ( $design_template == $d_item) {
                $selected_template = ' selected';
            }
            $html .= '<option value="' . $d_item . '"' . $selected_template . '>' . $d_item . '</option>';
        }
        
        $html .='</select></td></tr>';       
        $html .= '</table>';
        $html .= '</div>';          

        /* Pagination */
        $html .= '<div class="csvtohtml-p admin pagination">';
        $html .= '<h2>Pagination</h2>';
        $html .= '<table>';         
        $html .= $this->rowselect_yesno( 'Pagination active:', 'frm_pagination', $pagination );        
        $html .= $this->rowselect_yesno( 'Show pagination below table:', 'frm_pagination_below_table', $pagination_below_table );
        $html .= $this->rowselect_yesno( 'Show pagination above table:', 'frm_pagination_above_table', $pagination_above_table );

        if ( mb_strlen( $pagination_start ) == 0)
        {
            $pagination_start = 1;
        }
        $html .= '<tr><td>Row to start pagination with (generally always 1):</td><td><input type="text" name="frm_pagination_start" value="' . $pagination_start . '"</td></tr>';
        $html .= '<tr><td>Text start for pagination. Leave if you do not want to show:</td><td><input type="text" name="frm_pagination_text_start" value="' . $pagination_text_start . '"</td></tr>';
        $html .= '<tr><td>Text Prev (previous) for pagination. Leave empty if you do not want to show:</td><td><input type="text" name="frm_pagination_text_prev" value="' . $pagination_text_prev . '"</td></tr>';
        $html .= '<tr><td>Text Next for pagination. Leave empty if you do not want to show:</td><td><input type="text" name="frm_pagination_text_next" value="' . $pagination_text_next . '"</td></tr>';
        $html .= '<tr><td>Text Last for pagination. Leave empty if you do not want to show:</td><td><input type="text" name="frm_pagination_text_last" value="' . $pagination_text_last . '"</td></tr>';
        $html .= '<tr><td>How many rows you should show at each pagination</td><td><input type="text" name="frm_pagination_rows" value="' . $pagination_rows . '"</td></tr>';
        $html .= '<tr><td>Show links (1,2,3... up to 10 links (10 is default)). Set to 0 if you do not want to show at all:</td><td><input type="text" name="frm_pagination_links_max" value="' . $pagination_links_max . '"</td></tr>';
        $html .= '</table></div>';                                          
      
        /* Search */
        $html .= '<div class="csvtohtml-p admin search">';
        $html .= '<h2>Search</h2>';
        $html .= '<table>';                
        $html .= $this->rowselect_yesno( 'Search active:', 'frm_search_functionality', $search_functionality );
        $html .= $this->rowselect_yesno( 'Realtime search (search is done when user is entering searchcriteria):', 'frm_search_realtime', $search_realtime );
        $html .= $this->rowselect_yesno( 'Search case insensitive (e.g "great" and "GREaT" <strong>are</strong> treated the same):', 'frm_search_caseinsensitive', $search_caseinsensitive );
        $html .= $this->rowselect_yesno( 'Search exact match  (e.g "great" and "GREaT" <strong>are not</strong> treated the same):', 'frm_search_exactmatch', $search_exactmatch );
        $html .= $this->rowselect_yesno( 'Search excluded rows (search in rows that are not displayed):', 'frm_search_excludedrows', $search_excludedrows );
        $html .= $this->rowselect_yesno( 'Search highlight (show a backgroundcolor on searched and filtered items):', 'frm_search_highlight', $search_highlight );        
        $html .= '<tr><td>Highlight color (if search highlight is set to yes):</td>';
        $html .= '<td><input type="text" name="frm_search_highlightcolor" value="' . $search_highlightcolor . '"</td></tr>';

        $column_nr = 1;
        $html .= '<tr><td colspan="2"><table style="margin-left:-2px;">';

        $html .= '<tr>';
        $html .= '<th style="padding-right:4em;">Columns to search in (default is all columns)</th>';
        for($i=1;$i<$nr_cols+1;$i++) 
        {
            $html .= '<th style="text-align:left;">' . $i . '</th>';
        }

        $html .= '</tr><tr>';
     
        $search_includecols = $this->adjust_columns( $search_cols ); 
        
        $html .= '<td>&nbsp;</td>';

        for($i=1;$i<$nr_cols+1;$i++) 
        {
            $selected_include = '';
            if ( in_array( $i-1, $search_includecols) !== false ) 
            {
                $selected_include = ' checked="checked"';
            }

            $selected_ignore = '';
            if ( $selected_include == '') {
                $selected_ignore = '  checked="checked"';
            }         

            //inclusion of search columns (searchincludecols)
            $html .= '<td>
            <div class="check"><input type="radio" name="search_includecols[' . $column_nr . ']" data-num="ignore"' . $selected_ignore . '>None</div>         
            <div class="check"><input type="radio" name="search_includecols[' . $column_nr . ']" data-num="include"'  . $selected_include . '>Include</div>
            </td>';
            $column_nr++;
        }
        $html .= '</td></table></tr>';

        $html .= '<tr><td colspan="2">';
        $html .= '<input type="text" name="frm_search_cols" id="include_searchcols_shortcode_str" value="' . $search_cols . '">';
        $html .= '</td></tr>';
        $html .= $this->rowselect_yesno( 'Hide table when page loads first time (no values are shown until a search is done):', 'frm_hidetable_load', $hidetable_load );        
        $html .= $this->rowselect_yesno( 'Hide table when user clicks reset-button (button is created when search is active):', 'frm_hidetable_reset', $hidetable_reset );        
        $html .= '<tr><td>Search button text:</td><td><input type="text" name="frm_searchbutton_text" value="' . $searchbutton_text . '"</td></tr>';
        $html .= '<tr><td>Reset button text:</td><td><input type="text" name="frm_resetbutton_text" value="' . $resetbutton_text . '"</td></tr>';
        $html .= '<tr><td>Placeholder-text for search input field</td><td><input type="text" name="frm_searchinput_placeholder" value="' . $searchinput_placeholder . '"</td></tr>';
        $html .= '<tr><td>What message to show when searchresult is not found (if set to no, don\'t show anything):</td><td><input type="text" name="frm_notfound_message" value="' . $notfound_message . '"</td></tr>';
        $html .= '<tr><td>How many characters at least user must type in before search is valid:</td><td><input type="text" name="frm_search_requiredchars" value="' . $search_requiredchars . '"</td></tr>';
        $html .= '<tr><td>What message to show (tell user there are a required number of chars when doing a search)</td><td><input type="text" name="frm_search_requiredchars_message" value="' . $search_requiredchars_message . '"</td></tr>';
        $html .= '</table></div>';      
      
        /* Sorting */
        $html .= '<div class="csvtohtml-p admin sorting">';
        $html .= '<h2>Sorting</h2>';
        $html .= '<table>';
        $html .= $this->rowselect_yesno( 'Sort ascending/descending on userclick:', 'frm_sort_cols_userclick', $sort_cols_userclick );        
        $html .= $this->rowselect_yesno( 'If sorting based on user click, show arrows:', 'frm_sort_cols_userclick_arrows', $sort_cols_userclick_arrows );        
        $html .= '</table>';

        $html .= '<table>';
        $column_nr = 1;
        $html .= '<tr><td colspan="2"><table style="margin-left:-2px;">';
        
        $use_sort_cols = $this->adjust_columns( $sort_cols ); 
        if ( $sort_cols_order !== null )
        {
            $use_sort_directions = explode(',', $sort_cols_order);
        }
        else
        {
            $use_sort_directions = [];
        }

        //Connect columns with sorting directions
        $col_sortdirs = [];        

        //If this occurs, no columns are set
        if ( $sort_cols !== null)
        {
            if ( mb_strlen( $sort_cols ) == 0 )  
            { 
                unset( $use_sort_cols[0] );
            }
        }

        foreach($use_sort_cols as $key => $item) {
            $col_sortdirs[$item] = $use_sort_directions[$key];
        }
        $col_sortdirs_keys = array_keys( $col_sortdirs );

        $html .= '<tr>';
        $html .= '<th style="padding-right:4em;">Sort by column: </th>';
        $iteration = 1;
        for($i=1;$i<$nr_cols+1;$i++) 
        {
            $html .= '<th style="text-align:left;">' . $i . '<br>';
            $html .= '<select name="sortiteration_col[' . $i . ']">';
            $html .= '<option value="-1">Not used</option>';

            for($j=1;$j<$nr_cols+1;$j++)
            { 
                $selected = '';
                $pop = '';

                if ( isset( $col_sortdirs_keys[$j-1]) ) 
                {
                    if ( $col_sortdirs_keys[$j-1] == ($i-1) ) 
                    {
                        $selected = ' selected';
                    }
                    
                }
                $html .= '<option value="' . ($j-1) . '"' . $selected .  '>' . $pop . 'Iteration ' . $j . '</option>';
            }
            $html .= '</select>';
            $html .= '</th>';
        }
        $html .= '</tr><tr>';
        $html .= '<td>&nbsp;</td>';

        for($i=1;$i<$nr_cols+1;$i++) 
        {
            $selected_ignore = '';
            $selected_asc = '';
            $selected_desc = '';

            if (isset($col_sortdirs[$i-1])) 
            {
                if ($col_sortdirs[$i-1] == 'asc') 
                {
                    $selected_asc = ' checked="checked"';
                }
                else if ($col_sortdirs[$i-1] == 'desc') 
                {
                    $selected_desc = ' checked="checked"';
                }
                else {
                    $selected_ignore = ' checked="checked"';
                }
            }
            else {
                $selected_ignore = ' checked="checked"';
            }

            //sorting[column] = type of sorting, value = 0=> No sorting, 1=> Sort A-z, 2=> Sort Z-A
            //clude[column] = value 0 => include, 1 => exclude 
            $html .= '<td>
            <div class="check"><input type="radio" name="sorting[' . $column_nr . ']" data-num="nosort"' . $selected_ignore . '>No sorting</div>
            <div class="check"><input type="radio" name="sorting[' . $column_nr . ']" data-num="asc"' . $selected_asc . '>Sort A-Z</div>
            <div class="check"><input type="radio" name="sorting[' . $column_nr . ']" data-num="desc"' . $selected_desc . '>Sort Z-A</div>
            </td>';
            $column_nr++;
        }
        $html .= '</tr>';        
        $html .= '</table>';
        $html .= '<input type="text" name="frm_sort_cols" id="sort_str" value="' . $sort_cols .  '">';
        $html .= '<input type="text" name="frm_sort_cols_order" id="sort_str_direction" value="' . $sort_cols_order . '">';
        $html .= '</td></tr></table>';
        $html .= '</div>';

        /* Filter */
        $html .= '<div class="csvtohtml-p admin datafilter">';
        $html .= '<h2>Filter</h2>';
        $html .= '<table>';
        $html .= '<tr><td>What data to filter:</td><td><input type="text" name="frm_filter_data" value="' . $filter_data . '"></td></tr>';
        $html .= '<tr><td>Filter criterias<br>(AND/OR-logic with columns separated by comma):</td><td><input type="text" name="frm_filter_criterias" value="' . $filter_criterias . '"></td></tr>';
        $html .= '<tr><td>Remove characters from filter: <i>(e.g. if % is set, then 5.6% would convert into 5.6)</i></td><td><input type="text" name="frm_filter_removechars" value="' . $filter_removechars . '"</td></tr>';
        
        $filter_arr = [
                        'equals' => 'Equals to ' . $filter_data, 
                        'nequals' => 'Not equal to ' . $filter_data,
                        'equals_caseinsensitive' => 'Equals to ' . $filter_data . ' but case insensitive',
                        'is_empty' => 'The value is empty',
                        'more' => 'More than ' . $filter_data,
                        'less' => 'Less than ' . $filter_data,
                        'mequal' => 'More or equal to ' . $filter_data,
                        'lequal' => 'Less or equal to ' . $filter_data,
                        'newdates' => 'Show new dates from (default todays date if what data to filter is not set. If given it must have format YYYY-MM-DD)',
                        'wildcard' => 'Any data within ' . $filter_data . '(wildcard)'
                    ];

        $html .= '<tr><td col>Filter operator:</td><td>';
        $html .= '<select name="frm_filter_operator">
        <option value="">not set</option>';

        foreach( $filter_arr  as $filter_key => $filter_option) {
            $filter_selected = '';
            if ( $filter_operator == $filter_key) {
                $filter_selected = ' selected';
            }
            $html .= '<option' . $filter_selected . ' value="' . $filter_key . '">' . $filter_option . '</option>';            
        }
        $html .= '</select>
        </td></tr>';


        $html .= '<tr>';
        $html .= '<th style="padding-right:4em;">Columns to use filter on (default is all columns)</th>';
        for($i=1;$i<$nr_cols+1;$i++) 
        {
            $html .= '<th style="text-align:left;">' . $i . '</th>';
        }

        $html .= '</tr><tr>';
     
        $filter_includecols = $this->adjust_columns( $filter_col ); 
        
        $html .= '<td>&nbsp;</td>';

        $column_nr = 1;
        for($i=1;$i<$nr_cols+1;$i++) 
        {
            $selected_include = '';
            if ( in_array( $i-1, $filter_includecols) !== false ) 
            {
                $selected_include = ' checked="checked"';
            }

            $selected_ignore = '';
            if ( $selected_include == '') {
                $selected_ignore = '  checked="checked"';
            }         

            //inclusion of filter columns (filter_col)
            $html .= '<td>
            <div class="check"><input type="radio" name="filter_includecols[' . $column_nr . ']" data-num="ignore"' . $selected_ignore . '>None</div>         
            <div class="check"><input type="radio" name="filter_includecols[' . $column_nr . ']" data-num="include"'  . $selected_include . '>Include</div>
            </td>';
            $column_nr++;
        }
        $html .= '</td></table></tr>';

        $html .= '<tr><td colspan="2">';
        $html .= '<input type="text" name="frm_filter_col" id="include_filtercols_shortcode_str" value="' . $filter_col . '">';
        $html .= '</td></tr>';
        

        $html .= '</table></div>';


        /* Grouping */
        $html .= '<div class="csvtohtml-p admin grouping">';
        $html .= '<h2>Grouping</h2>';
        $html .= '<table>';
        $html .= '<tr><td>Which column to apply grouping on:</td><td><select name="frm_groupby_col" id="groupbycol">';
        $html .= '<option value="">not set</option>';
        for($i=1;$i<$nr_cols+1;$i++) 
        {
            $groupby_filtercol_selected = '';
            if ( $groupby_col == $i) {
                $groupby_filtercol_selected = ' selected';
            }
            $html .= '<option' . $groupby_filtercol_selected . ' value="' . $i . '">' . $i . '</option>';
        }
        $html .='</select></td></tr>';
        $html .= $this->rowselect_yesno( 'Grouping header (adds a class):', 'frm_groupby_col_header', $groupby_col_header );        
        $html .= '</table></div>';        

        /* Downloadable section */
        $html .= '<div class="csvtohtml-p admin downloadable">';
        $html .= '<h2>Download button</h2>';
        $html .= '<table>';        
        $html .= $this->rowselect_yesno( 'Downloadable (visible rows)?', 'frm_downloadable', $downloadable );        

        $html .= '<tr><td>Text on button:</td>';
        $html .= '<td><input type="text" name="frm_downloadable_text" value="' . $downloadable_text . '"></td></tr>';

        $html .= '<tr><td>Filename (to generate when downloading):</td>';
        $html .= '<td><input type="text" name="frm_downloadable_filename" value="' . $downloadable_filename . '"></td></tr>';
        $html .= '</table></div>';    

        if (!isset( $html_id) ) {
            $html_id = '';
        }

        /* HTML converts */
        $html .= '<div class="csvtohtml-p admin conversions">';
        $html .= '<h2>HTML conversion</h2>';
        $html .= '<table>';            
        $html .= $this->rowselect_yesno( 'Convert links to html-links (&lt;a&gt;), images to &lt;img&gt; etc:<br><i>(e.g. http://wibergsweb.se/ would be converted to &lt;a href="wibergsweb.se"&gt;Wibergsweb&lt;/a&gt;)</i>', 'frm_htmltags_autoconvert', $htmltags_autoconvert );        
        $html .= $this->rowselect_yesno( 'Make links open up in new window:', 'frm_htmltags_autoconvert_newwindow', $htmltags_autoconvert_newwindow );        
        $html .= '<tr><td>Alt-text on images (if nr or column name given, use value from that column on each row as alt-tag):</td>';
        $html .= '<td><input type="text" name="frm_htmltags_autoconvert_imagealt" value="' . $htmltags_autoconvert_imagealt . '"></td></tr>';
        $html .= '<tr><td>Width of images in px (default), %, vw, em, rem. If no width is given, then it just renders images original size in the cell.</td>';
        $html .= '<td><input type="text" name="frm_htmltags_autoconvert_imagewidth" value="' . $htmltags_autoconvert_imagewidth . '"></td></tr>';

        $html .= '<tr><td>Grab content from column and convert into link with specified columns content:</td>';
        $html .= '<td>From column:<br><select name="frm_grabcontent_col_fromlink" id="grabcontent_col_fromlink">';
        $html .= '<option value="">not set</option>';
        for($i=1;$i<$nr_cols+1;$i++) 
        {
            $grabcontent_col_fromlink_selected = '';
            if ( $grabcontent_col_fromlink == $i ) {
                $grabcontent_col_fromlink_selected = ' selected';
            }
            $html .= '<option' . $grabcontent_col_fromlink_selected . ' value="' . $i . '">' . $i . '</option>';
        }
        $html .='</select></td>';

        $html .= '<td>Column\'s content (to):<br><select name="frm_grabcontent_col_tolink" id="grabcontent_col_tolink_to">';
        $html .= '<option value="">not set</option>';
        for($i=1;$i<$nr_cols+1;$i++) 
        {
            $grabcontent_col_tolink_selected = '';
            if ( $grabcontent_col_tolink == $i ) {
                $grabcontent_col_tolink_selected = ' selected';
            }
            $html .= '<option' . $grabcontent_col_tolink_selected . ' value="' . $i . '">' . $i . '</option>';
        }
        $html .='</select></td></tr>'; 
        $html .= $this->rowselect_yesno('Markdown support:', 'frm_markdown_support', $markdown_support );
        $html .= $this->rowselect_yesno( 'Add https if not exists in expected link (<i>e.g. https://wibergsweb.se instead of wibergsweb.se</i>)', 'frm_grabcontent_col_tolink_addhttps', $grabcontent_col_tolink_addhttps );        
        $html .= '</table></div>';    

        /* Totals in columns */
        $html .= '<div class="csvtohtml-p admin totals">';
        $html .= '<h2>Totals (sum / calculation)</h2>';
        $html .= '<table>';        
        $html .= '<tr><td>Which column(s) to use for totals (eg 1,2 or 5-7):</td><td><input type="text" name="frm_totals_cols_bottom" value="' . $totals_cols_bottom . '"</td></tr>';
        $html .= $this->rowselect_yesno( 'Count number of rows (default is count values from each row in given column):', 'frm_totals_cols_bottom_countlines', $totals_cols_bottom_countlines );               
        $html .= '<tr><td>What string/character (maybe a zero?) to show when no calculation is done:</td><td><input type="text" name="frm_totals_cols_bottom_empty" value="' . $totals_cols_bottom_empty . '"</td></tr>';
        
        $html .= '<tr><td colspan="2"><hr></td></tr>';        
        $html .= '<tr><td>Set a specific string when added totals (overrides any value in totals):</td><td><input type="text" name="frm_totals_cols_bottom_title" value="' . $totals_cols_bottom_title . '"</td></tr>';
        $html .= '<tr><td>Which column to set this string (only one column is possible):</td><td><input type="text" name="frm_totals_cols_bottom_title_col" value="' . $totals_cols_bottom_title_col . '"</td></tr>';

        $html .= '<tr><td colspan="2"><hr></td></tr>';
        $html .= '<tr><td>Add prefix to the total column(s) <i>(e.g. $10 instead of 10)</i>:</td><td><input type="text" name="frm_totals_cols_prefix" value="' . $totals_cols_prefix . '"</td></tr>';
        $html .= '<tr><td>Add suffix to the total column(s) <i>(e.g. 10$ instead of 10)</i>:</td><td><input type="text" name="frm_totals_cols_suffix" value="' . $totals_cols_suffix . '"</td></tr>';
        
        $html .= '<tr><td colspan="2"><hr></td></tr>';
        $html .= '<tr><td>Check percentage of a specific value in a specific column (e.g. If for example the value "invoice" exists 5 of 10 times in a column, then percentage would be 50%):</td><td><input type="text" name="frm_total_percentage_checkvalue" value="' . $total_percentage_checkvalue . '"</td></tr>';

        $html .= '<tr><td>Which column to check in (which value above must be set for this to have any affect):</td><td><select name="frm_total_percentage_col">';
        $html .= '<option value="">Not set</option>';

        for($i=1;$i<$nr_cols+1;$i++) 
        {
            $total_percentage_col_selected = '';
            if ( intval($total_percentage_col)>0 ) {
                if ( $i == $total_percentage_col) 
                {
                    $total_percentage_col_selected = ' selected';
                }
            }
            $html .= '<option' . $total_percentage_col_selected . ' value="' . $i . '">' . $i . '</option>';
        }
        $html .='</select></td></tr>';        
        $html .= '<tr><td>Add prefix before the calculated percentage value (e.g. if set to "Percentage:", the text "Percentage: {value} % will be shown). If this is not given it will only show percentage value followed by %:</td><td><input type="text" name="frm_total_percentage_text" value="' . $total_percentage_text . '"</td></tr>';
        $html .= '<tr><td>Number of decimals to show when showing total percentage (e.g. 2 could give 47,56%):</td><td><input type="text" name="frm_total_percentage_decimals" value="' . $total_percentage_decimals . '"</td></tr>';
        $html .= $this->rowselect_yesno( 'Show total percentage of a specific value above table:', 'frm_total_percentage_above_table', $total_percentage_above_table );        
        $html .= $this->rowselect_yesno( 'Show total percentage of a specific value below table:', 'frm_total_percentage_below_table', $total_percentage_below_table );                
        $html .= '</table></div>';
    
        /* Character encoding */
        $html .= '<div class="csvtohtml-p admin characterencoding">';
        $html .= '<h2>Character encoding</h2>';
        $html .= '<table>';
        $html .= '<tr><td colspan="2">';
        $html .= '<i>(Do not do anything with these settings if not having issues with characters)</i>';
        $html .= '</td></tr>';

        $html .= '<tr><td>Convert encoding from:</td><td>';
        $html .= $this->create_select_encoding('frm_convert_encoding_from', $convert_encoding_from);
        $html .= '</td></tr>';
        $html .= '<tr><td>Convert encoding to:</td><td>';
        $html .= $this->create_select_encoding('frm_convert_encoding_to', $convert_encoding_to);
        $html .= '</td></tr>';
        $html .= '</table></div>';   

        /* Table in cell */
        $html .= '<div class="csvtohtml-p admin tableincell">';
        $html .= '<h2>Table in cell(s)</h2>';
        $html .= '<table>';        
        $html .= '<tr><td>Wrapper class:</td><td><input type="text" name="frm_table_in_cell_wrapperclass" value="' . $table_in_cell_wrapperclass . '"</td></tr>';
        $html .= '<tr><td>Header:</td><td><input type="text" name="frm_table_in_cell_header" value="' . $table_in_cell_header . '"</td></tr>';
        $html .= '<tr><td colspan="2"><input type="text" id="table_in_cell_cols" name="frm_table_in_cell_cols" value="' . $table_in_cell_cols . '"</td></tr>';
        $column_nr = 1;
        $html .= '<tr><td colspan="2"><table style="margin-left:-2px;">';

        $html .= '<tr>';
        $html .= '<th style="padding-right:4em;">Include columns</th>';
        for($i=1;$i<$nr_cols+1;$i++) 
        {
            $html .= '<th style="text-align:left;">' . $i . '</th>';
        }

        $html .= '</tr><tr>';
     
        $use_cols = $this->adjust_columns( $table_in_cell_cols );       
 
        $html .= '<td>&nbsp;</td>';
        $column_nr = 1;
        for($i=1;$i<$nr_cols+1;$i++) 
        {
            $selected_include = '';
            if ( in_array( $i-1, $use_cols) !== false ) 
            {
                $selected_include = ' checked="checked"';
            }

            //inclusion/exclusion column clude[col]
            $html .= '<td>            
            <div class="check"><input type="radio" name="tableincellsclude[' . $column_nr . ']" data-num="ignore"' . $selected_ignore . '>None</div>         
            <div class="check"><input type="radio" name="tableincellsclude[' . $column_nr . ']" data-num="include"'  . $selected_include . '>Include</div>
            </td>';
            $column_nr++;
        }
        $html .= '</td></table></tr>';
        $html .= '</table></div>';


        /* Referencelist of settings/attributes */
        $html .= '<div class="csvtohtml-p admin reflist">';
        $html .= '<h2>Referencelist</h2>';
        $html .= '<div class="referencelist">';
        $html .= do_shortcode('[csvtohtml_create html_id="csvtohtml-referencelist-csvtohtml-plugin" markdown_support="yes" source_type="guess" source_files="referencelist.csv" csv_delimiter=";" path="%temp%"]');
        $html .= '</div>';
        $html .= '</div>';
        
        
        $html .= '</div> <!-- end dynamic -->'; //End dynamic form

        //Reload of page takes last used shortcode.
        set_transient( 'csvtohtml_shortcode', $shortcode, 3600 );        
        
        //Return actual result of shortcode
        //(for previewing)
        if ( isset ( $_GET['doshortcode'] ) )
        {   
            if ( $_GET['doshortcode'] == 'yes' ) {
                echo '<h2>Preview</h2>'; 
                echo '<p>When you have changed anything and you want to see a preview of the shortcode-settings, just press on the update/preview - button. <strong>Note!</strong> This will show how the table would look like, but the actual functionality does not work until you put the shortcode in a page/post or widget.</p>';

                if ( $pagination == 'no' && $nr_rows > 1000 && !$include_rows ) {
                    echo 'This preview only shows first 20 rows because there are over 1000 rows';
                    echo ' and no pagination is set.<br>';
                    $shortcode = mb_substr(rtrim($shortcode), 0, -1) . ' include_rows="1-20"]';                    
                }

                $table_data = do_shortcode( $shortcode );
                echo $table_data;


               wp_die();
            }
        }  
        
      

        //Show dynamic form        
        echo $html;
    }

    
    /**
     *   get_tablestoragefolder
     * 
     *  Getter function for retrieving table storage folder used together with fetch interval
     * 
     *  @return   string                           Path to where to store files used for fetchinterval excluding upload path
     *                 
     */      
    protected function get_tablestoragefolder() 
    {
        return $this->tablestoragefolder;
    }

    
    /**
     *   valid_sourcetypes
     * 
     *  This function is a helper-function that is used for retrieving true/false if a source_type is valid or not
     *  (defined sourcetypes are used so plugin knows how to fetch content from csv files)
     * 
     *  @param  string  $source_type            what sourcetype to check
     *  @param  bool    $return_validtypes      true to return only valid sourcetypes
     *  @return bool                            true if valid, else false
     *  @return array   $valid_types            array of valid sourcetypes       
     */    
    public function valid_sourcetypes( $source_type = null, $return_validtypes = false ) 
    {
        if ( $source_type === null) 
        {
            return false;
        }
        
        //If guess is set as sourcetype, then plugin tries to figure out what sourcetype that should be used, 
        //but this is merely just a guess so it's better to define an actual source_type if applicable
        $valid_types = array( 'guess', 'guessonecol', 'visualizer_plugin', 'json' );
        if ( $return_validtypes === true )
        {
            return $valid_types;
        }

        if (in_array( $source_type, $valid_types) !== false) 
        {
            return true;
        }
        
        return false;
    }
    

    /**
     * Detects the end-of-line character of a string.
     * 
     * @param string $str The string to check.
     * @return string The detected eol. If no eol found, use default eol from object
     */    
    private function detect_eol( $str )
    {
        static $eols = array(
            "\0x000D000A", // [UNICODE] CR+LF: CR (U+000D) followed by LF (U+000A)
            "\0x000A",     // [UNICODE] LF: Line Feed, U+000A
            "\0x000B",     // [UNICODE] VT: Vertical Tab, U+000B
            "\0x000C",     // [UNICODE] FF: Form Feed, U+000C
            "\0x000D",     // [UNICODE] CR: Carriage Return, U+000D
            "\0x0085",     // [UNICODE] NEL: Next Line, U+0085
            "\0x2028",     // [UNICODE] LS: Line Separator, U+2028
            "\0x2029",     // [UNICODE] PS: Paragraph Separator, U+2029
            "\0x0D0A",     // [ASCII] CR+LF: Windows, TOPS-10, RT-11, CP/M, MP/M, DOS, Atari TOS, OS/2, Symbian OS, Palm OS
            "\0x0A0D",     // [ASCII] LF+CR: BBC Acorn, RISC OS spooled text output.
            "\0x0A",       // [ASCII] LF: Multics, Unix, Unix-like, BeOS, Amiga, RISC OS
            "\0x0D",       // [ASCII] CR: Commodore 8-bit, BBC Acorn, TRS-80, Apple II, Mac OS <=v9, OS-9
            "\0x1E",       // [ASCII] RS: QNX (pre-POSIX)
            "\0x15",       // [EBCDEIC] NEL: OS/390, OS/400
            "\r\n",
            "\r",
            "\n"
        );
        $cur_cnt = 0;
        $cur_eol = $this->default_eol;
        
        //Check if eols in array above exists in string
        foreach($eols as $eol){      
            $char_cnt = mb_substr_count($str, $eol);
                    
            if($char_cnt > $cur_cnt)
            {
                $cur_cnt = $char_cnt;
                $cur_eol = $eol;
            }
        }
        return $cur_eol;
    }


    /**
     *
     *  Helper-function to return a "highlighted syntax"
     *
     *  @param      string  $cell_value             Data from this current cell (and what is searched/filtered on)
     *  @param      string  $search_highlightcolor  Background color of highlighted cell(s) 
     *  @return     string                          formatted string for highlight                          
     *  
     *  @void       N/A       
     */
    private function make_highlighted( $cell_value, $search_highlightcolor )
    {   
        return '<span class="search-highlight" style="background-color:' . $search_highlightcolor . ';">' . $cell_value . '</span>';
    }


    /**
     *   Create object from given sourcetype
     * 
     *  Returns an object based on sourcetype given by user
     * 
     *  @param      string $source_type     source type from user
     *  @return     object $obj                     
     *                 
     */    
    public function object_fromsourcetype( $source_type, $json_startlevel ) {
        //What type of content in csv file(s) ?
        switch ( $source_type ) 
        {
            case 'guess':
                $obj = new csvtohtmlwp_guess( $this->csv_delimit, $this->headerrow_exists );
                break;     
                
            case 'guessonecol':
                $obj = new csvtohtmlwp_guessonecol( $this->csv_delimit, $this->headerrow_exists, $this->title );
                break;                  
            
            case 'visualizer_plugin':
                $obj= new csvtohtmlwp_visualizer_plugin();                
                break;   
            
            case 'json':                
                $obj = new csvtohtmlwp_json( $json_startlevel );
                break;

            default:
            //Guess object
            require_once( 'contentids/guess.php' ); 
            $obj = new csvtohtmlwp_guess();
            break;       
        }
        return $obj;
        
    }
    

    /**
     *   name_to_colnr
     * 
     *  This function is a helper function to convert column names into column numbers
     * 
     *  @param  string $what_columns             What columns it is about (1,2,3,7-12)
     *  @return   array                                        What columns to use
     *                 
     */  
    private function name_to_colnr( $what_columns, $header_values ) 
    {            
        $ex_cols = explode( ",", $what_columns );

        //Make all column values uppercase
        foreach($ex_cols as $ke=>$kval) 
        {
            $ex_cols[$ke] = mb_strtoupper(trim($kval));
        }
        //Make all header values uppercase
        foreach($header_values as $ke=>$kval) 
        {
            $header_values[$ke] = mb_strtoupper(trim($kval));
        }

        //Adjust any given column name into column number   
        $header_names = array_flip($header_values);
        $use_cols = [];

        foreach ( $ex_cols as $col_key => $col_value) 
        {    
            $result1 = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $col_value); 
            
            $found_col = false;
            foreach( $header_values as $header_key=>$hv ) 
            {               
                //Remove all non-visible characters from strings
                //@source: https://stackoverflow.com/questions/1176904/how-to-remove-all-non-printable-characters-in-a-string                    
                $result2 = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $hv); 

                //echo "Is $result1 same as $result2 ?<br>";
                if ( $result1 == $result2 ) 
                {                            
                    $use_cols[] = intval($header_names[$hv]) + 1; //Index + 1 tells user-dedfined column
                    $found_col = true;
                    break;
                }
            }
            
            if ( $found_col === false )
            {
                $use_cols[] = $col_value;
            }     
        }
        
        $what_columns = implode(",", array_unique($use_cols) );

        //This is needed because a lot of checks are done with NULL instead of an empty string
        if (mb_strlen($what_columns) == 0) 
        {
            return null;
        }
    
        $ex_cols = array_slice( array_unique($use_cols) ,0);

        foreach($ex_cols as $key=>$ec) {
            //Add hypen and number to array, so array will be consistent
            //with values users put in (1-3,7 will be 1,2,3,7 and not 7,1,2,3)
            if (stristr( $ec, '-') === false) 
            {
                $ex_cols[$key] .= '-' . $ec;
            }
        }
        
        //If two values given like 2-7...then add 2,3,4,5,6 and 7.
        foreach($ex_cols as $key=>$col_interval) 
        {
            $ac = explode('-', $col_interval); //3-7 would be array(3,7)
            if ((int)count($ac) === 2) { //Only include when array has two elements                                    
                //Remove blank spaces left and right of each element in $ac-array
                $ac[0] = (int)trim($ac[0]); //interval start
                $ac[1] = (int)trim($ac[1]) + 1; //interval stop
                
                //Go through interval and to $ac-array (add column array)
                for ($i=$ac[0];$i<$ac[1];$i++) {
                    $ex_cols[] = $i;
                }
                unset ( $ex_cols[$key] );
            }
        }


        return implode( ",",$ex_cols );

    }

    
    /**
     *   adjust_columns
     * 
     *  This function is a helper function for including or excluding columns in the final html table
     * 
     *  @param  string $what_columns             What columns it is about (1,2,3,7-12)
     *  @return   array                                        What columns to use
     *                 
     */       
    public function adjust_columns ( $what_columns ) 
    {
        if ( $what_columns === null) 
        {
            return [];
        }

        $ex_cols = explode(',', $what_columns );
        foreach($ex_cols as $key=>$ec) {
            //Add hypen and number to array, so array will be consistent
            //with values users put in (1-3,7 will be 1,2,3,7 and not 7,1,2,3)
            if (stristr( $ec, '-') === false) 
            {
                $ex_cols[$key] .= '-' . $ec;
            }
        }
        
        //If two values given like 2-7...then add 2,3,4,5,6 and 7.
        foreach($ex_cols as $key=>$col_interval) 
        {
            $ac = explode('-', $col_interval); //3-7 would be array(3,7)
            if ((int)count($ac) === 2) { //Only include when array has two elements                                    
                //Remove blank spaces left and right of each element in $ac-array
                $ac[0] = (int)trim($ac[0]); //interval start
                $ac[1] = (int)trim($ac[1]) + 1; //interval stop
                
                //Go through interval and to $ac-array (add column array)
                for ($i=$ac[0];$i<$ac[1];$i++) {
                    $ex_cols[] = $i;
                }
                unset ( $ex_cols[$key] );
            }
        }

        //Which columns to use?
        $use_cols = array();
        foreach ( $ex_cols as $c ) 
        {
            $use_cols[] = (int)($c - 1);
        }
                    
        return $use_cols;
    }
    
    
    /**
     *  custom_sort_columns
     * 
     *  This function is used for sorting one or several columns
     * 
     *  @param    $a                        First value
     *  @param    $b                        Second value
     *  @return   integer                   Returned comparision of firt and second value 
     *                 
     */      
    private function custom_sort_columns($a, $b)
    {        
        //This has to be an array to work
        if ( $this->sorting_on_columns === null ) 
        {
            return false;
        }        
        
        $columns = $this->sorting_on_columns;
        $first_column = true;        
        foreach($columns as $item)
        {            
            $col = $item[0];
            
            //If column not set, ignore sorting
            if (!isset($a[$col]) || !isset($b[$col])) 
            {
                return 0;
            }

            $sortorder = mb_strtolower( $item[1] );
            
            //First column to be sorted
            if ($first_column === true)
            {
                if ( $sortorder === 'asc' )
                {
                    $sorted_column = strnatcmp( $a[$col], $b[$col] );   
                }
                else
                {
                    $sorted_column = strnatcmp( $b[$col], $a[$col] );   
                }
                $first_column = false;     
            }                
            //If this column and previous column is identical, then sort on this column
            //(if it is not first column to be sorted)
            else if (!$sorted_column)
            {
                if ( $sortorder === 'asc' )
                {
                    $sorted_column = strnatcmp( $a[$col], $b[$col] );   
                }
                else
                {
                    $sorted_column = strnatcmp( $b[$col], $a[$col] );   
                }    
            }
        }                 
        
        return $sorted_column;
    }


    /**
     *  preserve_filter
     * 
     *  This function is used for filtering preservering original values
     * 
     *  @param    $search_cols              Which columns to search in
     *  @param    $original_headervalues    Original headervalues. Used for synchronizing indexes when having specific columns included in result
     *  @param    $include_cols             Which columns to include in filter
     *  @param    $freetext_search          Textstring from searchfield
     *  @param    $nrcols_table             Number of columns in table
     *  @param    $filter_col               Which column(s) that are used for filtering originally
     *  @param    $rv                       Row value (all columnvalues for a row)
     *  @param    $filter_removechars       What chars to remove when filtering
     * 
     *  @return   array                     Return array of which colums that are found true for filtering
     *                 
     */     
    private function preserve_filter( $search_cols, $original_headervalues, $include_cols, $freetext_search, $nrcols_table, $filter_col, $rv, $filter_removechars)
    {         
        if ( $include_cols === null )
        {
            $include_cols = "1-" . ( intval( $nrcols_table ) -1 );
        }

        //If search in columns is defined, use include cols to handle this
        if ( $search_cols !== null ) 
        {
            $include_cols = $search_cols;                       
        }
        
        //If include_cols is an array,make it a string
        if ( is_array( $include_cols ) !== false )
        {
            //Increase every item in include cols with 1 because we get indexes into
            //this function
            foreach( $include_cols as &$c)
            {
                $c++;
            }
            $include_cols = implode(",", $include_cols);    
        }
  
        $allcols_adj = $this->adjust_columns( $include_cols ); 
        $fcols_adj = $this->adjust_columns( $filter_col );
    
        //Down below are important to synchronize indexes with columns if specific
        //columns that are not incremented by 1
        $fcols_use = [];
        foreach($allcols_adj as $allcolsadj_key => $c)
        {
            $fcols_use[] = $original_headervalues[$c];
        }

        $fcols_use_indexes = [];
        foreach( $fcols_use as $fckey => $fcvalue )
        {
            foreach( $original_headervalues as $hkey=>$hkvalue )
            {
                if ( $fcvalue == $hkvalue ) 
                {
                    $fcols_use_indexes[] = $hkey;
                    break;
                }
            }
        }

        foreach( $fcols_use_indexes as $fkey => $fvalue )
        {
            if ( in_array ( $fvalue, $fcols_adj) !== false )
            {
                unset( $fcols_use_indexes[$fkey] );
            }
            else 
            {
                $fcols_use_indexes[$fkey]++;
            }
        }

        //Do the actual filtering and set filter_operator as null (which will default to wildcard)
        $cc_cols = implode( ",", $fcols_use_indexes ); //Format nr-column, nr-column, nr-column (e.g. 1,4,6)
      

        $filter = $this->is_row_applicable( $cc_cols, null, $freetext_search, $rv, $filter_removechars, "" );
        return $filter;
    }


    /**
     *  win1255_utf8encoding
     * 
     *  mb_convert_encoding does not support Windows1255 encoding by default
     *  so this user-defined function solves the issue. Thanks to stackoverflow (link below)
     *  
     *  @link      https://stackoverflow.com/questions/15593394/encoding-issues-windows-1255-to-utf-8
     *  @param    string  $given_item               Item to translate encoding from Windows-1255
     *  @return   string                            String in UTF-8 format
     *                 
     */ 
    public function win1255_utf8encoding( $given_item ) 
    {
        static $tbl = null;
        if (!$tbl) {
            $tbl = array_combine(range("\x80", "\xff"), array(
                "\xe2\x82\xac", "\xef\xbf\xbd", "\xe2\x80\x9a", "\xc6\x92",
                "\xe2\x80\x9e", "\xe2\x80\xa6", "\xe2\x80\xa0", "\xe2\x80\xa1",
                "\xcb\x86", "\xe2\x80\xb0", "\xef\xbf\xbd", "\xe2\x80\xb9",
                "\xef\xbf\xbd", "\xef\xbf\xbd", "\xef\xbf\xbd", "\xef\xbf\xbd",
                "\xef\xbf\xbd", "\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c",
                "\xe2\x80\x9d", "\xe2\x80\xa2", "\xe2\x80\x93", "\xe2\x80\x94",
                "\xcb\x9c", "\xe2\x84\xa2", "\xef\xbf\xbd", "\xe2\x80\xba",
                "\xef\xbf\xbd", "\xef\xbf\xbd", "\xef\xbf\xbd", "\xef\xbf\xbd",
                "\xc2\xa0", "\xc2\xa1", "\xc2\xa2", "\xc2\xa3", "\xe2\x82\xaa",
                "\xc2\xa5", "\xc2\xa6", "\xc2\xa7", "\xc2\xa8", "\xc2\xa9",
                "\xc3\x97", "\xc2\xab", "\xc2\xac", "\xc2\xad", "\xc2\xae",
                "\xc2\xaf", "\xc2\xb0", "\xc2\xb1", "\xc2\xb2", "\xc2\xb3",
                "\xc2\xb4", "\xc2\xb5", "\xc2\xb6", "\xc2\xb7", "\xc2\xb8",
                "\xc2\xb9", "\xc3\xb7", "\xc2\xbb", "\xc2\xbc", "\xc2\xbd",
                "\xc2\xbe", "\xc2\xbf", "\xd6\xb0", "\xd6\xb1", "\xd6\xb2",
                "\xd6\xb3", "\xd6\xb4", "\xd6\xb5", "\xd6\xb6", "\xd6\xb7",
                "\xd6\xb8", "\xd6\xb9", "\xef\xbf\xbd", "\xd6\xbb", "\xd6\xbc",
                "\xd6\xbd", "\xd6\xbe", "\xd6\xbf", "\xd7\x80", "\xd7\x81",
                "\xd7\x82", "\xd7\x83", "\xd7\xb0", "\xd7\xb1", "\xd7\xb2",
                "\xd7\xb3", "\xd7\xb4", "\xef\xbf\xbd", "\xef\xbf\xbd",
                "\xef\xbf\xbd", "\xef\xbf\xbd", "\xef\xbf\xbd", "\xef\xbf\xbd",
                "\xef\xbf\xbd", "\xd7\x90", "\xd7\x91", "\xd7\x92", "\xd7\x93",
                "\xd7\x94", "\xd7\x95", "\xd7\x96", "\xd7\x97", "\xd7\x98",
                "\xd7\x99", "\xd7\x9a", "\xd7\x9b", "\xd7\x9c", "\xd7\x9d",
                "\xd7\x9e", "\xd7\x9f", "\xd7\xa0", "\xd7\xa1", "\xd7\xa2",
                "\xd7\xa3", "\xd7\xa4", "\xd7\xa5", "\xd7\xa6", "\xd7\xa7",
                "\xd7\xa8", "\xd7\xa9", "\xd7\xaa", "\xef\xbf\xbd", "\xef\xbf\xbd",
                "\xe2\x80\x8e", "\xe2\x80\x8f", "\xef\xbf\xbd",
            ));
        }
        return strtr($given_item, $tbl);
    }


    /**
     *  convertarrayitem_encoding
     * 
     *  This function is used as a callback for walk_array and it changes
     *  characterencoding for each item in an array
     * 
     *  @param    array  $given_item           Arrayitem to translate encoding
     *  @return   N/A                          Change of arrayitem by reference
     *                 
     */  
    private function convertarrayitem_encoding( &$given_item ) 
    {       
        $encoding_to = $this->encoding_to;
        $encoding_from = $this->encoding_from;        
        
        
        $option_encoding = 0; //Only to encoding 
        if ( $encoding_from !== null && $encoding_to !== null ) 
        {
            $option_encoding = 1; //Both from and to encoding
        }
                         
        if ( $option_encoding === 1 )
        {
            if ( is_array($given_item) !== true ) 
            {
                //Windows1255 is not supported by builtin mb_convert_encoding, so special treatment
                if ( strtolower( $encoding_from )  === 'windows-1255' ) 
                {
                    $given_item = $this->win1255_utf8encoding( $given_item );
                }
                else 
                {
                    $given_item = @mb_convert_encoding($given_item, $encoding_to, $encoding_from);                  
                }
            }
        }
        else if ( $option_encoding === 0 )
        {
            if ( is_array($given_item) !== true )
            {
                //Windows1255 is not supported by builtin mb_convert_encoding, so no encoding
                //is done for this encoding  when only encoding TO is set
                if ( strtolower( $encoding_to )  === 'windows-1255' ) 
                {
                }
                else 
                {
                    $given_item = @mb_convert_encoding($given_item, $encoding_to); 
                            
                }
            }
        }
                    
    }


    /**
     *  is_row_applicable
     * 
     *  This function is to set apply filter based on user input
     * 
     *  @param    string    $filter_col             Which column(s) that should be used (starts with 1)
     *  @param    string    $filter_operator        Tells how the actual filter should the used
     *  @param    string    $filter_data            The actual filter
     *  @param    array     $row_values             Array of row values (cell values of table)     
     *  @param    string    $filter_remove          Remove char(s) from the actual cell content (if for example % is used, then remove that so only numbers are compared)     
     *  @return   bool      $apply_filter           Tells what 
     *                 
     */      
	private function is_row_applicable($filter_col,$filter_operator,$filter_data,$row_values,$filter_removechars='',$filter_criterias='')
	{        
        if ( $filter_data === "%userlogin%" ) 
        {
            $current_user = wp_get_current_user();
            $filter_data = str_replace( '%userlogin%', $current_user->user_login, $filter_data );          
        }
        else if ( $filter_data === "%userid%" )
        {
            $filter_data = str_replace( '%userid%', get_current_user_id(), $filter_data );              
        }

        $check_cols = $this->adjust_columns( $filter_col ); //several columns are allowed, e.g. 1, 3,5, 5- etc

        $found_in_columns = []; //Keep track if the row is appliplcable or not

        $fdata = explode(',', $filter_data);   

        //Make sure number of elements are the same
        //as number of element in filter_data array
        //but don't overwrite them in this filter operators 
        //if they exist
        foreach($fdata as $fdata_index => $filter_data)
        {  
            if ( !empty( $this->filter_operators) )
            {   
                if ( empty($this->filter_operators[$fdata_index]) )
                {
                    $this->filter_operators[$fdata_index] = 'equals';
                }
            }
        }
        
        foreach($fdata as $fdata_index => $filter_data)
        {            
            $apply_filter = false;
            $found_in_row = false;
            $found_cols = [];

            $filter_operator = $this->filter_operators[$fdata_index];    
            $filter_operator_index = $fdata_index;
 
            //Start checking in row            
            foreach($check_cols as $fc_index=>$filter_col)
            {   
                //Synchronize filter_index with filter_operator   
                //but only if user has more than one filter_data given            
                //(If more than one filter - it's multifilters)
                if ( mb_strlen ( $filter_criterias ) > 0 )
                {
                    if ( $fc_index != $filter_operator_index) 
                    {
                        continue;
                    }
                }        

                $rvalue = trim( str_replace( $filter_removechars, '', $row_values[$filter_col][1] ) );                                              
                switch ($filter_operator)
                {
                    case 'between':
                        $filter_data = explode( '-',$filter_data );
                        $f1 = str_replace( $filter_removechars, '', $filter_data[0] );
                        $f2 = str_replace( $filter_removechars, '', $filter_data[1] );
                        $apply_filter = $rvalue >= $f1 && $rvalue <= $f2;
                        break;
                        
                    case 'more':
                        $apply_filter = $rvalue > $filter_data;
                        break;
                        
                    case 'mequal':
                        $apply_filter = $rvalue >= $filter_data;
                        break;

                    case 'less':
                        $apply_filter = $rvalue < $filter_data;
                        break;
                        
                    case 'lequal':
                        $apply_filter = $rvalue <= $filter_data;
                        break;

                    case 'newdates';
                        //If filter data is set, use this as startdate, else
                        //use todays date
                        if (mb_strlen($filter_data)>0) {
                            $todays_date = $filter_data;
                        }
                        else 
                        {
                            $todays_date = date('Y-m-d'); 
                        }

                        $apply_filter = $rvalue >= $todays_date;
                        break;

                    case 'wildcard': 
                        //To get if filter exists inside row value
                        $af = stripos( $rvalue, $filter_data, 0 ); //Case insensitive match
                        $apply_filter = false;
                        if ( $af !== false) 
                        {
                            $apply_filter = true;
                        }
                        break;

                    case 'equalsurl':
                        //When fetching values from part of url (%urlparts-X), then spaces could become hyphens or underscores
                        //This settings checks all these combinations in a case insensitive manner
                        //(search case insensitive because webbservers could translate big letter to small and vice versa in urls)
                        if ( mb_strtolower($rvalue) == mb_strtolower( $filter_data )  
                            || mb_strtolower($rvalue) == str_replace( '-', ' ', mb_strtolower($filter_data) ) 
                            || mb_strtolower($rvalue) == mb_strtolower( str_replace('_', ' ', mb_strtolower($filter_data) ) ) )  
                        {
                            $apply_filter = true;
                        }
                        break;
                    
                    case 'equals_caseinsensitive':
                        //Equals case insensitive
                        $apply_filter = mb_strtolower($rvalue) == mb_strtolower($filter_data);
                        break;
                    case 'isempty':
                    case 'is_empty':
                        $apply_filter = mb_strlen( $rvalue ) == 0;
                        break;

                    case 'nequals':
                        $apply_filter = $rvalue != $filter_data;
                        break;

                    case 'equals':
                    default:                       
                        $apply_filter = $rvalue == $filter_data;
                        break;     
                }

                if ( $apply_filter == true)
                {
                    $found_in_row = true; //Found in this column ($filter_col) on this row
                    $found_cols[] = $filter_col;
                }
            }
            //End checking in row

            //This is required to check columns 
            //retrieved for invidual filter_data values
            if ( $found_in_row === true)
            {
                foreach($found_cols as $fc)
                {
                    $found_in_columns['fcols'][] = $fc;
                }                
            }
        }
        //END filter data array

        $apply_filter = false;
        if ( !empty( $found_in_columns) )
        {
            $apply_filter = true;
            if ( mb_strlen($filter_criterias) > 0 )
            {                
                $filter_criterias = mb_strtolower( $filter_criterias );
                $f_cols = explode(" or ", $filter_criterias);               
                                
                $apply_filters = [];
                foreach( $f_cols as $or_cols)
                {   
                    //Make sure user also can type names instead of numbers for columns
                    $or_cols2 = $this->name_to_colnr( $or_cols, $this->header_values ); 
                    $and_cols = explode( ",", $or_cols2 );                      
                    
                    $cnt = 0;
                    foreach( $and_cols as $c) 
                    {
                        //Columns minus 1 because user tells column, but
                        //programmatically we need index
                        if ( in_array( intval($c)-1, $found_in_columns['fcols'] ) !== false ) 
                        {
                            $cnt++;
                        }
                    }

                    //This are AND-values because there are more than one value
                    //in this arrayitem (1,6,7 instead of only 7)
                    if ( count( $and_cols ) > 1 )
                    {                           
                        //All values are equal 
                        //(e.g. 4 equals A AND 5 equals B 
                        //(if set filter_criterias="4,5" and filter_data="A,B")
                        //
                        if ( $cnt == count( $and_cols ) )
                        {
                            $apply_filters[] = true; //All values are equal
                        }
                    }
                    else 
                    {
                        //Only one value makes this arrayitem an OR-value
                        //this arrayitem (7 instead of 1,6,7)
                        //Any value in given columns?
                        if ( $cnt > 0 )
                        {
                            $apply_filters[] = true; 
                        }

                    }            
                }                
                //END foreach of filter_criterias

                //If any apply filters exists with value true, 
                //then apply filter should be true, otherwise false
                //(It's important to check true rather than false, because
                //if two values are for example true and then an "OR"-value is false)
                //apply filter should be true!)
                $apply_filter = false;
                if ( in_array( true, $apply_filters ) !== false )
                {
                    $apply_filter = true;
                }
            }
        }
  
        //Return all columns where values are found if filter should
        //be applied for this row
        if ( $apply_filter === true)
        {
            return $found_in_columns['fcols'];
        }

        return false;
        
	}
            

    /**
     *   get_defaults
     * 
     *  Helper function for setting default array-values
     *  It's public because of usage from CSV to HTML Premium.
     * 
     *  @param  void
     *  @return array defaultvalues for shortcode
     *                 
     */    
    public function get_defaults() {

        $defaults = array(
            'responsive' => 'yes',           //If set to no there won't be any class (responsive-html added and no css-rules would be applied for responsive table). If set to yes, there would basic settings of responsive tables.
            'css_max_width' => 760,         //breakpoints media-query
            'css_min_devicewidth' => 768,   //breakpoints media-query
            'css_max_devicewidth' => 1024,  //breakpoints media-query
            'header_backgroundcolor' => null, //Background color of header       
            'header_backgroundcolor_left' => null, //Background color of left header (when using fixed left column)     
            'header_textcolor' => null, //textcolor of header       
            'header_textcolor_left' => null, //textcolor of left header (when using fixed left column)                 
            'header_type' => '',            //header type. Can be set to sticky (header fixed on scroll) or fixed (requires a table height)
            'table_offset_header' => 0,           //Default top:0 (if using fixed or sticky header type)
            'table_height' => null,         //Table height. If header_type is set to fixed, this value must be set to a number (in pixels)
            'fixed_leftcol' => 'no',         //Fixed left column? yes/no
            'table_width' => null,          //Table width. 
            'html_id' > null,               //html id of table
            'html_class' => null,           //class(es) set for table (whole table)
            'title' => null, //if given then put titletext in top left corner
            'path' => '', //This is the base path AFTER the upload path of Wordpress (eg. /2016/03 = /wp-content/uploads/2016/03)
            'source_type' => 'visualizer_plugin', //So plugin knows HOW to fetch content from file(s)
            'source_files' => null, //Files are be divided with sources_separator (file1;file2 etc). It's also possible to include urls to csv files. It's also possible to use a wildcard (example *.csv) for fetching all files from specified path. This only works when fetching files directly from own server.
            'csv_delimiter' => ',', //Delimiter for csv - files (defaults to comma)
            'editable' => 'no', //Is file(s) in table editable? Only works when you are logged in!
            'selected_sheets' => null, //If fetching content from an excel-file, then define what sheet(s) you want by index or name (in format nr-nr or name(s), e.g. 1-3,glowsheet would return content from sheet 1,2,3 and the sheet named glowsheet).
            'fetch_lastheaders' => 0,   //If fetch_lastheaders=3 => (2012,2013,2014, if header_count = 2 => (2013,2014) etc
            'large_files' => 'no', //If set to yes, this would fetch row for row from file(s) instead of into an array. Less server memory, but takes a bit longer to load.
            'markdown_support' => 'no', //Use markdown (* = italic, ** = bold). Useful for easier formatting on specific word or similar.
            'exclude_cols' => null, //If you want to exclude some columns (eg. 1,4,9). Set to "last" if you want to remove last column.
            'hide_cols' => null,  //If you want to include columns but don't show them (could be useful when merging two columns together to a link with grabcontent_fromlink). If setting a column number, this number is based on the table after including/excluding columns.
            'include_cols' => null, //If you want to include these columns (only) use this option (eg. 1,4,9). If include_cols is given, then exclude_cols are ignored
            'include_rows' => null, //This will include only rows specified here in the following format (example 1,2,4 or 1-10,14,20-30) but further filtering on those rows is possible using the filter_data.
            'skip_headerrow' => 'no', //Set to yes if you don't want to include headers (headerrow)
            'headerrow_exists' => 'yes', //Set to no if there are no actual header row in file            
            'headerrows_start' => 1, //Which row in file that headers will be generated from
            'table_fixedlayout' => 'no', //Fixed layout calculates width of first row in table. Faster but not always applicaple
            'table_in_cell_cols' => null, //You can choose to have extra data in a table in a cell from specific columns given. exclude_cols is ignored if this is used. 
            'table_in_cell_header' => null, //Column name for added data, if table_in_cell_cols is specified and table_in_cell_header is not the default value is: "More Data"
            'table_in_cell_wrapperclass' => null, //Class for div surrounding table inside cell when using table_in_cell_cols
            'filter_data' => null, //what data to filter in table
            'filter_data_ucfirst' => 'no', //First letter uppercase (could be valuable when using %urlparts-%)
            'filter_operator' => 'equals', // possible filter_operators are: equals(default), nequals(not equal to), more, mequal(more or equal to), less, lequal (less or equal to), between (this requires hyphen '-' in filter_data),wildcard (anything that matches within string) and newdates (filter rows only on newer or same date as today)
            'filter_operators'=> null, //Same as filter_operator but indicates more values are possible. Combine filter operators by separating them with comma, e.g. equals,less which means that filter_col given is "equals" and second filter_col is "less".
            'filter_removechars' => '', //If having more characters than just numbers in the (cell)values, remove that/those characters so comparision will be done for numbers only            
            'filter_col' => null, //what column to use filter on
            'filter_cols' => null, //Filter columns (same as filter_col)
            'filter_criterias' => '', //and,or -logic when filtering: in format col,col,col or col,col (e.g. 1,6 or 2,9)
            'groupby_col' => null, //Group values by this column
            'groupby_col_header' => 'yes', //Set new header within the table for each grouping
            'eol_detection' => 'auto', //Use linefeed when using external files, Default auto = autodetect, CR/LF = Carriage return when using external files, CR = Carriage return, LF = Line feed
            'convert_encoding_from' => null, //If you want to convert character encoding from source. (use both from and to for best result) 
            'convert_encoding_to' => null, //If you want to convert character encoding from source. (use both from and to for best result)            
            'sort_cols' => null, //Which column(s) to sort on in format nr,nr och nr-nr (example 1,2,4 or 1-2,4)
            'sort_cols_order' => null, //Which order to sort columns on (asc/desc). If you have 3 columns, you can define these like asc,desc,asc
            'sort_cols_userclick' => 'no', //Sort_cols must be set. if this is set to you, user can click to sort a specific column. This overrides sort_cols_order after first click
            'sort_cols_userclick_arrows' => 'no', //Show arrows (yes) or not (no) as bacground in the header column. sort_cols_userclick must be set for this to work.
            'add_ext_auto' => 'yes', //If file is not included with .csv, then add .csv automatically if this value is yes. Otherwise, set no
            'float_divider' => '.', //If fetching float values from csv use this character to display "float-dividers" (default 6.4, 1.2 etc)
            'pagination' => 'no', //If pagination should be used
            'pagination_below_table' => 'yes', //Show pagination below table. Pagination must be set to yes for this to work.
            'pagination_above_table' => 'no', //Show pagination above table. Pagination must be set to yes for this to work.
            'pagination_start' => 1, //Row to start pagination with (generally always 1)            
            'pagination_text_start' => 'Start', //Text start for pagination. Set to "" if you do not want to show.
            'pagination_text_prev' => 'Prev', //Text Prev (previous) for pagination. Set to "" if you do not want to show.
            'pagination_text_next' => 'Next', //Text Next for pagination. Set to "" if you do not want to show.
            'pagination_text_last' => 'Last', //Text last for pagination. Set to "" if you do not want to show.
            'pagination_rows' => 10, //Only used when pagination is set to yes. Set to "" if you do not want to show.
            'pagination_links_max' => 10, //Show links (1,2,3... up to 10 links as default). Set to 0 if you do not want to show at all.
            'search_functionality' => 'no', //Show search input field to filter data dynamically
            'search_exactmatch' => 'no', //Make an exact match for search
            'search_cols' => null, //Search in all columns by default
            'search_caseinsensitive' => 'yes', //Make search case insensitive        
            'search_highlight' => 'no', //Show highlighted filtered or search
            'search_highlightcolor' => 'yellow', //Default color to show highlighted 
            'search_excludedrows' => 'no', //Search in excluded rows (e.g. if include_rows = "1-10" search in row 11-10000 would be searched on if those rows exists in table/file)
            'preservefilter_search' => 'no', //When searching keep original filter (filter when loaded)
            'hidetable_load' => 'no', //Hide table when page loads first time
            'hidetable_reset' => 'no', //Hide table when user click reset-button            
            'searchbutton_text' => 'Search', //Search button text (search_functionality must be set to yes for this option to be valid)
            'resetbutton_text' => 'Reset', //Reset button text  (search_functionality must be set to yes for this option to be valid)
            'searchinput_placeholder' => '', //Placeholder-text for search input field
            'notfound_message' => 'no', //What message to show when searchresult is not found
            'search_requiredchars' => 0, //Here you can specify how many characters at least user must type in before search is valid            
            'search_requiredchars_message' => '', //What message to show (tell user there are a required number of chars when doing a search)
            'search_realtime' => 'no', //Searches without hitting any specific button if set to yes
            'grabcontent_col_fromlink' => null, //fetch content(link) from a specific column and use this link as a wrapper for another columns content. This specifies the column to grab from
            'grabcontent_col_tolink' => null, //fetch content(link) from a specific column and use this link as a wrapper for another columns content. This is the column where to put the final link
            'grabcontent_col_tolink_addhttps' => 'yes', //Add https which column that expected link is grabbed from
            'htmltags_autoconvert' => 'no', //Convert links to html-links (<a>), images to <img> etc (when set to yes).
            'htmltags_autoconvert_newwindow' => 'no', //If ordinary links, open them up in a new window (target="_blank")
            'htmltags_autoconvert_imagealt' => '', //Set alt text based on a specific columns value (Or same text for all images if you just set some text here instead of a number)
            'htmltags_autoconvert_imagewidth' => null, //Width of converted images, can be set in px (default), %, vw, em, rem etc
            'totals_cols_bottom_countlines' => null, //total number of lines
            'totals_cols_bottom' => null, //Add totals with given columns at bottom of table (example 1,2,4 or 1-2,4)
            'totals_cols_bottom_empty' => '', //What string/character (maybe a zero?) to show when there's no calculation
            'totals_cols_prefix' => '', //Add prefix to the total column(s) (e.g. $10)
            'totals_cols_suffix' => '', //Add suffix to the total column(s) (e.g. 10$)
            'totals_cols_bottom_title' => null, //Set a specific string when added totals (overrides totals_cols_bottom_empty)
            'totals_cols_bottom_title_col' => null, //Which column to set this specific string            
            'total_percentage_above_table' => 'yes', //Show total percentage of a specific value above table
            'total_percentage_below_table' => 'no', //Show total percentage of a specific value below table
            'total_percentage_checkvalue' => null, //Check percentage of a specific value in a specific column. totals_pecentage_col must be specified.
            'total_percentage_col' => null, //Which column to check in. total_percentage_chechkvalue must be specified for this to work
            'total_percentage_text' => '', //Define what text to say when using total_percentage_checkvalue. If not defined it will only show percentage value followed by %
            'total_percentage_decimals' => 0, //Number of decimals to show when showing total percentage
            'downloadable' => 'no',  //If set to yes show a button to export values of the table as a csv-file
            'downloadable_text' => 'Download as csv', //Download text on button
            'downloadable_filename' => 'export_csvtohtml.csv', //What filename to show as when downloading
            'api_cdn' => 'yes', //Set this to no if you want to use wordpress api (could fail with cloudflare for unknown reason)
            'fetch_interval' => null, //Set to daily, hourly or weekly
            'json_startlevel' => 1, //When using json as source_type the plugin would fetch data from first level in json hiearchy
            'show_onlyloggedin' => 'no', //Show table only if any user is logged in
            'return_rows_array' => 'no', 
            'user_sort' => 'no', //Need internally for user sorting
            'doing_search' => 'no', //Used internally
            'return_found' => 'no',   
            'design_template' => 'outofthebox1', //Now used when no other template is set (default) 
            'debug_mode' => 'no'
        );

        return $defaults;
    }


    /**
     * design_template_validate
     * 
     * Validate if design_template is valid
     * 
     * @param string    $design_template                Check if this design template is valid
     * @param bool      $return_design_templates        If true, return valid design templates only
     * @return bool                                     string with classname if design_template is valid, else False
     * @return string                                     string with classname if design_template is valid, else False
     * @return array    $valid_design_templates         returns valid design templates
     */
    public function design_template_validate( $design_template = null, $return_design_templates = false ) 
    {
        if ( $design_template === null )
        {
            return false;
        }        

        $valid_design_templates = ['nolines', 'clean', 'funky', 'thick', 'pyjamas', 'pyjamascols', 'thinlines','outofthebox1'];
        if ( $return_design_templates === true ) 
        {
            sort($valid_design_templates);
            return $valid_design_templates;
        }

        if ( in_array($design_template, $valid_design_templates) !== false ) 
        {
           return 'csvtohtml-template-' . $design_template; //class to use for template
        }
        return false;
    } 


    /**
     * 
     * get_unit
     * 
     * Helper-function to check if a unit exists in a string (haystack)
     * 
     * @param   string   @haystack          What string to search in (e.g. 100px, 100vh, 100 etc)
     * @return  string   unit               If string does not contains any unit (listed in if-statement below), 'px' is returned. if it does contain any unit don't add anything (just return empty string)
     * 
     */
    private function get_unit( $haystack ) 
    {
        if ( 
           stristr( $haystack, 'px' ) == false 
           && stristr( $haystack, 'em' ) === false 
           && stristr( $haystack, 'rem' ) === false 
           && stristr( $haystack, '%') === false
           && stristr( $haystack, 'vh') === false
           && stristr( $haystack, 'vw') === false
        ) 
        {
            return "px"; 
        }
        return ""; 
    }


    /**
     * 
     * rowselect_yesno
     * 
     * Helper-function to display select yes/no html
     * 
     * @param   string   @row_title         Title of row
     * @param   string   @select_name       Actual name of form element (select)
     * @param   string   @check_var         What variable to check/compare
     *
     * @return  string   $html              html to return for select-list (with correct item selected)               
     * 
     */    
    private function rowselect_yesno( $row_title, $select_name, $check_var ) 
    {
        $html = '<tr><td>' . $row_title . '</td><td><select name="' . $select_name . '">';
        if ( $check_var === 'yes' ) 
        {
            $html .= '<option value="yes" selected>yes</option>';
            $html .= '<option value="no">no</option>';
        }
        else 
        {
            $html .= '<option value="yes">yes</option>';
            $html .= '<option value="no" selected>no</option>';
        }       
        $html .='</select></td></tr>';

        return $html;
    }


    /**
     * fetch_interval_validate
     * 
     * Validate if set fetch interval is valid
     * 
     * @param string    $interval                       What interval that is set by user in shortcode
     * @param bool      $return_validfetchintervals     If true, return valid fetchintervals only
     * @return bool                                     True if interval is valid, else False
     * @return array    $valid_intervals                returns valid fetchintervals
     */
    public function fetch_interval_validate( $interval = null, $return_validfetchintervals = false ) 
    {
        if ( $interval === null )
        {
            return false;
        }        

        $valid_intervals = ['daily', 'weekly', 'hourly', 'once'];
        if ( $return_validfetchintervals === true ) 
        {
            sort($valid_intervals);
            return $valid_intervals;
        }

        if ( in_array($interval, $valid_intervals) !== false ) 
        {
            return true;
        }
        return false;
    } 

    
    /**
     *   autoconvert_htmltags
     * 
     *  This functions converts a string into a valid html-tag string if applicapble
     *  (e.g. "Mail me at info@mail.com for further instructs" would converted into 
     *  Mail me at "<a href="mailto:info@mail.com">info@mail.com</a> for further instructions"
     *  but "wooden pie" would end up with "wooden pie".
     *  
     *  @param string $input_string             string to check
     *  @param string $new_window               open up in new window 
     *  @param string $alt                      alt-description for image 
     *  @param string $image_width              image width in units px (default),em,rem,%,vw
     *  @param string $search_highlight         keep track if search highlight is on (yes) or off
     *  @param string $search_highlightcolor    color if anything found when searching and search highlight is on  
     *  @return string $output_string           resultstring with applicable html tags
     *                 
     */            
    private function autoconvert_htmltags( $input_string, $new_window, $alt, $image_width, $search_highlight, $search_highlightcolor ) 
    {                    

        //We keep original string to be able to reuse for highlighting below
        $original_input_string = $input_string;

        //Remove all html-tags, offset from content
        $input_string = strip_tags( $input_string );

        //Turn string into words (separated by space) (If there for some reason are more than one word in a csv-column)
        $alt = strip_tags( $alt );
        $alt = str_replace(',', ' ', $alt);
        $alt = str_replace(' ', '***space***', $alt); //If alt contains spaces... temporary placeholders
        
        $word_list = explode ( ' ', $input_string );
        $new_wordlist = [];
        foreach ( $word_list as $word)
        {
            $new_word = $word;

            //Some kind of html-tag is probably already given for this string
            //Continue to next iteration
            if ( stristr( $word, '<' ) !== false && stristr( $word, '>' ) !== false )
            {
                continue;
            }

            //Probably an email
            if ( stristr( $word, '@') !== false && stristr( $word, '.') !== false )
            {
                $new_word = '<a href="mailto:' . $word . '">' . $word . '</a>';
            }

            //Probably a link
            //e.g www.domain.com should be <a href="http://www.domain.com">www.domain.com</a>
            else if ( 
                stristr ( $word, 'www.') !== false 
                || stristr( $word, 'http://') !== false
                || stristr( $word, 'https://') !== false
            )
            {
                $prefix = '';
                if ( stristr( $word, 'http://') === false &&  stristr( $word, 'https://') === false) 
                {
                    $prefix = '//';
                }

                //Probably not just an ordinary link but a link to an image
                if ( stristr ( $word, '.jpg') !== false
                || stristr( $word, '.jpeg') !== false 
                || stristr( $word, '.gif') !== false 
                || stristr( $word, '.png') !== false 
                || stristr( $word, '.webp') !== false ) 
                {
                    $alt = str_replace('***space***', ' ', $alt); //Restore "space" from temporary placeholders
                    $new_word = '<img src="' . $prefix . $word  . '" alt="' . $alt . '"';
                    if ( $image_width !== null )
                    {
                        $unit_width = $this->get_unit( $image_width );
                        $new_word .= ' style="width:' . $image_width . $unit_width . ';height:auto;"';
                    }
                    $new_word .= '>';
                }
                else {
                    //Probably an ordinary link
                    $nw = '';
                    if ( $new_window === 'yes' ) 
                    {
                        $nw = ' target="_blank"';
                    }
                    $new_word = '<a href="' . $prefix . $word  . '"' . $nw . '>' . $word . '</a>';
                }
                
            }   


            $new_wordlist[] = $new_word;
        }

        $output_string = implode(' ', $new_wordlist);

        //If search highlight is on, make the final output highlighted
        //Checking for span in original string is important (if not , then it will highlight everything all the time)!
        if ( $search_highlight === "yes" && stripos( $original_input_string, '<span') !== false )
        {
            $output_string = $this->make_highlighted( $output_string, $search_highlightcolor );
        }

        return $output_string;
    }


    /**
     *  convert_from_excel
     * 
     *  This function take an excel file (Open Document / xlsx only). It uses the spout-library
     *  to read excel files ( https://github.com/box/spout )
     * 
     *  and saves it as a csv-file (same filename but with csv as extension instead of xlsx)
     *  if csv already exists it just overwrites it, so latest data is always available
     *  Don't delete xlsx-file because the actual shortcode's source is the xlsx-file 
     *  (and this plugin don't want to modify content of page/post)
     * 
     *  @param  string $source                  source (path + file with xlsx extension)
     *  @param string $selected_sheets          what sheet(s) to grab from. If not set, then all content are fetched from all sheets
     *  @return string $file                    New source (path + file with csv extension (newly created csv))
     * 
     */
    private function convert_from_excel( $source, $csv_delimiter, $selected_sheets ) 
    {
        require_once 'spout-master/src/Spout/Autoloader/autoload.php';
        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open( $source );
 
        $excel_csv = '';

        //Create array that ends up with numeric indexes and names of sheets
        //(e.g. 1-5,7-9,sheet1, sheet3 would result in 1,2,3,4,5,7,8,9,sheet1,sheet3)
        if ( $selected_sheets !== null )
        {
            $selected_sheets = explode(',', $selected_sheets);
            foreach($selected_sheets as $key_ss=>$ss)
            {
                //If no letters are given, then set this as numeric indexes
                if( !preg_match("/[a-z]/i", $ss) )
                {       
                    $selected_sheets[$key_ss] = $this->adjust_columns( $ss );
                }            
            }
            
            $sheet_nrs = [];
            $sheet_names = [];
            foreach($selected_sheets as $inner_values)
            {
                if ( is_array($inner_values) )
                {
                    foreach($inner_values as $v)
                    {
                        $sheet_nrs[] = $v;
                    }
                }
                else 
                {
                    $sheet_names[] = $inner_values;
                }
            }

            $selected_sheets = array_merge($sheet_names, $sheet_nrs);
        }

        foreach ($reader->getSheetIterator() as $sheet) 
        {
            //selected_sheets comes from user input in shortcode
            //Go through selected-sheets array and compare if it 
            //it's equal to current sheet in this foreach sheet iterator
            $iterate_rows = true;  
            if ( $selected_sheets !== null )
            {
                $iterate_rows = false;  
                if ( !empty ( $selected_sheets) )
                {
                    foreach( $selected_sheets as $sht_value )
                    {                     
                        if ( gettype( $sht_value) == 'string')
                        {
                            if ( $sheet->getName() == $sht_value )
                            {
                                $iterate_rows = true;
                                break;
                            }
                        }
                        else if ( gettype( $sht_value) == 'integer')
                        {
                            if ( $sheet->getIndex() == $sht_value ) 
                            {
                                $iterate_rows = true;
                                break;
                            }
                        }
                    }
                }
            }
            
            if ( $iterate_rows === true ) 
            {
                foreach ($sheet->getRowIterator() as $row) 
                {       
                    $cells = $row->getCells();

                    $row_excel_csv = [];
                    foreach ($cells as $cell) 
                    {
                        $cv = $cell->getValue();
                        if ($cv instanceof DateTime) 
                        {
                            $cv = $cv->format('Y-m-d');
                        }

                        $row_excel_csv[] = $cv;  
                    }

                    $imploded_row = implode($csv_delimiter, $row_excel_csv);
                    $excel_csv .= $imploded_row . PHP_EOL;                
                }
            }
        }

        $new_source = str_replace('.xlsx', '.csv', $source);

        $f = fopen( $new_source , 'w');         
        fwrite($f, $excel_csv);
        fclose($f);

        $reader->close();

        return $new_source;
    }


    /**
     *   source_to_table
     * 
     *  This function creates a (html) table based on given source (csv) files
     * 
     *  @param  string $attr             shortcode attributes
     *  @return   string                      html-content
     *                 
     */    
    public function source_to_table( $attrs ) 
    {
        $defaults = $this->get_defaults();

        //Extract values from shortcode and if not set use defaults above
        $args = wp_parse_args( $attrs, $defaults );
        extract ( $args );

        //If header row doesn't exists, the header should not be shown
        if ( $headerrow_exists === "no" )
        {
            $skip_headerrow = "yes";    
        }        

        //This is necessary so no warnings when checking if local tablefile exists (below)
        if (isset($html_id)) 
        {
            $original_htmlid = $html_id;
        }
        else 
        {
            $original_htmlid = null;
        }


        $content_arr = null;

        //If local tablefile for this table exists, just return the content_arr for that table
        //if fetch interval is not reached (daily and fetched already for example)
        if ( $fetch_interval !== null && $original_htmlid !== null )
        {
            $upload_dir = wp_upload_dir();
            $upload_basedir = $upload_dir['basedir'];
            $fpath = $upload_basedir . "/{$this->tablestoragefolder}/{$original_htmlid}.csvhtml";

            //If this is the first time table is trying to be accessed no file will be created
            //Therefore this check has to be done                
            if ( file_exists ( $fpath) )
            {
                
                //Check fetch interval (daily is default)                
                $time_html = file_get_contents( $fpath ); //String separated by ***time***
                $exp_htmltime = explode('***time***', $time_html);
                $time = $exp_htmltime[0];                
                $current_time = time();

                switch ($fetch_interval) 
                {
                    case 'daily':
                    default:                        
                        $t1 = intval( date( 'd', $current_time ) );
                        $t2 = intval( date( 'd', $time) );
                        break;    

                    case 'hourly':                       
                        $t1 = intval( date( 'H', $current_time ) );
                        $t2 = intval( date( 'H', $time) );
                        break;    

                    case 'weekly':                       
                        $t1 = intval( date( 'W', $current_time ) );
                        $t2 = intval( date( 'W', $time) );
                        break;  

                    case 'once':
                        //"Fake" time so it would always fetch from local file 
                        //(it has fetched from source once before this local file was created)
                        $t1 = 0;
                        $t2 = 1;
                        break;
                }

                //Time have nas not changed based on fetch interval (daily => day has changed, hourly => hour has changed)
                //Then just return content_arr from file
                if ( !($t1 > $t2) )
                {
                    $content_arr = unserialize( $exp_htmltime[1] );   
                }
                
            }

        }

        //debug = alias for debug_mode
        if ( isset( $debug ) ) 
        {
            $debug_mode = $debug;
        }

        if ( $debug_mode === 'yes') 
        {
            require_once("debug.php");
            $debug_obj = new debug($args);
            if ($debug_obj === true) 
            {
                return '';                
            }
        }


        //Include class that are relevant for identifying header and row values from content
        //Include relevant after checking that source_type is valid        
        if ($this->valid_sourcetypes( $source_type) ) 
        {
            require_once("contentids/$source_type.php");                
        }
        else 
        {
            if ($debug_mode === 'yes') 
            {
                $debug_obj->show_msg('Invalid sourcetype given!');
                $debug_obj->show_msg('Valid sourcetypes are: ' . implode(',', $this->valid_sourcetypes($source_type, true)) );
            }
            
            return '';
        }
        				        
        $this->csv_delimit = $csv_delimiter; //Use this char as delimiter        
       
        //Using library for convertering markdown to html
        //@source: https://github.com/erusev/parsedown
        if ( $markdown_support === "yes" )
        {
            require_once 'parsedown-master/Parsedown.php';
            $parsedown = new Parsedown();
        }

        //Give siteuser ability to sort columns up/down with a click
        if ( $sort_cols_userclick === 'yes' )  
        {      
            //Add a space at the end of given class by user
            //so class for this functionality is not interfered with 
            //class(es) given by user
            if ( $html_class !== null )
            {
                if (mb_strlen($html_class)>0) 
                {
                    $html_class .= ' ';
                }
            }
            $html_class .= 'csvtohtml-sortable';

            //Show errors in header columns
            if ($sort_cols_userclick_arrows === 'yes')
            {
                $html_class .= ' arrows';    
            }
        }

        if ( $filter_data !== null )
        {                    
            //Get part of url to use as a filter data (e.g. siteurl/path1/path2/path3) and filter_data could be value of path3 (3)
            //%urlparts-X where X is the pathlevel. string "last" sets the last pathlevel (3 in this case)
            if (stristr($filter_data, '%urlparts-') && substr($filter_data,-1) == '%') 
            {            
                global $wp;
                $url_parts = explode( '/', $wp->request );            
                $cnt_parts = count( $url_parts );

                $fd1 = explode( '-', $filter_data );
                $fd2 = $fd1[1]; //number (or maybe string last) when %urlparts is set
                $fd2 = str_replace('%','', $fd2);
                
                //Set to the last urlpart
                if ($fd2 == 'last') 
                {                
                    $fd2 = $cnt_parts;
                }

                //In range of current url parts (sub1/sub2/sub3 would be range between 1 and 3)
                if ( intval($fd2) >= 1 && intval($fd2) <= $cnt_parts ) 
                {
                    $filter_data = $url_parts[$fd2-1];
                }

                //Need for pagination to work correctly! (Because pagination loops through $attrs-array)
                $attrs['filter_data'] = $filter_data; 
                            
            }
        }
        
        //Uppercase first letter?
        if ($filter_data_ucfirst === 'yes') 
        {
            $filter_data = ucfirst( $filter_data );
        }

        //Base upload path of uploads
        $upload_dir = wp_upload_dir();
        $upload_basedir = $upload_dir['basedir'];

        //If %userlogin% is specified somewhere in path, 
        //replace that with the actual username of logged in user
        $current_user = wp_get_current_user();
        $path = str_replace( '%userlogin%', $current_user->user_login, $path );
        $path = str_replace( "%userid%", get_current_user_id(), $path );

        //Useful for temporary testing. Content of this folder is 
        //removed for each update of this plugin
        if ( $path == '%temp%' )
        {
            $upload_basedir = WP_PLUGIN_DIR . '/csv-to-html';
            $path = 'examples';
        }

        //Usage if editable is applied
        $editable_files = [];

        //If content_arr is not found it's found from local file already (applicable if fetch_interval is set)
        if ( $content_arr === null )
        {

            //If user has put some wildcard in source_files then create a list of files
            //based on that wildcard in the folder that is specified    
            if ( stristr( $source_files, '*' ) !== false ) 
            {
                $files_path = glob( $upload_basedir . '/' . $path . '/'. $source_files);
                if ( $debug_mode === 'yes')
                {
                    $debug_obj->show_msg('Files grabbed from wildcard: ' . $upload_basedir . '/' . $path . '/'. $source_files . '<br><br>');
                }
                
                $source_files = '';
                foreach ($files_path as $filename) 
                {
                    if ( $debug_mode === 'yes' )
                    {
                        $debug_obj->show_msg( basename($filename) .  "<br>" );
                    }
                    $source_files .= basename($filename) . ';';
                }

                if ( strlen($source_files) > 0) 
                {
                    $source_files = substr($source_files,0,-1); //Remove last semicolon
                }
                else 
                {
                    if ( $debug_mode === 'yes')
                    {
                        $debug_obj->show_msg( __('Wildcard set for source-files but no source file(s) could be find in specified path.', 'csv-to-html') );
                    }
                }
                

            }

            //Find location of sources (if more then one source, user should divide them with 'sources_separator' (default semicolon) )
            //Example:  [stt_create path="2015/04" sources="bayern;badenwuertemberg"] 
            ///wp-content/uploads/2015/04/bayern.csv
            ///wp-content/uploads/2015/04/badenwuertemberg.csv        
            $sources = explode( ';', $source_files );
            
            //Create an array of ("csv content")
            $content_arr = array();
            
            foreach( $sources as $file_key => $s) 
            {
                //If file is excel, then set add_ext_auto to no
                //because else it would just add .csv as an extension after .xlsx
                if ( stristr( $s, '.xlsx') !== false) 
                {
                    $add_ext_auto = 'no';
                }

                //If $s(file) misses an extension add csv extension to filename(s)
                //if add extension auto is set to yes (yes is default)
                if (stristr($s, '.csv') === false && $add_ext_auto === 'yes')
                {
                    $file = $s . '.csv';
                }
                else {
                    $file = $s;
                }
            
                //Add array item with content from file(s)
            
                //If source file do not have http or https in it or if path is given, then it's a local file
                $local_file = true;

                //No file found when exploding source_files, go to next iteration of loop
                if (mb_strlen( $file ) == 0 ) {
                    continue;
                }
                
                if ( stristr($file, 'http') !== false || stristr($file, 'https') !== false )
                {
                    $local_file = false;
                }                    
                
                //Load external file and add it into array
                if ( $local_file === false ) 
                {         
                    $file_arr = false;
                                
                    //api_cdn if you for example use cloudflare (which seems to be an issue)
                    if ( $source_type === 'json') 
                    {
                        if ( $api_cdn === 'yes' )
                        {
                            //Get stream from API (wp_remote_get does not seem to work with API's using CloudFlare)               
                            //Add user agent to tell it's not    
                            $context = stream_context_create(
                                array(
                                    "http" => array(
                                        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                                    )
                                )
                            );

                            //If response returns any string move on
                            $wp_response = file_get_contents($file, false, $context);
                            if (strlen( $wp_response ) > 0)
                            {
                                $ret_code = 200;
                                $ret_message = 'Success';
                            }
                        }
                        else 
                        {
                            $wp_response = wp_remote_get($file);
                            $ret_code = wp_remote_retrieve_response_code( $wp_response );
                            $ret_message = wp_remote_retrieve_response_message( $wp_response );                                                
                        }

                        $content_arr = array_values( json_decode( $wp_response, true ) );

                    }
                    else 
                    {
                        //Ordinary use of fetching data - Wordpress API
                        $wp_response = wp_remote_get($file);
                        $ret_code = wp_remote_retrieve_response_code( $wp_response );
                        $ret_message = wp_remote_retrieve_response_message( $wp_response );
                    }

                    if ( $debug_mode === 'yes' )
                    {          
                        $debug_msg = '';          
                        $debug_msg .= __('<pre>', 'csv-to-html'); 
                        $debug_msg .= __('File', 'csv-to-html') . ':' . $file . '<br>';
                        $debug_msg .= __('Return code','csv-to-html') . ': ' . $ret_code . '<br>';
                        $debug_msg .= __('Return message','csv-to-html') . ': ' . $ret_message . '<br>';                        
                        $debug_msg .= __('</pre>','csv-to-html');
                        $debug_obj->show_msg( $debug_msg );
                    }

                    //200 OK               
                    if ( $ret_code === 200)
                    {
                        $body_data = wp_remote_retrieve_body( $wp_response );                        

                        //What end of line to use when handling file(s)
                        switch (strtolower( $eol_detection ) ) 
                        {
                            case 'auto':
                                $use_eol = $this->detect_eol ( $body_data ); 
                                break;
                            case 'lf':
                                $use_eol = "\n";
                            case 'cr':
                                $use_eol = "\r";
                                break;
                            case 'cr/lf':
                                $use_eol = "\r\n";
                                break;
                            default:
                                $use_eol = $this->default_eol;
                        }

                        //Explode array with selected end of line
                        $file_arr = explode( $use_eol, $body_data);

                        //remove last item from array
                        //if using visualizer-plugin format
                        if ( $source_type === 'visualizer_plugin') 
                        {
                            $x = count ( $file_arr ) - 1;
                            unset ( $file_arr[$x] );
                        }
                    }
                    else
                    {
                        if ( $debug_mode === 'yes') 
                        {
                            $debug_obj->show_msg( $file . ' ' . __('not found','csv-to-html') );
                        }
                    }

                    //try to fetch file with file() (fetching file as an array)
                    if ( $file_arr === false ) 
                    {                
                        $file_arr = @file ( $file );
                        if ( !is_array( $file_arr ) ) 
                        {
                            $file_arr = false;
                        }
                    }                
                    
                    //Put an array with content into this array item
                    //(but only if  array has been created from file/url)
                    if ( $file_arr !== false ) 
                    {
                        //Put an array with csv content into this array item                    
                        $content_arr[] = array_map(function($v){return str_getcsv($v, $this->csv_delimit);}, $file_arr);   
                    }
                    else {
                        if ( $debug_mode === 'yes') 
                        {
                            $debug_obj->show_msg( __('No success fetching content from ','csv-to-html') . $file );
                        }   
                        
                    }
                }
                
                //Load local file into content array
                if ( $local_file === true ) 
                {
                    
                    if ( strlen( $path ) > 0 ) 
                    {
                        $file = $upload_basedir . '/' . $path . '/' . $file; //File from uploads folder and path
                    }
                    else 
                    {
                        $file = $upload_basedir . '/' . $file; //File directly from root upload folder
                    }
                    
                    if ( file_exists( $file ) ) 
                    {   
                        //This is an Excel-file, convert it to csv before moving on
                        if ( stristr( $file, '.xlsx') !== false) 
                        {                           
                            $file = $this->convert_from_excel( $file, $csv_delimiter, $selected_sheets );
                        }

                        if ( $large_files !== "yes" )
                        {                        
                            //Arrayfilter means remove unneccasry empty lines, file_ignore_new_liens removes \r\n etc
                            $arr_from_file = array_filter( file( $file, FILE_IGNORE_NEW_LINES ) );       
    
                            //Create array for displaying rows and editable-files for 
                            //editing (if editable is set to yes, store it anyway)
                            foreach( $arr_from_file as $item)
                            {
                                $array_from_csvstring = str_getcsv($item, $this->csv_delimit);
                                $content_arr[$file_key][] = $array_from_csvstring;
                                $editable_files[$file_key] = [$s, count($content_arr[$file_key]), $file];
                            }                        
                        }                                                  
                        else 
                        {
                            //large_files = "yes"
                            //Somewhat slower than above, but more memory effecient
                            //Better handling of larger files (>=1MB)
                            $f = fopen($file, "r");
                            while(($array_from_csvstring = fgetcsv($f, null, $this->csv_delimit)) !== false)
                            {
                                $content_arr[$file_key][] = $array_from_csvstring;
                                $editable_files[$file_key] = [$s, count($content_arr[$file_key]), $file];
                            }
                            fclose($f);                        
                        }
                    }
                    else if ( $debug_mode === 'yes' ) 
                    {
                        $debug_obj->show_msg( $file . ' ' . __('not found','csv-to-html') );
                    }
                }
            }        
                    
            if ( count ( $content_arr) === 0 && $debug_mode === 'yes') 
            {
                $debug_obj->show_msg( __('No files found','csv-to-html') );
                return;
            }
        
        } 

        //If editable is set to yes, user is logged and local file is true
        //then remove attributes that are not applicable, but tell plugin
        //editable input fields should be active.
        if ( $editable === 'yes' && is_user_logged_in() && $local_file === true )
        {
            $fetch_lastheaders = 0;
            $exclude_cols = null;
            $include_cols = null;
            $table_in_cell_cols = null;
            $table_in_cell_header = null;
            $table_in_cell_wrapperclass = null;
            $filter_data = null;
            $filter_col = null;
            $sort_cols = null;
            $sort_cols_order = null; 
            $totals_cols_bottom = null; 
            $totallines_cols_bottom = null;
            $groupby_col = null;
            $htmltags_autoconvert = 'no';
        }
        else 
        {
            
            if ( $editable === 'yes' && $debug_mode === 'yes' ) 
            {
                $debug_obj->show_msg('Editable is set to yes, but user must be logged in and this only applies to local files');
                $debug_obj->show_msg('Editable is therefore set to no.');
            }

            $editable = 'no';
        }

        //Create the object used for fetching
        $this->title = $title; //Used with guessonecol
        $this->headerrow_exists = $headerrow_exists;

        $obj = $this->object_fromsourcetype( $source_type, $json_startlevel );        
                
        //Fetch row and headers from objects created above
        $header_values = array();
        $row_values = array();

         //Nr of items from end of array
        //If not set=0, then $cutarr_fromend would be 0 = last index)
        $cutarr_fromend = -1 * abs( (int)$fetch_lastheaders );
                
        //Cut array from end is set if fetch_lastheaders is sent
        //..but first check if any actual content is given. If not just return nothing
        if ( $content_arr === null) 
        {
            return;
        }
        
        if ( count ( $content_arr ) == 0 ) 
        {
            return;
        }

        //Fetch content and make it "viewable" into a table        
        $values_from_obj = $obj->fetch_content( $content_arr, $headerrows_start, $cutarr_fromend);
        $header_values = $values_from_obj['header_values'];
        $this->header_values = array_slice( $header_values, 0 );
        $original_headervalues = array_slice($header_values,0); //Used for setting correct column for user sorting when rendering table
        $org_headers = array_flip($original_headervalues); //Flip keys and values so it's easy to get actual index later on

        //If having column as name, convert it into column-nr. e.g. filter_col="age,gender" could be turned into filter_col="4,3"
        $change = false;
        $column_keys = ["search_cols", "htmltags_autoconvert_imagealt", "filter_col", "include_cols","exclude_cols", "hide_cols","grabcontent_col_fromlink", "grabcontent_col_tolink","sort_cols","totals_cols_bottom","groupby_col","table_in_cell_cols"];        

        if ( $this->org_altvalues === null)
        {
            $this->org_altvalues = explode( ",", $htmltags_autoconvert_imagealt );            
            $attrs['org_altvalues'] = implode(",", $this->org_altvalues);
        }        

        foreach( $attrs as $key=>$attr_value )
        {
            if ( in_array($key, $column_keys) !== false ) 
            {
                $attrs[$key] = $this->name_to_colnr( $attr_value, $header_values );
                $change = true;
            }
        }
        if ( $change === true )
        {
            $args = wp_parse_args( $attrs );
            extract ( $args );
        }   

        
        if ( stristr($source_files, 'http') !== false )
        {
            $local_file = false; //This is required to not raise any warning later on
            $editable = "no"; //It's not possible to edit external files directly.
        }    
        

        //filter_operator is here for backward compability
        if ( $filter_operators === null)
        {
            $filter_operators = $filter_operator;
        }
        else
        {
            $filter_operator = $filter_operators;
        }

        //filter_col is here for backward compability
        if ( $filter_cols === null)
        {
            $filter_cols = $filter_col;
        }
        else
        {
            $filter_col = $filter_cols;
        }        

        $this->filter_operators = explode(",", $filter_operators);

        $hide_cols = $this->adjust_columns( $hide_cols );
        
        $row_values = $values_from_obj['row_values'];
        $all_rowvalues = array_slice( $row_values, 0); //Is used for editing

        //Show percentage of a specific value in specific column
        //Could for example be used for successful rate based on number of "yes" in a column
        $html_percentage = '';
        if ( $total_percentage_checkvalue !== null && $total_percentage_col !== null)
        {
     
            $row_count = count($row_values);
            $perc_value = 0;
            foreach($row_values as $row) {
                $index_col = $total_percentage_col-1;
                $value_column = $row[$index_col][1];            
                if ($value_column == $total_percentage_checkvalue) {
                    $perc_value++;
                }
            }
            $percentage  = ($perc_value/$row_count)*100;
            $percentage = number_format($percentage, $total_percentage_decimals);

            $html_percentage .= '<span class="perc_text">'.$total_percentage_text .'</span><span class="perc">'. $percentage . '%</span>';
            $html_percentage .= '</div>';
        }

        
        //If encoding is specified, then encode entire array to specified characterset
        if ( $convert_encoding_from !== null || $convert_encoding_to !== null )
        {
            if ( $debug_mode === 'yes' )
            {
                $result_encoding = $debug_obj->check_encoding( $convert_encoding_from, $convert_encoding_to );
                if ($result_encoding === true) 
                {
                    return;
                }
            }
            
            $this->encoding_from = $convert_encoding_from;
            $this->encoding_to = $convert_encoding_to;        
            array_walk_recursive($header_values, array($this, 'convertarrayitem_encoding') );
            array_walk_recursive($row_values, array($this, 'convertarrayitem_encoding') );
        }


        //Recreate rows so rows with only the filtered data is used for filtering rows
        //(remove other rows from rows array). If pagination this option is forced for expected output
        $includerows_remove_nonfiltered = 'no';

        if ( $pagination === 'yes') {
            $includerows_remove_nonfiltered = 'yes';
        }

        if ($includerows_remove_nonfiltered === 'yes' && $filter_data !== null)
        {
            $rvalues = array_slice( $row_values , 0 );
            $row_values = [];
            foreach( $rvalues as $rkey => $rv ) 
            {
                if ( $filter_col !== null && $filter_data !== null )
                {                            
                    if ($this->is_row_applicable( $filter_col, $filter_operator, $filter_data, $rv, $filter_removechars, $filter_criterias))
                    {					
                        $row_values[] = $rv;
                    }                    
                }
            }               
        }
        //...end recreate rows


        //If pagination is set, then set 1 - nr of pagination rows as default
        //If pagination_start is set then start at that row.
        if ( $pagination === 'yes' ) 
        {
            if ( isset($_GET['pagination_start']) )
            {
                if ( intval($_GET['pagination_start']) > 0) 
                {
                    $pagination_start = $_GET['pagination_start'];
                }
            }

            $rowcount_table = count( $row_values ); //For usage when showing pagination links

            if ( $pagination_start > 0 )
            {
                $start_row = $pagination_start;               
                $include_rows = "$start_row-" . ($start_row + $pagination_rows - 1);    
                $pagination_start = $start_row + $pagination_rows;
            }
            else 
            {
                $include_rows = "1-" . $pagination_rows;
                $pagination_start = $pagination_rows + 1;
            }
        }


        //Sort by specific column(s) in format: 1,2,4 or 2-4
        if ( $sort_cols !== null)
        {                     
            //Create new array in a "sort-friendly format"
            $new_arr = array();
            $index = 0;
            $cnt_headers = count($header_values);
            foreach( $row_values as $r )
            {
                for ($c=0;$c<$cnt_headers;$c++) 
                {
                    $new_arr[$index][$c] = $r[$c][1]; //Column $c, value
                }
                
                $index++;
            }
            
            //Do the sorting    
            $this->sorting_on_columns = $this->adjust_columns( $sort_cols );    

            $sort_cols_order_arr = array();
            if ( $sort_cols_order === null )
            {                
                $so = 'asc';
                foreach($this->sorting_on_columns as $key => $soc)
                {
                    $sort_cols_order_arr[$key] = $so;
                }
            }
            else 
            {
                //Set unique sortorders for each column
                $sort_cols_order_arr = explode(',',$sort_cols_order);
            }

            foreach( $this->sorting_on_columns as $key => &$soc )
            {
                $so = 'asc';
                if (isset($sort_cols_order_arr[$key])) 
                {
                    $so = $sort_cols_order_arr[$key];
                }
                
                $soc = array(
                            $this->sorting_on_columns[$key],
                            $so
                        );                
            }            
            usort($new_arr, array( $this, 'custom_sort_columns') );
            
            //Put values from the orded array $new_arr into $row_values
            $index = 0;
            foreach($row_values as &$r)
            {
                for ($c=0;$c<$cnt_headers;$c++) 
                {
                    $r[$c][1] = $new_arr[$index][$c]; 
                }
                
                $index++;
            }
        }

        //Group by specific column
        //Basically create a temporary array that has column values as keys
        //and then put it together to old row_values array
        if ( $groupby_col !== null)
        {
            $groups_arr = [];        
            $groupby_column = $groupby_col-1; //Index-based 0

            //Create temporary array to group values where key is set as value from the grouped by column
            foreach($row_values as $row) 
            {
                //Value of category
                $groupedby_value = $row[$groupby_column][1];
                if ( !isset($groups_arr[$groupedby_value]) )
                {
                    $groups_arr[$groupedby_value] = [];
                }

                $groups_arr[$groupedby_value][] = array_slice($row,0);
            }
   
            //...then simply create new array based on temporary array 
            //(so things will get in correct order)
            $row_values = [];
            foreach($groups_arr as $key=>$inner_arr)
            {
                if ( $groupby_col_header === 'yes') //Show a header for each grouped key
                {                  
                    $row_groupedby_header = [];
                    $first_col = true;
                    foreach($inner_arr[0] as $kv=>$value) //$inner_arr is basically the first item, so we know number of cols
                    {
                        if ($first_col === true) 
                        {
                            $row_groupedby_header[] = ['','<span class="groupheader">' . $key . '</span>'];
                            $first_col = false;
                        }
                        else 
                        {
                            $row_groupedby_header[] = ['', ''];
                        }                        
                    }
                    $row_values[] = array_slice($row_groupedby_header,0);
                }

                foreach($inner_arr as $value)
                {
                    $row_values[] = array_slice($value,0);    
                }
            }
        }
                
        //If not specifically include columns given, then use all columns in table
        if ($table_in_cell_cols !== null ) 
        {
            if ( $include_cols === null ) 
            {
                $include_cols = "1-" . count($header_values); //all columns
            }            
            $additional_headervalues = array();
            $additional_rowvalues = array();
        }
		
		// if include_rows is specified it has to happen before include_columns (due to filter_data)
		if( $include_rows !== null )
		{
			$include_rows = explode (',',$include_rows);
			$include_rows_index = 0;
			$temp_rowvalues = array();
            $cnt_rowvalues = count( $row_values );

			for( $i=0; $i<$cnt_rowvalues; $i++ )
			{		
                $act_row_index = $i+1;
                if ( isset( $include_rows[$include_rows_index] ) ) 
                {
                    $include_rows_next_data = strpos($include_rows[$include_rows_index],'-');
                }
                else {
                    $include_rows_next_data = 0;
                }

				if ( $include_rows_next_data>0 ) //if include_rows given in format xxx-yyy
				{
					$inner_include_rows = explode('-',$include_rows[$include_rows_index]);	
					if( $act_row_index == $inner_include_rows[0] ) //finding the first row which is to be processed
					{
						while( ($i+1) <= $inner_include_rows[1] ) //processing grouped rows
						{
                            if ( isset( $row_values[$i]) ) 
                            {
                                $temp_rowvalues[] = $row_values[$i];
                            }
							$i++;
						}
						$include_rows_index++; //proceeding to the next include row element
					}
				}
				else // include_rows given as a number
				{   
                    if ( isset($include_rows[$include_rows_index]) ) 
                    {                   
                        if ( $act_row_index == $include_rows[$include_rows_index] ) //finding the row which is to be processed
                        {
                            if ( isset( $row_values[$i]) ) 
                            {
                                $temp_rowvalues[] = $row_values[$i];
                            }
                            $include_rows_index++; //proceeding to the next include row element
                        }
                    }
				}
			}
			$row_values = $temp_rowvalues;
		}

        //Include columns (only) ?        
        if ($include_cols !== null) 
        {
            if ( $exclude_cols !== null && $debug_mode === 'yes' ) 
            {
                $debug_obj->show_msg( __('Exclude cols is ignored when using include cols or table in cells attribute', 'csv-to-html') );
            }  

            $include_cols = $this->adjust_columns( $include_cols );           
			
			//extract additional data
			if ($table_in_cell_cols !== null)
			{
				$table_in_cell_cols = $this->adjust_columns( $table_in_cell_cols );
				 //Recreate header_values
            	
				foreach ( $table_in_cell_cols as $c) {
					if (isset ( $header_values[$c]) ) {
						$additional_headervalues[$c] = $header_values[$c];
					}
					else if ( $debug_mode === 'yes') 
					{
						$debug_obj->show_msg( __('Column for inclusion does not exist', 'csv-to-html') );									            
					}                
				}
				$nr = 0;
				foreach( $row_values as $key=>$rv ) 
				{         
					//Checking if filter data by row data is set
					if ($filter_col !== null)
					{
                        $filtered = $filtered2 = $this->is_row_applicable( $filter_col, $filter_operator, $filter_data, $rv, $filter_removechars, $filter_criterias );                                           
                        
                        //If preservefilter_search at search is set, then include columns 
                        //that is not included in original filtering
                        if ( $doing_search === "yes" && $preservefilter_search === "yes" )
                        {
                            $freetext_search = $_POST['search'];                    
                            $filtered2 = $this->preserve_filter( $search_cols, $original_headervalues, $include_cols, $freetext_search, $nr_col, $filter_col, $rv, $filter_removechars);                    
                        }

                        if ( !empty( $filtered ) && !empty( $filtered2 ) )
                        {
                            //Here we have a row that is found (based on criteria given from is_row_applicable())           
                            if ( $search_highlight === "yes" )
                            {
                                foreach($filtered as $col) {
                                    $rv[$col][1] = $this->make_highlighted( $rv[$col][1], $search_highlightcolor );                        
                                }
                            }
                        }
                        else
                        {
                            $nr_row++;
                            continue;
                        }
					}
					foreach($table_in_cell_cols as $ic) 
					{
						if ( isset( $rv[$ic])) 
						{
							$additional_rowvalues[$nr][] = $rv[$ic];
						}
						else if ( $debug_mode === 'yes') 
						{												
							$debug_obj->show_msg( __('Column for inclusion does not exist','csv-to-html') );
						}
					}      
					$nr++;
				}
			}
			
            //Recreate header_values
            $new_headervalues = array();
            foreach ( $include_cols as $c) {
                if (isset ( $header_values[$c]) ) {
                    $new_headervalues[$c] = $header_values[$c];
                }
                else if ( $debug_mode === 'yes') 
                {
                    $debug_obj->show_msg( __('Column for inclusion does not exist', 'csv-to-html') );									            
                }                
            }
            //If table_in_cell_cols is specified adding the last header column
            if ($table_in_cell_cols !== null)
            {
                if ($table_in_cell_header == null)
                {
                    $table_in_cell_header =  __('More Data','csv-to-html');
                }
                $new_headervalues[] = $table_in_cell_header;
            }
			
            $header_values = array();
            foreach($new_headervalues as $nhv) 
            {
                $header_values[]= $nhv;
            }
            if ( $debug_mode === 'yes') 
            {
                $debug_obj->show_msg ( $header_values );
            }            
            
            //Recreate row values (with appropiate columns)
            $new_rowvalues = array();

            //Add column values into new array from scratch
            //Go through include columns (indexes) for every row and
            //add item to the new array
            $nr = 0;
            $nr_row = 0;
            foreach( $row_values as $key=>$rv ) 
            {           
                //Checking if filter data by row data
                if ($filter_col !== null)
                {
                    $filtered = $filtered2 = $this->is_row_applicable( $filter_col, $filter_operator, $filter_data, $rv, $filter_removechars, $filter_criterias );                                           
                    //If preservefilter_search at search is set, then include columns 
                    //that is not included in original filtering
                    if ( $doing_search === "yes" && $preservefilter_search === "yes" )
                    {
                        $freetext_search = $_POST['search'];                    
                        $filtered2 = $this->preserve_filter( $search_cols, $original_headervalues, $include_cols, $freetext_search, $nr_col, $filter_col, $rv, $filter_removechars);                    
                    }                    
                
                    if ( !empty( $filtered ) && !empty( $filtered2 ) )
                    {
                        //Here we have a row that is found (based on criteria given from is_row_applicable())           
                        if ( $search_highlight === "yes" )
                        {
                            foreach($filtered as $col) {
                                $rv[$col][1] = $this->make_highlighted( $rv[$col][1], $search_highlightcolor );                        
                            }
                        }                        
                    }
                    else 
                    {
                        $nr_row++;
                        continue;
                    }
                }
                
                foreach($include_cols as $ic) 
                {
                    if ( isset( $rv[$ic])) 
                    {
                        $new_rowvalues[$nr][] = $rv[$ic];
                    }
                    else if ( $debug_mode === 'yes') 
                    {												
                    	$debug_obj->show_msg( __('Column for inclusion does not exist','csv-to-html') );
                    }
                }          

                //If table_in_cell_cols is specified populating cells in last column
                if ($table_in_cell_cols !== null)
                {			
                    $new_rowvalues[$nr][][1] = "";
                }
                $nr++;
           }  
           $row_values = array();

           foreach($new_rowvalues as $nrv) 
           {
               $row_values[]= $nrv;
           }
            
           if ( $debug_mode === 'yes') 
           {
                $debug_obj->show_msg ( $row_values );
           }
            
		
        }
        //Exclude columns? (if include_cols is set, this attribute is ignored)
        else if ( $exclude_cols !== null ) 
        {
            //Remove last column?
            if (stristr($exclude_cols, 'last') !== false ) 
            {
                $last_col = count ( $row_values[0] );                  
                $exclude_cols = str_replace('last', $last_col, $exclude_cols );
            }
            
            //remove given column(s)
            $remove_cols = $this->adjust_columns( $exclude_cols );

            //Remove header values
            foreach($remove_cols as $rc) 
            {
                unset( $header_values[$rc] );                
            }
            
             //Remove column values
             //Go through each row and for each row
             //remove (unset) the index set by remove_cols above
             foreach( $row_values as $key=>$rv ) 
             {  
                foreach($remove_cols as $rc) 
                {
                    unset ( $row_values[$key][$rc] );
                }             
             }
        }
        
        //If title given, set this title in left top corner of htmltable
        if ( isset($title) && isset($header_values[0])) 
        {
            $header_values[0] = sanitize_text_field( $title );
        }
        
        //Useful for debugging csv content
        if ( $debug_mode === 'yes') 
        {
            if (!isset($row_values[0][0][0]) ) 
            {
                if ( $exclude_cols === null)  //If excluding columns this would be inconsiscent, therefore not a real error
                { 
                    $debug_obj->show_msg( __('Inconsicensty with arrays created from csv', 'csv-to-html') );
                }
            }

            $debug_obj->show_msg ( $header_values );         
            
            if ( !isset($header_values[1]) )
            {
                $debug_msg = '';
                $debug_msg .= __('You only get an output of one column. This is probably incorrect.<br>','csv-to-html');
                $debug_msg .= __('Try setting csv_delimiter to something else (comma is default) in your shortcode if this is a true statement (else ignore this message).','csv-to-html');
                $debug_obj->show_msg ( $debug_msg );
            }
						
		    $debug_obj->show_msg ( $row_values );            
        }

        if ( $return_rows_array === 'yes' ) 
        {
            return $row_values;
        }
        
        if ( $show_onlyloggedin === 'yes' )
        {
            if ( !is_user_logged_in() )
            {
                return '';
            }
        }

        //Create table
        if ( isset($html_id) ) 
        {
            $htmlid_setbyuser = true;
            $htmlid_set = 'id="' .  $html_id . '" '; 
        }
        else 
        {
            $htmlid_set = '';
            $html_id = ''; //Important so no warnings arise with undefined variable
            $htmlid_setbyuser = false;
        }
                

        if ( isset($html_class) ) 
        {
            $html_class = ' ' . $html_class;
            if ( $responsive === 'yes' )
            {
                $html_class .= ' responsive-csvtohtml';
            }
        }
        else 
        {
            $html_class = '';
            if ( $responsive === 'yes' )
            {
                $html_class = ' responsive-csvtohtml';
            }
        }
        
        $html = '';
        $class_template = '';
        
        //If choosing one design template, the plugin basically just adds a html class
        //this html class resides in a css-file templates5.css
        if ( $design_template !== null ) 
        {   
            $class_template = $this->design_template_validate($design_template);

            if ( $class_template !== false ) 
            {
                if (!isset($html_class)) 
                {
                    $html_class = '';
                }

                $html_class .= ' ' . $class_template;
            }
        }

        //Responsive table(s)?
        //Then add "title" when lower resolutions (e.g. smartphones)
        if ( $responsive === 'yes' ) 
        {
            /*
            default values:
            'css_max_width' => 760,
            'css_min_devicewidth' => 768,
            'css_max_devicewidth' => 1024,
            */
            if (intval($css_max_width) == 0 ) 
            {
                $css_max_width = 760;
            }
            if (intval($css_min_devicewidth) == 0 ) 
            {
                $css_min_devicewidth = 768;
            }
            if (intval($css_max_devicewidth) == 0 )
            {
                $css_max_devicewidth = 1024;
            }

            //If having more than one table on a page
            //then each has to have an id to separate td-before-content below (responsive)
            //It's also used even if responsive is set to no
            //The unique_id() is based on time so it can never be exactly the same id
            if (empty($html_id)) 
            {
                $html_id = uniqid('csvtohtml_id-');
                $htmlid_set = 'id="' .  $html_id . '" ';                 
            }
        }



        //Create a page wrapper (for reloading content correctly including pagination which is
        //outside of the table). This wrapper is used if search-functionality is used to.
        if ( $pagination === 'yes' || $search_functionality === 'yes' || $sort_cols_userclick === 'yes' )
        {
            $htmlid_set_wrapper = 'id="' .  'wrapper-' . $html_id . '" class="' . ltrim($class_template) . '" ';
            $html .= '<div ' . $htmlid_set_wrapper . ' style="position:relative;">';
        }

        //Show percentage total above table
        if ( $total_percentage_checkvalue !== null && $total_percentage_col !== null && $total_percentage_above_table === 'yes') 
        {
            $html .= '<div class="csvhtml-percentage above">';
            $html .= $html_percentage;
        }

        $file_key = 0;
        $file_row = -1;
        
        if ( $pagination === 'yes') 
        {        
            //html pagination                                   
            $html_pagination = '';            
            if ( $editable === 'yes' ) 
            {                
                //Array which purpose is to keep track of key and row
                //This way the file_key and file_row could be identified by each 
                //"paginationblock", e.g. 0-9, 10-20, 21-30 etc
                //So $file_key_row[10] would be pagination_start at 10,
                //$file_key_row[20] would be pagination start at 20 etc        
                $file_key_row = []; 
                $pagination_blockstart = intval($pagination_start - $pagination_rows) - 1;

                //Go through ALL row values (invisible or not)
                foreach( $all_rowvalues as $m_rowkey => $rv ) 
                {            
                    $file_key_row[] = ['filekey' => $file_key, 'filerow' => $file_row];

                    $edit_filename = $editable_files[$file_key][0];
                    $edit_fullfilename = $editable_files[$file_key][2];
                    $nrrows_filename = intval($editable_files[$file_key][1]);
                    if ( $file_key == 0) {
                        $nrrows_filename-=1;
                    }
                  
                    //If having several files, indicate that it is file based on file_key here
                    if (intval($file_row) == intval($nrrows_filename)-1) { 
                        $file_row = 0;
                        $file_key++;
                        $edit_filename = $editable_files[$file_key][0];
                        $edit_fullfilename = $editable_files[$file_key][2];
                        $nrrows_filename = intval($editable_files[$file_key][1]);            
                    }
                    else {
                        $file_row++;
                    }

                    $nr_row++;                
                }  

                //File row and file key is start of pagination block
                //Set these to make filerow and filekey start at correct correct posistion
                //in pagination
                //e.g. $file_key_row[10] would be pagination_start at 10,
                //$file_key_row[20] would be pagination start at 20 etc                  
                $file_key = $file_key_row[$pagination_blockstart]['filekey'];
                $file_row = $file_key_row[$pagination_blockstart]['filerow'];
              
            }
                 
            if ( ($pagination_start > $pagination_rows) && ($pagination_start - $pagination_rows) > 1 ) 
            {
                //First link?              
                $html_pagination .= '<a data-htmlid="' . $html_id . '" data-pagination="1" class="first" href="?pagination_start=1">' . $pagination_text_start . '</a>';
            }

            //Previous link and pagination links
            if ( $pagination_start > ($pagination_rows * 2) ) 
            {
                $prev_start = $pagination_start - ($pagination_rows * 2);                
                $html_pagination .= '<a data-htmlid="' . $html_id . '" data-pagination="' . $prev_start . '" class="prev" href="?pagination_start=' . $prev_start . '">' . $pagination_text_prev . '</a>';

                //Links specific interval?
                if ( intval( $pagination_links_max ) > 0 )
                {
                    $nr_links = ceil( $rowcount_table / $pagination_rows );
                
                    $sp_prev = ceil( $prev_start / $pagination_rows ) + 1; 
                    $sp_prev_last = ceil( $sp_prev + $pagination_links_max );

                    $html_pagination .= '<span class"pagination_links">';
                    for( $i=$sp_prev; $i<$sp_prev_last; $i++ ) 
                    {
                        $sp = ($i * $pagination_rows) + 1;
                        //Don't show if larger than number of rows
                        if ( $sp > ( $rowcount_table - ($pagination_rows * $pagination_links_max) ) ) 
                        {
                            break;
                        }
                        $html_pagination .= '<a data-htmlid="' . $html_id . '" data-pagination="' . $sp . '" href="?pagination_start=' . $sp . '">' . ($i+1) . '</a> ';
                    }
                    $html_pagination .= '</span>';

                }    
                
               
            }

            if ( $pagination_start < $rowcount_table)
            {
                $html_pagination .= '<a data-htmlid="' . $html_id . '" data-pagination="' . ($pagination_start) . '" class="next" href="?pagination_start=' . ($pagination_start)  . '">' . $pagination_text_next . '</a>';     
                $html_pagination .= '<a data-htmlid="' . $html_id . '" data-pagination="' . (2 + $rowcount_table - $pagination_rows) . '" class="last" href="?pagination_start=' . (2+ $rowcount_table - $pagination_rows) . '">' . $pagination_text_last . '</a>';
            }
        
            //Show pagination links above table?
            if ( $pagination_above_table === 'yes' )
            {
                $html .= '<div class="csvhtml-pagination above">';        
                $html .= $html_pagination;            
                $html .= '</div>';        
            }    

        }

        //search field?
        if ( $search_functionality === 'yes' )
        {
            $html .= '<div class="csv-search">';
            $html .= '<form action="" method="post">';            
            $html .= '<input data-htmlid="' . $html_id . '" class="search-text" type="text" value="" name="frmSearch" placeholder="' . $searchinput_placeholder . '">';
            $html .= '<input data-htmlid="' . $html_id . '" class="search-submit" type="button" name="frmSubmit" value="' . $searchbutton_text . '">';
            $html .= '<input data-htmlid="' . $html_id . '" class="reset-submit" type="button" name="frmResetSubmit" value="' . $resetbutton_text . '">';
            $html .= '</form>';
            $html .= '</div>';            
        }

        //This result_message is set when no searchresult is found
        if ( isset( $result_message ) && isset ( $found_search ) )
        {                       
            if ( $found_search == 0) 
            { 
                $hidetable_load = 'yes';
                $html .= '<span class="message">' . $result_message . '</span>';
            }
        }


        //When using sticky, table height can't be set        
        $height_css = '';
        $width_css = '';
        $style_css = '';
        $endstyle_css = '';
        $overflow_css = '';
        $fixed_rowcols = false;

        //Fixed header on scroll (sticky) or fixed header (fixed) or fixed left col        
        if ( $header_type === "sticky" || $header_type === "fixed" || $fixed_leftcol === "yes") 
        {
            $fixed_rowcols = true;

            if ( $header_backgroundcolor === null) 
            {
                $header_backgroundcolor = '#fff'; //Default backgroundcolor for header
            }  
        

            if ( $header_backgroundcolor_left === null) 
            {
                $header_backgroundcolor_left = '#fff'; //Default backgroundcolor for header left col
            }  

            //If header-type set to sticky, then no height is needed            
            if ( $header_type === "sticky")
            {
                $table_height = null;
            }

            //When using header fixed, then overflow:auto is used 
            //(overflow doesn't work 100% with sticky)
            //with and height should be set
            if ( $header_type === "fixed" || ($fixed_leftcol === "yes" && $header_type === "") )
            {
                $overflow_css = 'overflow: auto !important;';

                if ( $table_height !== null ) 
                {
                    $unit_height = $this->get_unit( $table_height );        
                    $height_css  = 'height:' . $table_height . $unit_height . ';';
                    $style_css = ' style="';
                    $endstyle_css = ';"';
                }
            }

            //if horisontal scroll should be done directly, then width must 
            //be wider than it's parent's container
            if ( $table_width !== null )
            {           
                $unit_width = $this->get_unit( $table_width );            
                $width_css = 'width:' . $table_width . $unit_width . ' !important;';
                $style_css = ' style="';
                $endstyle_css = ';"';
            }

            $html .= '<div class="csvtohtml-tablescroll"' . $style_css . $overflow_css . $height_css . $width_css . $endstyle_css . '>';
        }
        else 
        {            
            //No header type set (not fixed or sticky). Ability to set width of table (better to do this in css of theme but it's possible to do this way)
            //Fixed height of table should not be used at all.
            if ( $table_width !== null )
            {                                        
                $style_css = ' style="';
                $unit_width = $this->get_unit( $table_width );            
                $width_css = 'width:' . $table_width . $unit_width . ' !important';                 
                $endstyle_css = ';"';
            }            
        }

        
        if ( ($header_type === "fixed" && $fixed_leftcol === "yes") || ($fixed_leftcol === "yes" && $header_type === "") ) 
        {
            $style_css .= 'table-layout:unset;'; //This is need for fixed left col and width to work (then table layout cannot be fixed)
        }

        if ( $table_fixedlayout === 'yes') 
        {
            if ( $html_class === null )  
            {
                $html_class = 'table-fixedlayout';
            }
            else 
            {
                $html_class .= ' table-fixedlayout';
            }
        }

        $html .= '<table ' . $htmlid_set . 'class="csvtohtml' . $html_class . '"' . $style_css . $height_css . $width_css . $endstyle_css . '>';
        
        //If skip header is set to yes, header will not be included in html
        if ( $skip_headerrow === "no" )
        {
            $html .= '<thead>';
                    
            //Hide table at first pageload (when doing search/pagination etc table will show)
            $hide_row_class = '';
            if ( $hidetable_load === 'yes') 
            {
            $hide_row_class = ' trhide';
            }

            //Take for granted HTML5 is used. Then this below is ok
            $nr_col = 1;
            $html .= '<style>';
            if ( $header_type === "sticky" )
            {
                //Need when sticky, else sticky won't work on left/top column sticky table
                //(Yes, this is a weird workaround but this is because of overflow can't be set on any element in html)
                //unset means it's set to initial or inherited.
                $html .= '* {overflow:unset !important;}'; 
            }
            
            if ( $responsive === "yes")
            {
                $html .= '@media 
                only screen and (max-width: ' . $css_max_width . 'px),
                (min-device-width: ' . $css_min_devicewidth . 'px) and (max-device-width: ' . $css_max_devicewidth . 'px)  {';
                $n = 1;       
                foreach( $header_values as $hvkey => $hv) 
                {      
                    $html .= 'table#' . $html_id . '.csvtohtml.responsive-csvtohtml .td:nth-child(' . ($n) . '):before { content: "' . $hv . '"; }';
                    $n++;
                }
                $html .= '}';
            }

            $html .= '</style>';     
            
            if ( empty( $editable_files ) )
            {
                $editable_files[0][0] = '';
            }
            //[0][0] is always used because it's header (first file is always on first row regarding        
            $html .= '<tr data-source="' . $editable_files[0][0] . '" class="headers' . $hide_row_class .  '">';
            
            $unit_offsetheader = $this->get_unit( $table_offset_header );
            
            //Header values where colname is the key               
            $header_values_flipped = array_flip ( $header_values ); 
            $tablecell_hidecolumn_class = []; 
            $colindexes = []; //Needed later on for grabcontent and where order of columns has changed

            foreach( $header_values as $header_key=>$hv) 
            {
                if ( $source_type === 'guessonecol' && $header_key !== 0)
                {
                    continue;                        
                }       

                //Style on header-elements on top row
                $hb_style = ' ';
                $hb_style_start = ' style="';
                $hb_style_end = '"';
                if ( $header_backgroundcolor !== null )
                {                
                    $hb_style .= 'background-color:' . $header_backgroundcolor . ';';
                }

                if ( $header_textcolor !== null )
                {
                    $hb_style .= 'color:' . $header_textcolor . ';';
                }

                
                $hb_style .= 'top:' . $table_offset_header . $unit_offsetheader . ';';
        

                //If fixed left column is no, then first th (left) on top row sets left position to auto (instead of 0)
                if ( $fixed_leftcol === "no" && $nr_col == 1 )
                {
                    $hb_style .= 'left:auto;';
                }            
                            
                $current_sortorder = '';
                if ( $user_sort === "yes" ) //One column
                {
                    if ( intval($sort_cols)-1 == $org_headers[$hv] ) 
                    {
                        $current_sortorder = $sort_cols_order . ' ';
                    }
                }
            
                //Hide specific column? (Column still included in result but invisible for user)            
                $tablecell_hidecolumn_class[$header_key] = '';
                foreach( $hide_cols as $iteration=>$hc )
                {                            
                    //No column found, go to next iteration
                    if ( empty( $original_headervalues[$hc] ) ) 
                    {
                        continue;
                    }

                    //Get correct index based on columnname or column value
                    $colname_fromindex = $original_headervalues[$hc];
                    $check_index = $header_values_flipped[$colname_fromindex];            

                    //If found hidden column (when loop is fetching that column)
                    //then put value in key indicating which column that should be invisible
                    if ( $header_values[$check_index] == $header_values[$header_key] )
                    {
                        $tablecell_hidecolumn_class[$header_key] = 'hide-column ';   
                    }
                }
            
                if ( !isset( $org_headers[$hv]) )
                {
                    $org_headers[$hv] = "";
                }

                $html .= '<th data-colindex=' . $org_headers[$hv] . ' class="' . $tablecell_hidecolumn_class[$header_key] . $current_sortorder . ' td colset colset-' . $nr_col . '"' . $hb_style_start . $hb_style . $hb_style_end . '>';
                $colindexes[] = $org_headers[$hv];

                if ( $editable === 'yes') 
                {                
                    $html .= '<input type="text" class="savecell" data-delimiter="' . $csv_delimiter . '" data-csvfile="' . $editable_files[0][2] . '" data-source="' . $editable_files[0][0] . '" value="' . $hv . '">';
                }        
                else {
                    $html .= $hv;
                }

                $html .=  '</th>';
                $nr_col++;
            }

            $html .= '</tr></thead>';
        }
        //END If skip header is set to yes, header will not be included in html
                
        $html .= '<tbody>';
        
        $nr_row = 1;
        $pyj_class = 'odd';

        //Add totals at bottom of table?
        if ( $totals_cols_bottom !== null )
        {
            $use_cols = $this->adjust_columns( $totals_cols_bottom );

            $row_sum = [];
            $cnt_rowvalues = count ( $row_values );
                       
            //If totals_cols_bottom_countlines is defined and set to yes, 
            //then count lines else don't
            $countlines = false;
            if ( $totals_cols_bottom_countlines !== null )
            {                            
                if ( $totals_cols_bottom_countlines === "yes" )
                {
                    $countlines = true;
                }
            }

            for($i=0;$i<$nr_col-1;$i++) 
            {
                if ( in_array( $i, $use_cols) !== false ) 
                {
                    //Get total sum per column
                    for($j=0; $j<$cnt_rowvalues; $j++) 
                    {
                        $v = $row_values[$j][$i][1]; //Get value for this cell

                        //If set to number of lines, count number of lines and put at botttom                        
                        //in the column that is set in totals_cols_bottom
                        if ( $countlines === true )
                        {
                            $row_sum[$i][] = 1;
                        }
                        else
                        {
                            //Get rid of non numeric values of string given (in this cell) 
                            //and thereafter add the sum to the array $row_sum
                            $numberonly_str = preg_replace("/[^0-9\s]/", "", $v);
                            $row_sum[$i][] = $numberonly_str;
                        }
                    }               
                    
                    //Add row with totals to the bottom of table 
                    //If suffix or prefix (or both) is given then print out those as well
                    if ( !empty( $row_sum[$i] )) 
                    {      
                        $row_values[$cnt_rowvalues][$i] = array('',  $totals_cols_prefix . array_sum($row_sum[$i]) . $totals_cols_suffix ); 
                    }
                }
                else 
                {
                    //Show empty totals (not even zeros)
                    $row_values[$cnt_rowvalues][$i] = array('',   $totals_cols_bottom_empty );
                }
                
                //If title is set (and titlecol. If not titlecol is set it's set at first column)
                if ( $totals_cols_bottom_title !== null ) 
                {
                    $title_col = 0;
                    if ($totals_cols_bottom_title_col !== null ) 
                    {
                        if ( $totals_cols_bottom_title_col > 0 ) 
                        {
                            $title_col = intval($totals_cols_bottom_title_col) - 1;
                        }                        
                    }

                    if ( $title_col === $i ) 
                    {
                        $row_values[$cnt_rowvalues][$i] = array('',  $totals_cols_bottom_title );
                    }
                }

            }
        }
       
        $csv_file = $csv_delimiter . implode($csv_delimiter, $header_values);
        $csv_file .= '<br>';

        $htmltags_autoconvert_imagealt_original = $htmltags_autoconvert_imagealt;

        //Go through visible row values
        $found_rows = 0;             
        foreach( $row_values as $m_rowkey => $rv ) 
        {
            if ( $editable === 'yes' )
            {  
                $edit_filename = $editable_files[$file_key][0];
                $edit_fullfilename = $editable_files[$file_key][2];
                $nrrows_filename = intval($editable_files[$file_key][1]);
                if ( $file_key == 0) {
                    $nrrows_filename-=1;
                }
            
                //If having several files, indicate that it is file based on file_key here
                if (intval($file_row) == intval($nrrows_filename)-1) { 
                    $file_row = 0;
                    $file_key++;
                    $edit_filename = $editable_files[$file_key][0];
                    $edit_fullfilename = $editable_files[$file_key][2];
                    $nrrows_filename = intval($editable_files[$file_key][1]);            
                }
                else {
                    $file_row++;
                }
            }
            else {
                //So no warnings are given when values of these variables are applied
                //to data attributes in table
                $edit_filename = '';
                $edit_fullfilename = '';                
            }

            if ($include_cols === null && $filter_col !== null)
            {   
                $filtered = $filtered2 = $this->is_row_applicable( $filter_col, $filter_operator, $filter_data, $rv, $filter_removechars, $filter_criterias );                                           

                //If preservefilter_search at search is set, then include columns 
                //that is not included in original filtering
                if ( $doing_search === "yes" && $preservefilter_search === "yes" )
                {
                    $freetext_search = $_POST['search'];                    
                    $filtered2 = $this->preserve_filter( $search_cols, $original_headervalues, $include_cols, $freetext_search, $nr_col, $filter_col, $rv, $filter_removechars);                    
                }
                
                if ( !empty( $filtered ) && !empty( $filtered2 ) )
                {
                    $found_rows++;
                    //Here we have a row that is found (based on criteria given from is_row_applicable())           
                    if ( $search_highlight === "yes" )
                    {
                        foreach($filtered as $col) {
                            $rv[$col][1] = $this->make_highlighted( $rv[$col][1], $search_highlightcolor );                        
                        }
                    }
                }
                else 
                {
					$nr_row++;
					continue;
                }
            }

            //If grouper header is set, add groupheading class to tablerow
            if ( !empty($rv[0]) )
            {
                if ( strpos($rv[0][0], '<span class="groupheader">') !== null )  
                {
                    if ( mb_strlen($rv[0][0]) == 0 )
                    {                        
                        $pyj_class .= ' groupheading';
                    }
                }
            }

            $html .= '<tr data-source="' . $edit_filename . '" class="rowset '. $pyj_class . ' rowset-' .$nr_row . $hide_row_class .  '">';    
            if ( $pyj_class === 'odd') 
            {
                $pyj_class = 'even';
            }
            else 
            {
                $pyj_class = 'odd';
            }

            $nr_col = 1;
            $toEnd = count($rv);

            //Handling of image-alt at conversion     
            if ( mb_strlen( $htmltags_autoconvert_imagealt  ) > 0 )
            {              
                $exp_alt = explode( ",", $htmltags_autoconvert_imagealt_original  );
                foreach( $exp_alt as $alt_key => $alt_value )
                {   
                    //Check if altvalue exists in headers
                    $found_header = false;
                    foreach($original_headervalues as $oh_key=>$oh)
                    {
                        if ( $alt_value == $oh) 
                        {
                            $found_header = true;
                        }
                    }

                    //If not found in header, 0 indicates "free text"
                    //else grab from specific column (1,2,3,4 etc)
                    if ( $found_header === false )
                    {
                        if ( intval( $alt_value )  == 0 )
                        {                                                    
                            $exp_alt[$alt_key] = $this->org_altvalues[$alt_key];
                        }      
                        else 
                        {
                            //Grab from column
                            if ( !empty( $rv[$alt_value-1]) )
                            {
                                $exp_alt[$alt_key] = $rv[$alt_value-1][1];
                            }
                        }  
                    }           
                }

                if ( is_array( $exp_alt ) )
                {
                    $htmltags_autoconvert_imagealt_arr = array_slice($exp_alt,0);
                    $htmltags_autoconvert_imagealt = implode(",",$htmltags_autoconvert_imagealt_arr); 
                }
            }                       
            
            foreach ( $rv as $m_colkey => $inner_value) 
            {  
                $tdh = 'td';
                if ( $fixed_leftcol === 'yes' && intval( $m_colkey) == 0)
                {
                    $tdh = 'th style="';

                    if ( $header_backgroundcolor_left !== null )
                    {
                        $tdh .= 'background-color:' . $header_backgroundcolor_left . ';';
                    }
                    if ( $header_textcolor_left !== null )
                    {
                        $tdh .= 'color:' . $header_textcolor_left . ';';
                    }
                    $tdh .= '"';
                }           

                //Display other float divider (e.g. 6,3 instead 6.2)
                if ( $float_divider != '.' ) 
                {
                    $inner_value[1] = str_replace( '.', $float_divider, $inner_value[1] );
                }

                //Markdown usage
                if ( $markdown_support === "yes" )
                {
                    $inner_value[1] = $parsedown->line( $inner_value[1] );
                }

                //Check here for potential strings that could and should be
                //converted to html (e.g. www.dn.se to <a href="https://www.dn.se">www.dn.se</a>)
                if ( $htmltags_autoconvert === 'yes')
                {       
                    $inner_value[1] = $this->autoconvert_htmltags( $inner_value[1], $htmltags_autoconvert_newwindow, $htmltags_autoconvert_imagealt, $htmltags_autoconvert_imagewidth, $search_highlight, $search_highlightcolor );     
                }

                //Grab content from column
                if ($grabcontent_col_fromlink !== null && $grabcontent_col_tolink !== null) 
                {
                    $content_from_col = $grabcontent_col_fromlink-1;
                    $this_col = $grabcontent_col_tolink-1;
                    
                    //Get content from column [$content_from_col]
                    //if we are on specified column
                    //(Can't just get m_colkey index because order of columns may have changed)
                    //$colindexes is created when header row is created
                    if ( intval ( $this_col ) == intval( $colindexes[$m_colkey] ) )
                    {
                        //Reindex so we get correct value based on column's name
                        $colname_from = $original_headervalues[$content_from_col]; //Name of columns and with keys index-based
                        $grab_content_from = $header_values_flipped[$colname_from];
                
                        if ( $rv[$grab_content_from][1] !== null)
                        {                           
                            $grabbed_content = $rv[$grab_content_from][1];
                            
                            if ( mb_strlen( $grabbed_content) > 0 ) //This might be empty if using headers for grouping
                            {
                                if ( $grabcontent_col_tolink_addhttps === "yes" )
                                {
                                    if ( stripos($grabbed_content,'https://') === false ) 
                                        {
                                            $grabbed_content = 'https://' . $grabbed_content;
                                        }        
                                }
                                $inner_value[1] = '<a href="' .  $grabbed_content . '">' . $inner_value[1]  . '</a>';
                            }
                        }
                    }                 
                }                

                //If table_in_cell_cols is specified adding the last header column and additional popuptable
                if ($table_in_cell_cols !== null && 0 === --$toEnd)
                {
                    $html .= '<td class="colset colset-' . $nr_col . '">';
                    if ( $table_in_cell_wrapperclass === null )
                    {
                        $table_in_cell_wrapperclass = 'extra-data';
                    }

                    $html .= '<div class="' . $table_in_cell_wrapperclass . '">';
                    $html .= '<table class="add-table"><thead><tr class="add-headers' . $hide_row_class . '">';
                    $nr_acol = 1;
                    foreach($additional_headervalues as $ahv)
                    {
                        $html .= '<th class="add-colset add-colset-' . $nr_acol . '">' . $ahv . '</th>';
                        $csv_file .= $csv_delimiter . $ahv;
                        $nr_acol++;
                    }
                    
                    $html .= '</tr></thead><tbody>';			
                    $html .= '<tr class="add-rowset add-rowset-' .$nr_row . $hide_row_class . '">'; 
                    $nr_acol = 1;
                    if (isset( $additional_rowvalues[$nr_row-1]) ) {

                        if ( is_array( $additional_rowvalues[$nr_row-1] ) )
                        {
                            foreach($additional_rowvalues[$nr_row-1] as $inner_avalue)
                            {
                                $html .= '<td class="add-colset add-colset-' . $nr_acol . '">' . $inner_avalue[1]  . '</td>';
                                $csv_file .= $csv_delimiter . $inner_avalue[1];
                                $nr_acol++;
                            }
                        }

                    }

                    $html .= '</tr>';


                    $html .= '</tbody></table>';
                    $html .= '</div></td>'; 
                }
                else
                {          
                    if (!isset( $inner_value[1]) ) 
                    {
                        $inner_value[1] = '';
                    }
                    if ( $source_type === 'guessonecol' )
                    {
                        if ( $nr_col == 1 ) 
                        {
                            $html .= '<'. $tdh . ' data-editable="' . $editable . '" class="' . $tablecell_hidecolumn_class[$m_colkey] . 'td colset colset-' . $nr_col . '">';
                        }
                    }
                    else 
                    {
                        $html .= '<'. $tdh . ' data-editable="' . $editable . '" class="' . $tablecell_hidecolumn_class[$m_colkey] . 'td colset colset-' . $nr_col . '">';
                    }

                    if ( $editable === 'yes') 
                    {
                        $html .= '<input type="text" class="savecell" data-delimiter="' . $csv_delimiter . '" data-filerow="' . $file_row . '" data-csvfile="' . $edit_fullfilename . '" data-source="' . $edit_filename . '" value="' . $inner_value[1] . '">';
                    }
                    else {
                        $html .= $inner_value[1];
                    }
                    $html .= '</' . $tdh . '>';
                    $csv_file .= $csv_delimiter . $inner_value[1];

                }
            
                $nr_col++;
            }
            $html .= '</tr>';
            $nr_row++;
            $csv_file .= '<br>';           
        


        }

        if ( $return_found === "yes")
        {            
            return $found_rows;
        }

        
        $html .= '</tbody></table>';


        if ( $fixed_rowcols === true )
        {
            $html .= '</div>'; //table scroll
        }

        //If table is downloadable as a csv-file
        if ($downloadable === 'yes') 
        {
            //Make an array of concatenated string and then
            //put them in a form to able to send to another file for 
            //download functionality of the csv
            $exploded_csv = explode('<br>', $csv_file);
            foreach($exploded_csv as $key => $ecsv) 
            {
                $exploded_csv[$key] = substr($ecsv, 1);
            }
            unset($exploded_csv[count($exploded_csv)-1]);

            //Create actual form with delimiter and "rows of csv".
            $html .= '<form action="' . plugins_url( '/' , __FILE__) . 'export_csv.php" method="POST">';
            $html .= '<input type="hidden" name="filename" value="' . $downloadable_filename . '">';
            $html .= '<input type="hidden" name="delimiter" value="' . $csv_delimiter . '">';

            foreach($exploded_csv as $csv_item) 
            {
                $html .= '<input type="hidden" name="itemdata[]" value="' . $csv_item . '">';
            }
           
            $html .= '<input type="submit" value="' . $downloadable_text . '">';
            $html .= '</form>';
        }


        //Pagination under table?
        if ( $pagination === 'yes' && $pagination_below_table === 'yes' )
        {
            $html .= '<div class="csvhtml-pagination below">';    
            $html .= $html_pagination;            
            $html .= '</div>';        
        }  

        //Show percentage total under table
        if ( $total_percentage_checkvalue !== null && $total_percentage_col !== null && $total_percentage_below_table === 'yes') 
        {
            $html .= '<div class="csvhtml-percentage below">';
            $html .= $html_percentage;
        }

                
        if ( $pagination === 'yes' || $search_functionality === 'yes' || $editable === 'yes' || $fixed_leftcol === "yes" || $sort_cols_userclick === "yes" ) 
        {
            //Shortcode attributes needed for communication js/php
            $sattributes = $attrs;
            $html .= '<form class="sc_attributes" action="">';
            foreach( $attrs as $ak_key=>$ak_value) {
                $html .= '<input type="text" value="' . $ak_value . '" name="' . $ak_key . '">';
            }

            if ( $editable === 'yes' )
            {                               
                //Print out all row values in separate divs based on which file is used
                //This is used later on for picking up what file to use for saving based on what content is changed (in js)
                foreach($content_arr as $file_index => $row_arr) 
                {
                    $html .= '<div class="all-rowcontent" data-source="'  . $editable_files[$file_index][0] . '">';

                    foreach($row_arr as $row_index => $column_values) 
                    {       
                        //Don't include headers (first row is header)
                        if ($row_index > 0 && $file_index == 0) 
                        {                          
                            $whole_row = implode( $csv_delimiter, $column_values);                           
                            $html .= $whole_row . '**';                        
                        }
                        else if ($file_index>0) {
                            $whole_row = implode( $csv_delimiter, $column_values);                           
                            $html .= $whole_row . '**';                        
                        }
                       
                    }

                    $html .= '</div>';
                }
                
            }

            $html .= '</form>';



            $html .= '</div>'; //Table wrapper end
        }
        
        //Save unix timestamp and content_arr to file if any fetch content is set (daily, hourly, weekly etc)   
        //You also must have specified a html id for this to work
        if ( $fetch_interval !== null && $original_htmlid !== null )
        {
            $fpath = $upload_basedir . '/';
            if ( !is_dir( $fpath . $this->tablestoragefolder) )
            {
                mkdir($fpath . $this->tablestoragefolder);
            }

            $fpath .= $this->tablestoragefolder . '/';

            $file_arr = "{$fpath}{$original_htmlid}.csvhtml";            

            //We can't just save html because of pagination, search etc (whole table is not loaded at once)
            //Instead we save the serialized content_arr generated
            file_put_contents( $file_arr, time() . '***time***' . serialize( $content_arr) );
        }


        return $html;
    }

}
        
$csvtohtmlwp = new csvtohtmlwp();
}