<?php

/*
Plugin Name: Sign Customiser
Plugin URI: https://signcustomiser.com
Version: 1.0.0
Requires Plugins: woocommerce
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Description: Easily integrate add-to-cart functionality from your Sign Customiser account with WooCommerce.
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

if (!function_exists('WP_Filesystem')) {
  require_once(ABSPATH . 'wp-admin/includes/file.php');
}

WP_Filesystem();

function spcwp_product_callback($request) {
  $data = $request['product'];

  $product = new WC_Product();

  $product->set_name(wc_trim_string($data['text'], 20));
  $product->set_price(floatval($data['price']));
  $product->set_regular_price(floatval($data['price']));
  $product->set_description($data['description']);

  if (!empty($data['productImage'])) {
    $upload_dir = wp_upload_dir();
    $image_data = base64_decode($data['productImage']);
    $filename = $upload_dir['path'] . '/sign-customiser/' . uniqid() . '.png';
    $fs = new WP_Filesystem_Direct( true );
    $fs->put_contents($filename, $image_data);

    $filetype = wp_check_filetype(basename($filename), null);
    $attachment = [
      'post_mime_type' => $filetype['type'],
      'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
      'post_content' => '',
      'post_status' => 'inherit',
    ];
    $attach_id = wp_insert_attachment($attachment, $filename);

    $product->set_image_id($attach_id);
  }

  $id = $product->save();

  wp_set_object_terms($id, 'SignCustomiser', 'product_tag');

  return rest_ensure_response(['product_id' => $id]);
}

add_action('rest_api_init', function () {
  register_rest_route('spc', '/products', [
    'methods' => WP_REST_Server::CREATABLE,
    'callback' => 'spcwp_product_callback',
  ]);
});
