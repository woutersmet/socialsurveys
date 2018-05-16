<?php
class app_userController extends appBaseController 
{
    public function __construct($login)
    {
        parent::__construct($login);
        
    }
    
    public function displayIndex()
    {
        $userid = $this->v->getValue('userid');
        
        $profileuser = User::getDetails($userid);
        
        $this->pv['specifictitle'] = $profileuser['username'];
        $this->pv['profileuser'] = $profileuser;

        $this->templateFile = 'app/app.user.html';
    }

    
    
}
?>