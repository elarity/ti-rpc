<?php
namespace Controller;

class Account{

	private $accountModel = null;

  public function __construct(){

		$this->accountModel = new \Model\Account();

	}	

  public function login(){
    $loginResult = $this->accountModel->login();
    return $loginResult; 
  }

}
