<?php
class WC_Molpay_Gateway extends WC_Payment_Gateway
{
    public $title;
    public $ordering_plugin;
    public $payment_title;
    public $description;
    public $merchant_id;
    public $verify_key;
    public $secret_key;
    public $account_type;
    public $recurring;
    public $extend_vcode;
    public $url;
    public $inquiry_url;
    public $payment_titles;
    protected $logger;
    protected $log_context;

    // Constructor method
    public function __construct()
    {
        global $woocommerce, $post;

        $this->id                 = 'wcmolpay';
        $this->icon = plugins_url('images/logo_E2Pay_Fiuu_small.png', __FILE__);
        $this->has_fields = false;
        $this->method_title       = __('E2Pay', 'wcmolpay');
        $this->method_description = __('Proceed payment via E2Pay Normal Integration Plugin', 'wcmolpay');
        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Define user setting variables.
        $this->title = $this->settings['title'];
        $this->ordering_plugin = $this->get_option('ordering_plugin');
        $this->payment_title = $this->settings['payment_title'];
        $this->description = $this->settings['description'];
        $this->merchant_id = $this->settings['merchant_id'];
        $this->verify_key = $this->settings['verify_key'];
        $this->secret_key = $this->settings['secret_key'];
        $this->account_type = $this->settings['account_type'];

        // Define hostname based on account_type
        $this->url = ($this->get_option('account_type')=='1') ? "https://pg.e2pay.co.id/" : "https://pg-uat.e2pay.co.id/" ;
        $this->inquiry_url = ($this->get_option('account_type')=='1') ? "https://api.e2pay.co.id/" : "https://api-uat.e2pay.co.id/" ;

        // Logger
        $this->logger = wc_get_logger();
        $this->log_context = ['source' => $this->id];

        // Actions.
        add_action('valid_molpay_request_returnurl', array(&$this, 'check_molpay_response_returnurl'));
        add_action('valid_molpay_request_callback', array(&$this, 'check_molpay_response_callback'));
        add_action('valid_molpay_request_notification', array(&$this, 'check_molpay_response_notification'));
        add_action('woocommerce_receipt_molpay', array(&$this, 'receipt_page'));

        if (isset($_GET['molpay_redirect']) && $_GET['molpay_redirect']) {
            $order_id = "";
            if (isset($_GET['key']) && $_GET['key']) {
                $order_id = wc_get_order_id_by_order_key($_GET['key']);
                do_action("woocommerce_receipt_molpay", $order_id);
                exit();
            }
        }
        $_GET['molpay_redirect'] = "";

        //save setting configuration
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Payment listener/API hook
        add_action('woocommerce_api_wc_molpay_gateway', array($this, 'check_ipn_response'));

        // Checking if merchant_id is not empty.
        $this->merchant_id == '' ? add_action('admin_notices', array(&$this, 'merchant_id_missing_message')) : '';

        // Checking if verify_key is not empty.
        $this->verify_key == '' ? add_action('admin_notices', array(&$this, 'verify_key_missing_message')) : '';

        // Checking if secret_key is not empty
        $this->secret_key == '' ? add_action('admin_notices', array(&$this, 'secret_key_missing_message')) : '';

        // Checking if account_type is not empty
        $this->account_type == '' ? add_action('admin_notices', array(&$this, 'account_type_missing_message')) : '';

        // Create list of Payment title of the channel
        $this->payment_titles = array(
            // 'credit21'            => 'Credit Card/ Debit Card',
            'CIMB_NIAGA'                    => "CIMB_NIAGA",
            'BRI'                           => "BRI",
            'BCA'                           => "BCA",
            'e2Pay_CIMBOctoClicks_IB'       => "e2Pay_CIMBOctoClicks_IB",
            'e2Pay_DANA'                    => "e2Pay_DANA",
            'e2Pay_LINKAJA_APPLINK'         => "e2Pay_LINKAJA_APPLINK",
            'e2Pay_LINKAJA_WCO'             => "e2Pay_LINKAJA_WCO",
            'e2Pay_SHOPEEPAY_JUMPAPP'       => "e2Pay_SHOPEEPAY_JUMPAPP",
            'e2Pay_OVO'                     => "e2Pay_OVO",
            'e2Pay_GOPAY'                   => "e2Pay_GOPAY",
            'e2Pay_CIMB_OctoPay'            => "e2Pay_CIMB_OctoPay",
            'e2Pay_Alipay_QR'               => "e2Pay_Alipay_QR",
            'e2Pay_WeChatPay_QR'            => "e2Pay_WeChatPay_QR",
            'e2Pay_Kredivo_FN'              => "e2Pay_Kredivo_FN",
            'e2Pay_Indodana_FN'             => "e2Pay_Indodana_FN",
            'e2Pay_PERMATA_VA'              => "e2Pay_PERMATA_VA",
            'e2Pay_BNI_VA'                  => "e2Pay_BNI_VA",
            'e2Pay_CIMB_VA'                 => "e2Pay_CIMB_VA",
            'e2Pay_BCA_VA'                  => "e2Pay_BCA_VA",
            'e2Pay_BRI_VA'                  => "e2Pay_BRI_VA",
            'e2Pay_MANDIRI_VA'              => "e2Pay_MANDIRI_VA",
            'e2Pay_BSI_VA'                  => "e2Pay_BSI_VA",
            'e2Pay_Indomaret'               => "e2Pay_Indomaret",
            'e2Pay_Alfamart'                => "e2Pay_Alfamart",
            'e2Pay_CIMB_QRIS'               => "e2Pay_CIMB_QRIS",
            'e2Pay_MBayar_QR'               => "e2Pay_MBayar_QR",
            'e2Pay_SHOPEEPAY_QRIS'          => "e2Pay_SHOPEEPAY_QRIS",
        );
    }

    /**
     * Checking if this gateway is enabled and available in the user's country.
     *
     * @return bool
     */
    public function is_valid_for_use()
    {
        if (!in_array(get_woocommerce_currency(), array('MYR'))) {
            return false;
        }
        return true;
    }

    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis.
     *
     */
    public function admin_options()
    {
?>
        <h3><?php _e('E2Pay', 'wcmolpay'); ?></h3>
        <p><?php _e('E2Pay works by sending the user to E2Pay to enter their payment information.', 'wcmolpay'); ?></p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table><!--/.form-table-->
<?php
    }

    /**
     * Gateway Settings Form Fields.
     * 
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'wcmolpay' ),
                'type' => 'checkbox',
                'label' => __( 'Enable E2Pay', 'wcmolpay' ),
                'default' => 'yes'
            ),
            'ordering_plugin' => array(
                'title' => __( '<p style="color:red;">Installed Ordering Plugins</p>', 'wcmolpay' ),
                'type' => 'select',
                'label' => __( ' ', 'wcmolpay' ),
                'default' => 'Sequential Order Numbers',
                'options' => array(
                    '0' => __( 'Not install any ordering plugin', 'wcmolpay'),
                    '1' => __( 'Sequential Order Numbers', 'wcmolpay' ),
                    '2' => __( 'Sequential Order Numbers Pro', 'wcmolpay' )
                ),
                'description' => __( 'Please select correct ordering plugin as it will affect your order result!!', 'wcmolpay' ),
                'desc_tip' => true,
            ),
            'title' => array(
                'title' => __( 'Title', 'wcmolpay' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'wcmolpay' ),
                'default' => __( 'E2Pay', 'wcmolpay' ),
                'desc_tip' => true,
            ),
            'payment_title' => array(
                'title' => __( 'Payment Title', 'wcmolpay'),
                'type' => 'checkbox',
                'label' => __( 'Showing channel instead of gateway title after payment.'),
                'description' => __( 'This controls the payment method which the user sees after payment.', 'wcmolpay' ),
                'default' => 'no',
                'desc_tip' => true
            ),
            'description' => array(
                'title' => __( 'Description', 'wcmolpay' ),
                'type' => 'textarea',
                'description' => __( 'This controls the description which the user sees during checkout.', 'wcmolpay' ),
                'default' => __( 'Pay with E2Pay', 'wcmolpay' ),
                'desc_tip' => true,
            ),
            'merchant_id' => array(
                'title' => __( 'Merchant ID', 'wcmolpay' ),
                'type' => 'text',
                'description' => __( 'Please enter your E2Pay Merchant ID.', 'wcmolpay' ) . ' ' . sprintf( __( 'You can to get this information in: %sE2Pay Account%s.', 'wcmolpay' ), '<a href="https://portal.e2pay.co.id/" target="_blank">', '</a>' ),
                'default' => ''
            ),
            'verify_key' => array(
                'title' => __( 'Verify Key', 'wcmolpay' ),
                'type' => 'text',
                'description' => __( 'Please enter your E2Pay Verify Key.', 'wcmolpay' ) . ' ' . sprintf( __( 'You can to get this information in: %sE2Pay Account%s.', 'wcmolpay' ), '<a href="https://portal.e2pay.co.id/" target="_blank">', '</a>' ),
                'default' => ''
            ),
            'secret_key' => array(
                'title' => __( 'Secret Key', 'wcmolpay' ),
                'type' => 'text',
                'description' => __( 'Please enter your E2Pay Secret Key.', 'wcmolpay' ) . ' ' . sprintf( __( 'You can to get this information in: %sE2Pay Account%s.', 'wcmolpay' ), '<a href="https://portal.e2pay.co.id/" target="_blank">', '</a>' ),
                'default' => ''
            ),
            'account_type' => array(
                'title' => __( 'Account Type', 'wcmolpay' ),
                'type' => 'select',
                'label' => __( ' ', 'wcmolpay' ),
                'default' => 'PRODUCTION',
                'options' => array(
                    '1'  => __('PRODUCTION', 'wcmolpay' ),
                    '2' => __( 'SANDBOX', 'wcmolpay' )
                    )
            ),
        );
    }

    /**
     * Generate the form.
     *
     * @param mixed $order_id
     * @return string
     */
    public function generate_form($order_id)
    {
        $order = new WC_Order($order_id);
        $pay_url = $this->url . 'MOLPay/pay/' . $this->merchant_id;
        $total = $order->get_total();
        $order_number = $order->get_order_number();
        $vcode = md5($order->get_total().$this->merchant_id.$order_number.$this->verify_key);

        if (sizeof($order->get_items()) > 0)
            foreach ($order->get_items() as $item)
                if ($item['qty'])
                    $item_names[] = $item['name'] . ' x ' . $item['qty'];

        $desc = sprintf(__('Order %s', 'woocommerce'), $order_number) . " - " . implode(', ', $item_names);

        $molpay_args = array(
            'vcode' => $vcode,
            'orderid' => $order_number,
            'amount' => $total,
            'bill_name' => $order->get_billing_first_name() . " " . $order->get_billing_last_name(),
            'bill_mobile' => $order->get_billing_phone(),
            'bill_email' => $order->get_billing_email(),
            'bill_desc' => $desc,
            'country' => $order->get_billing_country(),
            'cur' => get_woocommerce_currency(),
            'returnurl' => add_query_arg('wc-api', 'WC_Molpay_Gateway', home_url('/'))
        );

        $molpay_args_array = array();

        foreach ($molpay_args as $key => $value) {
            $molpay_args_array[] = "<input type='hidden' name='" . $key . "' value='" . $value . "' />";
        }

        return "<form action='" . $pay_url . "/' method='post' id='molpay_payment_form' name='molpay_payment_form'>"
            . implode('', $molpay_args_array)
            . "<script>document.molpay_payment_form.submit();</script>"
            . "</form>";
    }

    /**
     * Order error button.
     *
     * @param  object $order Order data.
     * @return string Error message and cancel button.
     */
    protected function molpay_order_error($order)
    {
        $html = '<p>' . __('An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'wcmolpay') . '</p>';
        $html .= '<a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Click to try again', 'wcmolpay') . '</a>';
        return $html;
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);
        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true) . "&molpay_redirect=1"
        );
    }

    /**
     * Output for the order received page.
     * 
     * @param  object $order Order data.
     */
    public function receipt_page($order)
    {
        echo $this->generate_form($order);
    }

    /**
     * Check for E2Pay Response
     *
     * @access public
     * @return void
     */
    function check_ipn_response()
    {
        @ob_clean();

        if (!(isset($_POST['nbcb']))) {
            do_action("valid_molpay_request_returnurl", $_POST);
        } else if ($_POST['nbcb'] == '1') {
            do_action("valid_molpay_request_callback", $_POST);
        } else if ($_POST['nbcb'] == '2') {
            do_action("valid_molpay_request_notification", $_POST);
        } else {
            $error_message = "E2Pay Request Failure";
            $this->logger->error($error_message, $this->log_context);
            wp_die($error_message);
        }
    }

    /**
     * This part is handle return response
     * 
     * @global mixed $woocommerce
     */
    function check_molpay_response_returnurl()
    {
        global $woocommerce;

        $verifyresult = $this->verifySkey($_POST);
        $status = $_POST['status'];
        if (!$verifyresult)
            $status = "-1";

        $WCOrderId = $this->get_WCOrderIdByOrderId($_POST['orderid']);
        if (empty($WCOrderId)) {
            $error_message = "Order not found";
            $this->logger->error($error_message, $this->log_context);
            wp_die($error_message);
        }

        $order = new WC_Order($WCOrderId);
        $referer = "<br>Referer: ReturnURL";
        $getStatus =  $order->get_status();
        if (!in_array($getStatus, array('processing', 'completed'))) {
            if ($status == "11") {
                $referer .= " (Inquiry)";
                $status = $this->inquiry_status($_POST['tranID'], $_POST['amount'], $_POST['domain']);
            }
            $this->update_Cart_by_Status($WCOrderId, $status, $_POST['tranID'], $referer, $_POST['channel']);
            if (in_array($status, array("00", "22"))) {
                wp_redirect($order->get_checkout_order_received_url());
            } else {
                wp_redirect($order->get_cancel_order_url());
            }
        } else {
            wp_redirect($order->get_checkout_order_received_url());
        }
        $this->acknowledgeResponse($_POST);
        exit;
    }

    /**
     * This part is handle notification response
     * 
     * @global mixed $woocommerce
     */
    function check_molpay_response_notification()
    {
        global $woocommerce;
        $verifyresult = $this->verifySkey($_POST);
        $status = $_POST['status'];
        if (!$verifyresult)
            $status = "-1";

        $WCOrderId = $this->get_WCOrderIdByOrderId($_POST['orderid']);
        $referer = "<br>Referer: NotificationURL";
        $this->update_Cart_by_Status($WCOrderId, $status, $_POST['tranID'], $referer, $_POST['channel']);
        $this->acknowledgeResponse($_POST);
    }

    /**
     * This part is handle callback response
     * 
     * @global mixed $woocommerce
     */
    function check_molpay_response_callback()
    {
        global $woocommerce;
        $verifyresult = $this->verifySkey($_POST);
        $status = $_POST['status'];
        if (!$verifyresult)
            $status = "-1";

        $WCOrderId = $this->get_WCOrderIdByOrderId($_POST['orderid']);
        $referer = "<br>Referer: CallbackURL";
        $this->update_Cart_by_Status($WCOrderId, $status, $_POST['tranID'], $referer, $_POST['channel']);
        $this->acknowledgeResponse($_POST);
    }

    /**
     * Adds error message when not configured the merchant_id.
     * 
     */
    public function merchant_id_missing_message()
    {
        $message = '<div class="error">';
        $message .= '<p>' . sprintf(__('<strong>Gateway Disabled</strong> You should fill in your Merchant ID in E2Pay. %sClick here to configure!%s', 'wcmolpay'), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=wc_molpay_gateway">', '</a>') . '</p>';
        $message .= '</div>';
        echo $message;
    }

    /**
     * Adds error message when not configured the verify_key.
     * 
     */
    public function verify_key_missing_message()
    {
        $message = '<div class="error">';
        $message .= '<p>' . sprintf(__('<strong>Gateway Disabled</strong> You should fill in your Verify Key in E2Pay. %sClick here to configure!%s', 'wcmolpay'), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=wc_molpay_gateway">', '</a>') . '</p>';
        $message .= '</div>';
        echo $message;
    }

    /**
     * Adds error message when not configured the secret_key.
     * 
     */
    public function secret_key_missing_message()
    {
        $message = '<div class="error">';
        $message .= '<p>' . sprintf(__('<strong>Gateway Disabled</strong> You should fill in your Secret Key in E2Pay. %sClick here to configure!%s', 'wcmolpay'), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=wc_molpay_gateway">', '</a>') . '</p>';
        $message .= '</div>';
        echo $message;
    }

    /**
     * Adds error message when not configured the account_type.
     * 
     */
    public function account_type_missing_message()
    {
        $message = '<div class="error">';
        $message .= '<p>' . sprintf(__('<strong>Gateway Disabled</strong> Select account type in E2Pay. %sClick here to configure!%s', 'wcmolpay'), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=wc_molpay_gateway">', '</a>') . '</p>';
        $message .= '</div>';
        echo $message;
    }

    /**
     * Inquiry transaction status
     *
     * @param int $tranID
     * @param double $amount
     * @param string $domain
     * @return status
     */
    public function inquiry_status($tranID, $amount, $domain)
    {
        $verify_key = $this->verify_key;
        $requestUrl = $this->inquiry_url . "MOLPay/q_by_tid.php";
        $request_param = array(
            "amount"    => number_format($amount, 2),
            "txID"      => intval($tranID),
            "domain"    => urlencode($domain),
            "skey"      => urlencode(md5(intval($tranID) . $domain . $verify_key . number_format($amount, 2)))
        );
        $post_data = http_build_query($request_param);
        $header[] = "Content-Type: application/x-www-form-urlencoded";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $response = trim($response);
        $temp = explode("\n", $response);
        foreach ($temp as $value) {
            $array = explode(':', $value);
            $key = trim($array[0], "[]");
            $result[$key] = trim($array[1]);
        }
        $verify = md5($result['Amount'] . $this->secret_key . $result['Domain'] . $result['TranID'] . $result['StatCode']);
        if ($verify != $result['VrfKey']) {
            $result['StatCode'] = "99";
        }
        return $result['StatCode'];
    }

    /**
     * Update Cart based on E2Pay status
     * 
     * @global mixed $woocommerce
     * @param int $order_id
     * @param int $MOLPay_status
     * @param int $tranID
     * @param string $referer
     */
    public function update_Cart_by_Status($orderid, $MOLPay_status, $tranID, $referer, $channel)
    {
        global $woocommerce;

        $order = new WC_Order($orderid);
        switch ($MOLPay_status) {
            case '00':
                $M_status = 'SUCCESSFUL';
                break;
            case '22':
                $M_status = 'PENDING';
                $W_status = 'pending';
                break;
            case '11':
                $M_status = 'FAILED';
                $W_status = 'failed';
                break;
            default:
                $M_status = 'Invalid Transaction';
                $W_status = 'on-hold';
                break;
        }

        $getStatus = $order->get_status();
        if (!in_array($getStatus, array('processing', 'completed'))) {
            if ($MOLPay_status == "00") {
                $order->payment_complete();
            } else {
                $order->update_status($W_status, sprintf(__('Payment %s via E2Pay.', 'woocommerce'), $tranID));
            }
            if ($this->payment_title == 'yes') {
                $paytitle = $this->payment_titles[strtolower($channel)];
                $order->set_payment_method_title($paytitle);
                $order->save();
            }
            $order->add_order_note('E2Pay Payment Status: ' . $M_status . '<br>Transaction ID: ' . $tranID . $referer);
        } else {
            echo "Order completed";
            exit;
        }
    }

    /**
     * Obtain the original order id based using the returned transaction order id
     * 
     * @global mixed $woocommerce
     * @param int $orderid
     * @return int $real_order_id
     */
    public function get_WCOrderIdByOrderId($orderid)
    {
        switch ($this->ordering_plugin) {
            case '1':
                $args = array(
                    'limit' => 1,
                    array(
                        'key' => '_order_number',
                        'value' => $orderid,
                        'compare' => '='
                    )
                );
                $orders = wc_get_orders($args);
                $data = json_decode($orders[0], true);
                $WCOrderId = $data['id'];
                break;
            case '2':
                $WCOrderId = wc_seq_order_number_pro()->find_order_by_order_number($orderid);
                break;
            case '0':
            default:
                $WCOrderId = $orderid;
                break;
        }
        return $WCOrderId;
    }

    /**
     * Acknowledge transaction result
     * 
     * @global mixed $woocommerce
     * @param array $response
     */
    public function acknowledgeResponse($response)
    {
        if ($isset($response['nbcb']) && response['nbcb'] == '1') {
            echo "CBTOKEN:MPSTATOK";
            exit;
        } else {
            $response['treq'] = '1'; // Additional parameter for IPN
            foreach ($response as $k => $v) {
                $postData[] = $k . "=" . $v;
            }
            $postdata = implode("&", $postData);
            $url = $this->url . "MOLPay/API/chkstat/returnipn.php";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            $result = curl_exec($ch);
            curl_close($ch);
        }
    }

    /**
     * To verify transaction result using merchant secret key setting.
     * 
     * @global mixed $woocommerce
     * @param  array $response
     * @return boolean verifyresult
     */
    public function verifySkey($response)
    {

        $amount = $response['amount'];
        $orderid = $response['orderid'];
        $tranID = $response['tranID'];
        $status = $response['status'];
        $domain = $response['domain'];
        $currency = $response['currency'];
        $appcode = $response['appcode'];
        $paydate = $response['paydate'];
        $skey = $response['skey'];
        $vkey = $this->secret_key;

        $key0 = md5($tranID . $orderid . $status . $domain . $amount . $currency);
        $key1 = md5($paydate . $domain . $key0 . $appcode . $vkey);
        if ($skey != $key1)
            return false;
        else
            return true;
    }
}
?>