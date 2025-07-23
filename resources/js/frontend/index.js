import { sprintf, __ } from "@wordpress/i18n";
import { registerPaymentMethod } from "@woocommerce/blocks-registry";
import { decodeEntities } from "@wordpress/html-entities";
import { getSetting } from "@woocommerce/settings";
import { useState, useEffect } from "@wordpress/element";
import alipayLogo from "../../images/alipay_logo.png";
import unionpayLogo from "../../images/unionpay_logo.png";
import wechatpayLogo from "../../images/wechatpay_logo.png";

const settings = getSetting("nihaopay_data", {});

const defaultLabel = __("NihaoPay Checkout", "woo-gutenberg-products-block");

const label = decodeEntities(settings.title) || defaultLabel;

const isWechatPayEnabled = decodeEntities(settings.enable_wechatpay) === "yes";
const isAlipayEnabled = decodeEntities(settings.enable_alipay) === "yes";
const isUnionPayEnabled = decodeEntities(settings.enable_unionpay) === "yes";

/**
 * Content component
 */
const Content = ({ eventRegistration, emitResponse }) => {
  const [selectedOption, setSelectedOption] = useState("");
  const { onPaymentProcessing } = eventRegistration;

  const vendorMap = {
    wechatpay: isWechatPayEnabled,
    alipay: isAlipayEnabled,
    unionpay: isUnionPayEnabled,
  };

  useEffect(() => {
    const unsubscribe = onPaymentProcessing(async () => {
      const vendor = selectedOption?.toLowerCase() || "";

      if (!vendor || !vendorMap[vendor])
        return {
          type: emitResponse.responseTypes.ERROR,
          message: `Please select a payment method`,
        };

      return {
        type: emitResponse.responseTypes.SUCCESS,
        meta: {
          paymentMethodData: {
            vendor,
          },
        },
      };
    });

    return () => unsubscribe();
  }, [
    emitResponse.responseTypes.ERROR,
    emitResponse.responseTypes.SUCCESS,
    onPaymentProcessing,
    selectedOption,
  ]);

  // set default option
  useEffect(() => {
    if (isWechatPayEnabled) setSelectedOption("WechatPay");
    else if (isAlipayEnabled) setSelectedOption("AliPay");
    else setSelectedOption("UnionPay");
  }, [
    setSelectedOption,
    isWechatPayEnabled,
    isAlipayEnabled,
    isUnionPayEnabled,
  ]);

  const genVendorRadioButtonGroup = (vendor) => (
    <>
      <input
        name="vendor"
        type="radio"
        value={vendor}
        checked={selectedOption === pp}
      />
      <p>{vendor}</p>
    </>
  );

  return (
    <div
      style={{
        display: "flex",
        flexDirection: "row",
        alignItems: "flex-start",
      }}
      onChange={(e) => {
        setSelectedOption(e.target.value);
      }}
    >
      <fieldset
        style={{
          flexGrow: "1",
        }}
      >
        <legend>Select method of payment*</legend>
        <div style={{ display: "flex", gap: "20px" }}>
          {isWechatPayEnabled && genVendorRadioButtonGroup("WechatPay")}
        </div>
        <div style={{ display: "flex", gap: "20px" }}>
          {isAlipayEnabled && genVendorRadioButtonGroup("AliPay")}
        </div>
        <div style={{ display: "flex", gap: "20px" }}>
          {isUnionPayEnabled && genVendorRadioButtonGroup("UnionPay")}
        </div>
      </fieldset>
    </div>
  );
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = (props) => {
  const logoMap = {
    wechatpay: wechatpayLogo,
    alipay: alipayLogo,
    unionpay: unionpayLogo,
  };

  const genVendorIconContainer = (vendor) => {
    return (
      <div
        style={{
          height: "24px",
          maxHeight: "24px",
          width: "fit-content",
        }}
      >
        <img
          style={{
            height: "100%",
            objectFit: "cover",
          }}
          src={logoMap[pp.toLowerCase()]}
          alt={`${vendor} logo`}
        />
      </div>
    );
  };

  const { PaymentMethodLabel } = props.components;
  return (
    <div
      style={{
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
        width: "100%",
      }}
    >
      <PaymentMethodLabel text={label} />
      <div style={{ display: "flex", gap: "5px" }}>
        {["WechatPay", "AliPay", "UnionPay"].map((vendor) =>
          genVendorIconContainer(vendor),
        )}
      </div>
    </div>
  );
};

/**
 * NihaoPay Checkout Config Object
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
