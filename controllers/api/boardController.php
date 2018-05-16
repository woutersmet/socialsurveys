<?php

class api_boardController extends ApiBaseController
{
	public function __construct($login){
		parent::__construct($login);

		//note that after the construct we have basically a $this->user that works

		$this->requireParams('boardid');

		$boardid = $this->v->getValue('boardid');

		//user requested list of public boards
		if ($boardid == 'PUBLIC'){
			$boards = BarListo::getPublicBoards();
			$this->output($boards);
		}
		
		//board id, passing single board
		$accessrights = BarListo::getBoardAccessRights($this->user['userid'], $boardid);
		if (!$accessrights){
	    	$this->outputError('board_access_denied', "board $boardid");
        }
        $board = Barlisto::getBoardDetails($boardid);

        if (!$board){
        	$this->outputError('board_not_found', $boardid);
        }

		$this->output($board);
	}
}

?>