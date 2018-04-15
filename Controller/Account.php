<?php
namespace Controller;

class Account{

	private $accountModel = null;

  public function __construct(){

		$this->accountModel = new \Model\Account();

	}	

  public function login(){

		$pay = new \Yansongda\Pay\Pay();
		print_r( $pay );

    $loginResult = $this->accountModel->login();
    return $loginResult; 
  }

}
