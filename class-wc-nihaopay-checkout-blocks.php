<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * NihaoPay Checkout Blocks Integrations
 *
 * @since 1.0.0
 */
final class WC_Nihaopay_Checkout_Blocks_Support extends AbstractPaymentMethodType
{
  /**
   * Gateway instance
   *
   * @var WC_Nihaopay_Gateway
   */
  private $gateway;

  /**
   * Payment method name
   *
   * @var string
   */
  protected $name = 'nihaopay';

  public function initialize()
  {
    $this->settings = get_option("woocommerce_nihaopay_settings", []);
    $gateways = WC()->payment_gateways->payment_gateways();
    $this->gateway = $gateways[$this->name];
  }

  public function is_active()
  {
    return $this->gateway->is_available();
  }

  public function get_payment_method_script_handles()
  {
    $script_path = '/assets/js/frontend/blocks.js';
    $script_asset_path = WC_Nihaopay_Checkout::plugin_abspath() . "assets/js/frontend/blocks.asset.php";
    $script_asset = file_exists($script_asset_path) ? require($script_asset_path) : [
      "dependencies" => [],
      "version" => "1.0.0"
    ];

    $script_url = WC_Nihaopay_Checkout::plugin_url() . $script_path;

    wp_register_script(
      "wc-nihaopay-checkout-blocks",
      $script_url,
      $script_asset['dependencies'],
      $script_asset['version'],
      true
    );

    if (function_exists("wp_set_script_translations")) {
      wp_set_script_translations("wc-nihaopay-checkout-blocks", "woocommerce-nihaopay-checkout", WC_Nihaopay_Checkout::plugin_abspath() . 'languages/');

      return ['wc-nihaopay-checkout-blocks'];
    }
  }

  //TODO: pass more data into the js blocks script
  public function get_payment_method_data()
  {
    return [
      "title" => $this->get_setting('title'),
      "enable_wechatpay" => $this->get_setting("enable_wechatpay"),
      "enable_alipay" => $this->get_setting("enable_alipay"),
      "enable_unionpay" => $this->get_setting("enable_unionpay")
    ];
  }
}
