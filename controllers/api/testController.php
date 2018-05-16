<?php

class api_testController extends ApiBaseController
{
	
	public function __construct($login)
	{
        parent::__construct($login);

        $this->requireSParameter();
	}

	//something like http://local.barlisto.com/api/test?s=generateresponse&email=woutersmet@gmail.com&appsecret=test&password=asdf&challenge=xsTkK
	//DOES WHAT THE USER APP SHOULD DO
	public function displayGenerateResponse()
	{
		$this->requireParams(array('email', 'password', 'appsecret', 'challenge'));
		$mail = $this->v->getValue('email');
		$password = $this->v->getValue('password');
		$appsecret = $this->v->getValue('appsecret');
		$challenge = $this->v->getValue('challenge');

		//allright so this is what our app will have to do
		$passWordSalt = 'blablawhatevdernotaword';

		$userpasswordhash = md5($passWordSalt . $password);

		$correctresponse = md5($challenge . $appsecret . $userpasswordhash);

		$this->output(array('calculated_response' => $correctresponse));
	}
}

?>