<?php

class User
{

    //defines accessibility of pages - public, logged in only, admin only...
    const USERRIGHTS_USER = 'USER';
    const USERRIGHTS_ADMIN = 'ADMIN';
   
    public static function logout()
    {
        Debug::log("Logging out! Removing login cookie (if there) and session...");
        
        if (isset($_GET['keepcookie']))
        {
            Debug::log("Logging out but keeping cookie!");
            echo "Logging session out but keeping cookie!";
        }  
        else
        {
            Cookie::delete('login');
        }
        
         return session_destroy();
    }
    
    public static function isLoggedIn()
    {
        return isset($_SESSION['userid']);
    }
    
    public static function getGuestUserId()
    {
        return Util::isLocalEnvironment() ? 32 : 43;
    }
    
    public static function resetPassword($email)
    {
        Debug::log("Resetting user password with something random");
        
        //source for random string: http://php.net/manual/en/function.rand.php
        $n = rand(10e16, 10e20);
        $newPass = base_convert($n, 10, 36);
        Debug::log("New pass chosen: $newPass");
        
        $db = DB::getInstance();
        $db->prepare("UPDATE users SET password={pass} WHERE email={email} LIMIT 1");
        $db->assignVar('pass', self::passwordToHash($newPass));
        $db->assignVar('email', $email);
        
        if((int) $db->getAffectedRows() > 0)
        {
            Debug::log("Password changed! Sending mail...");
            
            Mail::sendPasswordChangedMail($email, $newPass);
            
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public static function update($userid, $data)
    {
        Debug::log($data, "Updating user # $userid...");
        
        return DB::updateOrInsert('users', $data, array(array('key' => 'userid', 'value' => $userid, 'type' => 'int')));
    }
    
    public static function updatePassword($userid, $password)
    {
        Debug::log("Updating password of user $userid...");
        
        $db = DB::getInstance();
        $db->prepare("UPDATE users SET password={pass} WHERE userid={userid} LIMIT 1");
        $db->assignVar('pass', self::passwordToHash($password));
        $db->assignInt('userid', $userid);
        
        return $db->execute(); 
    }
    
    public static function logLogin($userid)
    {
        Debug::log("Logging user $userid login...");
        $db = DB::getInstance();
        $db->prepare("UPDATE users SET timesloggedin=timesloggedin+1, lastloggedin=NOW() WHERE userid={userid}");
        $db->assignInt("userid", $userid);
        
        return $db->execute();
    }
    
    public static function loginByUserId($userid)
    {
        Debug::log("Login as user id $userid...");
        
        $_SESSION = array();
        $details = self::getUserData($userid);
        
        if ($details)
        {
            Debug::log("User exists!");
        
            $_SESSION['userid'] = $details['userid'];
            //also load the details in session right away
            $_SESSION['userdata_' . $details['userid']] = $details;
            
            //log login? NO cause this is an admin doing it
            //self::logLogin($details['userid']);
            
            return true;
        }
        
        return false;
    }
    
    public static function getLatestActivity($userid, $publicOnly = true)
    {
        
        $publicCondition = $publicOnly ? " AND b.privacy='PUBLIC'" : '';
        Debug::log("Getting latest activity for user $userid - " . ($publicOnly ? 'PUBLIC ONLY' : 'both public and private'));
        
        $db = DB::getInstance();
        $db->prepare("SELECT b.*,c.*,w.*,bb.*, b.boardid AS boardid, c.dateadded AS dateadded FROM changelogs c 
                            JOIN boards b ON c.boardid=b.boardid 
                            LEFT JOIN widgets w ON w.widgetid=c.entityid
                            LEFT JOIN bars bb ON bb.barid=c.entityid
                        WHERE userid={userid} $publicCondition
                        ORDER BY changelogid DESC
                        LIMIT 20");
        $db->assignInt('userid', $userid);
        
        return $db->getResults();
    }
    
    public static function loginUser($email, $password, $requireConfirmedEmail, $isRawPass = true)
    {
        Debug::log("Logging in with  $email pass $password ...");
        
        //$requireConfirmedEmail = Configuration::get('general', 'requireconfirmedemail');
        $details = self::getDetailsByEmailAndPass($email, $password, $requireConfirmedEmail, $isRawPass);
        
        if ($details)
        {
            Debug::log("User exists!");
        
            $_SESSION['userid'] = $details['userid'];
            //also load the details in session right away
            $_SESSION['userdata_' . $details['userid']] = $details;
            
            //log login?
            self::logLogin($details['userid']);
            return true;
        }
        
        return false;
    }
    
    //password may be directly from user submit 'raw' or already hashed (because we stored it like that in a cookie)
    public static function getDetailsByEmailAndPass($email,$password, $requireconfirmedemail = false, $isRawPass = true) 
    {
        Debug::log("Getting details by email $email and pass $password (Raw? $isRawPass)... Requiring confirmed email for login?" . ($requireconfirmedemail ? 'YES' : 'NO'));
        
        $confirmSQL = $requireconfirmedemail ? "AND emailstatus='CONFIRMED'" : '';
        
        //also allowing 'email' to be actually the username
        $wherepart = strpos($email, '@') > 0 ? 'email={email}' : 'username={email}';
        
        $db = DB::getInstance();
        $db->prepare("SELECT * FROM users WHERE $wherepart AND password={pass} $confirmSQL");
        $db->assignVar('email', $email);
        
        $passToSearch = $isRawPass ? User::passwordToHash($password) : $password;
        $db->assignVar('pass', $passToSearch);
        
        $row = $db->getRow();
        
        if ($row)
        {
            $row['avatarurl'] = Image::getGravatarUrl($row['email']);
        }
        
        return $row;
    }
    
    public static function getDetailsByEmail($email)
    {
        Debug::log("Getting details by email ..");
        $db = DB::getInstance();
        $db->prepare("SELECT * FROM users WHERE email={email}");
        $db->assignVar('email', $email);
        
        return $db->getRow();        
    }
    
    
    public static function getLoggedinUserId()
    {
        Debug::log("Getting logged in user id...");
            return isset($_SESSION['userid']) ? $_SESSION['userid'] : false;
    }
    
    public static function somethingWrongWithUserDetails($details, $onlyUpdateFields = false)
    {
        Debug::log($details, "Validating if something is wrong with user details...");
        
        if ($onlyUpdateFields )
        {
            $required = array('username', 'email');
        }
        else
        {
            $required = array('username', 'email', 'password', 'captcha');
        }
        
        foreach ($required as $field)
        {
            if (!isset($details[$field]))
            {
                Debug::log("Missing field $field");
                return array('MISSING_' . strtoupper($field), 'field ' . $field . ' is missing');
            }
        }

        $v = new Validator();
        if (in_array('email', $required) && !$v->isValidEmail($details['email']))
        {
            Debug::log("Invalid email!");
            return array('INVALID_EMAIL', 'invalid e-mail address');
        }
        
        elseif (in_array('password', $required) && !$v->isValidPassword($details['password'])){
            Debug::log("Password too short!");
            return array('INVALID_PASS','password too short');
        }
        
        elseif (in_array('captcha', $required) && !Captcha::verifyCaptchaQuestion($details['captcha'])) {
            Debug::log("Invalid captcha!");
            return array('INVALID_CAPTCHA','invalid captcha');
        }
        
        Debug::log("Nothing wrong!");
        return array(false, false);
    }

    public static function getLoggedinUserData()
    {
        Debug::log("Getting logged in user data");
        $userID = self::getLoggedinUserId();

        if ($userID)
        {
            Debug::log("We have a logged in userid in session! -> $userID");
                return User::getUserData($userID);
        }

        Debug::log("No logged-in user id retrieved!");
        return false;
    }

    //will try to get it from session as 'cache' ?
    public static function getUserData($userid, $invalidate = false)
    {
        Debug::log("Getting user data...");
        if ($invalidate || !isset($_SESSION['userdata_' . $userid]))
        {	
            Debug::log("Not set or called with invalidate! Storing in session....");
            $details = self::getDetails($userid);
            
            $_SESSION['userdata_' . $userid] = $details;
        }	

        return $_SESSION['userdata_' . $userid];
    } 
    
    public static function passwordToHash($password)
    {
        Debug::log("Converting password $password to hash. ..");
            $passWordSalt = Configuration::get('auth', 'passwordsalt');

            return md5($passWordSalt . $password);
    }
    
    public static function getList($start = 0, $limit = 30)
    {
        Debug::log("Getting list of users start $start limit $limit ...");

        $db = DB::getInstance();
        $db->prepare('SELECT * from users u WHERE 1
                            ORDER BY u.userid DESC 
                            LIMIT {start},{limit} ');
        $db->assignInt('start', $start);
        $db->assignInt('limit', $limit);

        $results = $db->getResults();
        if ($results)
        {
            return $results;
        }
        else
        {
            return false;
        }
    }

     public static function addNew($fields, $rights)
    {
        return DB::updateOrInsert('users', $fields);
    }

    public static function delete($userid)
    {
        Debug::log("Will delete every trace of user #$userid ...");
        
        $db = DB::getInstance();
        $db->prepare('DELETE FROM users WHERE userid={id} LIMIT 1');
        $db->assignInt('id',$userid);

        $a =  $db->execute();
        
        $db->prepare("DELETE bb.*,s.*,b.* FROM boards b 
                                LEFT JOIN widgets s ON b.boardid=s.boardid 
                                LEFT JOIN bars bb ON bb.widgetid =s.widgetid
                                WHERE b.createdbyuserid={id} AND b.privacy='PRIVATE'");
        $db->assignInt('id',$userid);
        $b =  $db->execute();
        
        $db->prepare("DELETE FROM bar_users WHERE userid={id}");
        $db->assignInt('id',$userid);
        $c =  $db->execute();
        
        $db->prepare("DELETE FROM user_boards WHERE userid={id}");
        $db->assignInt('id',$userid);
        $d =  $db->execute();

        return $a && $b && $c && $d;
    }
    
    
    public static function getUserCount()
    {
        $db = DB::getInstance();
        $db->prepare('SELECT COUNT(userid) as count FROM users;');
        
        $row = $db->getRow();
        
        return $row['count'];
    }
    
    public static function getDetails($userid)
    {
        Debug::log("Fetching details from user $userid. ..");
        $db = DB::getInstance();
        $db->prepare('SELECT * FROM users WHERE userid={id}');
        $db->assignInt('id', $userid);

        $results = $db->getRow();
        
        if ($results)
        {   
            $results['avatarurl'] = Image::getGravatarUrl($results['email']);
            
            $results['latestactivity'] = self::getLatestActivity($userid);
            return $results;
        }
        else
        {
            return false;
        }
    }
}

?>