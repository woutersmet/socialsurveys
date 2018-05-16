<?php
        
class Logging
{
    const LOG_MAIL_RECEPIENTS = 'woutersmet@gmail.com';
    
    public static function getLogs($start = 0, $limit = 30)
    {
        Debug::log("Getting list of logs start $start limit $limit ...");

        $db = DB::getInstance();
        $db->prepare('SELECT * FROM log_general LIMIT {start},{limit}');
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
    
    public static function sendLogMail($type, $message)
    {
        Debug::log("Sending log mail");
        
        $subject = "[Whatshop $type] " . substr($message,0, 30) . "...";
        
        $message = "The full message: \n\n<br /> " . $message . "\n Code trace: \n" . print_r(debug_backtrace(), true);
        
        mail(self::LOG_MAIL_RECEPIENTS, $subject, $message);
    }

    public static function logEvent($type,$message)
    {
        $request_uri = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        Debug::log("Adding new log with fields: $type,$message,$request_uri ...");

        $db = DB::getInstance();
        $db->prepare('INSERT INTO log_general SET
            type={type}, 
            message={message}, 
            request_uri={request_uri}, 
            dateadded=NOW()');
        $db->assignVar('type',$type);
        $db->assignVar('message',$message);
        $db->assignVar('request_uri',$request_uri);

        //die ($db->getQuery());
        try
        {
            $db->execute();
        }
        catch (ErrorException $e)
        {
            mail('woutersmet@gmail.com', "[whatshop] Logging failed", "Logging::logEvent() didn't work somehow: " . $e->getMessage());
            //nothing, if this goes wrong the db is probably having issues
        }
        
        self::sendLogMail($type, $message);
    }

    public static function delete($logid)
    {
        Debug::log("Will delete log #$logid ...");
        $db = DB::getInstance();
        $db->prepare('DELETE FROM log_general WHERE logid={id} LIMIT 1');
        $db->assignInt('id',$logid);

        return $db->execute();
    }

    public static function getDetails($logid)
    {
        $db = DB::getInstance();
        $db->prepare('SELECT * FROM log_general WHERE logid={id}');
        $db->assignInt('id', $logid);

        return $db->getRow();
    }
}
 
?>