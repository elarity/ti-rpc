<?php
namespace Application\Model;
class Account{

	private $Di = null;

	public function __construct(){
		if( !is_object( $this->Di ) ){
		  $this->Di = \System\Component\Di::getInstance();
		}
	}

  public function login(){
    return array(
      'code' => 0,
      'message' => '登陆成功',
    );
  }

}
