<?php

/*
 * CONFIGURATION VARS
 * should be required by class.configuration.php
 */

$vars = Array(
    'general' => array(
        'sitename' => 'Social Surveys',
        'requireconfirmedemail' => false,
        'defaultlistcolor' => '#CAE1FF',
        'defaultscribblecolor' => '#fffb91',
        'defaultfilecolor' => '#000000',
        'defaultboardcolor' => '#eeeeee',
        'defaultboardimage' => '/images/background_fridge.jpg',
        'demoboardid_local' => 40,
        'demoboardid_online' => 32,
    ),
    'auth' => array(
        'recaptcha_publickey' => '6LfJeboSAAAAANp9TPTvCeUdEKJzvvhSTHRLrr9_', //see https://www.google.com/recaptcha/admin/site?siteid=314210761
        'recaptcha_privatekey' => '6LfJeboSAAAAACFeh89YoJd0Fr7kY8fxwJVAf4CS', //see https://www.google.com/recaptcha/admin/site?siteid=314210761
        'beta_users' => array('whatshop' => 'asdf', 'yellow' => 'submarine'),
        'passwordsalt'  => 'blablawhatevdernotaword',
        'debugmodehash' => 'asdf',
        'preloginrequired' => false,
        'invitecoderequired' => false,
        'invitecodes'   => array('asdf', 'test'),
        'use_captcha' => true,
       ),
    'database' => array(
        'local' => array('host' => 'localhost',
                                 'user' => 'root',
                                 'pass' => 'root',
                                  'database' => 'socialsurveys'
                        ),               
        'online' => array('host' => 'localhost',
                           'user' => 'wauter_ss',
                           'pass' => 'testing123',
                           'database' => 'wauter_socialsurveys'
                        ) 
	),						
'mail' => array(
        'site_sendername' => 'Social Surveys',
        'site_fromaddress' => 'wouter@werklozennet.be',
    ),
    'locations' => array(
        'productimages' =>           'tabignite_storage/productimages',
        'quickskin_compiledpages' => 'tabignite_storage/compiledpages'
    )
);

?>