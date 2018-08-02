<?php

class ps_arianpal extends ps_payment_gateway {
	public $merchant_id;
	public $password;

	public function __construct() {
		self::load_nusoap();
	}

	public function send( $callback, $price, $username, $email, $order_id ) {
		$client                   = new nusoap_client( 'http://merchant.arianpal.com/WebService.asmx?wsdl', 'wsdl' );
		$client->soap_defencoding = 'UTF-8';
		$res                      = $client->call( 'RequestPayment', array(
			'MerchantID'  => $this->merchant_id,
			'Password'    => $this->password,
			'Price'       => $price,
			'ReturnPath'  => $callback,
			'ResNumber'   => time(),
			'Description' => 'خرید از سایت با استفاده از افزونه فروش پست',
			'Paymenter'   => $username,
			'Email'       => $email,
			'Mobile'      => ''
		) );

		$PayPath = $res['RequestPaymentResult']['PaymentPath'];
		$status  = $res['RequestPaymentResult']['ResultStatus'];

		if ( $status == 'Succeed' ) {
			$this->insert_payment( $username, $price, $order_id, $email );
			echo $this->info_alert( 'در حال اتصال به درگاه ...' );
			$this->redirect( $PayPath );
		} else {
			echo $this->danger_alert( 'خطا در متصل شدن به درگاه !' . $status );
		}
	}

	public function verify( $price, $post_id, $order_id, $course_id = 0 ) {
		if ( isset( $_POST['status'] ) && isset( $_POST['refnumber'] ) ) {
			if ( $_POST['status'] == 100 ) {
				$Refnumber                = $_POST['refnumber'];
				$client                   = new nusoap_client( 'http://merchant.arianpal.com/WebService.asmx?wsdl', 'wsdl' );
				$client->soap_defencoding = 'UTF-8';
				$res                      = $client->call( 'VerifyPayment', array(
					'MerchantID' => $this->merchant_id,
					'Password'   => $this->password,
					"Price"      => $price,
					"RefNum"     => $Refnumber
				) );
				$status                   = $res['verifyPaymentResult']['ResultStatus'];
//			$PayPrice = $res['verifyPaymentResult']['PayementedPrice'];
				if ( $status == 'success' ) {
					$this->success_payment( $Refnumber, $order_id, $price, $post_id, $course_id );
				} else {
					echo $this->danger_alert( 'خطا در پردازش عملیات پرداخت ، نتیجه پرداخت : ' . $status );
				}
			} else {
				echo $this->danger_alert( 'پرداخت ناموفق!' );
			}
			$this->end_payment();
		}
	}
}