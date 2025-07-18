import { sprintf, __ } from "@wordpress/i18n";
import { registerPaymentMethod } from "@woocommerce/blocks-registry";
import { decodeEntities } from "@wordpress/html-entities";
import { getSetting } from "@woocommerce/settings";

const settings = getSetting("nihaopay_data", {});

const defaultLabel = __("NihaoPay Checkout", "woo-gutenberg-products-block");

const label = decodeEntities(settings.title) || defaultLabel;
/**
 * Content component
 */
const Content = () => {
  return decodeEntities(settings.description || "");
};
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = (props) => {
  const { PaymentMethodLabel } = props.components;
  return <PaymentMethodLabel text={label} />;
};

/**
 * Dummy payment method config object.
 */
const NihaoPay = {
  name: "nihaopay",
  label: <Label />,
  content: <Content />,
  edit: <Content />,
  canMakePayment: () => true,
  ariaLabel: label,
  supports: {
    features: settings.supports,
  },
};

registerPaymentMethod(NihaoPay);
