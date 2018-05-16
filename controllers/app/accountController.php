<?php
class app_accountController extends appBaseController 
{
    public function __construct($login)
    {
        parent::__construct($login);
        
    }
    
    public function actionRemoveuser()
    {
        if (User::delete($this->userid))
        {
            User::logout();
            Util::forward('/');
        }
        else
        {
            $this->fail("Something went wrong deleting your account");
        }
    }

    public function actionUpdateuserdata()
    {
        $whatsWrongWithTheFields = false;

        list($errorcode, $readableError) = User::somethingWrongWithUserDetails($_POST, true);

        if (!$errorcode)
        {
            Debug::log("All fields good! Creating user...");
            $data = array (
                        array('key' => 'username',
                            'value' => $this->v->getValue('username'),
                            'type' => 'var'),
                        array('key' => 'email',
                            'value' => $this->v->getValue('email'),
                            'type' => 'var'),
                        array('key' => 'reminderemails', 
                            'value' => ($this->v->getValue('emailreminders') == 'on' ? 'YES' : 'NO'), 
                            'type' => 'var')
                        );
            
            if ($this->v->getValue('password'))
            {
                $data[] = array('key' => 'password',
                            'value' => User::passwordToHash($this->v->getValue('password')),
                            'type' => 'var');
            }
            
            $success = User::update($this->login->user['userid'], $data);

            if ($success)
            {
                $this->success("Updated user data!");
                
                //reset login object so facebook user id is now part of session user data
                $this->pv['user'] = User::getUserData($this->login->user['userid'], true);
                User::getUserData($this->userid, true);
            }
            else
            {
                $this->fail("Something went wrong changing user data!");
            }
        }
        else //incorrect form fields
        {
            $this->fail("Something went wrong changing user data: " . $readableError, $errorcode);
        }

    }
    
    public function displayIndex()
    {
        $this->templateFile = 'app/app.account.html';
    }

    
    
}
?>