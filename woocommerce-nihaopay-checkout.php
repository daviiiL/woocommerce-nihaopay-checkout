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
    }

    public static function includes()
    {
        if (class_exists("WC_Payment_Gateway")) {
            require_once "class-wc-nihaopay-gateway.php";
        }
    }

    public static function add_gateway($gateways)
    {
        /* $options = get_option("wc-nihaopay-settings", []); */
        /**/
        /* if (isset($options["hide_for_regular_user"])) { */
        /*     $hide = $options["hide_for_regular_user"]; */
        /* } else { */
        /*     $hide = false; */
        /* } */
        /**/
        /* if (($hide && current_user_can("manage_options")) || !$hide) { */
            $gateways[] = "WC_Nihaopay_Gateway";
        /* } */

        return $gateways;
    }
}

WC_Nihaopay_Checkout::init();
