<?php

//stuff for creating adding managing participations to surveys
class Respondents
{
    
    //access token becasue you never know?
    public static function initRespondentWithFacebookData($user_profile, $accesstoken = false)
    {
        Debug::log($user_profile, "Creating respondent with this facebook data...");

        $facebookname = isset($user_profile['name']) ? $user_profile['name'] : 'NOTPROVIDED';
        $facebookuserid = isset($user_profile['id']) ? $user_profile['id'] : 'NOTPROVIDED';
        $facebookprofileurl = isset($user_profile['link']) ? $user_profile['link'] : 'NOTPROVIDED';
        $facebookdatadump = json_encode($user_profile);
     
        $respondentid = DB::updateOrInsert('respondents', array(
                array('key' => 'dateadded', 'value' => 'NOW', 'type' => 'function'),
                array('key' => 'IP', 'value' => $_SERVER['REMOTE_ADDR'], 'type' => 'var'),
                array('key' => 'facebookname', 'value' => $facebookname, 'type' => 'var'),
                array('key' => 'facebookuserid', 'value' => $facebookuserid, 'type' => 'var'),
                array('key' => 'facebookprofileurl', 'value' => $facebookprofileurl, 'type' => 'var'),
                array('key' => 'facebookaccesstoken', 'value' => $accesstoken, 'type' => 'var'),
                array('key' => 'facebookdatadump', 'value' => $facebookdatadump, 'type' => 'var'),
            ));

        return $respondentid;
    }

}

?>