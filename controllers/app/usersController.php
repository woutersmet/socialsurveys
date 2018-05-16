<?php

class admin_usersController extends BaseController
{
    public $requiredRights = User::USERRIGHTS_ADMIN;
    public function __construct($login)
    {
        parent::__construct($login);
        
        $this->pv['section'] = 'users';
        
    }
    
    public function displayIndex()
    {
        
        $itemsperpage = 10;
        $start = $this->v->getValue('q') ? $start = $this->v->getValue('q') : 1;
        $offset = ($start - 1) * $itemsperpage;
        $users = User::getList($offset, $itemsperpage);
        
        $totalcount = User::getUserCount();

        $this->pv['users'] = $users;
        $this->pv['usercount'] = $totalcount;
        
        $this->assignPagerVars($itemsperpage,$totalcount, '/admin/users',$start, 2);
        
        $this->templateFile = 'admin/admin.users.html';        
    }
    
    public function actionLoginas()
    {
        //logging in as user...
        $userid = $this->v->getValue('userid');
       
        if (User::loginByUserId($userid))
        {
            Util::forward('/app');
        }
        else
        {
            $this->fail("logging in as user # $userid failed...");
        }
        
    }
    
    public function actionDeleteUser()
    {
        $userid = $this->v->getValue('userid');
        
        $this->setActionState(User::delete($userid), "Succesfully deleted user # $userid", "Something went wrong deleting user # $userid.");
    }
    
   public function actionDosomethingwithusers() {
        $v = new Validator();
        $todo = $v->getValue('todo');

        $userids = $v->getValue('users');

        Debug::log($userids, "Users I will do action $todo on ...");
            $emails = $v->getValue('emails');
        if ($todo == 'SENDREMINDMAIL') 
        {
            $result = Mail::sendMail($emails, 'testing', 'this is a test!');
            
            $this->pv['actionstate'] = $result ? 'sendmailsuccess' : 'sendmailfail';
        } 
        elseif ($todo == 'SENDCONFIRMMAIL')
        {
            Mail::sendUserConfirmMail($emails);
        }
        else {
            Debug::error("Don't know what to do with todo: $todo");
        }
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

        Mail::sendUserConfirmMail($email);

        $this->pv['adduserresult'] = $result;
    }
    
   
}

?>