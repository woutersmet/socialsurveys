<?php


Class Configuration
{

	public static function get($key, $subkey)
	{
            require('configuration.inc.php');
            
            if (isset($vars[$key]))
            {
                if (isset($vars[$key][$subkey]))
                {
                    return $vars[$key][$subkey];
                }
                
                Debug::error("CONFIG SUBKEY $subkey FOR KEY $key NOT FOUND");
                return false;
            }
            else
            {
                Debug::error("CONFIG KEY $key NOT FOUND!");
                return false;
            }
	}
}

?>