<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_SiliconPay extends WC_Payment_Gateway_CC
{

    /**
     * Is test mode active?
     *
     * @var bool
     */
    public $testmode;

    /**
     * Should orders be marked as complete after payment?
     *
     * @var bool
     */
    public $autocomplete_order;

    /**
     * SiliconPay payment page type.
     *
     * @var string
     */
    public $payment_page;
    /**
     * SiliconPay encryption key.
     *
     * @var string
     */
    public $live_encryption_key;

    /**
     * SiliconPay live secret key.
     *
     * @var string
     */
    public $live_secret_key;

    /**
     * Should we save customer cards?
     *
     * @var bool
     */
    public $saved_cards;

    /**
     * Should SiliconPay split payment be enabled.
     *
     * @var bool
     */
    public $split_payment;

    /**
     * Should the cancel & remove order button be removed on the pay for order page.
     *
     * @var bool
     */
    public $remove_cancel_order_button;

    /**
     * SiliconPay sub account code.
     *
     * @var string
     */
    public $subaccount_code;

    /**
     * Who bears SiliconPay charges?
     *
     * @var string
     */
    public $charges_account;

    /**
     * A flat fee to charge the sub account for each transaction.
     *
     * @var string
     */
    public $transaction_charges;

    /**
     * Should custom metadata be enabled?
     *
     * @var bool
     */
    public $custom_metadata;

    /**
     * Should the order id be sent as a custom metadata to SiliconPay?
     *
     * @var bool
     */
    public $meta_order_id;

    /**
     * Should the customer name be sent as a custom metadata to SiliconPay?
     *
     * @var bool
     */
    public $meta_name;

    /**
     * Should the billing email be sent as a custom metadata to SiliconPay?
     *
     * @var bool
     */
    public $meta_email;

    /**
     * Should the billing phone be sent as a custom metadata to SiliconPay?
     *
     * @var bool
     */
    public $meta_phone;

    /**
     * Should the billing address be sent as a custom metadata to SiliconPay?
     *
     * @var bool
     */
    public $meta_billing_address;

    /**
     * Should the shipping address be sent as a custom metadata to SiliconPay?
     *
     * @var bool
     */
    public $meta_shipping_address;

    /**
     * Should the order items be sent as a custom metadata to SiliconPay?
     *
     * @var bool
     */
    public $meta_products;

    /**
     * API public key
     *
     * @var string
     */
    public $encryption_key;

    /**
     * API secret key
     *
     * @var string
     */
    public $secret_key;

    /**
     * Gateway disabled message
     *
     * @var string
     */
    public $msg;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = 'siliconpay';
        $this->method_title = __('SiliconPay', 'woo-siliconpay');
        $this->method_description = sprintf(__('SiliconPay provide merchants with the tools and services needed to accept online payments from local and international customers using Mastercard, Visa, Verve Cards and Bank Accounts. <a href="%1$s" target="_blank">Sign up</a> for a SiliconPay account, and <a href="%2$s" target="_blank">get your API keys</a>.', 'woo-siliconpay'), 'https://siliconpay.com', 'https://dashboard.siliconpay.com/#/settings/developer');
        $this->has_fields = true;

        $this->payment_page = $this->get_option('payment_page');

        $this->supports = array(
            'products',
        );

        // Load the form fields
        $this->init_form_fields();

        // Load the settings
        $this->init_settings();

        // Get setting values

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = $this->get_option('testmode') === 'yes' ? true : false;
        $this->autocomplete_order = $this->get_option('autocomplete_order') === 'yes' ? true : false;

        $this->live_encryption_key = $this->get_option('live_encryption_key');
        $this->live_secret_key = $this->get_option('live_secret_key');

        $this->saved_cards = $this->get_option('saved_cards') === 'yes' ? true : false;

        $this->split_payment = $this->get_option('split_payment') === 'yes' ? true : false;
        $this->remove_cancel_order_button = $this->get_option('remove_cancel_order_button') === 'yes' ? true : false;
        $this->subaccount_code = $this->get_option('subaccount_code');
        $this->charges_account = $this->get_option('split_payment_charge_account');
        $this->transaction_charges = $this->get_option('split_payment_transaction_charge');

        $this->custom_metadata = $this->get_option('custom_metadata') === 'yes' ? true : false;

        $this->meta_order_id = $this->get_option('meta_order_id') === 'yes' ? true : false;
        $this->meta_name = $this->get_option('meta_name') === 'yes' ? true : false;
        $this->meta_email = $this->get_option('meta_email') === 'yes' ? true : false;
        $this->meta_phone = $this->get_option('meta_phone') === 'yes' ? true : false;
        $this->meta_billing_address = $this->get_option('meta_billing_address') === 'yes' ? true : false;
        $this->meta_shipping_address = $this->get_option('meta_shipping_address') === 'yes' ? true : false;
        $this->meta_products = $this->get_option('meta_products') === 'yes' ? true : false;

        $this->encryption_key = $this->live_encryption_key;
        $this->secret_key = $this->live_secret_key;

        // Hooks
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));

        add_action('admin_notices', array($this, 'admin_notices'));
        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array(
                $this,
                'process_admin_options',
            )
        );

        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

        // Payment listener/API hook.
        add_action('woocommerce_api_wc_gateway_siliconpay', array($this, 'verify_siliconpay_app_transaction'));

        // Check if the gateway can be used.
        if (!$this->is_valid_for_use()) {
            $this->enabled = false;
        }

    }

    /**
     * Check if this gateway is enabled and available in the user's country.
     */
    public function is_valid_for_use()
    {

        if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_siliconpay_app_supported_currencies', array('UGX', 'USD', 'KES')))) {

            $this->msg = sprintf(__('SiliconPay does not support your store currency. Kindly set it to either UGX (USh), USD (&#36;), KES (KSh) <a href="%s">here</a>', 'woo-siliconpay'), admin_url('admin.php?page=wc-settings&tab=general'));

            return false;

        }

        return true;

    }

    /**
     * Display siliconpay payment icon.
     */
    public function get_icon()
    {

        $base_location = wc_get_base_location();

        if ('GH' === $base_location['country']) {
            $icon = '<img src="' . WC_HTTPS::force_https_url(plugins_url('assets/images/siliconpay-gh.png', WC_SILICONPAY_WOO_MAIN_FILE)) . '" alt="SiliconPay Payment Options" />';
        } elseif ('ZA' === $base_location['country']) {
            $icon = '<img src="' . WC_HTTPS::force_https_url(plugins_url('assets/images/siliconpay-za.png', WC_SILICONPAY_WOO_MAIN_FILE)) . '" alt="SiliconPay Payment Options" />';
        } elseif ('KE' === $base_location['country']) {
            $icon = '<img src="' . WC_HTTPS::force_https_url(plugins_url('assets/images/siliconpay-ke.png', WC_SILICONPAY_WOO_MAIN_FILE)) . '" alt="SiliconPay Payment Options" />';
        } else {
            $icon = '<img src="' . WC_HTTPS::force_https_url(plugins_url('assets/images/siliconpay-wc.png', WC_SILICONPAY_WOO_MAIN_FILE)) . '" alt="SiliconPay Payment Options" />';
        }

        return apply_filters('woocommerce_gateway_icon', $icon, $this->id);

    }

    /**
     * Check if SiliconPay merchant details is filled.
     */
    public function admin_notices()
    {

        if ($this->enabled == 'no') {
            return;
        }

        // Check required fields.
        if (!($this->encryption_key && $this->secret_key)) {
            echo '<div class="error"><p>' . sprintf(__('Please enter your SiliconPay merchant details <a href="%s">here</a> to be able to use the SiliconPay WooCommerce plugin.', 'woo-siliconpay'), admin_url('admin.php?page=wc-settings&tab=checkout&section=siliconpay')) . '</p></div>';
            return;
        }

    }

    /**
     * Check if SiliconPay gateway is enabled.
     *
     * @return bool
     */
    public function is_available()
    {

        if ('yes' == $this->enabled) {

            if (!($this->encryption_key && $this->secret_key)) {

                return false;

            }

            return true;

        }

        return false;

    }

    /**
     * Admin Panel Options.
     */
    public function admin_options()
    {

        ?>

        <h2><?php _e('SiliconPay', 'woo-siliconpay'); ?>
            <?php
            if (function_exists('wc_back_link')) {
                wc_back_link(__('Return to payments', 'woo-siliconpay'), admin_url('admin.php?page=wc-settings&tab=checkout'));
            }
            ?>
        </h2>

        <h4>
            <strong><?php printf(__('Optional: To avoid situations where bad network makes it impossible to verify transactions, set your webhook URL <a href="%1$s" target="_blank" rel="noopener noreferrer">here</a> to the URL below<span style="color: red"><pre><code>%2$s</code></pre></span>', 'woo-siliconpay'), 'https://dashboard.siliconpay.co/#/settings/developer', WC()->api_request_url('Tbz_WC_SiliconPay_Webhook')); ?></strong>
        </h4>

        <?php

        if ($this->is_valid_for_use()) {

            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';

        } else {
            ?>
            <div class="inline error">
                <p>
                    <strong><?php _e('SiliconPay Payment Gateway Disabled', 'woo-siliconpay'); ?></strong>:
                    <?php echo $this->msg; ?>
                </p>
            </div>

            <?php
        }

    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {

        $form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woo-siliconpay'),
                'label' => __('Enable SiliconPay', 'woo-siliconpay'),
                'type' => 'checkbox',
                'description' => __('Enable SiliconPay as a payment option on the checkout page.', 'woo-siliconpay'),
                'default' => 'no',
                'desc_tip' => true,
            ),
            'title' => array(
                'title' => __('Title', 'woo-siliconpay'),
                'type' => 'text',
                'description' => __('This controls the payment method title which the user sees during checkout.', 'woo-siliconpay'),
                'default' => __('Debit/Credit Cards', 'woo-siliconpay'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'woo-siliconpay'),
                'type' => 'textarea',
                'description' => __('This controls the payment method description which the user sees during checkout.', 'woo-siliconpay'),
                'default' => __('Make payment using your debit and credit cards', 'woo-siliconpay'),
                'desc_tip' => true,
            ),
            'testmode' => array(
                'title' => __('Test mode', 'woo-siliconpay'),
                'label' => __('Enable Test Mode', 'woo-siliconpay'),
                'type' => 'checkbox',
                'description' => __('Test mode enables you to test payments before going live. <br />Once the LIVE MODE is enabled on your SiliconPay account uncheck this.', 'woo-siliconpay'),
                'default' => 'yes',
                'desc_tip' => true,
            ),
            'payment_page' => array(
                'title' => __('Payment Option', 'woo-siliconpay'),
                'type' => 'select',
                'description' => __('Popup shows the payment popup on the page while Redirect will redirect the customer to SiliconPay to make payment.', 'woo-siliconpay'),
                'default' => '',
                'desc_tip' => false,
                'options' => array(
                    '' => __('Select One', 'woo-siliconpay'),
                    'inline' => __('Popup', 'woo-siliconpay'),
                    'redirect' => __('Redirect', 'woo-siliconpay'),
                ),
            ),
            'live_secret_key' => array(
                'title' => __('Merchant Secret Key', 'woo-siliconpay'),
                'type' => 'text',
                'description' => __('Enter your Merchant Secret Key here.', 'woo-siliconpay'),
                'default' => '',
            ),
            'live_encryption_key' => array(
                'title' => __('Merchant Encryption Key', 'woo-siliconpay'),
                'type' => 'text',
                'description' => __('Enter your Merchant Encryption Key here.', 'woo-siliconpay'),
                'default' => '',
            ),
            'autocomplete_order' => array(
                'title' => __('Autocomplete Order After Payment', 'woo-siliconpay'),
                'label' => __('Autocomplete Order', 'woo-siliconpay'),
                'type' => 'checkbox',
                'class' => 'wc-siliconpay-autocomplete-order',
                'description' => __('If enabled, the order will be marked as complete after successful payment', 'woo-siliconpay'),
                'default' => 'no',
                'desc_tip' => true,
            ),
            'remove_cancel_order_button' => array(
                'title' => __('Remove Cancel Order & Restore Cart Button', 'woo-siliconpay'),
                'label' => __('Remove the cancel order & restore cart button on the pay for order page', 'woo-siliconpay'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ),
            'custom_metadata' => array(
                'title' => __('Custom Metadata', 'woo-siliconpay'),
                'label' => __('Enable Custom Metadata', 'woo-siliconpay'),
                'type' => 'checkbox',
                'class' => 'wc-siliconpay-metadata',
                'description' => __('If enabled, you will be able to send more information about the order to SiliconPay.', 'woo-siliconpay'),
                'default' => 'yes',
                'desc_tip' => true,
            ),
            'meta_order_id' => array(
                'title' => __('Order ID', 'woo-siliconpay'),
                'label' => __('Send Order ID', 'woo-siliconpay'),
                'type' => 'checkbox',
                'class' => 'wc-siliconpay-meta-order-id',
                'description' => __('If checked, the Order ID will be sent to SiliconPay', 'woo-siliconpay'),
                'default' => 'no',
                'desc_tip' => true,
            ),
            'meta_name' => array(
                'title' => __('Customer Name', 'woo-siliconpay'),
                'label' => __('Send Customer Name', 'woo-siliconpay'),
                'type' => 'checkbox',
                'class' => 'wc-siliconpay-meta-name',
                'description' => __('If checked, the customer full name will be sent to SiliconPay', 'woo-siliconpay'),
                'default' => 'no',
                'desc_tip' => true,
            ),
            'meta_email' => array(
                'title' => __('Customer Email', 'woo-siliconpay'),
                'label' => __('Send Customer Email', 'woo-siliconpay'),
                'type' => 'checkbox',
                'class' => 'wc-siliconpay-meta-email',
                'description' => __('If checked, the customer email address will be sent to SiliconPay', 'woo-siliconpay'),
                'default' => 'yes',
                'desc_tip' => true,
            ),
            'meta_phone' => array(
                'title' => __('Customer Phone', 'woo-siliconpay'),
                'label' => __('Send Customer Phone', 'woo-siliconpay'),
                'type' => 'checkbox',
                'class' => 'wc-siliconpay-meta-phone',
                'description' => __('If checked, the customer phone will be sent to SiliconPay', 'woo-siliconpay'),
                'default' => 'yes',
                'desc_tip' => true,
            ),
            'meta_billing_address' => array(
                'title' => __('Order Billing Address', 'woo-siliconpay'),
                'label' => __('Send Order Billing Address', 'woo-siliconpay'),
                'type' => 'checkbox',
                'class' => 'wc-siliconpay-meta-billing-address',
                'description' => __('If checked, the order billing address will be sent to SiliconPay', 'woo-siliconpay'),
                'default' => 'no',
                'desc_tip' => true,
            ),
            'meta_shipping_address' => array(
                'title' => __('Order Shipping Address', 'woo-siliconpay'),
                'label' => __('Send Order Shipping Address', 'woo-siliconpay'),
                'type' => 'checkbox',
                'class' => 'wc-siliconpay-meta-shipping-address',
                'description' => __('If checked, the order shipping address will be sent to SiliconPay', 'woo-siliconpay'),
                'default' => 'no',
                'desc_tip' => true,
            ),
            'meta_products' => array(
                'title' => __('Product(s) Purchased', 'woo-siliconpay'),
                'label' => __('Send Product(s) Purchased', 'woo-siliconpay'),
                'type' => 'checkbox',
                'class' => 'wc-siliconpay-meta-products',
                'description' => __('If checked, the product(s) purchased will be sent to SiliconPay', 'woo-siliconpay'),
                'default' => 'no',
                'desc_tip' => true,
            )
        );

        if ('UGX' !== get_woocommerce_currency()) {
            unset($form_fields['custom_gateways']);
        }

        $this->form_fields = $form_fields;

    }

    /**
     * Payment form on checkout page
     */
    public function payment_fields()
    {
        // ok, let's display some description before the payment form
        if ($this->description) {
            if ($this->testmode) {
                $this->description .= ' TEST MODE ENABLED. In test mode, payment using this mode is not charged to the user';
                $this->description = trim($this->description);
            }
            echo wpautop(wptexturize($this->description));
        }


        // I will echo() the form, but you can close PHP tags and print it directly in HTML
        ?>
        <fieldset id="wc-<?php echo esc_attr($this->id) ?>-cc-form" class="wc-credit-card-form wc-payment-form"
                  style="background:transparent;">
            <!-- // Add this action hook if you want your custom payment gateway to support it -->
            <?php do_action('woocommerce_silicon_pay_channel_form_start', $this->id); ?>
            <!-- // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc -->
            <div class="form-row form-row-wide">
                <label class="label">Payment Channel <span class="required">*</span></label>
                <select class="form-control" id="pf-wc-method" name="pf-wc-method">
                    <option value="Mobile Money" selected>Mobile Money</option>
                    <option value="Credit/Debit Card">Credit/Debit Card</option>
                    <option value="MPESA">MPESA</option>
                </select>
            </div>
            <div class="clear"></div>
            <?php do_action('woocommerce_silicon_pay_channel_form_start', $this->id); ?>
            <div class="clear"></div>
        </fieldset>
        <?php

        if (!is_ssl()) {
            return;
        }
    }

    /**
     * Outputs scripts used for siliconpay payment.
     */
    public function payment_scripts()
    {

        if (!is_checkout_pay_page()) {
            return;
        }

        if ($this->enabled === 'no') {
            return;
        }

        $order_key = urldecode($_GET['key']);
        $order_id = absint(get_query_var('order-pay'));

        $order = wc_get_order($order_id);

        $payment_method = method_exists($order, 'get_payment_method') ? $order->get_payment_method() : $order->payment_method;

        if ($this->id !== $payment_method) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('wc_siliconpay', plugins_url('assets/js/siliconpay.js', WC_SILICONPAY_WOO_MAIN_FILE), array('jquery'), WC_SILICONPAY_WOO_VERSION, false);
        wp_enqueue_style('wc_siliconpay_0', 'https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap', false);
        wp_enqueue_style('wc_siliconpay_1', plugins_url('assets/css/siliconpay-wc-style.css', WC_SILICONPAY_WOO_MAIN_FILE), array(), WC_SILICONPAY_WOO_VERSION, 'all');


        $siliconpay_app_params = array(
            'key' => $this->encryption_key,
        );

        if (is_checkout_pay_page() && get_query_var('order-pay')) {

            $email = method_exists($order, 'get_billing_email') ? $order->get_billing_email() : $order->billing_email;
            $amount = $order->get_total();
            $txnref = $order_id . '_' . time();
            $the_order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
            $the_order_key = method_exists($order, 'get_order_key') ? $order->get_order_key() : $order->order_key;
            $currency = method_exists($order, 'get_currency') ? $order->get_currency() : $order->order_currency;

            if ($the_order_id == $order_id && $the_order_key == $order_key) {

                $siliconpay_app_params['email'] = $email;
                $siliconpay_app_params['amount'] = $amount;
                $siliconpay_app_params['txnref'] = $txnref;
                $siliconpay_app_params['currency'] = $currency;

            }


            if ($this->custom_metadata) {

                if ($this->meta_order_id) {

                    $siliconpay_app_params['meta_order_id'] = $order_id;

                }

                if ($this->meta_name) {

                    $first_name = method_exists($order, 'get_billing_first_name') ? $order->get_billing_first_name() : $order->billing_first_name;
                    $last_name = method_exists($order, 'get_billing_last_name') ? $order->get_billing_last_name() : $order->billing_last_name;

                    $siliconpay_app_params['meta_name'] = $first_name . ' ' . $last_name;

                }

                if ($this->meta_email) {

                    $siliconpay_app_params['meta_email'] = $email;

                }

                if ($this->meta_phone) {

                    $billing_phone = method_exists($order, 'get_billing_phone') ? $order->get_billing_phone() : $order->billing_phone;

                    $siliconpay_app_params['meta_phone'] = $billing_phone;

                }

                if ($this->meta_products) {

                    $line_items = $order->get_items();

                    $products = '';

                    foreach ($line_items as $item_id => $item) {
                        $name = $item['name'];
                        $quantity = $item['qty'];
                        $products .= $name . ' (Qty: ' . $quantity . ')';
                        $products .= ' | ';
                    }

                    $products = rtrim($products, ' | ');

                    $siliconpay_app_params['meta_products'] = $products;

                }

                if ($this->meta_billing_address) {

                    $billing_address = $order->get_formatted_billing_address();
                    $billing_address = esc_html(preg_replace('#<br\s*/?>#i', ', ', $billing_address));

                    $siliconpay_app_params['meta_billing_address'] = $billing_address;

                }

                if ($this->meta_shipping_address) {

                    $shipping_address = $order->get_formatted_shipping_address();
                    $shipping_address = esc_html(preg_replace('#<br\s* /?>#i', ', ', $shipping_address));

                    if (empty($shipping_address)) {

                        $billing_address = $order->get_formatted_billing_address();
                        $billing_address = esc_html(preg_replace('#<br\s* /?>#i', ', ', $billing_address));

                        $shipping_address = $billing_address;

                    }

                    $siliconpay_app_params['meta_shipping_address'] = $shipping_address;

                }
            }

            update_post_meta($order_id, '_siliconpay_app_txn_ref', $txnref);

        }

        wp_localize_script('wc_siliconpay', 'wc_siliconpay_app_params', $siliconpay_app_params);

    }

    /**
     * Load admin scripts.
     */
    public function admin_scripts()
    {

        if ('woocommerce_page_wc-settings' !== get_current_screen()->id) {
            return;
        }

        //$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '';

        $siliconpay_app_admin_params = array(
            'plugin_url' => WC_SILICONPAY_WOO_URL,
        );

        wp_enqueue_script('wc_siliconpay_app_admin', plugins_url('assets/js/siliconpay-admin.js',
            WC_SILICONPAY_WOO_MAIN_FILE), array(), WC_SILICONPAY_WOO_VERSION, true);

        wp_localize_script('wc_siliconpay_app_admin', 'wc_siliconpay_app_admin_params', $siliconpay_app_admin_params);

    }

    /**
     * Process the payment.
     *
     * @param int $order_id
     *
     * @return array|void
     */
    public function process_payment($order_id)
    {

        if ('redirect' === $this->payment_page) {

            //return $this->process_redirect_payment_option($order_id);

        } else {
            $order = wc_get_order($order_id);

            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true),
            );
        }

    }

    /**
     * Process a redirect payment option payment.
     *
     * @param int $order_id
     * @return array|void
     * @since 5.7
     */
    public function process_redirect_payment_option($order_id)
    {

        $order = wc_get_order($order_id);
        $email = method_exists($order, 'get_billing_email') ? $order->get_billing_email() : $order->billing_email;
        $amount = $order->get_total();
        $txnref = $order_id . '_' . time();
        $currency = method_exists($order, 'get_currency') ? $order->get_currency() : $order->order_currency;
        $callback_url = WC()->api_request_url('WC_Gateway_SiliconPay');


        $siliconpay_app_params = array(
            'amount' => $amount,
            'email' => $email,
            'currency' => $currency,
            'reference' => $txnref,
            'callback_url' => $callback_url,
        );


        $siliconpay_app_url = esc_url_raw($_REQUEST['siliconpay_payment_link']);

        $headers = array(
            'Authorization' => 'Bearer ' . $this->secret_key,
            'Content-Type' => 'application/json',
        );

        $args = array(
            'headers' => $headers,
            'timeout' => 60,
            'body' => json_encode($siliconpay_app_params),
        );

        $request = wp_remote_post($siliconpay_app_url, $args);

        if (!is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request)) {

            $siliconpay_app_response = json_decode(wp_remote_retrieve_body($request));

            return array(
                'result' => 'success',
                'redirect' => $siliconpay_app_response->data->authorization_url,
            );

        } else {
            wc_add_notice(__('Unable to process payment try again', 'woo-siliconpay'), 'error');

            return;
        }

    }

    /**
     * Show new card can only be added when placing an order notice.
     */
    public function add_payment_method()
    {

        wc_add_notice(__('You can only add a new card when placing an order.', 'woo-siliconpay'), 'error');

        return;

    }

    /**
     * Displays the payment page.
     *
     * @param $order_id
     */
    public function receipt_page($order_id)
    {

        $order = wc_get_order($order_id);

        echo '<div id="wc-siliconpay-form">';

        echo '<p>' . __('Thank you for your order, please click the button below to pay with SiliconPay.',
                'woo-siliconpay') . '</p>';

        echo '<div id="siliconpay_form">
                    <form id="order_review" method="post"
                        action="' . WC()->api_request_url('WC_Gateway_SiliconPay') . '"></form><button class="button"
                        id="siliconpay-payment-button">' . __('Pay Now', 'woo-siliconpay') . '</button>';

        if (!$this->remove_cancel_order_button) {
            echo ' <a class="button cancel" id="siliconpay-cancel-payment-button"
                        href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Cancel order &amp; restore cart',
                    'woo-siliconpay') . '</a>
                </div>';
        }

        echo '</div>';

    }

    /**
     * Verify SiliconPay payment.
     */
    public function verify_siliconpay_app_transaction()
    {

        if (isset($_REQUEST['siliconpay_txnref'])) {
            $siliconpay_app_txn_ref = sanitize_text_field($_REQUEST['siliconpay_txnref']);
        } elseif (isset($_REQUEST['siliconpay_reference'])) {
            $siliconpay_app_txn_ref = sanitize_text_field($_REQUEST['siliconpay_reference']);
        } else {
            $siliconpay_app_txn_ref = false;
        }

        @ob_clean();

        if ($siliconpay_app_txn_ref) {

            if (sanitize_text_field($_REQUEST['siliconpay_status_code_report']) === '200') {

                if (trim($_REQUEST['siliconpay_status']) === 'Successful' || trim($_REQUEST['siliconpay_status']) === '200') {

                    $order_details = explode('_', $_REQUEST['siliconpay_txnref']);
                    $order_id = (int)$order_details[0];
                    $order = wc_get_order($order_id);

                    if (in_array($order->get_status(), array('processing', 'completed', 'on-hold'))) {

                        wp_redirect($this->get_return_url($order));

                        exit;

                    }

                    $order_total = $order->get_total();
                    $order_currency = method_exists($order, 'get_currency') ? $order->get_currency() :
                        $order->get_order_currency();
                    $currency_symbol = get_woocommerce_currency_symbol($order_currency);
                    $amount_paid = (float)sanitize_text_field($_REQUEST['siliconpay_amount']);
                    $siliconpay_app_ref = sanitize_text_field($_REQUEST['siliconpay_reference']);
                    $payment_currency = sanitize_text_field($_REQUEST['siliconpay_currency']);
                    $gateway_symbol = get_woocommerce_currency_symbol($payment_currency);

                    // check if the amount paid is equal to the order amount.
                    if ($amount_paid < $order_total) {
                        $order->update_status('on-hold', '');

                        add_post_meta($order_id, '_transaction_id', $siliconpay_app_ref, true);

                        $notice = sprintf(__('Thank you for shopping with us.%1$sYour payment transaction was successful, but
                the amount paid is not the same as the total order amount.%2$sYour order is currently on hold.%3$sKindly
                contact us for more information regarding your order and payment status.', 'woo-siliconpay'), '<br />',
                            '<br />', '<br />');
                        $notice_type = 'notice';

                        // Add Customer Order Note
                        $order->add_order_note($notice, 1);

                        // Add Admin Order Note
                        $admin_order_note = sprintf(__('<strong>Look into this order</strong>%1$sThis order is currently on
                hold.%2$sReason: Amount paid is less than the total order amount.%3$sAmount Paid was <strong>%4$s
                    (%5$s)</strong> while the total order amount is <strong>%6$s (%7$s)</strong>%8$s<strong>SiliconPay
                    Transaction Reference:</strong> %9$s', 'woo-siliconpay'), '<br />', '<br />', '<br />',
                            $currency_symbol, $amount_paid, $currency_symbol, $order_total, '<br />', $siliconpay_app_ref);
                        $order->add_order_note($admin_order_note);

                        function_exists('wc_reduce_stock_levels') ? wc_reduce_stock_levels($order_id) :
                            $order->reduce_order_stock();

                        wc_add_notice($notice, $notice_type);

                    } else {

                        if ($payment_currency !== $order_currency) {

                            $order->update_status('on-hold', '');

                            update_post_meta($order_id, '_transaction_id', $siliconpay_app_ref);

                            $notice = sprintf(__('Thank you for shopping with us.%1$sYour payment was successful, but the payment
                currency is different from the order currency.%2$sYour order is currently on-hold.%3$sKindly contact us
                for more information regarding your order and payment status.', 'woo-siliconpay'), '<br />', '<br />',
                                '<br />');
                            $notice_type = 'notice';

                            // Add Customer Order Note
                            $order->add_order_note($notice, 1);

                            // Add Admin Order Note
                            $admin_order_note = sprintf(__('<strong>Look into this order</strong>%1$sThis order is currently on
                hold.%2$sReason: Order currency is different from the payment currency.%3$sOrder Currency is
                <strong>%4$s (%5$s)</strong> while the payment currency is <strong>%6$s
                    (%7$s)</strong>%8$s<strong>SiliconPay Transaction Reference:</strong> %9$s', 'woo-siliconpay'),
                                '<br />', '<br />', '<br />', $order_currency, $currency_symbol, $payment_currency, $gateway_symbol,
                                '<br />', $siliconpay_app_ref);
                            $order->add_order_note($admin_order_note);

                            function_exists('wc_reduce_stock_levels') ? wc_reduce_stock_levels($order_id) :
                                $order->reduce_order_stock();

                            wc_add_notice($notice, $notice_type);

                        } else {

                            $order->payment_complete($siliconpay_app_ref);
                            $order->add_order_note(sprintf(__('Payment via SiliconPay successful (Transaction Reference: %s)',
                                'woo-siliconpay'), $siliconpay_app_ref));

                            if ($this->is_autocomplete_order_enabled($order)) {
                                $order->update_status('completed');
                            }
                        }
                    }


                    WC()->cart->empty_cart();

                } else {

                    $order_details = explode('_', $_REQUEST['siliconpay_txnref']);

                    $order_id = (int)$order_details[0];

                    $order = wc_get_order($order_id);

                    $order->update_status('failed', __('Payment was declined by SiliconPay.', 'woo-siliconpay'));

                }
            }

            wp_redirect($this->get_return_url($order));

            exit;
        }

        wp_redirect(wc_get_page_permalink('cart'));

        exit;

    }


    /**
     * Get custom fields to pass to SiliconPay.
     *
     * @param int $order_id WC Order ID
     *
     * @return array
     */
    public function get_custom_fields($order_id)
    {

        $order = wc_get_order($order_id);

        $custom_fields = array();

        $custom_fields[] = array(
            'display_name' => 'Plugin',
            'variable_name' => 'plugin',
            'value' => 'woo-siliconpay',
        );

        if ($this->custom_metadata) {

            if ($this->meta_order_id) {

                $custom_fields[] = array(
                    'display_name' => 'Order ID',
                    'variable_name' => 'order_id',
                    'value' => $order_id,
                );

            }

            if ($this->meta_name) {

                $first_name = method_exists($order, 'get_billing_first_name') ? $order->get_billing_first_name() :
                    $order->billing_first_name;
                $last_name = method_exists($order, 'get_billing_last_name') ? $order->get_billing_last_name() :
                    $order->billing_last_name;

                $custom_fields[] = array(
                    'display_name' => 'Customer Name',
                    'variable_name' => 'customer_name',
                    'value' => $first_name . ' ' . $last_name,
                );

            }

            if ($this->meta_email) {

                $email = method_exists($order, 'get_billing_email') ? $order->get_billing_email() :
                    $order->billing_email;

                $custom_fields[] = array(
                    'display_name' => 'Customer Email',
                    'variable_name' => 'customer_email',
                    'value' => $email,
                );

            }

            if ($this->meta_phone) {

                $billing_phone = method_exists($order, 'get_billing_phone') ? $order->get_billing_phone() :
                    $order->billing_phone;

                $custom_fields[] = array(
                    'display_name' => 'Customer Phone',
                    'variable_name' => 'customer_phone',
                    'value' => $billing_phone,
                );

            }


            if ($this->meta_products) {

                $line_items = $order->get_items();

                $products = '';

                foreach ($line_items as $item_id => $item) {
                    $name = $item['name'];
                    $quantity = $item['qty'];
                    $products .= $name . ' (Qty: ' . $quantity . ')';
                    $products .= ' | ';
                }

                $products = rtrim($products, ' | ');

                $custom_fields[] = array(
                    'display_name' => 'Products',
                    'variable_name' => 'products',
                    'value' => $products,
                );

            }

            if ($this->meta_billing_address) {

                $billing_address = $order->get_formatted_billing_address();
                $billing_address = esc_html(preg_replace('#<br\s* /?>#i', ', ', $billing_address));

                $siliconpay_app_params['meta_billing_address'] = $billing_address;

                $custom_fields[] = array(
                    'display_name' => 'Billing Address',
                    'variable_name' => 'billing_address',
                    'value' => $billing_address,
                );

            }

            if ($this->meta_shipping_address) {

                $shipping_address = $order->get_formatted_shipping_address();
                $shipping_address = esc_html(preg_replace('#<br\s* /?>#i', ', ', $shipping_address));

                if (empty($shipping_address)) {

                    $billing_address = $order->get_formatted_billing_address();
                    $billing_address = esc_html(preg_replace('#<br\s* /?>#i', ', ', $billing_address));

                    $shipping_address = $billing_address;

                }
                $custom_fields[] = array(
                    'display_name' => 'Shipping Address',
                    'variable_name' => 'shipping_address',
                    'value' => $shipping_address,
                );

            }

        }

        return $custom_fields;
    }


    /**
     * Checks if WC version is less than passed in version.
     *
     * @param string $version Version to check against.
     *
     * @return bool
     */
    public function is_wc_lt($version)
    {
        return version_compare(WC_VERSION, $version, '<');
    }

    /** * Checks if autocomplete order
     * is enabled for the payment method. * * @param WC_Order $order Order object. *
     * @return bool * @since 5.7
     */
    protected function
    is_autocomplete_order_enabled($order)
    {
        $autocomplete_order = false;
        $payment_method = $order->get_payment_method();

        $siliconpay_app_settings = get_option('woocommerce_' . $payment_method .
            '_settings');

        if (isset($siliconpay_app_settings['autocomplete_order']) && 'yes' ===
            $siliconpay_app_settings['autocomplete_order']) {
            $autocomplete_order = true;
        }

        return $autocomplete_order;
    }

    /**
     * Retrieve the payment channels configured for the gateway
     *
     * @param WC_Order $order Order object.
     * @return array
     * @since 5.7
     */
    protected function get_gateway_payment_channels($order)
    {

        $payment_method = $order->get_payment_method();

        if ('siliconpay' === $payment_method) {
            return array();
        }

        $payment_channels = $this->payment_channels;

        if (empty($payment_channels)) {
            $payment_channels = array('card');
        }

        return $payment_channels;
    }

}