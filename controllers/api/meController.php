<?php

class api_meController extends ApiBaseController
{
	public function __construct($login){
		parent::__construct($login);

		//note that after the construct we have basically a $this->user that works
		$user = $this->user;
		$user['boards'] = Barlisto::getUserBoards($user['userid']);

		$this->output($user);
	}
}

?>