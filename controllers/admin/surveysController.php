<?php

class admin_surveysController extends AdminBaseController
{
    public function __construct($login)
    {
        parent::__construct($login);
        
        $this->pv['section'] = 'surveys';
        
    }
    
    public function displayIndex()
    {
        $this->templateFile = 'admin/admin.surveys.html';        

        $this->pv['surveys'] = SocialSurveys::getUserSurveys();
    }

   
}

?>