<?php

//verifying that the user is not a robot
//see https://www.google.com/recaptcha/admin/site?siteid=314210761


class Captcha
{
	public static function getCaptchaHtml()
	{
            include(__SITE_PATH . '/lib/recaptchalib.php');
            $publickey = Configuration::get('auth', 'recaptcha_publickey'); // you got this from the signup page
            return recaptcha_get_html($publickey);
	}
        
        public static function getCaptchaQuestion()
        {
            $a = round(rand(0,10));
            $b = round(rand(0,10));
            
            $_SESSION['correctcaptcha'] = $a + $b;
            
            return "$a + $b =";
        }
        
        public static function verifyCaptchaQuestion($userGuess)
        {
            return $_SESSION['correctcaptcha'] == $userGuess;
        }

	public static function checkValidCaptcha($challenge, $response)
	{
                include(__SITE_PATH . '/lib/recaptchalib.php');

		return recaptcha_check_answer (Configuration::get('auth', 'recaptcha_privatekey'),
	                                $_SERVER["REMOTE_ADDR"],
	                                $challenge,
	                                $response);
	}
}

?>