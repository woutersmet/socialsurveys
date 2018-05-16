<?php

//stuff for creating adding managing surveys / lists / bars
class SocialSurveys
{
    
    public static $scopeitems = array(
        array('label' => 'email', 'description' => 'Email'),
        array('label' => 'user_about_me', 'description' => 'About me'),
        array('label' => 'user_events', 'description' => 'Events'),
        array('label' => 'user_birthday', 'description' => 'Birthday'),
        array('label' => 'user_likes', 'description' => 'Likes'),
        array('label' => 'user_interests', 'description' => 'Interests'),
        array('label' => 'user_education_history', 'description' => 'Education history'),
        array('label' => 'user_religion_politics', 'description' => 'Religion/politics'),
        array('label' => 'user_status', 'description' => 'Current status'),
        array('label' => 'user_relationships', 'description' => 'Relationships'),
        array('label' => 'user_photos', 'description' => 'Photos')
        );
    
    public static function makeSurveyPublic($surveyid)
    {
        Debug::log("Making survey public!");
        return DB::updateOrInsert('surveys', array(array('key' => 'privacy', 'value' => 'PUBLIC', 'type'=>'var')),
                                            array(array('key' => 'surveyid', 'value' => $surveyid, 'type' => 'var'))
                );
    }

    public static function insertPossibleAnswer($surveyid, $questionid, $possibleanswertext, $numericvalue = 0)
    {
        Debug::log("Inserting possible answer with data: $surveyid, $questionid, $possibleanswertext, $numericvalue)");

        return DB::updateOrInsert('question_possibleanswers',
            array(
                array('key' => 'surveyid', 'value' => $surveyid, 'type' => 'var'),
                array('key' => 'questionid', 'value' => $questionid, 'type' => 'int'),
                array('key' => 'possibleanswertext', 'value' => $possibleanswertext, 'type' => 'var'),
                array('key' => 'numericvalue', 'value' => $numericvalue, 'type' => 'int'),
                array('key' => 'dateadded', 'value' => 'NOW', 'type' => 'function')
                ));
    }
    
    public static function getSurveyCount()
    {
        Debug::log("Getting survey count...");
        
        $db = DB::getInstance();
        $db->prepare('SELECT COUNT(surveyid) as count FROM surveys;');
        
        $row = $db->getRow();
        
        return $row['count'];
    }
    
    public static function removeQuestion($surveyid, $questionid)
    {
        Debug::log("Removing question $questionid on survey $surveyid ...");

        $db = DB::getInstance();
        $db->prepare("DELETE FROM questions WHERE surveyid={surveyid} AND questionid={questionid} LIMIT 1");
        $db->assignVar('surveyid', $surveyid);
        $db->assignInt('questionid', $questionid);

        $result1 = $db->execute();

        $db->prepare("DELETE FROM questions WHERE surveyid={surveyid} AND questionid={questionid} LIMIT 1");
        $db->assignVar('surveyid', $surveyid);
        $db->assignInt('questionid', $questionid);

        $result2 = $db->execute();

        return $result1 && $result2;
    }

    public static function getRespondents($surveyid, $start = 0, $limit = 100)
    {
        Debug::log("Getting respondents of survey $surveyid .... Limit: $limit");

        $db = DB::getInstance();
        $db->prepare("SELECT * FROM respondents r JOIN respondent_surveys s ON r.respondentid=s.respondentid WHERE surveyid={surveyid} LIMIT {start},{limit}");
        $db->assignVar('surveyid', $surveyid);
        $db->assignInt('start', $start);
        $db->assignInt('limit', $limit);

        $respondents = $db->getResults();

        $ids = Util::resultsetToKeyArray($respondents, 'respondentid');

        //get answers
        $db->prepare("SELECT *,a.questionid as questionanswered FROM respondent_answers a 
                        LEFT JOIN question_possibleanswers p ON a.answeredid=p.possibleanswerid 
                        WHERE a.respondentid IN ({respondentids}) 
                        ORDER BY respondentid,respondentanswerid
            ");
        $db->assignIntArray('respondentids', $ids);

        $answers = $db->getResults();

        $idsaskey = array_flip($ids);
        foreach ($respondents as $r){
            $r['answers'] = array();
            $idsaskey[$r['respondentid']] = $r;
        }

        foreach ($answers as $answer){
            $idsaskey[$answer['respondentid']]['answers'][] = $answer;
        }

        $results = array_values($idsaskey);

        Debug::log($results, "Answer results!");

        return $results;
    }

    public static function getQuestion($questionid, $asJson = false)
    {
        Debug::log("Getting (non-list)question $questionid...");
    
        $question = DB::selectRow('questions', array(array('key' => 'questionid', 'value' => $questionid, 'type' => 'int')));

        return $asJson ? json_encode(array($question['questionid'] => $question)) : $question;
    }
    
    public static function getList($listid, $asJson = false)
    {
        Debug::log("Getting list $listid...");
        
        $db = DB::getInstance();
        $db->prepare("SELECT b.*,l.*,bb.* FROM surveys b 
            LEFT JOIN questions l ON b.surveyid=l.surveyid
            LEFT JOIN bars bb ON l.questionid=bb.questionid
            WHERE type='LIST' AND l.questionid={listid}");
        $db->assignInt('listid', $listid);
        
        $results = $db->getResults();
        
        $reordered = self::sortRowsIntoQuestions($results);
        
        return $asJson ? json_encode($reordered) : $reordered;
    }
    
    public static function getSurveyBasic($surveyid)
    {
        Debug::log("Getting basic survey row");
        
        $row = DB::selectRow('surveys', array(array('key' => 'surveyid', 'value' => $surveyid, 'type' => 'var')));
        
        $row['shareurl'] = self::getShareUrl($surveyid);
        $row['sharedurlsecret'] = self::getSharedUrlSecret($surveyid);
        
        return $row;
    }
    
    public static function createQuestion($surveyid, $questiontext, $ismandatory, $questiontype)
    {
        Debug::log("Creating question '$questiontext' for survey $surveyid ...");
        
        $userid = User::getLoggedinUserId();
        
        $data = array(
            array('key' => 'questiontext', 'value' => $questiontext, 'type' => 'var'),
            array('key' => 'questiontype', 'value' => $questiontype, 'type' => 'var'),
            array('key' => 'ismandatory', 'value' => $ismandatory, 'type' => 'var'),
            array('key' => 'dateadded', 'value' => 'NOW', 'type' => 'function'),
            array('key' => 'lastedited', 'value' => 'NOW', 'type' => 'function'),
            array('key' => 'lasteditedbyuserid', 'value' => $userid, 'type' => 'int'),
            array('key' => 'createdbyuserid', 'value' => $userid, 'type' => 'int'),
            array('key' => 'questionorder', 'value' => time(), 'type' => 'int'),
            array('key' => 'surveyid', 'value' => $surveyid, 'type' => 'var'),
            );
        
        return DB::updateOrInsert('questions', $data);
    }

    public static function updateSurvey($surveyid, $surveyname, $description = '')
    {
        Debug::log("Updating survey $surveyid...");
        
        $result = DB::updateOrInsert('surveys', array(
                array('key' => 'surveyname', 'value' => $surveyname,'type' => 'var'),
                array('key' => 'surveydescription', 'value' => $description, 'type' => 'var')
                ),
            array(
                array('key' => 'surveyid', 'value' => $surveyid, 'type' => 'var')
                )
            );

        return $result;
    }
    
    public static function createSurvey($userid, $surveyname, $description = '')
    {
        Debug::log("Creating survey...");
        
        $insertid = DB::updateOrInsert('surveys', array(
            array('key' => 'surveyname', 
                'value' => $surveyname, 
                'type' => 'var'
                ),
            array('key' => 'surveydescription', 
                'value' => $description, 
                'type' => 'var'
                ),
            array('key' => 'createdbyuserid', 
                'value' => User::getLoggedinUserId(), 
                'type' => 'int'
                ),
                array('key' => 'dateadded',
                    'value' => 'NOW',
                    'type' => 'function')
                ));
        
        if ($insertid) {
            $surveyid = md5(microtime());
            DB::updateOrInsert('surveys', array(
                    array('key' => 'surveyid', 'value' => $surveyid, 'type' => 'var')
                    ),
                array(
                    array('key' => 'surveyinsertid', 'value' => $insertid, 'type' => 'int')
                    )
            );

            self::connectUserToSurvey($userid, $surveyid);

            return $surveyid;
        }

        return false;
    }
    
    public static function connectUserToSurvey($userid, $surveyid)
    {
        Debug::log("Adding user $userid as owner to survey $surveyid...");
        $db = DB::getInstance();
        $db->prepare("INSERT IGNORE INTO user_surveys SET userid={userid}, surveyid={surveyid}, dateadded=NOW()");
        $db->assignInt('userid', $userid);
        $db->assignVar('surveyid', $surveyid);
        
        return $db->execute();
    }
    
    public static function removesurvey($surveyid, $softDelete = true)
    {
        Debug::log("Removing survey $surveyid ...");
        
        if ($softDelete)
        {
            Debug::log("Only soft deleting!");
            
            return DB::updateOrInsert('surveys', 
                    array(array('key' => 'status', 'value' => 'DELETED', 'type' => 'var')),
                    array(array('key' => 'surveyid', 'value' => $surveyid, 'type' => 'int'))
                    );
        }
        
        Debug::log("HARD deleting!");
        
        $db = DB::getInstance();
        $db->prepare("DELETE FROM surveys WHERE surveyid={surveyid} LIMIT 1");
        $db->assignVar('surveyid', $surveyid);
        $a = $db->execute();
        
        $db->prepare("SELECT questionid FROM questions WHERE surveyid={surveyid}");
        $db->assignVar('surveyid', $surveyid);
        $questions = $db->getResults();
        
        if ($questions )
        {
            $questionids = array();

            foreach ($questions as $question)
            {
                $questionids[] = $question['questionid'];
            }

            $db->prepare("DELETE FROM questions WHERE surveyid={surveyid}");
            $db->assignVar('surveyid', $surveyid);
            $b =$db->execute();

            $db->prepare("DELETE FROM bars WHERE questionid IN ({questionids})");
            $db->assignIntArray('questionids', $questionids);
            $c = $db->execute();
        }

        $db->prepare("DELETE FROM user_surveys WHERE surveyid={surveyid}");
        $db->assignVar('surveyid', $surveyid);
        $d = $db->execute();
        
        return $a && ($questions ? ($b && $c) : true) && $d;
    }
    
    public static function removeBar($barid)
    {
        Debug::log("Removing bar $barid ...");
        
        $db = DB::getInstance();
        $db->prepare("DELETE FROM bars WHERE barid={barid} LIMIT 1");
        $db->assignInt('barid', $barid);
        $db->execute();
        
        return $db->execute();
    }
    
    public static function getUserSurveys($userid = false, $limit = 100)
    {
        Debug::log("Getting user surveys $userid ...");
        
        $userwhere = $userid ? 'ub.userid={userid}' : '1';

        $db = db::getInstance();
        $db->prepare("SELECT * , (
            SELECT COUNT( questionid ) 
            FROM questions l
            WHERE l.surveyid = ub.surveyid AND questiontype!='GRIDROW' AND questiontype!='INSTRUCTION' AND questiontype!='PAGEBREAK'
            ) as questioncount,
        (
            SELECT COUNT( respondentid ) 
            FROM respondent_surveys r
            WHERE r.surveyid = ub.surveyid
            ) as respondentcount
            FROM user_surveys ub
            JOIN surveys b ON ub.surveyid = b.surveyid
            JOIN users u ON b.createdbyuserid = u.userid
            WHERE $userwhere
            LIMIT {limit}");
        $db->assignInt('userid', $userid);
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

    public static function userCanManageSurvey($userid, $surveyid)
    {
        Debug::log("Can user $userid manage survey $surveyid...?");

        $canmanage = false !== DB::selectRow('user_surveys', array(
                                    array('key' => 'userid',
                                        'value' => $userid,
                                        'type' => 'int'),
                                    array('key' => 'surveyid',
                                        'value' => $surveyid,
                                        'type' => 'int')));

        if (!$canmanage){ 
            Debug::error("NO! User $userid trying to manage $surveyid without permission");
        }
        return $canmanage;
    }
    
    public static function sortRowsIntoQuestions($results)
    {
        Debug::log($results, "Reordering these rows...");
        $reordered = array();
        foreach ($results as $result)
        {
            //only results with questionid matter (empty surveys generate rows without questionid
            if (!$result['questionid']) continue;

            //question not encountered yet! Creating it + first bar (icludign avatar urls)
            if (!isset($reordered[$result['questionid']]))
            {
               $reordered[$result['questionid']] = array_merge(
                        array_intersect_key($result, array_flip(array(
                            'listname', 'questionid', 'dateadded', 'xpos', 'ypos','type','collapsed' , 'questiontext', 'backgroundcolor', 'importance','questionuserid','questionusername','zindex'
                                ))),
                                array(
                                    'bars' => array(array_merge(
                                                $result, 
                                                array('avatarurl' => Image::getGravatarUrl($result['baremail'])
                                                ))),
                                    'avatarurl' => Image::getGravatarUrl($result['questionemail'])
                                    )
                        );
            }
            else
            {
                $reordered[$result['questionid']]['bars'][] = array_merge(
                        $result, 
                        array('avatarurl' => Image::getGravatarUrl($result['baremail'])
                        ));
            }
        }
        
        //make sure positions are correct
        foreach ($reordered as $questionid => &$question)
        {
            Util::sortDBResults($question['bars'], 'barposition');
        }
        
        Debug::log($reordered, "Into this");
        
        return $reordered;
    }
    
    public static function getSurveyUsers($surveyid)
    {
        Debug::log("Getting users with access to survey $surveyid ...");
        
        $db = DB::getInstance();
        $db->prepare("SELECT * FROM user_surveys us
                        JOIN users u ON us.userid=u.userid
                        WHERE us.surveyid={surveyid}
                        ORDER by us.dateadded DESC
                        ");
        $db->assignVar('surveyid', $surveyid);
        
        $results = $db->getResults();
        Debug::log($results, "Results");
        
        return $results;
    }

    public static function getSurveyAnswerableQuestions($surveyid)
    {
        Debug::log("getting 'flat' questions - including grid rows - from survey $surveyid ....");

        $db = DB::GetInstance();
        $db->prepare("SELECT * FROM questions WHERE surveyid={surveyid} AND questiontype IN ('FREETEXT', 'MULTIPLECHOICE', 'GRIDROW','INTEGER', 'BOOLEAN')");

        $db->assignVar('surveyid', $surveyid);
        $questions = $db->getResults();

        return $questions;
    }
    
    //questionsonly is for showing results, it will show each 'grid row' on the same level as other questions, and no instructions etc etc
    public static function getSurveyDetails($surveyid)
    {
        Debug::log("Getting survey $surveyid. ");
        
        $db = DB::getInstance();
        $db->prepare("SELECT *
            FROM surveys
            WHERE surveyid={surveyid}");
        $db->assignVar('surveyid', $surveyid);
        $survey = $db->getRow();

        if ($survey)
        {
            Debug::log($survey, "Got raw results, adding questions...");
            
            $db->prepare("SELECT *
                    FROM questions
                    WHERE surveyid={surveyid}
                    ORDER BY questionorder,questionid
                ");

            $db->assignVar('surveyid', $surveyid);
            $survey['questions'] = $db->getResults();

            if ($survey['questions']){
                $gridrowsbyquestionid = array();
                foreach ($survey['questions'] as $i => &$question){
                    if ($question['questiontype'] == 'MULTIPLECHOICE'){
                        Debug::log("Question " . $question['questionid'] . ' is a multiple choice quesiton! Adding possible answers...');
                        
                        $db = DB::getInstance();
                        $db->prepare("SELECT * FROM question_possibleanswers WHERE surveyid={surveyid} AND questionid={questionid}");
                        $db->assignInt('questionid', $question['questionid']);
                        $db->assignVar('surveyid', $surveyid);

                        $question['possibleanswers'] = $db->getResults();
                    }
                    elseif ($question['questiontype'] == 'GRIDROW'){
                        $gridrowsbyquestionid['grid' . $question['gridquestionid']][] = $question;
                        unset($survey['questions'][$i]);
                    }
                }

                Debug::log($gridrowsbyquestionid, "Encountered these grid rows...");
                //now add grids to their questions
                foreach ($survey['questions'] as &$question){
                    if ($question['questiontype'] == 'GRID'){
                        Debug::log("Question " . $question['questionid'] . ' is a grid quesiton! Adding rows and columns...');
                        $question['gridrows'] = $gridrowsbyquestionid['grid' . $question['questionid']];

                        $db->prepare("SELECT * FROM question_possibleanswers WHERE surveyid={surveyid} AND gridquestionid={questionid}");
                        $db->assignInt('questionid', $question['questionid']);
                        $db->assignVar('surveyid', $surveyid);
                        $question['gridcolumns'] = $db->getResults();

                        //adding them even AGAIN here (for creating table in template engine)
                        foreach ($question['gridrows'] as &$row){
                            $row['rowcolumns'] = $question['gridcolumns'];
                        }

                        Debug::log($question, "complete question info");
                    }
                }

            }

            //add owners
            $survey['users'] = self::getSurveyUsers($surveyid);

            //scope
            $exploded = explode(',', $survey['facebookscope']);
            $survey['facebookscopeitems'] = self::$scopeitems;
            foreach ($survey['facebookscopeitems'] as &$scope){
                if (in_array($scope['label'], $exploded)){
                    $scope['enabled'] = true;
                }
                else {
                    $scope['enabled'] = false;
                }
            }
        }
        else
        {
            return false;
        }
        
        Debug::log($survey, "Final results");

        return $survey;
    }

    public static function sendEmailReminders($date = false)
    {
        if (!$date){
            $date = date('Y-m-d',time());
        }
        Debug::log("Will send reminders (if there are any) for date $date ...");

        $db = DB::getInstance();
        $db->prepare("SELECT * FROM bars b
            JOIN questions w on b.questionid=w.questionid
            JOIN surveys bo on w.surveyid = bo.surveyid
            JOIN user_surveys bu on bu.surveyid=bo.surveyid
            JOIN users u on u.userid = bu.userid
            WHERE u.reminderemails='YES' AND reminderdate={date}");
        $db->assignVar('date', $date);
        $results = $db->getResults();

        if ($results){
            $total = 0;
            foreach ($results as $result){
                $total += Mail::sendReminderMail($result);
            }

            return $total == count($results);
        }
    }
}

?>