<?php

function v3d_load_woo_scripts() {
    global $post;
    wp_enqueue_script('v3d_admin', plugin_dir_url( __FILE__ ) . 'js/woo_product.js');

    $switch_on_update = get_post_meta(get_the_ID(), 'v3d_app_show_gallery', true) &&
          get_post_meta(get_the_ID(), 'v3d_app_switch_on_update', true);

    wp_localize_script('v3d_admin', 'v3d_ajax_object',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'switch_on_update' => $switch_on_update
        ));

}
add_action('wp_enqueue_scripts', 'v3d_load_woo_scripts');


// Verge3D tab in product data (admin)

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

    $app_id = get_post_meta($post->ID, 'v3d_app_id', true);
    if (empty($app_id))
        $app_id = '';

    $app_posts = get_posts(array(
        'posts_per_page' => -1,
        'post_type'      => 'v3d_app',
        'post_status'    => 'publish',
    ));

    $app_options[''] = __('None (select a value)', 'verge3d');

    foreach ($app_posts as $app_post)
        $app_options[$app_post->ID] = $app_post->post_title;

    woocommerce_wp_select(array(
        'id' => 'v3d_app_id',
        'label' => __('Application', 'verge3d'),
        'options' =>  $app_options,
        'value' => $app_id,
        'desc_tip' => 'true',
        'description' => __('Verge3D application which will be displayed on the product page.', 'verge3d'),
    ));


    woocommerce_wp_checkbox(array(
        'id' => 'v3d_app_show_gallery',
        'label' => __('Show as gallery item', 'verge3d'),
        'value' => get_post_meta(get_the_ID(), 'v3d_app_show_gallery', true),
        'desc_tip' => 'true',
        'description' => __('Show application as last thumbnail in the gallery. If disabled, all 2D images will be replaced by 3D app.', 'verge3d'),
    ));

    woocommerce_wp_checkbox(array(
        'id' => 'v3d_app_switch_on_update',
        'label' => __('Switch to 3D on update', 'verge3d'),
        'value' => get_post_meta(get_the_ID(), 'v3d_app_switch_on_update', true),
        'desc_tip' => 'true',
        'description' => __('Automatically slide to 3D app when product updates (gallery mode only).', 'verge3d'),
    ));

    ?></div>
    <script>
        const showGalleryCheckbox = document.getElementById('v3d_app_show_gallery');

        function showHideSwitchOnUpdate() {
            document.getElementById('v3d_app_switch_on_update').disabled =
                !showGalleryCheckbox.checked;
        }

        showGalleryCheckbox.onchange = showHideSwitchOnUpdate;
        showHideSwitchOnUpdate();

    </script>
    </div><?php
}
add_action('woocommerce_product_data_panels', 'v3d_product_tab_content');


function v3d_save_product_settings($post_id) {

    $app_id = $_POST['v3d_app_id'];
    if (empty($app_id))
        $app_id = '';
    update_post_meta($post_id, 'v3d_app_id', esc_attr($app_id));

    $show_gallery = !empty($_POST['v3d_app_show_gallery']) ? $_POST['v3d_app_show_gallery'] : '';
    update_post_meta($post_id, 'v3d_app_show_gallery', esc_attr($show_gallery));

    $switch_on_update = !empty($_POST['v3d_app_switch_on_update']) ? $_POST['v3d_app_switch_on_update'] : '';
    update_post_meta($post_id, 'v3d_app_switch_on_update', esc_attr($switch_on_update));

}
add_action('woocommerce_process_product_meta', 'v3d_save_product_settings');


function v3d_get_product_iframe($product) {

    $app_posts = get_posts(array(
        'posts_per_page' => -1,
        'post_type'      => 'v3d_app',
        'post_status'    => 'publish',
    ));

    if (!empty($app_posts)) {
        $app_id = get_post_meta($product->get_id(), 'v3d_app_id', true);
        if (!empty($app_id) && !empty(get_post($app_id))) {
            return v3d_gen_app_iframe_html($app_id);
        } else
            return '';
    }
}


function v3d_product_image($html) {
    global $product;

    if (get_post_meta(get_the_ID(), 'v3d_app_show_gallery', true))
        return $html;

    $app_iframe = v3d_get_product_iframe($product);
    if (!empty($app_iframe))
        return $app_iframe;
    else
        return $html;
}
add_filter('woocommerce_single_product_image_thumbnail_html', 'v3d_product_image');


function v3d_show_product_thumbnails() {
    global $product;

    if (!get_post_meta(get_the_ID(), 'v3d_app_show_gallery', true))
        return;

    $app_iframe = v3d_get_product_iframe($product);
    if (empty($app_iframe))
        return;

    $app_id = get_post_meta($product->get_id(), 'v3d_app_id', true);
    if (!empty($app_id) && !empty(get_post($app_id))) {
        $cover_att_id = get_post_meta($app_id, 'cover_attachment_id', true);

        $thumbnail_src = wp_get_attachment_image_src($cover_att_id, 'thumbnail');
        $full_src = wp_get_attachment_image_src($cover_att_id, 'full');
        $alt_text = trim(strip_tags(get_post_meta($cover_att_id, '_wp_attachment_image_alt', true)));
    }

    if (!is_array($thumbnail_src)) {
        $default_url = plugin_dir_url(__FILE__) . 'images/product_thumbnail.png';
        $thumbnail_src = [$default_url];
        $full_src = [$default_url];
        $alt_text = '3D preview';
    }

    $html = '<div data-thumb="' . esc_url($thumbnail_src[0]) . '" data-thumb-alt="' . esc_attr($alt_text) . '" data-thumb-v3d-app-cover-src="' . esc_url($thumbnail_src[0]) . '" class="woocommerce-product-gallery__image"><a href="' . esc_url($full_src[0]) . '" class="v3d-product-gallery-empty-a">' . $app_iframe . '</a></div>';

    echo $html;
}
add_action('woocommerce_product_thumbnails', 'v3d_show_product_thumbnails', 30);


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
