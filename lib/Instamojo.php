<?php
/**
 * Instamojo
 * used to manage Instamojo API calls
 * 
 */
include_once __DIR__ . DIRECTORY_SEPARATOR . "curl.php";
include_once __DIR__ . DIRECTORY_SEPARATOR . "ValidationException.php";

use \ValidationException as ValidationException;
use \Exception as Exception;

Class Instamojo
{
	private $api_endpoint;
	private $auth_endpoint;
	private $payment_endpoint;
	private $order_endpoint;
	private $getpayment_endpoint;
	private $application_endpoint;
	private $auth_headers;
	private $access_token;
	private $client_id;
	private $client_secret;
	
	 function __construct($client_id,$client_secret,$test_mode)
	{
		
		
		$this->curl = new Curl();
		$this->curl->setCacert(__DIR__."/cacert.pem");
		$this->client_id 		= $client_id;
		$this->client_secret	= $client_secret;

		if($test_mode){
			$this->api_endpoint  = "https://test.instamojo.com/v2/";
			$this->auth_endpoint = "https://test.instamojo.com/oauth2/token/";
			$this->payment_endpoint = "https://test.instamojo.com/v2/payment_requests/";
			$this->order_endpoint = "https://test.instamojo.com/v2/gateway/orders/";
		    $this->refund_endpoint = "https://test.instamojo.com/v2/payments/{payment_id}/refund/";
			$this->getpayment_endpoint ="https://test.instamojo.com/v2/payment_requests/:id/";
			$this->application_endpoint ="https://test.instamojo.com/oauth2/token/";
			$this->get_gatewayorder_endpoint = "https://test.instamojo.com/v2/gateway/orders/{id}/";
			$this->checkout_endpoint ="https://test.instamojo.com/v2/gateway/orders/{id}/checkout-options/";
			$this->get_paymentdetails_endpoint="https://test.instamojo.com/v2/payments/{id}/";
		}
		else{
			$this->api_endpoint  = "https://api.instamojo.com/v2/";
			$this->auth_endpoint = "https://api.instamojo.com/oauth2/token/";
			$this->payment_endpoint = "https://api.instamojo.com/v2/payment_requests/"; 
			$this->order_endpoint = "https://api.instamojo.com/v2/gateway/orders/"; 
		    $this->refund_endpoint = "https://api.instamojo.com/v2/payments/{payment_id}/refund/";
			$this->getpayment_endpoint ="https://api.instamojo.com/v2/payment_requests/:id/";
			$this->application_endpoint ="https://api.instamojo.com/oauth2/token/";
			$this->get_gatewayorder_endpoint = "https://api.instamojo.com/v2/gateway/orders/{id}/";
			$this->checkout_endpoint ="https://api.instamojo.com/v2/gateway/orders/{id}/checkout-options/";
			$this->get_paymentdetails_endpoint="https://api.instamojo.com/v2/payments/{id}/";
			
		}
			
		
		$this->getAccessToken();
	}

	public function getAccessToken()
	{
		$data = array();
		
		$data['client_id']		= $this->client_id;
		$data['client_secret'] 	= $this->client_secret;
		$data['scopes'] 		= "all";
		$data['grant_type'] 	= "client_credentials";

		$result = $this->curl->post($this->auth_endpoint,$data);
		if($result)
		{
			$result = json_decode($result);
			if(isset($result->error))
			{
				throw new ValidationException("The Authorization request failed with message '$result->error'",array("Payment Gateway Authorization Failed."),$result);
			}else
				$this->access_token = 	$result->access_token;
			
		}
		
		$this->auth_headers[] = "Authorization:Bearer $this->access_token";
		
	}
	public function createOrderPayment($data)
	{ 
		$endpoint = $this->order_endpoint;
		$result = $this->curl->post($endpoint,$data,array("headers"=>$this->auth_headers));
		$result =json_decode($result);
		
		if($result)
		{
			return $result;
		}else{
			$errors = array();  
			if(isset($result->message))
				throw new ValidationException("Validation Error with message: $result->message",array($result->message),$result);
			
			foreach($result as $k=>$v)
			{
				if(is_array($v))
					$errors[] =$v[0];
			}
			if($errors)
				throw new ValidationException("Validation Error Occurred with following Errors : ",$errors,$result);
		}
	}
	
	public function requestPayment($data)
	{
		
		$endpoint = $this->payment_endpoint;
		$result = $this->curl->post($endpoint,$data,array("headers"=>$this->auth_headers));
		$result_new =json_decode($result);
		if($result_new->id)
		{
			return $result_new;
		}else{
			throw new Exception("Unable to Fetch Payment Request id:'$id' Server Responds ".print_R($result,true));
		}
	}
	public function getGatewayOrder($data)
	{
		$endpoint = str_replace("{id}",$data->id,$this->get_gatewayorder_endpoint);
		$result = $this->curl->get($endpoint,array("headers"=>$this->auth_headers));
		$result_new =json_decode($result);
		if($result_new)
		{
			return $result_new;
		}else{
			throw new Exception("Unable to Fetch Payment Request id:'$id' Server Responds ".print_R($result,true));
		}
	}
	
	public function checkOutOrder($data)
	{
		$endpoint = str_replace("{id}",$data->id,$this->checkout_endpoint);
		$result = $this->curl->get($endpoint,array("headers"=>$this->auth_headers));
		$result_new =json_decode($result);
		if($result_new)
		{
			return $result_new;
		}else{
			throw new Exception("Unable to Fetch Payment Request id:'$id' Server Responds ".print_R($result,true));
		}
	}
	
	public function paymentDetails($data)
	{
		$endpoint = str_replace("{id}",$data->id,$this->get_paymentdetails_endpoint);
		$result = $this->curl->get($endpoint,array("headers"=>$this->auth_headers));
		$result_new =json_decode($result);
		if($result_new)
		{
			return $result_new;
		}else{
			throw new Exception("Unable to Fetch Payment Request id:'$id' Server Responds ".print_R($result,true));
		}
	}
	
	
	public function refundamount($data)
	{
		$payload = Array(
			'transaction_id' => 'partial_refund_1',
			'type' => 'TNR',
			'body' => 'Need to refund to the buyer.',
			'refund_amount' => '100'
		);

		$endpoint = str_replace("{payment_id}",$payload->id,$this->refund_endpoint);
		$result = $this->curl->post($endpoint,$data,array("headers"=>$this->auth_headers));
		$result_new =json_decode($result);
		if($result_new)
		{
			return $result_new;
		}else{
			throw new Exception("Unable to Fetch Payment Request id:'$id' Server Responds ".print_R($result,true));
		}
	}
	
	public function getOrderById($id)
	{
		$endpoint = $this->order_endpoint."id:$id/";
		$result = $this->curl->get($endpoint,array("headers"=>$this->auth_headers));
		
		$result = json_decode($result);
		if(isset($result->id) and $result->id)
			return $result;
		else
			throw new Exception("Unable to Fetch Payment Request id:'$id' Server Responds ".print_R($result,true));
	}

	public function getPaymentStatus($payment_id, $payments){
		foreach($payments as $payment){
		    if($payment->id == $payment_id){
			    return $payment->status;
		    }
		}
	}
	
}
