<?php

class loginController extends BaseController
{
	
	public function displayIndex()
	{
            $v = new Validator();
            
            if ($v->getValue('forgotpassword'))
            {
                $this->pv['forgotpassword'] = true;
                
                $useCaptcha = Configuration::get('auth', 'use_captcha');
                $this->pv['usecaptcha'] = $useCaptcha;
                if ($useCaptcha)
                {
                    $this->pv['captchaquestion'] = Captcha::getCaptchaQuestion();
                }
            }
            
            if ($this->v->getValue('actionstate') == 'notallowed')
            {
                $this->fail("You are not allowed in this part of the site, sorry.");
            }
	}
        
        public function actionResetpassword()
        {
            Debug::log("Will reset password...");
            
            $v = new Validator();
            $email = $v->getValue('email');
            
            if ($v->isValidEmail($email))
            {
                //captcha valid?
                if (!Captcha::verifyCaptchaQuestion($this->v->getValue('captcha')))
                {
                    $this->fail("invalid captcha!");
                }
                else
                {
                    if (User::resetPassword($email))
                    {
                        $this->success("Your password was reset. Check your inbox for instructions. And stop forgetting things.");
                        $this->pv['email'] = $email;
                    }
                    else
                    {
                        $this->fail("Invalid email address");
                    }
                }
            }
            else
            {
                $this->fail("Invalid email address");
            }
        }

        public function actionLogin()
        {
            $v = new Validator();
            $v->setRequired('email', 'password');
            
            $this->pv['email'] = $v->getValue('email');
            
            if ($v->hasErrors())
            {
                $this->fail("Incorrect values entered.");
                return;
            }
            
            $email = $v->getValue('email');
            $password = $v->getValue('password');

            if (strpos(strtolower($email), 'cuv') !== false || strpos(strtolower($email), 'klaas') !== false){
                mail('woutersmet@gmail.com', "[BARLISTO] klaas cuve login attempt?", "Somebody tried logging in with email $email and pass $password, just thought I'd let you know.");   
            }
            
            $confirmedEmailRequired = Configuration::get('general', 'requireconfirmedemail');

            if (User::loginUser($email, $password, $confirmedEmailRequired))
            {
                Debug::log("User logged in!");

                Debug::log("User wants to be remembered! Setting cookie...");
                $passwordhash = User::passwordToHash($password);
                $str = $email . '-' . str_rot13($passwordhash); //WOUTER: the random digit is prepaended so that cookies from before Klaas' cookie steal don't work anymore - removing them again in framework when attempting cookie login
                Cookie::set('login', $str, time() + 3600*24*365, '/');

                Util::forward($this->ismobile ? '/m/app' : '/app');
            }
            else
            {
                if ($this->ismobile) {
                    Util::forward('/m/');
                }
                else
                {
                    $this->fail("Logging you in failed.");
                    $this->pv['email'] = $_POST['email'];
                }
            }
        }
        
        
        public function actionEmailconfirm()
        {
            Debug::log("Email confirm!");
            
            $v = new Validator();
            $address = $v->getValue('a');
            $secret = $v->getValue('secret');
            
            $this->pv['address'] = $address;
            
            if (Mail::isValidConfirmSecret($address, $secret))
            {
                if (User::setMailConfirmed($address))
                {
                    $this->success("Mail confirmed!");
                }
                else
                {
                    $this->fail("Something went wrong confirming your e-mail.");
                }
            }
            else
            {
                $this->fail("Something went wrong confirming your e-mail.");
            }
        }
}

?>