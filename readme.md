**PesaPal Payment Plugin for Joomla's Hikashop**


-------------------------------------------------
**Saw it cool for us to have local methods integrated! :)**


Howdy,

This plugin helps you make payments using pesapal as a payment method in hikashop.

 
 * Install Via Joomla Install Menu.

 * Configure Backend, via Plugins Menu / Hikashop Plugins

 * Include **Key** and **Secret** in the file named **pesapal-ipn-listener** 

 * Also, Change $statusrequestAPI (http://demo.pesapal.com/api/QueryPaymentStatus) to 
			https://www.pesapal.com/API/QueryPaymentStatus when going live

----

	$consumer_key = 'ConsumerKey'; 
	$consumer_secret = 'consumerSecret'; 

----

* You can use below for tests. 

**Key** joWKvjo8YFczk01HgZQtZk6u3A2bhfNt
**Secret** nONPLP+nlfSPMHQPqgdlg7fSWxk=

* Point the ipn listening url to the file in **pesapal's** IPN [settings](http://pesapal.com/merchantipn) . 


* put a logo image in the plugin folder plz `logo.png` 

All Systems Go 


-------------------------------------------------
