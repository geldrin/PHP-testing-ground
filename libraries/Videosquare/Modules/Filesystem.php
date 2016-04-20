<?php
namespace Videosquare\Modules;

include_once( BASE_PATH . 'libraries/Springboard/Filesystem.php');
    
class Filesystem extends \Springboard\Filesystem { 

    public static function findFilesByPattern($directory, $type = "d", $pattern = "*") {
        
        if ( empty($directory) or empty($type) or empty($pattern) ) throw new \Exception("[ERROR] Empty directory or type");
    
        $find_command_template = "find %s -mindepth 1 -maxdepth 1 -type %s -name '%s' -printf '%%p\n'";
        $command = sprintf($find_command_template, $directory, $type, $pattern);
        //echo $command . "\n";
        
        exec($command, $files, $result);

        if ( $result != 0 ) return false;
        
        for ( $i = 0; $i < count($files); $i++ ) {
            $pathinfo = pathinfo($files[$i]);
            $files[$i] = $pathinfo['basename'];
        }
        
        return $files;
    }
    
    public static function isFileClosed($file) {

        if ( empty($file) ) throw new \Exception("[ERROR] Input file name empty");
    
        if ( !file_exists($file) ) return false;
    
        $command = "lsof " . $file . " 2>&1 > /dev/null";
        exec($command, $output, $result);
    
        if ( $result != 1 ) return false;

        return true;
    }

}