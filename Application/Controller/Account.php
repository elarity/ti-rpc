<?php
namespace Application\Controller;
use Application\Model as Model;

class Account{

	private $accountModel = null;

  public function __construct(){

		$this->accountModel = new Model\Account();

	}	

  public function login(){
    $loginResult = $this->accountModel->login();
    return $loginResult; 
  }

}
