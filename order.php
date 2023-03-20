<?php

if (!class_exists('WP_List_Table')){
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

$ALLOWED_MIME_TYPES = array(
    'image/png' => 'png',
    'image/jpeg' => 'jpeg',
    'image/webp' => 'webp',
    'audio/mpeg' => 'mp3',
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
    'text/plain' => 'txt',
    'text/csv' => 'csv',
    'application/pdf' => 'pdf',
    'model/gltf+json' => 'gltf',
    'model/gltf-binary' => 'glb',
);

function v3d_order_menu()
{
    if (!current_user_can('manage_verge3d')) {
        echo 'Access denied';
        return;
    }

    $screen_id = get_current_screen()->id;

    add_filter('admin_footer_text', 'v3d_replace_footer');

    $action = (!empty($_REQUEST['action'])) ? sanitize_text_field($_REQUEST['action']) : '';

    switch ($action) {
    case 'createform':
        $order = array();
        v3d_order_add_metaboxes($order, -1, $screen_id);
        v3d_display_order($order, -1);
        break;
    case 'create':
        v3d_save_order(v3d_admin_form_request_to_order());
        v3d_redirect_order_list();
        break;
    case 'editform':
        $order_id = intval($_REQUEST['order']);

        if (empty($order_id)) {
            echo 'Bad request';
            return;
        }

        $order = v3d_get_order_by_id($order_id);
        v3d_order_add_metaboxes($order, $order_id, $screen_id);
        v3d_display_order($order, $order_id);

        break;
    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_REQUEST['order'])) {
            $order_id = intval($_REQUEST['order']);

            v3d_update_order($order_id, v3d_admin_form_request_to_order());
            v3d_redirect_same();
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
    case 'genpdf':
        if (!empty($_REQUEST['order'])) {
            ob_end_clean();

            $order_id = intval($_REQUEST['order']);
            $order = v3d_get_order_by_id($order_id);
            $pdftype = esc_attr($_REQUEST['pdftype']);

            $attachments = v3d_gen_email_attachments($order, $order_id, false, [$pdftype]);

            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="'.$pdftype.'.pdf"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($attachments[0]));

            readfile($attachments[0]);

            v3d_cleanup_email_attachments($attachments);
        }
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

// Get order array by post ID
function v3d_get_order_by_id($order_id) {
    if (empty($order_id) or $order_id < 0)
        return null;
    return update_order_compat(json_decode(get_post_field('post_content', $order_id), true));
}

// COMPAT: < Verge3D 4.1
function update_order_compat($order) {
    if (!isset($order['items'])) {
        $order['items'] = array(array(
            'title' => $order['content'],
            'sku' => '',
            'price' => intval($order['price']),
            'quantity' => 1
        ));

        unset($order['title']);
        unset($order['content']);
        unset($order['price']);

        $order['discount'] = 0;
        $order['tax'] = 0;
    }

    return $order;
}

function calc_subtotal_price($order_items, $round_result=false) {
    if (empty($order_items))
        return 0;

    $subtotal_price = 0;

    foreach ($order_items as $item) {
        $subtotal_price += $item['price'] * $item['quantity'];
    }

    if ($round_result)
        $subtotal_price = round($subtotal_price, get_option('v3d_price_decimals'));

    return $subtotal_price;
}

function calc_discount($order, $round_result=false) {
    if (empty($order))
        return 0;

    $subtotal_price = calc_subtotal_price($order['items']);
    $discount = $subtotal_price * $order['discount'] / 100;

    if ($round_result)
        $discount = round($discount, get_option('v3d_price_decimals'));

    return $discount;
}

function calc_tax($order, $round_result=false) {
    if (empty($order))
        return 0;

    $discounted_price = calc_subtotal_price($order['items']) - calc_discount($order);
    $tax = $discounted_price * $order['tax'] / 100;

    if ($round_result)
        $tax = round($tax, get_option('v3d_price_decimals'));

    return $tax;
}

function calc_total_price($order, $round_result=false) {
    if (empty($order))
        return 0;

    $total_price = calc_subtotal_price($order['items']);

    if (!empty($order['discount']))
        $total_price -= calc_discount($order);

    if (!empty($order['tax']))
        $total_price += calc_tax($order);

    if ($round_result)
        $total_price = round($total_price, get_option('v3d_price_decimals'));

    return $total_price;
}

function v3d_format_order($text, $order, $order_id) {
    $out = $text;
    $out = str_replace('%c', $order['user_name'], $out);
    $out = str_replace('%n', $order_id, $out);
    return $out;
}

// format price
function v3d_price($value) {
    if (!empty($value))
        return v3d_currency_symbol().$value;
    else
        return $value;
}

function v3d_get_order_downloads($order_id) {
    $order = v3d_get_order_by_id($order_id);
    $downloads = array();

    if (!$order)
        return $downloads;

    foreach ($order['items'] as $item) {
        $product = v3d_find_product_by_sku($item['sku']);
        if (!empty($product) && !empty($product['download_link'])) {
            $downloads[hash('sha1', $order_id.$product['id'])] = array(
                'title' => $item['title'],
                'link' => $product['download_link'],
            );
        }
    }

    return $downloads;
}

function v3d_save_order($order, $send_emails=true) {
    $post_arr = array(
        'post_title'   => '',
        'post_content' => json_encode($order, JSON_UNESCAPED_UNICODE),
        'post_status'  => 'publish',
        'post_type'    => 'v3d_order'
    );

    $order = apply_filters('v3d_save_order', $order);
    if (empty($order))
        return null;

    // can never be 0
    $order_id = wp_insert_post($post_arr);

    if ($send_emails)
        v3d_send_emails('new', $order, $order_id);

    return $order_id;
}

function v3d_send_emails($notify_type, $order, $order_id) {
    $order_email = get_option('v3d_order_email');
    $order_from_name = get_option('v3d_order_email_from_name');
    $order_from_email = get_option('v3d_order_email_from_email');

    $attachments = array();

    $send_me = !empty($order_email) && $notify_type != 'quote' && $notify_type != 'invoice' && get_option("v3d_order_email_{$notify_type}_notify");
    $send_user = !empty($order['user_email']) && ($notify_type == 'quote' || $notify_type == 'invoice' || get_option("v3d_order_email_{$notify_type}_notify_user"));

    if ($send_me || $send_user) {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        if (!empty($order_from_name) || !empty($order_from_email)) {
            $headers[] = 'From: "'.$order_from_name.'" <'.$order_from_email.'>';
        }

        $pdftypes = array();

        switch ($notify_type) {
            case 'quote':
                $att_custom = false;
                $pdftypes[] = 'quote';
                break;
            case 'invoice':
                $att_custom = false;
                $pdftypes[] = 'invoice';
                break;
            default:
                $att_custom = get_option("v3d_order_email_{$notify_type}_attach_custom");
                if (get_option("v3d_order_email_{$notify_type}_attach_quote"))
                    $pdftypes[] = 'quote';
                if (get_option("v3d_order_email_{$notify_type}_attach_invoice"))
                    $pdftypes[] = 'invoice';
                break;
        }

        $attachments = v3d_gen_email_attachments($order, $order_id, $att_custom, $pdftypes);
    }

    if ($send_me) {
        $to = $order_email;
        $subject = get_option("v3d_order_email_{$notify_type}_subject");

        ob_start();
        include v3d_get_template('order_email_body.php');
        $body = ob_get_clean();

        wp_mail($to, $subject, $body, $headers, $attachments);
    }

    if ($send_user) {
        $to = $order['user_email'];
        $subject = get_option("v3d_order_email_{$notify_type}_subject_user");

        ob_start();
        include v3d_get_template('order_email_body.php');
        $body = ob_get_clean();

        wp_mail($to, $subject, $body, $headers, $attachments);
        //file_put_contents('/var/www/wordpress/mail.html', $body);
    }

    v3d_cleanup_email_attachments($attachments);
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

function v3d_gen_email_attachments($order, $order_id, $gen_custom, $gen_pdftypes=array()) {

    $attachments = array();

    if ($gen_custom and !empty($order['attachments'])) {
        foreach ($order['attachments'] as $index => $att_url) {
            $att_file = v3d_get_upload_dir().'attachments/'.basename($att_url);
            if (is_file($att_file)) {
                $name = 'attachment'.($index >= 1 ? $index+1 : '').'.'.pathinfo($att_file, PATHINFO_EXTENSION);
                $att_path_tmp = v3d_get_attachments_tmp_dir($attachments).$name;
                copy($att_file, $att_path_tmp);
                $attachments[] = $att_path_tmp;
            }
        }
    }

    $chrome_path = v3d_get_chrome_path();

    if (!empty($chrome_path)) {
        foreach ($gen_pdftypes as $pdftype) {
            ob_start();
            include v3d_get_template('order_pdf.php');
            $pdf_html_text = ob_get_clean();

            $temp_dir = get_temp_dir();
            $pdf_html = $temp_dir.wp_unique_filename($temp_dir, uniqid('v3d_email_att').'.html');
            $pdf = v3d_get_attachments_tmp_dir($attachments).$pdftype.'.pdf';

            $success = file_put_contents($pdf_html, $pdf_html_text);
            //copy($pdf_html, '/var/www/wordpress/pdf.html');

            if ($success) {
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

function v3d_update_order($order_id, $order, $send_emails=true) {
    $post_arr = array(
        'ID'           => $order_id,
        'post_title'   => '',
        'post_content' => json_encode($order, JSON_UNESCAPED_UNICODE),
        'post_status'  => 'publish',
        'post_type'    => 'v3d_order'
    );

    wp_update_post($post_arr);

    if ($send_emails)
        v3d_send_emails('update', $order, $order_id);
}

function v3d_admin_form_request_to_order() {
    $order = array();

    $IGNORED_KEYS = ['page', 'action', 'order', 'order_status', 'order_items', 'payment'];

    foreach ($_POST as $key => $value) {
        if (!in_array($key, $IGNORED_KEYS, true))
            $order[$key] = $value;
    }

    if (!empty($_POST['order_status']))
        $order['status'] = preg_replace('/^order_status_/', '', $_POST['order_status']);
    else
        $order['status'] = 'pending';

    if (!empty($_POST['order_items']))
        $order['items'] = json_decode(stripslashes($_POST['order_items']), true);
    else
        $order['items'] = array();

    if (!empty($_POST['payment']))
        $order['payment'] = json_decode(stripslashes($_POST['payment']), true);

    return $order;
}


function v3d_order_add_metaboxes($order, $order_id, $screen_id) {

    add_meta_box(
        'v3d_order_table_mb',
        'Order',
        'v3d_order_add_table_metabox',
        $screen_id,
        'normal',
        'default',
        array(
            'order' => $order,
            'order_id' => $order_id,
        )
    );

    if (get_option('v3d_require_billing_address')) {
        add_meta_box(
            'v3d_order_billing_mb',
            'Billing address',
            'v3d_order_billing_metabox',
            $screen_id,
            'normal',
            'default',
            array(
                'order' => $order,
                'order_id' => $order_id,
            )
        );
    }

    if (get_option('v3d_require_shipping_address')) {
        add_meta_box(
            'v3d_order_shipping_mb',
            'Shipping address',
            'v3d_order_shipping_metabox',
            $screen_id,
            'normal',
            'default',
            array(
                'order' => $order,
                'order_id' => $order_id,
            )
        );
    }

    add_meta_box(
        'v3d_order_totals_mb',
        'Totals',
        'v3d_order_add_totals_metabox',
        $screen_id,
        'side',
        'default',
        array(
            'order' => $order,
            'order_id' => $order_id,
        )
    );

    $downloads = v3d_get_order_downloads($order_id);
    if (!empty($downloads)) {
        add_meta_box(
            'v3d_order_downloads_mb',
            'Downloads',
            'v3d_order_add_downloads_metabox',
            $screen_id,
            'side',
            'default',
            array(
                'order_id' => $order_id,
                'downloads' => $downloads,
            )
        );
    }

    add_meta_box(
        'v3d_order_actions_mb',
        'Actions',
        'v3d_order_add_actions_metabox',
        $screen_id,
        'side',
        'default',
        array(
            'order' => $order,
            'order_id' => $order_id,
        )
    );
}

function v3d_display_order($order, $order_id) {
    ?>
    <div class="wrap">
      <h1 class="wp-heading-inline"><?php echo $order_id > -1 ? 'Update Order' : 'Create Order' ?></h1>

      <form method="post" id="updateorderform">
        <input type="hidden" name="page" value="<?php echo sanitize_text_field($_REQUEST['page']) ?>" />
        <input type="hidden" name="action" value="<?php echo $order_id > -1 ? 'edit' : 'create' ?>" />
        <input type="hidden" name="order" value="<?php echo esc_attr($order_id) ?>" />
        <input type="hidden" name="order_items" value='<?php echo json_encode(empty($order["items"]) ? array() : $order["items"], JSON_UNESCAPED_UNICODE) ?>' />
        <input type="hidden" name="payment" value='<?php echo json_encode(empty($order["payment"]) ? array() : $order["payment"], JSON_UNESCAPED_UNICODE) ?>' />
        <?php if (!empty($order['attachments'])): ?>
          <?php foreach($order['attachments'] as $att): ?>
            <input type="hidden" name="attachments[]" value="<?= $att ?>" />
          <?php endforeach; ?>
        <?php endif; ?>

        <div id="poststuff">
          <div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">

            <div id="post-body-content">
                <?php do_meta_boxes('', 'normal', null); ?>
            </div>

            <div id="postbox-container-1" class="postbox-container">
                <?php do_meta_boxes('', 'side', null); ?>
            </div>

            <div id="postbox-container-2" class="postbox-container">
                <?php do_meta_boxes('', 'advanced', null); ?>
            </div>
          </div>
        </div>
      </form>
    </div>

    <div class="dialog-bg" id="add_product_item">
      <div class="dialog">
        <div class="dialog-heading">Add product</div>
        <table>
          <tbody>
            <tr class="dialog-item">
              <th>
                <label for="add_product_item_select">Product</label>
              </th>
              <td>
                <select id="add_product_item_select"></select>
              </td>
            </tr>
            <tr class="dialog-item">
              <th>
                <label for="add_product_item_quantity">Quantity</label>
              </th>
              <td>
                <input type="text" id="add_product_item_quantity">
              </td>
            </tr>
          </tbody>
        </table>

        <div class="dialog-buttons">
          <button onclick="add_product_item_save_cb()" class="wp-core-ui button">Save</button>
          <button onclick="add_product_item_cancel_cb()" class="wp-core-ui button">Cancel</button>
        </div>
      </div>
    </div>

    <div class="dialog-bg" id="edit_order_item">
      <div class="dialog">
        <div class="dialog-heading">Update order item</div>
        <table>
          <tbody>
            <tr class="dialog-item">
              <th>
                <label for="edit_order_item_title">Item</label>
              </th>
              <td>
                <input type="text" id="edit_order_item_title">
              </td>
            </tr>
            <tr class="dialog-item">
              <th>
                <label for="edit_order_item_sku">SKU</label>
              </th>
              <td>
                <input type="text" id="edit_order_item_sku">
              </td>
            </tr>
            <tr class="dialog-item">
              <th>
                <label for="edit_order_item_price">Price</label>
              </th>
              <td>
                <input type="text" id="edit_order_item_price">
              </td>
            </tr>
            <tr class="dialog-item">
              <th>
                <label for="edit_order_item_quantity">Quantity</label>
              </th>
              <td>
                <input type="text" id="edit_order_item_quantity">
              </td>
            </tr>
          </tbody>
        </table>

        <div class="dialog-buttons">
          <button onclick="edit_order_item_save_cb()" class="wp-core-ui button">Save</button>
          <button onclick="edit_order_item_cancel_cb()" class="wp-core-ui button">Cancel</button>
        </div>
      </div>
    </div>

    <div class="dialog-bg" id="quote_sent">
      <div class="dialog dialog-quote-sent">
        <div class="dialog-heading">Quote sent successfully</div>
        <div class="dialog-buttons">
          <button onclick="quote_sent_close_cb()" class="button">OK</button>
        </div>
      </div>
    </div>

    <div class="dialog-bg" id="invoice_sent">
      <div class="dialog dialog-quote-sent">
        <div class="dialog-heading">Invoice sent successfully</div>
        <div class="dialog-buttons">
          <button onclick="invoice_sent_close_cb()" class="button">OK</button>
        </div>
      </div>
    </div>
    <?php
}

function v3d_order_add_table_metabox($post, $metabox) {
    $order = $metabox['args']['order'];
    $order_id = $metabox['args']['order_id'];
    ?>
      <table class="form-table">
        <tbody>
          <tr class="form-field">
            <th scope="row">
              <label for="order_status">Order Status</span></label>
            </th>
            <td>
              <select id="order_status" name="order_status">
                <option value="order_status_pending" <?php echo (!empty($order['status']) and $order['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                <option value="order_status_processing" <?php echo (!empty($order['status']) and $order['status'] === 'processing') ? 'selected' : '' ?>>Processing</option>
                <option value="order_status_shipped" <?php echo (!empty($order['status']) and $order['status'] === 'shipped') ? 'selected' : '' ?>>Shipped</option>
                <option value="order_status_completed" <?php echo (!empty($order['status']) and $order['status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
                <option value="order_status_cancelled" <?php echo (!empty($order['status']) and $order['status'] === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                <option value="order_status_refunded" <?php echo (!empty($order['status']) and $order['status'] === 'refunded') ? 'selected' : '' ?>>Refunded</option>
                <option value="order_status_draft" <?php echo (!empty($order['status']) and $order['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
              </select>
            </td>
          </tr>
          <tr class="form-field form-required">
            <th scope="row">
              <label for="user_name">Customer Name <span class="description">(required)</span></label>
            </th>
            <td>
              <input type="text" name="user_name" id="user_name" value="<?php echo empty($order['user_name']) ? '' : esc_html($order['user_name']) ?>" required="true" >
            </td>
          </tr>
          <tr class="form-field form-required">
            <th scope="row">
              <label for="user_email">Customer E-Mail <span class="description">(required)</span></label>
            </th>
            <td>
              <input type="email" name="user_email" id="user_email" value="<?php echo empty($order['user_email']) ? '' : esc_html($order['user_email']) ?>" required="true" >
            </td>
          </tr>
          <tr class="form-field form-required">
            <th scope="row">
              <label for="user_phone">Customer Phone <span class="description">(required)</span></label>
            </th>
            <td>
              <input type="tel" name="user_phone" id="user_phone" value="<?php echo empty($order['user_phone']) ? '' : esc_html($order['user_phone']) ?>" required="true" >
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label>Items</label>
            </th>
            <td class="v3d-admin-order-items-row">
              <div class="v3d-admin-order-items">
                <?php
                  $order_items_table = new V3D_Order_Item_List_Table();
                  $order_items_table->set_source_items(empty($order['items']) ? array() : $order['items']);
                  $order_items_table->prepare_items();
                  $order_items_table->display()
                ?>
              </div>
            </td>
          </tr>
          <tr class="form-field">
            <th scope="row">
              <label for="discount">Discount %</label>
            </th>
            <td>
              <input type="number" step="any" name="discount" id="discount" value="<?php echo empty($order['discount']) ? '0' : esc_html($order['discount']) ?>">
            </td>
          </tr>
          <tr class="form-field">
            <th scope="row">
              <label for="tax">Tax %</label>
            </th>
            <td>
              <input type="number" step="any" name="tax" id="tax" value="<?php echo empty($order['tax']) ? '0' : esc_html($order['tax']) ?>">
            </td>
          </tr>
          <tr class="form-field">
            <th scope="row">
              <label for="user_comment">Comments</label>
            </th>
            <td>
              <input type="text" name="user_comment" id="user_comment" value="<?php echo empty($order['user_comment']) ? '' : esc_html($order['user_comment']) ?>">
            </td>
          </tr>
          <?php if (!empty($order['attachments'])): ?>
            <tr class="form-field">
              <th scope="row">
                <label for="attachments">Attachments</label>
              </th>
              <td>
                <?php foreach($order['attachments'] as $att): ?>
                  <a href="<?= esc_url($att); ?>" target="_blank"><img src="<?= esc_url(v3d_attachment_icon($att)); ?>" id="attachments" class="v3d-admin-attachments"></a>
                <?php endforeach; ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

    <?php
}

function v3d_attachment_icon($att) {
    if (in_array(pathinfo($att, PATHINFO_EXTENSION), ['png', 'jpeg', 'webp']))
        return $att;
    else
        return plugin_dir_url(__FILE__) . 'images/document.svg';
}

function v3d_order_billing_metabox($post, $metabox) {
    $order = $metabox['args']['order'] ;
    $order_id = $metabox['args']['order_id'];
    ?>
      <table class="form-table">
        <tbody>
          <tr class="form-field form-required">
            <th scope="row">
              <label for="user_address1">Address 1 <span class="description">(required)</span></label>
            </th>
            <td>
              <input type="text" name="user_address1" id="user_address1" value="<?php echo empty($order['user_address1']) ? '' : esc_html($order['user_address1']) ?>" required="true" >
            </td>
          </tr>
          <tr class="form-field">
            <th scope="row">
              <label for="user_address2">Address 2</label>
            </th>
            <td>
              <input type="text" name="user_address2" id="user_address2" value="<?php echo empty($order['user_address2']) ? '' : esc_html($order['user_address2']) ?>">
            </td>
          </tr>
          <tr class="form-field">
            <th scope="row">
              <label for="user_city">City</label>
            </th>
            <td>
              <input type="text" name="user_city" id="user_city" value="<?php echo empty($order['user_city']) ? '' : esc_html($order['user_city']) ?>">
            </td>
          </tr>
          <tr class="form-field">
            <th scope="row">
              <label for="user_state">State / County</label>
            </th>
            <td>
              <input type="text" name="user_state" id="user_state" value="<?php echo empty($order['user_state']) ? '' : esc_html($order['user_state']) ?>">
            </td>
          </tr>
          <tr class="form-field form-required">
            <th scope="row">
              <label for="user_country">Country <span class="description">(required)</span></label>
            </th>
            <td>
              <input type="text" name="user_country" id="user_country" value="<?php echo empty($order['user_country']) ? '' : esc_html($order['user_country']) ?>" required="true" >
            </td>
          </tr>
          <tr class="form-field form-required">
            <th scope="row">
              <label for="user_postcode">Postcode <span class="description">(required)</span></label>
            </th>
            <td>
              <input type="text" name="user_postcode" id="user_postcode" value="<?php echo empty($order['user_postcode']) ? '' : esc_html($order['user_postcode']) ?>" required="true" >
            </td>
          </tr>
        </tbody>
      </table>
    <?php
}

function v3d_order_shipping_metabox($post, $metabox) {
    $order = $metabox['args']['order'];
    $order_id = $metabox['args']['order_id'];
    ?>
      <table class="form-table">
        <tbody>
          <tr class="form-field form-required">
            <th scope="row">
              <label for="shipping_address1">Address 1 <span class="description">(required)</span></label>
            </th>
            <td>
              <input type="text" name="shipping_address1" id="shipping_address1" value="<?php echo empty($order['shipping_address1']) ? '' : esc_html($order['shipping_address1']) ?>" required="true" >
            </td>
          </tr>
          <tr class="form-field">
            <th scope="row">
              <label for="shipping_address2">Address 2</label>
            </th>
            <td>
              <input type="text" name="shipping_address2" id="shipping_address2" value="<?php echo empty($order['shipping_address2']) ? '' : esc_html($order['shipping_address2']) ?>">
            </td>
          </tr>
          <tr class="form-field">
            <th scope="row">
              <label for="shipping_city">City</label>
            </th>
            <td>
              <input type="text" name="shipping_city" id="shipping_city" value="<?php echo empty($order['shipping_city']) ? '' : esc_html($order['shipping_city']) ?>">
            </td>
          </tr>
          <tr class="form-field">
            <th scope="row">
              <label for="shipping_state">State / County</label>
            </th>
            <td>
              <input type="text" name="shipping_state" id="shipping_state" value="<?php echo empty($order['shipping_state']) ? '' : esc_html($order['shipping_state']) ?>">
            </td>
          </tr>
          <tr class="form-field form-required">
            <th scope="row">
              <label for="shipping_country">Country <span class="description">(required)</span></label>
            </th>
            <td>
              <input type="text" name="shipping_country" id="shipping_country" value="<?php echo empty($order['shipping_country']) ? '' : esc_html($order['shipping_country']) ?>" required="true" >
            </td>
          </tr>
          <tr class="form-field form-required">
            <th scope="row">
              <label for="shipping_postcode">Postcode <span class="description">(required)</span></label>
            </th>
            <td>
              <input type="text" name="shipping_postcode" id="shipping_postcode" value="<?php echo empty($order['shipping_postcode']) ? '' : esc_html($order['shipping_postcode']) ?>" required="true" >
            </td>
          </tr>
        </tbody>
      </table>
    <?php
}

function v3d_order_add_totals_metabox($post, $metabox) {
    $order = $metabox['args']['order'];
    $order_id = $metabox['args']['order_id'];
    ?>
      <table class="form-table v3d-side-panel-table">
        <tr>
          <th scope="row">Subtotal:</th>
          <td><?= v3d_price(!empty($order['items']) ? calc_subtotal_price($order['items'], true) : 0); ?></td>
        </tr>
        <tr>
          <th scope="row">Total:</th>
          <td><?= v3d_price(calc_total_price($order, true)); ?></td>
        </tr>
      </table>
    <?php
}

function v3d_order_add_downloads_metabox($post, $metabox) {
    $order_id = $metabox['args']['order_id'];
    $downloads = $metabox['args']['downloads'];

    ?>
    <table class="form-table v3d-side-panel-table">
      <?php
      foreach ($downloads as $h => $d) {
        ?>
        <tr>
          <td><a href="<?= get_site_url().'?v3d_download_file='.$h.'&order='.$order_id; ?>"><?= basename($d['link']); ?></a></td>
        </tr>
        <?php
      }
      ?>
    </table>
    <?php
}

function v3d_order_add_actions_metabox($post, $metabox) {
    $order = $metabox['args']['order'];
    $order_id = $metabox['args']['order_id'];

    if ($order_id > -1) {
      echo sprintf('<p><a href="?page=%s&action=genpdf&order=%s&pdftype=quote" class="button button-primary v3d-side-panel-button v3d-half-width">Create Quote</a>', sanitize_text_field($_REQUEST['page']), $order_id);
      ?>
        <button onclick="send_pdf_cb('quote'); return false;" class="button button-primary v3d-half-width">Send Quote</button></p>
      <?php

      echo sprintf('<p><a href="?page=%s&action=genpdf&order=%s&pdftype=invoice" class="button button-primary v3d-side-panel-button v3d-half-width">Create Invoice</a>', sanitize_text_field($_REQUEST['page']), $order_id);
      ?>
        <button onclick="send_pdf_cb('invoice'); return false;" class="button button-primary v3d-half-width">Send Invoice</button></p>
      <?php
    }

    ?>
      <p><input type="submit" value="<?php echo $order_id > -1 ? 'Update Order' : 'Create Order' ?>" class="button button-primary v3d-full-width"></p>
    <?php
}

function v3d_delete_order($order_id) {

    $order = v3d_get_order_by_id($order_id);

    if (!empty($order['attachments'])) {
        foreach ($order['attachments'] as $att_url) {
            if (!empty($att_url)) {
                $att_file = v3d_get_upload_dir().'attachments/'.basename($att_url);
                if (is_file($att_file))
                    @unlink($att_file);
            }
        }
    }

    wp_delete_post($order_id);
}


class V3D_Order_List_Table extends WP_List_Table {

    function __construct() {
        global $status, $page;

        // Set parent defaults
        parent::__construct( array(
            'singular'  => 'order',
            'plural'    => 'orders',
            'ajax'      => false
        ) );

    }

    function column_default($item, $column_name) {
        switch ($column_name) {
        case 'status':
        case 'price':
        case 'user_email':
        case 'user_phone':
        case 'date':
            return $item[$column_name];
        default:
            return print_r($item, true); // show the whole array for troubleshooting purposes
        }
    }

    function column_title($item) {

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

    function column_payment($item) {
        $payment = $item['payment'];

        // Return the title contents
        return sprintf('%1$s<div style="color:silver">%2$s</div><div style="color:silver">%3$s</div>',
            /*$1%s*/ !empty($payment) ? 'Paid' : 'Unpaid',
            /*$2%s*/ !empty($payment) ? 'via '.$payment['method'] : '',
            /*$3%s*/ !empty($payment) ? 'on '.wp_date(get_option('date_format').' '.get_option('time_format'), $payment['date']) : ''
        );
    }

    // bulk actions callback
    function column_cb($item) {
        return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'], $item['ID']);
    }

    function get_columns() {
        $columns = array(
            'cb'         => '<input type="checkbox" />',
            'title'      => 'Order',
            'status'     => 'Status',
            'price'      => 'Total Price',
            'payment'    => 'Payment',
            'user_email' => 'Customer Email',
            'user_phone' => 'Phone Number',
            'date'       => 'Date',
        );
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'title'      => array('ID', false),
            'status'     => array('status', false),
            'price'      => array('price', false),
            'payment'    => array('payment', false),
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
        $orderby = !empty($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'ID';
        // if no order, default to asc
        $order = !empty($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC';

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
            'author'           => '',
            'author_name'      => '',
            'post_status'      => 'publish',
            'suppress_filters' => true,
            'fields'           => '',
        );
        $q_posts = get_posts($args);

        $posts = array();

        foreach ($q_posts as $q_post) {

            $id = $q_post->ID;
            $content = v3d_get_order_by_id($id);

            $posts[] = array(
                'ID'     => $id,
                'title'  => (!empty($content['user_name'])) ? '#'.$id.' '.$content['user_name'] : 'N/A',
                'status'  => (!empty($content['status'])) ? ucfirst($content['status']) : 'N/A',
                'price'  => v3d_price(calc_total_price($content, true)),
                'payment'  => (!empty($content['payment'])) ? $content['payment'] : '',
                'user_email'  => (!empty($content['user_email'])) ? $content['user_email'] : 'N/A',
                'user_phone'  => (!empty($content['user_phone'])) ? $content['user_phone'] : 'N/A',
                'date' => get_the_time(get_option('date_format').' '.get_option('time_format'), $id),
            );
        }

        if ($orderby != 'ID' && $orderby != 'date') {
            function build_sorter($key, $dir) {
                return function ($a, $b) use ($key, $dir) {
                    if (strtolower($dir) == 'asc')
                        return strnatcmp($a[$key], $b[$key]);
                    else
                        return -strnatcmp($a[$key], $b[$key]);
                };
            }

            usort($posts, build_sorter($orderby, $order));
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

class V3D_Order_Item_List_Table extends WP_List_Table {

    private $source_items;

    function __construct() {
        // Set parent defaults
        parent::__construct(array(
            'singular'  => 'order_item',
            'plural'    => 'order_items',
            'ajax'      => true,
            'screen'    => 'nothing',
        ));

    }

    function set_source_items($items) {
        $this->source_items = $items;
    }

    // bulk actions callback
    function column_cb($item) {
        return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'], $item['ID']);
    }

    function column_title($item) {
        // Build row actions
        $actions = array(
            'edit'   => sprintf('<a href="javascript:;" onclick="edit_order_item_cb(%1$s)">Edit</a>',
                    $item['ID']),
            'delete' => sprintf('<a href="javascript:;" onclick="delete_item_cb(%1$s)">Delete</a>',
                    $item['ID']),
        );

        // Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item['title'],
            /*$2%s*/ $item['ID'],
            /*$3%s*/ $this->row_actions($actions)
        );
    }

    function column_default($item, $column_name) {
        switch ($column_name) {
        case 'title':
        case 'sku':
        case 'price':
        case 'quantity':
            return $item[$column_name];
        default:
            return print_r($item, true); // show the whole array for troubleshooting purposes
        }
    }

    function get_columns() {
        $columns = array(
            'cb'       => '<input type="checkbox" />',
            'title'    => 'Item',
            'sku'      => 'SKU',
            'price'    => 'Item Price',
            'quantity' => 'Quantity',
        );
        return $columns;
    }

    function get_bulk_actions() {
        $actions = array(
            'delete_order_item'    => 'Delete'
        );
        return $actions;
    }

    // NOTE: overriding this method to disable nonce
    protected function display_tablenav($which) {
        ?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">

            <?php if ( $this->has_items() ) : ?>
            <div class="alignleft actions bulkactions">
                <?php $this->bulk_actions($which); ?>
            </div>
                <?php
            endif;
            $this->extra_tablenav($which);
            $this->pagination($which);
            ?>

            <br class="clear" />
        </div>
        <?php
    }

    // NOTE: overriding this method to disable form actions
    protected function bulk_actions( $which = '' ) {

        if (is_null( $this->_actions)) {
            $this->_actions = $this->get_bulk_actions();
            $this->_actions = apply_filters("bulk_actions-{$this->screen->id}", $this->_actions);

            $two = '';
        } else {
            $two = '2';
        }

        if (empty($this->_actions)) {
            return;
        }

        echo '<label for="bulk-action-selector-' . esc_attr($which) . '" class="screen-reader-text">' . __( 'Select bulk action') . '</label>';
        echo '<select id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
        echo '<option value="-1">' . __( 'Bulk actions' ) . "</option>\n";

        foreach ( $this->_actions as $key => $value ) {
            if ( is_array( $value ) ) {
                echo "\t" . '<optgroup label="' . esc_attr( $key ) . '">' . "\n";

                foreach ( $value as $name => $title ) {
                    $class = ( 'edit' === $name ) ? ' class="hide-if-no-js"' : '';

                    echo "\t\t" . '<option value="' . esc_attr( $name ) . '"' . $class . '>' . $title . "</option>\n";
                }
                echo "\t" . "</optgroup>\n";
            } else {
                $class = ( 'edit' === $key ) ? ' class="hide-if-no-js"' : '';

                echo "\t" . '<option value="' . esc_attr( $key ) . '"' . $class . '>' . $value . "</option>\n";
            }
        }

        echo "</select>\n";

        submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );
        echo "\n";
    }

    function extra_tablenav($which) {
        ?>
          <a href="javascript:;" onclick="add_product_item_cb()" class="button action">Add Product</a>
          <a href="javascript:;" onclick="add_custom_item_cb()" class="button action">Add Custom Item</a>
        <?php
    }

    function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $posts = array();

        foreach ($this->source_items as $index => $item) {
            $posts[] = array(
                'ID'       => $index,
                'title'    => (!empty($item['title'])) ? $item['title'] : 'N/A',
                'sku'      => (!empty($item['sku'])) ? $item['sku'] : 'N/A',
                'price'    => (isset($item['price'])) ? v3d_price($item['price']) : 'N/A',
                'quantity' => isset($item['quantity']) ? $item['quantity'] : 'N/A',
            );
        }
        $this->items = $posts;

        $total_items = count($posts);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $total_items,
            'total_pages' => 1
        ));
    }
}

function v3d_sanitize_order($order) {
    $order_out = array(
        'user_name' => (!empty($order['user_name'])) ? sanitize_text_field($order['user_name']) : '',
        'user_email' => (!empty($order['user_email'])) ? sanitize_email($order['user_email']) : '',
        'user_phone' => (!empty($order['user_phone'])) ? sanitize_text_field($order['user_phone']) : '',
        'user_comment' => (!empty($order['user_comment'])) ? sanitize_textarea_field($order['user_comment']) : '',
        'items' => (!empty($order['items'])) ? v3d_sanitize_order_items($order['items']) : array(),
        'attachments' => (!empty($order['attachments'])) ? v3d_sanitize_order_attachments($order['attachments']) : array(),
    );

    if (get_option('v3d_require_billing_address')) {
        $order_out['user_address1'] = (!empty($order['user_address1'])) ? sanitize_text_field($order['user_address1']) : '';
        $order_out['user_address2'] = (!empty($order['user_address2'])) ? sanitize_text_field($order['user_address2']) : '';
        $order_out['user_city'] = (!empty($order['user_city'])) ? sanitize_text_field($order['user_city']) : '';
        $order_out['user_state'] = (!empty($order['user_state'])) ? sanitize_text_field($order['user_state']) : '';
        $order_out['user_country'] = (!empty($order['user_country'])) ? sanitize_text_field($order['user_country']) : '';
        $order_out['user_postcode'] = (!empty($order['user_postcode'])) ? sanitize_text_field($order['user_postcode']) : '';
    }

    if (get_option('v3d_require_shipping_address')) {
        $order_out['shipping_address1'] = (!empty($order['shipping_address1'])) ? sanitize_text_field($order['shipping_address1']) : '';
        $order_out['shipping_address2'] = (!empty($order['shipping_address2'])) ? sanitize_text_field($order['shipping_address2']) : '';
        $order_out['shipping_city'] = (!empty($order['shipping_city'])) ? sanitize_text_field($order['shipping_city']) : '';
        $order_out['shipping_state'] = (!empty($order['shipping_state'])) ? sanitize_text_field($order['shipping_state']) : '';
        $order_out['shipping_country'] = (!empty($order['shipping_country'])) ? sanitize_text_field($order['shipping_country']) : '';
        $order_out['shipping_postcode'] = (!empty($order['shipping_postcode'])) ? sanitize_text_field($order['shipping_postcode']) : '';
    }

    return $order_out;
}

// verify & find missing fields
function v3d_sanitize_order_items($items) {

    $items_out = array();

    foreach ($items as $item) {

        $quantity = isset($item['quantity']) ? floatval($item['quantity']) : 1;

        if (!empty($item['sku'])) {
            $product = v3d_find_product_by_sku(sanitize_text_field($item['sku']));
            if (!empty($product)) {
                $items_out[] = array(
                    'title' => $product['title'],
                    'sku' => $product['sku'],
                    'price' => $product['price'],
                    'quantity' => $quantity
                );
                continue;
            }
        }

        if (get_option('v3d_custom_products')) {
            $items_out[] = array(
                'title' => (!empty($item['title'])) ? sanitize_text_field($item['title']) : '',
                'sku' => (!empty($item['sku'])) ? sanitize_text_field($item['sku']) : '',
                'price' => isset($item['price']) ? floatval($item['price']) : 0,
                'quantity' => $quantity
            );
        }
    }

    return $items_out;
}

function v3d_sanitize_order_attachments($atts) {
    $atts_out = array();

    foreach ($atts as $att) {
        $atts_out[] = sanitize_text_field($att);
    }

    return $atts_out;
}

function v3d_order_shortcode($atts = [], $content = null, $tag = '') {
    // normalize attribute keys, lowercase
    $atts = array_change_key_case((array)$atts, CASE_LOWER);

    $action = (!empty($_REQUEST['v3d_action'])) ? sanitize_text_field($_REQUEST['v3d_action']) : '';
    $items = (!empty($_REQUEST['v3d_items'])) ? v3d_sanitize_order_items(json_decode(
            stripslashes($_REQUEST['v3d_items']), true)) : array();

    $attachments = array();
    if (!empty($_REQUEST['v3d_attachments'])) {
        if ($action !== 'submit') {
            $attachments = v3d_save_order_attachments(v3d_sanitize_order_attachments($_REQUEST['v3d_attachments']));
        } else {
            $attachments = v3d_sanitize_order_attachments($_REQUEST['v3d_attachments']);
        }
    }

    $user_name = (!empty($_REQUEST['v3d_user_name'])) ? sanitize_text_field($_REQUEST['v3d_user_name']) : '';
    $user_email = (!empty($_REQUEST['v3d_user_email'])) ? sanitize_email($_REQUEST['v3d_user_email']) : '';
    $user_phone = (!empty($_REQUEST['v3d_user_phone'])) ? sanitize_text_field($_REQUEST['v3d_user_phone']) : '';
    $user_comment = (!empty($_REQUEST['v3d_user_comment'])) ? sanitize_textarea_field($_REQUEST['v3d_user_comment']) : '';

    if ($action !== 'submit') {
        ob_start();
        include v3d_get_template('order_form.php');
        return ob_get_clean();
    } else {
        $order = array(
            'status' => 'pending',
            'user_name' => $user_name,
            'user_email' => $user_email,
            'user_phone' => $user_phone,
            'user_comment' => $user_comment,
            'items' => $items,
            'attachments' => $attachments,
        );

        if (get_option('v3d_require_billing_address')) {
            $user_address1 = (!empty($_REQUEST['v3d_user_address1'])) ? sanitize_text_field($_REQUEST['v3d_user_address1']) : '';
            $user_address2 = (!empty($_REQUEST['v3d_user_address2'])) ? sanitize_text_field($_REQUEST['v3d_user_address2']) : '';
            $user_city = (!empty($_REQUEST['v3d_user_city'])) ? sanitize_text_field($_REQUEST['v3d_user_city']) : '';
            $user_state = (!empty($_REQUEST['v3d_user_state'])) ? sanitize_text_field($_REQUEST['v3d_user_state']) : '';
            $user_country = (!empty($_REQUEST['v3d_user_country'])) ? sanitize_text_field($_REQUEST['v3d_user_country']) : '';
            $user_postcode = (!empty($_REQUEST['v3d_user_postcode'])) ? sanitize_text_field($_REQUEST['v3d_user_postcode']) : '';

            $order['user_address1'] = $user_address1;
            $order['user_address2'] = $user_address2;
            $order['user_city'] = $user_city;
            $order['user_state'] = $user_state;
            $order['user_country'] = $user_country;
            $order['user_postcode'] = $user_postcode;
        }

        if (get_option('v3d_require_shipping_address')) {
            $shipping_address1 = (!empty($_REQUEST['v3d_shipping_address1'])) ? sanitize_text_field($_REQUEST['v3d_shipping_address1']) : '';
            $shipping_address2 = (!empty($_REQUEST['v3d_shipping_address2'])) ? sanitize_text_field($_REQUEST['v3d_shipping_address2']) : '';
            $shipping_city = (!empty($_REQUEST['v3d_shipping_city'])) ? sanitize_text_field($_REQUEST['v3d_shipping_city']) : '';
            $shipping_state = (!empty($_REQUEST['v3d_shipping_state'])) ? sanitize_text_field($_REQUEST['v3d_shipping_state']) : '';
            $shipping_country = (!empty($_REQUEST['v3d_shipping_country'])) ? sanitize_text_field($_REQUEST['v3d_shipping_country']) : '';
            $shipping_postcode = (!empty($_REQUEST['v3d_shipping_postcode'])) ? sanitize_text_field($_REQUEST['v3d_shipping_postcode']) : '';

            $order['shipping_address1'] = $shipping_address1;
            $order['shipping_address2'] = $shipping_address2;
            $order['shipping_city'] = $shipping_city;
            $order['shipping_state'] = $shipping_state;
            $order['shipping_country'] = $shipping_country;
            $order['shipping_postcode'] = $shipping_postcode;
        }

        $use_payment = v3d_use_payment();

        // do not send emails now if the user needs to pay
        $result = v3d_save_order($order, !$use_payment);

        ob_start();

        if ($result && $use_payment) {
            v3d_display_payment($order, $result);
        } else if ($result) {
            include v3d_get_template('order_success.php');
        } else {
            include v3d_get_template('order_failed.php');
        }

        return ob_get_clean();
    }
}

function v3d_parse_data_url($data_url) {
    if (substr($data_url, 0, 5) == 'data:') {
        $data_url = str_replace(' ', '+', $data_url);
        $mime_start = strpos($data_url, ':') + 1;
        $mime_length = strpos($data_url, ';') - $mime_start;
        return array(
            'mime' => substr($data_url, $mime_start, $mime_length),
            'data' => base64_decode(substr($data_url, strpos($data_url, ',') + 1))
        );
    } else {
        return null;
    }
}

function v3d_save_order_attachments($data_urls) {

    global $ALLOWED_MIME_TYPES;

    $att_urls = array();

    foreach ($data_urls as $data_url) {
        $parsed = v3d_parse_data_url($data_url);
        if (empty($parsed))
            continue;

        $mime = $parsed['mime'];
        if (empty($ALLOWED_MIME_TYPES[$mime]))
            continue;

        $upload_dir = v3d_get_upload_dir();
        $att_dir = $upload_dir.'attachments/';
        if (!is_dir($att_dir)) {
            mkdir($att_dir, 0777, true);
        }

        $data = $parsed['data'];
        // strip possible harmful data
        if ($mime == 'text/plain')
            $data = esc_html($data);

        $ext = $ALLOWED_MIME_TYPES[$mime];
        $name = time().bin2hex(random_bytes(4)).'.'.$ext;
        $file = $att_dir.$name;
        $success = file_put_contents($file, $data);
        if ($success)
            $att_urls[] = v3d_get_upload_url().'attachments/'.basename($file);
    }

    return $att_urls;
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

// COMPAT: < Verge3D 4.1
function v3d_api_place_order_compat(WP_REST_Request $request) {

    $response = new WP_REST_Response(array(
        'status' => 'rejected',
        'error' => 'This API method was removed in Verge3D 4.1. Place order with "v2" method.'
    ), 200);

    if (get_option('v3d_cross_domain'))
        $response->header('Access-Control-Allow-Origin', '*');

    return $response;

}

function v3d_api_place_order(WP_REST_Request $request) {

    $params = $request->get_json_params();

    if (!empty($params)) {

        $params = v3d_sanitize_order($params);

        if (!empty($params['attachments']))
            $params['attachments'] = v3d_save_order_attachments($params['attachments']);

        $params['status'] = 'pending';

        if (v3d_save_order($params)) {
            $response = new WP_REST_Response(
                array(
                    'status' => 'ok',
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
            'callback' => 'v3d_api_place_order_compat',
            'permission_callback' => '__return_true',
        ));
        register_rest_route('verge3d/v2', '/place_order', array(
            'methods' => 'POST',
            'callback' => 'v3d_api_place_order',
            'permission_callback' => '__return_true',
        ));
    }
});


function v3d_ajax_fetch_order_items() {
    $order_items = $_POST['order_items'];
    if (empty($order_items))
        $order_items = array();
    else
        $order_items = json_decode(stripslashes($order_items), true);

    $order_items_table = new V3D_Order_Item_List_Table();
    $order_items_table->set_source_items($order_items);
    $order_items_table->ajax_response();
}
add_action('wp_ajax_v3d_ajax_fetch_order_items', 'v3d_ajax_fetch_order_items');


function v3d_ajax_fetch_product_info() {
    $products = v3d_get_products();
		ob_clean();
    wp_die(json_encode($products, JSON_UNESCAPED_UNICODE));
}
add_action('wp_ajax_v3d_ajax_fetch_product_info', 'v3d_ajax_fetch_product_info');


function v3d_ajax_send_pdf() {
    $response = array();

    $order_id = intval($_POST['order']);
    $order = v3d_get_order_by_id($order_id);
    $pdftype = esc_attr($_POST['pdftype']);

    v3d_send_emails($pdftype, $order, $order_id);

    $response['status'] = 'ok';

    wp_send_json($response);
}
add_action('wp_ajax_v3d_ajax_send_pdf', 'v3d_ajax_send_pdf');


function v3d_order_ajax_api() {
	  $screen = get_current_screen();

    if ($screen->id !== 'verge3d_page_verge3d_order')
        return;

    if (empty($_REQUEST['action']) or ($_REQUEST['action'] !== 'createform' and
            $_REQUEST['action'] !== 'editform'))
        return;

    $order_id = !empty($_REQUEST['order']) ? intval($_REQUEST['order']) : -1;

    wp_enqueue_script('v3d_admin', plugin_dir_url( __FILE__ ) . 'js/order.js');
    wp_localize_script('v3d_admin', 'ajax_object',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'order_id' => $order_id
        ));
}
add_action('admin_enqueue_scripts', 'v3d_order_ajax_api');
