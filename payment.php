<?php

$PAYPAL_SUPPORTED_CURRENCIES = [
    'AUD',
    'BRL',
    'CAD',
    'CNY',
    'CZK',
    'DKK',
    'EUR',
    'HKD',
    'HUF',
    'ILS',
    'JPY',
    'MYR',
    'MXN',
    'TWD',
    'NZD',
    'NOK',
    'PHP',
    'PLN',
    'GBP',
    'RUB',
    'SGD',
    'SEK',
    'CHF',
    'THB',
    'USD',
];

function v3d_use_payment() {
    if (!empty(get_option('v3d_payment_paypal')) && !empty(get_option('v3d_payment_paypal_id')))
        return true;

    return false;
}

function v3d_display_payment($order, $order_id) {
    $paypal = get_option('v3d_payment_paypal');
    $paypal_id = get_option('v3d_payment_paypal_id');
    $price = calc_total_price($order, true);

    ob_start();
    include v3d_get_template('order_failed.php');
    $failed_html = ob_get_clean();

    if (!empty($paypal) && !empty($paypal_id)) {
        include v3d_get_template('payment_paypal.php');
    }
}

function v3d_payment_done() {

    if (!check_ajax_referer('v3d-payment', false, false)) {
        ob_start();
        include v3d_get_template('order_failed.php');
        wp_die(ob_get_clean());
    }

    $order_id = intval($_REQUEST['order_id']);
    $order = v3d_get_order_by_id($order_id);

    $payment_status = sanitize_text_field($_REQUEST['payment_status']);

    if ($payment_status == 'success') {
        $order['status'] = get_option('v3d_payment_success_status');
        $order['payment'] = array(
            'method' => 'PayPal',
            'date' => time()
        );
        v3d_update_order($order_id, $order, false);
        v3d_send_emails('new', $order, $order_id);

        ob_start();
        include v3d_get_template('order_success.php');
        wp_die(ob_get_clean());
    } else {
        $order['status'] = 'failed';
        v3d_update_order($order_id, $order, false);

        ob_start();
        include v3d_get_template('order_failed.php');
        wp_die(ob_get_clean());
    }
}
add_action('wp_ajax_v3d_payment_done', 'v3d_payment_done');
add_action('wp_ajax_nopriv_v3d_payment_done', 'v3d_payment_done');
