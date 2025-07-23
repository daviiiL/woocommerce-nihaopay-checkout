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

    // add session data get action
    add_action(
      'wp_ajax_get_session_data',
      function () {
        global $woocommerce;
        $key_to_get = isset($_GET['orderId']) ? sanitize_text_field($_GET['orderId']) : '';
        $data = $woocommerce->session->get($key_to_get);
        wp_send_json([$key_to_get => $data]);
        wp_die();
      }
    );
    add_action(
      'wp_ajax_nopriv_get_session_data',
      function () {
        global $woocommerce;
        $key_to_get = isset($_GET['orderId']) ? sanitize_text_field($_GET['orderId']) : '';
        $data = $woocommerce->session->get($key_to_get);
        wp_send_json([$key_to_get => $data]);
        wp_die();
      }
    );

    add_action('init', function () {
      add_rewrite_rule('^nihaopay-redirect/?$', 'index.php?nihaopay_redirect=1', 'top');
      add_rewrite_tag('%nihaopay_redirect%', '1');
    });

    add_action('template_redirect', [__CLASS__, 'handle_redirect_template']);
  }

  function add_rewrite()
  {
    add_rewrite_rule('^nihaopay-redirect/?$', 'index.php?nihaopay_redirect=1', 'top');
    add_rewrite_tag('%nihaopay_redirect%', '1');
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

  public static function handle_redirect_template()
  {
    if (get_query_var('nihaopay_redirect')) {
?>
      <!DOCTYPE html>
      <html>

      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>loading</title>
      </head>

      <body>
        <script type="text/javascript">
          function decode(str) {
            let txt = document.createElement("textarea");
            txt.innerHTML = str;
            return txt.value;
          }

          function contentDecode(res) {
            var content = atob(res);
            return decode(content);
          }

          function getUrlParam(key) {
            var args = {};
            var pairs = location.search.substring(1).split('&');
            for (var i = 0; i < pairs.length; i++) {
              var pos = pairs[i].indexOf('=');
              if (pos === -1) {
                continue;
              }
              args[pairs[i].substring(0, pos)] = decodeURIComponent(pairs[i].substring(pos + 1));
            }
            return args[key] === undefined ? '' : args[key];
          }

          function getUrlParams() {
            var args = {};
            var pairs = location.search.substring(1).split('&');
            for (var i = 0; i < pairs.length; i++) {
              var pos = pairs[i].indexOf('=');
              if (pos === -1) {
                continue;
              }
              args[pairs[i].substring(0, pos)] = decodeURIComponent(pairs[i].substring(pos + 1));
            }
            return args;
          }

          function getSessionData(orderId, cb) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/wp-admin/admin-ajax.php?action=get_session_data&orderId=' + orderId, true);
            xhr.onreadystatechange = function() {
              if (xhr.readyState === 4 && xhr.status === 200) {
                var responseData = JSON.parse(xhr.responseText);
                var dataForKey = responseData[orderId];
                cb(dataForKey);
              } else {
                cb(null);
              }
            };
            xhr.send();
          }

          function runScripts(element) {
            var list, scripts, index;
            list = element.getElementsByTagName("script");
            scripts = [];
            for (index = 0; index < list.length; ++index) {
              scripts[index] = list[index];
            }
            list = undefined;
            continueLoading();

            function continueLoading() {
              var script, newscript;
              while (scripts.length) {
                script = scripts[0];
                script.parentNode.removeChild(script);
                scripts.splice(0, 1);
                newscript = document.createElement('script');
                if (script.src) {
                  newscript.onerror = continueLoadingOnError;
                  newscript.onload = continueLoadingOnLoad;
                  newscript.onreadystatechange = continueLoadingOnReady;
                  newscript.src = script.src;
                } else {
                  newscript.text = script.text;
                }
                document.documentElement.appendChild(newscript);
                if (script.src) {
                  return;
                }
              }
              newscript = undefined;

              function continueLoadingOnLoad() {
                if (this === newscript) {
                  continueLoading();
                }
              }

              function continueLoadingOnError() {
                if (this === newscript) {
                  continueLoading();
                }
              }

              function continueLoadingOnReady() {
                if (this === newscript && this.readyState === "complete") {
                  continueLoading();
                }
              }
            }
          }

          function getContent() {
            var params = getUrlParams();
            if ('orderId' in params) {
              var orderId = params['orderId'];
              getSessionData(orderId, function(res) {
                if (res) {
                  document.documentElement.innerHTML = decode(res);
                  runScripts(document.documentElement);
                } else {
                  document.documentElement.innerHTML = '<h1>cant\'t get order info by orderId</h1>';
                  runScripts(document.documentElement);
                }
              });
            } else if ('res' in params) {
              document.documentElement.innerHTML = contentDecode(params['res']);
              runScripts(document.documentElement);
            } else {
              document.documentElement.innerHTML = '<h1>cant\'t get order info by orderId</h1>';
              runScripts(document.documentElement);
            }
          }
          getContent();
        </script>
      </body>

      </html><?php
              exit;
            }
          }
        }

        WC_Nihaopay_Checkout::init();
