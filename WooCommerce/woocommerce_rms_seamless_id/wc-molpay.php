<?php
/**
 * Fiuu WooCommerce Shopping Cart Plugin
 * 
 * @author Fiuu Technical Team <technical@fiuu.com>
 * @version 6.1.4
 * @example For callback : http://shoppingcarturl/?wc-api=WC_Molpay_Gateway
 * @example For notification : http://shoppingcarturl/?wc-api=WC_Molpay_Gateway
 */

/**
 * Plugin Name: WooCommerce E2Pay Services Seamless
 * Plugin URI: https://github.com/FiuuPayment/Shopping-Cart-Plugins-Fiuu_WooCommerce
 * Description: WooCommerce Fiuu | The leading payment gateway in South East Asia Grow your business with Fiuu Services payment solutions & free features: Physical Payment at 7-Eleven, Seamless Checkout, Tokenization, Loyalty Program and more for WooCommerce
 * Author: Fiuu Services Tech Team
 * Author URI: https://fiuu.com/
 * Version: 6.1.4
 * License: MIT
 * Text Domain: wcmolpay
 * Domain Path: /languages/
 * For callback : http://shoppingcarturl/?wc-api=WC_Molpay_Gateway
 * For notification : http://shoppingcarturl/?wc-api=WC_Molpay_Gateway
 * Invalid Transaction maybe is because vkey not found / skey wrong generated
 */

/**
 * If WooCommerce plugin is not available
 * 
 */
function wcmolpay_woocommerce_fallback_notice() {
    $message = '<div class="error">';
    $message .= '<p>' . __( 'WooCommerce E2Pay Gateway depends on the last version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work!' , 'wcmolpay' ) . '</p>';
    $message .= '</div>';
    echo $message;
}

//Load the function
add_action( 'plugins_loaded', 'wcmolpay_gateway_load', 0 );

/**
 * Load E2Pay gateway plugin function
 * 
 * @return mixed
 */
function wcmolpay_gateway_load() {
    if ( !class_exists( 'WC_Payment_Gateway' ) ) {
        add_action( 'admin_notices', 'wcmolpay_woocommerce_fallback_notice' );
        return;
    }

    //Load language
    load_plugin_textdomain( 'wcmolpay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    add_filter( 'woocommerce_payment_gateways', 'wcmolpay_add_gateway' );

    /**
     * Add E2Pay gateway to ensure WooCommerce can load it
     * 
     * @param array $methods
     * @return array
     */
    function wcmolpay_add_gateway( $methods ) {
        $methods[] = 'WC_Molpay_Gateway';
        return $methods;
    }

    /**
     * Define the E2Pay gateway
     * 
     */
    class WC_Molpay_Gateway extends WC_Payment_Gateway {

        protected $logger;
        protected $log_context;

        /**
         * Construct the E2Pay gateway class
         * 
         * @global mixed $woocommerce
         */
        public function __construct() {
            global $woocommerce;

            $this->id = 'molpay';
            $this->icon = plugins_url( 'images/logo_E2Pay_Fiuu_small.png', __FILE__ );
            $this->has_fields = false;
            $this->method_title = __( 'E2Pay', 'wcmolpay' );
            $this->method_description = __( 'Proceed payment via E2Pay Seamless Integration Plugin', 'woocommerce' );

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
            $this->url = ($this->get_option('account_type')=='1') ? "https://pg.e2pay.co.id" : "https://pg-sandbox.e2pay.co.id" ;
            $this->inquiry_url = ($this->get_option('account_type')=='1') ? "https://api.e2pay.co.id/" : "https://api-sandbox.e2pay.co.id/" ;
            
            // Define channel setting variables            
            $this->CIMB_NIAGA = ($this->get_option('CIMB_NIAGA')=='yes' ? true : false);
            $this->BRI = ($this->get_option('BRI')=='yes' ? true : false);
            $this->BCA = ($this->get_option('BCA')=='yes' ? true : false);
            $this->e2Pay_CIMBOctoClicks_IB = ($this->get_option('e2Pay_CIMBOctoClicks_IB')=='yes' ? true : false);
            $this->e2Pay_DANA = ($this->get_option('e2Pay_DANA')=='yes' ? true : false);
            $this->e2Pay_LINKAJA_APPLINK = ($this->get_option('e2Pay_LINKAJA_APPLINK')=='yes' ? true : false);
            $this->e2Pay_LINKAJA_WCO = ($this->get_option('e2Pay_LINKAJA_WCO')=='yes' ? true : false);
            $this->e2Pay_SHOPEEPAY_JUMPAPP = ($this->get_option('e2Pay_SHOPEEPAY_JUMPAPP')=='yes' ? true : false);
            $this->e2Pay_OVO = ($this->get_option('e2Pay_OVO')=='yes' ? true : false);
            $this->e2Pay_GOPAY = ($this->get_option('e2Pay_GOPAY')=='yes' ? true : false);
            $this->e2Pay_CIMB_OctoPay = ($this->get_option('e2Pay_CIMB_OctoPay')=='yes' ? true : false);
            $this->e2Pay_Alipay_QR = ($this->get_option('e2Pay_Alipay_QR')=='yes' ? true : false);
            $this->e2Pay_WeChatPay_QR = ($this->get_option('e2Pay_WeChatPay_QR')=='yes' ? true : false);
            $this->e2Pay_Kredivo_FN = ($this->get_option('e2Pay_Kredivo_FN')=='yes' ? true : false);
            $this->e2Pay_Indodana_FN = ($this->get_option('e2Pay_Indodana_FN')=='yes' ? true : false);

            $this->e2Pay_PERMATA_VA = ($this->get_option('e2Pay_PERMATA_VA')=='yes' ? true : false);
            $this->e2Pay_BNI_VA = ($this->get_option('e2Pay_BNI_VA')=='yes' ? true : false);
            $this->e2Pay_CIMB_VA = ($this->get_option('e2Pay_CIMB_VA')=='yes' ? true : false);
            $this->e2Pay_BCA_VA = ($this->get_option('e2Pay_BCA_VA')=='yes' ? true : false);
            $this->e2Pay_BRI_VA = ($this->get_option('e2Pay_BRI_VA')=='yes' ? true : false);
            $this->e2Pay_MANDIRI_VA = ($this->get_option('e2Pay_MANDIRI_VA')=='yes' ? true : false);
            $this->e2Pay_BSI_VA = ($this->get_option('e2Pay_BSI_VA')=='yes' ? true : false);
            $this->e2Pay_Indomaret = ($this->get_option('e2Pay_Indomaret')=='yes' ? true : false);
            $this->e2Pay_Alfamart = ($this->get_option('e2Pay_Alfamart')=='yes' ? true : false);
            $this->e2Pay_CIMB_QRIS = ($this->get_option('e2Pay_CIMB_QRIS')=='yes' ? true : false);
            $this->e2Pay_MBayar_QR = ($this->get_option('e2Pay_MBayar_QR')=='yes' ? true : false);
            $this->e2Pay_SHOPEEPAY_QRIS = ($this->get_option('e2Pay_SHOPEEPAY_QRIS')=='yes' ? true : false);

            // Transaction Type for Credit Channel
            $this->credit_tcctype = ($this->get_option('credit_tcctype')=='SALS' ? 'SALS' : 'AUTH');

            // Logger
            $this->logger = wc_get_logger();
            $this->log_context = ['source' => $this->id];

            // Actions.
            add_action( 'valid_molpay_request_returnurl', array( &$this, 'check_molpay_response_returnurl' ) );
            add_action( 'valid_molpay_request_callback', array( &$this, 'check_molpay_response_callback' ) );
            add_action( 'valid_molpay_request_notification', array( &$this, 'check_molpay_response_notification' ) );
            add_action( 'woocommerce_receipt_molpay', array( &$this, 'receipt_page' ) );
            
            //save setting configuration
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                        
            // Payment listener/API hook
            add_action( 'woocommerce_api_wc_molpay_gateway', array( $this, 'check_ipn_response' ) );
            
            // Checking if merchant_id is not empty.
            $this->merchant_id == '' ? add_action( 'admin_notices', array( &$this, 'merchant_id_missing_message' ) ) : '';

            // Checking if verify_key is not empty.
            $this->verify_key == '' ? add_action( 'admin_notices', array( &$this, 'verify_key_missing_message' ) ) : '';
            
            // Checking if secret_key is not empty.
            $this->secret_key == '' ? add_action( 'admin_notices', array( &$this, 'secret_key_missing_message' ) ) : '';
            
            // Checking if account_type is not empty.
            $this->account_type == '' ? add_action( 'admin_notices', array( &$this, 'account_type_missing_message' ) ) : '';
        }

        /**
         * Checking if this gateway is enabled and available in the user's country.
         *
         * @return bool
         */
        public function is_valid_for_use() {
            if ( !in_array( get_woocommerce_currency() , array( 'MYR', 'IDR' ) ) ) {
                return false;
            }
            return true;
        }

        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis.
         *
         */
        public function admin_options() {
            ?>
            <h3><?php _e( 'E2Pay', 'wcmolpay' ); ?></h3>
            <p><?php _e( 'E2Pay works by sending the user to E2Pay to enter their payment information.', 'wcmolpay' ); ?></p>
            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table><!--/.form-table-->
            <?php
        }

        /**
         * Gateway Settings Form Fields.
         * 
         */
        public function init_form_fields() {
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
                    'default' => '0',
                    'options' => array(
                        '0' => __( 'Not install any ordering plugin', 'wcmolpay'),
                        '1' => __( 'Sequential Order Numbers', 'wcmolpay' ),
                        '2' => __( 'Sequential Order Numbers Pro', 'wcmolpay' ),
                        '3' => __( 'Advanced Order Numbers', 'wcmolpay' ),
                        '4' => __( 'Custom Order Numbers', 'wcmolpay' )
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
                    'default' => __( 'E2Pay', 'wcmolpay' ),
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
                'channel' => array(
                    'title'         => 'Channel to be Enabled',
                    'type'          => 'title',
                    'description'   => '',
                ),

                // Credit Card
                'CIMB_NIAGA' => array(
                    'title' => __( 'CIMB NIAGA CC', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'BRI' => array(
                    'title' => __( 'BRI CC', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'BCA' => array(
                    'title' => __( 'BCA CC', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                // Internet Banking
                'e2Pay_CIMBOctoClicks_IB' => array(
                    'title' => __( 'CIMB Octo Clicks IB', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                // E-Wallet
                'e2Pay_DANA' => array(
                    'title' => __( 'DANA', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_LINKAJA_APPLINK' => array(
                    'title' => __( 'LINKAJA Applink', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_LINKAJA_WCO' => array(
                    'title' => __( 'LINKAJA Web Checkout', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_SHOPEEPAY_JUMPAPP' => array(
                    'title' => __( 'SHOPEEPAY', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_OVO' => array(
                    'title' => __( 'OVO', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_GOPAY' => array(
                    'title' => __( 'GoPay', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_CIMB_OctoPay' => array(
                    'title' => __( 'CIMB Octo Pay', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_Alipay_QR' => array(
                    'title' => __( 'AliPay QR', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_WeChatPay_QR' => array(
                    'title' => __( 'WeChatPay QR', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                
                // BNPL
                'e2Pay_Kredivo_FN' => array(
                    'title' => __( 'Kredivo', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_Indodana_FN' => array(
                    'title' => __( 'Indodana', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                // VA
                'e2Pay_PERMATA_VA' => array(
                    'title' => __( 'PERMATA VA', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_BNI_VA' => array(
                    'title' => __( 'BNI VA', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_CIMB_VA' => array(
                    'title' => __( 'CIMB VA', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_BCA_VA' => array(
                    'title' => __( 'BCA VA', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_BRI_VA' => array(
                    'title' => __( 'BRI VA', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_MANDIRI_VA' => array(
                    'title' => __( 'MANDIRI VA', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_BSI_VA' => array(
                    'title' => __( 'BSI VA', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),

                // Convenience Store
                'e2Pay_Indomaret' => array(
                    'title' => __( 'Indomaret', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_Alfamart' => array(
                    'title' => __( 'Alfamart', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),

                // QRIS
                'e2Pay_CIMB_QRIS' => array(
                    'title' => __( 'CIMB QRIS', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_MBayar_QR' => array(
                    'title' => __( 'M-Bayar QRIS', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                'e2Pay_SHOPEEPAY_QRIS' => array(
                    'title' => __( 'ShopeePay QRIS', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'                
                ),
                
                'tcctype' => array(
                    'title'         => 'Transaction Type for Credit Card / Debit Card Channel',
                    'type'          => 'title',
                    'description'   => '',
                ),
                'credit_tcctype' => array(
                    'title' => __( 'Credit Card/ Debit Card', 'wcmolpay' ),
                    'type' => 'select',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'SALS',
                    'options' => array(
                        'SALS'  => __('SALS', 'wcmolpay' ),
                        'AUTH' => __( 'AUTH', 'wcmolpay' )
                    ),
                's' => array(
                    'title' => __( 'Credit Card/ Debit Card', 'wcmolpay' ),
                    'type' => 'select',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'SALS',
                    'options' => array(
                        'SALS'  => __('SALS', 'wcmolpay' ),
                        'AUTH' => __( 'AUTH', 'wcmolpay' )
                        )
                )
                )
            );
        }

        /**
         * Generate the form.
         *
         * @param mixed $order_id
         * @return string
         */
        public function generate_form( $order_id ) {
            $order = new WC_Order( $order_id );
            $pay_url = $this->url.'/MOLPay/pay/'.$this->merchant_id;
            $total = $order->get_total();
            $order_number = $order->get_order_number();
            $vcode = md5($order->get_total().$this->merchant_id.$order_number.$this->verify_key);
            
            if ( sizeof( $order->get_items() ) > 0 ) 
                foreach ( $order->get_items() as $item )
                    if ( $item['qty'] )
                        $item_names[] = $item['name'] . ' x ' . $item['qty'];

            $desc = sprintf( __( 'Order %s' , 'woocommerce'), $order_number ) . " - " . implode( ', ', $item_names );
                        
            $molpay_args = array(
                'vcode' => $vcode,
                'orderid' => $order_number,
                'amount' => $total,
                'bill_name' => $order->get_billing_first_name()." ".$order->get_billing_last_name(),
                'bill_mobile' => $order->get_billing_phone(),
                'bill_email' => $order->get_billing_email(),
                'bill_desc' => $desc,
                'country' => $order->get_billing_country(),
                'cur' => get_woocommerce_currency(),
                'returnurl' => add_query_arg( 'wc-api', 'WC_Molpay_Gateway', home_url( '/' ) )
            );

            $molpay_args_array = array();

            foreach ($molpay_args as $key => $value) {
                $molpay_args_array[] = "<input type='hidden' name='".$key."' value='". $value ."' />";
            }
            
            $mpsreturn = add_query_arg( 'wc-api', 'WC_Molpay_Gateway', home_url( '/' ));
            $latest = ($this->get_option('account_type')=='1') ? "3.28" : "latest" ;
            return "<form action='".$pay_url."/' method='post' id='molpay_payment_form' name='molpay_payment_form'>"
                    
                    // . implode('', $molpay_args_array)
                    
                    . "<script src='https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js'></script>"
                    ."<script src='".$this->url."/MOLPay/API/seamless/".$latest."/js/MOLPay_seamless.deco.js'></script>"
                    . "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css'>"

                    // New Layout
                    . "<h3 style='font-size:16px; font-weight:bold;'><u>Pay via</u>:</h3>"
                    . "<img src='".plugins_url('images/logo_E2Pay_Fiuu.png', __FILE__)."' width='150px' style='display:block; margin-bottom:10px;'>"

                    // Button Chooser
                    . "<div id='main-buttons' style='display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;'>"
                    // Credit Card
                    . ($this->CIMB_NIAGA || $this->BRI || $this->BCA ? "<button type='button' class='category-btn' style='background:white; border-radius:5px; border:2px solid gray; outline:none;' data-category='card'><img src='".plugins_url('images/card-payment.png', __FILE__)."' width='100%'/></button>" : '')
                    // Credit Card Installment, set to false since nothing is ready yet for ID
                    . (false && ($this->CIMB_NIAGA || $this->BRI || $this->BCA) ?"<button type='button' class='category-btn' style='background:white; border-radius:5px; border:2px solid gray; outline:none;' data-category='card-installment'><img src='".plugins_url('images/card-instalment.png', __FILE__)."' width='100%'/></button>" : '')
                    // Online Banking/Internet Banking
                    . ($this->e2Pay_CIMBOctoClicks_IB ? "<button type='button' class='category-btn' style='background:white; border-radius:5px; border:2px solid gray; outline:none;' data-category='online-banking'><img src='".plugins_url('images/online-banking.png', __FILE__)."' width='100%'/></button>" : '')
                    // E-Wallet
                    . ($this->e2Pay_DANA || $this->e2Pay_LINKAJA_APPLINK || $this->e2Pay_LINKAJA_WCO || $this->e2Pay_SHOPEEPAY_JUMPAPP || $this->e2Pay_OVO || $this->e2Pay_GOPAY || $this->e2Pay_CIMB_OctoPay || $this->e2Pay_Alipay_QR || $this->e2Pay_WeChatPay_QR ? "<button type='button' class='category-btn' style='background:white; border-radius:5px; border:2px solid gray; outline:none ;' data-category='ewallet'><img src='".plugins_url('images/ewallet.png', __FILE__)."' width='100%'/></button>" : '')
                    // QRIS
                    . ($this->e2Pay_CIMB_QRIS || $this->e2Pay_MBayar_QR || $this->e2Pay_SHOPEEPAY_QRIS ? "<button type='button' class='category-btn' style='background:white; border-radius:5px; border:2px solid gray; outline:none ;' data-category='qris'><img src='".plugins_url('images/qris.png', __FILE__)."' width='100%'/></button>" : '')
                    // VA
                    . ($this->e2Pay_PERMATA_VA || $this->e2Pay_BNI_VA || $this->e2Pay_CIMB_VA || $this->e2Pay_BCA_VA || $this->e2Pay_BRI_VA || $this->e2Pay_MANDIRI_VA || $this->e2Pay_BSI_VA ? "<button type='button' class='category-btn' style='background:white; border-radius:5px; border:2px solid gray; outline:none;' data-category='virtual-account'><img src='".plugins_url('images/virtual-account.png', __FILE__)."' width='100%'/></button>" : '')              
                    // Convenience Store, set to false since noting is ready yet for ID
                    . ($this->e2Pay_Indomaret || $this->e2Pay_Alfamart ?"<button type='button' class='category-btn' style='background:white; border-radius:5px; border:2px solid gray; outline:none;' data-category='convenience-store'><img src='".plugins_url('images/convenience-store.png', __FILE__)."' width='100%'/></button>" : '')
                    // FN Channel
                    . ($this->e2Pay_Kredivo_FN || $this->e2Pay_Indodana_FN ?"<button type='button' class='category-btn' style='background:white; border-radius:5px; border:2px solid gray; outline:none;' data-category='bnpl'><img src='".plugins_url('images/pay-later.png', __FILE__)."' width='100%'/></button>" : '')   
                    . "</div>"

                    . "<label style='font-size:14px; display: block; margin-bottom: 10px;'><i class='fa-solid fa-circle-info' style='font-size: 12px;'></i>    Please ensure all payment details are accurate.</label>"
                    ."<br/>"


                    . "<div id='custom-dropdown' style='position: relative; display: inline-block; width: 500px; margin-bottom:10px;'>"
                        . "<button id='dropdown-button' type='button' style='width: 100%; padding: 12px; font-size: 16px; border-radius: 8px; border: 1px solid #ccc; background:white; text-align:left; display:flex; align-items:center; outline:none;'>"
                        . "Select payment method"
                        . "</button>"
                        . "<div id='dropdown-list' style='display: none; position: absolute; width: 100%; background: white; border: 1px solid #ccc; border-radius: 8px; z-index: 10; max-height: 300px; overflow-y: auto;'></div>"
                    . "</div>"

                    . "<label for='agree' style='font-size: 14px; display: block; margin-bottom: 15px;'>"
                        . "<input type='checkbox' name='checkbox' value='check' id='agree' style='margin-right: 5px;' />"
                            . " I have read and agree to the <b> <a href='https://fiuu.com/terms-of-services/' style='color: #44d62c;' target='_blank'>Terms & Conditions</a> </b> and "
                            . "<b><a href='https://fiuu.com/privacy-policy/' style='color: #44d62c;' target='_blank'>Privacy Policy</a></b>."
                        . "<br/>"
                    . "</label>"
            
                    . "<div id='div_generatedSingleBtn'>"
                        . "<button id='pay-button' type='button' class='btn btn-success btn-lg' style='width:200px; background-color:#44d62c; font-size:20px; padding:10px; border-radius:5px; border:none; margin-bottom:30px;'>Pay</button>"
                    . "</div>"

                    // jQuery
                    . "<script>
                const el = jQuery('#pay-button');
                const formPost = jQuery('#molpay_payment_form');
                jQuery(document).ready(function() {
                    API_HOST = '".$this->url."';
                    var paymentOptions = {
                        'card': {
                           " . ($this->CIMB_NIAGA ? "'CIMB NIAGA': { value: 'credit21', image: '".plugins_url('images/CIMBNiaga.png', __FILE__)."' }," : '') . "
                           " . ($this->BCA ? "'BCA': { value: 'credit23', image: '".plugins_url('images/BCA.png', __FILE__)."' }," : '') . "
                           " . ($this->BRI ? "'BRI': { value: 'credit24', image: '".plugins_url('images/BRI.png', __FILE__)."' }," : '') . "
                        },
                        'card-installment': {
                           " . ($this->CIMB_NIAGA ? "'CIMB NIAGA': { value: 'credit21', image: '".plugins_url('images/CIMBNiaga.png', __FILE__)."' }," : '') . "
                           " . ($this->BCA ? "'BCA': { value: 'credit23', image: '".plugins_url('images/BCA.png', __FILE__)."' }," : '') . "
                           " . ($this->BRI ? "'BRI': { value: 'credit24', image: '".plugins_url('images/BRI.png', __FILE__)."' }," : '') . "
                        },
                        'online-banking': {
                           " . ($this->e2Pay_CIMBOctoClicks_IB ? "'CIMB Octo Clicks Internet Banking': { value: 'e2Pay_CIMBOctoClicks_IB', image: '".plugins_url('images/CIMBOctoClicks.png', __FILE__)."' }," : '') . "
                        },
                        'ewallet' : {
                           " . ($this->e2Pay_DANA ? "'DANA': { value: 'e2Pay_DANA', image: '".plugins_url('images/DANA.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_LINKAJA_APPLINK ? "'LinkAja App Link': { value: 'e2Pay_LINKAJA_APPLINK', image: '".plugins_url('images/LINKAJA.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_LINKAJA_WCO ? "'LinkAja Web Checkout': { value: 'e2Pay_LINKAJA_WCO', image: '".plugins_url('images/LINKAJA.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_SHOPEEPAY_JUMPAPP ? "'ShopeePay JumpApp': { value: 'e2Pay_SHOPEEPAY_JUMPAPP', image: '".plugins_url('images/SHOPEEPAY.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_OVO ? "'OVO': { value: 'e2Pay_OVO', image: '".plugins_url('images/OVO.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_GOPAY ? "'GOPAY': { value: 'e2Pay_GOPAY', image: '".plugins_url('images/GOPAY.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_CIMB_OctoPay ? "'CIMB OctoPay': { value: 'e2Pay_CIMB_OctoPay', image: '".plugins_url('images/CIMBOctoClicks.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_Alipay_QR ? "'Alipay QR': { value: 'e2Pay_Alipay_QR', image: '".plugins_url('images/ALIPAY.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_WeChatPay_QR ? "'WeChatPay QR': { value: 'e2Pay_WeChatPay_QR', image: '".plugins_url('images/WeChatPay.png', __FILE__)."' }," : '') . "
                        },
                        'qris' : {
                           " . ($this->e2Pay_CIMB_QRIS ? "'CIMB QRIS': { value: 'e2Pay_CIMB_QRIS', image: '".plugins_url('images/CIMBClicks.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_MBayar_QR ? "'MBayar QRIS': { value: 'e2Pay_MBayar_QR', image: '".plugins_url('images/MBAYAR.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_SHOPEEPAY_QRIS ? "'ShopeePay QRIS': { value: 'e2Pay_SHOPEEPAY_QRIS', image: '".plugins_url('images/SHOPEEPAY.png', __FILE__)."' }," : '') . "
                        },
                        'virtual-account' : {
                           " . ($this->e2Pay_PERMATA_VA ? "'Permata Virtual Account': { value: 'e2Pay_PERMATA_VA', image: '".plugins_url('images/Permata.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_BNI_VA ? "'BNI Virtual Account': { value: 'e2Pay_BNI_VA', image: '".plugins_url('images/BNI.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_CIMB_VA ? "'CIMB Virtual Account': { value: 'e2Pay_CIMB_VA', image: '".plugins_url('images/CIMB_VA.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_BCA_VA ? "'BCA Virtual Account': { value: 'e2Pay_BCA_VA', image: '".plugins_url('images/BCA.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_BRI_VA ? "'BRI Virtual Account': { value: 'e2Pay_BRI_VA', image: '".plugins_url('images/BRI.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_MANDIRI_VA ? "'Mandiri Virtual Account': { value: 'e2Pay_MANDIRI_VA', image: '".plugins_url('images/mandiri.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_BSI_VA ? "'BSI Virtual Account': { value: 'e2Pay_BSI_VA', image: '".plugins_url('images/BSI.png', __FILE__)."' }," : '') . "
                        },
                        'convenience-store' : {
                           " . ($this->e2Pay_Indomaret ? "'Indomaret': { value: 'e2Pay_Indomaret', image: '".plugins_url('images/Indomaret.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_Alfamart ? "'Alfamart': { value: 'e2Pay_Alfamart', image: '".plugins_url('images/ALFA.png', __FILE__)."' }," : '') . "
                        },
                        'bnpl' : {
                           " . ($this->e2Pay_Kredivo_FN ? "'Kredivo': { value: 'e2Pay_Kredivo_FN', image: '".plugins_url('images/kredivo.png', __FILE__)."' }," : '') . "
                           " . ($this->e2Pay_Indodana_FN ? "'Indodana': { value: 'e2Pay_Indodana_FN', image: '".plugins_url('images/Indodana.png', __FILE__)."' }," : '') . "

                        },


                    };

            
                    jQuery('.category-btn').on('click', function() {
                        var selectedCategory = jQuery(this).data('category');
                        var dropdownList = jQuery('#dropdown-list');
                        dropdownList.empty();
                        clearAttrData(el);
                        jQuery('#dropdown-button').text('Select payment method');
            
                        if (paymentOptions[selectedCategory]) {
                            jQuery.each(paymentOptions[selectedCategory], function(name, data) {
                                dropdownList.append(
                                    `<div class='dropdown-item' data-value='`+data.value+`' style='padding: 10px; cursor: pointer; display: flex; align-items: center; font-size:16px;'> 
                                        <img src='`+data.image+`' style='margin-right:10px; width:100px; height::100px;'> `+name+`
                                    </div>`                               );
                            });
                        }
                        
                        jQuery('.category-btn').css('border-color', '#707070'); 
                        jQuery(this).css('border-color', '#44d62c'); 
                        jQuery()
                    });
            
                    // Show/hide dropdown on button click
                    jQuery('#dropdown-button').on('click', function() {
                        jQuery('#dropdown-list').toggle();
                    });
            
                    // Hide dropdown when clicking outside
                    jQuery(document).on('click', function(event) {
                        if (!jQuery(event.target).closest('#custom-dropdown').length) {
                            jQuery('#dropdown-list').hide();
                        }
                    });

                    // Handle Pay Button Clicked/Submit
                    jQuery('#pay-button').on('click', function(e) {
                        if (!jQuery(this).is('[data-toggle]')) {
                            if (!jQuery('#agree').is(':checked')) {
                                e.preventDefault();
                                alert('Please indicate that you have read and agree to the Terms and Conditions and Privacy Policy');
                                return;
                            } else {
                                el.attr('data-toggle','molpayseamless');
                            }
                        }
                    });
            
                    // Handle selection from the dropdown
                    jQuery('#dropdown-list').on('click', '.dropdown-item', function() {
                        var selectedMethod = jQuery(this).data('value');
                        jQuery('#dropdown-button').html(jQuery(this).html()); // Update button with selected value
                        jQuery('#dropdown-list').hide(); // Close dropdown after selection
            
                        var merchantID = '" . $this->merchant_id . "';
                        var orderID = '" . $order_number . "';
                        var bill_name = '" . $order->get_billing_first_name() . " " . $order->get_billing_last_name() . "';
                        var bill_email = '" . $order->get_billing_email() . "';
                        var bill_mobile = '" . $order->get_billing_phone() . "';
                        var bill_desc = '" . $desc . "';
                        var currency = '" . get_woocommerce_currency() . "';
                        var amt = '" . $total . "';
                        var vcode = '" . $vcode . "';
                        var returnUrl = '" . $mpsreturn . "';
                        var country = '" . $order->get_billing_country() . "';
                        var cctype = '" . $this->credit_tcctype . "';
                        
                        clearAttrData(el);

                        el.attr('data-mpsmerchantid',merchantID);
                        el.attr('data-mpschannel',selectedMethod);
                        el.attr('data-mpsamount',amt);
                        el.attr('data-mpstcctype',cctype);
                        el.attr('data-mpsorderid',orderID);
                        el.attr('data-mpsbill_name',bill_name);
                        el.attr('data-mpsbill_email',bill_email);
                        el.attr('data-mpsbill_mobile',bill_mobile);
                        el.attr('data-mpsbill_desc',bill_desc);
                        el.attr('data-mpscurrency',currency);
                        el.attr('data-mpsvcode',vcode);
                        el.attr('data-mpsreturnurl',returnUrl);
                        el.attr('data-mpscountry',country);
                    });
                    
                    function clearAttrData() {
                        el.each(function () {
                            const attrs = Array.from(this.attributes);

                            attrs.forEach(attr => {
                                if (attr.name.startsWith('data-')) {
                                    jQuery(this).removeAttr(attr.name);
                                }
                            });

                            jQuery(this).removeData();
                        });
                    }
                });
            </script>"                    
                    . "</form>";
        }
        

        /**
         * Order error button.
         *
         * @param  object $order Order data.
         * @return string Error message and cancel button.
         */
        protected function molpay_order_error( $order ) {
            $html = '<p>' . __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'wcmolpay' ) . '</p>';
            $html .='<a class="buttoncancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Click to try again', 'wcmolpay' ) . '</a>';
            return $html;
        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment( $order_id ) {
            $order = new WC_Order( $order_id );
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url( true )
            );
        }

        /**
         * Output for the order received page.
         * 
         * @param  object $order Order data.
         */
        public function receipt_page( $order ) {
            echo $this->generate_form( $order );
        }

        /**
         * Check for E2Pay Response
         *
         * @access public
         * @return void
         */
        function check_ipn_response() {
            @ob_clean();

            if ( !( isset($_POST['nbcb']) )) {
                do_action( "valid_molpay_request_returnurl", $_POST );
            } else if ( $_POST['nbcb']=='1' ) {
                do_action ( "valid_molpay_request_callback", $_POST );
            } else if ( $_POST['nbcb']=='2' ) {
                do_action ( "valid_molpay_request_notification", $_POST );
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
        function check_molpay_response_returnurl() {
            global $woocommerce;
            
            $verifyresult = $this->verifySkey($_POST);
            $status = $_POST['status'];
            if( !$verifyresult )
                $status = "-1";

            $WCOrderId = $this->get_WCOrderIdByOrderId($_POST['orderid']);
            $order = new WC_Order( $WCOrderId );

            $referer = "<br>Referer: ReturnURL";
            $getStatus =  $order->get_status();
            if(!in_array($getStatus,array('processing','completed'))) {
                if ($status == "11") {
                    $referer .= " (Inquiry)";
                    $status = $this->inquiry_status( $_POST['tranID'], $_POST['amount'], $_POST['domain']);
                }
                $this->update_Cart_by_Status($WCOrderId, $status, $_POST['tranID'], $referer, $_POST['channel']);
                if (in_array($status, array("00","22"))) {
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
        function check_molpay_response_notification() {
            global $woocommerce;
            $verifyresult = $this->verifySkey($_POST);
            $status = $_POST['status'];
            if ( !$verifyresult )
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
        function check_molpay_response_callback() {
            global $woocommerce;
            $verifyresult = $this->verifySkey($_POST);
            $status = $_POST['status'];
            if ( !$verifyresult )
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
        public function merchant_id_missing_message() {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf( __( '<strong>Gateway Disabled</strong> You should fill in your Merchant ID in E2Pay. %sClick here to configure!%s' , 'wcmolpay' ), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=wc_molpay_gateway">', '</a>' ) . '</p>';
            $message .= '</div>';
            echo $message;
        }

        /**
         * Adds error message when not configured the verify_key.
         * 
         */
        public function verify_key_missing_message() {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf( __( '<strong>Gateway Disabled</strong> You should fill in your Verify Key in E2Pay. %sClick here to configure!%s' , 'wcmolpay' ), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=wc_molpay_gateway">', '</a>' ) . '</p>';
            $message .= '</div>';
            echo $message;
        }

        /**
         * Adds error message when not configured the secret_key.
         * 
         */
        public function secret_key_missing_message() {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf( __( '<strong>Gateway Disabled</strong> You should fill in your Secret Key in E2Pay. %sClick here to configure!%s' , 'wcmolpay' ), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=wc_molpay_gateway">', '</a>' ) . '</p>';
            $message .= '</div>';
            echo $message;
        }

        /**
         * Adds error message when not configured the account_type.
         * 
         */
        public function account_type_missing_message() {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf( __( '<strong>Gateway Disabled</strong> Select account type in E2Pay. %sClick here to configure!%s' , 'wcmolpay' ), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=wc_molpay_gateway">', '</a>' ) . '</p>';
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
        public function inquiry_status($tranID, $amount, $domain) {
            $verify_key = $this->verify_key;
            $requestUrl = $this->inquiry_url."/RMS/q_by_tid.php";
            $request_param = array(
                "amount"    => number_format($amount,2),
                "txID"      => intval($tranID),
                "domain"    => urlencode($domain),
                "skey"      => urlencode(md5(intval($tranID).$domain.$verify_key.number_format($amount,2))) );
            $post_data = http_build_query($request_param);
            $header[] = "Content-Type: application/x-www-form-urlencoded";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch,CURLOPT_URL, $requestUrl);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $response = trim($response);
            $temp = explode("\n", $response);
            foreach ( $temp as $value ) {
                $array = explode(':', $value);
                $key = trim($array[0], "[]");
                $result[$key] = trim($array[1]);
            }
            $verify = md5($result['Amount'].$this->secret_key.$result['Domain'].$result['TranID'].$result['StatCode']);
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
        public function update_Cart_by_Status($orderid, $MOLPay_status, $tranID, $referer, $channel) {
            global $woocommerce;

            $order = new WC_Order( $orderid );

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
                    $M_status = 'PENDING';
                    $W_status = 'pending';
                    break;
            }

            $getStatus = $order->get_status();
            if(!in_array($getStatus,array('processing','completed'))) {
                $order->add_order_note('E2Pay Payment Status: '.$M_status.'<br>Transaction ID: ' . $tranID . $referer);
                if ($MOLPay_status == "00") {
                    $order->payment_complete();
                } else {
                    $order->update_status($W_status, sprintf(__('Payment %s via E2Pay.', 'woocommerce'), $tranID ) );
                }
                if ($this->payment_title == 'yes') {
                    $paytitle = $this->form_fields[strtolower($channel)]['title'];
                    $order->set_payment_method_title($paytitle);
                    $order->save();
                }
            }
        }


        /**
         * Obtain the original order id based using the returned transaction order id
         * 
         * @global mixed $woocommerce
         * @param int $orderid
         * @return int $real_order_id
         */
        public function get_WCOrderIdByOrderId($orderid) {
            switch($this->ordering_plugin) {
                case '1' : // sequential order number
                    $WCOrderId = wc_sequential_order_numbers()->find_order_by_order_number( $orderid );
                    break;
                case '2' : // sequential order number pro
                    $WCOrderId = wc_seq_order_number_pro()->find_order_by_order_number( $orderid );
                    break;
                case '3' : // advanced order number
                    $WCOrderId = $this->find_order_by_advanced_order_number( $orderid, '_oton_number_ordernumber' );
                    break;
                case '4' : // custom order number
                    $WCOrderId = $this->find_order_by_custom_order_number($orderid, '_alg_wc_full_custom_order_number');
                    break;
                case '0' : 
                default :
                    $WCOrderId = $orderid;
                    break;
            }
            return $WCOrderId;
        }

        /**
         * Get order id from ordering plugin's order id.
         *
         * @global mixed  $woocommerce
         * @param  int    $orderid
         * @param  string $metaKey
         *
         * @return int
         */
        private function find_order_by_custom_order_number($orderid, $metaKey)
        {
            $query_args = array(
                'numberposts' => 1,
                'meta_key'    => $metaKey,
                'meta_value'  => $orderid,
                'post_type'   => 'shop_order',
                'post_status' => 'any',
                'fields'      => 'ids',
            );
            $post = get_posts( $query_args );
            list( $WCOrderId ) = $post;

            return $WCOrderId;
        }

        public function find_order_by_advanced_order_number( $order_number, $metaKey ) {

            $query_args = array(
                'numberposts' => 1,
                'meta_key'    => $metaKey,
                'meta_value'  => $order_number,
                'post_type'   => 'shop_order',
                'post_status' => 'any',
                'fields'      => 'ids',
            );
            $post = get_posts( $query_args );
            list( $order_number ) = ! empty( $post ) ? $post : null;

            return $order_number;

        }


        /**
         * Acknowledge transaction result
         * 
         * @global mixed $woocommerce
         * @param array $response
         */
        public function acknowledgeResponse($response) {
            if ($response['nbcb'] == '1') {
                echo "CBTOKEN:MPSTATOK"; exit;
            } else {
                $response['treq']= '1'; // Additional parameter for IPN
                foreach($response as $k => $v) {
                    $postData[]= $k."=".$v;
                }
                $postdata = implode("&",$postData);
                $url = $this->url."/RMS/API/chkstat/returnipn.php";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_POST , 1 );
                curl_setopt($ch, CURLOPT_POSTFIELDS , $postdata );
                curl_setopt($ch, CURLOPT_URL , $url );
                curl_setopt($ch, CURLOPT_HEADER , 1 );
                curl_setopt($ch, CURLINFO_HEADER_OUT , TRUE );
                curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1 );
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , FALSE);
                curl_setopt($ch, CURLOPT_SSLVERSION , CURL_SSLVERSION_TLSv1 );
                $result = curl_exec( $ch );
                curl_close( $ch );
            }
        }

        /**
         * To verify transaction result using merchant secret key setting.
         * 
         * @global mixed $woocommerce
         * @param  array $response
         * @return boolean verifyresult
         */
        public function verifySkey($response) {

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
            
            $key0 = md5($tranID.$orderid.$status.$domain.$amount.$currency);
            $key1 = md5($paydate.$domain.$key0.$appcode.$vkey);
            if ($skey != $key1)
                return false;
            else
                return true;
        }

    }
}