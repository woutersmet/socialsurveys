<?php

Class Util
{

	private static $workingInAjaxMode = false; //needed to add ajax=1 to forwards

	public static function setInAjaxMode()
	{
		self::$workingInAjaxMode = true;
	}
        
        public static function isAjaxRequest()
        {
            $isajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            
            Debug::log("Ajax request?" . $isajax ? 'yes!' : 'nope');
            return $isajax;
        }

        //shit that can be shown in the browser
        public static function santizeAjaxOutput($string){
            $string = str_replace('<', '&lt;', $string);
            $string = str_replace('>', '&gt;', $string);

            return $string;
        }

        public static function show404()
        {
            header("HTTP/1.0 404 Not Found");
             //Debug::error("Showing 404 page! Current location: " . $_SERVER["HTTP_HOST"].$_SERVER['REQUEST_URI'] . "\n\rGet: " . print_r($_GET,true) . "\n POST: " . print_r($_POST, true)); 
            die(include('templates/404.html'));
        }
        
        public static function isMobileBrowser()
        {
            Debug::log("Detecting whether or not we have a mobile browser...");
            $mobile_browser = '0';

            if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
                $mobile_browser++;
            }

            if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') > 0) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))) {
                $mobile_browser++;
            }    

            $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
            $mobile_agents = array(
                'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
                'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
                'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
                'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
                'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
                'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
                'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
                'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
                'wapr','webc','winw','winw','xda ','xda-');

            if (in_array($mobile_ua,$mobile_agents)) {
                $mobile_browser++;
            }

            if (isset($_SERVER['ALL_HTTP']) && strpos(strtolower($_SERVER['ALL_HTTP']),'OperaMini') > 0) {
                $mobile_browser++;
            }

            if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'windows') > 0) {
                $mobile_browser = 0;
            }

            if ($mobile_browser > 0) {
               // do something
            }
            else {
               // do something else
            }   
            
            $ismobile = $mobile_browser > 0;
            Debug::log("Mobile? " . ($ismobile ? 'yes!' : 'nope'));
            return $ismobile;
        }
        
           
    public static function createTextFile($fullpath, $contents)
    {
        Debug::log("Saving text file to $fullpath....");
        
        $fh = fopen($fullpath, 'w') or Debug::error("can't open file for writing at $fullpath ");
        
        fwrite($fh, $contents);
        
        fclose($fh);
        
        return true;
    }
        
        //courtesy of filipminev
        public static function linkify($t)
         {
            Debug::log("Linkify");
            
            //not yet until i implemented the 'raw' assigning for these things...
            return $t;
            
           $t = " ".preg_replace( "/(([[:alnum:]]+:\/\/)|www\.)([^[:space:]]*)".
          "([[:alnum:]#?\/&=])/i", "<a href=\"\\1\\3\\4\" target=\"_blank\">".
          "\\3\\4</a>", $t);

          // link mailtos
          $t = preg_replace( "/(([a-z0-9_]|\\-|\\.)+@([^[:space:]]*)".
           "([[:alnum:]-]))/i", "<a href=\"mailto:\\1\">\\1</a>", $t);

          //link twitter users
          $t = preg_replace( "/ +@([a-z0-9_]*) ?/i", " <a href=\"http://twitter.com/\\1\" target=\"_blank\">@\\1</a> ", $t);

          //link twitter arguments
          $t = preg_replace( "/ +#([a-z0-9_]*) ?/i", " <a href=\"http://twitter.com/search?q=%23\\1\" target=\"_blank\">#\\1</a> ", $t);

          // truncates long urls that can cause display problems (optional)
          $t = preg_replace("/>(([[:alnum:]]+:\/\/)|www\.)([^[:space:]]".
           "{30,40})([^[:space:]]*)([^[:space:]]{10,20})([[:alnum:]#?\/&=])".
           "</", ">\\3...\\5\\6<", $t);
          
          //die('linkfiying');
          return trim($t);
         }

	public static function strToUnixDays($timeString)
	{
		return self::secsToDays(strtotime($timeString));
	}
        
        public static function respondAsJson($array)
        {
            Debug::log("Will respond with array as json string! Setting headers too?");
            die(json_encode($array));
        }

	public static function secsToDays($timestamp)
	{
		$secsToDays = 60 * 60 * 24;

		return round($timestamp / $secsToDays, 0);
	}
        
        /*
         * So google chart likes them. Puts x-axis field in front, adds totals to display above graph...
         * Make sure that the $dbResults contains fields xaxisfields and all those in yaxisfields!
         * 
         *  //specific version to understand whats going on:
            $chartData = array('fields' => array('day', 'count','income'), 'data' => array());

            $totalCount = $totalIncome = 0;
            foreach ($results as $result)
            {
                $totalCount += $result['count'];
                $totalIncome += $result['income'];

                $chartData['data'][] = array($result['day'], (int) $result['count'], (float) $result['income']);
            }

            $totals = array('count' => $totalCount, 'income' => $totalIncome);

            Debug::log($chartData, "Chart data");
            $finalResult = array('jsondata' => json_encode($chartData), 'totals' => $totals);

         */
        public static function formatDBResultsForChart($dbResults, $xAxisField, $yAxisFields, $startdate, $enddate)
        {
            Debug::log($dbResults, "Will format these for chat with x axis $xAxisField and Y-axes: " . implode(',', $yAxisFields));
            
            if (!$dbResults)
            {
                Debug::log("Empty results received, returning false");
                $dbResults = array();
            }
            
            if ($xAxisField == 'day')
            {
                Debug::log("X axis is day! Making sure each day is present...");
                Util::fillDBResultsWithAbsentDates($dbResults, $startdate, $enddate);
            }
            Util::sortDBResults($dbResults, $xAxisField);
            
            $fields = $yAxisFields;
            array_unshift($fields, $xAxisField);
            
            $chartData = array('fields' => array(),  //we will append totals to fields later on!
                                'data' => array());

            $totalCount = $totalIncome = 0;
            $totals = array();
            
            //whoops, not each 'result' may have a value for each field! (because results are constructed, like when showing products)
            foreach ($dbResults as $result)
            {
                $values = array($result[$xAxisField]);
                foreach ($yAxisFields as $yField)
                {
                    $datapoint = isset ($result[$yField]) ? $result[$yField] : 0;

                    if (!isset($totals[$yField]))
                    {
                        $totals[$yField] = $datapoint;
                    }
                    else
                    {
                        $totals[$yField] += $datapoint;
                    }

                    $values[] = round((float) $datapoint, 2);
                }

                $chartData['data'][] = $values;
            }

            //add field totals to field names like this field(total)
            foreach ($fields as $key => &$field)
            {
                if ($field != $xAxisField) //total over period of x axis does not make sense
                {
                    $field .= ' (' . $totals[$field] . ')';
                }
            }
            
            
            $chartData['fields'] = $fields;

            $totals = Util::arrayToDbResults($totals, 'field', 'total');
            
            Debug::log($chartData, "Chart data");
            Debug::log($totals, "Totals");
            
            return array('jsondata' => json_encode($chartData), 'totals' => $totals);
        }
        
        //will do a normal 2 array to an array of arrays like a db result with fields $keyname and $valuename
        public static function arrayToDbResults($array, $keyname, $valuename)
        {
            $results = array();
            
            foreach ($array as $key => $value)
            {
                $results[] = array ($keyname => $key, $valuename => $value);
            }
            
            return $results;
        }

	public static function forward($destination, $isAjax = false)
	{
            Debug::Log("Will forward to: $destination");
            
            if (self::$workingInAjaxMode)
            {
                    $connector = strpos($destination, '?') > 0 ? '&' : '?';
                    $destination .= $connector . 'ajax=1';
            }

            if (!Debug::debuggingIsEnabled())
            {
                Header("Location: " . $destination);
            }
            else
            {
                $output1 = '<h2>Forwarding to: <a href="' . $destination . '">' . $destination . '</a></h2>';
                $output2 = '<script src="lib/prototype.js" type="text/javascript"></script>';
                die(Debug::getLogMarkup() . $output1 . $outpu2);
            }
	}

    public static function generateRandomString($length = 10) 
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
        
        //method can also be 'POST'
        public static function doHTTPRequest($destUrl, $method = 'GET', $queryparameters = array())
        {
            Debug::log("Will try HTTP $method request to: $destUrl");
            $time1 = microtime(true);
        
            //cURL extension installed yet?
            if (!function_exists('curl_init')){
                Debug::error("Trying HTTP request but cURL not installed!");
                return false;
            }

            $ch = curl_init();

            if ($method == 'GET')
            {
                Debug::log("GET request: making parameters into query string...");
                
                $querystring = http_build_query($queryparameters);
                $destUrl .= (strpos('?', $destUrl) > 0 ? '&' : '?' ) . $querystring;
                Debug::log("New url: $destUrl");
            }
            
            curl_setopt($ch, CURLOPT_URL, $destUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            if ($method == 'POST')
            {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $queryparameters);
            }

            $output = curl_exec($ch);
            
            $time2 = microtime(true);
            $requesttimeInMs = round(($time2 - $time1) * 1000, 2);
            Debug::log($output, "Request results! (Took $requesttimeInMs ms to fetch)");

            curl_close($ch);

            return $output;
        }
        
        public static function fillDBResultsWithAbsentDates(&$results, $startdate, $enddate)
        {
            Debug::log($results, "Filling these results with empty rows for non-present dates between $startdate and $enddate...");
            
            $resultDays = Util::resultsetToKeyArray($results, 'day');
            Debug::log($resultDays, 'resultdays');
            
            $days = (strtotime($enddate) - strtotime($startdate)) / (60 * 60 * 24);
            
            for ($i = 0; $i < $days; $i++)
            {
                $day = date('Y-m-d', strToTime("$startdate + $i days"));
                
                if (!in_array($day, $resultDays))
                {
                    $results[] = array('day' => $day, 'count' => 0, 'income' => 0);
                }
            }
            
            Debug::log($results, "Filled results");
        }
        
	public static function sortDBResults(&$data, $sortKey, $order = 'ASC')
	{
            if (count($data) > 0)
            {
                    // Obtain a list of columns
                    foreach ($data as $key => $row)
                    {
                    $temp[$key]  = $row[$sortKey];
                    }

                    // Sort the data with volume descending, edition ascending
                    // Add $data as the last parameter, to sort by the common key
                    $orderConst = $order == 'ASC' ? SORT_ASC : SORT_DESC;
                    array_multisort($temp, $orderConst, $data);
            }
	}

	public static function resultsetToKeyArray($results, $key)
	{
            $flat = Array();
            foreach ($results as $row)
            {
                    $flat[] = $row[$key];
            }

            return $flat;
    }

    //dirrrrty
    public static function isLocalEnvironment()
    {
        $isLocal1 = strpos('_' . $_SERVER['HTTP_HOST'], 'localhost') > 0;
        $isLocal2 = strpos('_' . $_SERVER['HTTP_HOST'], 'local.') > 0;

        $isLocal = $isLocal1 || $isLocal2;
        Debug::log("Checking if we are in local environment ->" . ($isLocal ? 'YES' : 'NO'));

        return $isLocal;
    }
    
    public static function isOnlineDevEnvironment()
    {
        $isDev = strpos('_' . $_SERVER['HTTP_HOST'], 'dev.') > 0;
        
        Debug::log("Checking if we are in online dev environment ->" . ($isDev ? 'YES' : 'NO'));
        
        return $isDev;
    }
        
        //since the https path is so dirty we sometimes need to alter our paths for this
        public static function isFacebookStorefront()
        {
            return isset($_GET['p']) && $_GET['p'] == 'storefront';
        }

	public static function signupDataIsValid($signupData)
	{
            //firstname
            if (strlen($signupData['firstname']) == 0)
            {
                    return false;
            }

            //email
            if (strlen($signupData['email']) > 0 && strpos($signupData['email'], '@') == false)
            {
                    return false;
            }

            return true;
	}
}

?>