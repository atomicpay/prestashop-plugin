<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @property int  is_eu_compatible
 * @property bool bootstrap
 */
class AtomicPay extends PaymentModule
{
    protected $_html = '';
    protected $_postErrors = array();

    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name = 'atomicpay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->author = 'AtomicPay';
        $this->controllers = array('validation', 'payment');
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('AtomicPay');
        $this->description = $this->l('Accept cryptocurrency payments via AtomicPay.');

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }

        if (!Configuration::get('ATOMICPAY_API_ACCOUNTID') || !Configuration::get('ATOMICPAY_API_PUBLICKEY') || !Configuration::get('ATOMICPAY_API_PRIVATEKEY')) {
            $this->warning = $this->l('Please set your API keys');
        }
    }

    public function uninstall() {
      Configuration::deleteByName('ATOMICPAY_TITLE');
      Configuration::deleteByName('ATOMICPAY_API_ACCOUNTID');
      Configuration::deleteByName('ATOMICPAY_API_PUBLICKEY');
      Configuration::deleteByName('ATOMICPAY_API_PRIVATEKEY');
      Configuration::deleteByName('ATOMICPAY_TRANSACTIONSPEED');
      return parent::uninstall();
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        if (!$this->installOrderState()) {
            return false;
        }

        if (!$this->installSQL()) {
            return false;
        }

        if ((float) _PS_VERSION_ < 1.7) {
            // hooks for 1.6
            if (!parent::install() || !$this->registerHook('payment')) {
                return false;
            }
        } else {
            // hooks for 1.7
            if (!parent::install()
                || !$this->registerHook('invoice')
                || !$this->registerHook('paymentOptions')
                || !$this->registerHook('paymentReturn')
            ) {
                return false;
            }
        }

        Configuration::updateValue('ATOMICPAY_TITLE', 'Pay with cryptocurrencies via AtomicPay');

        return true;
    }

    private function installSQL()
    {
        $db = Db::getInstance();
        $query = "CREATE TABLE IF NOT EXISTS `" ._DB_PREFIX_. "atomicpay_transactions` (
              `record` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
              `order_id` int(11) NOT NULL,
              `order_reference` VARCHAR(20) NOT NULL,
              `invoice_id` VARCHAR(100),
              `invoice_url` VARCHAR(1000),
              `payment_currency` VARCHAR(20),
              `payment_rate` VARCHAR(20),
              `payment_total` VARCHAR(100),
              `payment_paid` VARCHAR(100),
              `payment_due` VARCHAR(100),
              `payment_address` VARCHAR(100),
              `payment_confirmation` VARCHAR(20),
              `payment_tx_id` VARCHAR(200),
              `status` VARCHAR(20),
              `status_exception` VARCHAR(20),
              KEY `order_reference` (`order_reference`),
              KEY `order_id` (`order_id`),
              KEY `invoice_id` (`invoice_id`),
              KEY `status` (`status`)
        ) ENGINE="._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

        $db->Execute($query);

        return true;
    }


    public function hookPayment($params)
    {
        if (!$this->active) {
            return null;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return null;
        }

        if (!Configuration::get('ATOMICPAY_API_ACCOUNTID') || !Configuration::get('ATOMICPAY_API_PUBLICKEY') || !Configuration::get('ATOMICPAY_API_PRIVATEKEY')) {
            return [];
        }

        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_bw' => $this->_path,
        ));

        return $this->display(__FILE__, 'payment.tpl');
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return [];
        }

        if (!$this->checkCurrency($params['cart'])) {
            return [];
        }

        if (!Configuration::get('ATOMICPAY_API_ACCOUNTID') || !Configuration::get('ATOMICPAY_API_PUBLICKEY') || !Configuration::get('ATOMICPAY_API_PRIVATEKEY')) {
            return [];
        }

        return [
            $this->getPaymentOption(),
        ];
    }

    public function getPaymentOption()
    {
        libxml_use_internal_errors(true);
        $embeddedOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $embeddedOption
            ->setModuleName($this->name)
            ->setCallToActionText(Configuration::get('ATOMICPAY_TITLE'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
        ;

        return $embeddedOption;
    }

    public function hookPaymentReturn($params) {
      global $smarty;

      $order = $params['order'];
      $state = $order->current_state;

      if($state == Configuration::get('ATOMICPAY_OS_WAITING')){$title = "Order Pending Payment"; $message = "This order is pending payment. Order will be processed as soon as payment is validated."; $state = "1";}
      if($state == Configuration::get('ATOMICPAY_OS_PAID')){$title = "Payment Pending Confirmation"; $message = "Payment awaiting network confirmation. Order will be processed as soon as payment is confirmed."; $state = "1";}
      if($state == Configuration::get('ATOMICPAY_OS_OVERPAID')){$title = "Payment Pending Confirmation"; $message = "This order is overpaid. Awaiting network confirmation. Order will be processed as soon as payment is confirmed."; $state = "1";}
      if($state == Configuration::get('ATOMICPAY_OS_CONFIRMED')){$title = "Payment Pending Completion"; $message = "Payment has been confirmed. Order will be processed as soon as payment is completed."; $state = "1";}
      if($state == Configuration::get('PS_OS_PAYMENT')){$title = "Payment Completed"; $message = "Payment has completed successfully. Your order will be processed."; $state = "1";}
      if($state == Configuration::get('ATOMICPAY_OS_EXPIRED')){$title = "Order Has Expired"; $message = "Payment invoice has expired and this order will not be processed. Please create a new order.";}
      if($state == Configuration::get('ATOMICPAY_OS_INVALID')){$title = "Invalid Payment"; $message = "Payment is not confirmed on network and this order will not be processed. Please create a new order.";}
      if($state == Configuration::get('ATOMICPAY_OS_UNDERPAID')){$title = "Payment Underpaid"; $message = "Payment received is less than this order's total payable amount. Please contact us for assistance.";}
      if($state == Configuration::get('ATOMICPAY_OS_PAID_AFTER_EXPIRY')){$title = "Payment Completed"; $message = "Payment is received after order has expired. Please contact us for assistance.";}


      $smarty->assign(array(
                            'state'         => $state,
                            'title'         => $title,
                            'message'         => $message,
                            'this_path'     => $this->_path,
                            'this_path_ssl' => Configuration::get('PS_FO_PROTOCOL').$_SERVER['HTTP_HOST'].__PS_BASE_URI__."modules/{$this->name}/"));

      return $this->display(__FILE__, 'payment_return.tpl');
    }

    public function hookInvoice($params) {
      global $smarty;

      $id_order = $params['id_order'];

      $getTransaction = $this->getTransaction($id_order);

      if($getTransaction['invoice_id'] === 0)
      {
          return;
      }

      $status = ucwords($getTransaction['status']);

      $smarty->assign(array(
                            'invoice_id'    => $getTransaction['invoice_id'],
                            'invoice_url'   => $getTransaction['invoice_url'],
                            'status'  => $status,
                            'this_page'     => $_SERVER['REQUEST_URI'],
                            'this_path'     => $this->_path,
                            'this_path_ssl' => Configuration::get('PS_FO_PROTOCOL').$_SERVER['HTTP_HOST'].__PS_BASE_URI__."modules/{$this->name}/"
                           ));
      return $this->display(__FILE__, 'invoice_block.tpl');
    }

    public function getTransaction($id_order) {
      $db = Db::getInstance();
      $result = array();
      $result = $db->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'atomicpay_transactions` WHERE `order_id` = ' . intval($id_order) . ';');
      if (count($result)>0) {
            return $result[0];
      } else {
         return array( 'invoice_id' => 0, 'status' =>'null');
      }
    }

    // Check if currency is supported by AtomicPay
    public function checkCurrency($cart)
    {
        $currency = Currency::getCurrencyInstance((int)$cart->id_currency);
        $currency_code = $currency->iso_code;

        $AccountID = Configuration::get('ATOMICPAY_API_ACCOUNTID');
        $AccountPrivateKey = Configuration::get('ATOMICPAY_API_PRIVATEKEY');

        $endpoint_url = "https://merchant.atomicpay.io/api/v1/currencies/$currency_code";
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

        if($code == "200"){ return true; }
        else{ return false; }

        return false;
    }

    public function getContent()
    {
      $output = null;

      if (Tools::isSubmit('submit'.$this->name))
      {
          /** @var array $values */
          $values = Tools::getAllValues();
          foreach ($values as $name => $value)
          {
              if (strpos($name, 'atomicpay_') !== false)
              {
                  if($name == "atomicpay_api_accountID"){$atm_accountID = $value;}
                  if($name == "atomicpay_api_privateKey"){$atm_privateKey = $value;}
                  if($name == "atomicpay_api_publicKey"){$atm_publicKey = $value;}
                  if($name == "atomicpay_transactionSpeed"){$atm_transactionSpeed = $value;}
              }
          }

          if($atm_accountID != "" && $atm_privateKey != "" && $atm_publicKey != "")
          {
              $atm_accountID = trim($atm_accountID);
              $atm_privateKey = trim($atm_privateKey);
              $atm_publicKey = trim($atm_publicKey);

              // Validate API Connection
              $endpoint_url = 'https://merchant.atomicpay.io/api/v1/authorization';

              $data_to_post = [
                'account_id' => $atm_accountID,
                'account_privateKey' => $atm_privateKey,
                'account_publicKey' => $atm_publicKey
              ];

              $options = [
                CURLOPT_URL        => $endpoint_url,
                CURLOPT_POST       => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => $data_to_post,
              ];

              $curl = curl_init();
              curl_setopt_array($curl, $options);
              $response = curl_exec($curl);
              curl_close($curl);

              $data = json_decode($response);
              $code = $data->code;

              if($code == "200")
              {
                  $message = $data->message;
                  Configuration::updateValue('ATOMICPAY_API_ACCOUNTID', $atm_accountID);
                  Configuration::updateValue('ATOMICPAY_API_PUBLICKEY', $atm_publicKey);
                  Configuration::updateValue('ATOMICPAY_API_PRIVATEKEY', $atm_privateKey);
                  Configuration::updateValue('ATOMICPAY_TRANSACTIONSPEED', $atm_transactionSpeed);

                  $message = "Settings updated. $message";
                  $output .= $this->displayConfirmation($this->l($message));
              }
              else
              {
                  $message = $data->message;
                  $message = "Settings not updated. Error: $message";
                  $output .= $this->displayError($this->l($message));
              }
          }
          else
          {
              Configuration::updateValue('ATOMICPAY_API_ACCOUNTID', '');
              $output .= $this->displayError($this->l('Settings not updated. Error: Please input the required fields'));
          }
      }

	$this->_setHeader();

      return $output.$this->_html.$this->displayForm();
    }

    private function _setHeader() {
      $this->_html .= '<div style="margin-bottom:15px;"><img src="../modules/atomicpay/atomicpay.png" style="float:left;margin-right:15px;" /><br />
                       <b>This module allows you to accept cryptocurrency payments via AtomicPay</b><br />
                       You must have a AtomicPay merchant account and API keys to use this module.<br />
                       It is free to <a href="https://merchant.atomicpay.io/beta-registration" target="_blank" /><b>sign-up for a AtomicPay Merchant Account</b></a>
                       </div>

						After registration, you may retrieve the Account ID and API keys by login to <a href="https://merchant.atomicpay.io/login" target="_blank" />AtomicPay Merchant Account</a>
						and go to <a href="https://merchant.atomicpay.io/apiIntegration" target="_blank" />API Integration page</a>.
                       <div style="clear:both;">&nbsp;</div>';
    }

    public function displayForm()
    {
        // Get default language
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $defaultCurrency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('AtomicPay Settings'),
                'icon' => 'icon-cogs'
            ),
            'submit' => array(
                'title' => $this->l('Save Setting'),
                'class' => 'btn btn-default pull-right'
            )
        );

        $options = array(
        array( 'id_option' => 1, 'name' => 'High Risk 1 Confirmation' ),
        array( 'id_option' => 2, 'name' => 'Medium Risk 2 Confirmations' ),
        array( 'id_option' => 3, 'name' => 'Low Risk 6 Confirmations' ),
        );

        $fields_form[0]['form']['input'] = array(
            array(
                'type' => 'text',
                'required' => true,
                'label' => 'Account ID',
                'name' => 'atomicpay_api_accountID'
            ),
            array(
                'type' => 'text',
                'required' => true,
                'label' => 'API Private Key',
                'name' => 'atomicpay_api_privateKey'
            ),
            array(
                'type' => 'text',
                'required' => true,
                'label' => 'API Public Key',
                'name' => 'atomicpay_api_publicKey'
            ),
            array(
                'type' => 'select',
                'required' => true,
                'label' => 'Transaction Speed',
                'desc' => 'The transaction speed determines how quickly an invoice payment is considered to be completed, at which you would fulfill and complete the order. Note: 1 confirmation may take up to 10 mins.',
                'name' => 'atomicpay_transactionSpeed',
                'options' => array(
                  'query' => $options,
                  'id' => 'id_option',
                  'name' => 'name'
                )
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
                array(
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                        '&token='.Tools::getAdminTokenLite('AdminModules'),
                ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current value
        $helper->fields_value['atomicpay_api_accountID'] = Configuration::get('ATOMICPAY_API_ACCOUNTID');
        $helper->fields_value['atomicpay_api_privateKey'] = Configuration::get('ATOMICPAY_API_PRIVATEKEY');
        $helper->fields_value['atomicpay_api_publicKey'] = Configuration::get('ATOMICPAY_API_PUBLICKEY');
        $helper->fields_value['atomicpay_transactionSpeed'] = Configuration::get('ATOMICPAY_TRANSACTIONSPEED');

        return $helper->generateForm($fields_form);
    }

    /**
     * Create order state
     *
     * @return boolean
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function installOrderState()
    {
        $states = array(
            array(
                'name' => 'ATOMICPAY_OS_WAITING',
                'color' => '#1c82a8',
                'title' => 'Awaiting for AtomicPay payment'
            ),
            array(
                'name' => 'ATOMICPAY_OS_PAID',
                'color' => '#14617e',
                'title' => 'Paid. Awaiting network confirmation'
            ),
            array(
                'name' => 'ATOMICPAY_OS_OVERPAID',
                'color' => '#14617e',
                'title' => 'Over Paid. Awaiting network confirmation'
            ),
            array(
                'name' => 'ATOMICPAY_OS_CONFIRMED',
                'color' => '#147e2d',
                'title' => 'Confirmed. Awaiting payment completion status'
            ),
            array(
                'name' => 'ATOMICPAY_OS_EXPIRED',
                'color' => '#7e1446',
                'title' => 'Invoice has expired. Do not process order'
            ),
            array(
                'name' => 'ATOMICPAY_OS_INVALID',
                'color' => '#7e1446',
                'title' => 'Payment is invalid. Do not process order'
            ),
            array(
                'name' => 'ATOMICPAY_OS_UNDERPAID',
                'color' => '#7e1446',
                'title' => 'Underpaid and expired. Contact customer for refund'
            ),
            array(
                'name' => 'ATOMICPAY_OS_PAID_AFTER_EXPIRY',
                'color' => '#7e1446',
                'title' => 'Payment received after invoice expired'
            ),
        );

        $allOrderStates = OrderState::getOrderStates($this->context->language->id);
        $allOrderStatesOrdered = [];

        foreach ($allOrderStates as $orderState) {
            $allOrderStatesOrdered[$orderState['name']] = $orderState;
        }

        foreach ($states as $state) {
            if (!Configuration::get($state['name'])
                || !Validate::isLoadedObject(new OrderState(Configuration::get($state['name'])))) {
                if (!isset($allOrderStatesOrdered[$state['title']])) {
                    $orderState = new OrderState();
                    $orderState->name = array();
                    foreach (Language::getLanguages() as $language) {
                        $orderState->name[$language['id_lang']] = $state['title'];
                    }
                    $orderState->send_email = false;
                    $orderState->color = $state['color'];
                    $orderState->hidden = false;
                    $orderState->delivery = false;
                    $orderState->logable = false;
                    $orderState->invoice = false;
                    if ($orderState->add()) {
                        $source = _PS_MODULE_DIR_.'atomicpay/logo_os.png';
                        $destination = _PS_ROOT_DIR_.'/img/os/'.(int) $orderState->id.'.gif';
                        copy($source, $destination);
                    }
                } else {
                    $orderState = new OrderState($allOrderStatesOrdered[$state['title']]['id_order_state']);
                }

                Configuration::updateValue($state['name'], (int) $orderState->id);
            }
        }

        return true;
    }
}
