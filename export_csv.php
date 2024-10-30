<?php
/*
    Create a downloadable csv-file from an array
    with specified delimiter
*/
if (!empty($_POST)) 
{
    $array = $_POST['itemdata'];
    $delimiter = $_POST['delimiter'];
    $filename = $_POST['filename'];

    // open raw memory as file so no temp files needed, you might run out of memory though
    $f = fopen('php://memory', 'w'); 

    //Create content in memory
    foreach($array as $it) {
        fputcsv($f, explode($delimiter, $it), $delimiter);      
    }

    //Create download functionality and send download-action to browser
    fseek($f, 0);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'";');
    fpassthru($f);
}
?>