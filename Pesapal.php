<?php
/**
 * @package	Pesapal for HikaShop Joomla!
 * @version	1.0
 * @author	twitter.com/happiexy
 * @copyright	(C) 2010-2014 GBC SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
//define("ENCRYPTION_KEY", "!@#$%^&*");
class plgHikashoppaymentPesapal extends hikashopPaymentPlugin
{
	var $accepted_currencies = array( "KES", "USD" );
	var $multiple = true; 
	var $name = 'Pesapal'; 
	var $pluginConfig = array(
		'secret' => array("Consumer Key",'input'), //User's secret on the payment platform
		'keyx' => array("Consumer Secret",'input'), //User's key on the payment platform
		//'iframelink' => array("Iframe Link",'input'), //User's key on the payment platform
		'pesapal_link'=> array("Use https://www.pesapal.com/API/PostPesapalDirectOrderV4 for live",'input'), //http://demo.pesapal.com/api/PostPesapalDirectOrderV4 demo
		'notification' => array('ALLOW_NOTIFICATIONS_FROM_X', 'boolean','0'), 
		'payment_url' => array("Payment URL",'input'), 
		'debug' => array('DEBUG', 'boolean','0'), 
		'cancel_url' => array('Cancel Url','input'),
		'return_url_gateway' => array('RETURN_URL_DEFINE', 'html',''),
		'return_url' => array('Return Url', 'input'), 
		'notify_url' => array('Notification Url','input'),
		'invalid_status' => array('INVALID_STATUS', 'orderstatus'),
		'verified_status' => array('VERIFIED_STATUS', 'orderstatus') 
		);
	function __construct(&$subject, $config)
	{
		$this->pluginConfig['notification'][0] =  JText::sprintf('ALLOW_NOTIFICATIONS_FROM_X','Pesapal');
		$this->pluginConfig['cancel_url'][2] = HIKASHOP_LIVE."index.php?option=com_hikashop&ctrl=order&task=cancel_order";
		$this->pluginConfig['return_url'][2] = HIKASHOP_LIVE."index.php?option=com_hikashop&ctrl=checkout&task=after_end";
		$this->pluginConfig['notify_url'][2] = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=notify&amp;notif_payment='.$this->name.'&tmpl=component';
		return parent::__construct($subject, $config);
	}
	
	function onAfterOrderConfirm(&$order,&$methods,$method_id)
	{
		$order_id = $order->order_id;
		$user = JFactory::getUser();
		parent::onAfterOrderConfirm($order,$methods,$method_id);
		if (empty($this->payment_params->secret)) 
		{
			$this->app->enqueueMessage('You have to configure a secret key for the Pesapal plugin payment first : check your plugin\'s parameters, on your website backend','error');
			return false;
		}
		elseif (empty($this->payment_params->keyx))
		{
			$this->app->enqueueMessage('You have to configure a plugin key for the Pesapal plugin payment first : check your plugin\'s parameters, on your website backend','error');
			return false;
		}
		elseif (empty($this->payment_params->payment_url))
		{
			$this->app->enqueueMessage('You have to configure a payment url for the Pesapal plugin payment first : check your plugin\'s parameters, on your website backend','error');
			return false;
		}
		else
		{
			$address_type = 'billing_address';
			$address = $this->app->getUserState(HIKASHOP_COMPONENT . '.' . $address_type);
			
			$user = JFactory::getUser();
			$amout = round($order->cart->full_total->prices[0]->price_value_with_tax,2);
			
//retun 	HIKASHOP_LIVE."index.php?option=com_hikashop&ctrl=checkout&task=after_end";
//			HIKASHOP_LIVE."index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment=Pesapal&tmpl=component";
//			HIKASHOP_LIVE."index.php?option=com_hikashop&ctrl=order&task=cancel_order";
//			HIKASHOP_LIVE."index.php?option=com_hikashop&ctrl=checkout&task=after_end";
			$this->payment_params->return_url = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id='.$order_id.$this->url_itemid;
			$vars = array(
				'EMAIL' => $user->email,
				'TYPE' => "MERCHANT",
				'DESCRIPTION' => "My Order",
				'TYPE' => "MERCHANT",
				'REFERENCE' => $order->order_number,
				'FIRST_NAME' => $order->cart->$address_type->address_firstname,
				'LAST_NAME'=> $order->cart->$address_type->address_lastname,
				'IFRAMELINK'=> $this->payment_params->pesapal_link,
				'CALLBACK'=> $this->payment_params->return_url,
				//'CX1'	=> 	plgHikashoppaymentPesapal::encrypt($this->payment_params->keyx, ENCRYPTION_KEY),	
				//'CX2'	=> plgHikashoppaymentPesapal::encrypt($this->payment_params->secret, ENCRYPTION_KEY),
				//'IDENTIFIER' => $this->payment_params->secret, //User's identifier on the payment platform
				'CLIENTIDENT' => $order->order_user_id, 
				'DESCRIPTION' => "order number : ".$order->order_number,
				'ORDERID' => $order->order_id,
				'VERSION' => 2.0,
				'AMOUNT' => $amout 
			);
			//self
			$this->payment_params->return_urlx = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id='.$order_id.$this->url_itemid;
			$this->payment_params->ref = $order->order_number;
			$this->payment_params->firstname = $order->cart->$address_type->address_firstname;
			$this->payment_params->lastname = $order->cart->$address_type->address_lastname;
			$this->payment_params->amount = $amout;
			
			//endself
			$vars['HASH'] = $this->pesapal_signature($this->payment_params->secret,$vars);
			$this->vars = $vars;
			?>
            <!----------------->
            
<form style="display:none" id="hikashop_pesapal_form" name="hikashop_pesapal_form" action="<?php echo JURI::base() ?>plugins/hikashoppayment/Pesapal/pesapal_end.php" method="post">
    <div id="hikashop_pesapal_end_image" class="hikashop_pesapal_end_image">
      <input id="hikashop_pesapal_button" type="submit" class="btn btn-primary" value="<?php echo JText::_('PAY_NOW');?>" name="" alt="<?php echo JText::_('PAY_NOW');?>" />
    </div>
    <?php
			foreach($this->vars as $name => $value ) {
				echo '<input type="hidden" name="'.$name.'" value="'.htmlspecialchars((string)$value).'" />';
			}
			JRequest::setVar('noform',1); ?>
</form>
 <script type="text/javascript">
  	$(function(){
		$.post('<?php echo JURI::root() ?>index.php?option=com_hikashop&ctrl=checkout&task=step&step=1',$('#hikashop_pesapal_form').serialize(), function(){
			});
	});
			//document.getElementById('hikashop_pesapal_form').submit();
  </script> 
  
            <!------------------->
            <?php
			return $this->showPage('end'); 
		}
	}

	function getPaymentDefaultValues(&$element)
	{
		$element->payment_name='Pesapal';
		$element->payment_description='You can pay by visa/mobile money using this payment method';
		$element->payment_images='Pesapal';
		$element->payment_params->address_type="billing";
		$element->payment_params->notification=1;
		$element->payment_params->invalid_status='cancelled';
		$element->payment_params->verified_status='confirmed';
	}

	function onPaymentNotification(&$statuses)
	{
		$user = JFactory::getUser();
		$config = JFactory::getConfig();
		
		$vars = array();
		$filter = JFilterInput::getInstance();
		foreach($_REQUEST as $key => $value)
		{
			$key = $filter->clean($key);
			$value = JRequest::getString($key);
			$vars[$key]=$value;
		}

		$order_id = (int)@$vars['ORDERID'];
		$dbOrder = $this->getOrder($order_id);
		$this->loadPaymentParams($dbOrder);
		if(empty($this->payment_params))
			return false;
		$this->loadOrderData($dbOrder);

		$hash = $this->pesapal_signature($this->payment_params->keyx,$vars,false,true);
		if($this->payment_params->debug) //Debug mode activated or not
		{
			echo print_r($vars,true)."\n\n\n";
			echo print_r($dbOrder,true)."\n\n\n";
			echo print_r($hash,true)."\n\n\n";
		}

		if (strcasecmp($hash,$vars['HASH'])!=0) 
		{
			if($this->payment_params->debug)
				echo 'Hash error '.$vars['HASH'].' - '.$hash."\n\n\n";
			return false;
		}
		elseif($vars['EXECCODE']!='0000') 
		{
			if($this->payment_params->debug)
				echo 'payment '.$vars['MESSAGE']."\n\n\n";
			$this->modifyOrder($order_id, $this->payment_params->invalid_status, true, true); 
			return false;
		}
		else 
		{
			$this->modifyOrder($order_id, $this->payment_params->verified_status, true, true);
			 //$this->app->redirect($return_url);
			return true;
		}
	}
	function pesapal_signature($secret, $parameters, $debug=false, $decode=false)
	{
		ksort($parameters); 
		$clear_string = $secret;
		$expectedKey = array (
			'IDENTIFIER',
			'TRANSACTIONID',
			'CLIENTIDENT',
			'CLIENTEMAIL',
			'ORDERID',
			'VERSION',
			'LANGUAGE',
			'CURRENCY',
			'EXTRADATA',
			'CARDCODE',
			'CARDCOUNTRY',
			'EXECCODE',
			'MESSAGE',
			'DESCRIPTOR',
			'ALIAS',
			'3DSECURE',
			'AMOUNT',
		);
		foreach ($parameters as $key => $value)
		{
			if ($decode)
			{
				if (in_array($key,$expectedKey))
					$clear_string .= $key . '=' . $value . $secret;
			}
			else
				$clear_string .= $key . '=' . $value . $secret;
		}


		if (PHP_VERSION_ID < 50102)
		{
			$this->app->enqueueMessage('The Pesapal payment plugin requires at least the PHP 5.1.2 version to work, but it seems that it is not available on your server. Please contact your web hosting to set it up.','error');
			return false;
		}
		else
		{
			if ($debug)
				return $clear_string;
			else
				return hash('sha256', $clear_string); 
		}
}
}