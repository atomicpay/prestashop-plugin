<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/atomicpay.php');

$post = file_get_contents("php://input");

if(false === empty($post) && function_exists('json_decode'))
{
	$json = json_decode($post);
    if(false === empty($json))
    {
		$invoice_id = $json->invoice_id;
		if($invoice_id != "")
		{
			$AccountID = Configuration::get('ATOMICPAY_API_ACCOUNTID');
			$AccountPrivateKey = Configuration::get('ATOMICPAY_API_PRIVATEKEY');
			$endpoint_url = "https://merchant.atomicpay.io/api/v1/invoices/$invoice_id";
			$encoded_auth = base64_encode("$AccountID:$AccountPrivateKey");
			$authorization = "Authorization: BASIC $encoded_auth";

			$options = [
			CURLOPT_URL        => $endpoint_url,
			CURLOPT_HTTPHEADER => array('Content-Type:application/json', $authorization),
			CURLOPT_RETURNTRANSFER => true
			];

			$curl = curl_init();
			curl_setopt_array($curl, $options);
			$response = curl_exec($curl);
			curl_close($curl);

			$data = json_decode($response);
			$code = $data->code;

			if($code == "200")
			{
				$result = $data->result;
				$result_array = $result["0"];

				$atm_invoice_timestamp = $result_array->invoice_timestamp;
				$atm_invoice_id = $result_array->invoice_id;
				$atm_order_id = $result_array->order_id;
				$atm_order_description = $result_array->order_description;
				$atm_order_price = $result_array->order_price;
				$atm_order_currency = $result_array->order_currency;
				$atm_transaction_speed = $result_array->transaction_speed;
				$atm_payment_currency = $result_array->payment_currency;
				$atm_payment_rate = $result_array->payment_rate;
				$atm_payment_address = $result_array->payment_address;
				$atm_payment_paid = $result_array->payment_paid;
				$atm_payment_due = $result_array->payment_due;
				$atm_payment_total = $result_array->payment_total;
				$atm_payment_txid = $result_array->payment_txid;
				$atm_payment_confirmation = $result_array->payment_confirmation;
				$atm_notification_email = $result_array->notification_email;
				$atm_notification_url = $result_array->notification_url;
				$atm_redirect_url = $result_array->redirect_url;
				$atm_status = $result_array->status;
				$atm_statusException = $result_array->statusException;

				if($atm_payment_rate != ""){ $atm_payment_rate = "$atm_payment_rate $atm_order_currency"; }

				$explode_order_id = explode('-',$atm_order_id);
				$atm_order_reference = $explode_order_id[0];
				$atm_order_id = $explode_order_id[1];

				$order = Order::getByReference($atm_order_reference)->getFirst();
				$order_id = $order->id;
				$order_current_status = $order->current_state;
				$order_payment_method = $order->payment;

				if($order_id == $atm_order_id)
				{

						switch ($atm_status)
						{
							case 'paid':
							if($atm_statusException != "")
							{
								if($atm_statusException == "Overpaid")
								{
									$order->setCurrentState((int) Configuration::get('ATOMICPAY_OS_OVERPAID'));
								}
							}
							else
							{
								$order->setCurrentState((int) Configuration::get('ATOMICPAY_OS_PAID'));
							}

							break;

							case 'confirmed':
							$order->setCurrentState((int) Configuration::get('ATOMICPAY_OS_CONFIRMED'));
							break;

							case 'complete':
							$order->setCurrentState((int) Configuration::get('PS_OS_PAYMENT'));
							break;

							case 'invalid':
							$order->setCurrentState((int) Configuration::get('ATOMICPAY_OS_INVALID'));
							break;

							case 'expired':
							if($atm_statusException != "")
							{
								if($atm_statusException == "Paid After Expiry")
								{
									$order->setCurrentState((int) Configuration::get('ATOMICPAY_OS_PAID_AFTER_EXPIRY'));
								}

								if($atm_statusException == "Underpaid")
								{
									$order->setCurrentState((int) Configuration::get('ATOMICPAY_OS_UNDERPAID'));
								}
							}
							else
							{
								$order->setCurrentState((int) Configuration::get('ATOMICPAY_OS_EXPIRED'));
							}

							break;

							default:
						}

						Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."atomicpay_transactions` SET `payment_currency` = '". $atm_payment_currency ."', `payment_rate` = '". $atm_payment_rate ."', `payment_total` = '". $atm_payment_total ."', `payment_paid` = '". $atm_payment_paid ."', `payment_due` = '". $atm_payment_due ."', `payment_address` = '". $atm_payment_address ."', `payment_confirmation` = '". $atm_payment_confirmation ."', `payment_tx_id` = '". $atm_payment_txid ."', `status` = '". $atm_status ."', `status_exception` = '". $atm_statusException ."' WHERE `invoice_id` = '". $atm_invoice_id ."'");
				}
				else
				{

				}
			}
        }
    }
}
else
{

}
?>
