<?php

/**
 *
 * @author NihaoPay <david.liu@nihaopay.com>
 * @package WooCommerce NihaoPay Gateway
 * @since 1.0.0
 */

if (!defined("ABSPATH")) {
  exit();
}

/**
 * NihaoPay Gateway
 *
 * @class WC_Nihaopay_Gateway
 * @version 1.0.0
 */

class WC_Nihaopay_Gateway extends WC_Payment_Gateway
{
  /**
   * plugin id
   * @var string
   *
   */
  public $id = "nihaopay";

  public function __construct()
  {
    // has_fields shows payment fields on checkout
    $this->has_fields = true;
    $this->method_title = "NihaoPay Checkout";
    $this->method_description = "Enables WechatPay, AliPay, and UnionPay";
    $this->init_form_fields();
    $this->init_settings();

    $this->title = $this->get_option("title");
    $this->token = $this->get_option("token");
    $this->currency = $this->get_option("currency");
    $this->mode = $this->get_option("mode");
    $this->enable_alipay = $this->get_option("enable_alipay");
    $this->enable_wechatpay = $this->get_option("enable_wechatpay");
    $this->enable_unionpay = $this->get_option("enable_unionpay");

    $plugin_dir = WC_Nihaopay_Checkout::plugin_url();
    $this->wechatpay_icon = apply_filters('woocommerce_nihaopay_wechatpay_icon', '' . $plugin_dir . '/resources/images/wechatpay_logo.png');
    $this->alipay_icon = apply_filters('woocommerce_nihaopay_alipay_icon', '' . $plugin_dir . '/resources/images/alipay_logo.png');
    $this->unionpay_icon = apply_filters('woocommerce_nihaopay_unionpay_icon', '' . $plugin_dir . '/resources/images/unionpay_logo.png');

    add_action("woocommerce_update_options_payment_gateways_" . $this->id, [
      $this,
      "process_admin_options",
    ]);
  }

  public function admin_options()
  {
?>
    <h2><?php esc_html_e(
          "WooCommerce NihaoPay Checkout",
          "woocommerce-nihaopay-checkout",
        ); ?></h2>
    <table class="form-table">
      <?php $this->generate_settings_html(); ?>
    </table>
  <?php
  }


  function get_icon()
  {
    $icon = '';

    if ($this->wechatpay_icon) {
      $icon .= '<img src="' . $this->force_ssl($this->wechatpay_icon) . '" alt="' . $this->title . '" width="29" height="26" style="user-select: none;" />';
    }

    if ($this->alipay_icon) {
      $icon .= '<img src="' . $this->force_ssl($this->alipay_icon) . '" alt="' . $this->title . '" width="26" height="26" style="user-select: none;" />';
    }

    if ($this->unionpay_icon) {
      $icon .= '<img src="' . $this->force_ssl($this->unionpay_icon) . '" alt="' . $this->title . '" width="42" height="26" style="user-select: none;" />';
    }

    if ($icon) {
      $icon = '<div class="nihaopay-icons" style="display: inline-flex; gap: 8px; align-items: center; width:min-content; ">' . $icon . '</div>';
    }

    return apply_filters('woocommerce_gateway_icons', $icon, $this->id);
  }

  function payment_fields()
  {
    $isWechatPayEnabled =
      null !== $this->get_option("enable_wechatpay") &&
      $this->get_option("enable_wechatpay") === "yes";

    $isAliPayEnabled =
      null !== $this->get_option("enable_alipay") &&
      $this->get_option("enable_alipay") === "yes";

    $isUnionPayEnabled =
      null !== $this->get_option("enable_unionpay") &&
      $this->get_option("enable_unionpay") === "yes";

    global $woocommerce;
  ?>
    <fieldset>
      <legend class="payment-method-title"><label>Method of payment<span class="required">*</span></label></legend>
      <ul class="wc_payment_methods payment_methods methods">
        <?php if ($isAliPayEnabled) : ?>
          <li class="wc_payment_method">
            <input id="nihaopay_pay_method_alipay"
              class="input-radio"
              name="vendor"
              checked="checked"
              value="alipay"
              data-order_button_text="" type="radio" required>
            <label for="nihaopay_pay_method_alipay">AliPay</label>
          </li>
        <?php endif; ?>
        <?php if ($isWechatPayEnabled) : ?>
          <li class="wc_payment_method">
            <input id="nihaopay_pay_method_wechatpay"
              class="input-radio"
              name="vendor"
              value="wechatpay"
              data-order_button_text=""
              type="radio"
              required
              <?php echo !$isAliPayEnabled ?  'checked=\"checked\"' : ""; ?>>
            <label for="nihaopay_pay_method_wechatpay">WeChatPay</label>
          </li>
        <?php endif; ?>
        <?php if ($isUnionPayEnabled) : ?>
          <li class="wc_payment_method">
            <input id="nihaopay_pay_method_unionpay"
              class="input-radio"
              name="vendor"
              value="unionpay"
              data-order_button_text=""
              type="radio"
              required
              <?php echo !($isWechatPayEnabled && $isAliPayEnabled) ? 'checked=\"checked\"' : ""; ?>>
            <label for="nihaopay_pay_method_unionpay">UnionPay</label>
          </li>
        <?php endif; ?>
      </ul>
      <div class="clear"></div>
    </fieldset>
<?php
  }


  public function init_form_fields()
  {
    $this->form_fields = [
      "enabled" => [
        "title" => __(
          "Enable/Disable",
          "woocommerce-nihaopay-checkout",
        ),
        "type" => "checkbox",
        "label" => __(
          "Enable NihaoPay Checkout",
          "woocommerce-nihaopay-checkout",
        ),
      ],
      "title" => [
        "title" => __("Title", "woocommerce-nihaopay-checkout"),
        "type" => "text",
        "description" => __(
          "This sets the title displayed during checkout",
          "woocommerce-nihaopay-checkout",
        ),
        "default" => __(
          "NihaoPay Checkout",
          "woocommerce-nihaopay-checkout",
        ),
        "desc_tip" => true,
      ],
      "token" => [
        "title" => __("API Token", "woocommerce-nihaopay-checkout"),
        "type" => "text",
        "description" => __(
          "Enter your NihaoPay API Token",
          "woocommerce-nihaopay-checkout",
        ),
        "default" => "",
        "desc_tip" => true,
      ],
      "currency" => [
        "title" => __(
          "Settlement Currency",
          "woocommerce-nihaopay-checkout",
        ),
        "type" => "select",
        "options" => [
          "USD" => "USD",
          "JPY" => "JPY",
          "HKD" => "HKD",
          "EUR" => "EUR",
          "GBP" => "GBP",
          "CAD" => "CAD",
        ],
        "description" => __(
          "Select your preferred settlement currency",
          "woocommerce-nihaopay-checkout",
        ),
        "default" => "USD",
        "desc_tip" => true,
      ],
      "mode" => [
        "title" => __("Plugin Mode", "woocommerce-nihaopay-checkout"),
        "type" => "select",
        "options" => [
          "test" => "Test Mode",
          "live" => "Live Mode",
        ],
        "default" => "live",
        "description" => __(
          "Switch NihaoPay plugin to test/live mode",
          "woocommerce-nihaopay-checkout",
        ),
        "desc_tip" => true,
      ],
      "enable_alipay" => [
        "title" => __("Enable AliPay", "woocommerce-nihaopay-checkout"),
        "type" => "checkbox",
        "label" => __(
          "Enable AliPay payment method",
          "woocommerce-nihaopay-checkout",
        ),
        "default" => "yes",
        "desc_tip" => true,
      ],
      "enable_wechatpay" => [
        "title" => __(
          "Enable WeChatPay",
          "woocommerce-nihaopay-checkout",
        ),
        "type" => "checkbox",
        "label" => __(
          "Enable WeChatPay payment method",
          "woocommerce-nihaopay-checkout",
        ),
        "default" => "yes",
        "desc_tip" => true,
      ],
      "enable_unionpay" => [
        "title" => __(
          "Enable UnionPay",
          "woocommerce-nihaopay-checkout",
        ),
        "type" => "checkbox",
        "label" => __(
          "Enable UnionPay payment method",
          "woocommerce-nihaopay-checkout",
        ),
        "default" => "yes",
        "desc_tip" => true,
      ],
    ];
  }

  private function force_ssl($url)
  {
    if ('yes' == get_option('woocommerce_force_ssl_checkout')) {
      $url = str_replace('http:', 'https:', $url);
    }
    return $url;
  }
}
