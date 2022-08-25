<?php

if (!class_exists('WP_List_Table')){
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

function v3d_product_menu()
{
    if (!current_user_can('manage_verge3d')) {
        echo 'Access denied';
        return;
    }

    add_filter('admin_footer_text', 'v3d_replace_footer');

    $action = (!empty($_REQUEST['action'])) ? sanitize_text_field($_REQUEST['action']) : '';

    switch ($action) {
    case 'createform':
        v3d_display_product(-1);
        break;
    case 'create':
        v3d_create_product();
        v3d_redirect_product_list();
        break;
    case 'editform':
        $product_id = intval($_REQUEST['product']);

        if (empty($product_id)) {
            echo 'Bad request';
            return;
        }

        v3d_display_product($product_id);

        break;
    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_REQUEST['product'])) {
            $product_id = intval($_REQUEST['product']);
            v3d_update_product($product_id);
            v3d_redirect_product_list();
        } else {
            echo 'Bad request';
            return;
        }

        break;
    case 'delete':
        if (!empty($_REQUEST['product'])) {

            $product = $_REQUEST['product'];

            // process bulk request
            if (is_array($product)) {
                foreach ($product as $o)
                    if (!empty(intval($o)))
                        v3d_delete_product(intval($o));
            } else {
                if (!empty(intval($product))) {
                    v3d_delete_product($product);
                }
            }

            v3d_redirect_product_list();
        } else {
            echo 'Bad request';
            return;
        }

        break;
    default:
        $productTable = new V3D_Product_List_Table();
        $productTable->prepare_items();

        ?>
        <div class="wrap">
          <div id="icon-users" class="icon32"><br/></div>

          <h1 class='wp-heading-inline'>E-Commerce Products</h1>
          <a href="?page=verge3d_product&action=createform" class="page-title-action">Add New</a>

          <form id="products-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo sanitize_text_field($_REQUEST['page']) ?>" />

            <?php $productTable->display() ?>

          </form>
        </div>
        <?php
        break;
    }
}

function v3d_create_product() {
    $post_arr = array(
        'post_title'   => (!empty($_REQUEST['title'])) ?
                          sanitize_text_field($_REQUEST['title']) : 'My Product',
        'post_status'  => 'publish',
        'post_type'    => 'v3d_product',
        'meta_input'   => array(
            'sku' => (!empty($_REQUEST['sku'])) ? sanitize_text_field($_REQUEST['sku']) : '',
            'price' => (!empty($_REQUEST['price'])) ? sanitize_text_field($_REQUEST['price']) : 0,
            'download_link' => (!empty($_REQUEST['download_link'])) ? sanitize_text_field($_REQUEST['download_link']) : '',
        ),
    );
    return wp_insert_post($post_arr);
}

function v3d_update_product($product_id) {
    $post_arr = array(
        'ID'           => $product_id,
        'post_title'   => (!empty($_REQUEST['title'])) ?
                          sanitize_text_field($_REQUEST['title']) : 'My Product',
        'post_status'  => 'publish',
        'post_type'    => 'v3d_product',
        'meta_input'   => array(
            'sku' => (!empty($_REQUEST['sku'])) ? sanitize_text_field($_REQUEST['sku']) : '',
            'price' => (!empty($_REQUEST['price'])) ? sanitize_text_field($_REQUEST['price']) : 0,
            'download_link' => (!empty($_REQUEST['download_link'])) ? sanitize_text_field($_REQUEST['download_link']) : '',
        ),
    );

    wp_update_post($post_arr);
}

function v3d_display_product($product_id) {

    if ($product_id > -1) {
        $title = get_the_title($product_id);
        $sku = get_post_meta($product_id, 'sku', true);
        $price = get_post_meta($product_id, 'price', true);
        $download_link = get_post_meta($product_id, 'download_link', true);
    } else {
        $title = '';
        $sku = '';
        $price = 0;
        $download_link = '';
    }

    include v3d_get_template('product_admin_form.php');
}

function v3d_delete_product($product_id) {
    wp_delete_post($product_id);
}

function v3d_get_products() {
    $args = array(
        'posts_per_page'   => -1,
        'orderby'          => 'title',
        'order'            => 'ASC',
        'post_type'        => 'v3d_product',
        'post_status'      => 'publish',
        'suppress_filters' => true,
    );
    $posts = get_posts($args);

    $products = array();

    foreach ($posts as $p) {
        $products[] = array(
            'id' => $p->ID,
            'title' => get_the_title($p->ID),
            'sku' => get_post_meta($p->ID, 'sku', true),
            'price' => get_post_meta($p->ID, 'price', true),
            'download_link' => get_post_meta($p->ID, 'download_link', true),
        );
    }

    return $products;
}

function v3d_find_product_by_sku($sku) {
    $products = v3d_get_products();

    foreach ($products as $p) {
        if ($p['sku'] === $sku)
            return $p;
    }

    return null;
}

class V3D_Product_List_Table extends WP_List_Table {

    function __construct(){
        global $status, $page;

        // Set parent defaults
        parent::__construct( array(
            'singular'  => 'product',
            'plural'    => 'products',
            'ajax'      => false
        ) );

    }

    function column_default($item, $column_name){
        switch ($column_name) {
        case 'sku':
        case 'price':
            return $item[$column_name];
        default:
            return print_r($item, true); // show the whole array for troubleshooting purposes
        }
    }

    function column_title($item){

        // Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&product=%s">Edit</a>',
                    sanitize_text_field($_REQUEST['page']), 'editform', $item['ID']),
            'delete'    => sprintf('<a href="?page=%s&action=%s&product=%s">Delete</a>',
                    sanitize_text_field($_REQUEST['page']), 'delete', $item['ID']),
        );

        // Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item['title'],
            /*$2%s*/ $item['ID'],
            /*$3%s*/ $this->row_actions($actions)
        );
    }


    // bulk actions callback
    function column_cb($item){
        return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'], $item['ID']);
    }

    function get_columns(){
        $columns = array(
            'cb'      => '<input type="checkbox" />', //Render a checkbox instead of text
            'title'   => 'Title',
            'sku'   => 'SKU',
            'price'   => 'Price',
        );
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'title' => array('title', false),
            'sku'   => array('sku', false),
            'price' => array('price', false),
        );
        return $sortable_columns;
    }

    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete'
        );
        return $actions;
    }

    function process_bulk_action() {
        if ('delete' === $this->current_action()) {
            wp_die('Items deleted (or they would be if we had items to delete)!');
        }
    }

    function prepare_items() {
        $per_page = 15;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();

        // if no sort, default to title
        $orderby = !empty($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'title';
        // if no order, default to asc
        $order = !empty($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'ASC';

        $args = array(
            'posts_per_page'   => -1,
            'offset'           => 0,
            'category'         => '',
            'category_name'    => '',
            'orderby'          => $orderby,
            'order'            => $order,
            'include'          => '',
            'exclude'          => '',
            'meta_key'         => '',
            'meta_value'       => '',
            'post_type'        => 'v3d_product',
            'post_mime_type'   => '',
            'post_parent'      => '',
            'author'	         => '',
            'author_name'	     => '',
            'post_status'      => 'publish',
            'suppress_filters' => true,
            'fields'           => '',
        );

        if ($orderby == 'sku') {
            $args['meta_key'] = 'sku';
            $args['orderby'] = 'meta_value';
        } else if ($orderby == 'price') {
            $args['meta_key'] = 'price';
            $args['orderby'] = 'meta_value_num';
        }

        $q_posts = get_posts($args);

        $posts = array();

        foreach ($q_posts as $q_post) {

            $title = get_the_title($q_post->ID);
            $sku = get_post_meta($q_post->ID, 'sku', true);
            $price = get_post_meta($q_post->ID, 'price', true);

            $posts[] = array(
                'ID'    => $q_post->ID,
                'title' => !empty($title) ? $title : 'N/A',
                'sku'   => !empty($sku) ? $sku : 'N/A',
                'price' => isset($price) ? v3d_price($price) : 'N/A',
            );
        }

        $current_page = $this->get_pagenum();

        $total_items = count($posts);

        $posts = array_slice($posts, (($current_page-1)*$per_page), $per_page);

        $this->items = $posts;

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items/$per_page)
        ));
    }
}

function v3d_redirect_product($product_id=-1) {

    $params = '?page=verge3d_product';

    if ($product_id > -1) {
        $params .= ('&action=edit&product='.$product_id);
    }

    ?>
    <script type="text/javascript">
          document.location.href="<?php echo $params ?>";
    </script>
    <?php
}

function v3d_redirect_product_list() {
    ?>
    <script type="text/javascript">
          document.location.href="?page=verge3d_product";
    </script>
    <?php
}

function v3d_api_get_product_info(WP_REST_Request $request) {

    $sku = urldecode(esc_attr($request->get_param('sku')));
    if (!empty($sku)) {

        $product = v3d_find_product_by_sku($sku);

        if (!empty($product)) {
            $product_info = array(
                'status'   => 'ok',
                'title'    => $product['title'],
                'sku'      => $product['sku'],
                'price'    => $product['price'],
                'currency' => v3d_currency_symbol(),
            );
            $response = new WP_REST_Response($product_info);
        } else
            $response = new WP_REST_Response(array('error' => 'Product not found'));

    } else {

        $response = new WP_REST_Response(array('error' => 'Bad request'), 400);

    }

    if (get_option('v3d_cross_domain'))
        $response->header('Access-Control-Allow-Origin', '*');

    return $response;

}

add_action('rest_api_init', function () {
    if (get_option('v3d_product_api')) {
        register_rest_route('verge3d/v1', '/get_product_info/(?P<sku>.+)', array(
            'methods' => 'GET',
            'callback' => 'v3d_api_get_product_info',
            'args' => array(
                'sku' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_string($param);
                    }
                ),
            ),
            'permission_callback' => '__return_true',
        ));
    }

});
