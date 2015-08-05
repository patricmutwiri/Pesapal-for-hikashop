<?php
/**
 * @package	Pesapal for HikaShop Joomla!
 * @version	1.0
 * @author	twitter.com/patric_mutwiri
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
			require_once("../../../configuration.php");
//ini_set('display_errors',1);
			require_once("OAuth.php");
			$config = new JConfig();
			$dbx = $config->dbprefix.$config->db;
				try {
					$db = new PDO('mysql:host='.$config->host.';dbname='.$config->db.'', $config->user, $config->password);
				} catch (PDOException $e) {
			   	 	print "Error!: " . $e->getMessage() . "<br/>";
			    	die();
				}
			$consumer_key = ''; //hardcoded
			$consumer_secret = ''; //hardcoded
			
			//$statusrequestAPI = 'http://demo.pesapal.com/api/QueryPaymentStatus';
			$statusrequestAPI = 'https://www.pesapal.com/API/QueryPaymentStatus';
			$pesapal_notification_type  = $_GET['pesapal_notification_type'];
			//$pesapal_notification_type = isset($_GET['pesapal_notification_type']) ? $_GET['pesapal_notification_type'] : "CHANGE";
			$pesapal_merchant_reference = $_GET['pesapal_merchant_reference'];
			$pesapal_transaction_tracking_id = $_GET['pesapal_transaction_tracking_id'];	
			if ($pesapal_notification_type == 'CHANGE' && $pesapal_transaction_tracking_id != '') {
					  // Pesapal parameters
					  $token = $params = NULL;
					  $consumer = new OAuthConsumer($consumer_key, $consumer_secret);
					  // Get transaction status
					  $signature_method = new OAuthSignatureMethod_HMAC_SHA1();
					  $request_status = OAuthRequest::from_consumer_and_token($consumer, $token, 'GET', $statusrequestAPI, $params);
					  $request_status -> set_parameter('pesapal_merchant_reference', $pesapal_merchant_reference);
					  $request_status -> set_parameter('pesapal_transaction_tracking_id',$pesapal_transaction_tracking_id);
					  $request_status -> sign_request($signature_method, $consumer, $token);
					  $ch = curl_init();
					  curl_setopt($ch, CURLOPT_URL, $request_status);
					  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					  curl_setopt($ch, CURLOPT_HEADER, 1);
					  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
					  	if (defined('CURL_PROXY_REQUIRED')) {
						  	if (CURL_PROXY_REQUIRED == 'True') {
							  $proxy_tunnel_flag = (defined('CURL_PROXY_TUNNEL_FLAG') && strtoupper(CURL_PROXY_TUNNEL_FLAG) == 'FALSE') ? false : true;
							  curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, $proxy_tunnel_flag);
							  curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
							  curl_setopt ($ch, CURLOPT_PROXY, CURL_PROXY_SERVER_DETAILS);
						  	}
					  	}
					 $response = curl_exec($ch);
					 $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
					 $raw_header  = substr($response, 0, $header_size - 4);
					 $headerArray = explode('\r\n\r\n', $raw_header);
					 $header      = $headerArray[count($headerArray) - 1];
					 // Transaction status
					 $elements = preg_split('/=/',substr($response, $header_size));
					 $status = $elements[1]; // PENDING, COMPLETED or FAILED
					 curl_close ($ch);
							  $headers = "From: $config->sitename\r\n";
							  $headers .= "Reply-To: $config->mailfrom\r\n";
							  $headers .= "CC: $config->mailfrom\r\n";
							  $headers .= "MIME-Version: 1.0\r\n";
							  $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
					$url = "http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['REQUEST_URI']).'/';			 	 	
				if ($status = 'COMPLETED') {
					$stmt = $db->prepare("UPDATE {$config->dbprefix}hikashop_order SET order_status = 'confirmed' WHERE order_number = :order_number");
					$stmt->execute(array("order_number" => $pesapal_merchant_reference));
					$stmt = $db->prepare("SELECT order_user_id from {$config->dbprefix}hikashop_order WHERE order_number = :order_number");
					$stmt->execute(array("order_number" => $pesapal_merchant_reference));
					$user_id = $stmt->fetchColumn(0);
					$stmt = $db->prepare("SELECT user_email FROM {$config->dbprefix}hikashop_user WHERE user_id = ".$user_id."");
					$stmt->execute();
					$to = $stmt->fetchColumn(0); //emial
					$stmt = $db->prepare("SELECT address_firstname FROM {$config->dbprefix}hikashop_address WHERE address_user_id = ".$user_id."");
					$stmt->execute();
					$name = $stmt->fetchColumn(0); //customer name

					$subject = 'Payment Successfull';
					$message = '<html><body>';
					$message .= '<p style="font-family: "Times New Roman", Times, serif;"><img src="'.$url.'logo.png" /></p>';
					$message .= '<p style="font-family: "Times New Roman", Times, serif;"> Dear <b>'.ucwords($name).',</b> </p>';
					$message .= '<p style="font-family: "Times New Roman", Times, serif;"> Your payment for Order no: <b>'.$pesapal_merchant_reference.'</b> has been successfull. </p>';
					$message .= '<p style="font-family: "Times New Roman", Times, serif;"> The order status has been changed to <b>confirmed.</b> </p>';
					$message .= '<p style="font-family: "Times New Roman", Times, serif;"> Kind Regards, </p>';
					$message .= '<p style="font-family: "Times New Roman", Times, serif;"><b style="font-family: "Times New Roman", Times, serif;">'.$config->sitename.'</b></p>';
					$message .= '</body></html>';
						  
					if ($stmt) {
						  // Send Pesapal response only if we were able to update DB
						  $resp = 'pesapal_notification_type='.$pesapal_notification_type;
						  $resp .= '&pesapal_transaction_tracking_id='.$pesapal_transaction_tracking_id;
						  $resp .= '&pesapal_merchant_reference='.$pesapal_merchant_reference;
						  ob_start();
						  	 echo $resp;
						  ob_flush();
					}

			 	}	elseif ($status = 'FAILED') {
			  			  //send mail. 
					$stmt = $db->prepare("UPDATE {$config->dbprefix}hikashop_order SET order_status = 'cancelled' WHERE order_number = :order_number");
					$stmt->execute(array("order_number" => $pesapal_merchant_reference));
					$stmt = $db->prepare("SELECT order_user_id from {$config->dbprefix}hikashop_order WHERE order_number = :order_number");
					$stmt->execute(array("order_number" => $pesapal_merchant_reference));
					$user_id = $stmt->fetchColumn(0);
					$stmt = $db->prepare("SELECT user_email FROM {$config->dbprefix}hikashop_user WHERE user_id = ".$user_id."");
					$stmt->execute();
					$to = $stmt->fetchColumn(0); //emial
					$stmt = $db->prepare("SELECT address_firstname FROM {$config->dbprefix}hikashop_address WHERE address_user_id = ".$user_id."");
					$stmt->execute();
					$name = $stmt->fetchColumn(0); //customer name

					$subject = 'Payment Failed';
					$message = '<html><body>';
					$message .= '<p style="font-family: "Times New Roman", Times, serif;"><img src="'.$url.'logo.png" /></p>';
					$message .= '<p style="font-family: "Times New Roman", Times, serif;"> Dear <b>'.ucwords($name).',</b> </p>';
					$message .= '<p style="font-family: "Times New Roman", Times, serif;"> Your payment for order no: <b>'.$pesapal_merchant_reference.'</b> has not been successfull. </p>';
					$message .= '<p style="font-family: "Times New Roman", Times, serif;"> The order status has not been changed from <b>created.</b> </p>';
					$message .= '<p style="font-family: "Times New Roman", Times, serif;"> The transaction tracking id is <i>'.$pesapal_transaction_tracking_id.'</i></p>';
					$message .= '<p style="font-family: "Times New Roman", Times, serif;"> Kind Regards, </p>';
					$message .= '<p style="font-family: "Times New Roman", Times, serif;"><b style="font-family: "Times New Roman", Times, serif;"> '.$config->sitename.'</b></p>';
					$message .= '</body></html>';
							  		  	  
					$resp = 'pesapal_notification_type='.$pesapal_notification_type;
					$resp .= '&pesapal_transaction_tracking_id='.$pesapal_transaction_tracking_id;
					$resp .= '&pesapal_merchant_reference='.$pesapal_merchant_reference;
					ob_start();
					echo $resp;
					ob_flush();
			 	} else {
			 		//pending status
			 	}
				mail($to, $subject, $message, $headers);
				  exit;
			}	
?>
