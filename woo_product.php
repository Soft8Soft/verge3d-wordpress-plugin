<?php

function v3d_load_woo_scripts() {
    wp_enqueue_script('v3d_admin', plugin_dir_url( __FILE__ ) . 'js/woo_product.js');
    wp_add_inline_script('v3d_admin', 'var v3d_woo_ajax_url="'.admin_url('admin-ajax.php').'"', 'before');
}
add_action('wp_enqueue_scripts', 'v3d_load_woo_scripts');


function v3d_product_tab($tabs) {
    $tabs['verge3d'] = array(
        'label'	 => __('Verge3D', 'verge3d'),
        'target' => 'v3d_product_options',
        'class'  => array('show_if_verge3d'),
        'priority' => 100
    );

    return $tabs;
}
add_filter('woocommerce_product_data_tabs', 'v3d_product_tab');


function v3d_product_tab_content() {
    ?><div id='v3d_product_options' class='panel woocommerce_options_panel'><?php
    ?><div class='options_group'><?php

    global $post;

    $value = get_post_meta($post->ID, 'v3d_app_id', true);
    if (empty($value))
        $value = '';

    $app_posts = get_posts(array(
        'posts_per_page'   => -1,
        'post_type'        => 'v3d_app',
        'post_status'      => 'publish',
    ));

    $options[''] = __('None (select a value)', 'verge3d');

    foreach ($app_posts as $app_post)
        $options[$app_post->ID] = $app_post->post_title;

    woocommerce_wp_select(array(
        'id' => 'v3d_app_id',
        'label' => __('Application', 'verge3d'),
        'options' =>  $options,
        'value' => $value,
        'desc_tip' => 'true',
        'description' => __('Verge3D application which will be displayed on the product page.', 'verge3d'),
    ));

    ?></div>
    </div><?php
}
add_action('woocommerce_product_data_panels', 'v3d_product_tab_content');


function v3d_save_product_settings($post_id) {

    $app_id = $_POST['v3d_app_id'];
    if (empty($app_id))
        $app_id = '';

    update_post_meta($post_id, 'v3d_app_id', esc_attr($app_id));

}
add_action('woocommerce_process_product_meta', 'v3d_save_product_settings');


function v3d_product_image($html) {
    global $product;

    $app_posts = get_posts(array(
        'posts_per_page'   => -1,
        'post_type'        => 'v3d_app',
        'post_status'      => 'publish',
    ));

    if (!empty($app_posts)) {
        $app_id = get_post_meta($product->get_id(), 'v3d_app_id', true);
        if (!empty($app_id) && !empty(get_post($app_id)))
            return v3d_gen_app_iframe_html($app_id);
        else
            return $html;
    } else
        return $html;
}
add_filter('woocommerce_single_product_image_thumbnail_html', 'v3d_product_image');


function v3d_parse_request_attributes() {

    $attrs = array();

    foreach ($_REQUEST as $key => $value) {
        if (strpos($key, 'attribute_') !== false)
          $attrs[preg_replace('/^pa_/', '', urldecode(str_replace('attribute_', '', $key)))] = $value;
    }

    return $attrs;
}

function v3d_product_get_attributes($product) {
    $attrs = array();

    // NOTE: using get_attributes() alone does not work
    foreach ($product->get_attributes() as $attr_key => $attr_value) {
        // remove global attributes prefix if any
        $attrs[preg_replace('/^pa_/', '', urldecode($attr_key))] = $product->get_attribute($attr_key);
    }

    return $attrs;
}

function v3d_get_product_info() {

    $product = wc_get_product($_REQUEST['product_id']);

    $response = array();

    if (!empty($product)) {
        $response['status'] = 'ok';

        $quantity = $_REQUEST['quantity'];

        $response['name'] = $product->get_name();
        $response['type'] = $product->get_type();
        $response['quantity'] = intval($quantity);

        $response['sku'] = $product->get_sku();
        $response['price'] = floatval($product->get_price());

        $response['weight'] = floatval($product->get_weight());

        $response['length'] = floatval($product->get_length());
        $response['width'] = floatval($product->get_width());
        $response['height'] = floatval($product->get_height());

        $response['attributes'] = v3d_product_get_attributes($product);

        if ($product->is_type('variable')) {

            // preserving non-variable attributes
            foreach (v3d_parse_request_attributes() as $attr_key => $attr_value) {
                $response['attributes'][$attr_key] = $attr_value;
            }

            if (!empty($_REQUEST['variation_id'])) {
                $variation = wc_get_product($_REQUEST['variation_id']);

                $response['name'] = $variation->get_name();

                if (!empty($variation->get_sku()))
                    $response['sku'] = $variation->get_sku();
                if (!empty($variation->get_price()))
                    $response['price'] = floatval($variation->get_price());

                if (!empty($variation->get_weight()))
                    $response['weight'] = floatval($variation->get_weight());

                if (!empty($variation->get_length()))
                    $response['length'] = floatval($variation->get_length());
                if (!empty($variation->get_width()))
                    $response['width'] = floatval($variation->get_width());
                if (!empty($variation->get_height()))
                    $response['height'] = floatval($variation->get_height());
             }

        } else if ($product->is_type('grouped')) {

            unset($response['price']);
            unset($response['quantity']);
            unset($response['weight']);
            unset($response['length']);
            unset($response['width']);
            unset($response['height']);

            $response['children'] = array();

            foreach ($product->get_children() as $child_id) {
                $child = wc_get_product($child_id);
                $child_response = array();

                $child_response['name'] = $child->get_name();
                $child_response['quantity'] = intval($quantity[$child_id]);

                $child_response['sku'] = $child->get_sku();
                $child_response['price'] = floatval($child->get_price());

                $child_response['weight'] = floatval($child->get_weight());

                $child_response['length'] = floatval($child->get_length());
                $child_response['width'] = floatval($child->get_width());
                $child_response['height'] = floatval($child->get_height());

                $child_response['attributes'] = v3d_product_get_attributes($child);

                array_push($response['children'], $child_response);
            }

        }

    } else {
        $response['status'] = 'error';
        $response['error'] = 'Product not found';
    }

    wp_send_json($response);
}
add_action('wp_ajax_v3d_woo_get_product_info', 'v3d_get_product_info');
add_action('wp_ajax_nopriv_v3d_woo_get_product_info', 'v3d_get_product_info');
