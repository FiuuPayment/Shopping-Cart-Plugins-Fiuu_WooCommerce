<?php
/**
 * Fiuu WooCommerce Shopping Cart Plugin
 * 
 * @author Fiuu Technical Team <technical@fiuu.com>
 * @version 6.2.0
 * @example For callback : http://shoppingcarturl/?wc-api=WC_Molpay_Gateway
 * @example For notification : http://shoppingcarturl/?wc-api=WC_Molpay_Gateway
 */

/**
 * Plugin Name: WooCommerce Fiuu Services Seamless
 * Plugin URI: https://github.com/FiuuPayment/Shopping-Cart-Plugins-Fiuu_WooCommerce
 * Description: WooCommerce Fiuu | The leading payment gateway in South East Asia Grow your business with Fiuu Services payment solutions & free features: Physical Payment at 7-Eleven, Seamless Checkout, Tokenization, Loyalty Program and more for WooCommerce
 * Author: Fiuu Services Tech Team
 * Author URI: https://fiuu.com/
 * Version: 6.2.0
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
    $message .= '<p>' . __( 'WooCommerce Fiuu Gateway depends on the last version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work!' , 'wcmolpay' ) . '</p>';
    $message .= '</div>';
    echo $message;
}

//Load the function
add_action( 'plugins_loaded', 'wcmolpay_gateway_load', 0 );

/**
 * Load Fiuu gateway plugin function
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
     * Add Fiuu gateway to ensure WooCommerce can load it
     * 
     * @param array $methods
     * @return array
     */
    function wcmolpay_add_gateway( $methods ) {
        $methods[] = 'WC_Molpay_Gateway';
        return $methods;
    }

    /**
     * Define the Fiuu gateway
     * 
     */
    class WC_Molpay_Gateway extends WC_Payment_Gateway {

        protected $logger;
        protected $log_context;

        /**
         * Construct the Fiuu gateway class
         * 
         * @global mixed $woocommerce
         */
        public function __construct() {
            global $woocommerce;

            $this->id = 'molpay';
            $this->icon = plugins_url( 'images/logo_Fiuu_small.png', __FILE__ );
            $this->has_fields = false;
            $this->method_title = __( 'Fiuu', 'wcmolpay' );
            $this->method_description = __( 'Proceed payment via Fiuu Seamless Integration Plugin', 'woocommerce' );

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
            $this->cancelurl = $this->settings['cancelurl'];
            $this->waittime = $this->settings['waittime'];
            $this->extend_vcode = $this->settings['extend_vcode'];
            
            // Define hostname based on account_type
            $this->url = ($this->get_option('account_type')=='1') ? "https://pay.fiuu.com/" : "https://sandbox.merchant.razer.com/";
            $this->inquiry_url = ($this->get_option('account_type')=='1') ? "https://api.fiuu.com/" : "https://sandbox.merchant.razer.com/";
            
            // Define channel setting variables
            $this->credit = ($this->get_option('credit')=='yes' ? true : false);
            $this->credit7 = ($this->get_option('credit7')=='yes' ? true : false);
            $this->fpx_mb2u = ($this->get_option('fpx_mb2u')=='yes' ? true : false);
            $this->PayNow = ($this->get_option('PayNow')=='yes' ? true : false);
            $this->ESUN_Cash711 = ($this->get_option('ESUN_Cash711')=='yes' ? true : false);
            $this->ESUN_CashFamilyMart = ($this->get_option('ESUN_CashFamilyMart')=='yes' ? true : false);
            $this->ESUN_ATM = ($this->get_option('ESUN_ATM')=='yes' ? true : false);
            $this->ESUN_CashHiLife = ($this->get_option('ESUN_CashHiLife')=='yes' ? true : false);
            $this->fpx_cimbclicks = ($this->get_option('fpx_cimbclicks')=='yes' ? true : false);
            $this->fpx_hlb = ($this->get_option('fpx_hlb')=='yes' ? true : false);
            $this->fpx_rhb = ($this->get_option('fpx_rhb')=='yes' ? true : false);
            $this->fpx_amb = ($this->get_option('fpx_amb')=='yes' ? true : false);
            $this->fpx_pbb = ($this->get_option('fpx_pbb')=='yes' ? true : false);
            $this->fpx_abb = ($this->get_option('fpx_abb')=='yes' ? true : false);
            $this->fpx_bimb = ($this->get_option('fpx_bimb')=='yes' ? true : false);
            $this->fpx_abmb = ($this->get_option('fpx_abmb')=='yes' ? true : false);
            $this->fpx_bkrm = ($this->get_option('fpx_bkrm')=='yes' ? true : false);
            $this->fpx_bmmb = ($this->get_option('fpx_bmmb')=='yes' ? true : false);
            $this->fpx_bsn = ($this->get_option('fpx_bsn')=='yes' ? true : false);
            $this->fpx_hsbc = ($this->get_option('fpx_hsbc')=='yes' ? true : false);
            $this->fpx_kfh = ($this->get_option('fpx_kfh')=='yes' ? true : false);
            $this->fpx_ocbc = ($this->get_option('fpx_ocbc')=='yes' ? true : false);
            $this->fpx_scb = ($this->get_option('fpx_scb')=='yes' ? true : false);
            $this->fpx_uob = ($this->get_option('fpx_uob')=='yes' ? true : false);
            $this->FPX_M2E = ($this->get_option('FPX_M2E')=='yes' ? true : false);
            $this->FPX_B2B_ABB = ($this->get_option('FPX_B2B_ABB')=='yes' ? true : false);
            $this->FPX_B2B_ABBM = ($this->get_option('FPX_B2B_ABBM')=='yes' ? true : false);
            $this->FPX_B2B_ABMB = ($this->get_option('FPX_B2B_ABMB')=='yes' ? true : false);
            $this->FPX_B2B_AMB = ($this->get_option('FPX_B2B_AMB')=='yes' ? true : false);
            $this->FPX_B2B_BIMB = ($this->get_option('FPX_B2B_BIMB')=='yes' ? true : false);
            $this->FPX_B2B_BKRM = ($this->get_option('FPX_B2B_BKRM')=='yes' ? true : false);
            $this->FPX_B2B_BMMB = ($this->get_option('FPX_B2B_BMMB')=='yes' ? true : false);
            $this->FPX_B2B_BNP = ($this->get_option('FPX_B2B_BNP')=='yes' ? true : false);
            $this->FPX_B2B_CIMB = ($this->get_option('FPX_B2B_CIMB')=='yes' ? true : false);
            $this->FPX_B2B_CITIBANK = ($this->get_option('FPX_B2B_CITIBANK')=='yes' ? true : false);
            $this->FPX_B2B_DEUTSCHE = ($this->get_option('FPX_B2B_DEUTSCHE')=='yes' ? true : false);
            $this->FPX_B2B_HLB = ($this->get_option('FPX_B2B_HLB')=='yes' ? true : false);
            $this->FPX_B2B_HSBC = ($this->get_option('FPX_B2B_HSBC')=='yes' ? true : false);
            $this->FPX_B2B_KFH = ($this->get_option('FPX_B2B_KFH')=='yes' ? true : false);
            $this->FPX_B2B_OCBC = ($this->get_option('FPX_B2B_OCBC')=='yes' ? true : false);
            $this->FPX_B2B_PBB = ($this->get_option('FPX_B2B_PBB')=='yes' ? true : false);
            $this->FPX_B2B_PBBE = ($this->get_option('FPX_B2B_PBBE')=='yes' ? true : false);
            $this->FPX_B2B_RHB = ($this->get_option('FPX_B2B_RHB')=='yes' ? true : false);
            $this->FPX_B2B_SCB = ($this->get_option('FPX_B2B_SCB')=='yes' ? true : false);
            $this->FPX_B2B_UOB = ($this->get_option('FPX_B2B_UOB')=='yes' ? true : false);
            $this->FPX_B2B_UOBR = ($this->get_option('FPX_B2B_UOBR')=='yes' ? true : false);
            $this->Point_BCard = ($this->get_option('Point-BCard')=='yes' ? true : false);
            $this->dragonpay = ($this->get_option('dragonpay')=='yes' ? true : false);
            $this->NGANLUONG = ($this->get_option('NGANLUONG')=='yes' ? true : false);
            $this->paysbuy = ($this->get_option('paysbuy')=='yes' ? true : false);
            $this->cash_711 = ($this->get_option('cash-711')=='yes' ? true : false);
            $this->ATMVA = ($this->get_option('ATMVA')=='yes' ? true : false);
            $this->enetsD = ($this->get_option('enetsD')=='yes' ? true : false);
            $this->singpost = ($this->get_option('singpost')=='yes' ? true : false);
            $this->UPOP = ($this->get_option('UPOP')=='yes' ? true : false);
            $this->alipay = ($this->get_option('alipay')=='yes' ? true : false);
            $this->WeChatPay = ($this->get_option('WeChatPay')=='yes' ? true : false);
            $this->WeChatPayMY = ($this->get_option('WeChatPayMY')=='yes' ? true : false);
            $this->BOOST = ($this->get_option('BOOST')=='yes' ? true : false);
            $this->MB2U_QRPay_Push = ($this->get_option('MB2U_QRPay-Push')=='yes' ? true : false);
            $this->RazerPay = ($this->get_option('RazerPay')=='yes' ? true : false);
            $this->ShopeePay = ($this->get_option('ShopeePay')=='yes' ? true : false);
            $this->Rely_PW = ($this->get_option('Rely-PW')=='yes' ? true : false);
            $this->IOUPay_PW = ($this->get_option('IOUPay-PW')=='yes' ? true : false);
            $this->TNG_EWALLET = ($this->get_option('TNG-EWALLET')=='yes' ? true : false);
            $this->GrabPay = ($this->get_option('GrabPay')=='yes' ? true : false);
            $this->BAY_IB_U = ($this->get_option('BAY_IB_U')=='yes' ? true : false);
            $this->BBL_IB_U = ($this->get_option('BBL_IB_U')=='yes' ? true : false);
            $this->KBANK_PayPlus = ($this->get_option('KBANK_PayPlus')=='yes' ? true : false);
            $this->KTB_IB_U = ($this->get_option('KTB_IB_U')=='yes' ? true : false);
            $this->SCB_IB_U = ($this->get_option('SCB_IB_U')=='yes' ? true : false);
            $this->BigC = ($this->get_option('BigC')=='yes' ? true : false);
            $this->OMISE_TL = ($this->get_option('OMISE_TL')=='yes' ? true : false);
            $this->Crypto_tripleA = ($this->get_option('Crypto_tripleA')=='yes' ? true : false);
            $this->Atome = ($this->get_option('Atome')=='yes' ? true : false);
            $this->Pace = ($this->get_option('Pace')=='yes' ? true : false);
            $this->cimb_ebpg = ($this->get_option('cimb-ebpg')=='yes' ? true : false);
            $this->pbb_cybs = ($this->get_option('pbb-cybs')=='yes' ? true : false);

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
            if ( !in_array( get_woocommerce_currency() , array( 'MYR' ) ) ) {
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
            <h3><?php _e( 'Fiuu', 'wcmolpay' ); ?></h3>
            <p><?php _e( 'Fiuu works by sending the user to Fiuu to enter their payment information.', 'wcmolpay' ); ?></p>
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
                    'label' => __( 'Enable Fiuu', 'wcmolpay' ),
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
                    'default' => __( 'Fiuu', 'wcmolpay' ),
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
                    'default' => __( 'Fiuu', 'wcmolpay' ),
                    'desc_tip' => true,
                ),
                'merchant_id' => array(
                    'title' => __( 'Merchant ID', 'wcmolpay' ),
                    'type' => 'text',
                    'description' => __( 'Please enter your Fiuu Merchant ID.', 'wcmolpay' ) . ' ' . sprintf( __( 'You can to get this information in: %sFiuu Account%s.', 'wcmolpay' ), '<a href="https://portal.merchant.razer.com/" target="_blank">', '</a>' ),
                    'default' => ''
                ),
                'verify_key' => array(
                    'title' => __( 'Verify Key', 'wcmolpay' ),
                    'type' => 'text',
                    'description' => __( 'Please enter your Fiuu Verify Key.', 'wcmolpay' ) . ' ' . sprintf( __( 'You can to get this information in: %sFiuu Account%s.', 'wcmolpay' ), '<a href="https://portal.merchant.razer.com/" target="_blank">', '</a>' ),
                    'default' => ''
                ),
                'secret_key' => array(
                    'title' => __( 'Secret Key', 'wcmolpay' ),
                    'type' => 'text',
                    'description' => __( 'Please enter your Fiuu Secret Key.', 'wcmolpay' ) . ' ' . sprintf( __( 'You can to get this information in: %sFiuu Account%s.', 'wcmolpay' ), '<a href="https://portal.merchant.razer.com/" target="_blank">', '</a>' ),
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
                'extend_vcode' => array(
                    'title' => __('Extended VCode', 'wcmolpay'),
                    'type' => 'checkbox',
                    'label' => __('Enable extended VCode'),
                    'description' => __('This controls the extended VCode', 'wcmolpay'),
                    'default' => 'no',
                    'desc_tip' => true
                ),
                'waittimetitle' => array(
                    'title'         => 'Payment Timeout',
                    'type'          => 'title',
                    'description'   => '',
                ),

                'waittime' => array(
                    'title' => __( 'Timeout (seconds)', 'wcmolpay' ),
                    'type' => 'number',
                    'description' => __( 'This controls the timeout in Fiuu Card Hosted Page.', 'wcmolpay' ),
                    'default' => ''
                ),

                'cancelurl' => array(
                    'title' => __( 'Cancel URL', 'wcmolpay' ),
                    'type' => 'text',
                        'description' => __( 'This is the URL redirect after exceeding timeout, please ensure the domain of this URL is registered in Fiuu Portal, Example: (https://yourdomain.com/cancel)', 'wcmolpay' ),
                    'default' => ''
                ),
                
                'channel' => array(
                    'title'         => 'Channel to be Enabled',
                    'type'          => 'title',
                    'description'   => '',
                ),
                'credit' => array(
                    'title' => __( 'Credit Card/ Debit Card', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                
                ),
                'PayNow' => array(
                    'title' => __( 'PayNow', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                
                ),
                'cimb-ebpg' => array(
                    'title' => __( 'CIMB Bank (Installment) ', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                    
                ),
                'pbb-cybs' => array(
                    'title' => __( 'Public Bank (Installment) ', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                    
                ),
                'ESUN_Cash711' => array(
                    'title' => __( 'ESUN Cash-711', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                
                ),
                'ESUN_CashFamilyMart' => array(
                    'title' => __( 'ESUN Cash FamilyMart', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                
                ),
                'ESUN_ATM' => array(
                    'title' => __( 'ESUN ATM', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                
                ),
                'ESUN_CashHiLife' => array(
                    'title' => __( 'ESUN CashHiLife', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                
                ),
                'fpx_mb2u' => array(
                    'title' => __( 'FPX Maybank (Maybank2u)', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'fpx_cimbclicks' => array(
                    'title' => __( 'FPX CIMB Bank (CIMB Clicks)', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'fpx_hlb' => array(
                    'title' => __( 'FPX Hong Leong Bank (HLB Connect)', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'fpx_rhb' => array(
                    'title' => __( 'FPX RHB Bank (RHB Now)', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'fpx_amb' => array(
                    'title' => __( 'FPX Am Bank (Am Online)', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'fpx_pbb' => array(
                    'title' => __( 'FPX PublicBank (PBB Online)', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'fpx_abb' => array(
                    'title' => __( 'FPX Affin Bank (Affin Online)', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'fpx_bimb' => array(
                    'title' => __( 'FPX Bank Islam', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'fpx_abmb' => array(
                    'title' => __( 'FPX Alliance Bank (Alliance Online)', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'fpx_bkrm' => array(
                    'title' => __( 'FPX Bank Kerjasama Rakyat Malaysia', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'fpx_bmmb' => array(
                    'title' => __( 'FPX Bank Muamalat', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'fpx_bsn' => array(
                    'title' => __( 'FPX Bank Simpanan Nasional (myBSN)', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'fpx_hsbc' => array(
                    'title' => __( 'FPX Hongkong and Shanghai Banking Corporation', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'fpx_kfh' => array(
                    'title' => __( 'FPX Kuwait Finance House', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'fpx_ocbc' => array(
                    'title' => __( 'FPX OCBC Bank', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'fpx_scb' => array(
                    'title' => __( 'FPX Standard Chartered Bank', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'fpx_uob' => array(
                    'title' => __( 'FPX United Overseas Bank (UOB)', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'FPX_M2E' => array(
                    'title' => __('FPX Maybank2e', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_ABB' => array(
                    'title' => __('FPX B2B Affin Bank', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_ABBM' => array(
                    'title' => __('FPX B2B AffinMax', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_ABMB' => array(
                    'title' => __('FPX B2B Alliance Bank', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_AMB' => array(
                    'title' => __('FPX B2B AmBank', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_BIMB' => array(
                    'title' => __('FPX B2B Bank Islam Malaysia Berhad', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_BKRM' => array(
                    'title' => __('FPX B2B i-bizRAKYAT', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_BMMB' => array(
                    'title' => __('FPX B2B Bank Muamalat', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_BNP' => array(
                    'title' => __('FPX B2B BNP Paribas', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_CIMB' => array(
                    'title' => __('FPX B2B BizChannel@CIMB', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_CITIBANK' => array(
                    'title' => __('FPX B2B CITIBANK', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_DEUTSCHE' => array(
                    'title' => __('FPX B2B Deutsche Bank', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_HLB' => array(
                    'title' => __('FPX B2B Hong Leong Connect', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_HSBC' => array(
                    'title' => __('FPX B2B HSBC', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_KFH' => array(
                    'title' => __('FPX B2B Kuwait Finance House Overseas Bank', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_OCBC' => array(
                    'title' => __('FPX B2B OCBC Bank', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_PBB' => array(
                    'title' => __('FPX B2B Public Bank', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_PBBE' => array(
                    'title' => __('FPX B2B Public Bank Enterprise', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_RHB' => array(
                    'title' => __('FPX B2B RHB Reflex', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_SCB' => array(
                    'title' => __('FPX B2B Standard Chartered Bank', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_UOB' => array(
                    'title' => __('FPX B2B United Overseas Bank', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'FPX_B2B_UOBR' => array(
                    'title' => __('FPX B2B UOB Regional', 'wcmolpay'),
                    'type'  => 'checkbox',
                    'label' => __(' ', 'wcmolpay'),
                    'default' => 'no'
                ),
                'Point-BCard' => array(
                    'title' => __( 'Point-BCard', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'dragonpay' => array(
                    'title' => __( 'Dragonpay', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'NGANLUONG' => array(
                    'title' => __( 'NGANLUONG', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'paysbuy' => array(
                    'title' => __( 'PaysBuy', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'cash-711' => array(
                    'title' => __( '7-Eleven (Fiuu Cash)', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'ATMVA' => array(
                    'title' => __( 'ATM Transfer via Permata Bank', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'singpost' => array(
                    'title' => __( 'Cash-SAM', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'UPOP' => array(
                    'title' => __( 'China Union Pay', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'alipay' => array(
                    'title' => __( 'Alipay', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'WeChatPay' => array(
                    'title' => __( 'WeChatPay Cross Border', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),  
                'WeChatPayMY' => array(
                    'title' => __( 'WeChatPayMY', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'BOOST' => array(
                    'title' => __( 'Boost', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'MB2U_QRPay-Push' => array(
                    'title' => __( 'Maybank QRPay', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'RazerPay' => array(
                    'title' => __( 'Fiuu Pay', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'ShopeePay' => array(
                    'title' => __( 'Shopee Pay', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'IOUPay-PW' => array(
                    'title' => __( 'IOUPay', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'TNG-EWALLET' => array(
                    'title' => __( 'Touch `n Go eWallet', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'GrabPay' => array(
                    'title' => __( 'Grab Pay', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'BAY_IB_U' => array(
                    'title' => __( 'Bank of Ayudhya (Krungsri)', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'BBL_IB_U' => array(
                    'title' => __( 'Bangkok Bank (Fee on user)', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'KBANK_PayPlus' => array(
                    'title' => __( 'Kasikornbank K PLUS', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'KTB_IB_U' => array(
                    'title' => __( 'Krung Thai Bank (Fee on user)', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'SCB_IB_U' => array(
                    'title' => __( 'Siam Commercial Bank (Fee on user)', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'BigC' => array(
                    'title' => __( 'BigC', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'OMISE_TL' => array(
                    'title' => __( 'Tesco Lotus via OMISE', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'Crypto_tripleA' => array(
                    'title' => __( 'Crypto tripleA', 'wcmolpay' ),
                    'type' => 'checkbox',
                    'label' => __( ' ', 'wcmolpay' ),
                    'default' => 'no'
                ),
                'Atome' => array(
                    'title' => __( 'Atome', 'wcmolpay' ),
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
            $pay_url = $this->url.'MOLPay/pay/'.$this->merchant_id;
            $total = $order->get_total();
            $order_number = $order->get_order_number();
            if ($this->extend_vcode == 'yes') {
                $vcode = md5($order->get_total().$this->merchant_id.$order_number.$this->verify_key.get_woocommerce_currency());
            } else {
                $vcode = md5($order->get_total().$this->merchant_id.$order_number.$this->verify_key);
            }
            
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

            $cancelurl = !empty($this->cancelurl) ? $this->cancelurl.'/'.$order_number : '';


            return "<form action='".$pay_url."/' method='post' id='molpay_payment_form' name='molpay_payment_form'  
                    onsubmit='if(document.getElementById(\"agree\").checked) { return true; } else { alert(\"Please indicate that you have read and agree to the Terms and Conditions and Privacy Policy\"); return false; }'>"
            
                    . "<script src='https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js'></script>"
                    . "<script src='".$this->url."RMS/API/seamless/".$latest."/js/MOLPay_seamless.deco.js'></script>"
                    . "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css'>"
            
                    . "<h3 style='font-size:16px; font-weight:bold;'><u>Pay via</u>:</h3>"
                    . "<img src='".plugins_url('images/logo_Fiuu.png', __FILE__)."' width='150px' style='display:block; margin-bottom:10px;'>"
            
                    . "<div id='main-buttons' style='display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;'>"
                        . ($this->credit || $this->credit7 ? "<button type='button' class='category-btn' style='background:white; border-radius:5px; border:2px solid gray; outline:none;' data-category='card'><img src='".plugins_url('images/card-payment.png', __FILE__)."' width='100%'/></button>" : '')
                        . ($this->cimb_ebpg || $this->pbb_cybs ?"<button type='button' class='category-btn' style='background:white; border-radius:5px; border:2px solid gray; outline:none;' data-category='card-installment'><img src='".plugins_url('images/card-instalment.png', __FILE__)."' width='100%'/></button>" : '')
                        . ($this->BBL_IB_U || $this->BAY_IB_U || $this->UPOP || $this->dragonpay || $this->fpx_abb || $this->fpx_abmb || $this->fpx_amb || $this->fpx_bimb || $this->fpx_bkrm || $this->fpx_bmmb || $this->fpx_bsn || $this->fpx_cimbclicks || $this->fpx_hsbc || $this->fpx_hlb || $this->fpx_kfh || $this->fpx_mb2u || $this->FPX_M2E || $this->fpx_ocbc || $this->fpx_pbb || $this->fpx_rhb || $this->fpx_scb || $this->fpx_uob || $this->KBANK_PayPlus || $this->KTB_IB_U || $this->NGANLUONG || $this->SCB_IB_U || $this->paysbuy || $this->FPX_B2B_ABB || $this->FPX_B2B_ABBM || $this->FPX_B2B_ABMB || $this->FPX_B2B_AMB || $this->FPX_B2B_BIMB || $this->FPX_B2B_BKRM || $this->FPX_B2B_BMMB || $this->FPX_B2B_CIMB || $this->FPX_B2B_BNP || $this->FPX_B2B_CITIBANK || $this->FPX_B2B_DEUTSCHE || $this->FPX_B2B_HLB || $this->FPX_B2B_HSBC || $this->FPX_B2B_KFH || $this->FPX_B2B_OCBC || $this->FPX_B2B_PBB || $this->FPX_B2B_PBBE || $this->FPX_B2B_RHB || $this->FPX_B2B_SCB || $this->FPX_B2B_UOB || $this->FPX_B2B_UOBR
                        ? "<button type='button' class='category-btn' style='background:white; border-radius:5px; border:2px solid gray; outline:none;' data-category='online-banking'><img src='".plugins_url('images/online-banking.png', __FILE__)."' width='100%'/></button>" : '')
                        . ($this->alipay || $this->BOOST || $this->Crypto_tripleA || $this->RazerPay || $this->GrabPay || $this->MB2U_QRPay_Push || $this->PayNow || $this->Point_BCard || $this->ShopeePay || $this->TNG_EWALLET || $this->WeChatPay || $this->WeChatPayMY ? "<button type='button' class='category-btn' style='background:white; border-radius:5px; border:2px solid gray; outline:none ;' data-category='qr-ewallet'><img src='".plugins_url('images/ewallet.png', __FILE__)."' width='100%'/></button>" : '')
                        . ($this->cash_711 || $this->BigC || $this->singpost || $this->ESUN_ATM || $this->ESUN_Cash711 || $this->ESUN_CashFamilyMart || $this->ESUN_CashHiLife || $this->OMISE_TL || $this->ATMVA ? "<button type='button' class='category-btn' style='background:white; border-radius:5px; border:2px solid gray; outline:none;' data-category='cash-payment'><img src='".plugins_url('images/fiuu_cash.png', __FILE__)."' width='100%'/></button>" : '')              
                        . ($this->Atome || $this->IOUPay_PW ?"<button type='button' class='category-btn' style='background:white; border-radius:5px; border:2px solid gray; outline:none;' data-category='bnpl'><img src='".plugins_url('images/pay-later.png', __FILE__)."' width='100%'/></button>" : '')                
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
                            . " I have read and agree to the <b> <a href='https://merchant.razer.com/v3/terms-of-service/' style='color: #44d62c;' target='_blank'>Terms & Conditions</a> </b> and "
                            . "<b><a href='https://merchant.razer.com/v3/privacy-policy/' style='color: #44d62c;' target='_blank'>Privacy Policy</a></b>."
                        . "<br/>"
                    . "</label>"
            
                    . "<div id='div_generatedSingleBtn'>"
                        . "<button id='pay-button' type='button' class='btn btn-success btn-lg' style='width:200px; background-color:#44d62c; font-size:20px; padding:10px; border-radius:5px; border:none; margin-bottom:30px;'>Pay</button>"
                    . "</div>"

                    . "<script>
                jQuery(document).ready(function() {
                    var paymentOptions = {
                        'card': {
                           " . ($this->credit ? "'Credit/Debit Card': { value: 'creditAN', image: '".plugins_url('images/credit.png', __FILE__)."' }," : '') . "
                        },
                        'card-installment': {
                           " . ($this->cimb_ebpg ? "'CIMB Bank (Installment)': { value: 'cimb_ebpg', image: '".plugins_url('images/fpx_cimbclicks.png', __FILE__)."' }," : '') . "
                           " . ($this->pbb_cybs ? "'Public Bank (Installment)': { value: 'pbb_cybs', image: '".plugins_url('images/fpx_pbb.png', __FILE__)."' }," : '') . "
                        },
                        'online-banking': {
                           " . ($this->BBL_IB_U ? "'Bangkok Bank': { value: 'BBL_IB_U', image: '".plugins_url('images/BBL_IB_U.png', __FILE__)."' }," : '') . "
                           " . ($this->BAY_IB_U ? "'Bank of Ayudhya (Krungsi)': { value: 'BAY_IB_U', image: '".plugins_url('images/BAY_IB_U.png', __FILE__)."' }," : '') . "
                           " . ($this->UPOP ? "'China Union Pay': { value: 'UPOP', image: '".plugins_url('images/UPOP.png', __FILE__)."' }," : '') . "
                           " . ($this->dragonpay ? "'Dragonpay': { value: 'dragonpay', image: '".plugins_url('images/dragonpay.png', __FILE__)."' }," : '') . "
                           " . ($this->fpx_abb ? "'FPX Affin Bank (Affin Online)': { value: 'fpx_abb', image: '".plugins_url('images/fpx_abb.png', __FILE__)."' }," : '') . "
                           " . ($this->fpx_abmb ? "'FPX Alliance Bank (Alliance Online)': { value: 'fpx_abmb', image: '".plugins_url('images/fpx_abmb.png', __FILE__)."' }," : '') . "
                           " . ($this->fpx_amb ? "'FPX Am Bank (Am Online)': { value: 'fpx_amb', image: '".plugins_url('images/fpx_amb.png', __FILE__)."' }," : '') . "
                           " . ($this->fpx_bimb ? "'FPX Bank Islam': { value: 'fpx_bimb', image: '".plugins_url('images/fpx_bimb.png', __FILE__)."' }," : '') . "
                           " . ($this->fpx_bkrm ? "'FPX Bank Rakyat': { value: 'fpx_bkrm', image: '".plugins_url('images/fpx_bkrm.png', __FILE__)."' }," : '') . "
                           " . ($this->fpx_bmmb ? "'FPX Bank Muamalat': { value: 'fpx_bmmb', image: '".plugins_url('images/fpx_bmmb.png', __FILE__)."' }," : '') . "
                           " . ($this->fpx_bsn ? "'FPX Bank Simpanan Nasional (myBSN)': { value: 'fpx_bsn', image: '".plugins_url('images/fpx_bsn.png', __FILE__)."' }," : '') . "
                           " . ($this->fpx_cimbclicks ? "'FPX CIMB Bank (CIMB Clicks)': { value: 'fpx_cimbclicks', image: '".plugins_url('images/fpx_cimbclicks.png', __FILE__)."' }," : '') . "
                           " . ($this->fpx_hsbc ? "'FPX Hongkong and Shanghai Banking Corporation': { value: 'fpx_hsbc', image: '".plugins_url('images/fpx_hsbc.png', __FILE__)."' }," : '') . "
                           " . ($this->fpx_hlb ? "'FPX Hong Leong Bank (HLB Connect)': { value: 'fpx_hlb', image: '".plugins_url('images/fpx_hlb.png', __FILE__)."' }," : '') . "
                           " . ($this->fpx_kfh ? "'FPX Kuwait Finance House': { value: 'fpx_kfh', image: '".plugins_url('images/fpx_kfh.png', __FILE__)."' }," : '') . "
                           " . ($this->fpx_mb2u ? "'FPX Maybank2u': { value: 'fpx_mb2u', image: '".plugins_url('images/fpx_mb2u.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_M2E ? "'FPX Maybank2e': { value: 'FPX_M2E', image: '".plugins_url('images/FPX_M2E.png', __FILE__)."' }," : '') . "
                           " . ($this->fpx_ocbc ? "'FPX OCBC Bank': { value: 'fpx_ocbc', image: '".plugins_url('images/fpx_ocbc.png', __FILE__)."' }," : '') . "
                           " . ($this->fpx_pbb ? "'FPX PublicBank (PBB Online)': { value: 'fpx_pbb', image: '".plugins_url('images/fpx_pbb.png', __FILE__)."' }," : '') . "
                           " . ($this->fpx_rhb ? "'FPX RHB': { value: 'fpx_rhb', image: '".plugins_url('images/rhb.png', __FILE__)."' }," : '') . "
                           " . ($this->fpx_scb ? "'FPX Standard Chartered Bank': { value: 'fpx_scb', image: '".plugins_url('images/fpx_scb.png', __FILE__)."' }," : '') . "
                           " . ($this->fpx_uob ? "'FPX United Overseas Bank (UOB)': { value: 'fpx_uob', image: '".plugins_url('images/fpx_uob.png', __FILE__)."' }," : '') . "
                           " . ($this->KBANK_PayPlus ? "'Kasikornbank K PLUS': { value: 'KBANK_PayPlus', image: '".plugins_url('images/KBANK_PayPlus.png', __FILE__)."' }," : '') . "
                           " . ($this->KTB_IB_U ? "'Krung Thai Bank': { value: 'KTB_IB_U', image: '".plugins_url('images/KTB_IB_U.png', __FILE__)."' }," : '') . "
                           " . ($this->NGANLUONG ? "'NGANLUONG': { value: 'NGANLUONG', image: '".plugins_url('images/NGANLUONG.png', __FILE__)."' }," : '') . "
                           " . ($this->SCB_IB_U ? "'Siam Commercial Bank': { value: 'SCB_IB_U', image: '".plugins_url('images/SCB_IB_U.png', __FILE__)."' }," : '') . "
                           " . ($this->paysbuy ? "'PaysBuy': { value: 'paysbuy', image: '".plugins_url('images/paysbuy.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_ABB ? "'FPX B2B Affin Bank': { value: 'FPX_B2B_ABB', image: '".plugins_url('images/FPX_B2B_ABB.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_ABBM ? "'FPX B2B AffinMax': { value: 'FPX_B2B_ABBM', image: '".plugins_url('images/FPX_B2B_ABBM.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_ABMB ? "'FPX B2B Alliance Bank': { value: 'FPX_B2B_ABMB', image: '".plugins_url('images/FPX_B2B_ABMB.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_AMB ? "'FPX B2B AmBank': { value: 'FPX_B2B_AMB', image: '".plugins_url('images/FPX_B2B_AMB.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_BIMB ? "'FPX B2B Bank Islam': { value: 'FPX_B2B_BIMB', image: '".plugins_url('images/FPX_B2B_BIMB.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_BKRM ? "'FPX B2B i-bizRAKYAT': { value: 'FPX_B2B_BKRM', image: '".plugins_url('images/FPX_B2B_BKRM.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_BMMB ? "'FPX B2B Bank Muamalat': { value: 'FPX_B2B_BMMB', image: '".plugins_url('images/FPX_B2B_BMMB.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_CIMB ? "'FPX B2B BizChannel@CIMB': { value: 'FPX_B2B_CIMB', image: '".plugins_url('images/FPX_B2B_CIMB.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_BNP ? "'FPX B2B BNP Paribas': { value: 'FPX_B2B_BNP', image: '".plugins_url('images/FPX_B2B_BNP.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_CITIBANK ? "'FPX B2B CITIBANK': { value: 'FPX_B2B_CITIBANK', image: '".plugins_url('images/FPX_B2B_CITIBANK.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_DEUTSCHE ? "'FPX B2B Deutsche Bank': { value: 'FPX_B2B_DEUTSCHE', image: '".plugins_url('images/FPX_B2B_DEUTSCHE.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_HLB ? "'FPX B2B Hong Leong Connect': { value: 'FPX_B2B_HLB', image: '".plugins_url('images/FPX_B2B_HLB.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_HSBC ? "'FPX B2B HSBC': { value: 'FPX_B2B_HSBC', image: '".plugins_url('images/FPX_B2B_HSBC.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_KFH ? "'FPX B2B Kuwait Finance House Overseas Bank': { value: 'FPX_B2B_KFH', image: '".plugins_url('images/FPX_B2B_KFH.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_OCBC ? "'FPX B2B OCBC Bank': { value: 'FPX_B2B_OCBC', image: '".plugins_url('images/FPX_B2B_OCBC.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_PBB ? "'FPX B2B Public Bank': { value: 'FPX_B2B_PBB', image: '".plugins_url('images/FPX_B2B_PBB.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_PBBE ? "'FPX B2B Public Bank Enterprise': { value: 'FPX_B2B_PBBE', image: '".plugins_url('images/FPX_B2B_PBBE.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_RHB ? "'FPX B2B RHB Reflex': { value: 'FPX_B2B_RHB', image: '".plugins_url('images/FPX_B2B_RHB.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_SCB ? "'FPX B2B Standard Chartered Bank': { value: 'FPX_B2B_SCB', image: '".plugins_url('images/FPX_B2B_SCB.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_UOB ? "'FPX B2B United Overseas Bank': { value: 'FPX_B2B_UOB', image: '".plugins_url('images/FPX_B2B_UOB.png', __FILE__)."' }," : '') . "
                           " . ($this->FPX_B2B_UOBR ? "'FPX B2B UOB Regional': { value: 'FPX_B2B_UOBR', image: '".plugins_url('images/FPX_B2B_UOBR.png', __FILE__)."' }," : '') . "
                        },
                        'qr-ewallet' : {
                           " . ($this->alipay ? "'Alipay': { value: 'alipay', image: '".plugins_url('images/alipay.png', __FILE__)."' }," : '') . "
                           " . ($this->BOOST ? "'Boost': { value: 'BOOST', image: '".plugins_url('images/boost.png', __FILE__)."' }," : '') . "
                           " . ($this->Crypto_tripleA ? "'Crypto tripleA': { value: 'Crypto_tripleA', image: '".plugins_url('images/Crypto_tripleA.png', __FILE__)."' }," : '') . "
                           " . ($this->RazerPay ? "'Fiuu Pay': { value: 'RazerPay', image: '".plugins_url('images/razerpay.png', __FILE__)."' }," : '') . "
                           " . ($this->GrabPay ? "'Grab Pay': { value: 'GrabPay', image: '".plugins_url('images/grabpay.png', __FILE__)."' }," : '') . "
                           " . ($this->MB2U_QRPay_Push ? "'Maybank QR': { value: 'MB2U_QRPay-Push', image: '".plugins_url('images/maybankQR.png', __FILE__)."' }," : '') . "
                           " . ($this->PayNow ? "'PayNow': { value: 'PayNow', image: '".plugins_url('images/PayNow.png', __FILE__)."' }," : '') . "
                           " . ($this->Point_BCard ? "'Point BCard': { value: 'Point-BCard', image: '".plugins_url('images/Point-BCard.png', __FILE__)."' }," : '') . "
                           " . ($this->ShopeePay ? "'Shopee Pay': { value: 'ShopeePay', image: '".plugins_url('images/shopeepay_2.png', __FILE__)."' }," : '') . "
                           " . ($this->TNG_EWALLET ? "'Touch `n Go eWallet': { value: 'TNG-EWALLET', image: '".plugins_url('images/touchngo_ewallet.png', __FILE__)."' }," : '') . "
                           " . ($this->WeChatPay ? "'WeChatPay Cross Border': { value: 'WeChatPay', image: '".plugins_url('images/WeChatPay.png', __FILE__)."' }," : '') . "
                           " . ($this->WeChatPayMY ? "'WeChatPayMY': { value: 'WeChatPayMY', image: '".plugins_url('images/wechatpay_my.png', __FILE__)."' }," : '') . "
                        },
                        'cash-payment' : {
                           " . ($this->cash_711 ? "'7-Eleven': { value: 'cash-711', image: '".plugins_url('images/cash-711.png', __FILE__)."' }," : '') . "
                           " . ($this->BigC ? "'BigC': { value: 'BigC', image: '".plugins_url('images/BigC.png', __FILE__)."' }," : '') . "
                           " . ($this->singpost ? "'Cash-SAM': { value: 'singpost', image: '".plugins_url('images/singpost.png', __FILE__)."' }," : '') . "
                           " . ($this->ESUN_ATM ? "'ESUN ATM': { value: 'ESUN_ATM', image: '".plugins_url('images/ESUN-ATM.png', __FILE__)."' }," : '') . "
                           " . ($this->ESUN_Cash711 ? "'ESUN Cash711': { value: 'ESUN_Cash711', image: '".plugins_url('images/ESUN-Cash-711.png', __FILE__)."' }," : '') . "
                           " . ($this->ESUN_CashFamilyMart ? "'ESUN Cash FamilyMart': { value: 'ESUN_CashFamilyMart', image: '".plugins_url('images/ESUN-Cash-FamilyMart.png', __FILE__)."' }," : '') . "
                           " . ($this->ESUN_CashHiLife ? "'ESUN CashHiLife': { value: 'ESUN_CashHiLife', image: '".plugins_url('images/ESUN-CashHiLife.png', __FILE__)."' }," : '') . "
                           " . ($this->OMISE_TL ? "'Tesco Lotus via OMISE': { value: 'OMISE_TL', image: '".plugins_url('images/OMISE_TL.png', __FILE__)."' }," : '') . "
                           " . ($this->ATMVA ? "'ATM Transfer via Permata Bank': { value: 'ATMVA', image: '".plugins_url('images/ATMVA.png', __FILE__)."' }," : '') . "

                        },
                        'bnpl' : {
                           " . ($this->Atome ? "'Atome': { value: 'Atome', image: '".plugins_url('images/Atome.png', __FILE__)."' }," : '') . "
                           " . ($this->IOUPay_PW ? "'IOUPay': { value: 'IOUPay-PW', image: '".plugins_url('images/IOUPay-PW.png', __FILE__)."' }," : '') . "

                        },


                    };

            
                    jQuery('.category-btn').on('click', function() {
                        var selectedCategory = jQuery(this).data('category');
                        var dropdownList = jQuery('#dropdown-list');
                        dropdownList.empty();
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
            
                        var append_data = `data-mpsmerchantid=\"`+merchantID+`\" data-mpschannel=\"`+selectedMethod+`\" 
                                           data-mpsamount=\"`+amt+`\" data-mpstcctype=\"`+cctype+`\" data-mpsorderid=\"`+orderID+`\" data-mpsbill_name=\"`+bill_name+`\" 
                                           data-mpsbill_email=\"`+bill_email+`\" data-mpsbill_mobile=\"`+bill_mobile+`\" 
                                           data-mpsbill_desc=\"`+bill_desc+`\" data-mpscurrency=\"`+currency+`\" data-mpsvcode=\"`+vcode+`\" 
                                           data-mpsreturnurl=\"`+returnUrl+`\" data-mpscountry=\"`+country+`\" `;
            
                        jQuery('#div_generatedSingleBtn').html(
                            `<button type=\"button\" data-toggle=\"molpayseamless\" `+append_data+` 
                                class=\"btn btn-success btn-lg\" style='width:200px; background-color:#44d62c; font-size:20px; padding:10px; border-radius:5px; border:none;'>Pay</button>`
                        );
                    });
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
         * Check for Fiuu Response
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
                $error_message = "Fiuu Request Failure";
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
            $message .= '<p>' . sprintf( __( '<strong>Gateway Disabled</strong> You should fill in your Merchant ID in Fiuu. %sClick here to configure!%s' , 'wcmolpay' ), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=wc_molpay_gateway">', '</a>' ) . '</p>';
            $message .= '</div>';
            echo $message;
        }

        /**
         * Adds error message when not configured the verify_key.
         * 
         */
        public function verify_key_missing_message() {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf( __( '<strong>Gateway Disabled</strong> You should fill in your Verify Key in Fiuu. %sClick here to configure!%s' , 'wcmolpay' ), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=wc_molpay_gateway">', '</a>' ) . '</p>';
            $message .= '</div>';
            echo $message;
        }

        /**
         * Adds error message when not configured the secret_key.
         * 
         */
        public function secret_key_missing_message() {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf( __( '<strong>Gateway Disabled</strong> You should fill in your Secret Key in Fiuu. %sClick here to configure!%s' , 'wcmolpay' ), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=wc_molpay_gateway">', '</a>' ) . '</p>';
            $message .= '</div>';
            echo $message;
        }

        /**
         * Adds error message when not configured the account_type.
         * 
         */
        public function account_type_missing_message() {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf( __( '<strong>Gateway Disabled</strong> Select account type in Fiuu. %sClick here to configure!%s' , 'wcmolpay' ), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=wc_molpay_gateway">', '</a>' ) . '</p>';
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
            $requestUrl = $this->inquiry_url."MOLPay/q_by_tid.php";
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
         * Update Cart based on Fiuu status
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

            $channel_mappings = array(
                'maybank2u' => 'fpx_mb2u',
                'cimb' => 'fpx_cimbclicks',
                'hlb' => 'fpx_hlb',
                'rhb' => 'fpx_rhb',
                'amb' => 'fpx_amb',
                'publicbank' => 'fpx_pbb',
                'abb' => 'fpx_abb',
                'bankislam' => 'fpx_bimb',
                'alliancebank' => 'fpx_abmb',
                'bkrm' => 'fpx_bkrm',
                'bsn' => 'fpx_bsn',
                'hsbc' => 'fpx_hsbc',
                'kuwait-finace' => 'fpx_kfh',
                'ocbc' => 'fpx_ocbc',
                'scb' => 'fpx_scb',
                'uob' => 'fpx_uob',
                'TNG-EWALLET' => 'TNG_EWALLET',
                'cimb-ebpg' => 'cimb_ebpg',
                'pbb-cybs' => 'pbb_cybs'
            );

            if (isset($channel_mappings[$channel])) {
                $channel = $channel_mappings[$channel];
            }

            $getStatus = $order->get_status();
            if(!in_array($getStatus,array('processing','completed'))) {
                $order->add_order_note('Fiuu Payment Status: '.$M_status.'<br>Transaction ID: ' . $tranID . $referer);
                if ($MOLPay_status == "00") {
                    $order->payment_complete();
                } else {
                    $order->update_status($W_status, sprintf(__('Payment %s via Fiuu.', 'woocommerce'), $tranID ) );
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
                $url = $this->url."MOLPay/API/chkstat/returnipn.php";
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