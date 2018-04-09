<?php

function uuid(){
  $ch = curl_init();
  $timeout = 5;
  curl_setopt ($ch, CURLOPT_URL, 'http://100.66.39.224:1688');
  curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
  $uuid = curl_exec($ch);
  curl_close($ch);
	$uuid = false === $uuid ? dk_get_next_id() : $uuid ;
	return $uuid;
} 

function sms( $mobile, $code, $type ){
  $ch = curl_init();
  $timeout = 5;
  curl_setopt ($ch, CURLOPT_URL, "http://100.66.39.224:1689?mobile={$mobile}&code={$code}&type={$type}");
  curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
  $sms = curl_exec($ch);
  curl_close($ch);
	return $sms;
}

function sendSMS( $mobile, $code, $type ){
  $sign = '【蓝莓科技】';
	$encode = 'UTF-8';
	$username = 'lanmeikj';  
	$password = '7c79dd68b400e6b0c9f99f8f221dae26'; 
	$apikey = '120b958387af56f79cc38a0addd20788';  
	if( '86' == substr( $mobile, 0, 2 ) ){ 
		$messageTemplate = array(
			1 => "验证码{$code}，您正在注册成为积目用户，感谢支持!{$sign}",
			2 => "验证码{$code}，您正在修改积目账户的密码，如非本人操作请忽略此短信!{$sign}",
			3 => "验证码{$code}，您正在更换手机号，如非本人操作请忽略此短信!{$sign}",
			4 => "验证码{$code}，您正在重置密码，如非本人操作请忽略此短信!{$sign}",
			5 => "验证码{$code}，您正在登录积目，如非本人操作请忽略此短信!{$sign}",
		);  
	} else {
		$messageTemplate = array(
			1 => "Hey Gmu User,Your verification code is {$code},please use it to finish the process,It is Gmu!{$sign}",
			2 => "{$code},You are changing your password{$sign}",
			3 => "{$code},You are changing your account phone!{$sign}",
		  4 => "{$code},You are reseting your password!{$sign}",
			5 => "{$code},You are signin GMU!{$sign}",
		);  
  }   
	$content = $messageTemplate[$type];
	$contentUrlEncode = urlencode($content);
	//如连接超时，可能是您服务器不支持域名解析，请将下面连接中的：【m.5c.com.cn】修改为IP：【115.28.23.78】
	$url = "http://m.5c.com.cn/api/send/index.php?";  
	$data = array(
		'username' => $username,
		'password_md5' => $password,
		'apikey' => $apikey,
		'mobile' => $mobile,
	  'content' => $contentUrlEncode,
		'encode' => $encode,
	);  
  //使用curl发送短信
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
	curl_setopt( $ch, CURLOPT_HEADER, 1 );
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
	$data = curl_exec( $ch );
	curl_close( $ch );
	$sendResult = explode( "\r\n\r\n", $data );
	if( strpos( $sendResult[2], "success" ) > -1 ){
		return array(
			'code' => 0,
			'message' => '发送成功'
		);
	}else{
		return array(
			'code' => -1,
			'message' => '发送失败'
		);
	}
}

function bitmapHuman($bitmap){
  $bytes=unpack('C*',$bitmap);
  $bin=join(array_map(function($byte){
    return sprintf("%08b",$byte);
  }, $bytes));
  return $bin;
}
// common functions
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
  //if($config['redis']->exists(REDIS_ACCOUNT.$config['param']['id'])){
  if(true==$config['redisSlave']->hExists(REDIS_ACCOUNT.$config['param']['id'],'id')){
    //echo 'slave bingo'.PHP_EOL;
    //REDIS_ACCOUNT_COVER REDIS_ACCOUNT 
    if('*'===$config['field']){
      $account=$config['redisSlave']->hGetall(REDIS_ACCOUNT.$config['param']['id']);
      $tmpIns = explode('|', $account['ins']);
      $account['ins'] = array(
        'id' => $tmpIns[0],
        'name' => $tmpIns[1]
      );
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
      $redisAccount=$config['redisSlave']->hMget(REDIS_ACCOUNT.$config['param']['id'],$config['field']);
      $account=$account+$redisAccount;

      if(in_array('ins',$config['field'])){
        $tmp = explode('|',$account['ins']);
        $account['ins'] = array(
          'id' => $tmp[0],
          'name' => $tmp[1]
        );
      }

    }
  }else{
    $sql= empty($config['force']) ? "select * from ti_account where id=:id" : "/*master*/ select * from ti_account where id=:id" ;
    //$sql= "select * from ti_account where id=:id";
    $account=$config['pdo']->row(
      $sql,
      ['id'=>$config['param']['id']]
    );
    if( !isset( $account['id'] ) ){
      echo '修改sql'.PHP_EOL;
      $sql = "/*master*/ select * from ti_account where id=:id";
    }
    //把salt跟password干掉
    unset($account['salt']);
    unset($account['password']);
    $account['age']=strval($account['age']);
    $account['birthday']=date('Y-m-d',strtotime($account['birthday']));
    //从mongodb中获取经纬度
    if(!isset($config['es'])){
      $config['es']=\Ti\Library\ElasticSearch\Client::connection(array(
        'index'=>'jimu',
        'type'=>'account',
      )); 
    }
    $accountGeo=$config['es']->get($account['id']);
    if(isset($accountGeo['location'])){
      $account['lng']=$accountGeo['location']['lon'];
      $account['lat']=$accountGeo['location']['lat'];
    }else{
      $account['lng']=0;
      $account['lat']=0;
    }
    //处理兴趣爱好
    $intent=$config['pdo']->row(
      "select name from ti_intent where id=:id",
      ['id'=>$account['intent']]
    );
    //将用户信息放入到redis中
    if(1==$account['status']){
      $account['ins']=$account['intent'].'|'.$intent['name'];
      $config['redis']->hMset(REDIS_ACCOUNT.$account['id'],$account);
      $config['redis']->EXPIRE(REDIS_ACCOUNT.$account['id'],86400);
    }
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
      //添加用户级别  刘波2  普通用户1
      if(LIUBO == $_account['id'] ){
        $_account['type'] = 2;
      }else{
        $_account['type'] = 1;
      } 
      return $_account;
    }
  }
  //添加用户级别  刘波2  普通用户1
  if(LIUBO==$account['id']){
    $account['type'] = 2;
  }else{
    $account['type'] = 1;
  }
  //$account['age']=strval($account['age']);
  $account['account']='12';
  $account['status']=isset($account['status'])?$account['status']:1;
  return $account;
}
function getAccountCoverById(array $config){
  $_cover=$config['redis']->hGetall(REDIS_ACCOUNT_COVER.$config['param']['id']);
  //if($config['redis']->exists(REDIS_ACCOUNT_COVER.$config['param']['id'])){
  if( count($_cover)>0 ){
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
    $sql=empty($config['force'])?"select * from ti_account_cover where accountId=:accountId":"/*master*/ select * from ti_account_cover where accountId=:accountId";
    //$sql="select * from ti_account_cover where accountId=:accountId";
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
      $config['redis']->EXPIRE(REDIS_ACCOUNT_COVER.$config['param']['id'],86400);
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
function getDistance($longitude1, $latitude1, $longitude2, $latitude2, $unit=2, $decimal=2){
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
  //if(preg_match("/^861[34578]\d{9}$/",$mobile)){
  if(is_numeric($mobile)){
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
//定义hash_equals函数，有些老版本php中没有这个函数
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
