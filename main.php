<?php
/*
Plugin Name: Role Price
Description: Manage Price by Role
Author: Agi
Author URI: https://github.com/ghinearyou/
*/

require_once plugin_dir_path(__FILE__) . 'includes/functions.php';

function read_custom_json_file() {
	$file_path = plugin_dir_path(__FILE__) . '/includes/result.json'; // Adjust the path as needed
	if (!file_exists($file_path)) {
			return new WP_Error('file_not_found', 'The specified JSON file does not exist.');
	}

	$json_data = file_get_contents($file_path);
	$parsed_data = json_decode($json_data, true);

	if (json_last_error() !== JSON_ERROR_NONE) {
			return new WP_Error('json_error', 'Error decoding JSON: ' . json_last_error_msg());
	}

	return $parsed_data;
}

function apply_quantity_discount($cart) {
	$json_data = read_custom_json_file();
	$user = wp_get_current_user();
	$role = $user->roles[0];

  if ($json_data[$role]) {
		if (is_admin() && !defined('DOING_AJAX')) {
			return;
		}

		foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
			$quantity = $cart_item['quantity'];
			$product = $cart_item['data'];
			$regular_price = $product->get_sale_price();
			$sku = $product->get_sku();

			if ($quantity >= 5) {
				if ($json_data[$role][$sku]) {
					$product->set_price($json_data[$role][$sku]);
				}
			}
		}
	}
}

add_action('woocommerce_before_calculate_totals', 'apply_quantity_discount', 10, 1);

// Register activation hook to ensure the plugin is active
function wc_quantity_discount_activate() {
  if (!class_exists('WooCommerce')) {
      deactivate_plugins(plugin_basename(__FILE__));
      wp_die('This plugin requires WooCommerce to be installed and active.');
  }
}
register_activation_hook(__FILE__, 'wc_quantity_discount_activate');
