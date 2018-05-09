<?php
/*
 利用curl封装的简单对http的客户端演示案例
*/

// SW
$sw = array(
  'type' => 'SW',
  'requestId' => time(),
  'param' => array(
    'model' => 'Account',
    'method' => 'login',
    'param' => array(
      'username' => 'wahah',
      'password' => 'wahah',
    ),
  ),
);
// SN
$sn = array(
  'type' => 'SN',
  'requestId' => time(),
  'param' => array(
    'model' => 'Account',
    'method' => 'login',
    'param' => array(
      'username' => 'wahah',
      'password' => 'wahah',
    ),
  ),
);
// MW
$mw = array(
  'type' => 'MW',
  'requestId' => time(),
  'param' => array(
    'xas' => array(
      'model' => 'Account',
      'method' => 'login',
      'param' => array(
        'username' => 'wahah',
        'password' => 'wahah',
      ),
    ),
    'xxx' => array(
      'model' => 'Account',
      'method' => 'login',
      'param' => array(
        'username' => 'wahah',
        'password' => 'wahah',
      ),
    ),
  ),
);
// MN
$mn = array(
  'type' => 'MN',
  'requestId' => time(),
  'param' => array(
    'xas' => array(
      'model' => 'Account',
      'method' => 'login',
      'param' => array(
        'username' => 'wahah',
        'password' => 'wahah',
      ),
    ),
    'xxx' => array(
      'model' => 'Account',
      'method' => 'login',
      'param' => array(
        'username' => 'wahah',
        'password' => 'wahah',
      ),
    ),
  ),
);



$jsonData = json_encode( $sw );
$curl = curl_init();
curl_setopt_array( $curl, array(
  CURLOPT_PORT => 9802,
  CURLOPT_URL => "http://127.0.0.1:9802/",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => $jsonData,
  CURLOPT_HTTPHEADER => array(
    "cache-control: no-cache",
    "postman-token: efe52804-aa89-8c4d-01ae-5e9a27012312"
  ),
));
$response = curl_exec( $curl );
$err = curl_error( $curl );
curl_close($curl);
if ( $err ) {
  echo "cURL Error #:" . $err;
} else {
  //echo $response;
  print_r( json_decode( $response ) );
}


