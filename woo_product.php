<?php

function v3d_load_woo_scripts() {
    wp_enqueue_script('v3d_admin', plugin_dir_url( __FILE__ ) . 'js/woo_product.js');
}
add_action('wp_enqueue_scripts', 'v3d_load_woo_scripts');


function v3d_register_product_type() {
    class WC_Product_Verge3D extends WC_Product {
        public function __construct($product) {
            $this->product_type = 'verge3d';
            parent::__construct($product);
        }

        public function get_name($context = 'view') {
            $name = v3d_get_session_param($this->get_id(), 'name',
                parent::get_name($context));
            return $name;
        }

        public function get_price($context = 'view') {
            $price = v3d_get_session_param($this->get_id(), 'price',
                get_post_meta($this->get_id(), 'v3d_default_price', true));
            return $price;
        }

        public function get_sku($context = 'view') {
            $sku = v3d_get_session_param($this->get_id(), 'sku',
                parent::get_sku($context));
            return $sku;
        }

        /*
        public function is_purchasable() {
            return apply_filters('woocommerce_is_purchasable', $this->exists() && ('publish' === $this->get_status() || current_user_can('edit_post', $this->get_id())) && '' !== $this->get_price(), $this);
            return apply_filters('woocommerce_is_purchasable', $this->exists() && ('publish' === $this->get_status() || current_user_can('edit_post', $this->get_id())), $this);
        }
         */
    }
}
add_action('init', 'v3d_register_product_type');

function v3d_add_product_type($types) {
    $types['verge3d'] = __('Verge3D product', 'verge3d');
    return $types;
}
add_filter('product_type_selector', 'v3d_add_product_type');


function v3d_product_tab($tabs) {
    $tabs['verge3d'] = array(
        'label'	 => __('Verge3D Product', 'verge3d'),
        'target' => 'v3d_product_options',
        'class'  => array('show_if_verge3d'),
        'priority' => 10
    );

    return $tabs;
}
add_filter('woocommerce_product_data_tabs', 'v3d_product_tab');


function v3d_product_tab_content() {
    ?><div id='v3d_product_options' class='panel woocommerce_options_panel'><?php
    ?><div class='options_group'><?php

    woocommerce_wp_text_input(
        array(
            'id' => 'v3d_app_id',
            'label' => __('Application ID', 'verge3d'),
            'placeholder' => '',
            'desc_tip' => 'true',
            'description' => __('Enter Verge3D application ID.', 'verge3d'),
            'type' => 'text'
        )
    );

    woocommerce_wp_text_input(
        array(
            'id' => 'v3d_default_price',
            'label' => __('Default Price', 'verge3d'),
            'placeholder' => '',
            'desc_tip' => 'true',
            'description' => __('Enter Verge3D product default price.', 'verge3d'),
            'type' => 'text',
            'data_type' => 'price'
        )
    );

    ?></div>
    </div><?php
}
add_action('woocommerce_product_data_panels', 'v3d_product_tab_content');


function v3d_save_product_settings($post_id) {

    $app_id = $_POST['v3d_app_id'];
    if (!empty($app_id)) {
        update_post_meta($post_id, 'v3d_app_id', esc_attr($app_id));
    }

    $price = $_POST['v3d_default_price'];
    if (!empty($price)) {
        update_post_meta($post_id, 'v3d_default_price', esc_attr($price));
    }

}
add_action('woocommerce_process_product_meta', 'v3d_save_product_settings');


function v3d_product_front() {
    global $product;

    if ('verge3d' == $product->get_type()) {
        wc_get_template('single-product/add-to-cart/simple.php');
    }
}
add_action('woocommerce_single_product_summary', 'v3d_product_front', 30);


function v3d_product_image($html) {
    global $product;

    $app_posts = get_posts(array(
        'posts_per_page'   => -1,
        'post_type'        => 'v3d_app',
        'post_status'      => 'publish',
    ));

    if ('verge3d' == $product->get_type() && !empty($app_posts)) {
        $app_id = get_post_meta($product->get_id(), 'v3d_app_id', true);
        if (!empty($app_id) && !empty(get_post($app_id)))
            return v3d_gen_app_iframe_html($app_id);
        else
            return v3d_gen_app_iframe_html($app_posts[0]->ID);
    } else
        return $html;
}
add_filter('woocommerce_single_product_image_thumbnail_html', 'v3d_product_image');


function v3d_product_admin_custom_js() {
    if ('product' != get_post_type()) :
        return;
    endif;
    ?>
    <script type='text/javascript'>
        jQuery(document).ready(function () {
            //jQuery('.general_options').addClass('show_if_verge3d').show();
            //jQuery('#general_product_data').addClass('show_if_verge3d').show();
            //jQuery('.pricing').addClass('show_if_verge3d').show();

            // enable Inventory tab
            jQuery('.inventory_options').addClass('show_if_verge3d').show();
            jQuery('#inventory_product_data ._manage_stock_field').addClass('show_if_verge3d').show();
            jQuery('#inventory_product_data ._sold_individually_field').parent().addClass('show_if_verge3d').show();
            jQuery('#inventory_product_data ._sold_individually_field').addClass('show_if_verge3d').show();
        });
    </script>
    <?php
}
add_action('admin_footer', 'v3d_product_admin_custom_js');


function v3d_set_session_param($product_id, $name, $value) {

    $arr_name = 'v3d_'.$name.'s';

    if (empty(WC()->session))
        WC()->initialize_session();

    if (!empty(WC()->session->get($arr_name)))
        $params = WC()->session->get($arr_name);
    else
        $params = array();

    $params[$product_id] = $value;
    WC()->session->set($arr_name, $params);
}

function v3d_get_session_param($product_id, $name, $default_value='') {

    $arr_name = 'v3d_'.$name.'s';

    if (empty(WC()->session))
        WC()->initialize_session();

    if (!empty(WC()->session->get($arr_name)))
        $params = WC()->session->get($arr_name);
    else
        $params = array();

    if (!empty($params[$product_id]))
        return $params[$product_id];
    else
        return $default_value;
}

function v3d_unset_session_param($product_id, $name) {

    $arr_name = 'v3d_'.$name.'s';

    if (!empty(WC()->session->get($arr_name))) {
        $params = WC()->session->get($arr_name);
        unset($params[$product_id]);
        WC()->session->set($arr_name, $params);
    }
}

function v3d_change_param() {

    $url = wp_get_referer();
    $post_id = url_to_postid($url);
    $product = wc_get_product($post_id);

    $response = array(
        'status' => 'error'
    );

    if (!empty($_REQUEST['v3d_name'])) {
        $name = $_REQUEST['v3d_name'];

        v3d_set_session_param($product->get_id(), 'name', $name);
        $response['html'] = $name;
        $response['status'] = 'ok';
    }

    if (!empty($_REQUEST['v3d_price'])) {
        $price = $_REQUEST['v3d_price'];

        v3d_set_session_param($product->get_id(), 'price', $price);
        $response['html'] = wc_price($price) . $product->get_price_suffix();
        $response['status'] = 'ok';
    }

    if (!empty($_REQUEST['v3d_sku'])) {
        $sku = $_REQUEST['v3d_sku'];

        v3d_set_session_param($product->get_id(), 'sku', $sku);
        $response['html'] = $sku;
        $response['status'] = 'ok';
    }

    if (!empty($_REQUEST['v3d_short_description'])) {
        $desc = $_REQUEST['v3d_short_description'];

        v3d_set_session_param($product->get_id(), 'short_description', $desc);
        $response['html'] = $desc;
        $response['status'] = 'ok';
    }

    if (!empty($_REQUEST['v3d_debug'])) {
        $response['html'] = '"'.$product.'"';
        $response['status'] = 'ok';
    }

    wp_send_json($response);
}
add_action('wp_ajax_v3d_woocommerce_change_param', 'v3d_change_param');
add_action('wp_ajax_nopriv_v3d_woocommerce_change_param', 'v3d_change_param');


function v3d_get_attribute() {

    $url = wp_get_referer();
    $post_id = url_to_postid($url);
    $product = wc_get_product($post_id);

    $response = array(
        'status' => 'error'
    );

    if (!empty($_REQUEST['v3d_attribute'])) {
        $name = $_REQUEST['v3d_attribute'];

        $response['name'] = $name;
        $response['value'] = $product->get_attribute($name);
        $response['status'] = 'ok';
    }

    wp_send_json($response);
}
add_action('wp_ajax_v3d_woocommerce_get_attribute', 'v3d_get_attribute');
add_action('wp_ajax_nopriv_v3d_woocommerce_get_attribute', 'v3d_get_attribute');
