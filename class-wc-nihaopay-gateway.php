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
    public function __construct()
    {
        $this->icon = apply_filters("woocommerce_nihaopay_gateway_icon", "");
        // has_fields shows payment fields on checkout
        $this->has_fields = false;
        $this->method_title = "NihaoPay Checkout";
        $this->method_description = "Enables WechatPay, AliPay, and UnionPay";
        // TODO: implement function !!!
        $this->init_form_fields();
        /* $this->init_settings(); */

        /* $this->title = $this->get_option("title"); */
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            "enabled" => [
                "title" => __("Enable/Disable", "woocommerce-nihaopay-gateway"),
                "type" => "checkbox",
                "label" => __(
                    "Enable NihaoPay Checkout",
                    "woocommerce-nihaopay-gateway",
                ),
            ],
            "title" => [
                "title" => __("Title", "woocommerce-nihaopay-gateway"),
                "type" => "text",
                "description" => __(
                    "This sets the title displayed during checkout",
                    "woocommerce-nihaopay-gateway",
                ),
                "default" => __(
                    "NihaoPay Checkout",
                    "woocommerce-nihaopay-gateway",
                ),
                "desc_tip" => true,
            ],
            "default" => "yes",
        ];
    }
}
