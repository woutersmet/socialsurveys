<?php

//stuff for creating adding managing surveys
class SocialSurveyLog
{
    public static function getBusiestBoards()
    {
        Debug::log("Getting busiest boards...");
        
        $db = DB::getInstance();
        $db->prepare("SELECT COUNT( * ) AS logcount, b.*, u.username, u.userid
                FROM  `changelogs` c
                JOIN boards b ON b.boardid = c.boardid
                LEFT JOIN users u ON b.createdbyuserid=u.userid
                WHERE 1 
                GROUP BY c.boardid
                ORDER BY  logcount DESC 
                LIMIT 0 , 200");
        return $db->getResults();
    }
    
    public static function addLog($surveyid, $userid, $entitytype, $action, $entityid = false, $specifics = false)
    {
        Debug::log($specifics, "Adding log for survey $surveyid user $userid action $action with these specifics...");
        
        $specifics = serialize($specifics);
        
        return DB::updateOrInsert('changelogs', array(
            array('key' => 'surveyid', 'value' => $surveyid, 'type' => 'var'),
            array('key' => 'userid', 'value' => $userid, 'type' => 'int'),
            array('key' => 'entitytype', 'value' => $entitytype, 'type' => 'var'),
            array('key' => 'entityid', 'value' => $entityid, 'type' => 'int'),
            array('key' => 'action', 'value' => $action, 'type' => 'var'),
            array('key' => 'specifics', 'value' => $specifics, 'type' => 'var'),
            array('key' => 'sessionid', 'value' => session_id(), 'type' => 'var'),
            array('key' => 'dateadded', 'value' => 'NOW', 'type' => 'function'),
        ));
    }
}

?>