jQuery(function ($) {    
    //@Source: https://patdavid.net/2019/02/displaying-a-big-html-table/
    $('body').css('cursor','default');
    $('table.csvtohtml').show();

    var default_values;

    var current_url = my_ajax_object.ajax_url;

    $.ajax({
      url: current_url,
      method: 'GET',         
      dataType: 'json',
      data:{
          action: 'getdefaults'        
      }
    })
    .done(function( response ) {      
      default_values = response;   
    })
    .fail(function(textStatus) {
        console.log('failed getdefaults');
        console.log(textStatus);
    });

     
    //Sortable columns (by user)
    function doSorting(e, ths) {
        let sort_direction = 'asc';
        let sortindex = parseInt($(ths).attr('data-colindex'));

        if ($(ths).hasClass('asc')) {
            sort_direction = 'desc';
            $(ths).removeClass('asc').addClass('desc');
        }
        else {
            sort_direction = 'asc';
            $(ths).removeClass('desc').addClass('asc');
        }
        let colnr = sortindex + 1;        

       
        //If headertype is NOT set, then remove an extra parent (there is another scrollwrapper when using headertype)
        //(This is kind of backward, but I did this because I wanted to identify unique tables and not all)
        let table_wrapper = $(ths).parent().parent().parent().parent().parent();
        if ( $(table_wrapper).find("div.csvtohtml-tablescroll").length == 0 ) {
            table_wrapper = $(ths).parent().parent().parent().parent();
        }
        
        sortTable(e, table_wrapper, colnr, sort_direction);
    }

    $('table.csvtohtml-sortable').find('thead th').on('click',function(e) {
        doSorting(e, $(this));   
    });
   
    
    //Shortcode creation help function
    function  check_length( input_type , element_name ) {
        var tp = 'input';
        if (input_type=='select') {
          tp = 'select';
        }
        var element_nofrm = element_name.substr(4); //Remove four first chars (frm_)
    
        //Value exists, return value
        //If no html-object found, just return ''
        let el_val = $('#dynamic_form').find(tp + '[name="' + element_name  + '"]').val();
        if (!el_val) {return '';} 

        //Is value for element same as default?
        //Then skip it
        if (default_values[element_nofrm] != 'undefined') {
    
          if (default_values[element_nofrm] == el_val) {
            return '';
          }
          
        }
    
    
        if (  el_val.length > 0 ) {
          return element_nofrm + '="' + el_val + '" ';
        }
    
        //return empty string
        return '';
      }

    //Shortcode creation help function
    function generate_shortcode() {
        var sc_arr = [];
    
        sc_arr.push('[csvtohtml_create ');
    
        //Responsive
        sc_arr.push( check_length('select','frm_responsive') );
        sc_arr.push( check_length('input','frm_css_max_width') );
        sc_arr.push( check_length('input','frm_css_min_devicewidth') );
        sc_arr.push( check_length('input','frm_css_max_devicewidth') );
    
        //Html styling        
        sc_arr.push( check_length('select','frm_table_fixedlayout') );
        sc_arr.push( check_length('input','frm_html_id') );
        sc_arr.push( check_length('input','frm_html_class') );
        sc_arr.push( check_length('select','frm_header_type') );
        sc_arr.push( check_length('input','frm_header_backgroundcolor') );
        sc_arr.push( check_length('input','frm_header_backgroundcolor_left') );    
        sc_arr.push( check_length('input','frm_header_textcolor') );
        sc_arr.push( check_length('input','frm_header_textcolor_left') );  

        sc_arr.push( check_length('input','frm_table_height') );
        sc_arr.push( check_length('input','frm_table_width') );
        sc_arr.push( check_length('select','frm_fixed_leftcol') );
        sc_arr.push( check_length('input','frm_table_offset_header') );

        //General files/paths/format
        sc_arr.push( check_length('select','frm_show_onlyloggedin') );
        sc_arr.push( check_length('input','frm_path') );
        sc_arr.push( check_length('select','frm_source_type') );
        sc_arr.push( check_length('select','frm_skip_headerrow') );
        sc_arr.push( check_length('select','frm_headerrow_exists') );
        sc_arr.push( check_length('select','frm_editable') );
        sc_arr.push( check_length('input','frm_source_files') );
        sc_arr.push( check_length('input','frm_eol_detection') );
        sc_arr.push( check_length('input','frm_title') );        
        sc_arr.push( check_length('input','frm_csv_delimiter') );
        sc_arr.push( check_length('input','frm_float_divider') );
        sc_arr.push( check_length('select','frm_fetch_lastheaders') );
        sc_arr.push( check_length('select','frm_add_ext_auto') );
        sc_arr.push( check_length('select','frm_large_files') );
        
        //Encoding
        sc_arr.push( check_length('select','frm_convert_encoding_from') );
        sc_arr.push( check_length('select','frm_convert_encoding_to') );

        //Sorting
        sc_arr.push( check_length('input','frm_sort_cols') );
        sc_arr.push( check_length('input','frm_sort_cols_order') );
        sc_arr.push( check_length('select','frm_sort_cols_userclick') );  
        sc_arr.push( check_length('select','frm_sort_cols_userclick_arrows') );

        //Rows and columns
        sc_arr.push( check_length('input','frm_include_rows') );
        sc_arr.push( check_length('input','frm_include_cols') );
        sc_arr.push( check_length('input','frm_exclude_cols') );
        sc_arr.push( check_length('input','frm_hide_cols') );
        sc_arr.push( check_length('input','frm_headerrows_start') );
    
        //Filter
        sc_arr.push( check_length('input','frm_filter_data') );
        sc_arr.push( check_length('input','frm_filter_criterias') );
        sc_arr.push( check_length('input','frm_filter_removechars') );
        sc_arr.push( check_length('select','frm_filter_operator') );
        sc_arr.push( check_length('input','frm_filter_col') );        

        //Grouping
        sc_arr.push( check_length('select','frm_groupby_col') );
        sc_arr.push( check_length('select','frm_groupby_col_header') );
    
        //Table in columns
        sc_arr.push( check_length('input','frm_table_in_cell_wrapperclass') );
        sc_arr.push( check_length('input','frm_table_in_cell_header') );
        sc_arr.push( check_length('input','frm_table_in_cell_cols') );
    
        //Pagination
        sc_arr.push( check_length('select','frm_pagination') );
        sc_arr.push( check_length('select','frm_pagination_below_table') );
        sc_arr.push( check_length('select','frm_pagination_above_table') );
        sc_arr.push( check_length('input','frm_pagination_start') );
        sc_arr.push( check_length('input','frm_pagination_text_start') );
        sc_arr.push( check_length('input','frm_pagination_text_prev') );
        sc_arr.push( check_length('input','frm_pagination_text_next') );
        sc_arr.push( check_length('input','frm_pagination_text_last') );
        sc_arr.push( check_length('input','frm_pagination_rows') );        
        sc_arr.push( check_length('input','frm_pagination_links_max') );
        
        //Search
        sc_arr.push( check_length('select','frm_search_functionality') );
        sc_arr.push( check_length('select','frm_search_caseinsensitive') );
        sc_arr.push( check_length('select','frm_search_exactmatch') );
        sc_arr.push( check_length('input','frm_search_cols') );
        sc_arr.push( check_length('select','frm_hidetable_load') );
        sc_arr.push( check_length('select','frm_hidetable_reset') );
        sc_arr.push( check_length('select','frm_search_realtime') );
        sc_arr.push( check_length('input','frm_searchbutton_text') );
        sc_arr.push( check_length('input','frm_resetbutton_text') );
        sc_arr.push( check_length('input','frm_searchinput_placeholder') );
        sc_arr.push( check_length('input','frm_notfound_message') );
        sc_arr.push( check_length('input','frm_search_requiredchars') );
        sc_arr.push( check_length('input','frm_search_requiredchars_message') );      
        sc_arr.push( check_length('select','frm_search_highlight') );
        sc_arr.push( check_length('input','frm_search_highlightcolor') );
        sc_arr.push( check_length('select', 'frm_search_excludedrows') );

        //Totals
        sc_arr.push( check_length('input','frm_totals_cols_bottom') );
        sc_arr.push( check_length('input','frm_totals_cols_bottom_countlines') );
        sc_arr.push( check_length('input','frm_totals_cols_bottom_empty') );
        sc_arr.push( check_length('input','frm_totals_cols_bottom_title') );
        sc_arr.push( check_length('input','frm_totals_cols_bottom_title_col') );
        sc_arr.push( check_length('input','frm_totals_cols_prefix') );
        sc_arr.push( check_length('input','frm_totals_cols_suffix') );
        sc_arr.push( check_length('select','frm_total_percentage_above_table') );
        sc_arr.push( check_length('select','frm_total_percentage_below_table') );
        sc_arr.push( check_length('input','frm_total_percentage_checkvalue') );
        sc_arr.push( check_length('select','frm_total_percentage_col') );
        sc_arr.push( check_length('input','frm_total_percentage_text') );
        sc_arr.push( check_length('input','frm_total_percentage_decimals') );

        //Fetching
        sc_arr.push( check_length('select','frm_fetch_interval') );  
        sc_arr.push( check_length('select','frm_downloadable') );  
        sc_arr.push( check_length('input','frm_downloadable_text') );
        sc_arr.push( check_length('input','frm_downloadable_filename') );
    
        //Communication
        sc_arr.push( check_length('input','frm_selected_sheets') );
        sc_arr.push( check_length('select','frm_api_cdn') );  
        sc_arr.push( check_length('input','frm_json_startlevel') );
        sc_arr.push( check_length('select','frm_design_template') );  
        
        //Automatic converts
        sc_arr.push ( check_length('select', 'frm_markdown_support'));
        sc_arr.push ( check_length('select', 'frm_htmltags_autoconvert'));
        sc_arr.push ( check_length('select', 'frm_htmltags_autoconvert_newwindow'));
        sc_arr.push ( check_length('input', 'frm_htmltags_autoconvert_imagealt'));
        sc_arr.push ( check_length('input', 'frm_htmltags_autoconvert_imagewidth'));
        
        sc_arr.push ( check_length('select', 'frm_grabcontent_col_fromlink'));
        sc_arr.push ( check_length('select', 'frm_grabcontent_col_tolink'));
        sc_arr.push ( check_length('select', 'frm_grabcontent_col_tolink_addhttp'));

        sc_arr.push(']');        
        $('#new_shortcode').html( sc_arr.join("") );
    
    }

    //If path / files etc has changed, values in form must be updated 
    //regarding to columns, rows etc
    function updatevalues_fromshortcode() {
        let selected_value = $('#new_shortcode').html();
             
        var current_url = my_ajax_object.ajax_url;  
        $.ajax({
            url: current_url,
            method: 'GET',         
            dataType: 'html',
            data:{
                action: 'refreshform',
                doshortcode: 'no',
                shortcode: selected_value   
            }
        })
        .done(function( response ) {              
            $('#dynamic_form').html( response );
            $("div.csvtohtml-p.admin h2").on( "click", function() {
                $(this).parent().toggleClass('selectedsection');

                if ( $(this).parent().hasClass("reflist") ) {
                    $("#csvtohtml-referencelist-csvtohtml-plugin").toggle();
                }
                else {
                    $(this).parent().find('table').toggle();
                }
            });

            showresult_shortcode(); //Put this in preview div             
        })
        .fail(function(textStatus) {
            console.log('failed updatevalues_fromshortcode');
            console.log(textStatus);
        });    
    }

    //Init preview shortcode    
    if ($('#new_shortcode').length >0) {
        updatevalues_fromshortcode();
    }

    //Show result of generated shortcode in preview-div
    function showresult_shortcode() {
        let selected_value = $('#new_shortcode').html();
        var current_url = my_ajax_object.ajax_url;  

        //Shortcode preview
        $.ajax({
            url: current_url,
            method: 'GET',         
            dataType: 'html',
            data:{
                action: 'refreshform',
                doshortcode: 'yes',
                shortcode: selected_value   
            }
        })
        .done(function( response ) {              
            $('#shortcode_preview').html( response );   
            $('table.csvtohtml').show(); //Must be here, because invisible from start!         
        })
        .fail(function(textStatus) {
            console.log('failed showresult_shortcode');
            console.log(textStatus);
        });          
    }


    $('#csvtohtml-upload-form').submit(function(event) {
        event.preventDefault();    
        var current_url = my_ajax_object.ajax_url;
        
        // Create FormData object
        var formData = new FormData(this);
    
        $.ajax({
            url: current_url,
            method: 'POST',
            processData: false,  // Important: prevent jQuery from processing the data
            contentType: false,  // Important: prevent jQuery from setting content type
            dataType: 'json',
            data: formData,
        })
        .done(function(response) {  
            $('#upload-result').html(response).show().fadeOut(5000);
            $('body').css('cursor','wait');
            updatevalues_fromshortcode();
            $('body').css('cursor','default');
        })
        .fail(function(xhr, textStatus) {
            alert('Failed uploading');
            console.log(xhr.responseText);
            console.log(textStatus);
        });
    });
    
    $('body').on('click', '#update_shortcode', function() {
        $('body').css('cursor','wait');
        updatevalues_fromshortcode();
        $('body').css('cursor','default');
    });

    //Create short dynamically when user types / changes anything
    $('body').on('keyup', 'input', function() {
        generate_shortcode();
    });

    $('body').on('click', '#pathviewer', function(e) {
        e.preventDefault();
        $('#uploadpaths').toggle();
    });

    $('body').on('click', '#fileviewer', function(e) {
        e.preventDefault();
        $('#fileview').toggle();
    });  

    $('body').on('click', 'a.filelink', function(e) {
        e.preventDefault();
        let clicked_file = $(this).data('basename');
        let current_val = $('input[name="frm_source_files"]').val();
        if ( current_val == '*.csv') {
            current_val = '';
        }
        add_char = '';
        if (current_val.length > 0) {
            add_char = ';';
        }
        
        $('input[name="frm_source_files"]').val( current_val + add_char + clicked_file ) ;
        generate_shortcode();
    });

    //Debugging events
    $('body').on('click', '.useguess', function(e) {
        e.preventDefault();         
        $('select[name="frm_source_type"]').val('guess');    
        generate_shortcode();
    });

    $('body').on('click', '#identifydelimiter', function(e) {
        e.preventDefault();         
        $('input[name="frm_csv_delimiter"]').val( ',' );
        generate_shortcode();
    });

    $('body').on('click', '#removeencodings', function(e) {
        e.preventDefault();         
        $('select[name="frm_convert_encoding_from"]').val( '' );
        $('select[name="frm_convert_encoding_to"]').val( '' );
        generate_shortcode();
    });

    $('body').on('click', '#removefilter', function(e) {
        e.preventDefault();         
        $('input[name="frm_filter_data"]').val( '' );
        $('select[name="frm_filter_col"]')[0].selectedIndex = 0;    
        generate_shortcode();
    });
    //End debugging events


    //This is used when user might have mispelled a file
    $('body').on('click', '.changefile', function(e) {
        e.preventDefault();         
        let old_file = $('.adjustspelling').data('file');
        let new_file = $(this).parent().find('.adjustspelling').html();

        //Replace file and put replacement in source_files
        let current_sourcefiles = $('input[name="frm_source_files"]').val();
        let cs = current_sourcefiles.replace( old_file, new_file );
        $('input[name="frm_source_files"]').val(cs);
        generate_shortcode();
        updatevalues_fromshortcode();
    });




    $('body').on('click', 'a.pathlink', function(e) {
        e.preventDefault();
        let clickedon_folder = $(this).html();

        //Set input folderpath and also replace/add to baseupload folder for csv-files
        $('input[name="frm_path"]').val( clickedon_folder );
        $('#csvtohtmlsettings-path').html( clickedon_folder );    
        generate_shortcode();
        updatevalues_fromshortcode();
    });

    $('body').on('change', 'select', function() {
        generate_shortcode();    
    });

    //Total (bottom of table)
    $('body').on('click', 'div input[name^="totalcl"]', function() {

        let total_cols_bottom = '';
        $('div input[name^="totalcl"]').each(function( index ) {
        if ( $(this).is(':checked')) {
            total_cols_bottom += '' + (index+1) + ',';
        }
        
        
        });

        //Remove last char
        total_cols_bottom = total_cols_bottom.slice(0, -1); 
        $('input[name="frm_totals_cols_bottom"]').val(total_cols_bottom);
        generate_shortcode();
        
    });

    $('body').on('click', 'div input[name^="sorting"]', function() {
        doSort();
    });

    $('body').on('change', 'div select[name^="sortiteration_col"]', function() {
        doSort();
    });

    function doSort() {
        var str_sort = '';
        var str_sort_direction = '';
        var row = 1;

        var icol = [];
        $('div select[name^="sortiteration_col"]').each(function( index ) {

            var this_name = $(this).attr('name'),
            col_number  = this_name.split('[')[1].split(']')[0];
            var iteration = $('div select[name="sortiteration_col[' + col_number + ']"');

            var iteration_nr = null;
            if (iteration.length>0) {
                iteration_nr = parseInt(iteration.val());            
            }           
            icol.push( iteration_nr );
            
        });
            
        

        //Rearrange iteration/sortingorder values for column array
        var iterationorder_col = [];
        var sortorder_col = [];
        var use_col;

        for (var i = 0; i < icol.length; i++) {
            use_col = icol.indexOf(i) + 1;
            
            //Iteration order (in which order column(s) are sorted)
            iterationorder_col[i] = use_col;  //+1 to tell which column

            //Sortorder (asc,desc,ignore)
            var this_obj = $('div input[name="sorting[' + use_col + ']"');        
            var use_data = '';
            this_obj.each(function( index ) {
            if ($(this).is(':checked')) {
                use_data = $(this).data('num');
                return false;
            }
            });
                            
            sortorder_col.push(use_data);
        
        }

        //Generate final attribute inclusion of sorting columns      
        //Skip when nosort or col is set to zero
        for(i=0;i<iterationorder_col.length;i++) {
            if ( sortorder_col[i] !== 'nosort' && iterationorder_col[i]>0) {
                str_sort += '' + iterationorder_col[i] + ',';
                str_sort_direction += sortorder_col[i] + ',';
            }
        };

        //Remove last char
        str_sort = str_sort.slice(0, -1); 
        str_sort_direction = str_sort_direction.slice(0, -1);   
        
        $('#sort_str').val(str_sort);
        $('#sort_str_direction').val(str_sort_direction);

        generate_shortcode();

    };

    //Change location of (local) files when modifiying path
    $('body').on('keyup', 'div input[name="frm_path"]', function(){
        $('#csvtohtmlsettings-path').html($(this).val());
    });

    //Inclusion of cell table in cells functionality
    $('body').on('click', 'div input[name^="tableincellsclude"]', function(){
        var str_include = '';
        var str_exclude = '';

        var clude_arr = $('div input[name^="tableincellsclude"]');
        if (clude_arr.length > 0) {
        
        str_include = '';

        //Go through all include/exclude radio buttons
        clude_arr.each(function( index ) {
            
            if ($(this).is(':checked')) {   
            var current_data = $(this).data('num');

            //Get column of checked radiobutton...                        
            var this_name = $(this).attr('name'),
            col_number  = this_name.split('[')[1].split(']')[0];
            
            //Get values from data-attr for clude[col]
            if (current_data == 'ignore') {
                //Remove this columns values from both include and exclude
                str_include = str_include.replace( col_number + ',', '');
            }
            if (current_data == 'include') {
                str_include += col_number + ',';
            }
            
            }
        });

        //Remove last char
        str_include = str_include.slice(0, -1); 
        str_exclude = str_exclude.slice(0, -1);        
        
        if (str_include == 'include_cols="') {
            str_include = '';
        }

        $('#table_in_cell_cols').val(str_include);

        generate_shortcode();
        }

    });

    
    //Filter on columns
    $('body').on('click', 'div input[name^="filter_includecols"]', function(){
                
        var str_include = '';

        var filtercols_arr = $('div input[name^="filter_includecols"]');
        if (filtercols_arr.length > 0) {
            
            str_include = '';
            var nr_cols = 0;

            //Go through all include in search radio buttons
            filtercols_arr.each(function( index ) {
              
                if ($(this).is(':checked')) {   
                    var current_data = $(this).data('num');

                    //Get column of checked radiobutton...                        
                    var this_name = $(this).attr('name'),
                    col_number  = this_name.split('[')[1].split(']')[0];
                    
                    //Get values from data-attr for clude_search[col]
                    if (current_data == 'ignore') {
                        //Remove this columns values
                        str_include = str_include.replace( col_number + ',', '');
                    }
                    if (current_data == 'include') {
                        str_include += col_number + ',';
                    }
            
                }

                nr_cols++;
            });

            //Remove last char
            str_include = str_include.slice(0, -1); 
            
            if (str_include == 'filter_col="') {
                str_include = '';
            }

        }

        $('#include_filtercols_shortcode_str').val(str_include);

        generate_shortcode();
        
    });    

    //Search in columns
    $('body').on('click', 'div input[name^="search_includecols"]', function(){
                
        var str_include = '';

        var searchincols_arr = $('div input[name^="search_includecols"]');
        if (searchincols_arr.length > 0) {
            
            str_include = '';
            var nr_cols = 0;

            //Go through all include in search radio buttons
            searchincols_arr.each(function( index ) {
              
                if ($(this).is(':checked')) {   
                    var current_data = $(this).data('num');

                    //Get column of checked radiobutton...                        
                    var this_name = $(this).attr('name'),
                    col_number  = this_name.split('[')[1].split(']')[0];
                    
                    //Get values from data-attr for clude_search[col]
                    if (current_data == 'ignore') {
                        //Remove this columns values
                        str_include = str_include.replace( col_number + ',', '');
                    }
                    if (current_data == 'include') {
                        str_include += col_number + ',';
                    }
            
                }

                nr_cols++;
            });

            //Remove last char
            str_include = str_include.slice(0, -1); 
            
            if (str_include == 'search_cols="') {
                str_include = '';
            }

        }

        $('#include_searchcols_shortcode_str').val(str_include);

        generate_shortcode();
        
    });


    //Include/Exclusion of columns
    $('body').on('click', 'div input[name^="clude"]', function(){
        var str_include = '';
        var str_exclude = '';
        var str_hide = '';

        var clude_arr = $('div input[name^="clude"]');
        if (clude_arr.length > 0) {
            
            str_include = '';
            str_exclude = '';
            str_hide = '';
            var nr_cols = 0;

            //Go through all include/exclude radio buttons
            clude_arr.each(function( index ) {
            
                if ($(this).is(':checked')) {   
                    var current_data = $(this).data('num');

                    //Get column of checked radiobutton...                        
                    var this_name = $(this).attr('name'),
                    col_number  = this_name.split('[')[1].split(']')[0];
                    
                    //Get values from data-attr for clude[col]
                    if (current_data == 'ignore') {
                        //Remove this columns values from both include and exclude
                        str_include = str_include.replace( col_number + ',', '');
                        str_exclude = str_exclude.replace( col_number + ',', '');
                        str_hide = str_hide.replace( col_number + ',', '' );
                    }
                    if (current_data == 'include') {
                        str_include += col_number + ',';
                    }
                    if (current_data == 'exclude') {
                        str_exclude += col_number + ',';
                    }       
                    if (current_data == 'hide') {
                        str_hide += col_number + ',';
                    }      
                }

                nr_cols++;
            });
            
            //Remove last char
            str_include = str_include.slice(0, -1); 
            str_exclude = str_exclude.slice(0, -1); 
            str_hide = str_hide.slice(0, -1);       
            
            if (str_include == 'include_cols="') {
                str_include = '';
            }
            if (str_exclude == 'exclude_cols="') {
                str_exclude = '';
            }
            if (str_hide == 'hide_cols="') {
                str_hide = '';
            }

            $('#include_shortcode_str').val(str_include);
            $('#hide_shortcode_str').val(str_hide);
            let excl_sc = $('#exclude_shortcode_str').val(str_exclude);  


            //If number of exclusion is set to number of columns
            if (excl_sc.val().length == nr_cols) {
                alert('If you exclude everything - nothing is shown!');
            }

        }

        generate_shortcode();
        
    });


    $("div.csvtohtml-p.admin h2").on( "click", function() {
        $(this).parent().toggleClass('selectedsection');

        if ( $(this).parent().hasClass("reflist") ) {
            $("#csvtohtml-referencelist-csvtohtml-plugin").toggle();
        }
        else {
            $(this).parent().find('table').toggle();
        }
    });

      
    
    function editable() {

        //If editable is set to yes, this function 
        //is used for the actual saving when changing content in a textinput (with classname savecell) 
        $('.csvtohtml').on('change','.savecell', function(e) {
            //four levels up are always the same table that user tries to edit in
            var table_identifier = $(this).parent().parent().parent().parent();         

            //Relevant attributes to use
            var ds = $(this).data('source');
            
            var row_index = $(this).data('filerow');            
            var csvfile = $(this).data('csvfile');
            var delim = $(this).data('delimiter');
            var csv_editrow = [];
            var csv_headers = [];
            var csv_row = '';
            var all_content_file = null;

            //Headers
            $(table_identifier).find('tr.headers[data-source="' + ds +'"]').each(function(index) {
                csv_row = '';           
                $(this).find('.savecell').each(function(inner_index) {
                    csv_row += '' + $(this).val() + delim;                
                });                

                csv_headers.push( csv_row.slice(0,-1) ); //Remove last char in string (delimiter)
            });

            //All row content for this specific file
            var shortcode_attributes = table_identifier.parent().find('form.sc_attributes'); //If using pagination
            all_content_file = shortcode_attributes.find('.all-rowcontent[data-source="' + ds +'"]').html();

            //Grab which row is edited for this specific file and add that to a new array with index as row_index
            var row_content = $(table_identifier).find('tr.rowset[data-source="' + ds +'"]').find('.savecell[data-filerow="' + row_index + '"]');
           
            //Create a string of current row
            csv_row = '';
            row_content.each(function(inner_index) {               
                csv_row += $(this).val() + delim;                      
            });

            //Put this string into array with given row (file) index
            csv_editrow[row_index] = csv_row.slice(0,-1); //Remove last char in string
     

            //Save the actual content
            //with help / request to server-side
            var current_url = my_ajax_object.ajax_url;
          
            $.ajax({
                url: current_url,
                method: 'POST',         
                dataType: 'json',
                data:{
                    action: 'savecsvfile',  
                    filerow: row_index,
                    csvcontent: csv_editrow,    
                    csvheaders: csv_headers,            
                    allcontent: all_content_file,                    
                    csvfile: csvfile, //this value is part of this filename
                    attrs: shortcode_attributes.serialize()
                }
            })
            .done(function( response ) {  
                //Update all updated values into html content all-rowscontent for saving correctly
                //several times/values in same file
                shortcode_attributes.find('.all-rowcontent[data-source="' + ds +'"]').html(response.allcontent);
            })
            .fail(function(textStatus) {
                alert('failed saving');
                console.log(textStatus);
            });

        });
    }

    editable();

    var searching = false;

    function checkSearchRealtime() {
        var timeout;
        var delay = 1000;   // 1 second //Get this value from attributes
        
        //If user presses enter, handle it as an ordinary search
        $('.csv-search').on('keypress','.search-text', function(e) { 
            if (e.which == 13 ) {                
                searchTable(e, $(this).parent().find('.search-submit'));
                return false;
            }
        });

        $('.csv-search').on('keyup','.search-text', function(e) { 
            //If user presesed enter, don't do anything (it's done in keypress above)
            if (e.which == 13 ) {                
                return false;               
            }

            var ev = e;
            var t = $(this);

            //Check if realtimesearch is set to yes for this table
            //and do the search then
            var attrs = t.parent().parent().parent().find('.sc_attributes');
            var srt = attrs.find('input[name="search_realtime"]');
            if (srt.length>0) {
                if (srt.val() == 'yes') {
                    if(timeout) {
                        clearTimeout(timeout);
                    }
                    timeout = setTimeout(function() {            
                        searchTable(ev, t);
                    }, delay);
                }
            }
        });        
    }

    checkSearchRealtime();





    $('.csv-search').on('click','.search-submit', function(e) {
        searchTable(e, $(this));
    });

    $('.csv-search').on('click','.reset-submit', resetTable);
    

    function sortTable(e, tablewrapper_obj, colnr, sort_direction) {
        e.preventDefault();       
        var shortcode_attributes = tablewrapper_obj.find('form.sc_attributes');

        $('body').css('cursor','wait');
        tablewrapper_obj.find('table.csvtohtml').find('thead th').css('cursor','wait');
        $('.csv-search').css('cursor', 'wait');
        $('.csv-search form').css('cursor', 'wait');
        $('.csv-search input').css('cursor', 'wait');    

        var current_url = my_ajax_object.ajax_url;
        $.ajax({
            url: current_url,
            method: 'POST',         
            dataType: 'json',
            data:{
                action: 'fetchtable',  
                attrs: shortcode_attributes.serialize(),  
                pagination_start: 1, //always start at page 1 when search result is presented
                dosort: "yes",
                column: colnr,
                direction: sort_direction
            }
        })
        .done(function( response ) {   
            $('body').css('cursor','default');
            $('.csv-reset').off('click', '.search-submit');        
            $('.csv-reset').off('click', '.reset-submit');   
            $('.csv-search').off('keyup','.search-text');
            $('.csvhtml-pagination').off('click', 'a');
            $('table.csvtohtml-sortable').find('thead th').off('click');

            tablewrapper_obj[0].outerHTML = response.tabledata;

            //Reinitiate for click to work after replacing html-content table/pagination     
            $('.csv-search').on('click','.search-submit', function(e) {
                searchTable(e, $(this))
            });
            $('.csvhtml-pagination').on('click', 'a', reloadTable);
            $('.csv-search').on('click','.reset-submit', resetTable);
            checkSearchRealtime();

            $('table.csvtohtml-sortable').find('thead th').on('click',function(e) {
                doSorting(e, $(this));
            });            

            tablewrapper_obj.find('table.csvtohtml').find('thead th').css('cursor','default');
            $('table.csvtohtml').show();
        })
        .fail(function(textStatus) {
            $('body').css('cursor','default');
            $('.csv-search').css('cursor', 'default');
            $('.csv-search form').css('cursor', 'default');
            $('.csv-search input').css('cursor', 'default');  
            $('.csv-search').off('click', '.search-submit');  
            $('.csvhtml-pagination').off('click', 'a');      
            $('.csv-search').off('click', '.reset-submit');            
            $('.csv-search').off('keyup','.search-text');  
            $('table.csvtohtml-sortable').find('thead th').off('click');            

            $('.csv-search').on('click','.search-submit', function(e) {
                searchTable(e, $(this))
            });
            checkSearchRealtime();                       
            $('.csvhtml-pagination').on('click', 'a', reloadTable);
            $('.csv-search').on('click','.reset-submit', resetTable);
            $('table.csvtohtml-sortable').find('thead th').on('click',function(e) {
                doSorting(e, $(this));
            });              
            tablewrapper_obj.find('table.csvtohtml').find('thead th').css('cursor','default'); 
            console.log('failed resetTable');
            console.log(textStatus);
        });
    };

    function resetTable(e) {
        e.preventDefault();
        var h = $(this).data();
        var tablewrapper_obj = $('#wrapper-' + h.htmlid);
        var shortcode_attributes = tablewrapper_obj.find('form.sc_attributes');
        
        $('body').css('cursor','wait');
        $('.csv-search').css('cursor', 'wait');
        $('.csv-search form').css('cursor', 'wait');
        $('.csv-search input').css('cursor', 'wait');    

        var current_url = my_ajax_object.ajax_url;
        $.ajax({
            url: current_url,
            method: 'POST',         
            dataType: 'json',
            data:{
                action: 'fetchtable',  
                attrs: shortcode_attributes.serialize(),  
                pagination_start: 1, //always start at page 1 when search result is presented
                reset: 1
            }
        })
        .done(function( response ) {   
            $('body').css('cursor','default');
            $('.csv-search').css('cursor', 'default');
            $('.csv-search form').css('cursor', 'default');   
            $('.csv-search input').css('cursor', 'default'); 
            $('.csv-reset').off('click', '.search-submit');        
            $('.csv-reset').off('click', '.reset-submit');   
            $('.csv-search').off('keyup','.search-text');
            $('.csvhtml-pagination').off('click', 'a');
            $('table.csvtohtml-sortable').find('thead th').off('click');
            tablewrapper_obj[0].outerHTML = response.tabledata;

            //Reinitiate for click to work after replacing html-content table/pagination     
            $('.csv-search').on('click','.search-submit', function(e) {
                searchTable(e, $(this))
            });
            $('.csvhtml-pagination').on('click', 'a', reloadTable);
            $('.csv-search').on('click','.reset-submit', resetTable);
            $('table.csvtohtml-sortable').find('thead th').on('click',function(e) {
                doSorting(e, $(this));
            });              
            checkSearchRealtime();
            $('table.csvtohtml').show();
        })
        .fail(function(textStatus) {
            $('body').css('cursor','default');
            $('.csv-search').css('cursor', 'default');
            $('.csv-search form').css('cursor', 'default');
            $('.csv-search input').css('cursor', 'default');  
            $('.csv-search').off('click', '.search-submit');  
            $('.csvhtml-pagination').off('click', 'a');      
            $('.csv-search').off('click', '.reset-submit');            
            $('.csv-search').off('keyup','.search-text');  
            $('table.csvtohtml-sortable').find('thead th').off('click');

            $('.csv-search').on('click','.search-submit', function(e) {
                searchTable(e, $(this))
            });
            checkSearchRealtime();                       
            $('.csvhtml-pagination').on('click', 'a', reloadTable);
            $('.csv-search').on('click','.reset-submit', resetTable);
            $('table.csvtohtml-sortable').find('thead th').on('click',function(e) {
                doSorting(e, $(this));
            });               
            console.log('failed resetTable');
            console.log(textStatus);
        });
    };

    function searchTable(e, ths) {
        if ( searching == true ) {
            return false;
        }

        searching = true;
        e.preventDefault();      
        var search_text = ths.parent().find('.search-text').val();                   
        var h = ths.data();
        var tablewrapper_obj = $('#wrapper-' + h.htmlid);       
        var shortcode_attributes = tablewrapper_obj.find('form.sc_attributes');

        $('body').css('cursor','wait');
        $('.csv-search').css('cursor', 'wait');
        $('.csv-search form').css('cursor', 'wait');
        $('.csv-search input').css('cursor', 'wait');    

        var current_url = my_ajax_object.ajax_url;
        $.ajax({
            url: current_url,
            method: 'POST',         
            dataType: 'json',
            data:{
                action: 'fetchtable',  
                attrs: shortcode_attributes.serialize(),  
                pagination_start: 1, //always start at page 1 when search result is presented
                search: search_text
            }
        })
        .done(function( response ) {   
            $('body').css('cursor','default');
            $('.csv-search').css('cursor', 'default');
            $('.csv-search form').css('cursor', 'default');   
            $('.csv-search input').css('cursor', 'default');      
            $('.csv-search').off('click', '.search-submit');   
            $('.csvhtml-pagination').off('click', 'a');          
            $('.csv-search').off('click', '.reset-submit');   
            $('.csv-search').off('keyup','.search-text');  
            $('table.csvtohtml-sortable').find('thead th').off('click');
            tablewrapper_obj[0].outerHTML = response.tabledata;
           

            //Reinitiate for click to work after replacing html-content table/pagination     
            $('.csv-search').on('click','.search-submit', function(e) {
                searchTable(e, $(this))
            });
            $('.csvhtml-pagination').on('click', 'a', reloadTable);
            $('.csv-search').on('click','.reset-submit', resetTable);                             
            checkSearchRealtime();
            $('table.csvtohtml-sortable').find('thead th').on('click',function(e) {
                doSorting(e, $(this));
            });               
            $('table.csvtohtml').show();
            searching = false;       
        })
        .fail(function(textStatus) {
            ths.disabled = false;
            $('body').css('cursor','default');
            $('.csv-search').css('cursor', 'default');
            $('.csv-search form').css('cursor', 'default');
            $('.csv-search input').css('cursor', 'default');  
            $('.csv-search').off('click', '.search-submit');
            $('.csvhtml-pagination').off('click', 'a');      
            $('.csv-search').off('click', '.reset-submit');     
            $('.csv-search').off('keyup','.search-text');  
            $('table.csvtohtml-sortable').find('thead th').off('click');

            $('.csv-search').on('click','.search-submit', function(e) {
                searchTable(e, $(this))
            });
            $('.csvhtml-pagination').on('click', 'a', reloadTable);
            $('.csv-search').on('click','.reset-submit', resetTable);
            $('table.csvtohtml-sortable').find('thead th').on('click',function(e) {
                doSorting(e, $(this));
            });   

            checkSearchRealtime();
            searching = false;            
            console.log('failed searchTable');
            console.log(textStatus);
        });
    };

    
    //Pagination click - reload table based on what row to start on
    //(pagination)
    $('.csvhtml-pagination').on('click', 'a', reloadTable);

    //this is a separate function because a reinitation is need after every click
    //because html is replaced totally from shortcode
    function reloadTable(e) {       
        if (searching == true) {return false;};
        searching = true;

        e.preventDefault();              
        var h = $(this).data();
        var tablewrapper_obj = $('#wrapper-' + h.htmlid);
        var shortcode_attributes = tablewrapper_obj.find('form.sc_attributes');

        $('body').css('cursor','wait');
        $('.csvhtml-pagination').css('cursor', 'wait');
        $('.csvhtml-pagination a').css('cursor', 'wait');

        var current_url = my_ajax_object.ajax_url;
        $.ajax({
            url: current_url,
            method: 'POST',         
            dataType: 'json',
            data:{
                action: 'fetchtable',  
                attrs: shortcode_attributes.serialize(),  
                pagination_start: h.pagination
            }
        })
        .done(function( response ) {   
            $('body').css('cursor','default');     
            $('.csvhtml-pagination').css('cursor', 'default');
            $('.csvhtml-pagination a').css('cursor', 'default');
            $('.csv-search').off('click', '.search-submit');
            $('.csvhtml-pagination').off('click', 'a');     
            $('.csv-search').off('click', '.reset-submit');  
            $('.csv-search').off('keyup','.search-text'); 
            $('table.csvtohtml-sortable').find('thead th').off('click');
                               
            tablewrapper_obj[0].outerHTML = response.tabledata;              
            $('.csvhtml-pagination').on('click', 'a', reloadTable); //Reinitiate for click to work after replacing html-content table/pagination                                                          
            $('.csv-search').on('click','.search-submit', function(e) {
                searchTable(e, $(this))
            });
            $('.csv-search').on('click','.reset-submit', resetTable);
            checkSearchRealtime();     
            editable();

            $('table.csvtohtml-sortable').find('thead th').on('click',function(e) {
                doSorting(e, $(this));
            });              
            $('table.csvtohtml').show(); 
            searching = false;
        })
        .fail(function(textStatus) {
            $('body').css('cursor','default');
            $('.csvhtml-pagination').css('cursor', 'default');
            $('.csvhtml-pagination a').css('cursor', 'default');
            $('.csvhtml-pagination').off('click', 'a');     
            $('.csv-search').off('click', '.search-submit');
            $('.csv-search').off('click', '.reset-submit');
            $('.csv-search').off('keyup','.search-text');  
            $('.csvhtml-pagination').on('click', 'a', reloadTable); //Reinitiate for click to work after replacing html-content table/pagination                                              
            $('.csv-search').on('click','.search-submit', function(e) {
                var t = $(this);
                searchTable(e, t)
            });
            $('table.csvtohtml-sortable').find('thead th').off('click');
            $('.csv-search').on('click','.reset-submit', resetTable);    
            checkSearchRealtime();  
            $('table.csvtohtml-sortable').find('thead th').on('click',function(e) {
                doSorting(e, $(this));
            });                
            searching = false;
             
            console.log('failed reloadTable');
            console.log(textStatus);
        });
    };
});