<?php
/*
 * @parameter:field，字段  
 * @parameter:pdo，mysql数据库句柄
 * @parameter:redis，redis数据库句柄
 * @parameter:param，条件参数
 */
function getAccountById(array $config){
  //字段控制
  $config['field']=isset($config['field'])?$config['field']:'*';
  //从redis或者mysql中获取用户信息
  if($config['redis']->exists(REDIS_ACCOUNT.$config['param']['id'])){
    //REDIS_ACCOUNT_COVER REDIS_ACCOUNT 
    if('*'===$config['field']){
      $account=$config['redis']->hGetall(REDIS_ACCOUNT.$config['param']['id']);
      $account['avatar']=AVATAR.$account['id'];
      $account['cover']=getAccountCoverById($config);
    }else{
      //外部字段:avatar,cover
      $account=[];
      if(in_array('avatar',$config['field'])){
        $account['avatar']=AVATAR.$config['param']['id'];
        unset($config['field']['avatar']);
      }  
      if(in_array('cover',$config['field'])){
        $account['cover']=getAccountCoverById($config);
        unset($config['field']['cover']);
      }  
      $redisAccount=$config['redis']->hMget(REDIS_ACCOUNT.$config['param']['id'],$config['field']);
      $account=$account+$redisAccount;

      $tmp = explode('|',$account['ins']);
      $account['ins'] = array(
        'id' => $tmp[0],
        'name' => $tmp[1]
      );

    }
  }else{
    $account=$config['pdo']->row(
      "select * from ti_account where id=:id",
      ['id'=>$config['param']['id']]
    );
    //把salt跟password干掉
    unset($account['salt']);
    unset($account['password']);
    $account['age']=strval($account['age']);
    $account['birthday']=date('Y-m-d',strtotime($account['birthday']));
    //从mongodb中获取经纬度
    $accountCollection=$config['mongo']->selectDB(MONGO_DB)->selectCollection('account');
    $accountGeo=$accountCollection->findOne(array(
      'id'=>new \MongoInt64($account['id']),
    ));
    if(isset($accountGeo['location'][0])){
      $account['lng']=$accountGeo['location'][0];
      $account['lat']=$accountGeo['location'][1];
    }else{
      $account['lng']=0;
      $account['lat']=0;
    }
    //处理兴趣爱好
    $intent=$config['pdo']->row(
      "select name from ti_intent where id=:id",
      ['id'=>$account['intent']]
    );
    $account['ins']=$account['intent'].'|'.$intent['name'];
    //将用户信息放入到redis中
    $config['redis']->hMset(REDIS_ACCOUNT.$account['id'],$account);
    //用户封面
    $account['cover']=getAccountCoverById($config);
    //二次处理兴趣爱好
    $account['ins']=array(
      'id'=>$account['intent'],
      'name'=>$intent['name'],
    );
    //用户头像
    $account['avatar']=AVATAR.$account['id'];
    //根据实际需求返回只需要的字段
    if(is_array($config['field'])){
      foreach($config['field'] as $item){
        $_account[$item]=$account[$item];
      } 
      return $_account;
    }
  }
  return $account;
}
function getAccountCoverById(array $config){
  if($config['redis']->exists(REDIS_ACCOUNT_COVER.$config['param']['id'])){
    $_cover=$config['redis']->hGetall(REDIS_ACCOUNT_COVER.$config['param']['id']);
    foreach($_cover as $key=>$item){
      $_coverInfoArr=explode('|',$item);
      //key是图片id,item中,|前是文件名,|后是文件序列 
      $cover[]=array(
        'id'=>intval($key),
        'name'=>$_coverInfoArr[0],
        'ord'=>$_coverInfoArr[1],
        'url'=>COVER.$_coverInfoArr[0],
      );
    }
  }else{
    $sql="select * from ti_account_cover where accountId=:accountId";
    $bindParam=array(
      'accountId'=>$config['param']['id']
    );
    $_rcover=$config['pdo']->query($sql,$bindParam);
    //如果还只有一张图，那么记得把头像复制成一张封面
    $cover=[];
    if(count($_rcover)>0){
      foreach($_rcover as $key=>&$item){
        $cover[]=array(
          'id'=>intval($item['id']),
          'name'=>$item['name'],
          'ord'=>$item['ord'],
          'url'=>COVER.$item['name'],
        );
        //redis数据
        $_redisCover[$item['id']]=$item['name'].'|'.$item['ord'];
      }
      //向redis中写入缓存
      $config['redis']->hMset(REDIS_ACCOUNT_COVER.$config['param']['id'],$_redisCover);
    }    
  }
  return $cover;
}
function arraySort($array,$keys,$type='asc'){
  //$array为要排序的数组,$keys为要用来排序的键名,$type默认为升序排序
  $keysvalue = $new_array = array();
  foreach ($array as $k=>$v){
    $keysvalue[$k] = $v[$keys];
  }
  if($type == 'asc'){
    asort($keysvalue);
  }else{
    arsort($keysvalue);
  }
  reset($keysvalue);
  foreach ($keysvalue as $k=>$v){
    $new_array[$k] = $array[$k];
  }
  return $new_array;
}
function getDistance($longitude1,$latitude1,$longitude2,$latitude2,$unit=2,$decimal=2){
  $EARTH_RADIUS=6370.996;
  $PI=3.1415926;
  $radLat1=$latitude1*$PI/180.0;
  $radLat2=$latitude2*$PI/180.0;
  $radLng1=$longitude1*$PI/180.0;
  $radLng2=$longitude2*$PI/180.0;
  $a=$radLat1-$radLat2;
  $b=$radLng1-$radLng2;
  $distance=2*asin(sqrt(pow(sin($a/2),2)+cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)));
  $distance=$distance*$EARTH_RADIUS*1000;
  if($unit==2){
    $distance=$distance/1000;
  }
  return round($distance,$decimal);
}
function isMobile($mobile){
  if(preg_match("/^861[34578]\d{9}$/",$mobile)){
  //if(is_numeric($mobile)){
    return true;
  }else{
    return false;
  }
}
function isPassword($password){
  if(strlen($password)==32){
    return true;
  }else{
    return false;
  }
}
function isInt($int){
  return filter_var($int,FILTER_VALIDATE_INT);
}
function isEmail($email){
  return filter_var($email,FILTER_VALIDATE_EMAIL);
}
function createString($type='alpha',$length=6){
  switch($type){
    case 'alpha':
      // total length is 62
      $cover='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
      $str='';
      for($i=1;$i<=$length;$i++){
        $index=mt_rand(0,61);
        $str.=$cover[$index];
      }
      break;
    case 'number':
      $cover='1234567890';
      $str='';
      for($i=1;$i<=$length;$i++){
        $index=mt_rand(0,9);
        $str.=$cover[$index];
      }
      break;
  }
  return $str;
}
if(!function_exists('time33Hash')){
  function time33Hash(&$keyword,$n){
    $hash = crc32($keyword) >> 16 & 0x7fff;
    return $hash % $n;
  }
}
if(!function_exists('hash_equals')) {
  function hash_equals($str1, $str2) {
    if(strlen($str1) != strlen($str2)) {
      return false;
    } else {
      $res = $str1 ^ $str2;
      $ret = 0;
      for($i = strlen($res) - 1; $i >= 0; $i--) $ret |= ord($res[$i]);
      return !$ret;
    }
  }
}
function autoload($class){
  $include_path=str_replace('\\',DS,$class);
  $target_file=ROOT.DS.$include_path.'.php';
  require_once($target_file);
}
