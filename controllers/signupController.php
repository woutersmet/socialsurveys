<?php

Class signupController extends baseController
{

    /*
     * This will either add a new user + shop or just a shop for an existing user!
     */
	public function actionSignup()
	{
            $whatsWrongWithTheFields = false;

            list($errorcode, $readableError) = User::somethingWrongWithUserDetails($_POST);
	      
            if (!$errorcode)
            {
                Debug::log("All fields good! Creating user...");
                $data = $_POST;

                $this->userid = User::addNew(
                        array (
                            array('key' => 'username',
                                'value' => $data['username'],
                                'type' => 'var'),
                            array('key' => 'email',
                                'value' => $data['email'],
                                'type' => 'var'),
                            array('key' => 'password',
                                'value' => User::passwordToHash($data['password']),
                                'type' => 'var'),
                            array('key' => 'dateadded',
                                  'value' => 'NOW',
                                   'type' => 'function')
                            ), User::USERRIGHTS_USER);

                if ($this->userid)
                {
                    Debug::log("Now user has to confirm his email!");
                    //new conversion! Logging that
                    Debug::log("User created (and confirmation not required for login)! Logging in ...");
                    
                    Mail::sendWelcomeMail($data['email']);
                    
                    User::loginUser($data['email'], $data['password'], false);
                    
                    //make a board right away?
                    $boardid = false;//nah let them figure it out BarListo::createBoard($this->userid, 'My personal lists');
        
                    if ($boardid)
                    {
                        //insert a list
                        $listid = BarListo::createList($boardid, '');
                        BarListo::createBar($listid, '');
                        BarListo::createBar($listid, '');

                        $scribbletext = "This is a scribble!\n\nIt's basically a way to add some freehand text to your board.\n(like for instructions)";
                        BarListo::createScribble($boardid, $scribbletext);

                        Util::forward($this->ismobile ? '/m/app' : '/app?boardid=' . $boardid .'&actionstate=signupsuccess');
                    }
                    else
                    {
                      Util::forward($this->ismobile ? '/m/app' : '/app?actionstate=signupsuccess');
                    }
                }
                else
                {
                    if ($this->ismobile){
                      Util::forward('/m/');
                    }
                    else
                    {
                        $this->fail("Something went wrong signing up: user with that e-mail already exists.", 'INVALID_EMAIL');
                    }
                }
            }
            else //incorrect form fields
            {
                if ($this->ismobile){
                  Util::forward('/m/');
                }
                else
                {
                    $this->fail("Something went wrong signing up: " . $readableError, $errorcode);
                }
            }

            $this->assignPagevarArray($_POST);
	}

	function displayIndex()
	{
                        
            $useCaptcha = Configuration::get('auth', 'use_captcha');
            $this->pv['usecaptcha'] = $useCaptcha;
            if ($useCaptcha)
            {
                    $this->pv['captchaquestion'] = Captcha::getCaptchaQuestion();
            }

            $this->pv['invitecoderequired'] = Configuration::get('auth', 'invitecoderequired');
		
            if (isset($_GET['signupsuccess']))
            {
                $this->pv['signupSuccess'] = true;
            }
	}
}

?>