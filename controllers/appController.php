<?php
class appController extends appBaseController 
{
    public $requiredRights = User::USERRIGHTS_USER;
    
    public function __construct($login)
    {
        parent::__construct($login);
        
        $this->pv['staticversion'] = 1;

        $requestedsurveyid = $this->v->getValue('surveyid');
        if ($requestedsurveyid && SocialSurveys::userCanManageSurvey($this->userid, $requestedsurveyid))
        {
            $_SESSION['surveyid'] = $this->v->getValue('surveyid');
            
            $this->displayFunction = 'displayOne';
        }
        
        if (isset($_SESSION['surveyid']))
        {
            $this->surveyid = $_SESSION['surveyid'];
        }
        else
        {
            $this->surveyid = false;
        }
        
        $this->pv['section'] = 'overview';
        
    }

    public function actionSaveOrder()
    {
        $questionorder = $this->v->getValue('question');

        foreach ($questionorder as $order => $questionid){
            DB::updateOrInsert('questions', array(
                array('key'=> 'questionorder', 'value' => $order, 'type' => 'int')),
            array(
                array('key'=> 'questionid', 'value' => $questionid, 'type' => 'int'),
                array('key'=> 'surveyid', 'value' => $this->surveyid, 'type' => 'var')));
        }

        die("SURVEY: " . $this->surveyid . 'post' . print_r($questionorder));
    }

    public function actionSetintrotext()
    {
        $introtext = $this->v->getValue('introtext');
        $this->setActionState(DB::updateOrInsert('surveys', 
            array(
                array('key' => 'introtext', 'value' => $introtext, 'type' => 'var')
            ), 
                array(array('key' => 'surveyid', 'value' => $this->surveyid, 'type' => 'var'))
            ));
    }

    public function displayOne () 
    {
         $this->templateFile = 'app/app.survey.html';

         $surveyid = $this->v->getValue('surveyid');
         $survey = SocialSurveys::getSurveyDetails($surveyid); 

        $this->pv['survey'] = $survey;

        if ($survey && $this->v->getValue('s') == 'respondents'){
            $this->templateFile = 'app/app.respondents.html';

            $this->pv['answerablequestions'] = SocialSurveys::getSurveyAnswerableQuestions($this->surveyid);

            $respondents = SocialSurveys::getRespondents($surveyid);
            $this->pv['respondents'] = $respondents;
        }
    }
 
    public function displayIndex ()
    {
        $this->templateFile = $this->ismobile ? 'app/mobile.app.html' : 'app/app.html';
        
        $usersurveys = SocialSurveys::getUsersurveys($this->login->user['userid']);
            
        $this->pv['usersurveys'] = $usersurveys; 
    }
    
    public function actionRemoveQuestion()
    {
        $questionid = $this->v->getValue('questionid');
        
        if (!SocialSurveys::userCanManageSurvey($this->userid, $this->surveyid))
        {
            $this->fail("You do not have permission to remove this question.");
            return;
        }
        
        //SocialSurveyLog::addLog($this->surveyid, $this->userid, 'survey', 'REMOVE', $this->surveyid);
        
        $this->setActionState(SocialSurveys::removequestion($this->surveyid, $questionid), "Succesfully removed survey", "Something went wrong removing your survey");
        $this->pv['addedquestion'] = true;
    }

    public function actionsetfacebookscope()
    {
        $scope = array_keys($this->v->getValue('scope'));

        foreach ($scope as &$item){
            if (!in_array($item, SocialSurveys::$scopeitems)){
                unset($item);
            }
        }

        $tostore = implode(',', $scope);

        DB::updateOrInsert('surveys', array(
             array('key' => 'facebookscope', 'value' => $tostore, 'type' => 'var')
            ),
        array(array('key' => 'surveyid', 'value' => $this->surveyid, 'type' => 'var'))
        );
    }
    
    public function actionAddQuestion()
    {
        $type = $this->v->getValue('questiontype');
        $questiontext = ($type == 'INSTRUCTION' ? $this->v->getValue('instructiontext') : $this->v->getValue('questiontext'));

        if (!$questiontext){
            $this->fail('question text needed');
            return false;
        }

        $ismandatory = $this->v->getValue('ismandatory') == 'on' ? 'YES' : 'NO';
        $questionid = SocialSurveys::createQuestion($this->surveyid, $questiontext, $ismandatory, $type);

        if ($type == 'MULTIPLECHOICE')
        {
            Debug::log("Multiple choice question! Inserting possible answers...");

            $possibleanswers = $this->v->getValue('possibleanswers');
            $numericvalues = $this->v->getValue('possibleanswerinternalnumericvalues');

            foreach ($possibleanswers as $i => $answer){
                SocialSurveys::insertPossibleAnswer($this->surveyid, $questionid, $possibleanswers[$i], $numericvalues[$i]);
            }
        }
        elseif ($type == 'GRID')
        {
            foreach ($this->v->getValue('gridrows') as $i => $row){
                DB::updateOrInsert('questions',array(
                        array('key' => 'surveyid', 'value' => $this->surveyid, 'type' => 'var'),
                        array('key' => 'questiontext', 'value' => $row, 'type' => 'var'),
                        array('key' => 'questiontype', 'value' => 'GRIDROW', 'type' => 'var'),
                        array('key' => 'ismandatory', 'value' => $ismandatory, 'type' => 'var'),
                        array('key' => 'gridquestionid', 'value' => $questionid, 'type' => 'int')
                    ));
            }

            $numericvalues = $this->v->getValue('gridcolumnnumericvalues');
            foreach ($this->v->getValue('gridcolumns') as $i => $column){
                DB::updateOrInsert('question_possibleanswers',array(
                        array('key' => 'surveyid', 'value' => $this->surveyid, 'type' => 'var'),
                        array('key' => 'possibleanswertext', 'value' => $column, 'type' => 'var'),
                        array('key' => 'numericvalue', 'value' => (isset($numericvalues[$i]) ? $numericvalues[$i] : 0), 'type' => 'var'),
                        array('key' => 'gridquestionid', 'value' => $questionid, 'type' => 'int'),
                        array('key' => 'questionid', 'value' => $questionid, 'type' => 'int')
                    ));
            }
        }
       
        $this->setActionState($questionid, 'Success!', 'Fail!');
    }
    
    public function actionsetShareSettings()
    {
        $privacy = $this->v->getValue('privacy');
        
        if (!in_array($privacy, array('PRIVATE', 'SHARED', 'PUBLIC')))
        {
            $this->fail('Invalid privacy setting specified');
             return;
        }
        
        DB::updateOrInsert('surveys', array(array('key' => 'privacy', 'value' => $privacy, 'type' => 'var')),
                    array(array('key' => 'surveyid', 'value' => $this->surveyid, 'type' => 'int'))
                        );
        
        //user wanst to share with an email address?
        $email = $this->v->getValue('email');
        if ($email && $this->v->isValidEmail($email))
        {
            Debug::log("We also got an email submitted! Doing da sharing thing...");
            $this->sharesurvey($email);
        }
    }
    
    public function actionsetoptions()
    {
        $privacy = $this->v->getValue('privacy');
        $surveyname = $this->v->getValue('surveyname');
        $backgroundurl = trim($this->v->getValue('backgroundurl'));
        $backgroundcolor = trim($this->v->getValue('surveycolor'));
        $backgroundrepeat = trim($this->v->getValue('backgroundrepeat'));
        
        if (!$surveyname)
        {
            $this->fail("survey needs a valid name!");
        }
        
        if (!(substr($backgroundcolor, 0, 1) == '#' && strlen($backgroundcolor) <= 7))
        {
            $this->fail("Saving failed bacause background-color is not in hex (#xxxxxx) format:" . $backgroundcolor);
        }
        
        //only owners can
        if (SocialSurveys::getsurveyAccessRights($this->userid, $this->surveyid) == SocialSurveys::surveyRIGHTS_OWNER)
        {
            SocialSurveyLog::addLog($this->surveyid, $this->userid, 'survey', 'CHANGE', $this->surveyid);
            
            $this->success("Your survey settings are updated.");
            DB::updateOrInsert('surveys', 
                array(
                    array('key' => 'privacy', 'value' => $privacy, 'type' => 'var'),
                    array('key' => 'backgroundurl', 'value' => $backgroundurl, 'type' => 'var'),
                    array('key' => 'backgroundrepeat', 'value' => $backgroundrepeat, 'type' => 'var'),
                    array('key' => 'surveyname', 'value' => $surveyname, 'type' => 'var'),
                    array('key' => 'surveycolor', 'value' => $backgroundcolor, 'type' => 'var')),
                array(array('key' => 'surveyid', 'value' => $this->surveyid, 'type' => 'var')));
        }
        else
        {
            $this->fail("You are not allowed to change this survey privacy settings.");
        }
    }        
    
    public function actionCreatesurvey()
    {
        $surveyname = $this->v->getValue('surveyname');
        $surveydescription = $this->v->getValue('description');
        
        if (!$surveyname)
        {
            Debug::log("User didn't bother to pick a survey name...");
            $surveyname = 'My survey';
        }        

        if ($this->v->getValue('surveyid')){
            Debug::log("Got a survey id! Will update name/description...");
            $this->setActionState(SocialSurveys::updateSurvey($this->surveyid, $surveyname, $surveydescription));
            return;
        }
        
        $surveyid = SocialSurveys::createsurvey($this->login->user['userid'], $surveyname, $surveydescription);
        
        if ($surveyid)
        {

            SocialSurveyLog::addLog($surveyid, $this->userid, 'survey', 'ADD', $surveyid);
            
            Util::forward('/app?surveyid=' . $surveyid);
        }
        else
        {
            $this->fail("something went wrong creating your survey.");
        }
    }
    
}
?>