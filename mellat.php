<?php

/**
 * کلاس درگاه پرداخت بانک ملت
 *
 * با استفاده از این کلاس میتوانید به راحتی از درگاه پرداخت بانک ملت 
 * در نرم افزار های تحت وب خود استفاده کنید، همچنین میتوایند از این
 * کلاس در سی ام اس هایی مانند وردپرس ، جوملا و .. نیز استفاده کنید.
 *
 * @category  Gateway
 * @package   Mellatbank
 * @license   http://www.opensource.org/licenses/BSD-3-Clause
 * @example   ../index.php
 * @example <br />
 *  $mellat = new MellatBank();<br />
 *  $mellat->startPayment('1000', 'http://localhost');<br />
 *  $results = $mellat->checkPayment($_POST);<br />
 *  if($results['status']=='success') echo 'OK';<br />
 * @version   1
 * @since     2014-12-10
 * @author    Hasan Shafei [ www.netparadis.com ]
 */
class MellatBank {
	
	/**
   * ترمینال درگاه بانک ملت.
   * @var intiger
   */
  private $terminal = 'Enter Your Terminal' ;

  /**
   * نام کاربری درگاه بانک ملت.
   * @var string
   */
  private $username = 'Enter Your Username' ;

  /**
   * رمز عبور درگاه بانک ملت.
   * @var string
   */
  private $password = 'Enter Your Password' ;
	
	
	/**
	 * __cunstruct
	 *
	 * @terminal : bankmellat terminal (int)
	 * @username : bankmellat username (string)
	 * @password : bankmellat password (string)
	 */
	public function __cunstruct($terminal = '', $username = '', $password = '')
	{
		if(!empty($terminal))
			$this->terminal = $terminal;

		if(!empty($username))
			$this->username = $username;

		if(!empty($password))
			$this->password = $password;
	}

	
	/**
	 * تابع پرداخت
	 * با استفاده از این متد میتوانید درخواست پرداخت را به بانک ملت ارسال کنید.
	 *
	 * @param intiger $amount : مبلغ پرداخت
	 * @param string $callBackUrl : آدرس برگشت بعد از پرداخت
	 *
	 
	 * @author  Hasan Shafei [ www.netparadis.com ]
	 */
	public function startPayment($amount, $callBackUrl,$additionalData)
	{			
		$client = new nusoap_client( 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl' ) ;
    $client->setCurlOption(CURLOPT_CONNECTTIMEOUT, 0);
		$terminalId = $this->terminal ;
		$userName = $this->username;
		$userPassword = $this->password;
		$orderId = rand(0,9999).time();
		$localDate = date('ymj');
		$localTime = date('His');
		$payerId = $_SESSION['customer']['user_id'];
		$err = $client->getError();
		if ($err) {
			echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			die();
		}
		$parameters = array(
			'terminalId' => $terminalId,
			'userName' => $userName,
			'userPassword' => $userPassword,
			'orderId' => $orderId,
			'amount' => $amount,
			'localDate' => $localDate,
			'localTime' => $localTime,
			'additionalData' => $additionalData,
			'callBackUrl' => $callBackUrl,
			'payerId' => $payerId);
		$result = $client->call('bpPayRequest', $parameters, 'http://interfaces.core.sw.bps.com/');
		if ($client->fault) {
			echo '<h2>Fault</h2><pre>';
			print_r($result);
			echo '</pre>';
			die();
		} 
		else {
			$resultStr  = $result;
			$err = $client->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
				die();
			} 
			else {
				$res = explode (',',$resultStr);
				echo '<div style="display:none;">Pay Response is : ' . $resultStr . '</div>';
				$ResCode = $res[0];	
				if ($ResCode == "0") {
					$this->postRefId($res[1]);
				} 
				else {
					$this->error($ResCode);
				}
			}
		}
			
	}
	
	
	/**
	 * تابع تایید پرداخت
	 * با استفاده از این تابع میتوانید درخواست تایید پرداخت را 
	 * به بانک ملت ارسال کنید و پاسخ آن را دریافت کنید.
	 *
	 * @param array $params : اطلاعات دریافتی از درگاه پرداخت
	 *
	 * @return  void
	 *
	 
	 * @author  Hasan Shafei [ www.netparadis.com ]
	 */
	protected function verifyPayment($params) 
	{
		$client = new nusoap_client( 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl' ) ;
    $client->setCurlOption(CURLOPT_CONNECTTIMEOUT, 0);
		$orderId = $params["SaleOrderId"];
		$verifySaleOrderId = $params["SaleOrderId"];
		$verifySaleReferenceId = $params['SaleReferenceId'];
		$err = $client->getError();
		if ($err) {
			echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			die();
		}	  
		$parameters = array(
			'terminalId'=> $this->terminal, 
			'userName'=> $this->username, 
			'userPassword'=> $this->password, 
			'orderId' => $orderId,
			'saleOrderId' => $verifySaleOrderId,
			'saleReferenceId' => $verifySaleReferenceId);
		$result = $client->call('bpVerifyRequest', $parameters, 'http://interfaces.core.sw.bps.com/');
		if ($client->fault) {
			echo '<h2>Fault</h2><pre>';
			print_r($result);
			echo '</pre>';
			die();
		} 
		else {
			$resultStr = $result;	
			$err = $client->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
				die();
			} 
			else {
				if( $resultStr == '0' ) {
					return true;
				}
			}
		}
		return false;
	}
	
	
	/**
	 * تابع درخواست تصفیه حساب
	 * با استفاده از این تابع میتوانید درخواست تصفیه حساب
	 * را به بانک ملت ارسال و نتیجه آن را دریافت کنید.
	 *
	 * @param array $params : اطلاعات دریافتی از درگاه پرداخت
	 *
	 * @return  void
	 *
	 
	 * @author  Hasan Shafei [ www.netparadis.com ]
	 */
	protected function settlePayment($params) 
	{
		$client = new nusoap_client( 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl' ) ;
    $client->setCurlOption(CURLOPT_CONNECTTIMEOUT, 0);
		$orderId = $params["SaleOrderId"];
		$settleSaleOrderId = $params["SaleOrderId"];
		$settleSaleReferenceId = $params['SaleReferenceId'];
		$err = $client->getError();
		if ($err) {
			echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			die();
		}		  
		$parameters = array(
			'terminalId'=> $this->terminal, 
			'userName'=> $this->username, 
			'userPassword'=> $this->password, 
			'orderId' => $orderId,
			'saleOrderId' => $settleSaleOrderId,
			'saleReferenceId' => $settleSaleReferenceId);
		$result = $client->call('bpSettleRequest', $parameters, 'http://interfaces.core.sw.bps.com/');
		if ($client->fault) {
			echo '<h2>Fault</h2><pre>';
			print_r($result);
			echo '</pre>';
			die();
		} 
		else {
			$resultStr = $result;	
			$err = $client->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
				die();
			} 
			else {
				if( $resultStr == '0' ) {
					return true;
				}
				return $resultStr ;
			}
		}
		return false;
	}
	
	
	/**
	 * تابع بررسی ترانش
	 * با استفاده از این تابع میتوانید درخواست تایید و تصفیه حساب را 
	 * ارسال کنید و از نتیجه آن آگاه شوید.
	 *
	 * @param array $params : اطلاعات دریافتی از درگاه پرداخت
	 *
	 * @return  void
	 *
	 * @author  Hasan Shafei [ www.netparadis.com ]
	 */
	public function checkPayment($params) 
	{
		$params["RefId"] = $params["RefId"] ;
		$params["ResCode"] = $params["ResCode"] ;
		$params["SaleOrderId"] = $params["SaleOrderId"] ;
		$params["SaleReferenceId"] = $params["SaleReferenceId"] ;
		if( $params["ResCode"] == 0 ) 
		{
			if( $this->verifyPayment($params) == true ) {
				if( $this->settlePayment($params) == true ) {
					return array(
						"status"=>"success", 
						"trans"=>$params["SaleReferenceId"]
					);
				}
			}
		}
		return false;
	}	
	
	
	protected function postRefId($refIdValue) 
	{
		echo '<script language="javascript" type="text/javascript"> 
				function postRefId (refIdValue) {
				var form = document.createElement("form");
				form.setAttribute("method", "POST");
				form.setAttribute("action", "https://bpm.shaparak.ir/pgwchannel/startpay.mellat");         
				form.setAttribute("target", "_self");
				var hiddenField = document.createElement("input");              
				hiddenField.setAttribute("name", "RefId");
				hiddenField.setAttribute("value", refIdValue);
				form.appendChild(hiddenField);
	
				document.body.appendChild(form);         
				form.submit();
				document.body.removeChild(form);
			}
			postRefId("' . $refIdValue . '");
			</script>';
	}
	
	
	protected function error($number) 
	{
		$err = $this->response($number);
		echo '<!doctype html><html><head><meta charset="utf-8"><title>خطا</title></head><body dir="rtl">';
		echo '<style>div.error{direction:rtl;background:#A80202;float:right;text-align:right;color:#fff;';
		echo 'font-family:tahoma;font-size:13px;padding:3px 10px}</style>';
		echo '<div class="error"><strong>خطا</strong> : ' . $err . '</div>';
		die ;
	}
	
	
	
	protected function response($number) 
	{
		switch($number) {
			case 31 :
				$err = "پاسخ نامعتبر است!";	
				break;
			case 17 :
				$err = "کاربر از انجام تراکنش منصرف شده است!";
				break;
			case 21 :
				$err = "پذیرنده نامعتبر است!";
				break;
			case 25 :
				$err = "مبلغ نامعتبر است!";
				break;
			case 34 :
				$err = "خطای سیستمی!";
				break;
			case 41 :
				$err = "شماره درخواست تکراری است!";
				break;
			case 421 :
				$err = "ای پی نامعتبر است!";
				break;
			case 412 :
				$err = "شناسه قبض نادرست است!";
				break;
			case 45 :
				$err = "تراکنش از قبل ستل شده است";
				break;
			case 46 :
				$err = "تراکنش ستل شده است";
				break;
			case 35 :
				$err = "تاریخ نامعتبر است";
				break;
			case 32 :
				$err = "فرمت اطلاعات وارد شده صحیح نمیباشد";
				break;
			case 43 :
				$err = "درخواست verify قبلا صادر شده است";
				break;
			
		}
		return $err ;
	}


}
