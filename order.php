<?php

if (!class_exists('WP_List_Table')){
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

// for Captcha
function start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'start_session', 1);


function v3d_order_menu()
{
    if (!current_user_can('manage_verge3d')) {
        echo 'Access denied';
        return;
    }

    $action = (!empty($_REQUEST['action'])) ? sanitize_text_field($_REQUEST['action']) : '';

    switch ($action) {
    case 'create':
        $order = array(
            'title' => '',
            'content' => '',
            'price' => '0',
            'screenshot' => '',
            'user_name' => '',
            'user_email' => '',
            'user_tel' => '',
            'user_comment' => '',
        );
        v3d_display_order($order, -1);
        break;
    case 'createorder':

        $order = array(
            'title' => (!empty($_REQUEST['title'])) ?
                    sanitize_text_field($_REQUEST['title']) : 'Unknown Order',
            'content' => (!empty($_REQUEST['content'])) ?
                    sanitize_textarea_field($_REQUEST['content']) : '',
            'price' => (!empty($_REQUEST['price'])) ?
                    sanitize_text_field($_REQUEST['price']) : '0',
            'screenshot' => (!empty($_REQUEST['screenshot'])) ?
                    sanitize_text_field($_REQUEST['screenshot']) : '',
            'user_name' => (!empty($_REQUEST['user_name'])) ?
                    sanitize_text_field($_REQUEST['user_name']) : '',
            'user_email' => (!empty($_REQUEST['user_email'])) ?
                    sanitize_email($_REQUEST['user_email']) : '',
            'user_tel' => (!empty($_REQUEST['user_tel'])) ?
                    sanitize_text_field($_REQUEST['user_tel']) : '',
            'user_comment' => (!empty($_REQUEST['user_comment'])) ?
                    sanitize_textarea_field($_REQUEST['user_comment']) : '',
        );

        v3d_create_order($order);
        v3d_redirect_order_list();
        break;
    case 'edit':
        $order_id = intval($_REQUEST['order']);

        if (empty($order_id)) {
            echo 'Bad request';
            return;
        }

        $order = array(
            'title' => get_the_title($order_id),
            'content' => get_post_field('post_content', $order_id),
            'price' => get_post_meta($order_id, 'price', true),
            'screenshot' => get_post_meta($order_id, 'screenshot', true),
            'user_name' => get_post_meta($order_id, 'user_name', true),
            'user_email' => get_post_meta($order_id, 'user_email', true),
            'user_tel' => get_post_meta($order_id, 'user_tel', true),
            'user_comment' => get_post_meta($order_id, 'user_comment', true),
        );

        v3d_display_order($order, $order_id);

        break;
    case 'editorder':
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_REQUEST['order'])) {
            $order_id = intval($_REQUEST['order']);

            if (!empty($_REQUEST['title']) || !empty($_REQUEST['content'])) {
                $post_arr = array(
                    'ID'           => $order_id,
                    'post_title'   => (!empty($_REQUEST['title'])) ?
                            sanitize_text_field($_REQUEST['title']) : '',
                    'post_content'   => (!empty($_REQUEST['content'])) ?
                            sanitize_textarea_field($_REQUEST['content']) : '',
                    'post_status'  => 'publish',
                    'post_type'    => 'v3d_order',
                    'meta_input'   => array(
                        'price' => (!empty($_REQUEST['price'])) ?
                                sanitize_text_field($_REQUEST['price']) : '0',
                        'screenshot' => (!empty($_REQUEST['screenshot'])) ? 
                                sanitize_text_field($_REQUEST['screenshot']) : '',
                        'user_name' => (!empty($_REQUEST['user_name'])) ?
                                sanitize_text_field($_REQUEST['user_name']) : '',
                        'user_email' => (!empty($_REQUEST['user_email'])) ?
                                sanitize_email($_REQUEST['user_email']) : '',
                        'user_tel' => (!empty($_REQUEST['user_tel'])) ?
                                sanitize_text_field($_REQUEST['user_tel']) : '',
                        'user_comment' => (!empty($_REQUEST['user_comment'])) ?
                                sanitize_textarea_field($_REQUEST['user_comment']) : '',
                    ),
                );
                wp_update_post($post_arr);
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
            if (is_array($order))
                foreach ($order as $o)
                    if (!empty(intval($o)))
                        v3d_delete_order(intval($o));
            else
                if (!empty(intval($order)))
                    v3d_delete_order($order);

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
          <a href="?page=verge3d_order&action=create" class="page-title-action">Add New</a>

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
        'post_title'   => $order['title'],
        'post_content'   => $order['content'],
        'post_status'  => 'publish',
        'post_type'    => 'v3d_order',
        'meta_input'   => array(
            'price' => $order['price'],
            'screenshot' => $order['screenshot'],
            'user_name' => $order['user_name'],
            'user_email' => $order['user_email'],
            'user_tel' => $order['user_tel'],
            'user_comment' => $order['user_comment'],
        ),
    );
    wp_insert_post($post_arr);

    
    $order_email = get_option('v3d_order_email');
    $order_from_name = get_option('v3d_order_email_from_name');
    $order_from_email = get_option('v3d_order_email_from_email');

    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    if (!empty($order_from_name) || !empty($order_from_email)) {
        $headers[] = 'From: "'.$order_from_name.'" <'.$order_from_email.'>';
    }

    ob_start();
    include v3d_get_template('order_email_body.php');
    $body_template = ob_get_clean();
    
    $attachments = array();
    if (!empty($order['screenshot'])) {
        $scr_file = v3d_get_upload_dir().'screenshots/'.basename($order['screenshot']);
        if (is_file($scr_file))
            $attachments[] = $scr_file;
    }

    if (!empty($order_email)) {
        $to = $order_email;
        $subject = 'New order notification';
        $body = $body_template;
        wp_mail($to, $subject, $body, $headers, $attachments);
    }

    if (!empty($order['user_email'])) {
        $to = $order['user_email'];
        $subject = 'Order notification';
        $body = $body_template;
        wp_mail($to, $subject, $body, $headers, $attachments);
    }
}

function v3d_display_order($order, $order_id) {
    $title = $order['title'];
    $content = $order['content'];
    $price = $order['price'];
    $screenshot = $order['screenshot'];
    $user_name = $order['user_name'];
    $user_email = $order['user_email'];
    $user_tel = $order['user_tel'];
    $user_comment = $order['user_comment'];

    ?>
    <div class="wrap">
      <h1 class="wp-heading-inline"><?php echo $order_id > -1 ? 'Update Order' : 'Create Order' ?></h1>
      <form method="post" id="updateorderform">
        <input type="hidden" name="page" value="<?php echo sanitize_text_field($_REQUEST['page']) ?>" />
        <input type="hidden" name="action" value="<?php echo $order_id > -1 ? 'editorder' : 'createorder' ?>" />
        <input type="hidden" name="order" value="<?php echo $order_id ?>" />
        <table class="form-table">
          <tbody>
            <tr class="form-field form-required">
              <th scope="row">
                <label for="title">Title <span class="description">(required)</span></label>
              </th>
              <td>
                <input type="text" name="title" id="title" value="<?php echo $title ?>" required="true" autocapitalize="none" autocorrect="off" maxlength="200">
              </td>
            </tr>
            <tr class="form-field form-required">
              <th scope="row">
                <label for="content">Content <span class="description">(required)</span></label>
              </th>
              <td>
                <input type="text" name="content" id="content" value="<?php echo $content ?>" required="true" autocapitalize="none" autocorrect="off" maxlength="200">
              </td>
            </tr>
            <tr class="form-field form-required">
              <th scope="row">
                <label for="price">Total Price <span class="description">(required)</span></label>
              </th>
              <td>
                <input type="text" name="price" id="price" value="<?php echo $price ?>" required="true" >
              </td>
            </tr>
            <tr class="form-field form-required">
              <th scope="row">
                <label for="user_name">Customer Name <span class="description">(required)</span></label>
              </th>
              <td>
                <input type="text" name="user_name" id="user_name" value="<?php echo $user_name ?>" required="true" >
              </td>
            </tr>
            <tr class="form-field form-required">
              <th scope="row">
                <label for="user_email">Customer E-Mail <span class="description">(required)</span></label>
              </th>
              <td>
                <input type="email" name="user_email" id="user_email" value="<?php echo $user_email ?>" required="true" >
              </td>
            </tr>
            <tr class="form-field form-required">
              <th scope="row">
                <label for="user_tel">Customer Phone <span class="description">(required)</span></label>
              </th>
              <td>
                <input type="tel" name="user_tel" id="user_tel" value="<?php echo $user_tel ?>" required="true" >
              </td>
            </tr>
            <tr class="form-field">
              <th scope="row">
                <label for="user_comment">Comments</label>
              </th>
              <td>
                <input type="tel" name="user_comment" id="user_comment" value="<?php echo $user_comment ?>">
              </td>
            </tr>
            <tr class="form-field">
              <th scope="row">
                <label for="screenshot">Screenshot</label>
              </th>
              <td>
                <img src="<?php echo $screenshot ?>" id="screenshot" class="v3d-admin-screenshot">
              </td>
            </tr>
          </tbody>
        </table>
        <p class="submit"><input type="submit" class="button button-primary"></p>
      </form>
    </div>
    <?php
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
        case 'content':
        case 'price':
        case 'user_name':
        case 'user_email':
        case 'user_tel':
        case 'date':
            return $item[$column_name];
        default:
            return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    function column_title($item){
        
        // Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&order=%s">Edit</a>',
                    sanitize_text_field($_REQUEST['page']), 'edit', $item['ID']),
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
            'content' => 'Content',
            'price'   => 'Total Price',
            'user_name'   => 'Customer',
            'user_email'   => 'Customer Email',
            'user_tel'   => 'Phone Number',
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
            'user_tel'   => array('user_tel', false),
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
            $email = get_post_meta($q_post->ID, 'user_email', true);

            $posts[] = array(
                'ID'     => $q_post->ID,
                'title'  => $q_post->post_title,
                'content'  => $q_post->post_content,
                'price' => get_post_meta($q_post->ID, 'price', true),
                'user_name' => get_post_meta($q_post->ID, 'user_name', true),
                'user_email' => '<a href="mailto:'.$email.'">'.$email.'</a>',
                'user_tel' => get_post_meta($q_post->ID, 'user_tel', true),
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
        $screenshot = sanitize_text_field($_REQUEST['v3d_screenshot']);

        if ($action != 'submit') {
            $screenshot = str_replace('data:image/png;base64,', '', $screenshot);
            $screenshot = str_replace(' ', '+', $screenshot);

            $upload_dir = v3d_get_upload_dir();
            $screenshot_dir = $upload_dir.'screenshots/'; 
            if (!is_dir($screenshot_dir)) {
                mkdir($screenshot_dir, 0777, true);
            }

            $data = base64_decode($screenshot);
            $file = $screenshot_dir.time().'.png';
            $success = file_put_contents($file, $data);
            if ($success)
                $screenshot = v3d_get_upload_url().'screenshots/'.basename($file);
        }
    }

    $user_name = (!empty($_REQUEST['v3d_user_name'])) ? sanitize_text_field($_REQUEST['v3d_user_name']) : '';
    $user_email = (!empty($_REQUEST['v3d_user_email'])) ? sanitize_email($_REQUEST['v3d_user_email']) : '';
    $user_tel = (!empty($_REQUEST['v3d_user_tel'])) ? sanitize_text_field($_REQUEST['v3d_user_tel']) : '';
    $user_comment = (!empty($_REQUEST['v3d_user_comment'])) ? sanitize_textarea_field($_REQUEST['v3d_user_comment']) : '';

    if ($action == 'submit') {
        if ($_SESSION['captcha_string'] == sanitize_text_field($_REQUEST["v3d_captcha"])) {
            v3d_create_order(array(
                'title' => $title,
                'content' => $content,
                'price' => $price,
                'screenshot' => $screenshot,
                'user_name' => $user_name,
                'user_email' => $user_email,
                'user_tel' => $user_tel,
                'user_comment' => $user_comment,
            ));
            ob_start();
            include v3d_get_template('order_success.php');
            return ob_get_clean();
        } else {
            ob_start();
            include v3d_get_template('order_failed.php');
            return ob_get_clean();
        }
    } else {
        $_SESSION['count'] = time();
        v3d_create_captcha();
        $captcha_url = v3d_get_upload_url().'captcha/'.$_SESSION['count'].'.png';

        ob_start();
        include v3d_get_template('order_form.php');
        return ob_get_clean();
    }
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

function v3d_create_captcha()
{
    global $image;
    $image = imagecreatetruecolor(150, 40) or die("Cannot Initialize new GD image stream");
    $background_color = imagecolorallocate($image, 255, 255, 255);
    $text_color = imagecolorallocate($image, 0, 255, 255);
    $line_color = imagecolorallocate($image, 64, 64, 64);
    $pixel_color = imagecolorallocate($image, 128, 128, 255);
    imagefilledrectangle($image, 0, 0, 150, 40, $background_color);
    //for ($i = 0; $i < 3; $i++) {
    //    imageline($image, 0, rand() % 40, 150, rand() % 40, $line_color);
    //}
    for ($i = 0; $i < 500; $i++) {
        imagesetpixel($image, rand() % 150, rand() % 40, $pixel_color);
    }
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $len = strlen($letters);
    $letter = $letters[rand(0, $len - 1)];
    $text_color = imagecolorallocate($image, 0, 0, 0);
    $word = "";
    for ($i = 0; $i < 6; $i++) {
        $letter = $letters[rand(0, $len - 1)];
        imagestring($image, 5, 20 + ($i * 20), 12, $letter, $text_color);
        $word .= $letter;
    }
    $_SESSION['captcha_string'] = $word;

    $upload_dir = v3d_get_upload_dir();
    $captcha_dir = $upload_dir.'captcha/';

    if (!is_dir($captcha_dir)) {
        mkdir($captcha_dir, 0777, true);
    }

    $images = glob($captcha_dir.'*.png');

    foreach ($images as $image_to_delete) {
        @unlink($image_to_delete);
    }
    imagepng($image, $captcha_dir . $_SESSION['count'] . ".png");
}

