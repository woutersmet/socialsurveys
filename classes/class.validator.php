<?php

Class Validator
{
    private $errors = array(); 
    
    public function getValue($key)
    {
        return isset($_REQUEST[$key]) ? $_REQUEST[$key] : false;
    }
    
    public function get($key)
    {
        return isset($_GET[$key]) ? $_GET[$key] : false;       
    }
    
    public function post($key)
    {
        return isset($_POST[$key]) ? $_POST[$key] : false;       
    }
    
    public function setRequired($keys)
    {
        if (!is_array($keys))
        {
            $keys = array($keys);
        }
        
        foreach ($keys as $key)
        {
            if (!$this->getValue($key))
            {
                $this->errors[] = array('field' => $key, 'error' => 'REQUIRED');
            }
        }
    }
    
    public function isValidPassword($password)
    {
        return strlen($password) >= 4;
    }
    
    //will test that it is of the form 1235123412344,23 with max 2 behind the decimal and comma as separator
    // 0.234 invalid
    // 0,23 valid
    // 0,55523 invald (too many decimals)
    // 125341324124,2 valid
    public function setNumeric($keys)
    {
        if (!is_array($keys))
        {
            $keys = array($keys);
        }
        
        foreach ($keys as $key)
        {
            if (!preg_match('/^[0-9]{0,12}(,[0-9]{0,2})?$/',$this->getValue($key))) 
            {
                $this->errors[] = array('field' => $key, 'error' => 'NUMERIC');
            }
        }        
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
    
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }
    
    public function isValidEmail($string)
    {
        $result = filter_var($string, FILTER_VALIDATE_EMAIL);
        Debug::log("Checking if email $string is valid? -> $result");
        
        return $result;
    }
    
    public function setEmail($key)
    {
        if (!$this->isValidEmail($this->getValue($key)))
        {
            $this->errors[] = array('field' => $key, 'error' => 'EMAIL');
        }
    }
}

?>