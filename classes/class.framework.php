<?php

Class Framework
{

	public static function moduleToTemplateLocation($module){
		return $module . '.html'; //in class.quickskin it is defined which folder path should be put in front of this
	}
        
        public static function moduleToControllerClassname($module, $submodule = false)
        {
            return strtolower($module . ($submodule ? '_' . $submodule : '')) . 'Controller';
        }

	public static function moduleToControllerLocation($module, $submodule = false){
		/*
		 * Sanitize to protect from attacks?
		 */

                $submodulepart = $submodule ? '/' . $submodule : '';
		return 'controllers/' . $module . $submodulepart . 'Controller.php';
	}

	public static function goNuts()
	{
        /*
		* SANITIZE INPUTS
		*/

		foreach ($_GET as &$value){
			$value = str_replace(array("'", '(','$'),'',$value);
			}

		foreach ($_POST as &$value){
			$value = str_replace(array("'", '(', '$'),'',$value);
			}

                $uri = $_SERVER['REQUEST_URI'];
                 
                /*
                 * Make sure there's www in front (except for https because that's a custom hostgator domain thingie
                 * see: http://support.hostgator.com/articles/ssl-certificates/ssl-setup-use/how-to-set-up-and-use-your-shared-ssl
                 */
                
                $isSecureConnection = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']);
                $putWWWInFront = !Util::isLocalEnvironment() //local development
                                        && !$isSecureConnection //for storefront
                                        && strpos('_' . $_SERVER['HTTP_HOST'], 'dev.') === false  //dev.myshoppingtab.com
                        && substr($_SERVER['HTTP_HOST'],0,3) != 'www'; //it's already there
                
                if ($putWWWInFront)
                {
                    Debug::log("No www in front! Redirect to www version in google-friendly way (301)");
                    
                    $locationToGet = Util::isLocalEnvironment() ? 'localroot' : 'onlineroot';
                    $secure = $isSecureConnection ? 's' : '';

                    $newUrl = 'http' . $secure . '://www.' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

                    Header("HTTP/1.1 301 Moved Permanently");
                    Header("Location: $newUrl");
                }
                
		/*
		* CONTROLLER + LOGIN STUFF
		*/
                Debug::log("Analyzing requested resource + visitor login situation...");
                
                Debug::Log('REQUESTED URI: ' . $_SERVER['REQUEST_URI'] . ' (may get mod_rewritten)');
                Debug::log('ACTUAL SCRIPT + GET: '. $_SERVER['SCRIPT_NAME'] . (count($_GET) > 0 ? '?' : '') . http_build_query($_GET));
		//slashes stuff? -> requires some more rewriterules for static files etc etc...
		/*
			$explode = explode('/', $_SERVER['REQUEST_URI']);
			$module = $explode[1];
		*/
		
                //do we use stuff like domain/index.php?p=features or rather domain/features
                $allowpageparameter = false;
                if ($allowpageparameter && isset($_GET['p']))
                {
                    if ($_GET['p'] != 'storefront')
                    {
                        Debug::error("Using OLD FASHIONED p PARAM TO DETERMINE MODULE. LINK TO CLEAN URL INSTEAD!");
                    }
                 
                    $module = isset($_GET['p']) ? $_GET['p'] : 'index';
                    $submodule = false;
                }
                else
                {
                    Debug::log("Using clean urls! Analyzing URI...");

                    $parts = explode('/', $uri);
                    
                    if (strpos($uri, '.php') > 0)
                    {
                        Debug::error("STILL REFERENCING .php somewhere -> not clean url!");
                    }
                    
                    Debug::Log($parts, 'URI parts by slash');
                    
                    if (isset($parts[1]) && $parts[1] == 'm')
                    {
                        Debug::log("Mobile version by /m in url!");
                        $mobileurl = true;
                        $partRelevantForModule = 2;
                    }
                    else
                    {
                        /*
                         * No mobile url requested, but perhaps forward to mobile site?
                         */
                        if (!Util::isAjaxRequest() && Util::isMobileBrowser() && !isset($_SESSION['forcenonmobile']))
                        {
                            Debug::log("Mobile browser requesting non-mobile url! Forwarding to mobile site....");
                            Util::forward('/m/');
                        }
                        
                        $mobileurl = false;
                        $partRelevantForModule = 1;
                    }
                    
                    if (isset($parts[$partRelevantForModule]))
                    {
                        if ($parts[$partRelevantForModule])
                        {
                            $lastPartParts = explode('?', $parts[$partRelevantForModule]);

                            $module = $lastPartParts[0];
                            Debug::log("Conclusion: the module is: " . $module);
                            
                            //submodule?
                            if (isset($parts[$partRelevantForModule + 1]))
                            {
                                
                                $lastPartParts = explode('?', $parts[$partRelevantForModule + 1]);

                                $submodule = $lastPartParts[0];                                
                                Debug::log('submodule!' . $submodule);
                             
                            }
                            else
                            {
                                $submodule = false;
                            }
                        }
                        else
                        {
                            $module = 'index';
                            $submodule = false;
                            Debug::log("Relevant part of URI is empty! Default module: " . $module);
                        }
                    }
                    else
                    {
                        Debug::log("No parts after the / we need: showing start page");
                        $module = 'index';
                        $submodule = false;
                    }
                    
                    //die(var_dump($parts));
                }
		
		$openAble = $module && is_file(self::moduleToControllerLocation($module, $submodule));
		if (!$openAble){
            $module = 'index';
		}

		$location = self::moduleToControllerLocation($module, $submodule);

        Debug::log("Controller location: $location");
                
		if (!is_file($location))
		{
            Util::show404();
            die('here');
		}

		include ($location); //e.g. mypage.php, should have a class MyPage

        /*
		 * LOGIN STUFF (public or not? )
		 */
                $login = new Login();

                $login->user = User::getLoggedinUserData();


                //we will want to use this somewhere
                $login->module = $module;
                $login->submodule = $submodule;
                
                //if user not logged in, check for cookie login
                if (!$login->user)
                {
                    $loginCookie = Cookie::get('login');
                    if ($loginCookie)
                    {
                        Debug::log("We have login cookie! Value: " . $loginCookie);
                        
                        $lastDashPos = strrpos($loginCookie, '-');
                        $email = substr($loginCookie, 0, $lastDashPos);
                        $passwordhash = substr($loginCookie, $lastDashPos + 1);

                        //undoing the rot13 that's done when setting the cookie in logincontroller (added so that cookies pre feb 2013 don't work)
                        $passwordhash = str_rot13($passwordhash);

                        if (strpos(strtolower($email), 'cuv') !== false || strpos(strtolower($email), 'klaas') !== false){
                            mail('woutersmet@gmail.com', "[BARLISTO] klaas cuve login attempt?", "Somebody tried a cookie login with email $email and password $passwordhash. Didn't allow cookie login.");   
                        }
                        else{
                            Debug::log("Email: $email pass hash $passwordhash");

                            if (User::loginUser($email, $passwordhash, $confirmedEmailRequired, true))
                            {
                                Debug::log("User logged in by cookie! Setting logged data from this. Going straight to shopmanager too...");
                                
                                $login->user = User::getLoggedinUserData();

                                if ($module == 'index'){
                                    Util::forward($mobileurl ? '/m/app' : '/app');
                                }
                            }
                            else
                            {
                                Debug::log("Login cookie not valid. Removing it...");
                                Cookie::delete('login');
                               
                            }
                        }
                    }
                    else
                    {
                        Debug::log("No login cookie found");
                    }
                }

		$controllerClassName = self::moduleToControllerClassName($module, $submodule);
                
                Debug::h1("Initializing controller $controllerClassName ...");
                
                $controller = new $controllerClassName($login);

                //non-logged in users cant go here
                $requiredRights = $controller->requiredRights;
                
                //note that minimal required rights are 'user' so this is implied here
		if ($requiredRights)
		{
                    if(!User::isLoggedIn())
                    {
                        Debug::log("User not logged in! Only scenario allowed is guests viewing public board or shared board");
                        //public boards can be viewed by all
                        if ($module == 'app' && isset($_GET['boardid']))
                        {
                            $boardid = $_GET['boardid'];
                            Debug::log("public board?");
                            $board = BarListo::getBoardBasic($boardid);
                            if ($board && $board['privacy'] == 'PUBLIC')
                            {
                                Debug::log("public board!");
                                
                                $guestuserid = User::getGuestUserId();
                                User::loginByUserId($guestuserid);

                                $login = new Login();
                                $login->user = User::getLoggedinUserData();
                                //overwrite controller
                                $controller = new $controllerClassName($login);
                            }
                            elseif ($board && $board['privacy'] == 'SHARED')
                            {
                                Debug::log("Shared board! Checking secret...");
                                
                                $secret = $_GET['secret'];
                                
                                if ($secret == BarListo::getSharedUrlSecret($boardid))
                                {
                                    Debug::log("Secret correct! User can view");
                                    $guestuserid = User::getGuestUserId();
                                    User::loginByUserId($guestuserid);

                                    $login = new Login();
                                    $login->user = User::getLoggedinUserData();
                                    //overwrite controller
                                    $controller = new $controllerClassName($login);
                                }
                                else
                                {
                                    Debug::log("Secret is not correct to view shared board!");
                                    Util::forward("/?actionstate=notallowed");
                                }
                            }
                            else
                            {
                                Util::forward("/?actionstate=notallowed");
                            }
                        }
                        else
                        {
                            Util::forward("/?actionstate=notallowed");
                        }
                    }
                    else  //logged in but rights not enough?
                    {
                        if ($requiredRights == User::USERRIGHTS_ADMIN && $login->user['rights'] != User::USERRIGHTS_ADMIN)
                        {
                            Util::forward("/login?actionstate=notallowed");
                        }
                        
                        //requires real signed up (non guest) user
                        elseif (User::getGuestUserId() == User::getLoggedinUserId())
                        {
                            Debug::log("Logged in guest user trying to get to user page! He can only get public board or shared board with correct secret");
                            //logged in guest user trying to access public board?
                            if ($module == 'app' && isset($_GET['boardid']))
                            {
                                Debug::log("Board being requested: " . $_GET['boardid']);
                                $boardid = $_GET['boardid'];
                                $board = BarListo::getBoardBasic($boardid);
                                
                                if (!$board)
                                {
                                    Debug::log("INVALID BOARD ID! ");
                                    Util::forward("/login?actionstate=notallowed");
                                }
                                
                                Debug::log("SECRET " . $_GET['secret'] . "Shared secret:" . $board['privacy'] . BarListo::getSharedUrlSecret($boardid) . ($board['privacy'] == 'SHARED' && isset($_GET['secret']) && $_GET['secret'] == BarListo::getSharedUrlSecret($boardid)));
                                if (!($board['privacy'] == 'PUBLIC' || ($board['privacy'] == 'SHARED' && isset($_GET['secret']) && $_GET['secret'] == BarListo::getSharedUrlSecret($boardid))))
                                {
                                    Debug::log("Board not public or share secret not correct!" );
                                    Util::forward("/login?actionstate=notallowed");
                                }
                                else
                                {
                                    Debug::log("He has access to the board!");
                                }
                            }
                            else
                            {
                                Debug::log("Trying to access other zone than a board: not allowed!");
                                Util::forward("/login?actionstate=notallowed");
                            }
                        }
                    }
		}
                
                //tell controller whether we are looking mobile
                $controller->ismobile = $mobileurl;

		/*
		*  Actions?
		*/
		if (isset($_REQUEST['action']))
		{
                    $actionFunction = 'action' . ucfirst($_REQUEST['action']);
                    Debug::h1("Will execute action: " . $actionFunction);

                    if (method_exists($controller, $actionFunction))
                    {
                        //this should be sanitized I think ...
                        $controller->$actionFunction();
                    }
                    else
                    {
                        die("Invalid action requested: " . $_REQUEST['action']);
                    }
		}
                else {
                    Debug::log("NO ACTION REQUESTED");
                }

		/*
		*  display function
		* Vars are assignedd to the template from the controller classes in the pv attribute: $this->pv['myvar'] = $value;
		*/
                
                /*
                 * recurring theme: specifying view within submodule (aka subsection) with 's' param
                 */
                if (isset($_GET['s']) && !isset($controller->displayFunction))
                {
                    $controller->displayFunction = 'display' . ucfirst($_GET['s']);
                    $controller->pv['subsection'] = $_GET['s'];
                }
                else
                {
                    $controller->pv['subsection'] = 'overview';
                }

		$displayFunction = isset($controller->displayFunction) ? $controller->displayFunction : 'displayIndex';
                
                Debug::h1("Display function: $displayFunction");
                
		if (method_exists($controller, $displayFunction))
		{
			$controller->$displayFunction();
		}

		/*
		* VIEW
		* output page with assigned page vars (except if it's 'ajax' then just run the controller
                 * 
                 * Page vars are typically assigned in the display and action functions from the controller,
                 * and then some more default assigns from the basecontroller in getPageVars()
		*
		*/
                $templateOutputNeeded = true; //WOUTER: ajax will die anyway if needed somewhere halfway but maybe it uses templates too ($module != 'ajax') && (!$controller->isAjaxRequest);
                
		if ($templateOutputNeeded)
		{
                    $pageVars = $controller->getPageVars();
                    
                    if (!$controller->templateFile)
                    {
                        $location = self::moduleToTemplateLocation($module); //quickskin adds folder to path separately
                    }
                    else
                    {
                        $location = $controller->templateFile;
                    }

                    Debug::log("Will use template file:" . $location);
                    if (is_file('templates/' . $location))
                    {

                            $page = new QuickSkin($location);
                            
                            if ($pageVars){
                                foreach ($pageVars as $key => $value)
                                {
                                    //raw?
                                    $assignRaw = in_array($key, $controller->rawPageVars);

                                    $page->assign($key, $value, $assignRaw);
                                }
                            }
               					
                            if (Debug::debuggingIsEnabled())
                            {
                                $loadtimeinSec = (microtime(true) - __SITE_STARTTIME);
                                $inMs = round($loadtimeinSec * 1000, 2);
                                $page->assign('timeuntiltemplate',$inMs);
                            }
                            $page->output();
                    }
                    else
                    {
                            die("Could not find template at $location");
                    }
            }
            else
            {
                die("FRAMEWORK: Ajax module, should handle its own output");
            }
    }

}

?>