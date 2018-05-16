<?php
class appBaseController extends baseController 
{
    public $requiredRights = User::USERRIGHTS_USER;
    
    protected $userid;
    
    public function __construct($login)
    {
        parent::__construct($login);
        
    }
    
    public function preAction()
    {
        Debug::log("Doing pre-action stuff...");
        
        //make double sure that user is managing correct data, catch csrf...?
        
        $boardid = $this->v->getValue('boardid');
        if (!BarListo::userCanAccessBoard($boardid, $this->userid))
        {
            Debug::error("using trying to change board he doesnt have access to!");
            
            Util::forward('/?actionstate=notallowed');
        }
    }
    
    public function postAction()
    {
        Debug::log("Doing post-action stuff...");
        
        //clear cache of something?
    }
    
}
?>