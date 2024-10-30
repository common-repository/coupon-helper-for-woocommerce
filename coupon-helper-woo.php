<?php
/*
Plugin Name: Coupon Helper for WooCommerce
Description: A plugin to allow WooCommerce couponing system to have a coupon which will make discounts to be equal to the least expensive item on the WooCommerce cart.
Author: Mohit Agarwal
Version: 1.3.6
Author URI: http://agarwalmohit.com
Stable tag: "trunk"
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Coupon Helper for WooCommerce is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Coupon Helper for WooCommerce is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Coupon Helper for WooCommerce. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/




/**
 * @package Coupon Helper for WooCommerce
 * @version 1.3.6
 */


 /* I'd like to configure Woo Commerce on my Wordpress site so that when a coupon is applied, it takes off the cost of the least expensive item.  */
 
 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


function me_chw_add_coupon_type( $items = '' ) {
	 // Received discount types. Add our type.
	if(is_array($items)){
		$items = array_merge($items,array('least_exp'   => __( 'Least expensive item for free', 'ma_woocommerce_coupon' )));
	}
    return $items;
}
add_filter( 'woocommerce_coupon_discount_types', 'me_chw_add_coupon_type' );


function me_chw_widget_enqueue_script() {   
	// Add js to admin backend.
    wp_enqueue_script( 'custom-script-chw', plugins_url( '/js/woo_coupon.js', __FILE__ ),array( 'jquery' ), '1.0' );
}
add_action('admin_enqueue_scripts', 'me_chw_widget_enqueue_script');



//function to get coupon amount for "custom_discount"
function  me_chw_woo_coupon_get_discount_amount($discount, $discounting_amount, $cart_item, $single, $coupon) {
        if ($coupon->type == 'least_exp'){ //if $coupon->type == 'fixed_cart' or 'percent' or 'fixed_product' or 'percent_product' The code Works
		
		global $woocommerce;
		
		if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) { 
		foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
				$_product = $values['data'];
				$product_price[] = get_option('woocommerce_tax_display_cart') == 'excl' ? $_product->get_price_excluding_tax() : $_product->get_price_including_tax(); /*Store all product price from cart items in Array */
			}
		}
		$lowestprice = min($product_price); // Lowest Price from array
		
		$cart_item_qty = $woocommerce->cart->get_cart_contents_count();
		if($cart_item_qty>1){
			$discount = $lowestprice / $cart_item_qty;
			return $discount;
		}else {
			// $this->error_message = $this->get_coupon_error( $e->getMessage() );
				return $discount;
            }
            
            } else {
				return $discount;
            }
        }
//add hook to coupon amount hook
add_filter('woocommerce_coupon_get_discount_amount', 'me_chw_woo_coupon_get_discount_amount', 10, 5);




function me_chw_woo_coupon_is_valid( $valid, $coupon ) {
	
		global $woocommerce;
		$cart_item_qty = $woocommerce->cart->get_cart_contents_count();
		 if (($coupon->type == 'least_exp') && ($cart_item_qty<=1)){
			return false;
		}else{
			return $valid;
		}
}
add_filter( 'woocommerce_coupon_is_valid', 'me_chw_woo_coupon_is_valid', 1, 2 );


function me_chw_woo_coupon_is_valid_for_product($valid, $product, $coupon, $values){
    if ( ! $coupon->is_type( array( 'least_exp' ) ) ) {
        return $valid;
    }

		$valid        = false;
		$product_cats = wc_get_product_cat_ids( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() );
		$product_ids  = array( $product->get_id(), $product->get_parent_id() );

		// Specific products get the discount
		if ( sizeof( $coupon->get_product_ids() ) && sizeof( array_intersect( $product_ids, $coupon->get_product_ids() ) ) ) {
			$valid = true;
		}

		// Category discounts
		if ( sizeof( $coupon->get_product_categories() ) && sizeof( array_intersect( $product_cats, $coupon->get_product_categories() ) ) ) {
			$valid = true;
		}

		// No product ids - all items discounted
		if ( ! sizeof( $coupon->get_product_ids() ) && ! sizeof( $coupon->get_product_categories() ) ) {
			$valid = true;
		}

		// Specific product IDs excluded from the discount
		if ( sizeof( $coupon->get_excluded_product_ids() ) && sizeof( array_intersect( $product_ids, $coupon->get_excluded_product_ids() ) ) ) {
			$valid = false;
		}

		// Specific categories excluded from the discount
		if ( sizeof( $coupon->get_excluded_product_categories() ) && sizeof( array_intersect( $product_cats, $coupon->get_excluded_product_categories() ) ) ) {
			$valid = false;
		}

		// Sale Items excluded from discount
		if ( $coupon->get_exclude_sale_items() ) {
			$product_ids_on_sale = wc_get_product_ids_on_sale();

			if ( in_array( $product->get_id(), $product_ids_on_sale, true ) ) {
				$valid = false;
			}
		}
    return $valid;
}

add_filter('woocommerce_coupon_is_valid_for_product', 'me_chw_woo_coupon_is_valid_for_product', 10, 4);

