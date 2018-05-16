<?php

//basically a bag of data accessible to all controllers
// most important one is $this->login->user
Class Login
{
	public $user;
	public $module;
	public $submodule;
	
	public function getUserID()
	{
		return $_SESSION['userid'];
	}
}

?>