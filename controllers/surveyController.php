<?php

Class surveyController extends baseController
{

    public function __construct($login)
    {
        parent::__construct($login);

        $surveyid = $this->v->getValue('surveyid');
        if ($surveyid){
            $_SESSION['viewingsurveyid'] = $surveyid;
        }

        if (!isset($_SESSION['viewingsurveyid'])){
            $this->displayFunction = 'displayError';
        }

        //all good to go, we got a survey
        $this->surveyid = $_SESSION['viewingsurveyid'];
        $this->survey = SocialSurveys::getSurveyDetails($surveyid);
        $this->pv['respondent'] = $_SESSION['currentrespondent'];
        $this->pv['survey'] = $this->survey;

        if (isset($_SESSION['respondentstatus']) && $_SESSION['respondentstatus'] == 'FINISHED'){
            $this->displayFunction = 'displayFinished';
        }
        elseif (!(isset($_SESSION['currentrespondentid']) && $_SESSION['currentrespondentid'] > 0)){
            $this->displayFunction = 'displayIndex';
        }
        else {
            $this->displayFunction = 'displayQuestions';
        }
    }

    public function actionReset(){
        session_destroy();
        Util::forward('/survey?surveyid=' . $this->surveyid);
    }

    public function displayError()
    {
        $this->templateFile = 'survey/survey.error.html';
    }

    /*
     * This will either add a new user + shop or just a shop for an existing user!
     */
	public function actionParticipate()
	{
        $check = $this->v->getValue('browsersonly2');

        if ($check !== $_SESSION['lasthumancheck']){
            Debug::log("Participation without human check valid!");
            $this->fail("Something went wrong submitting your survey. Sure you are a human taking this survey like you are supposed to?");
            return false;
        }

        $answers = $this->v->getValue('answers');

        //create respondent if we don't have him yet
        if (!isset($_SESSION['currentrespondentid'])){
            Debug::error("Submitting but we don't know respondent yet? Did he/she not go trough facebook?");
            
            $respondentid = DB::updateOrInsert('respondents',
            array(
                array('key' => 'dateadded', 'value' => 'NOW', 'type' => 'function'),
                array('key' => 'IP', 'value' => $_SERVER['REMOTE_ADDR'], 'type' => 'var'),
                ));

            $_SESSION['currentrespondentid'] = $respondentid;
        }

        //indicate he did this survey
        DB::updateOrInsert('respondent_surveys',
            array(
                array('key' => 'dateaparticipated', 'value' => 'NOW', 'type' => 'function'),
                array('key' => 'surveyid', 'value' => $this->surveyid, 'type' => 'var'),
                array('key' => 'respondentid', 'value' => $_SESSION['currentrespondentid'], 'type' => 'int')
                )
            );

        foreach ($answers as $questionstring => $answer){
            $parts = explode('_', $questionstring);
            if (!isset($parts[2])) {
                Debug::error("Wrong answer format: $answer");
                continue;
            }

            $questiontype = $parts[1];
            $questionid = $parts[2];

            if (in_array($questiontype,array('FREETEXT','INTEGER'))){
                $answerarray = array('key' => 'answeredtext', 'value' => $answer, 'type' => 'var');
            }
            else {
                $answerarray = array('key' => 'answeredid', 'value' => $answer, 'type' => 'int');
            }
                
            $success = DB::updateOrInsert('respondent_answers',
            array(
                array('key' => 'surveyid', 'value' => $this->surveyid, 'type' => 'var'),
                array('key' => 'dateadded', 'value' => 'NOW', 'type' => 'function'),
                array('key' => 'questionid', 'value' => $questionid, 'type' => 'int'),
                array('key' => 'respondentid', 'value' => $_SESSION['currentrespondentid'], 'type' => 'int'),
                $answerarray
                ));
        }

        $this->success("Succesfully submitted your participation!");
        $_SESSION['respondentstatus'] = 'FINISHED';
        $this->displayFunction = 'displayFinished';
	}

    function displayFinished()
    {
        $this->templateFile = 'survey/survey.finished.html';
    }

    function displayIndex()
    {
        //get a respondent ASAP
        if (!isset($_SESSION['currentrespondentid'])){

            require 'lib/facebook.php';

            // Create our Application instance (replace this with your appId and secret).
            //from my app page social surveys: https://developers.facebook.com/apps/167581786732324/summary?web_hosting=true
            $facebook = new Facebook(array('appId'  => '167581786732324','secret' => 'da99b26a7e38f98abaf9467c3a5b99f5',));
            
            $permissions = array('scope' => $this->survey['facebookscope']);

            // Get User ID
            $user = $facebook->getUser();

            //To make [API][API] calls:

        if ($user) {
          try {
            // Proceed knowing you have a logged in user who's authenticated.
            $user_profile = $facebook->api('/me');
            Debug::log($user_profile, "Got this profile!");

            $respondentid = Respondents::initRespondentWithFacebookData($user_profile, $facebook->getAccessToken());
            $_SESSION['currentrespondentid'] = $respondentid;

            $_SESSION['currentrespondent'] = DB::selectRow('respondents', array(array('key' => 'respondentid', 'value' => $respondentid, 'type' => 'int')));

            //die("We got one!<pre> ." . print_r($user_profile, true) . '</pre>');
            Util::forward('/survey?surveyid=' . $this->surveyid);
              } 
              catch (FacebookApiException $e) {
                Debug::error($e);
                $user = null;
              }
            }
            else {
                $this->pv['respondentstatus'] = 'NOTAUTHENTICATED';
            }

            //Login or logout url will be needed depending on current user state.

            $this->pv['fblogoutUrl'] = $facebook->getLogoutUrl();
           $this->pv['fbloginUrl'] = $facebook->getLoginUrl($permissions);
            
            $this->templateFile = 'survey/survey.intro.html';
        }
    }

	function displayQuestions()
	{
        if ($this->survey){
            $this->pv['introtext'] = $this->survey['introtext'];
            $this->rawPageVars[] = 'introtext';
            
            $_SESSION['lasthumancheck'] = Util::generateRandomString(20);
            $this->pv['check'] = $_SESSION['lasthumancheck'];
        }
        else {
            Debug::error("Tried loading survey id $surveyid but got nothing!");
            return false;
        }

        $this->templateFile = 'survey/survey.questions.html';
	}
}

?>