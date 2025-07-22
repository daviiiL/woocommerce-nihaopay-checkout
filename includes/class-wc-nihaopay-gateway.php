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

    // checks if the store region is supported by NihaoPay
    if (!$this->is_required_currencies_supported()) {
      $this->enabled = false;
    };

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

    $this->gateway_url = $this->mode === "live" ? "https://api.nihaopay.com/v1.2/transactions/securepay" : "https://apitest.nihaopay.com/v1.2/transactions/securepay";
    $this->notify_url = add_query_arg("wc-api", "wc_nihaopay", home_url("/"));

    $this->is_in_wechat_app = $this->is_in_wechat_app();
    $this->is_mobile = $this->is_mobile();

    //whether if the checkout is block (js/react) or classic (php)
    $this->is_checkout_block = $this->is_checkout_block();

    add_action("woocommerce_receipt_" . $this->id, [$this, "receipt_page"]);
    add_action("woocommerce_update_options_payment_gateways_" . $this->id, [
      $this,
      "process_admin_options",
    ]);

    add_action("woocommerce_api_wc_nihaopay", [$this, "check_ipn_response"]);
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

  /**
   * Check if this gateway is available and suports the user's country and currency
   */
  private function is_required_currencies_supported()
  {
    if (in_array(get_option('woocommerce_currency'), ['USD', 'GBP', 'HKD', 'JPY', 'EUR', 'CAD', 'CNY']))
      return true;

    return false;
  }

  private function isWechatPayEnabled()
  {
    return
      null !== $this->get_option("enable_wechatpay") &&
      $this->get_option("enable_wechatpay") === "yes";
  }

  private function isAliPayEnabled()
  {
    return
      null !== $this->get_option("enable_alipay") &&
      $this->get_option("enable_alipay") === "yes";
  }

  private function isUnionPayEnabled()
  {
    return
      null !== $this->get_option("enable_unionpay") &&
      $this->get_option("enable_unionpay") === "yes";
  }
  /* for classic checkout */
  function payment_fields()
  {
    global $woocommerce;
  ?>
    <fieldset>
      <legend class="payment-method-title"><label>Method of payment<span class="required">*</span></label></legend>
      <ul class="wc_payment_methods payment_methods methods">
        <?php if ($this->isAliPayEnabled()) : ?>
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
        <?php if ($this->isWechatPayEnabled()) : ?>
          <li class="wc_payment_method">
            <input id="nihaopay_pay_method_wechatpay"
              class="input-radio"
              name="vendor"
              value="wechatpay"
              data-order_button_text=""
              type="radio"
              required
              <?php echo !$this->isAliPayEnabled() ?  'checked=\"checked\"' : ""; ?>>
            <label for="nihaopay_pay_method_wechatpay">WeChatPay</label>
          </li>
        <?php endif; ?>
        <?php if ($this->isUnionPayEnabled()) : ?>
          <li class="wc_payment_method">
            <input id="nihaopay_pay_method_unionpay"
              class="input-radio"
              name="vendor"
              value="unionpay"
              data-order_button_text=""
              type="radio"
              required
              <?php echo !($this->isWechatPayEnabled() && $this->isAliPayEnabled()) ? 'checked=\"checked\"' : ""; ?>>
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

  function encode_chinese_chars($url)
  {
    // 使用正则匹配多语言字符，并对其进行编码
    return preg_replace_callback(
      '/[\p{L}\p{M}]+/u', // 匹配所有语言的字母和变音符号
      function ($matches) {
        return urlencode($matches[0]);
      },
      $url
    );
  }

  function is_checkout_block()
  {
    return \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::is_checkout_block_default();
  }

  function is_in_wechat_app()
  {
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
      return true;
    }
    return false;
  }

  function is_mobile()
  {
    $useragent = $_SERVER['HTTP_USER_AGENT'];
    return (bool)(
      preg_match(
        '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',
        $useragent
      )
      || preg_match(
        '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',
        substr($useragent, 0, 4)
      )
    );
  }

  function process_payment($order_id)
  {
    global $woocommerce;

    $order = new WC_Order($order_id);

    $time_stamp = date("YmdHis");
    $orderid = $time_stamp . "-" . $order_id;

    $nhp_arg[] = array();

    $mark_currency = get_option('woocommerce_currency');

    $nhp_arg['currency'] = $this->currency;

    if ($mark_currency == 'CNY') {
      $nhp_arg['rmb_amount'] = intval($order->order_total * 100);
    } else {
      if ($mark_currency != 'JPY') {
        $nhp_arg['amount'] = intval($order->order_total * 100);
      } else {
        $nhp_arg['amount'] = intval($order->order_total);
      }
    }

    $nhp_arg['ipn_url'] = $this->notify_url;
    $nhp_arg['callback_url'] = $this->encode_chinese_chars($order->get_checkout_order_received_url());
    $nhp_arg['reference'] = $orderid;
    $nhp_arg['note'] = $order_id;

    // if block checkout / classic checkout 
    if ($this->is_checkout_block()) {
      $vendor = WC()->checkout->get_value('vendor') ? WC()->checkout->get_value('vendor') : "";
    } else {
      $vendor = isset($_POST['vendor']) ? $_POST['vendor'] : "";
    }

    if ($vendor === 'alipay' && !$this->isAliPayEnabled()) {
      $woocommerce->add_error(__('AliPay is not enabled.', 'woocommerce'));
      return;
    } else if ($vendor === 'wechatpay' && !$this->isWechatPayEnabled()) {
      $woocommerce->add_error(__('WeChatPay is not enabled.', 'woocommerce'));
      return;
    } else if ($vendor === 'unionpay' && !$this->isUnionPayEnabled()) {
      $woocommerce->add_error(__('UnionPay is not enabled.', 'woocommerce'));
      return;
    }

    $nhp_arg['vendor'] = $vendor;

    if ($this->is_mobile) {
      $nhp_arg['terminal'] = 'WAP';
    } else {
      $nhp_arg['terminal'] = 'ONLINE';
    }

    if ($_POST['vendor'] === 'wechatpay') {
      $nhp_arg['inWechat'] = $this->is_in_wechat_app ? 'true' : 'false';
    }


    $post_values = "";
    foreach ($nhp_arg as $key => $value) {
      $post_values .= "$key=" . $value . "&";
    }
    $post_values = rtrim($post_values, "& ");

    /* block  */
    /* DEBUG] payment informations{"0":[],"currency":"USD","amount":199,"ipn_url":"http:\\/\\/localhost\\/?wc-api=wc_nihaopay","callback_url":"http:\\/\\/localhost\\/checkout\\/order-received\\/14\\/?key=wc_order_haPMEWYYMrIaf","reference":"20250722194452-14","note":14,"vendor":"","terminal":"ONLINE"}, referer: http://localhost/checkout/ */
    /**/

    /*    classic  */
    /* [DEBUG] payment informations{"0":[],"currency":"USD","amount":199,"ipn_url":"http:\\/\\/localhost\\/?wc-api=wc_nihaopay","callback_url":"http:\\/\\/localhost\\/checkout\\/order-received\\/18\\/?key=wc_order_l4D3wHVbM5mR6","reference":"20250722194822-18","note":18,"vendor":"alipay","terminal":"ONLINE"}, referer: http://localhost/checkout/ */
    /**/

    error_log("-----------------------------------------------------");
    error_log("[DEBUG] payment informations" . json_encode($nhp_arg));

    $response = wp_remote_post($this->gateway_url, array(
      'body' => $post_values,
      'method' => 'POST',
      'headers' => array('Content-Type' => 'application/x-www-form-urlencoded', 'Authorization' => 'Bearer ' . $this->token),
      'sslverify' => FALSE
    ));

    if (!is_wp_error($response)) {
      $resp = $response['body'];
      $res = esc_attr($resp);
      $woocommerce->session->set($order_id, $res);
      $redirect = add_query_arg('orderId', $order_id, home_url('/nihaopay-redirect/'));
      return array(
        'result'   => 'success',
        'redirect'  => $redirect
      );
    } else {
      $woocommerce->add_error(__('Gateway Error.', 'woocommerce'));
    }
  }

  /**
   * Generate the nihaopayserver button link
   **/
  public function generate_nihaopay_form($order_id)
  {
    global $woocommerce;
    $order = new WC_Order($order_id);

    wc_enqueue_js('
					jQuery("body").block({
							message: "<img src="' . esc_url($woocommerce->plugin_url()) . '/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" style=\"float:left; margin-right: 10px;\" />' . __('Thank you for your order. We are now redirecting you to verify your card.', 'woothemes') . '",
							overlayCSS:
							{
								background: "#fff",
								opacity: 0.6
							},
							css: {
						        padding:        20,
						        textAlign:      "center",
						        color:          "#555",
						        border:         "3px solid #aaa",
						        backgroundColor:"#fff",
						        cursor:         "wait",
						        lineHeight:		"32px"
						    }
						});
					jQuery("#submit_nihaopay_payment_form").click();
				');

    return '<form action="' . esc_url(get_transient('nihaopay_next_url')) . '" method="post" id="nihaopay_payment_form">
						<input type="submit" class="button alt" id="submit_nihaopay_payment_form" value="' . __('Submit', 'woothemes') . '" /> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Cancel order', 'woothemes') . '</a>
					</form>';
  }

  /**
   * receipt_page
   **/
  function receipt_page($order)
  {
    global $woocommerce;
    echo '<p>' . __('Thank you for your order.', 'woothemes') . '</p>';

    echo $this->generate_nihaopay_form($order);
  }

  function check_ipn_response()
  {
    global $woocommerce;
    @ob_clean();
    $note = $_REQUEST['note'];
    $status = $_REQUEST['status'];

    $wc_order   = new WC_Order(absint($note));

    if ($status == 'success') {
      $wc_order->payment_complete();
      $woocommerce->cart->empty_cart();
      wp_redirect($this->get_return_url($wc_order));
      exit;
    } else {
      wp_die("Payment failed. Please try again.");
    }
  }
}
