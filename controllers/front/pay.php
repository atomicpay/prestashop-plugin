<?php
class AtomicPayPayModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $orderReference = Tools::getValue('order');
        /** @var Order $order */
        $order = Order::getByReference($orderReference)->getFirst();

        // Get Customer Details
        $customer = $order->getCustomer();
        $currency = Currency::getCurrencyInstance((int)$order->id_currency);
        $currency_code = $currency->iso_code;

        $orderID = $order->id;
        $orderString = "$orderReference-ID-$orderID";
        $order_total = $order->total_paid;
        $order_total = round($order_total, 2);
        $notification_email = $customer->email;
        $notification_url = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'modules/'.$this->module->name.'/ipn.php';

        if ((float) _PS_VERSION_ < 1.7) {
            $redirect_url = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'index.php?controller=order-detail&id_order='.$order->id.'&key='.$customer->secure_key;
        } else {
            $redirect_url = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'index.php?controller=order-confirmation&id_cart='.$order->id_cart.'&id_module='.$this->module->id.'&id_order='.$order->reference.'&key='.$customer->secure_key;
        }

        $transaction_speed = Configuration::get('ATOMICPAY_TRANSACTIONSPEED');

        if($transaction_speed == "1") { $transaction_speed = "high"; }
        if($transaction_speed == "2") { $transaction_speed = "medium"; }
        if($transaction_speed == "3") { $transaction_speed = "low"; }

        $AccountID = Configuration::get('ATOMICPAY_API_ACCOUNTID');
        $AccountPrivateKey = Configuration::get('ATOMICPAY_API_PRIVATEKEY');

        $endpoint_url = "https://merchant.atomicpay.io/api/v1/invoices";
        $encoded_auth = base64_encode("$AccountID:$AccountPrivateKey");
        $authorization = "Authorization: BASIC $encoded_auth";

        $data_to_post = [
          'order_id' => $orderString,
          'order_price' => $order_total,
          'order_currency' => $currency_code,
          'notification_email' => $notification_email,
          'notification_url' => $notification_url,
          'redirect_url' => $redirect_url,
          'transaction_speed' => $transaction_speed
        ];

        $data_to_post = json_encode($data_to_post);

        $options = [
        CURLOPT_URL        => $endpoint_url,
        CURLOPT_HTTPHEADER => array('Content-Type:application/json', $authorization),
        CURLOPT_POST       => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $data_to_post
        ];

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response);
        $code = $data->code;

        if($code == "200")
        {
          $invoice_id = $data->invoice_id;
          $invoice_url = $data->invoice_url;

          Db::getInstance()->execute("INSERT INTO `"._DB_PREFIX_."atomicpay_transactions` (`order_id`, `order_reference`, `invoice_id`, `invoice_url`) VALUES ('". $orderID ."', '". $orderReference ."', '". $invoice_id ."', '". $invoice_url ."')");

          \ob_clean();
          header('Location:  ' . $invoice_url);
          exit;
        }
        else
        {
          $errormessage = $data->message;
          die(Tools::displayError("Error: Generation of payment invoice failed. " . $errormessage));
        }
    }
}
