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
      'message'=>'获取成功',
      'data'=>array(
        'cpu'=>'ok',
        'memory'=>'ok',
        'ssd'=>'ok',
      ),
    );
  }
}
