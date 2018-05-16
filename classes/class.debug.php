<?php 
class debug {

    private static $debugLog = Array();
    private static $dumpAllOutput = false;
    
    public static function setDebuggingEnabled()
    {
            $_SESSION['debugmode'] = 'ON';

            /*** error reporting on ***/
            error_reporting(E_ALL);
            Debug::log("Init debug...");
    }

    public static function enableOutputDumping()
    {
        self::$dumpAllOutput = true;
    }
    
    public static function setDebuggingDisabled()
    {
            unset($_SESSION['debugmode']);
    }

    public static function debuggingIsEnabled()
    {
        //return true; //@WOUTER always debugging for now
        $result = isset($_SESSION['debugmode']) && $_SESSION['debugmode']=='ON';
        return $result;
    }

    public static function wasHere($string = false)
    {
                    if (self::debuggingIsEnabled())
                    {
                            try
                            {
                                    $traceArray = debug_backtrace(false);

                                    $file = explode('/',$traceArray[0]['file']);
                                    $fileName = $file[count($file)-1];
                                    $args = $traceArray[1]['args'];
                                    $argString = implode(',', $args);

                                    if (is_array($string))
                                    {
                                            $string = '<pre>' . print_r($string, true) . '</pre>';
                                    }
                                    $additional = $string ? "Extra info: $string" : '';
                                    die("WAS HERE [$fileName | LINE ".$traceArray[0]['line']." | ".$traceArray[1]['class']."::".$traceArray[1]['function']."($argString)] $additional");
                            }
                            catch(Error $e)
                            {
                                    return "Error finding WAS HERE location: " . print_r($e);	
                            }
                    }
                    else
                    {
                            return false;
                    }
    }

    public static function customError($errno, $errstr, $errfile, $errline, $errcontext)
    {
        self::log("Error in file $errfile at line $errline:</b> [$errno] $errstr", false, 'error');
    }

    //debugmode-only vardump
    public static function var_dump_($something)
    {
            if( self::debuggingIsEnabled())
            {
                    var_dump($something);
            }
    }

    public static function startDebugging()
    {
        self::$debugLog = Array();

        self::$debugLog['sysInfo'] = array('QueriesWithCache' => 0, 
                                        'QueriesWithoutCache' => 0
                                        );

        self::log('Started debug log...');

        Debug::log($_POST,'POST');
        Debug::log($_GET,'GET');
        Debug::log($_SESSION,'SESSION');
        Debug::log($_FILES, 'FILES');
        
        $cleanedCookie = array(); //quickskin gives trouble about serialized objects in cookie
        Debug::log("Replacing { with < in cookie to not confuse quicksin parser DIRTY");
        foreach ($_COOKIE as $key => $value)
        {
            $cleanedCookie[$key] = str_replace('{', '<', $value);
        }
        Debug::log($cleanedCookie, 'COOKIE');
    }

    public static function logQueryWithCache()
    {
            if(self::debuggingIsEnabled())
            {
                    self::$debugLog['sysInfo']['QueriesWithCache'] ++;
            }
    }

    public static function logQueryWithoutCache()
    {
            if(self::debuggingIsEnabled())
            {
                    self::$debugLog['sysInfo']['QueriesWithoutCache'] ++;
            }
    }

    public static function getSystemInfo()
    {
            return isset(self::$debugLog['sysInfo']) ? self::$debugLog['sysInfo'] : false;
    }
    
    //fucker keeps thinking anything of the form { : } is an extension
    //NOT NEEDED ANYMORE WE REPLACED THE EXTENSION DEFINITION TO INCLUDE TWO DELIMITERS NOT ONE {{}}
    public static function doDirtyQuickskinSanitization($message, $toggletitle)
    {
        if ($toggletitle)
        {
            $toggletitle = str_replace('{', '<', $toggletitle);
        }

        if(is_array($message))
        {
            foreach ($message as $key => &$value)
            {
                if (is_array($value))
                {
                    foreach ($value as $valuekey => &$valuevalue)
                    {
                        $valuevalue = str_replace('{', '<', $valuevalue);
                        
                    }
                }
                else
                {
                    $value = str_replace('{', '<', $value);
                }
            }

        }
        else
        {
            $message = str_replace('{', '<', $message);
        }        
        return array ($message, $toggletitle);
    }

    public static function log($message,$toggletitle = false, $formatting = 'normal')
    {
        if (!isset(self::$debugLog))
        {
                self::startDebugging();
                Debug::error("DEBUG LOG CALLED BEFORE DEBUGGING INIT (actually, is this a problem?)");
        }
        
        //list($message, $toggletitle) = self::doDirtyQuickskinSanitization($message, $toggletitle);
        
        //format arrays nicely
        if(is_array($message))
        {
            $message = print_r($message,true);
        }

        //put in log array
        if(self::debuggingIsEnabled())
        {
                $timing = round(1000*(microtime(true) - __SITE_STARTTIME),3);// get time sinds pageload in ms
                
                if (self::$dumpAllOutput)
                {
                    $fullitem = ($toggletitle? $toggletitle . ":\n" : '') . $message . "\n";
                    echo '<pre>' . print_r($fullitem, true) . '</pre>';
                }
                else
                {
                    $fullitem = Array('message'=>$message,
                                                                                    'backtrace'=>debug_backtrace(),
                                                                                    'toggletitle'=>$toggletitle,
                                                                                    'formatting'    => $formatting,
                                                                                    'timing'=>$timing);
                    self::$debugLog[] = $fullitem;
                }
        }
    }

    public static function error($message,$toggletitle = false)
    {
        /*
        if (!Util::isLocalEnvironment())
        {
            Logging::logEvent('ERROR', $message . "\n\n" . print_r($toggletitle, true));
        }
        */
     
        //mail('woutersmet@gmail.com', "[BARLISTO] error: $message", "[SHUTDOWN] file:".$error['file']." | ln:".$error['line']." | msg:".$error['message'] .PHP_EOL);   
        self::log($message,$toggletitle, 'error');
    }

    public static function alert($message,$toggletitle = false)
    {
            self::log($message,$toggletitle, 'alert');
    }	

    public static function logQuery($message, $toggletitle = false)
    {
            self::log($message, $toggletitle, 'query');
    }

    public static function h1($message,$toggletitle = false)
    {
            self::log($message,$toggletitle, 'h1');
    }

    public static function getDebugLog()
    {
            $log = self::$debugLog;
            //Debug::wasHere($log);
            return $log;	
    }

    public static function getLogMarkup()
    {

            $log = self::$debugLog;

            $r = '';
            foreach($log as $line)
            {
                    $trace = self::formatBacktrace($line['backtrace']);
                    $r.= $line['timing'].'ms  '; // include timing
                    //generate trace link
                    $randomstring1 = rand(0,10000);
                    $r.= "<a href=\"#\" onClick=\"$('tracestring_$randomstring1').toggle();return false\">+</a>\n";
                    //display with toggle if there's one
                    if($line['toggletitle'] && $line['toggletitle'] != 'none')
                    {
                            $title = $line['toggletitle'];
                            $randomstring2 = rand(0,1000);
                            $r.= "<a href=\"#\" onClick=\"$('bugstring_$randomstring2').toggle();return false\"><b>$title</b></a>\n";
                            $r.= "<div id=\"bugstring_$randomstring2\" style=\"display:none\"><pre>".htmlentities($line['message'])."</pre></div>\n";	
                    }
                    else
                    {
                    $r .= $line['message']; 
                    }
                    //trace
                    $r.= "<div id=\"tracestring_$randomstring1\" style=\"display:none\"><pre>".$trace."</pre></div>\n<br>";	
            }

            //replace individual chars by their game unit icon for less boring game mechanics debugging
            $r = preg_replace('/\s([stadfhmcwqijuvpe])\s/','<img src="templates/img/unitgraphics/tiny_$1.png">',$r);

            return $r;
    }

    public static function formatBacktrace($backtrace)
    {
            $r = '';

            $counter = 0;
            foreach($backtrace as $step)
            {
                    if($counter > 0) //first part of trace is always about the logging itself
                    {
                        $pathparts = array_merge(array('..','.'),explode('/',$step['file']));
                        $file = $pathparts[count($pathparts) - 2].'/'.$pathparts[count($pathparts) - 1];
                        $r .= "file: ".$file." line ".$step['line']."\n";
                        $r .= "function: ".$step['function']."(".implode('\',\'',$step['args']).")\n\n";
                    }
                    $counter ++;
            }
            return $r;
    }
}
?>