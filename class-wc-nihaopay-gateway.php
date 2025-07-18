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
        $this->icon = apply_filters("woocommerce_nihaopay_gateway_icon", "");
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
        $this->enable_wechatpay = $this->get_option("enable_wechat");
        $this->enable_unionpay = $this->get_option("enable_unionpay");

        add_action("woocommerce_update_options_payment_gateways_" . $this->id, [
            $this,
            "process_admin_options",
        ]);
        //TODO: add block support
    }

    public function admin_options()
    {
        ?>
    <h2><?php esc_html_e(
        "WooCommerce NihaoPay Checkout",
        "woocommerce-nihaopay-gateway",
    ); ?></h2>
    <table class="form-table">
        <?php $this->generate_settings_html(); ?>
    </table>
    <?php
    }

    public function payment_fields()
    {
        ?>
        <fieldset>
               <legend class="payment-method-title">
                   <label><?php esc_html_e(
                       "Method of payment",
                       "woocommerce-nihaopay-checkout",
                   ); ?><span class="required">*</span></label>
               </legend>
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
}
