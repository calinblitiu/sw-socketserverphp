<?php
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
/**
 * Check dependencies
 */
if( ! extension_loaded('sockets' ) ) {
	echo "This example requires sockets extension (http://www.php.net/manual/en/sockets.installation.php)\n";
	exit(-1);
}

if( ! extension_loaded('pcntl' ) ) {
	echo "This example requires PCNTL extension (http://www.php.net/manual/en/pcntl.installation.php)\n";
	exit(-1);
}

/**
 * Connection handler
 */
function onConnect( $client ) {
	$pid = pcntl_fork();

	if ($pid == -1) {
		 die('could not fork');
	} else if ($pid) {
		// parent process
		return;
	}

	$read = '';
	printf( "[%s] Connected at port %d\n", $client->getAddress(), $client->getPort() );
	$client->send( 'Wellcome to PHP Socket Server, type exit to quit'.PHP_EOL);

	while( true ) {
		$read = $client->read();
		$command = preg_replace('/[\x00-\x1F\x7F]/u', '', $read);

		if( $command != '' ) {
			$client->send( 'You sent : ' . $read  );
		}
		else {
			continue;
		}
		if( $read === null ) {
			printf( "[%s] Disconnected\n", $client->getAddress() );
			return false;
		}
		else {
			printf( "[%s] recieved: %s ", $client->getAddress(), $read );
		}


		if( $command == 'exit' ) {
			break;
		}
		$commandArrys = explode(";",$command);
		printf( "Content %s ", $command );

		$arrayName = array();
		foreach($commandArrys as $part){
			$partArray = explode("=",$part);
			//printf( "Content %s", $partArray );

			$arrayName[$partArray[0]] = $partArray[1]; // This adds a new element to
			printf("0 %s ", $partArray[0] );
			printf("1 %s ", $partArray[1] );

		}
		//printf($arrayName);
		printf( "command %s ", $arrayName['command'] );
		$cmd = $arrayName['command'];
		if($cmd == 'update_device'){
			$devideid = $arrayName['device_id'];
			$ip = $arrayName['ip'];
			if(!$devideid || !$ip){
				continue;
			}
			saveDeviceIdbyIP($ip ,$devideid );
		}else if($cmd == 'update_info'){
			$devideid = $arrayName['device_id'];
			if(!$devideid){
				continue;
			}
			$alarm = $arrayName['alarm'];
			printf( "alarm %s ", $alarm );
			printf( "devideid %s ", $devideid );
			
			if($alarm == '1'){
				$token = getDeviceToken($devideid);				
				printf( "token 1 : %s ", $token );	
				
				pushNotification($token, $devideid);
				
			}
		}
	}
	$client->close();
	printf( "[%s] Disconnected\n ", $client->getAddress() );

}

function saveDeviceIdbyIP($ip, $device_id){

	$ip = strtolower($ip);
	$device_id = strtolower($device_id);

	$path = './ip2id/' .$ip;
	return file_put_contents($path,$device_id);
}

function saveDeviceToken($device_id, $token){
	$device_id = strtolower($device_id);
	$token = strtolower($token);

	$path = './id2token/' .$device_id;
	return file_put_contents($path,$token);
}

function getDeviceToken($device_id){
	$device_id = strtolower($device_id);
	$path = './id2token/' .$device_id;
	printf( " Path : %s ", $path );
	
	$token = file_get_contents($path);
	
	return $token;
}

function pushNotification($token, $devide_id){
	printf( "Response 1 : %s ", $devide_id );	
	
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
	printf( "Response 2 : %s ", $devide_id );	
	
	$arrayToSend = array('to' => $token, 'sound' => 'telephone', 'vibrate' => 1, 'data' => $message/*, 'notification' => $notification*/,'priority'=>'high');
	$json = json_encode($arrayToSend);
	$headers = array();
	$headers[] = 'Content-Type: application/json';
	$headers[] = 'Authorization: key='. $serverKey;
	printf( "Response 3 : %s ", $devide_id );	
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	printf( "Response 4 : %s ", $devide_id );	
	
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST,
	
	"POST");
	printf( "Response 5 : %s ", $devide_id );	
	
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
	//Send the request
	printf( "Response 6 : %s ", $devide_id );	
	
	$response = curl_exec($ch);
	printf( "Response 7 : %s ", $devide_id );	
	
	printf( "Response : %s ", $response );	
	//Close request
	if ($response === FALSE) {
	die('FCM Send Error: ' . curl_error($ch));
	}
	curl_close($ch);
}

require "sock/SocketServer.php";

$server = new \Sock\SocketServer('9898','45.76.177.252');
$server->init();
$server->setConnectionHandler( 'onConnect' );
$server->listen();
