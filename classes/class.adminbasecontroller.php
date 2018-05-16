<?php
class adminBaseController extends baseController 
{
    public $requiredRights = User::USERRIGHTS_ADMIN;
    
}
?>