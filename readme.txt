=== CSV to html ===
Contributors: wibergsweb, gdeepwell
Donate link: https://www.paypal.com/donate?hosted_button_id=8JHZ495S839LQ
Tags: import, csv, html, table, dynamically
Requires PHP: 5.6
Requires at least: 3.0.1
Tested up to: 6.5.3
Stable tag: 3.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display/edit/synchronize csv-file(s) dynamically into a html-table. No coding required.

== Description ==

CSV to html is a highly configurable plugin that makes it easy to fetch content from file(s) (.csv,.xlsx, .json and .ods) and put content from that file/those files and display the html(table) on a page with a single shortcode. If using more then one file, content from all files are mixed into one single table instead of creating two tables. 

The plugin fetches the actual content directly from the file(s) without having to import/export any file(s) manually. So any changes in the file(s) will be updated when you view your table(s). CSV to HTML is able to fetch both local and external files.

Get started with the plugin by moving on to the Installation tab. There's a 4 step guide how you could start using the plugin. Good luck!

== Installation ==

This section describes how to install the plugin and get it working.

Look at inspirational examples of what you can do with the plugin here: <http://wibergsweb.se/plugins/csvtohtml/>

But **FIRST**, go through below steps!

**STEP1 - INSTALLATION**
1. Upload the plugin folder csvtohtml to the `/wp-content/plugins/' directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress

**STEP2 - PREPARATION**
The plugin can either fetch files locally from your server (or from an external resource). The plugin supports csv-files, Excel(xlsx-files only), ods-files (spreadsheet) and json-files. If using a json-file then you should set source type to JSON. In most cases the source type should be set to guess if not using JSON.

= Local fetch =
1. Create a folder called csvtohtmlfiles in your wp-content/uploads/ manually (e.g. wp-content/uploads/csvtohtmlfiles should be created)
2. Upload/copy your file to this created folder (e.g. wp-content/uploads/csvtohtmlfiles/testfile.csv). This **cannot**be done from media-upload (Use ftp, ssh or directly on your os to copy files to the webserver).

= External fetch =
1. Have an uri to your csv-file available/accessible from your computer (eg. https://[domain]/testfile.csv).

**STEP3 - SHORTCODE GENERATOR**
1. Begin to understand what you are able to to do with the plugin. Do this by playing around with the CSV to HTML shortcode generator. This is accessible in the admin dashboard - Tools/CSV to HTML. Do this on a desktop computer for easiest management.
2. Try and click/modify settings and see the preview of the table to the right. This preview only shows how it would look like, but functionality such as search and pagination etc isn't going to work. 
3. If you want to see the current output based on applied settings, then you should click the update/preview - button. 
4. Settings are splitted into different sections in the shortcode generator page. 
5. The section &quot;Debugging&quot; shows known errors and gives warnings depending on your settings. It turns into red when there are any errors or warnings. This doesn't necessarily mean it won't work but can be an indicator of somethings you must adjust.
6. You should see the &quot;General&quot; section as a starting point for your settings. By default all csv files that exists in wp-content/uploads are used to generate a table (*.csv).
7. Change *.csv to the name of the file you prepared in STEP2. 

= Local fetch =
8. **Set Path(local)** to csvtohtmlfiles or click on the button with three dots and select csvtohtmlfiles (if you have done previous steps you should see your folder csvtohtmlfiles here). **In File(s) combined to this table** type the file that you put into wp-content/uploads/csvtohtmlfiles
9. If an Excel-file is selected for fetch, then it is converted to a csv-file and the Excel-file remains intact (so there are two files that has identical data in the same folder). This may seem redudantant, but this is because the plugin fetches data from the csv-file and not from the Excel-file directly.

= External fetch =
8. **In File(s) combined to this table** put in your url that you prepared in STEP2. If your external file is a google spreadsheet, make sure it's publicy available for download and that you have the full uri to your googlesheets workbook. Also set &quot;add extension automatically&quot; to no.
9. If you have more files you want to combine, then just add a semicolon (;) and add filename (local or external)

**STEP4 - MAKE IT WORK**
10. Play around with settings until you're satisfied. In very few cases you should change character encodings but if you're having issues with encoding, you could try and change these settings.
11. Copy generated shortcode (above General section) and put the shortcode in a wordpress page or post to display table with the functionality you have set in the shortcode generator page.
12. If you don't like the default design of the table(s), use your theme's css to change the layout of your site/table etc.

If having issues that you cannot solve please send a mail to info@wibergsweb.se.
If you like the plugin, please consider donating. Please donate to paypal info@wibergsweb.se. Amount does not matter. I like coffee! :-)

**SHORTCODE**
If you feel comfortable by modifying settings directly in your shortcode: Down below are settings available and what they are intended for. Value within parenthesis show the default value for the setting, so if that setting/attribute is not included in the shortcode, the default value for the setting will be applied. 
The shortcode should always start with *[csvtohtml_create* followed by *settings/attributes* below. Every setting must have a space between and *]* must be set at the end of the shortcode.

Another option is to go into the shortcode generator again and repeat previous steps (from STEP2). 


== Frequently Asked Questions ==

= Is your tables responsive? =

The short answer is yes. From v1.60 the tables are responsive as default. If you want more advanced responsivity you have to modify css yourself.

= Why don't you include any css for the plugin? =

From v1.60 there are som basic css to make responsive table. It's possible to turn this css off if having issue though.
The goal is to make the plugin work as fast as possible as expected. By not supplying a lot of css the developer has full control over the design. If you know css, it's easy to apply your style on all tables / invidual tables etc. From version 1.6 there are also some templates to use.

= Is the plugin totally free? =

Yes it is. If you feel bad about using this plugin for free, please write a review and/or give a donation to PayPal info@wibergsweb.se.

= Is there a premium/pro version of the plugin available? =

No there's no need. This plugin offers a lot totally free. The plugin's existence depends on donations only.If you like the plugin, please consider donating. Please donate to paypal info@wibergsweb.se. Amount does not matter. I like coffee! :-)

== Screenshots ==

The screenshots are shown almost &quot;out of the box&quot; using the theme Emmet Lite. I'm using this css: 
.csvtohtml th.colset-1 {background:red !important;}
.csvtohtml .even{background:#ddd;}


1. Screenshot - file structure with folder nordic in upload-folder
2. Screenshot - [csvtohtml_create path=&quot;nordic&quot; source_files=&quot;denmark&quot; source_type=&quot;guess&quot; debug_mode=&quot;no&quot;] OR [csvtohtml_create path=&quot;nordic&quot; source_files=&quot;denmark.csv&quot; source_type=&quot;guess&quot; debug_mode=&quot;no&quot;]
3. Screenshot - [csvtohtml_create path=&quot;nordic&quot; source_files=&quot;*.csv&quot; source_type=&quot;guess&quot; debug_mode=&quot;no&quot;]
4. Screenshot - [csvtohtml_create path=&quot;nordic&quot; source_files=&quot;*.csv&quot; source_type=&quot;guess&quot; debug_mode=&quot;no&quot; filter_data=&quot;2010&quot; filter_col=&quot;1&quot;]
5. Screenshot - [csvtohtml_create path=&quot;nordic&quot; source_files=&quot;denmark;iceland&quot; source_type=&quot;guess&quot; debug_mode=&quot;no&quot;]
6. Screenshot - [csvtohtml_create path=&quot;nordic&quot; source_files=&quot;denmark;iceland&quot; source_type=&quot;guess&quot; debug_mode=&quot;no&quot;] with css.
**CSS**
.csvtohtml tr.rowset-7 .colset-2 {
    background: #27b399;
    color: #fff;
    font-weight:600;
}
7. Screenshot - [csvtohtml_create path=&quot;nordic&quot; source_files=&quot;denmark;iceland&quot; source_type=&quot;guess&quot; title=&quot;Nordic growth&quot; debug_mode=&quot;no&quot;]
8. Screenshot - [csvtohtml_create path=&quot;nordic&quot; source_files=&quot;denmark&quot; source_type=&quot;guess&quot; debug_mode=&quot;no&quot; sort_cols=&quot;2&quot; sort_cols_order=&quot;asc&quot;] AND [csvtohtml_create path=&quot;nordic&quot; source_files=&quot;iceland&quot; source_type=&quot;guess&quot; debug_mode=&quot;no&quot; sort_cols=&quot;1,2&quot; sort_cols_order=&quot;desc,asc&quot;]
9. Screenshot - [csvtohtml_create path=&quot;nordic&quot; source_files=&quot;nordic.csv&quot; source_type=&quot;guess&quot; debug_mode=&quot;no&quot; sort_cols=&quot;1&quot; sort_cols_order=&quot;asc&quot; title=&quot;Year&quot;]
**original csv (nordic.csv):**
,Finland,Iceland,Denmark
2013,&quot;10.2%&quot;,&quot;7.2%&quot;,&quot;1.4%&quot;
2014,&quot;11.0%&quot;,&quot;11.0%&quot;,&quot;1.9%&quot;
2010,&quot;2.5%&quot;,&quot;2.3%&quot;,&quot;2.8%&quot;
2011,&quot;-16.6%&quot;,&quot;6.6%&quot;,&quot;-6.6%&quot;
2012,&quot;-14.2%&quot;,&quot;6.2%&quot;,&quot;1.2%&quot;
2015,&quot;13.2%&quot;,&quot;16.2%&quot;,&quot;2.0%&quot;

10. Screenshot - [csvtohtml_create path=&quot;nordic&quot; source_files=&quot;nordic.csv&quot; source_type=&quot;guess&quot; debug_mode=&quot;no&quot; sort_cols=&quot;1&quot; sort_cols_order=&quot;asc&quot; title=&quot;Year&quot; exclude_cols=&quot;3&quot;]
11. Screenshot - [csvtohtml_create path=&quot;nordic&quot; source_files=&quot;nordic.csv&quot; source_type=&quot;guess&quot; debug_mode=&quot;no&quot; sort_cols=&quot;1&quot; sort_cols_order=&quot;asc&quot; title=&quot;Year&quot; exclude_cols=&quot;2,4&quot;]
12. Screenshot - [csvtohtml_create path=&quot;nordic&quot; source_files=&quot;nordic.csv&quot; source_type=&quot;guess&quot; debug_mode=&quot;no&quot; sort_cols=&quot;1&quot; sort_cols_order=&quot;asc&quot; title=&quot;Year&quot; include_cols=&quot;1,3-4&quot;]
13. Screenshot - [csvtohtml_create path=&quot;nordic&quot; source_files=&quot;nordic.csv&quot; source_type=&quot;guess&quot; debug_mode=&quot;no&quot; sort_cols=&quot;1&quot; sort_cols_order=&quot;asc&quot; title=&quot;Year&quot; include_cols=&quot;1,3-4&quot; fetch_lastheaders=&quot;2&quot;]
14. Screenshot - [csvtohtml_create path=&quot;nordic&quot; source_files=&quot;nordic.csv&quot; source_type=&quot;guess&quot; debug_mode=&quot;no&quot; sort_cols=&quot;1&quot; sort_cols_order=&quot;asc&quot; title=&quot;Year&quot; table_in_cell_cols=&quot;2,3,4&quot;]
15. Screenshot - [csvtohtml_create path=&quot;nordic&quot; source_files=&quot;nordic.csv&quot; source_type=&quot;guess&quot; debug_mode=&quot;no&quot; sort_cols=&quot;1&quot; sort_cols_order=&quot;asc&quot; title=&quot;Year&quot; table_in_cell_cols=&quot;2,3,4&quot; include_cols=&quot;1&quot; table_in_cell_header=&quot;Countries&quot;]
16. Screenshot - [csvtohtml_create path=&quot;nordic&quot; source_files=&quot;nordic.csv&quot; source_type=&quot;guess&quot; debug_mode=&quot;no&quot; sort_cols=&quot;1&quot; sort_cols_order=&quot;asc&quot; title=&quot;Year&quot; table_in_cell_cols=&quot;2,3,4&quot; include_cols=&quot;1&quot; table_in_cell_header=&quot;Countries&quot;]
**CSS**
.csvtohtml .extra-data::before {content: &quot;Hover here for more info&quot;;}
.csvtohtml .extra-data table {visibility:hidden;}
.csvtohtml .extra-data:hover table {visibility:visible;}

17. Screenshot - Same as 16 but when hovered on a specific row(year)


== Changelog ==

= 3.04 =
Total nr of lines can now be calculated and shown instead of calculated total values (with setting totals_cols_bottom_countlines="yes")
To avoid warnings: The plugin now checks if a directory exists first before creating. This is used when fetching data from an exernal site with intervals (Before it just suppressed the warning which work differenly on different webservers)

= 3.02 =
File structure / location of referencelist changed
Minor adjustment instruction text shortcode generator

= 3.00 =
Bugfix: Removed further deprecated warnings from PHP >= 8.0 of function structure in some contentids
Excel-files now acceptable to upload directly with import-function
Import function using temporary files with %temp%. They will be copied into examples folder of the plugin. They will deleted upon next update.

= 2.97 =
Bugfix: Removed deprecated from PHP >= 8.0 of function structure in some contentids

= 2.96 =
Warnings appeared in some cases using external files. These are removed.
Editable inactivated by default when using external files (External files are not editable)
In the shortcode generator, there are now a scroll for the actual shortcode being generated when needed.
Bugfix: When adding first filename to path through button, no semicolon is added in the beginning
Bugfix: Shortcode-generator not removing ampersand characters from external files with arguments anymore
IMPORT-functionality introduced. It copys an uploaded file to the uploads-folder. Useful if you don't have direct access to this folder (through ftp etc).

= 2.90 =
Link to the Shortcode generator is added to the plugin list page
A referencelist is avaialable directly within the plugin (in the shortcode generator)

= 2.88 =
A new sourcetype is implemented: guessonecol, this type sets one column (based on same format as guess). Set type title to set a custom header
Custom title now included in the shortcode generator
Delimiter in shortcode generator can now be set to five characters (instead of one)
A new attribute (skip_headerrow) to tell if the headerrow should be skipped (not shown) or not.
A new attribute (headerrow_exists) to tell if there are an actual headerrow in the source file(s). This attribute applies to sourcetypes guess and guessonecol

= 2.82 =
Markdown support now don't add paragraphs to it.

= 2.81 =
Now it's possible to preserve original filter when doing a search (preservefilter_search)
With a new attribute (show_onlyloggedin) you could show table(s) only if you're logged in.
A new filter operator is added that indicates NOT equal (nequals).
Markdown-support for text in cells. Set markdown_support to yes. Uses library: [Parsedown](https://github.com/erusev/parsedown 'Parsedown').

= 2.72 =
Bugfix search (ordinay search). Searches in all columns as default (as it did before introduction of multi-filters)

= 2.70 =
Multi filters introduced! You're now able to combine different logics to filter the data you need.
(using filter_criterias and filter_data and filter_operators in combination)
is_empty is a new operator that can be used to filter based on if a column is empty

= 2.62 =
Now it's possible to filter one or more sheets when fetching content from an excel-file (selected_sheets).

= 2.61 =
**IMPORTANT!** Table layout defaults to NOT fixed (it's too hard to get a table looking good without this default setting, therefore this is a new default setting)
Support for more graphic format (webp, jpeg (jpg)) when autoconverting into html
When searching and highlight/autoconversion is active:
* Keeping format of links and images 
* Alt values with spaces are now handled correctly
* Image is showing instead of the actual path to the image.
* If something in image-name is found a border/background is set with the highlight color.

= 2.55 =
Bugfix when changing order of displayed columns and using merge and column-names
Update of shortcode-generator to handle hide-columns attribute

= 2.53 =
Bugfix when using merging columns into links in combination with inclusion or exclusion of columns.
Added attribute hide_cols which make it possible to hide column(s) (but still including them in the result). Useful when mergin two columns (you may not want one of the original columns to display but it must be included in the table)
Ignores spaces in beginning of search-field (e.g. "      crowbar" is equal to "crowbar")

= 2.50 =
Alt-value (htmltags_autoconvert_imagealt) now support to combine columns and other text (e.g. "This is a profile image of",First Name")

= 2.49 =
Bugfix generating correct alt-value for specific row(s)
Alt-value now support for several columns (e.g. htmltags_autoconvert_imagealt="First Name,Last Name")

= 2.47 =
Added possibility to use fixed width of images when converted into images
htmltags_autoconvert_imagealt support for specifying column as name instead of number

= 2.45 =
Bugfix: When using sticky header, the top left header is not fixed anymore when fixed_leftcol is set to no.
Added header_textcolor and header_textcolor_left
%userid and %userlogin - special variables for filter by userid or username in filter_data
%userid - special variable for use within path

= 2.40 =
Specify column name(s) instead of column numbers when specifying columns in any kind of filtering attribute:e.g. exclude_cols, include_cols, sort_cols,filter_col etc.
Show columns in which order you want to (doesn't have to be in the same order as the file(s) has) 
The plugin identifys whatever column-name you have (even if it's numbers or contains spaces).
(e.g. type include_cols="my age, gender" instead of include_cols="4,3").,
Speed improvement on rendering, especially when there are a more than one table on the page

= 2.22 =
fixed_leftcol can now be used without header sticky or fixed.

= 2.21 =
Pointing to correct templates css. Small but import update!

= 2.20 =
Minor changes regarding to the outofthebox1 design template and processor-indicator when user is sorting a column

= 2.19 =
Table width is now possible to set even if no header type (sticky/fixed is set 
Added vw as a possible unit when using table_width
(Although it is possible. Using CSS in the wordpress child's theme is a far better option)
Bugfix regarding to searching when having htmltags_autoconvert set to yes, having images and doing a search. 
Slightly faster rendering when using htmltags_autoconvert
Possible to use different background colors on the fixed left column and header row of the table
Bug regarding to usage of "grab_content..." and htmltags_autoconvert from another column and create link now fixed. 
New attribute added grabcontent_col_tolink_addhttps - this adds https instead of a link (instead of google.se it would be https://www.google.se/)
Outofthebox1 template changed input width to auto, so the input would not automatically be 100% (or whatever is set in the theme's css) of it's parent
Duplicate values of some settings created in the shortcode generator (now fixed)

= 2.11 =
Now correct js and css included!!! (UPDATE to this if you just updated to 2.1)

= 2.1 =
Outofthebox1 is a new design template designed for a ready to use table. This is default now. If you don't want to use any design template, simply do design_template=""
Sorting is now done on all of the file( or combined files) that occurs in the table (even those rows that arent visible)
Bugfix pagination: If user were "to quick when clicking previous or next or first or last" then it could mess up order of the rows in the table
Fixed table-layout for any csvtohtml-table is now default for faster rendering (width of first rows are used, other rows = no need for calculation)

= 2.0.64 =
Bugfix responsive table ( when responsive is set to default(yes) ) when using fixed and sticky columns. Now displaying all columns and values when minimized.

= 2.0.63 =
Bugfix sorting on first column when using fixed left column.

= 2.0.62 =
Bugfixes regarding to combining fixed_leftcol and user sorting. 
Now sorting on correct column (when user sorting is active) and fixed_leftcol is set to "yes"

= 2.0.61 =
Bugfix: arrow not shown now on the left column when using fixed left col

= 2.0.6 =
Sticky top header and left column (at the same time) working on several themes (not just default Wordpress themes)

= 2.0.5 =
Sticky and fixed headers improved
Table width are now possible to specify
Table height and width as support for all standard units css (%, em, rem, px, vw (width), vh (height))
Fixed left column introduced (fixed_leftcol)
Offset of header introduced (table_offset_header)

= 2.0 =
Search and filter functionality improved
- Filtering can now be done on one ore unlimit number of columns (e.g. filter_col = "1-2, 5-10, 13 etc but still same filter_data)
- Searches with match(es) display rows from all columns and not just one (Before all columns were searched but only one column was filtered out)
- It's possible to search in the whole file(s) when doing a search (search_excludedrows = "yes") even if include_rows is set (e.g. search on data in all 20 rows would the attribute ignore include_rows="1-5")

New attribute that returns how many visible rows are returned on search programmatically: return_found (yes/no) (advanced programmers)

Bugfix in shortgenerator regarding to search in columns-functionality
Bugfix in shortgenerator regarding to inclusion of columns and html conversions

Some explainatory guidelines added in shortcode generator

= 1.9.9 =
Bugfixes regarding to sort_cols_userclick and sort_cols_userclick_arrows

= 1.9.8 =
Search highlighting (search_highlight="yes") - new feature (yellow default color, can be changed by using search_highlightcolor)

= 1.9.7 =
Fixed headers (requires height of table) - new feature
Sticky headers (fixed on scroll) - new feature
Table height (set fixed height) - new feature
It's now possible to change background-color of header through attributes (looks better with a solid background color when using a header type) - new feature
Fixed some spelling in the shortcode tool area
Some minor related deprecated issues fixed for PHP 8.1.

= 1.9.1 =
Specific autodetection (ini_set()) syntax of line-endings removed because this is deprecated from PHP 8.1. PHP should be able to handle this by it's own.
Other PHP 8.1 related deprecated issues fixed.
Attribute added (large_files) to use less memory when loading csv-file(s).

= 1.8.8 =
Now it's possible to fetch content(link) from a specific column and use this link as a wrapper for another columns content.
Bugfix - get_defaults() generating an error in console when user not logged in

= 1.8.6 =
Grouping is now done after sorting, so grouper heading is not included when sorting is applied
Groupheading class is added on the tablerow for each groupheader section

= 1.8.4 =
It's now possible to group by values based on a specific column
The possiblity to add a header or not for each grouping section 

= 1.8.2 =
Shortcode generator bugfix

= 1.8.1 =
Exact match searching is now available
Case insensitive search and filtering is now possible 
Possible to search in given columns only

= 1.7.8 =
Added possibility to have arrows in header columns.

= 1.7.7 =
Bugfix using external files with parameters (such as google documents) for searching/pagination fixed.

= 1.7.6 =
Bugfix sortable user click: Now it works if you have setting sort_cols_userclick=&quot;yes&quot; on serveral tables on the same page. It also trims text so sorting is more accurate.

= 1.7.5 =
Files with .xlsx (Excel) and .ods are now to supported (to load from / source_files) with help from Spout library. 
It's now possible to have autosort for user (user clicks on a column and sort the column ascending or descending)
You can now tell what row in file(s) to start from (where headers should start)
It's now possible to filter rows that has same date as today or newer (filter_operator = newdates). It's also possible to filter dates from a specific date.
Shortcode generator (located in Tools/CSV to HTML) is updated with above functionality

= 1.7 =
Renamed css-rules for shortcut generator to avoid conflicts with users css
Adjusted some template's css (headers left aligned)

= 1.6.8 =
Added settings regarding to totals in the shortcode generator (these attributes was available before but not in the shortcode-generator)
Clarified some settings in descriptions of settings

= 1.6.3 =
Bugfix javascript, warning in javascript-console when using inputs not related to this plugin.
Bugfix shortcode generator when selecting source type
No warnings generated from json source type when fetching non-json file

= 1.6 =
A totally new shortcode generator is now available to be able to create shortcodes and seeing a preview of the result while creating it. Just go into the menu tools/CSV To HTML and play around with it!

= 1.3.5 =
When user hits enter in search box it acts same way as it does when you click on search-button

= 1.3.4 =
Clarifications in debugging mode and some spell/grammar fixes. Now a given shortcode (shortest possible) is given for testing.

= 1.3.3 =
Removed warning issue of editable file (when not using editing)

= 1.3.2 =
Bugfix - Also at resetting - show table correctly again

= 1.3.1 =
Bugfix - important update for them who uses search functionality! Displaying correctly after search now.

= 1.3 =
Editable values in local csv-file(s) directly in the browser. This makes it smooth to update csv-file(s) without using any other texteditor. 
Faster loading of csv-files

= 1.2.88 =
Possible to add percentage value for a specific column and show that both above and below table. Some improvements regarding to debug-functionality.

= 1.2.80 =
Characters are now ignored at calculation (e.g. €56 will turn into 56 and 24€ will turn into 24. These numeric values will be used for the actual calculation of the column)

= 1.2.79 =
IMPORTANT! Totals not showing correctly before. Now this is fixed!
It's now possible to add char(s) to the total column(s) - before (e.g. $10) or after (e.g.) 

= 1.2.75 =
Some added design templates (premium version only)

= 1.2.74 =
Preparation before upcoming Premium version of the plugin

= 1.2.73 =
Handling of empty data better together with debuginfo. Only &quot;silent&quot; error now when using debug when no data avaialable. 

= 1.2.72 =
Warning of html_id removed (when checking local tablefiles)

= 1.2.71 =
Important update! Bugfix: First two columns should not be duplicated now

= 1.2.70 =
Possible to save tables temporarily and update from source once,hourly, daily or weekly (If set to once, it only retrieves data from source once and then always fetches from file on server). It would make most sense to use this together with external files.
New source_type json added (It's now possible to fetch json content from file(s))
Another way of fetching jsondata is added because of some issues with cloudflare and wp_remote_get()
Added some checks to make debugging even easier

= 1.2.64 =
Important update! Bugfix: Pagination will work together will filter_operator equalsurl.

= 1.2.63 =
A new operator is added for filter_operator and it is called equalsurl. When fetching data from url, spaces often becomes hyphens(-) or underscores(_) and this setting solves this by seaching for all necessary combinations (with spaces between words, with hyphens and with underscores). Filter is applied in a case insensitive manner. If filter_data_ucfirst is set to yes, the filter_operater overrides this value.

= 1.2.62 =
Filter data can now be set by part of url (e.g. domain/part1/part2/part3). The use of %urlparts-X% makes this possible where X indicates level in path hiearchy (1,2 or 3). If X is set to &quot;last&quot; it would be automatically fetch last level (part3).
Filter data's first letter could be automatically set to upper case (Useful in combination with %urlparts-X%)

= 1.2.60 =
It is now possible to export a csv-file when clicking on a button under the table. The download process does not require any temporary file creations.

= 1.2.58 =
Possible to use %userlogin% for creating a dynamic path based on loggedin user, e.g. users/%userlogin%. 

= 1.2.57 =
Ignore error when using Premium version (>0.96) in combination with &quot;Gutenburg&quot;.

= 1.2.56 =
Ignore error when entering incorrect values for table in cell columns

= 1.2.55 =
Bugfixes: Some warnings are ignored when including files when not are avaialable, when using incorrect encoding etc
Compability fix for CSV to HTML Premium (Premium is only in beta stage for now)

= 1.2.53 =
Bugfix: Include last row when fetching external files when using source_type guess
Debugging-class improved for translation/internationalisation
More hints what could be going wrong when debugging

= 1.2.5 =
It's now possible to easily add a totals row under the bottom of a table and tell which columns that should be included
You can also add a custom title to show , e.g. TOTAL at specific column

= 1.2.1 =
Added possibility to set alt-description based on a specific columns value (or a fixed alt-value for all images)

= 1.2 =
Bugfix: fixed issue when realtime search active and when typing to fast or searching while plugin already searching
Realtime search attribute now works invidually for different tables (so one table can have realtime search and another not on the same page)
Autoconversion of url's, images and emails to html-tags are now possible with attribute settings
With autoconversion it's also possible to set if links should open in a new window or not

= 1.1.96 =
Possible to have an empty table when clicking reset button and not only at first pageload. 
Show a custom message when search by user did not give any results. This is within a span which makes it easy to style with css.
Set a required length of characters at search
Set a specific message when required length length of characters at search is not valid
New attribute to add placeholder to search inputfield
It's now possible to search in &quot;realtime&quot;, e.g. search starts when user enter characters in search input field directly
debug-attribute alais for debug_mode (because of some people enters debug instead of debug_mode)

= 1.1.89 =
Possible to have an empty table at pageload (when doing search it will get filled with appropiate result). 

= 1.1.88 =
Added a function for converting Windows-1255 characters to UTF-8 (this is not builtin into PHP mb_convert_encoding - function).

= 1.1.87 =
Bugfix responsive - relationsship between column and rows works indenpently of source_type given and 
managaging several tables on same page with different number of columns now works.

= 1.1.86 =
Textdomain has changed from csvtohtml-wp to the expected csv-to-html for correct internationalisation.

= 1.1.85 =
filter_operator has a new option: wildcard which filters data from a string within a substring
Search functionality implemented: 
An input search field, a search button and a reset button is created 
Easy configurable texts for the buttons
The buttons are easily styled because of classes set for each button
Searchresult is shown directly without reloading the page
Bugfix: When excluded columns responsive tables &quot;out of the box&quot; was in some cases not working as expected. Now it is.

= 1.1.78 =
Pagination implemented: 
It's possible to show pagination first, previous, next or last and specific links in between. 
Easy configurable texts and number of specific links to show. 
You can also specify if the pagination should be above or below table or both. If having pagination for several tables 
The pagination is separated accordingly and the page is not reloaded when browsing through tabledata. 
If javascript is not applicable then pagination could be used but only for one specific table and the page would be reloaded

= 1.1.61 =
If having more than one table an automatic html id is generated so responsive css rules can be generated invidually.

= 1.1.60 =
Responsive tables &quot;out of the box&quot;. It adjusts table view automatically based on different resolutions/devices. 
It's also possible to adjust mediaquery/css breakpoints. 

= 1.1.55 =
Possible to tell which rows to include (in the same format as include_cols)

= 1.1.54 =
Possible to apply filter in different ways such less then, more than , less or equal then, more or equal then or between
Extended debug-functionality separated from core.
Many thanks to g.deepwell for making this update possible! ( <a href="https://wordpress.org/support/users/gdeepwell/" target="_blank">https://wordpress.org/support/users/gdeepwell/</a> )

= 1.1.52 =
Apply a new filter on all rows based on a specific string from a specific column.
It's now possible to include a subset of the whole table in a cell where you specify columns that should be included. This data is added to the end of the table. Combined with css this could be used for hiding/showing data.
Added more useful debug-functionality for getting the plugin running even quicker
Many thanks to g.deepwell for making this update possible! ( <a href="https://wordpress.org/support/users/gdeepwell/" target="_blank">https://wordpress.org/support/users/gdeepwell/</a> )
Plugin tested and verified for use with version 5.6 of Wordpress

= 1.1.46 =
Better performance when not debugging due to debugging code loaded when neccessary.
A new attribute has been added so float numbers fetched from csv-files could be shown with another character instead of dots (probably comma)

= 1.1.44 =
A new attribute has been added so .csv does not have to be added automatically. This could be useful when loading files from Google Drive or similar cloud based services.

= 1.1.43 =
A html-tag that (for some reason) has been inserted by mistake. It's now corrected. Validation of html table should now validate.

= 1.1.42 =
Some missing tag in table corrected (/tbody and id-attribute(when used) corrected). 

= 1.1.41 =
Debug code removed. (update to this version if you tried to install/update to 1.1.40!)

= 1.1.40 =
Changed autodetection of EOL to auto(detect) (from cr/lf) when importing CSV-files.

= 1.1.39 =
Bugfix: Display order of columns when including, excluding is fixed. (In some circumstances the display order of columns would be inccorect before)

= 1.1.38 =
CURL is not used anymore for fetching external files. Instead native Wordpress HTTP API is used. Why? It's faster and handles security (with ssl) much better. As a fallback CURL is used by the HTTP API.

= 1.1.37 =
Bugfix admin page. Important to update to this version! (if you have installed 1.1.36)

= 1.1.36 =
Help-page introduced in wordpress dashboard
Debugging functionality improved
CURL bugfix when mixing http and https - protocols.
Possible to disable/ignore curl if something goes wrong (even if CURL is installed)
Now it's possible to use wildcards (to grab all csv-file from a specified path for example) 

= 1.1.31 =
Bugfix sorting on columns when both asc and desc are used

= 1.1.3 =
Sorting of columns are now possible. It's also possible to define different sortorders depending on which column is sorted.

= 1.1.1 =
Now it's possible to convert(translate) characterencoding from csv to another charset (for display)

= 1.0.9 =
Now it's possible to use &quot;last&quot; instead of entering a specifing column-number when excluding a column.

= 1.0.8 =
Now it's possible to use a custom delimiter for each row in a csv file.

= 1.0.7 =
Autodetection of line endings now supported. Works automatically on local files (on server)
Possible to specifiy custom line endings when loading external links (when CURL is enabled)

= 1.0.5 =
Now it's possible to specify an extra class for the table. Example: If used together with the tablesorter plugin (https://wordpress.org/plugins/table-sorter/) you can add a class called tablesorter and combine these two plugins to
create a &quot;tablesorted&quot; html table generated from csv file(s) in realtime.

= 1.0.4 =
Now it's possible to include or exclude columns in format 1,2,3 or 1-3. It's also possible to combine these two eg. 1,2,3,7-9,13,14,15.

= 1.0.2 =
Semantic bugfix. When not defining a path an extra slash was included in the path. Now there are no extra slashes.
Now it's possible to include a full url to fetch csv-files from external sources. CURL are used to fetch csv files from external sources, but if CURL is not installed, then php's file() function is used but that requires that the server  
has allow url fopen enabled.

= 1.0 =
Plugin released


== Upgrade notice ==
When using IMPORT-functionality ALL files with the same name will be overwritten!
Please tell me if you're missing something (please mail info@wibergsweb.se for fastest reply) ! I will do my best to add the feature.
