<?php

/*
 * Used in the templates as {function:var1, var2}, these functions should return some string,
 * typically a formatted string or something
 * 
 * If you touch a databse or even anything else complex here here, burn in hell
 * 
 * Look at these functions as CONTROLLERS, so delegate to the model as much as possible!
 */

class TemplateExtensions 
{

    //call this function writing {{randomShit:somevarname}} in the template
    public static function randomShit($var)
    {
        return "RANDOM SHIT TEST - VAR RECEIVED: $var";
    }
    
    //will make long strings shorter to something with ... after it
    //accepting multiple args so we can pass it {{ellips:3,firstname,lastname}} for example
    public static function ellips($string, $maxcharcount)
    {
        if (strlen($string) <= $maxcharcount)
        {
            return $string;
        }
        else
        {
            return substr($string, 0, (int) $maxcharcount - 2) . '...';
        }
    }
    
    public static function day($timestamp)
    {
        return date('Y-m-d', strtotime($timestamp));
    }
    
    public static function formatDate($date)
    {
        return date('j M', strtotime($date));
    }
    
    public static function actionstate($succesOrFail, $message)
    {
        $state = $successOrFail ? 'success' : 'fail';
        $class = $successOrFail ? 'success' : 'error';
        
        return '<!-- IF actionstate="' . $state . '" --><div class="' . $class . '">' . $message . '</div><!-- ENDIF -->';
    }
}
?>