<?php

$site_path = realpath(dirname(__FILE__));
define ('__SITE_PATH', $site_path);

define('__SITE_STARTTIME', microtime(true));

//ini_set('error_reporting', E_ALL); //to have him complain to much
ini_set('error_reporting', E_ALL);

// gzip pages if gzip works
if(!ob_start("ob_gzhandler")) ob_start();

//stop timezone complaints when using date()
@date_default_timezone_set('Europe/Brussels');

//silly attempt to get less memory exhausted errors (ITS CLEARLY A BUG IN SOME DB CODE)
ini_set('memory_limit','1024M');

session_start();

/*
* MODEL
*/

function __autoload($className)
{
    require_once('classes/class.' . strtolower($className) . '.php');
}
//Debug::enableOutputDumping();
/*
 * LOCAL DB MODE 
 */
if (isset($_GET['localdbmode']))
{
    if ($_GET['localdbmode'] == 1){
        DB::enableLocalDbMode();
        User::logout();
        Util::forward('/');
    }
    else
    {
        DB::disableLocalDbMode();
        User::logout();
        Util::forward('/');
    }
}

/*
 * handling fatal errors
 */

if (! Util::isLocalEnvironment())
{
    register_shutdown_function('handleShutdown');
}

function showErrorPage($reason){
     include 'templates/404.html';
     exit;
}

function handleShutdown() {
        $error = error_get_last();
        if($error !== NULL){
            $info = "While watching URL: " . $_SERVER['REQUEST_URI'] ."\n\n[SHUTDOWN] file:".$error['file']." | ln:".$error['line']." | msg:".$error['message'] . "trace:<br />" . print_r(debug_backtrace(),true) . "User agent: " . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'dunno') . "IP: \n" . $_SERVER['REMOTE_ADDR'];
           mail('woutersmet@gmail.com', "[BARLISTO] Fatal error!", $info);

            showErrorPage($info);
        }
        else{
            //show404page("SHUTDOWN");
        }
    }


/*
 * DEBUG STUFF
 */
if (isset($_GET['debugmode']))
{
    if ($_GET['debugmode'] == Configuration::get('auth', 'debugmodehash')){
        Debug::setDebuggingEnabled();
    }
    else
    {
        Debug::setDebuggingDisabled();
    }
}

Debug::startDebugging();

//set error handling, see http://www.w3schools.com/php/php_error.asp
function customError($errno, $errstr, $errfile, $errline, $errcontext) {
    Debug::customError($errno, $errstr, $errfile, $errline, $errcontext);
}

set_error_handler("customError");

/*
* Before-all authentication (for private beta)
 * https facebook pages no authentication (for testing)
*/
$authenticationRequired = Configuration::get('auth','preloginrequired') && (!isset($_SERVER['HTTPS']) || !$_SERVER['HTTPS']);
if ($authenticationRequired)
{
    //HTTP AUTHENTICATION
    $authUsers = Configuration::get('auth', 'beta_users');
    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) 
    {
            header('WWW-Authenticate: Basic realm="One does not simply browse into tabignite.com!"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'You are not allowed to see this. Bad boy.';
            exit;
    }
    else 
    {
        $user = $_SERVER['PHP_AUTH_USER'];
        $pass = $_SERVER['PHP_AUTH_PW'];
        
        if (isset($authUsers[$user]) && $authUsers[$user] = $pass)
        {
            //correct nick and pass entered! we can carry on...
        }
        else
        {
            //incorrect nick and pass entered!
            header('HTTP/1.0 401 Unauthorized');
            echo 'You are not allowed to see this. Bad boy.';
            exit;
        }
    }

    
    //AUTHENTICATION LOGIN PAGE
    Debug::log("Not doing that pre-login php page anymore");
    /*
    $authUsers = Configuration::get('auth', 'beta_users');
    if (isset($_POST['name'],$authUsers[$_POST['name']]) && $authUsers[$_POST['name']] == $_POST['password'])
    {
            $_SESSION['authenticated'] = true;
    }

    if (!isset($_SESSION['authenticated']))
    {
            die(file_get_contents('templates/authenticate.html'));	
    }
     */

    //if we make it here we are authetnicated, yay!
}

/*
* CONTROLLER & VIEW
*/
Framework::goNuts();

?>