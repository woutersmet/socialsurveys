<?php

class indexController extends BaseController
{
    
    public function __construct($login)
    {
        parent::__construct($login);
        
        //mobile?
        
    }
    
    public function actionForceNonMobile()
    {
        $_SESSION['forcenonmobile'] = true;
        Util::forward('/m/');
    }

    public function displayIndex()
    {
        if (isset($_GET['demo']))
        {
            $this->pv['showdemo'] = true;
        }
        
        $localsuffix = Util::isLocalEnvironment() ? '_local' : '_online';
        $this->pv['demoboardid'] = Configuration::get('general', 'demoboardid' . $localsuffix);

        if (isset($_GET['notAllowed']))
        {
            $this->fail("You are not allowed in here.");
        }
        
        if (isset($_GET['invalid']))
        {
            $this->fail("You are not allowed in here.");
        }
        
        if ($this->ismobile)
        {
                                    
            $useCaptcha = Configuration::get('auth', 'use_captcha');
            $this->pv['usecaptcha'] = $useCaptcha;
            if ($useCaptcha)
            {
                    $this->pv['captchaquestion'] = Captcha::getCaptchaQuestion();
            }

            $this->pv['invitecoderequired'] = Configuration::get('auth', 'invitecoderequired');
            
            $this->templateFile = 'mobile.index.html';
        }
    }

    public function actionLogout()
    {
        Debug::log("Logging user out!");
        
        User::logout();
        Util::forward($this->ismobile ? '/m/' : '/');
    }
}

?>