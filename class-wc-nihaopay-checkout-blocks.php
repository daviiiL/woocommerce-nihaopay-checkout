<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * NihaoPay Checkout Blocks Integrations
 *
 * @since 1.0.0
 */

final class WC_Nihaopay_Checkout_Blocks_Support extends
    AbstractPaymentMethodType
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
    protected $name = "nihaopay";

    public function initialize()
    {
        $this->settings = get_option("wc_nihaopay_settings", []);
        $gateways = WC()->payment_gateways->payment_gateways();
        $this->gateway = $gateways[$this->name];
    }
}
