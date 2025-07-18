<?php

/**
 * Plugin Name: WooCommerce NihaoPay Checkout
 * Plugin URI: https://nihaopay.com/
 * Description: Adds NihaoPay payment method to your WooCommerce site.
 * Version: 1.0.0
 *
 * Author: NihaoPay
 * Author URI: https://nihaopay.com/
 *
 * Text Domain: woocommerce-nihaopay-checkout
 *
 * Requires at least: 4.2
 * Tested up to: 10.0
 */

if (!defined("ABSPATH")) {
  exit();
}

class WC_Nihaopay_Checkout
{
  public static function init()
  {
    add_action("plugins_loaded", [__CLASS__, "includes"], 0);
    add_filter("woocommerce_payment_gateways", [__CLASS__, "add_gateway"]);
    add_action("woocommerce_blocks_loaded", [__CLASS__, "woocommerce_nihaopay_checkout_woocommerce_block_support"]);
  }

  public static function includes()
  {
    if (class_exists("WC_Payment_Gateway")) {
      require_once "includes/class-wc-nihaopay-gateway.php";
    }
  }

  public static function add_gateway($gateways)
  {
    $gateways[] = "WC_Nihaopay_Gateway";
    return $gateways;
  }

  /**
   * Plugin url.
   *
   * @return string
   */
  public static function plugin_url()
  {
    return untrailingslashit(plugins_url('/', __FILE__));
  }

  /**
   * Plugin url.
   *
   * @return string
   */
  public static function plugin_abspath()
  {
    return trailingslashit(plugin_dir_path(__FILE__));
  }

  public static function woocommerce_nihaopay_checkout_woocommerce_block_support()
  {
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
      require_once 'includes/class-wc-nihaopay-checkout-blocks.php';
      add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
          $payment_method_registry->register(new WC_Nihaopay_Checkout_Blocks_Support());
        }
      );
    }
  }
}

WC_Nihaopay_Checkout::init();
