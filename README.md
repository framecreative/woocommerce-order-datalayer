# Woocommerce Order to Datalayer

Pushes completed orders into a javascript object named `datalayer` into the `wp_head()` for Google Tag Manager to use.

## Requirements

- Wordpress 4.0+
- Woocommerce 3.0+
- PHP 5.6+

## Install

#### Via Composer

`composer require woocommerce-order-datalayer`

#### Manually

Download and install into your plugins folder


## Features

- Use filter `woocommerce_order_datalayer` to add objects to the dataLayer before it is outputted. 
- Adds order meta to track that the dataLayer has been triggered already.

DataLayer contains:
  - transactionId
  - transactionDate
  - transactionType: 'sale'
  - transactionAffiliation
  - transactionTotal
  - transactionShipping
  - transactionTax
  - transactionPaymentType
  - transactionCurrency
  - transactionShippingMethod
  - transactionPromoCode
  - ecomm_totalvalue
  - event: 'orderCompleted'
  - ecomm_prodid: [ids]
  - ecomm_pagetype: 'purchase'
  - transactionProducts:
    - id
    - name
    - sku
    - category
    - price
    - currency
    - quantity
