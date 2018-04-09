<?php
namespace System\Library\Baidu;
include 'BaiduBce.phar';
use BaiduBce\BceClientConfigOptions;
use BaiduBce\Util\Time;
use BaiduBce\Util\MimeTypes;
use BaiduBce\Http\HttpHeaders;
use BaiduBce\Services\Bos\BosClient;
use BaiduBce\Services\Bos\CannedAcl;
use BaiduBce\Services\Bos\BosOptions;
use BaiduBce\Auth\SignOptions;
use BaiduBce\Log\LogFactory;
class Bos{
  public $client;
  private $bucket;
  private $key;
  private $filename;
  private $download;
/*
define('__BOS_CLIENT_ROOT', dirname(__DIR__));
//1f50fdf742c240d0ac8d988061da3979
//24c325f9e86c4b4898388759ccfe7297
$BOS_TEST_CONFIG =
    array(
        'credentials' => array(
            'ak' => '1f50fdf742c240d0ac8d988061da3979',
            'sk' => '24c325f9e86c4b4898388759ccfe7297',
        ),
    );
 */
  private $bosKey=array(
    'credentials' => array(
      'ak'=>'1f50fdf742c240d0ac8d988061da3979',
      'sk'=>'24c325f9e86c4b4898388759ccfe7297',
    ),
  );
  public function __construct(){
    //include 'SampleConf.php';
    $this->logger=LogFactory::getLogger(get_class($this));
  }
  public function deleteObject($bucket,$key){
    $this->client=new BosClient($this->bosKey);
    $this->client->deleteObject($bucket,$key);
  }
  //向bos中上传头像
  public function avatar($avatarName,$targetName){
    $this->bucket='jimu-cover-new';
    $this->client=new BosClient($this->bosKey);
    $avatarDir=ROOT.'Upload'.DS.'avatar'.DS; 
    $avatarFile=$avatarDir.$avatarName; 
    try{
      $avatar=$this->client->putObjectFromFile($this->bucket,$targetName,$avatarFile); 
      //return $avatar;
      return array(
        'code'=>0,
      );
    }catch(\BaiduBce\Exception\BceBaseException $e){
      return array(
        'code'=>1008,
        'message'=>'头像上传失败'
      );
    }
  }
  public function cover($coverName,$targetName){
    $this->bucket=COVER_BOS;
    $this->client=new BosClient($this->bosKey);
    $coverDir=UPLOAD.'cover'.DS; 
    $coverFile=$coverDir.$coverName; 
    try{
      $cover=$this->client->putObjectFromFile($this->bucket,$targetName,$coverFile); 
      return array(
        'code'=>0,
      );
    }catch(\BaiduBce\Exception\BceBaseException $e){
      return array(
        'code'=>1009,
        'message'=>'封面上传失败'
      );
    }
  }
  public function complain($complainName,$targetName){
    $this->bucket=COMPLAIN_BOS;
    $this->client=new BosClient($this->bosKey);
    $complainDir=UPLOAD.'complain'.DS;
    $complainFile=$complainDir.$complainName; 
    try{
      $cover=$this->client->putObjectFromFile($this->bucket,$targetName,$complainFile); 
      return array(
        'code'=>0,
      );
    }catch(\BaiduBce\Exception\BceBaseException $e){
      return array(
        'code'=>1009,
        'message'=>'图片上传失败',
        'debug'=>$e->getMessage(),
      );
    }
  }
  public function copyFile($sourceBucket,$sourceFile,$targetBullet,$targetFile){
    $this->client=new BosClient($this->bosKey);
    try{
      $copy=$this->client->copyObject($sourceBucket,$sourceFile,$targetBullet,$targetFile); 
      return array(
        'code'=>0,
      );
    }catch(\BaiduBce\Exception\BceBaseException $e){
      return array(
        'code'=>1009,
        'message'=>'封面上传失败',
        'debug'=>'头像复制为封面失败',
      );
    }
  }
  public function test($coverName='i',$id=1){
    $this->bucket=COVER_BOS;
    $this->client=new BosClient($this->bosKey);
    //$coverDir=UPLOAD.'cover'.DS; 
    //$coverFile=$coverDir.$coverName; 
    $coverFile=UPLOAD.'1.png'; 
    try{
      echo 'ii'.PHP_EOL;
      $cover=$this->client->putObjectFromFile($this->bucket,$id,$coverFile); 
      return array(
        'code'=>0,
      );
    }catch(\BaiduBce\Exception\BceBaseException $e){
      var_dump( $e->getMessage() );
      echo 'iii'.PHP_EOL;
      return array(
        'code'=>1009,
        'message'=>'封面上传失败'
      );
    }
  }
  public function brandLogo($avatarName,$targetName){
    $this->bucket=ACTIVITY_BOS;
    $this->client=new BosClient($this->bosKey);
    $avatarDir=UPLOAD.DS.'activity'.DS; 
    $avatarFile=$avatarDir.$avatarName; 
    try{
      $avatar=$this->client->putObjectFromFile($this->bucket,$targetName,$avatarFile); 
      //return $avatar;
      return array(
        'code'=>0,
      );
    }catch(\BaiduBce\Exception\BceBaseException $e){
      return array(
        'code'=>1008,
        'message'=>'品牌logo上传失败'
      );
    }
  }
  public function brandBackground($avatarName,$targetName){
    $this->bucket=ACTIVITY_BOS;
    $this->client=new BosClient($this->bosKey);
    $avatarDir=UPLOAD.DS.'activity'.DS; 
    $avatarFile=$avatarDir.$avatarName; 
    try{
      $avatar=$this->client->putObjectFromFile($this->bucket,$targetName,$avatarFile); 
      //return $avatar;
      return array(
        'code'=>0,
      );
    }catch(\BaiduBce\Exception\BceBaseException $e){
      return array(
        'code'=>1008,
        'message'=>'品牌背景上传失败'
      );
    }
  }
  public function activity($avatarName,$targetName){
    $this->bucket=ACTIVITY_BOS;
    $this->client=new BosClient($this->bosKey);
    $avatarDir=UPLOAD.DS.'activity'.DS; 
    $avatarFile=$avatarDir.$avatarName; 
    //echo $targetName;exit;
    try{
      $avatar=$this->client->putObjectFromFile($this->bucket,$targetName,$avatarFile); 
      //return $avatar;
      unlink($avatarFile);
      return array(
        'code'=>0,
      );
    }catch(\BaiduBce\Exception\BceBaseException $e){
      return array(
        'code'=>1008,
        'message'=>'品牌背景上传失败'
      );
    }
  }
  //机器人
  public function robotAvatar($targetName){
    $this->bucket=AVATAR_BOS;
    $this->client=new BosClient($this->bosKey);
    $index=mt_rand(1,36);
    $avatarFile=UPLOAD.DS.'robot'.DS.$index.'.jpg'; 
    try{
      $avatar=$this->client->putObjectFromFile($this->bucket,$targetName,$avatarFile); 
      return array(
        'code'=>0,
      );
    }catch(\BaiduBce\Exception\BceBaseException $e){
      return array(
        'code'=>1008,
        'message'=>'品牌背景上传失败'
      );
    }
  }
}
