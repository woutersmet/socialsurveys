<?php

class app_remindersController extends AdminBaseController
{
    public function __construct($login)
    {
        parent::__construct($login);
        
        $this->pv['section'] = 'reminders';
        
    }
    
    public function displayIndex()
    {
        $this->pv['reminders'] = Barlisto::getAllReminders($this->userid);
        
        $this->templateFile = $this->ismobile ? 'app/mobile.app.reminders.html' : 'app/app.reminders.html';        
        
        $totalcount = BarListo::getRemindersCount($this->userid);
        $this->pv['totalcount'] = $totalcount;
    }
    
    public function actionRemoveReminder()
    {
        $barid = (int) $this->v->getValue('barid');

        $this->setActionState(BarListo::removeReminder($barid), "Succesfully removed reminder", "Something went wrong removing the reminder.");
    }
}

?>