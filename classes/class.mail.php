<?php
        
class Mail
{
    public static function sendMail($addresses,$subject,$message, $replacementheaders = false)
    {
        Debug::log("Sending mail to $addresses with subject '$subject' ...");
        // In case any of our lines are larger than 70 characters, word wrap?
        //$message = wordwrap($message, 70);

        if ($replacementheaders)
        {
            Debug::log($replacementheaders, "We got replacement headers, using those...");
            $headers = $replacementheaders;
        }
        else
        {
            Debug::log("Using our default headers....");
            // To send HTML mail, the Content-type header must be set
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

            // Additional headers
            //$headers .= 'To: Mary <mary@example.com>, Kelly <kelly@example.com>' . "\r\n";
            $sendername = Configuration::get('mail', 'site_sendername');
            $fromaddress = Configuration::get('mail', 'site_fromaddress');

            $headers .= 'From: ' . $sendername . ' <' . $fromaddress . ">\r\n";
            //$headers .= 'Cc: birthdayarchive@example.com' . "\r\n";
            //$headers .= 'Bcc: birthdaycheck@example.com' . "\r\n";
        }
        
        Debug::log($headers, "Headers set...");

        if (!is_array($addresses))
        {
            $addresses = array($addresses);
        }
        
        foreach ($addresses as $address)
        {
            $mailresult = mail($address, $subject, $message, $headers);
            Debug::log(($mailresult ? "Mail sent to $address!" : "Something went wrong sending to $address..."));
        }
    
        return $mailresult;
    }
    
    public static function sendBackupMail($backupfilepath)
    {
        Debug::log("Sending backup mail with attachment...");
        
        $subject = 'SocialSurvey - database backup';
        
        $parts = explode('/',$backupfilepath);
        $filename = $parts[count($parts) - 1];
        $parts = explode('.', $filename);
        $filetype = strtolower($parts[1]);
        
        $random_hash = md5(date('r', time()));
        
        $attachment = chunk_split(base64_encode(file_get_contents($backupfilepath)));
        
        $message = "
--PHP-mixed-$random_hash
Content-Type: multipart/alternative; boundary='PHP-alt-$random_hash'
--PHP-alt-$random_hash
Content-Type: text/plain; charset='iso-8859-1'
Content-Transfer-Encoding: 7bit

        Hi myself
        
        Attached is the database dump from Social Survey as it is right now

--PHP-alt-$random_hash
Content-Type: text/html; charset='iso-8859-1'
Content-Transfer-Encoding: 7bit

        <h2>Hi myself</h2>
        
        <p>Attached is the database dump from socialsuvey as it is right now</p>

--PHP-alt-$random_hash--

--PHP-mixed-$random_hash
Content-Type: application/$filetype; name=$filename
Content-Transfer-Encoding: base64
Content-Disposition: attachment

$attachment
--PHP-mixed-$random_hash--";
        

        $headers = "From: ".Configuration::get('mail', 'site_fromaddress')."\r\nReply-To: " . Configuration::get('mail', 'site_fromaddress');

        $headers .= "\r\nContent-Type: multipart/mixed; boundary=\"PHP-mixed-".$random_hash."\"";
        
        self::sendMail('woutersmet@gmail.com', $subject, $message, $headers);
    }
    
        public static function sendPasswordChangedMail($email, $newPass)
    {
        Debug::log("Changing 'your password has been reset' email to $email ...");
        $subject = 'BarListo - your password has been reset.';
        
        $location = 'mail/mail.passreset.html';
        
        $mailpage = new QuickSkin($location);

        $loginurl = 'http://' . $_SERVER['HTTP_HOST'] . "/login";
        $accounturl = 'http://' . $_SERVER['HTTP_HOST'] . "/app/account";

        $mailpage->assign('loginurl', $loginurl);
        $mailpage->assign('accountsettingsurl', $accounturl);
        $mailpage->assign('email', $email);
        $mailpage->assign('newpass', $newPass);
        $mailpage->assign('sitename', Configuration::get('general', 'sitename'));
        $mailcontent = $mailpage->result();
        
        //die($mailcontent);
        Mail::sendMail($email, $subject, $mailcontent);
    }
    
    public static function sendUserConfirmMail($addresses)
    {
        Debug::log($addresses, "Will send user confirm mail to these adresses ... First creating hash to verify it's him");
        
        if (!is_array($addresses))
        {
            $addresses = array($addresses);
        }
        
        $subject = 'Please confirm your e-mail address.';
        
        $location = 'mail/mail.confirm.html';
        
        foreach ($addresses as $address)
        {
            $mailpage = new QuickSkin($location);

            $hash = Mail::mailToConfirmSecretHash($address);
            $confirmurl = 'http://' . $_SERVER['HTTP_HOST'] . "/login?action=emailconfirm&a=" . urlencode($address) . "&secret=$hash";

            $mailpage->assign('confirmurl', $confirmurl);

            $mailcontent = $mailpage->result();
            
            Mail::sendMail($address, $subject, $mailcontent);
        }
    }
    
    public static function mailToConfirmSecretHash($address)
    {
        return md5('whatever' . $address);
    }
    
    public static function isValidConfirmSecret($address, $secret)
    {
        $isValid = $secret == self::mailToConfirmSecretHash($address);
        return $isValid;
    }
    
    public static function handleContactForm($contactData)
    {
        Debug::log("Sending mail after user filled contact form!");
        
        $subject = '[WHATSHOP] Contact form used';
        
        $location = 'mail/mail.contactform.html';
        
        $mailpage = new QuickSkin($location);

        $mailpage->assign('contactdata', $contactData);
        
        $mailcontent = $mailpage->result();
        
        $mails = 'woutersmet@gmail.com,stijnatdg@gmail.com, info@myshoppingtab.com';
        
        //die($mailcontent);
        Mail::sendMail($mails, $subject, $mailcontent);
    }
    
    public static function sendBoardShareNotification($email, $invitername, $boardid, $isnewuser)
    {
        Debug::log("Sending mail for board invite.");

        $board = BarListo::getBoardDetails($boardid);
        
        $subject = 'You received access to BarListo board \'' . $board['boardname'] ."'";
        
        $location = 'mail/mail.sharenotice.html';
        
        $mailpage = new QuickSkin($location);

        $mailpage->assign('board', $board);
        $mailpage->assign('isnewuser', $isnewuser);
        $mailpage->assign('invitername', $invitername);
        
        $mailcontent = $mailpage->result();
        
        //die($mailcontent);
        Mail::sendMail($email, $subject, $mailcontent);    
    }

    public static function sendWelcomeMail($email)
    {
        Debug::log("Sending welcome mail.");

        $subject = 'Welcome to Social Surveys!';
        
        $location = 'mail/mail.welcome.html';
        $mailpage = new QuickSkin($location);
        $mailcontent = $mailpage->result();
        
        //die($mailcontent);
        Mail::sendMail($email, $subject, $mailcontent);        
        
    }
}
 
?>