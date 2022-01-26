<?php
/**
 * Plugin Name: SiliconPay WooCommerce Payment Gateway
 * Plugin URI: https://silicon-pay.com
 * Description: WooCommerce payment gateway for SiliconPay
 * Version: 1.0.0
 * Author: S.A.V.I.O.U.R
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * WC requires at least: 3.0.0
 * WC tested up to: 5.8
 * Text Domain: woo-siliconpay
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WC_SILICONPAY_WOO_MAIN_FILE', __FILE__);
define('WC_SILICONPAY_WOO_URL', untrailingslashit(plugins_url('/', __FILE__)));

define('WC_SILICONPAY_WOO_VERSION', '1.0.0');

/**
 * Initialize SiliconPay WooCommerce payment gateway.
 */
function tbz_wc_siliconpay_app_init()
{

    load_plugin_textdomain('woo-siliconpay', false, plugin_basename(dirname(__FILE__)) . '/languages');

    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'tbz_wc_siliconpay_app_wc_missing_notice');
        return;
    }

    add_action('admin_notices', 'tbz_wc_siliconpay_app_testmode_notice');

    require_once dirname(__FILE__) . '/includes/class-wc-gateway-silicon-pay.php';

    add_filter('woocommerce_payment_gateways', 'tbz_wc_add_siliconpay_app_gateway', 99);
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'tbz_woo_siliconpay_app_plugin_action_links');

}

add_action('plugins_loaded', 'tbz_wc_siliconpay_app_init', 99);

/**
 * Add Settings link to the plugin entry in the plugins' menu.
 *
 * @param array $links Plugin action links.
 *
 * @return array
 **/
function tbz_woo_siliconpay_app_plugin_action_links($links)
{

    $settings_link = array(
        'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=siliconpay') . '" title="' . __('View SiliconPay WooCommerce Settings', 'woo-siliconpay') . '">' . __('Settings', 'woo-siliconpay') . '</a>',
    );

    return array_merge($settings_link, $links);

}

/**
 * Add SiliconPay Gateway to WooCommerce.
 *
 * @param array $methods WooCommerce's payment gateways methods.
 *
 * @return array
 */
function tbz_wc_add_siliconpay_app_gateway($methods)
{
    $methods[] = 'WC_Gateway_SiliconPay';


    return $methods;

}

/**
 * Display a notice if WooCommerce is not installed
 */
function tbz_wc_siliconpay_app_wc_missing_notice()
{
    echo '<div class="error"><p><strong>' . sprintf(__('SiliconPay requires WooCommerce to be installed and active. Click %s to install WooCommerce.', 'woo-siliconpay'), '<a href="' . admin_url('plugin-install.php?tab=plugin-information&plugin=woocommerce&TB_iframe=true&width=772&height=539') . '" class="thickbox open-plugin-details-modal">here</a>') . '</strong></p></div>';
}

/**
 * Display the test mode notice.
 **/
function tbz_wc_siliconpay_app_testmode_notice()
{

    if (!current_user_can('manage_options')) {
        return;
    }

    $siliconpay_app_settings = get_option('woocommerce_siliconpay_app_settings');
    $test_mode = isset($siliconpay_app_settings['testmode']) ? $siliconpay_app_settings['testmode'] : '';

    if ('yes' === $test_mode) {
        /* translators: 1. SiliconPay settings page URL link. */
        echo '<div class="error"><p>' . sprintf(__('SiliconPay test mode is still enabled, Click <strong><a href="%s">here</a></strong> to disable it when you want to start accepting live payment on your site.', 'woo-siliconpay'), esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=siliconpay'))) . '</p></div>';
    }
}