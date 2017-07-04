<?php
/**
 *
 * Plugin Name: Woocommerce Order Datalayer
 * Plugin URI: https://github.com/framedigital/woocommerce-order-datalayer
 * Version: 1.0.0
 * Author: Frame Creative
 * Author URI: https://framecreative.com.au
 * Description: Pushes completed orders into a javascript object named datalayer for Google Tag Manager to use.
 * Requires at least: 4.0
 * Tested up to: 4.8
 *
 *
 * @package Woocommerce Order Datalayer
 * @category Woocommerce
 * @author Frame Creative
*/

class DataLayer
{
    protected static $_instance = null;

    public $dataLayer = [];

    public function __construct()
    {
        if (!$this->hasValidRequirements()) {
            add_action('admin_notices', [ $this, 'showAdminNotice' ]);
            return;
        }
        if (!is_order_received_page()) {
            return;
        }
        $this->setupDataLayer();
        $this->addToWPHead();
    }

    private function hasValidRequirements()
    {
        return class_exists('WooCommerce') && abs(WC()->version) >= 3;
    }

    public function showAdminNotice()
    {
        echo "<p>Woocommerce Order Datalayer requires at least Woocommerce 3.0</p>";
    }

    private function addToWPHead()
    {
        add_action('wp_head', [ $this, 'output' ]);
    }

    public function output()
    {
        if (!empty($this->dataLayer)) {
            $dataLayer = json_encode($this->dataLayer);
            $script = '<script data-cfasync="false" type="text/javascript">dataLayer.push( %s );</script>';
            echo sprintf($script, $dataLayer);
        }
    }

    private function setupDataLayer()
    {
        $orderId = $this->getOrderId();
        $order_key = apply_filters('woocommerce_thankyou_order_key', empty($_GET[ 'key' ]) ? '' : wc_clean($_GET[ 'key' ]));

        if ($orderId > 0) {
            $this->order = new WC_Order($orderId);

            if ($this->order->get_order_key() != $order_key) {
                unset($this->order);
            }
        }


        if (1 == get_post_meta($orderId, '_ga_tracked', true)) {
            unset($this->order);
        }

        if (isset($this->order)) {
            $this->setGeneralOrderObjects();
            $this->setOrderItemsObjects();

            update_post_meta($orderId, '_ga_tracked', 1);
        }
    }

    private function getOrderId()
    {
        $order_id = empty($_GET[ 'order' ]) ? ($GLOBALS[ 'wp' ]->query_vars[ 'order-received' ] ? $GLOBALS[ 'wp' ]->query_vars[ 'order-received' ] : 0) : absint($_GET[ 'order' ]);

        $order_id_filtered = apply_filters('woocommerce_thankyou_order_id', $order_id);
        if ('' != $order_id_filtered) {
            $order_id = $order_id_filtered;
        }
        return $order_id;
    }

    private function setOrderItemsObjects()
    {
        if ($this->order->get_items()) {
            $_products = [];
            $_sumprice = 0;
            $_product_ids = [];

            foreach ($this->order->get_items() as $item) {
                $product     = $item->get_product();
                $product_id  = $product->get_id();
                $product_sku = $product->get_sku();

                $product_categories = get_the_terms($product_id, 'product_cat');

                if ((is_array($product_categories)) && (count($product_categories) > 0)) {
                    $product_cat = array_pop($_product_cats);
                    $product_cat = $product_cat->name;
                } else {
                    $product_cat = '';
                }

                $productId = $product_sku ? $product_sku : $product_id;

                $product_price = $this->order->get_item_total($item);
                $product_data  = [
                    'id'       => $productId,
                    'name'     => $item['name'],
                    'sku'      => $product_sku ? $product_sku : $product_id,
                    'category' => $product_cat,
                    'price'    => $product_price,
                    'currency' => get_woocommerce_currency(),
                    'quantity' => $item['qty']
                ];

                $_products[] = $product_data;
                $_sumprice += $product_price * $product_data[ 'quantity' ];
                $_product_ids[] = $productId;
            }

            $this->dataLayer['transactionProducts'] = $_products;
            $this->dataLayer['ecommerce']['purchase']['products'] = $_products;
            $this->dataLayer['event'] = 'orderCompleted';
            $this->dataLayer['ecomm_prodid'] = $_product_ids;
            $this->dataLayer['ecomm_pagetype'] = 'purchase';
            $this->dataLayer['ecomm_totalvalue'] = (float)$_sumprice;
        }
    }

    private function setGeneralOrderObjects()
    {
        $this->dataLayer['transactionId']             = $this->order->get_order_number();
        $this->dataLayer['transactionDate']           = date('c');
        $this->dataLayer['transactionType']           = 'sale';
        $this->dataLayer['transactionAffiliation']    = html_entity_decode(get_bloginfo('name'), ENT_QUOTES, 'utf-8');
        $this->dataLayer['transactionTotal']          = $this->order->get_total();
        $this->dataLayer['transactionShipping']       = $this->order->get_shipping_total();
        $this->dataLayer['transactionTax']            = $this->order->get_total_tax();
        $this->dataLayer['transactionPaymentType']    = $this->order->get_payment_method_title();
        $this->dataLayer['transactionCurrency']       = get_woocommerce_currency();
        $this->dataLayer['transactionShippingMethod'] = $this->order->get_shipping_method();
        $this->dataLayer['transactionPromoCode']      = implode(', ', $this->order->get_used_coupons());
    }

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}


function dataLayer()
{
    return DataLayer::instance();
}

dataLayer();
