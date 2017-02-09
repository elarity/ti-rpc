<?php
namespace Server\Module;
class System{
  /*
   * @description:用户登录
   * @access:public
   * @paramter:array
   * @return:array
   */ 
  public static function info(array $config){
    return array(
      'code'=>0,
      'message'=>'登录成功',
      'data'=>array(
        'account'=>'test',
        'gender'=>'m',
        'time'=>'1900-01-01 12:34:12',
      ),
    );
  }
}
