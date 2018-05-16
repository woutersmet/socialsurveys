<?php

/*
 OK SO LOGIN IS 3 STEPS:
 - receive email, if it is a user, issue a challenge
 - app mixes challenge with password trough an md5 hash and sends as 'response'
 - if response is ok, we store it with the email and check this one each time for an API call
*/

class api_loginController extends ApiBaseController
{
 
 public function __construct($login)
    {
    	parent::__construct($login);

    	$this->requireSParameter();
    }

    //app needs valid 'app code' (= md5 of useremail with their appsecret)
    //FIRST CALL APP SHOULD DO
    //for example: http://local.barlisto.com/api/login?s=getchallenge&email=woutersmet@gmail.com&appid=wouteriphone
    public function displayGetChallenge()
    {
    	$this->requireParams(array('email', 'appid'));

    	$email = $this->v->getValue('email');
    	$appid = $this->v->getValue('appid');
    	
    	if (!isset(API::$registeredApplications[$appid])){
    		$this->outputError('invalid_apid', $appid);
    	}

    	//verify that app exists with some app id and that not just anybody is trying to get to the API? (requiring an APP id + hash of the 'app secret')
    	//Perhaps even verify that the user gave this app permission in his settings??? (oauth typically does this also as part of the 'auth dance')

    	$user = User::getDetailsByEmail($email);

    	if ($user){
    		//generate temporary 'challenge'
    		$challenge = Util::generateRandomString(5);

    		//calculate hash with user password and challenge code and store it in DB with time stamp
    		$response = API::generateAndStoreResponse($challenge, $appid, $user['email'], $user['password']);
    		//send temp response back
    		$this->output(array ('success' => 1, 'challenge' => $challenge));
    	}
    	else {
    		$this->outputError('user_not_found', $email);
    	}
    }

    //SECOND CALL THE APP NEEDS TO DO!
    //example: http://local.barlisto.com/api/login?s=authenticate&email=woutersmet@gmail.com&response=aa7378c7cb5ce929ed1b6ace5c70891a
    public function displayAuthenticate()
    {
    	$this->requireParams(array('email', 'response'));

    	//receive email + challenge response
    	$email = $this->v->getValue('email');
    	$response = $this->v->getValue('response');

    	//verify email + hash + timestamp (was hash correct + created within last 10 minutes?) in user db
    	$correctresponse = Api::verifyResponse($email, $response);
    	
    	if ($correctresponse){
	    	//if so, generate some random access token, store that in DB, and allow only that as auth with each API call from now on
			$accesstoken = Util::generateRandomString(15);

			API::storeAccessToken($email, $accesstoken);

			$this->output(array("access_token" => $accesstoken, "success" => "FUCKYEAH"));
		}
		else {
			$this->outputError("invalid_response", "The correct responsecode should be provided within 5 minutes of receiving the challenge code, and is calculated as follows: MD5(CHALLENGE + APPSECRET + MD5('blablawhatevdernotaword' + USERPASSWORD))");
		}
    	//with each API call, require the hash, fetch the user based on it, and check with user data if user can get what is requested

    	//invalidating happens by removing the hash - DO THIS WHENEVER USER RESETS PASSWORD
    }
}

?>