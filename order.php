<?php

if (!class_exists('WP_List_Table')){
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

function v3d_order_menu()
{
    if (!current_user_can('manage_verge3d')) {
        echo 'Access denied';
        return;
    }

    add_filter('admin_footer_text', 'v3d_replace_footer');

    $action = (!empty($_REQUEST['action'])) ? sanitize_text_field($_REQUEST['action']) : '';

    switch ($action) {
    case 'createform':
        $order = array();
        v3d_display_order($order, -1);
        break;
    case 'create':
        v3d_create_order(v3d_request_to_order());
        v3d_redirect_order_list();
        break;
    case 'editform':
        $order_id = intval($_REQUEST['order']);

        if (empty($order_id)) {
            echo 'Bad request';
            return;
        }

        $order = json_decode(get_post_field('post_content', $order_id), true);
        v3d_display_order($order, $order_id);

        break;
    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_REQUEST['order'])) {
            $order_id = intval($_REQUEST['order']);

            if (!empty($_REQUEST['title']) || !empty($_REQUEST['content'])) {
                v3d_update_order($order_id, v3d_request_to_order());
            }

            v3d_redirect_order_list();
        } else {
            echo 'Bad request';
            return;
        }

        break;
    case 'delete':
        if (!empty($_REQUEST['order'])) {

            $order = $_REQUEST['order'];

            // process bulk request
            if (is_array($order)) {
                foreach ($order as $o)
                    if (!empty(intval($o)))
                        v3d_delete_order(intval($o));
            } else {
                if (!empty(intval($order))) {
                    v3d_delete_order($order);
                }
            }

            v3d_redirect_order_list();
        } else {
            echo 'Bad request';
            return;
        }

        break;
    default:
        $orderTable = new V3D_Order_List_Table();
        $orderTable->prepare_items();

        ?>
        <div class="wrap">
          <div id="icon-users" class="icon32"><br/></div>

          <h1 class='wp-heading-inline'>E-Commerce Orders</h1>
          <a href="?page=verge3d_order&action=createform" class="page-title-action">Add New</a>

          <div class="v3d-hint">
            <p>To handle orders sent from a Verge3D application (generated with "send order" puzzle) add an order form to a web page/post using <code>[verge3d_order]</code> shortcode.</p>
            <p>Specify the link to that page/post in the "send order" puzzle to make it work.</p>
          </div>

          <form id="orders-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo sanitize_text_field($_REQUEST['page']) ?>" />

            <?php $orderTable->display() ?>

          </form>
        </div>
        <?php
        break;
    }
}

function v3d_create_order($order) {
    $post_arr = array(
        'post_title'   => '',
        'post_content' => json_encode($order, JSON_UNESCAPED_UNICODE),
        'post_status'  => 'publish',
        'post_type'    => 'v3d_order'
    );

    $order = apply_filters('v3d_create_order', $order);
    if (empty($order))
        return false;

    // to use it inside templates
    $order_id = wp_insert_post($post_arr);

    $order_email = get_option('v3d_order_email');
    $order_from_name = get_option('v3d_order_email_from_name');
    $order_from_email = get_option('v3d_order_email_from_email');

    $attachments = array();

    if (!empty($order_email) || !empty($order['user_email'])) {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        if (!empty($order_from_name) || !empty($order_from_email)) {
            $headers[] = 'From: "'.$order_from_name.'" <'.$order_from_email.'>';
        }

        $attachments = v3d_gen_email_attachments($order, $order_id, get_option('v3d_order_email_attach_pdf'));
    }

    if (!empty($order_email)) {
        $to = $order_email;
        $subject = get_option('v3d_order_email_subject');

        ob_start();
        include v3d_get_template('order_email_body.php');
        $body = ob_get_clean();

        wp_mail($to, $subject, $body, $headers, $attachments);
    }

    if (!empty($order['user_email'])) {
        $to = $order['user_email'];
        $subject = get_option('v3d_order_email_subject');

        ob_start();
        include v3d_get_template('order_email_body.php');
        $body = ob_get_clean();

        wp_mail($to, $subject, $body, $headers, $attachments);
    }

    v3d_cleanup_email_attachments($attachments);

    return true;
}

function v3d_terminal($command) {

    $output = '';

    if (function_exists('system')) {
        ob_start();
        system($command, $return_var);
        $output = ob_get_contents();
        ob_end_clean();
    }

    if (empty($output) && function_exists('passthru')) {
        ob_start();
        passthru($command, $return_var);
        $output = ob_get_contents();
        ob_end_clean();
    }

    if (empty($output) && function_exists('exec')) {
        exec($command, $output , $return_var);
        $output = implode("n" , $output);
    }

    if (empty($output) && function_exists('shell_exec')) {
        $output = shell_exec($command);
        $return_var = 0;
    }

    if (empty($output)) {
        $output = 'Command execution not possible on this system';
        $return_var = 1;
    }

    return array('output' => $output , 'status' => $return_var);
}

function v3d_get_chrome_path() {

    $chrome_path = get_option('v3d_chrome_path');

    if (!empty($chrome_path))
        return $chrome_path;

    # perform search in system paths

    $CHROME_PATHS = [
        'chromium-browser',
        'google-chrome',
        'chromium'
    ];

    foreach ($CHROME_PATHS as $p) {
        if (v3d_terminal($p.' --version')['status'] == 0)
            return $p;
    }

    return '';
}

function v3d_get_attachments_tmp_dir($attachments) {
    if (empty($attachments)) {
        $temp_dir = get_temp_dir().uniqid('v3d_email_att');
        mkdir($temp_dir, 0777, true);
        return $temp_dir.'/';
    } else {
        return dirname($attachments[0]).'/';
    }
}

function v3d_gen_email_attachments($order, $order_id, $use_pdf) {

    $attachments = array();

    if (!empty($order['screenshot'])) {
        $scr_file = v3d_get_upload_dir().'screenshots/'.basename($order['screenshot']);
        if (is_file($scr_file)) {
            $att_path = v3d_get_attachments_tmp_dir($attachments).'order_screenshot.'.pathinfo($scr_file, PATHINFO_EXTENSION);
            copy($scr_file, $att_path);
            $attachments[] = $att_path;
        }
    }

    if ($use_pdf && is_file(v3d_get_template('order_email_pdf.php'))) {
        ob_start();
        include v3d_get_template('order_email_pdf.php');
        $pdf_html_text = ob_get_clean();
    } else {
        return $attachments;
    }

    $temp_dir = get_temp_dir();
    $pdf_html = $temp_dir.wp_unique_filename($temp_dir, uniqid('v3d_email_att').'.html');
    $pdf = v3d_get_attachments_tmp_dir($attachments).'order_details.pdf';

    $success = file_put_contents($pdf_html, $pdf_html_text);
    if ($success) {

        $chrome_path = v3d_get_chrome_path();

        if (!empty($chrome_path)) {

            // NOTE: undocumented wkhtmltopdf feature
            if (basename($chrome_path) == 'wkhtmltopdf')
                v3d_terminal($chrome_path.' -s Letter --print-media-type '.$pdf_html.' '.$pdf);
            else
                v3d_terminal($chrome_path.' --headless --disable-gpu --print-to-pdf='.$pdf.' '.$pdf_html);

            if (is_file($pdf))
                $attachments[] = $pdf;
        }

        @unlink($pdf_html);
    }

    return $attachments;

}

function v3d_cleanup_email_attachments($attachments) {

    if (empty($attachments))
        return;

    foreach ($attachments as $a) {
        @unlink($a);
    }

    rmdir(v3d_get_attachments_tmp_dir($attachments));
}

function v3d_update_order($order_id, $order) {
    $post_arr = array(
        'ID'           => $order_id,
        'post_title'   => '',
        'post_content' => json_encode($order, JSON_UNESCAPED_UNICODE),
        'post_status'  => 'publish',
        'post_type'    => 'v3d_order'
    );

    wp_update_post($post_arr);
}

function v3d_request_to_order() {
    $order = array();

    $IGNORED_KEYS = ['page', 'action', 'order'];

    foreach ($_POST as $key => $value) {
        if (in_array($key, $IGNORED_KEYS, true))
            continue;

        // allow multi-dimensional keys, separated by ":"
        $keys = strpos($key, ':') !== false ? explode(':', $key) : array($key);

        $ptr = &$order;

        foreach ($keys as $k) {
            if (!isset($ptr[$k])) {
                $ptr[$k] = array();
            }
            $ptr = &$ptr[$k];
        }
        if (empty($ptr)) {
            $ptr = $value;
        } else {
            $ptr[] = $value;
        }
    }

    return $order;
}

function v3d_display_order($order, $order_id) {
    include v3d_get_template('order_admin_form.php');
}

function v3d_delete_order($order_id) {

    $scr_url = get_post_meta($order_id, 'screenshot', true);

    if (!empty($scr_url)) {
        $scr_file = v3d_get_upload_dir().'screenshots/'.basename($scr_url);
        if (is_file($scr_file))
            @unlink($scr_file);
    }

    wp_delete_post($order_id);
}


class V3D_Order_List_Table extends WP_List_Table {

    function __construct(){
        global $status, $page;

        // Set parent defaults
        parent::__construct( array(
            'singular'  => 'order',
            'plural'    => 'orders',
            'ajax'      => false
        ) );

    }

    function column_default($item, $column_name){
        switch ($column_name) {
        case 'price':
        case 'user_name':
        case 'user_email':
        case 'user_phone':
        case 'date':
            return $item[$column_name];
        default:
            return print_r($item, true); // show the whole array for troubleshooting purposes
        }
    }

    function column_title($item){

        // Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&order=%s">Edit</a>',
                    sanitize_text_field($_REQUEST['page']), 'editform', $item['ID']),
            'delete'    => sprintf('<a href="?page=%s&action=%s&order=%s">Delete</a>',
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
            'price'   => 'Total Price',
            'user_name'   => 'Customer',
            'user_email'   => 'Customer Email',
            'user_phone'   => 'Phone Number',
            'date'    => 'Date',
        );
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'title'      => array('title', false),
            'price'      => array('price', false),
            'user_name'  => array('user_name', false),
            'user_email' => array('user_email', false),
            'user_phone' => array('user_phone', false),
            'date'       => array('date', false),
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
        $orderby = (!empty($_REQUEST['orderby'])) ?
                sanitize_text_field($_REQUEST['orderby']) : 'title';
        // if no order, default to asc
        $order = (!empty($_REQUEST['order'])) ?
                sanitize_text_field($_REQUEST['order']) : 'ASC';

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
            'post_type'        => 'v3d_order',
            'post_mime_type'   => '',
            'post_parent'      => '',
            'author'	   => '',
            'author_name'	   => '',
            'post_status'      => 'publish',
            'suppress_filters' => true,
            'fields'           => '',
        );
        $q_posts = get_posts($args);

        $posts = array();

        foreach ($q_posts as $q_post) {

            $content = json_decode($q_post->post_content, true);

            $posts[] = array(
                'ID'     => $q_post->ID,
                'title'  => (!empty($content['title'])) ? $content['title'] : 'N/A',
                'price'  => (!empty($content['price'])) ? $content['price'] : 'N/A',
                'user_name'  => (!empty($content['user_name'])) ? $content['user_name'] : 'N/A',
                'user_email'  => (!empty($content['user_email'])) ? $content['user_email'] : 'N/A',
                'user_phone'  => (!empty($content['user_phone'])) ? $content['user_phone'] : 'N/A',
                'date'   => $q_post->post_date,
            );
        }

        $current_page = $this->get_pagenum();

        $total_items = count($posts);

        $posts = array_slice($posts, (($current_page-1)*$per_page), $per_page);

        $this->items = $posts;

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items/$per_page)
        ) );
    }
}



function v3d_order_shortcode($atts = [], $content = null, $tag = '')
{
    // normalize attribute keys, lowercase
    $atts = array_change_key_case((array)$atts, CASE_LOWER);

    $action = (!empty($_REQUEST['v3d_action'])) ? sanitize_text_field($_REQUEST['v3d_action']) : '';
    $title = (!empty($_REQUEST['v3d_title'])) ? sanitize_text_field($_REQUEST['v3d_title']) : '';
    $content = (!empty($_REQUEST['v3d_content'])) ? sanitize_textarea_field($_REQUEST['v3d_content']) : '';
    $price = (!empty($_REQUEST['v3d_price'])) ? sanitize_text_field($_REQUEST['v3d_price']) : '0';

    $screenshot = '';
    if (!empty($_REQUEST['v3d_screenshot'])) {
        if ($action != 'submit') {
            $screenshot = v3d_save_screenshot(sanitize_text_field($_REQUEST['v3d_screenshot']));
        } else {
            $screenshot = sanitize_text_field($_REQUEST['v3d_screenshot']);
        }
    }

    $user_name = (!empty($_REQUEST['v3d_user_name'])) ? sanitize_text_field($_REQUEST['v3d_user_name']) : '';
    $user_email = (!empty($_REQUEST['v3d_user_email'])) ? sanitize_email($_REQUEST['v3d_user_email']) : '';
    $user_phone = (!empty($_REQUEST['v3d_user_phone'])) ? sanitize_text_field($_REQUEST['v3d_user_phone']) : '';
    $user_comment = (!empty($_REQUEST['v3d_user_comment'])) ? sanitize_textarea_field($_REQUEST['v3d_user_comment']) : '';

    if ($action == 'submit') {
        $result = v3d_create_order(array(
            'title' => $title,
            'content' => $content,
            'price' => $price,
            'screenshot' => $screenshot,
            'user_name' => $user_name,
            'user_email' => $user_email,
            'user_phone' => $user_phone,
            'user_comment' => $user_comment,
        ));
        ob_start();
        include v3d_get_template($result ? 'order_success.php' : 'order_failed.php');
        return ob_get_clean();
    } else {
        ob_start();
        include v3d_get_template('order_form.php');
        return ob_get_clean();
    }
}

function v3d_save_screenshot($data_url) {

    $data_url = str_replace('data:image/png;base64,', '', $data_url);
    $data_url = str_replace(' ', '+', $data_url);

    $upload_dir = v3d_get_upload_dir();
    $screenshot_dir = $upload_dir.'screenshots/';
    if (!is_dir($screenshot_dir)) {
        mkdir($screenshot_dir, 0777, true);
    }

    $data = base64_decode($data_url);
    $file = $screenshot_dir.time().'.png';
    $success = file_put_contents($file, $data);
    if ($success)
        return v3d_get_upload_url().'screenshots/'.basename($file);
    else
        return '';
}

function v3d_order_shortcode_init() {
    add_shortcode('verge3d_order', 'v3d_order_shortcode');
}
add_action('init', 'v3d_order_shortcode_init');


function v3d_redirect_order_list() {
    ?>
    <script type="text/javascript">
          document.location.href="?page=verge3d_order";
    </script>
    <?php
}


function v3d_api_place_order(WP_REST_Request $request) {

    $params = $request->get_json_params();

    if (!empty($params)) {

        if (!empty($params['screenshot']))
            $params['screenshot'] = v3d_save_screenshot($params['screenshot']);

        if (v3d_create_order($params)) {
            $response = new WP_REST_Response(
                array(
                    'status' => 'ok',
                    // COMPAT: < 2.12
                    'order' => 'ok'
                )
            );
        } else {
            $response = new WP_REST_Response(array(
                'status' => 'rejected',
                'error' => 'Order rejected'
            ), 400);
        }
    } else {
        $response = new WP_REST_Response(array(
            'status' => 'rejected',
            'error' => 'Bad request'
        ), 400);
    }

    if (get_option('v3d_cross_domain'))
        $response->header('Access-Control-Allow-Origin', '*');

    return $response;

}

add_action('rest_api_init', function () {
    if (get_option('v3d_order_api')) {
        register_rest_route('verge3d/v1', '/place_order', array(
            'methods' => 'POST',
            'callback' => 'v3d_api_place_order',
        ));
    }
});
