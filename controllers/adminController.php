<?php

class adminController extends adminBaseController
{
    public function __construct($login)
    {
        parent::__construct($login);
        
    }
    
    public function actioncreatebackup()
    {
        
        DB::mailExportFile();
    }
    
    public function actionPhpinfo()
    {
        phpinfo();
        die();
    }

    public function actionArrangeBoards ()
    {
        Debug::log("Will arrange boards");
        //die('here');

        $db = DB::getInstance();
        $db->prepare("SELECT * FROM boards b JOIN user_boards u ON b.boardid = u.boardid WHERE 1");
        $results = $db->getResults();

        $xcos = array();
        $ycos = array();
        
        foreach ($results as $result){
            if (!isset($xcos[$result['userid']])){
                $xcos[$result['userid']] = 0;
                $ycos[$result['userid']] = 0;
            }

            $newx = $xcos[$result['userid']] + 20;
            $xcos[$result['userid']] = $newx;

            $newy = $ycos[$result['userid']] + 5;
            $ycos[$result['userid']] = $newy;

            DB::updateOrInsert('boards', array(
                array ('key' => 'xpos', 'value' => $newx, 'type' => 'int'),
                array ('key' => 'ypos', 'value' => $newy, 'type' => 'int'),
                ),
            array (array('key' => 'boardid', 'value' => $result['boardid'], 'type' => 'int'))
            );
        }
    }
    
    public function displayIndex()
    {
        $this->pv['debugmodehash'] = Configuration::get('auth','debugmodehash');
        
        $this->pv['date'] = date('Y-m-d', time());

        $this->templateFile = 'admin/admin.html';        
    }

    public function actionSendReminders(){
        $date = $this->v->getValue('date');

        $this->setActionState(BarListo::sendEmailReminders($date));
    }
    
    public function actionaddinput()
    {
        $v = new Validator();
        
        $msg = $v->getValue('message');
        
        if (!$msg)
        {
            return false;
            $this->pv['actionstate'] = 'updatefail';
        }
        
        $this->pv['actionstate'] = Update::addNew('ALL', $msg) ? 'updatesuccess' : 'updatefail';
    }

    public function displayShops()
    {
        $v = new Validator();
        $start = $v->getValue('start') ? $v->getValue('start') : 0;
        $limit = $v->getValue('limit') ? $v->getValue('limit') : 30;
    
        $shops = Shop::getList($start, $limit);

        $this->pv['shops'] = $shops;
       
        $this->templateFile = 'admin/admin.shops.html';        
    }


    public function actionScaffold()
        {
            $item = $_POST['name'];
            $fields = array_filter($_POST['fields']);
            $fieldTypes = $_POST['fieldtypes'];
        
            list($query, $model, $view, $controller) = Scaffold::generateMVCCode($item, $fields, $fieldTypes);            
            
            $this->pv['model'] = $model;
            $this->pv['view'] = $view;
            $this->pv['controller'] = $controller;
            $this->pv['itemname'] = $item;
            $this->pv['query'] = $query;
            
            
        $this->templateFile = 'admin/admin.developer.html';  
        }
    
    public function displayUsers()
    {
        $v = new Validator();
        $start = $v->getValue('start') ? $start = $v->getValue('start') : 0;
        $limit = $v->getValue('limit') ? $limit = $v->getValue('limit') : 30;
    
        $users = User::getList($start, $limit);

        $this->pv['users'] = $users;
        
        $this->pv['signupsbydaychartdata'] = User::getSignupsByDay(true);
        
        $this->templateFile = 'admin/admin.users.html';        
    }
    
    public function displayDeveloper()
    {
        
        $this->templateFile = 'admin/admin.developer.html';        
    }
    
   public function actionDosomethingwithusers() {
        $v = new Validator();
        $todo = $v->getValue('todo');

        $userids = $v->getValue('users');

        Debug::log($userids, "Users I will do action $todo on ...");
        if ($todo == 'SENDREMINDMAIL') 
        {
            $result = Mail::sendMail('woutersmet@gmail.com', 'testing', 'this is a test!');
            
            $this->pv['actionstate'] = $result ? 'sendmailsuccess' : 'sendmailfail';
        } else {
            Debug::error("Don't know what to do with todo: $todo");
        }
    }
        
    public function actionDeleteUser()
    {
        $v = new Validator();
        $userid = $v->getValue('userid');
        
        $result = User::delete($userid);
        
        $this->pv['deleteUserresult'] = $result;
    }
 
    public function displayOneUser ()
    {
        $v = new Validator();
        $userid = $v->getValue('userid');
        
        if (!$userid)
        {
            Debug::log("user not found!");
            return;
        }
         
        $details = User::getDetails($userid);
        
        $this->pv['user'] = $details;
        
        $this->templateFile = 'admin/admin.users.one.html';
    }
        
    public function actionAddNewuser ()
    {
        $v = new Validator();
          $firstname = $v->getValue('firstname');
          $lastname = $v->getValue('lastname');
          $email = $v->getValue('email');
          $password = $v->getValue('password');
          $rights = $v->getValue('rights');


        $result = User::addNew($firstname,$lastname,$email,$password, $rights);

        $this->pv['adduserresult'] = $result;
    }
    
    public function actionAddOwner()
    {
        $v = new Validator();
        $ownerID = $v->getValue('ownerid');
        $shopID = $v->getValue('shopid');
        
        //if it's an email find corresponding user
        if (!is_numeric($ownerID))
        {
            $userDetails = User::getDetailsByEmail($ownerID);
            
            $ownerID = $userDetails ? $userDetails['userid'] : false;
        }
        
        if ($ownerID)
        {
            $result = Shop::addOwnerToShop($shopID, $ownerID);
            $this->pv['actionstate'] = $result ? 'owneraddsuccess' : 'owneraddfail';
        }
        else
        {
            $this->pv['actionstate'] = 'owneraddfail';
        }
    }
        
    public function actionDeleteShop()
    {
        $v = new Validator();
        $shopid = $v->getValue('shopid');
        
        $result = Shop::delete($shopid);
        
        $this->pv['deleteShopresult'] = $result;
    }
        
    public function displayOneShop ()
    {
        $v = new Validator();
        $shopid = $v->getValue('shopid');
        
        if (!$shopid)
        {
            Debug::log("shop not found!");
            return;
        }
         
        $details = Shop::getDetails($shopid);
        
        //add owners
        $details['owners'] = Shop::getOwners($shopid);
        
        $this->pv['shop'] = $details;
        
        $this->templateFile = 'admin/admin.shops.one.html';
        
    }
        
    public function actionAddNewshop ()
    {
        $v = new Validator();
          $name = $v->getValue('name');
          $description = $v->getValue('description');
          $facebookid = $v->getValue('facebookid');


        $insertid = Shop::addNew($name,$description,$facebookid);

        $shopid = Shop::insertIdToRealId($insertid);

        Shop::setRealShopId($insertid, $shopid);
        
        $this->pv['addshopresult'] = $insertid !== false;
    }
}

?>