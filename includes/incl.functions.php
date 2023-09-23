<?php 
$dirs = [];
$files = [];
function getLibs ($Dir = 'lib', $l = 2) {
    global $dirs;
    global $files;
     $dir = opendir($Dir);
     while (false !== ($f = readdir($dir)) && $l):
         if ($f !== "." && $f !== ".." && $f !== "ignore" && $f !== "modules") {
             if ( is_dir( "$Dir/$f" )) {
                 $dirs[] = "$Dir/$f";
                 getLibs("$Dir/$f", $l - 1);     
             } else {
                 $break = explode('/', $Dir);
                 $s = '';
                 foreach ($break as $b) $s .= "['". preg_replace("/[^a-z0-9]/i", "", $b) ."']";
                 $ext = explode('.', $f); 
                 $ext = $ext[count($ext)-1];
                 $eval = $s . "['$ext'] = '$Dir/$f';";
                 eval('$files' . $eval);
             }
         }
         
     endwhile;
 }

 function getFlags ($limit = 0, $from = 0, $Dir = 'flags') {
     $flags = [];
     $dir = opendir($Dir);
     while (false !== ($f = readdir($dir)))
         if ($f !== "." && $f !== ".." )
             if ( !is_dir( "$Dir/$f" )) { 
                $file = explode('.', $f); 
                $flags[] = array("name" => $file[0], "flag" => "$Dir/$f");
             }
         
        $count = count($flags);
     return [array_slice($flags, $from, $limit), $count];
 }

 function sortit(&$arr, $col, $dir = SORT_ASC) {
    $sort_col = array();
    foreach ($arr as $key=> $row) {
        $sort_col[$key] = $row[$col];
    }

    array_multisort($sort_col, $dir, $arr);
} 
?>