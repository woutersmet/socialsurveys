<?php

Class BaseController
{

	public $pv = array(); //associative array containing template assigned vars!
    public $rawPageVars = array(); //if page var is also in here it means it shouldnt be html escaped after assigning

    //set in framework, used in for example analyzing API calls
    public $module;
    public $submodule;

	public $requiredRights = false; //false for public pages, 'LOGGEDIN' for logged in only, user rights...
	public $isAjaxRequest = false; //no need to do template stuff

	public $displayFunction; //shows the page (and does ocntroller logic)

    public $templateFile; //has the html - default is modulename.html (as defined in framework class)
        
	public $login; // has loggedin user stuff
        
    public $ismobile = false; //are we on a mobile device?
        
    protected $validator; //to get vars from requests
        
	function __construct($login)
	{
            $this->login = $login;
            
            //are we on mobile? this is set in the framework
            
            //doing here instead of getPageVars() like most other default assigns because often the 
            //user object is changed by some action
            $this->pv['user'] = $this->login->user;
            $this->pv['sitename'] = Configuration::get('general', 'sitename');
            
            if ($this->login->user)
            {
                $this->userid = $this->login->user['userid'];

                $this->isguest = $this->userid == User::getGuestUserId();
                $this->pv['__isguest'] = $this->isguest;
            }
            
            $this->v = new Validator();
            
            //ajax or not? (the ajax script should send parameter 'ajax=1')
            /* AJAX check  */
            if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
            {
                Debug::log("ajax request!");
                $this->isAjaxRequest = true;
                Util::setInAjaxMode();
            }
            else
            {
                Debug::log("Not an ajax request...");
                $this->isAjaxRequest = false;
            }
	}
        
        
	public function displayIndex()
	{
            
	}
        
        public function assignPagevarArray($array)
        {
            foreach ($array as $key=>$value)
            {
                $this->pv[$key] = $value;
            }
        }
        
        public function setRequiredAndDoAssigns($fieldsArray, $errorState = 'adderrorfieldsrequired')
        {
            Debug::log($fieldsArray, "Setting these form fields as required");
            $v = new Validator();
            foreach ($fieldsArray as $field)
            {
                $v->setRequired($field);
                $this->pv[$field] = $field;
            }
            
            if ($v->hasErrors())
            {
                $this->pv['actionstate'] = $errorState;
            }
        }
        
        public function assignPagerVars($itemsperpage, $totalCount, $baselink, $currentpage, $aroundrange = 2)
        {
            //no pager needed!
            $this->pv['pagerneeded'] = $totalCount >= $itemsperpage;

            $lastpage = ceil($totalCount / $itemsperpage);

            $min = $currentpage - $aroundrange >= 1 ? $currentpage - $aroundrange : 1;
            $max = ($currentpage + $aroundrange) <= $lastpage ? ($currentpage + $aroundrange) : $lastpage;

            $range = range($min, $max);

            $pagerdata['surrounding'] = Util::arrayToDbResults($range, 'key', 'page');

            $pagerdata['current'] = $currentpage;
            $pagerdata['last'] = $lastpage;
            $pagerdata['prev'] = $currentpage - 1;
            $pagerdata['next'] = $currentpage + 1;

            $pagerdata['moretocomeafter'] = $max < $lastpage;
            $pagerdata['moretocomebefore'] = $min > 1;

            $pagerdata['baselink'] = $baselink . (substr($baselink, -1) == '?' ? '&' : '?');
            
            $this->pv['pager'] = $pagerdata;
        }
        
        public function setActionState($successOrFail, $message = false, $messageForFail = false, $errorcode = false)
        {
            if (!$successOrFail && $messageForFail) {
                Debug::log("Assigning actionstate for a fail and we have a fail message! Using that");
                $message = $messageForFail;
            }
            
            Debug::log("Setting actionstate: " . ($successOrFail ? 'success' : 'fail') . "With message: $message");
            
            if ($this->isAjaxRequest)
            {
                Util::respondAsJson(array('success' => $successOrFail,
                                        'message' => $message
                                        )
                    );
            }
            else
            {
                $this->pv['actionstate'] = $successOrFail ? 'success' : 'fail';
                $this->pv['actionstatemessage'] = $message;
                $this->pv['errorcode'] = $errorcode;
            }
        }
        
        public function success($message = 'Operation succesful!')
        {
            $this->setActionState(true, $message);
        }
        
        public function fail($message = 'Ouch! Something went wrong - please let us know if the problem persists.', $errorcode = false)
        {
            $this->setActionState(false, false, $message, $errorcode);
        }

	public function getPageVars()
	{
            //first add some overall stuff
            $this->pv['module'] = substr(get_class($this), 0, -10);
            
            //url stuff
            $locationToGet = Util::isLocalEnvironment() ? 'localroot' : 'onlineroot';
            $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 's' : '';
            
            if ($this->login->user && $this->login->user['rights'] == 'ADMIN')
            {
                Debug::log("Admin user! Assigning debug code for link...");
                $this->pv['debugmodehash'] = Configuration::get('auth', 'debugmodehash');
            }
            
            $this->pv['__rootUrl'] = 'http' . $secure . '://' . $_SERVER['HTTP_HOST'] . (Util::isLocalEnvironment() ? '/tabignite' : '');
            $this->pv['__currentUrl'] = 'http' . $secure . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $this->pv['__isLocalEnvironment'] = Util::isLocalEnvironment();
            $this->pv['__ismobile'] = $this->ismobile;
            
            //GET always seems to contain 2 vars, namely index_php and the current module/submodule
            //(.htaccess adds these I guess)
            //let page know if url contains query string yet so links like 'debugmode' know whether to use a ? or &
            $this->pv['urlHasQueryString'] = count($_GET) > 2 ? true : false;
            $this->pv['inlocaldbmode'] = DB::inLocalDbMode();
            
            //detect if loaded trough ajax (it will trigger conditional wrapper stuff)
            $this->pv['loadinwrapper'] = !$this->isAjaxRequest;

            //add page vars to debug
            Debug::log($this->pv, "Page vars");
            if (Debug::debuggingIsEnabled())
            {
                $this->pv['debuglog'] = Debug::getDebugLog();
            }   
            
            return $this->pv;
	}
        
        //well not do html entities on this file
        public function setRawPageVar($pagevarKey)
        {
            $this->rawPageVars[] = $pagevarKey;
        }
}

?>