<?php
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

$cmd = $_GET['cmd'];
if($cmd == 'checkdevice'){
	$deviceip = $_GET['ip'];
	$deviceid = getDeviceIdbyIP($deviceip);
	//$deviceid = preg_replace( '/[^a-z]/', '', $deviceid );
	$deviceid = preg_replace("/[^a-zA-Z0-9]/", "", $deviceid);

	if(!$deviceid){
		$deviceid = '';
	}
	$arrayData = array('device_id' => $deviceid );

	header('Content-Type: application/json');
	echo json_encode($arrayData);
}else if($cmd == 'uptoken'){
	$deviceid = $_GET['device_id'];
	$token = $_GET['token'];
	$code = saveDeviceToken($deviceid, $token);
	$arrayData = array('code' => $code );
	header('Content-Type: application/json');
	echo json_encode($arrayData);
}else if($cmd == 'test'){
	$deviceid = $_GET['device_id'];
	$token = getDeviceToken($deviceid);
	pushNotification($token, $deviceid);
	$arrayData = array('token' => $token );
	header('Content-Type: application/json');
	echo json_encode($arrayData);
}

function getDeviceToken($device_id){
	$device_id = strtolower($device_id);
	$path = './id2token/' .$device_id;
	printf( " Path : %s ", $path );
	
	$token = file_get_contents($path);	
	return $token;
}

function pushNotification($token, $devide_id){
	$url = "https://fcm.googleapis.com/fcm/send";
	$serverKey = 'AAAAPUtAsPQ:APA91bHQ2_pEXUiiA9DsvTSAQTv7q_44glEBGDBxMQ_1eysQivCxF4bTYh7rbwBZkXkJT68bPyWpvmK9Vk_zCOK63XHUH_0IZzE_MUA7x5dwCocHFglGSSDFNktRtjzCC5KjIulxj1fL';
	$title = "Device Notification";
	$body = "This is alarm of device " . $devide_id;
	$notification = array('title' =>$title , 'text' => $body, 'sound' => 'default', 'badge' => '1');

	$message = array(
		'title' => $title,
		'message' => $body,
		'subtitle' => '',
		'tickerText' => '',
		'msgcnt' => 1,
		'vibrate' => 1
	); 
	
	$arrayToSend = array('to' => $token, 'sound' => 'telephone', 'vibrate' => 1, 'data' => $message/*, 'notification' => $notification*/,'priority'=>'high');
	$json = json_encode($arrayToSend);
	$headers = array();
	$headers[] = 'Content-Type: application/json';
	$headers[] = 'Authorization: key='. $serverKey;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST,
	
	"POST");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
	//Send the request
	$response = curl_exec($ch);
	//Close request
	if ($response === FALSE) {
	die('FCM Send Error: ' . curl_error($ch));
	}
	curl_close($ch);
}

function getDeviceIdbyIP($ip){
	$path = './ip2id/' .$ip;
	if(!file_exists($path)){
		return '';
	}

	$devideid =  file_get_contents($path);
	$devideid = strtolower($devideid);
	return $devideid;
}

function saveDeviceToken($device_id, $token){
	$device_id = strtolower($device_id);
	$path = './id2token/' .$device_id;
	return file_put_contents($path,$token);
}
