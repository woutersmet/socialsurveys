<?php

Class Cookie
{

    public static function get($key)
    {
        Debug::log("Retrieving cookie value for $key");
        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : false;
    }
    
    public static function set($key, $value, $expire = false, $domain = false)
    {
        if (!$expire)
        {
            $expire = time()+3600*24*30; //default 30 days
        }
        
        if (!$domain)
        {
            $domain = '/';
        }
        
        Debug::log("Setting cookie with key $key. Expire: $expire Domain: $domain)");
        return setcookie($key, $value, $expire, $domain);
    }
    
    public static function delete($key, $domain = '/')
    {
        Debug::log("Deleting cookie: $key (by setting expire in past) for domain: $domain");
        return setcookie ($key, "", time() - 3600, '/');
    }
}

?>