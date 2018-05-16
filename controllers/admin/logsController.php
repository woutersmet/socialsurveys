<?php

class admin_logsController extends BaseController
{
    public $requiredRights = User::USERRIGHTS_ADMIN;
    public function __construct($login)
    {
        parent::__construct($login);
        
        if (isset($_GET['shopid']))
        {
            $this->displayFunction = 'displayOneShop';
        }
        
        $this->pv['section'] = 'logs';
    }

    public function displayIndex()
    {
        $v = new Validator();
        $start = $v->getValue('start') ? $v->getValue('start') : 0;
        $limit = $v->getValue('limit') ? $v->getValue('limit') : 30;
    
        $logs = Logging::getLogs($start, $limit);

        $this->pv['logs'] = $logs;
       
        $this->templateFile = 'admin/admin.logs.html';        
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

        $insertid = Shop::addNew($name,$description);

        $shopid = Shop::insertIdToRealId($insertid);

        Shop::setRealShopId($insertid, $shopid);

        $this->pv['actionstate'] = $insertid !== false ? 'addshopsuccess' : 'addshopfail';
    }
}

?>