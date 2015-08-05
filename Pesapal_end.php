<?php
/**
 * @package	Pesapal for HikaShop Joomla!
 * @version	1.0
 * @author	twitter.com/patric_mutwiri
 * @copyright	(C) 2010-2014 GBC SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
require_once("OAuth.php");
?>
<div class="hikashop_pesapal_end" id="hikashop_pesapal_end"> 
<?php

//plgHikashoppaymentPesapal::onAfterOrderConfirm(&$order,&$methods,$method_id);

$user = JFactory::getUser();
$token = $params = NULL;

$consumer_key = $this->payment_params->key;
$consumer_secret = $this->payment_params->secret;

$signature_method = new OAuthSignatureMethod_HMAC_SHA1();
$iframelink = $this->payment_params->pesapal_link;
$amount = $this->payment_params->amount;
$amount = number_format($amount, 2);
$desc = "order number : ".$this->payment_params->ref;
$type = "MERCHANT";
$reference = $this->payment_params->ref;
$first_name = $this->payment_params->firstname;
$last_name = $this->payment_params->lastname;
$email = $user->email;
$phonenumber = '';
$callback_url = $this->payment_params->return_urlx;
$post_xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?><PesapalDirectOrderInfo xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" Amount=\"".$amount."\" Description=\"".$desc."\" Type=\"".$type."\" Reference=\"".$reference."\" FirstName=\"".$first_name."\" LastName=\"".$last_name."\" Email=\"".$email."\" PhoneNumber=\"".$phonenumber."\" xmlns=\"http://www.pesapal.com\" />";
$post_xml = htmlentities($post_xml);
$consumer = new OAuthConsumer($consumer_key, $consumer_secret);
//post transaction to pesapal
$iframe_src = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $iframelink, $params);
$iframe_src->set_parameter("oauth_callback", $callback_url);
$iframe_src->set_parameter("pesapal_request_data", $post_xml);
$iframe_src->sign_request($signature_method, $consumer, $token);
//display pesapal - iframe and pass iframe_src
?>

<iframe src="<?php echo $iframe_src;?>" width="100%" height="700px"  scrolling="no" frameBorder="0">
<p>Browser unable to load iFrame</p>
</iframe>

</div>
