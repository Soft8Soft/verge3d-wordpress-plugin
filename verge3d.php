<?php
/*
Plugin Name: Verge3D
Plugin URI: https://www.soft8soft.com/verge3d
Description: Verge3D is the most artist-friendly toolkit for creating interactive web-based experiences. It can be used to create product configurators, 3D presentations, online stores, e-learning apps, 3D portfolios, browser games and more.
Version: 4.3.0
Author: Soft8Soft LLC
Author URI: https://www.soft8soft.com
License: GPLv2 or later
*/

include plugin_dir_path(__FILE__) . 'app.php';
include plugin_dir_path(__FILE__) . 'file_storage.php';
include plugin_dir_path(__FILE__) . 'order.php';
include plugin_dir_path(__FILE__) . 'payment.php';
include plugin_dir_path(__FILE__) . 'product.php';
include plugin_dir_path(__FILE__) . 'woo_product.php';
include plugin_dir_path(__FILE__) . 'currencies.php';
include plugin_dir_path(__FILE__) . 'download_file.php';


function v3d_add_capability() {
    $role = get_role('administrator');
    $role->add_cap('manage_verge3d', true);

    $role = get_role('editor');
    $role->add_cap('manage_verge3d', true);
}
register_activation_hook(__FILE__, 'v3d_add_capability');

function v3d_remove_capability() {
    $role = get_role('administrator');
    $role->remove_cap('manage_verge3d', true);

    $role = get_role('editor');
    $role->remove_cap('manage_verge3d', true);
}
register_deactivation_hook(__FILE__, 'v3d_remove_capability');


function v3d_uninstall() {

    // remove all apps
    $app_posts = get_posts(array(
        'posts_per_page'   => -1,
        'post_type'        => 'v3d_app',
        'post_status'      => 'publish',
    ));
    foreach ($app_posts as $post)
        wp_delete_post($post->ID);

    // remove all orders
    $order_posts = get_posts(array(
        'posts_per_page'   => -1,
        'post_type'        => 'v3d_order',
        'post_status'      => 'publish',
    ));
    foreach ($order_posts as $post)
        wp_delete_post($post->ID);

    // cleanup upload dir
    v3d_rmdir(v3d_get_upload_dir());
}

register_uninstall_hook(__FILE__, 'v3d_uninstall');


function v3d_rmdir($src) {
    $dir = opendir($src);
    while (false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            $full = $src . '/' . $file;
            if (is_dir($full))
                v3d_rmdir($full);
            else
                unlink($full);
        }
    }
    closedir($dir);
    rmdir($src);
}

function v3d_init_custom_post_types()
{
    register_post_type('v3d_app',
           array(
               'labels'      => array(
                   'name'          => 'Applications',
                   'singular_name' => 'Application',
                   'add_new_item'  => 'Add New Application',
                   'edit_item'  => 'Edit Application',
                   'new_item'  => 'New Application',
                   'view_item'  => 'View Application',
               ),
               'public'      => false,
               'has_archive' => false,
               'show_ui' => false,
               'supports' => array('title'),
           )
    );

    register_post_type('v3d_order',
           array(
               'labels'      => array(
                   'name'          => 'Orders',
                   'singular_name' => 'Order',
                   'add_new_item'  => 'Add New Order',
                   'edit_item'  => 'Edit Order',
                   'new_item'  => 'New Order',
                   'view_item'  => 'View Order',
               ),
               'public'      => false,
               'has_archive' => false,
               'show_ui' => false,
               'supports' => array('title'),
           )
    );

    register_post_type('v3d_product',
           array(
               'labels'      => array(
                   'name'          => 'Products',
                   'singular_name' => 'Product',
                   'add_new_item'  => 'Add New Product',
                   'edit_item'  => 'Edit Product',
                   'new_item'  => 'New Product',
                   'view_item'  => 'View Product',
               ),
               'public'      => false,
               'has_archive' => false,
               'show_ui' => false,
               'supports' => array('title'),
           )
    );
}

add_action('init', 'v3d_init_custom_post_types');


function v3d_add_menus()
{
    add_menu_page(
        'Verge3D',
        'Verge3D',
        'manage_verge3d',
        'verge3d_app',
        'v3d_app_menu',
        plugin_dir_url(__FILE__) . 'images/logo.svg',
        20
    );

    add_submenu_page(
        'verge3d_app',
        'Verge3D Applications',
        'Applications',
        'manage_verge3d',
        'verge3d_app',
        'v3d_app_menu'
    );

    add_submenu_page(
        'verge3d_app',
        'Verge3D Orders',
        'Orders',
        'manage_verge3d',
        'verge3d_order',
        'v3d_order_menu'
    );

    add_submenu_page(
        'verge3d_app',
        'Verge3D Products',
        'Products',
        'manage_verge3d',
        'verge3d_product',
        'v3d_product_menu'
    );

    add_submenu_page(
        'verge3d_app',
        'Verge3D Plug-in Settings',
        'Settings',
        'manage_options',
        'verge3d_settings',
        'v3d_settings_menu'
    );
}

add_action('admin_menu', 'v3d_add_menus');

function v3d_settings_menu() {
    if (!current_user_can('manage_options'))
        return;

    add_filter('admin_footer_text', 'v3d_replace_footer');

    if (isset($_GET['settings-updated'])) {
        add_settings_error('verge3d_messages', 'verge3d_message', 'Settings Saved', 'updated');
    }

    settings_errors('verge3d_messages');

    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'verge3d_general';

    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>

        <h2 class="nav-tab-wrapper">
          <a href="?page=verge3d_settings&tab=verge3d_general" class="nav-tab <?php if ($active_tab == 'verge3d_general'){ echo 'nav-tab-active'; } ?> "><?php echo 'General'; ?></a>
          <a href="?page=verge3d_settings&tab=verge3d_mail" class="nav-tab <?php if ($active_tab == 'verge3d_mail'){ echo 'nav-tab-active'; } ?>"><?php echo 'Mail'; ?></a>
          <a href="?page=verge3d_settings&tab=verge3d_documents" class="nav-tab <?php if ($active_tab == 'verge3d_documents'){ echo 'nav-tab-active'; } ?>"><?php echo 'Documents'; ?></a>
          <a href="?page=verge3d_settings&tab=verge3d_payment" class="nav-tab <?php if ($active_tab == 'verge3d_payment'){ echo 'nav-tab-active'; } ?>"><?php echo 'Payment'; ?></a>
          <a href="?page=verge3d_settings&tab=verge3d_security" class="nav-tab <?php if ($active_tab == 'verge3d_security'){ echo 'nav-tab-active'; } ?>"><?php echo 'Security'; ?></a>
        </h2>

        <form action="options.php" method="post">
            <?php
            settings_fields($active_tab);
            do_settings_sections($active_tab);
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

function v3d_cleanup_options() {
    delete_option('v3d_currency');
    delete_option('v3d_price_decimals');
    delete_option('v3d_merchant_name');
    delete_option('v3d_merchant_address1');
    delete_option('v3d_merchant_address2');
    delete_option('v3d_merchant_city');
    delete_option('v3d_merchant_state');
    delete_option('v3d_merchant_country');
    delete_option('v3d_merchant_postcode');
    delete_option('v3d_merchant_phone');
    delete_option('v3d_merchant_logo');
    delete_option('v3d_merchant_logo_width');
    delete_option('v3d_require_billing_address');
    delete_option('v3d_require_shipping_address');
    delete_option('v3d_order_success_text');
    delete_option('v3d_order_failed_text');

    delete_option('v3d_order_email');
    delete_option('v3d_order_email_from_name');
    delete_option('v3d_order_email_from_email');

    delete_option('v3d_order_email_new_notify');
    delete_option('v3d_order_email_new_notify_user');
    delete_option('v3d_order_email_new_subject');
    delete_option('v3d_order_email_new_subject_user');
    delete_option('v3d_order_email_new_content');
    delete_option('v3d_order_email_new_content_user');
    delete_option('v3d_order_email_new_attach_custom');
    delete_option('v3d_order_email_new_attach_quote');
    delete_option('v3d_order_email_new_attach_invoice');

    delete_option('v3d_order_email_update_notify');
    delete_option('v3d_order_email_update_notify_user');
    delete_option('v3d_order_email_update_subject');
    delete_option('v3d_order_email_update_subject_user');
    delete_option('v3d_order_email_update_content');
    delete_option('v3d_order_email_update_content_user');
    delete_option('v3d_order_email_update_attach_custom');
    delete_option('v3d_order_email_update_attach_quote');
    delete_option('v3d_order_email_update_attach_invoice');

    delete_option('v3d_order_email_quote_subject_user');
    delete_option('v3d_order_email_quote_content_user');
    delete_option('v3d_order_email_invoice_subject_user');
    delete_option('v3d_order_email_invoice_content_user');

    delete_option('v3d_chrome_path');
    delete_option('v3d_quote_notes');
    delete_option('v3d_quote_valid');
    delete_option('v3d_invoice_notes');

    delete_option('v3d_payment_success_status');
    delete_option('v3d_payment_paypal');
    delete_option('v3d_payment_paypal_id');

    delete_option('v3d_order_api');
    delete_option('v3d_file_api');
    delete_option('v3d_product_api');
    delete_option('v3d_cross_domain');
    delete_option('v3d_custom_products');
}
register_deactivation_hook(__FILE__, 'v3d_cleanup_options');


function v3d_settings_init()
{
    add_option('v3d_currency', 'USD');
    add_option('v3d_price_decimals', 2);

    add_option('v3d_merchant_name', get_option('blogname'));
    add_option('v3d_merchant_address1', '');
    add_option('v3d_merchant_address2', '');
    add_option('v3d_merchant_city', '');
    add_option('v3d_merchant_state', '');
    add_option('v3d_merchant_country', '');
    add_option('v3d_merchant_postcode', '');
    add_option('v3d_merchant_phone', '');
    add_option('v3d_merchant_logo', '');
    add_option('v3d_merchant_logo_width', 100);

    add_option('v3d_require_billing_address', 0);
    add_option('v3d_require_shipping_address', 0);
    add_option('v3d_order_success_text', 'Thank you for your order! We\'re processing it now and will contact with you soon.');
    add_option('v3d_order_failed_text', 'Order failed.');

    add_option('v3d_order_email', get_option('admin_email'));
    add_option('v3d_order_email_from_name', get_option('blogname'));
    add_option('v3d_order_email_from_email', get_option('admin_email'));

    add_option('v3d_order_email_new_notify', 1);
    add_option('v3d_order_email_new_notify_user', 1);
    add_option('v3d_order_email_new_subject', 'Online order notification');
    add_option('v3d_order_email_new_subject_user', 'You just placed an order in our store');
    add_option('v3d_order_email_new_content', 'You\'ve received a new customer order from %c.');
    add_option('v3d_order_email_new_content_user', 'Thank you for your order! We\'re processing it now and will contact with you soon.');
    add_option('v3d_order_email_new_attach_custom', 1);
    add_option('v3d_order_email_new_attach_quote', 0);
    add_option('v3d_order_email_new_attach_invoice', 0);

    add_option('v3d_order_email_update_notify', 0);
    add_option('v3d_order_email_update_notify_user', 1);
    add_option('v3d_order_email_update_subject', 'Order updated');
    add_option('v3d_order_email_update_subject_user', 'Your order has been updated');
    add_option('v3d_order_email_update_content', 'Order #%n updated.');
    add_option('v3d_order_email_update_content_user', 'Your order #%n has been updated. Here is the latest details.');
    add_option('v3d_order_email_update_attach_custom', 1);
    add_option('v3d_order_email_update_attach_quote', 0);
    add_option('v3d_order_email_update_attach_invoice', 0);

    add_option('v3d_order_email_quote_subject_user', 'Your quote is ready');
    add_option('v3d_order_email_quote_content_user', 'Please check out the quote document attached.');
    add_option('v3d_order_email_invoice_subject_user', 'Your invoice is ready');
    add_option('v3d_order_email_invoice_content_user', 'Please check out the invoice document attached.');

    add_option('v3d_chrome_path', '');
    add_option('v3d_quote_notes', '');
    add_option('v3d_quote_valid', 30);
    add_option('v3d_invoice_notes', '');

    add_option('v3d_payment_success_status', 'processing');
    add_option('v3d_payment_paypal', 0);
    add_option('v3d_payment_paypal_id', '');

    add_option('v3d_order_api', 1);
    add_option('v3d_file_api', 1);
    add_option('v3d_product_api', 1);
    add_option('v3d_cross_domain', 1);
    add_option('v3d_custom_products', 1);

    register_setting('verge3d_general', 'v3d_currency');
    register_setting('verge3d_general', 'v3d_price_decimals');

    register_setting('verge3d_general', 'v3d_merchant_name');
    register_setting('verge3d_general', 'v3d_merchant_address1');
    register_setting('verge3d_general', 'v3d_merchant_address2');
    register_setting('verge3d_general', 'v3d_merchant_city');
    register_setting('verge3d_general', 'v3d_merchant_state');
    register_setting('verge3d_general', 'v3d_merchant_country');
    register_setting('verge3d_general', 'v3d_merchant_postcode');
    register_setting('verge3d_general', 'v3d_merchant_phone');
    register_setting('verge3d_general', 'v3d_merchant_logo');
    register_setting('verge3d_general', 'v3d_merchant_logo_width');

    register_setting('verge3d_general', 'v3d_require_billing_address');
    register_setting('verge3d_general', 'v3d_require_shipping_address');
    register_setting('verge3d_general', 'v3d_order_success_text');
    register_setting('verge3d_general', 'v3d_order_failed_text');


    add_settings_section(
        'v3d_ecommerce_settings',
        'E-Commerce common',
        '',
        'verge3d_general'
    );

    add_settings_field(
        'v3d_currency',
        'Currency',
        'v3d_currency_cb',
        'verge3d_general',
        'v3d_ecommerce_settings'
    );

    add_settings_field(
        'v3d_price_decimals',
        'Price decimals',
        'v3d_price_decimals_cb',
        'verge3d_general',
        'v3d_ecommerce_settings'
    );


    add_settings_section(
        'v3d_merchant_info',
        'Merchant info',
        '',
        'verge3d_general'
    );

    add_settings_field(
        'v3d_merchant_name',
        'Company / Brand',
        'v3d_merchant_name_cb',
        'verge3d_general',
        'v3d_merchant_info'
    );

    add_settings_field(
        'v3d_merchant_address1',
        'Address line 1',
        'v3d_merchant_address1_cb',
        'verge3d_general',
        'v3d_merchant_info'
    );

    add_settings_field(
        'v3d_merchant_address2',
        'Address line 2',
        'v3d_merchant_address2_cb',
        'verge3d_general',
        'v3d_merchant_info'
    );

    add_settings_field(
        'v3d_merchant_city',
        'City',
        'v3d_merchant_city_cb',
        'verge3d_general',
        'v3d_merchant_info'
    );

    add_settings_field(
        'v3d_merchant_state',
        'State / County',
        'v3d_merchant_state_cb',
        'verge3d_general',
        'v3d_merchant_info'
    );

    add_settings_field(
        'v3d_merchant_country',
        'Country',
        'v3d_merchant_country_cb',
        'verge3d_general',
        'v3d_merchant_info'
    );

    add_settings_field(
        'v3d_merchant_postcode',
        'Postcode',
        'v3d_merchant_postcode_cb',
        'verge3d_general',
        'v3d_merchant_info'
    );

    add_settings_field(
        'v3d_merchant_phone',
        'Phone',
        'v3d_merchant_phone_cb',
        'verge3d_general',
        'v3d_merchant_info'
    );

    add_settings_field(
        'v3d_merchant_logo',
        'Logo',
        'v3d_merchant_logo_cb',
        'verge3d_general',
        'v3d_merchant_info'
    );

    add_settings_field(
        'v3d_merchant_logo_width',
        'Logo width',
        'v3d_merchant_logo_width_cb',
        'verge3d_general',
        'v3d_merchant_info'
    );

    add_settings_section(
        'v3d_order_form_settings',
        'Order form',
        '',
        'verge3d_general'
    );

    add_settings_field(
        'v3d_require_address',
        'Order form fields',
        'v3d_require_address_cb',
        'verge3d_general',
        'v3d_order_form_settings'
    );

    add_settings_field(
        'v3d_order_success_text',
        'Order success text',
        'v3d_order_success_text_cb',
        'verge3d_general',
        'v3d_order_form_settings'
    );

    add_settings_field(
        'v3d_order_failed_text',
        'Order failed text',
        'v3d_order_failed_text_cb',
        'verge3d_general',
        'v3d_order_form_settings'
    );


    register_setting('verge3d_mail', 'v3d_order_email');
    register_setting('verge3d_mail', 'v3d_order_email_from_name');
    register_setting('verge3d_mail', 'v3d_order_email_from_email');

    register_setting('verge3d_mail', 'v3d_order_email_new_notify');
    register_setting('verge3d_mail', 'v3d_order_email_new_notify_user');
    register_setting('verge3d_mail', 'v3d_order_email_new_subject');
    register_setting('verge3d_mail', 'v3d_order_email_new_subject_user');
    register_setting('verge3d_mail', 'v3d_order_email_new_content');
    register_setting('verge3d_mail', 'v3d_order_email_new_content_user');
    register_setting('verge3d_mail', 'v3d_order_email_new_attach_custom');
    register_setting('verge3d_mail', 'v3d_order_email_new_attach_quote');
    register_setting('verge3d_mail', 'v3d_order_email_new_attach_invoice');

    register_setting('verge3d_mail', 'v3d_order_email_update_notify');
    register_setting('verge3d_mail', 'v3d_order_email_update_notify_user');
    register_setting('verge3d_mail', 'v3d_order_email_update_subject');
    register_setting('verge3d_mail', 'v3d_order_email_update_subject_user');
    register_setting('verge3d_mail', 'v3d_order_email_update_content');
    register_setting('verge3d_mail', 'v3d_order_email_update_content_user');
    register_setting('verge3d_mail', 'v3d_order_email_update_attach_custom');
    register_setting('verge3d_mail', 'v3d_order_email_update_attach_quote');
    register_setting('verge3d_mail', 'v3d_order_email_update_attach_invoice');

    register_setting('verge3d_mail', 'v3d_order_email_quote_subject_user');
    register_setting('verge3d_mail', 'v3d_order_email_quote_content_user');

    register_setting('verge3d_mail', 'v3d_order_email_invoice_subject_user');
    register_setting('verge3d_mail', 'v3d_order_email_invoice_content_user');

    add_settings_section(
        'v3d_mail_common_settings',
        'Common email settings',
        '',
        'verge3d_mail'
    );

    add_settings_field(
        'v3d_order_email',
        'Order notification email',
        'v3d_order_email_cb',
        'verge3d_mail',
        'v3d_mail_common_settings'
    );

    add_settings_field(
        'v3d_order_email_from',
        'Order emails "From"',
        'v3d_order_email_from_cb',
        'verge3d_mail',
        'v3d_mail_common_settings'
    );

    add_settings_section(
        'v3d_mail_new_order_settings',
        'New order notifications',
        '',
        'verge3d_mail'
    );

    add_settings_field(
        'v3d_order_email_notify',
        'Notify',
        'v3d_order_email_new_notify_cb',
        'verge3d_mail',
        'v3d_mail_new_order_settings'
    );

    add_settings_field(
        'v3d_order_email_new_subject',
        'Email subject',
        'v3d_order_email_new_subject_cb',
        'verge3d_mail',
        'v3d_mail_new_order_settings'
    );

    add_settings_field(
        'v3d_order_email_new_content',
        'Email content',
        'v3d_order_email_new_content_cb',
        'verge3d_mail',
        'v3d_mail_new_order_settings'
    );

    add_settings_field(
        'v3d_order_email_attach',
        'Attach',
        'v3d_order_email_new_attach_cb',
        'verge3d_mail',
        'v3d_mail_new_order_settings'
    );


    add_settings_section(
        'v3d_mail_update_order_settings',
        'Updated order notifications',
        '',
        'verge3d_mail'
    );

    add_settings_field(
        'v3d_order_email_update_notify',
        'Notify',
        'v3d_order_email_update_notify_cb',
        'verge3d_mail',
        'v3d_mail_update_order_settings'
    );

    add_settings_field(
        'v3d_order_email_update_subject',
        'Email subject',
        'v3d_order_email_update_subject_cb',
        'verge3d_mail',
        'v3d_mail_update_order_settings'
    );

    add_settings_field(
        'v3d_order_email_update_content',
        'Email content',
        'v3d_order_email_update_content_cb',
        'verge3d_mail',
        'v3d_mail_update_order_settings'
    );

    add_settings_field(
        'v3d_order_email_update_attach',
        'Attach',
        'v3d_order_email_update_attach_cb',
        'verge3d_mail',
        'v3d_mail_update_order_settings'
    );


    add_settings_section(
        'v3d_mail_quote_order_settings',
        'Sales quotes',
        '',
        'verge3d_mail'
    );

    add_settings_field(
        'v3d_order_email_quote_subject',
        'Email subject',
        'v3d_order_email_quote_subject_cb',
        'verge3d_mail',
        'v3d_mail_quote_order_settings'
    );

    add_settings_field(
        'v3d_order_email_quote_content',
        'Email content',
        'v3d_order_email_quote_content_cb',
        'verge3d_mail',
        'v3d_mail_quote_order_settings'
    );

    add_settings_section(
        'v3d_mail_invoice_order_settings',
        'Invoices',
        '',
        'verge3d_mail'
    );

    add_settings_field(
        'v3d_order_email_invoice_subject',
        'Email subject',
        'v3d_order_email_invoice_subject_cb',
        'verge3d_mail',
        'v3d_mail_invoice_order_settings'
    );

    add_settings_field(
        'v3d_order_email_invoice_content',
        'Email content',
        'v3d_order_email_invoice_content_cb',
        'verge3d_mail',
        'v3d_mail_invoice_order_settings'
    );


    register_setting('verge3d_documents', 'v3d_chrome_path');
    register_setting('verge3d_documents', 'v3d_quote_notes');
    register_setting('verge3d_documents', 'v3d_quote_valid');
    register_setting('verge3d_documents', 'v3d_invoice_notes');

    add_settings_section(
        'v3d_documents_common_settings',
        'Common',
        '',
        'verge3d_documents'
    );

    add_settings_field(
        'v3d_chrome_path',
        'PDF Generator',
        'v3d_chrome_path_cb',
        'verge3d_documents',
        'v3d_documents_common_settings'
    );

    add_settings_section(
        'v3d_documents_quote_settings',
        'Quotes',
        '',
        'verge3d_documents'
    );

    add_settings_field(
        'v3d_quote_notes',
        'Additional notes',
        'v3d_quote_notes_cb',
        'verge3d_documents',
        'v3d_documents_quote_settings'
    );

    add_settings_field(
        'v3d_quote_valid',
        'Valid',
        'v3d_quote_valid_cb',
        'verge3d_documents',
        'v3d_documents_quote_settings'
    );

    add_settings_section(
        'v3d_documents_invoice_settings',
        'Invoices',
        '',
        'verge3d_documents'
    );

    add_settings_field(
        'v3d_invoice_notes',
        'Additional notes',
        'v3d_invoice_notes_cb',
        'verge3d_documents',
        'v3d_documents_invoice_settings'
    );


    register_setting('verge3d_payment', 'v3d_payment_success_status');
    register_setting('verge3d_payment', 'v3d_payment_paypal');
    register_setting('verge3d_payment', 'v3d_payment_paypal_id');

    add_settings_section(
        'v3d_payment_settings',
        'Payment Systems',
        '',
        'verge3d_payment'
    );

    add_settings_field(
        'v3d_payment_success_status',
        'Paid status',
        'v3d_payment_success_status_cb',
        'verge3d_payment',
        'v3d_payment_settings'
    );

    add_settings_field(
        'v3d_payment_paypal',
        'PayPal',
        'v3d_payment_paypal_cb',
        'verge3d_payment',
        'v3d_payment_settings'
    );


    register_setting('verge3d_security', 'v3d_order_api');
    register_setting('verge3d_security', 'v3d_file_api');
    register_setting('verge3d_security', 'v3d_product_api');
    register_setting('verge3d_security', 'v3d_cross_domain');
    register_setting('verge3d_security', 'v3d_custom_products');

    add_settings_section(
        'v3d_security_settings',
        'Security',
        '',
        'verge3d_security'
    );

    add_settings_field(
        'v3d_rest_api',
        'Enable REST APIs',
        'v3d_rest_api_cb',
        'verge3d_security',
        'v3d_security_settings'
    );

    add_settings_field(
        'v3d_cross_domain',
        'Cross-domain requests',
        'v3d_cross_domain_cb',
        'verge3d_security',
        'v3d_security_settings'
    );

    add_settings_field(
        'v3d_custom_products',
        'Custom products',
        'v3d_custom_products_cb',
        'verge3d_security',
        'v3d_security_settings'
    );

}
add_action('admin_init', 'v3d_settings_init');


/* General settings UI */

function v3d_ecommerce_settings_cb() {
    echo 'Order notification settings:';
}

function v3d_price_decimals_cb() {
    // get the value of the setting we've registered with register_setting()
    $decimals = get_option('v3d_price_decimals');
    ?>
    <input type="number" name="v3d_price_decimals" min=0 max=100 value="<?php echo isset($decimals) ? esc_attr($decimals) : ''; ?>">
    <p class="description">Number of decimal digits used to display prices.</p>
    <?php
}

function v3d_currency_cb() {
    // get the value of the setting we've registered with register_setting()
    $currency = get_option('v3d_currency');

    ?>
    <select id="v3d_currency" name="v3d_currency">
    <?php

    global $v3d_currencies;

    foreach ($v3d_currencies as $c) {
        ?>
        <option value="<?php echo $c['code']; ?>" <?php echo ($currency == $c['code']) ? 'selected' : ''; ?>><?php echo $c['name'].' ('.$c['symbol'].')'; ?></option>
        <?php
    }

    ?>
    </select>
    <p class="description">Currency used to store/display prices.</p>
    <?php
}

function v3d_merchant_name_cb() {
    $name = get_option('v3d_merchant_name');
    ?>
    <label>
      <input type="text" name="v3d_merchant_name" value="<?php echo isset($name) ? esc_attr($name) : ''; ?>" class="v3d-wide-input">
    </label>
    <?php
}

function v3d_merchant_address1_cb() {
    $address1 = get_option('v3d_merchant_address1');
    ?>
    <label>
      <input type="text" name="v3d_merchant_address1" value="<?php echo isset($address1) ? esc_attr($address1) : ''; ?>" class="v3d-wide-input">
    </label>
    <?php
}

function v3d_merchant_address2_cb() {
    $address2 = get_option('v3d_merchant_address2');
    ?>
    <label>
      <input type="text" name="v3d_merchant_address2" value="<?php echo isset($address2) ? esc_attr($address2) : ''; ?>" class="v3d-wide-input">
    </label>
    <?php
}

function v3d_merchant_city_cb() {
    $city = get_option('v3d_merchant_city');
    ?>
    <label>
      <input type="text" name="v3d_merchant_city" value="<?php echo isset($city) ? esc_attr($city) : ''; ?>" class="v3d-wide-input">
    </label>
    <?php
}

function v3d_merchant_state_cb() {
    $state = get_option('v3d_merchant_state');
    ?>
    <label>
      <input type="text" name="v3d_merchant_state" value="<?php echo isset($state) ? esc_attr($state) : ''; ?>" class="v3d-wide-input">
    </label>
    <?php
}

function v3d_merchant_country_cb() {
    $country = get_option('v3d_merchant_country');
    ?>
    <label>
      <input type="text" name="v3d_merchant_country" value="<?php echo isset($country) ? esc_attr($country) : ''; ?>" class="v3d-wide-input">
    </label>
    <?php
}

function v3d_merchant_postcode_cb() {
    $postcode = get_option('v3d_merchant_postcode');
    ?>
    <label>
      <input type="text" name="v3d_merchant_postcode" value="<?php echo isset($postcode) ? esc_attr($postcode) : ''; ?>" class="v3d-wide-input">
    </label>
    <?php
}

function v3d_merchant_phone_cb() {
    $phone = get_option('v3d_merchant_phone');
    ?>
    <label>
      <input type="text" name="v3d_merchant_phone" value="<?php echo isset($phone) ? esc_attr($phone) : ''; ?>" class="v3d-wide-input">
    </label>
    <?php
}

function v3d_merchant_logo_cb() {
    $image_id = get_option('v3d_merchant_logo');
    $image_src = wp_get_attachment_image_src($image_id, 'full');
    $has_image = is_array($image_src);

    ?>
    <div id='image_preview_wrapper'>
      <?php if ($has_image): ?>
        <img id='image_preview_image' src='<?= $image_src[0]; ?>' style='max-width: 200px;'>
      <?php endif; ?>
    </div>
    <input id="upload_image_button" type="button" class="button <?= $has_image ? 'hidden' : ''; ?>" value="Select image" />
    <input id="clear_image_button" type="button" class="button <?= $has_image ? '' : 'hidden'; ?>" value="Clear image" />
    <input type='hidden' name='v3d_merchant_logo' id='image_attachment_id' value='<?= esc_attr($image_id); ?>'>
    <?php
}

function v3d_merchant_logo_width_cb() {
    $width = get_option('v3d_merchant_logo_width');

    ?>
    <input type="number" name="v3d_merchant_logo_width" min=0 max=10000 value="<?php echo isset($width) ? esc_attr($width) : ''; ?>">
    <p class="description">Logo width in pixels.</p>
    <?php
}

function v3d_require_address_cb() {
    ?>
    <fieldset>
    <label>
      <input type="checkbox" name="v3d_require_billing_address" value="1" <?php checked(1, get_option('v3d_require_billing_address')); ?>>
      Billing address
    </label>
    <br>
    <label>
      <input type="checkbox" name="v3d_require_shipping_address" value="1" <?php checked(1, get_option('v3d_require_shipping_address')); ?>>
      Shipping address
    </label>
    </fieldset>
    <?php
}

function v3d_order_success_text_cb() {
    $content = get_option('v3d_order_success_text');
    ?>
    <textarea name="v3d_order_success_text" class="v3d-wide-textarea"><?php echo isset($content) ? esc_attr($content) : ''; ?></textarea>
    <p class="description">Text to display when order successfully placed.</p>
    <?php
}

function v3d_order_failed_text_cb() {
    $content = get_option('v3d_order_failed_text');
    ?>
    <textarea name="v3d_order_failed_text" class="v3d-wide-textarea"><?php echo isset($content) ? esc_attr($content) : ''; ?></textarea>
    <p class="description">Text to display when order failed.</p>
    <?php
}

/* Mail settings UI */

function v3d_order_email_cb() {
    $email = get_option('v3d_order_email');
    ?>
    <input type="email" name="v3d_order_email" value="<?php echo isset($email) ? esc_attr($email) : ''; ?>" class="v3d-wide-input">
    <p class="description">You will be notified about new orders on this e-mail. For example sales@yourcompany.com.</p>
    <?php
}

function v3d_order_email_from_cb() {
    // get the value of the setting we've registered with register_setting()
    $name = get_option('v3d_order_email_from_name');
    $email = get_option('v3d_order_email_from_email');

    ?>
    <input type="text" name="v3d_order_email_from_name" value="<?php echo isset($name) ? esc_attr($name) : ''; ?>" class="v3d-wide-input">
    <p class="description">From whom customers will be receiving e-mail confirmations. For example YourCompany.</p>
    <input type="email" name="v3d_order_email_from_email" value="<?php echo isset($email) ? esc_attr($email) : ''; ?>" class="v3d-wide-input">
    <p class="description">From what e-mail customers will be receiving confirmations. For example sales@yourcompany.com</p>
    <?php
}

function v3d_order_email_new_notify_cb() {
    ?>
    <fieldset>
    <label>
      <input type="checkbox" name="v3d_order_email_new_notify" value="1" <?php checked(1, get_option('v3d_order_email_new_notify')); ?>>
      Merchant
    </label>
    <br>
    <label>
      <input type="checkbox" name="v3d_order_email_new_notify_user" value="1" <?php checked(1, get_option('v3d_order_email_new_notify_user')); ?>>
      Customer
    </label>
    </fieldset>
    <?php
}

function v3d_order_email_new_subject_cb() {
    $subject = get_option('v3d_order_email_new_subject');
    $subject_user = get_option('v3d_order_email_new_subject_user');
    ?>
    <label>
      Merchant:
      <input type="text" name="v3d_order_email_new_subject" value="<?php echo isset($subject) ? esc_attr($subject) : ''; ?>" class="v3d-wide-input">
      <p class="description">Subject of new order notifications sent to you.</p>
    </label>
    <br>
    <label>
      Customer:
      <input type="text" name="v3d_order_email_new_subject_user" value="<?php echo isset($subject_user) ? esc_attr($subject_user) : ''; ?>" class="v3d-wide-input">
      <p class="description">Subject of new order notifications sent to your customers.</p>
    </label>
    <?php
}

function v3d_order_email_new_content_cb() {
    $content = get_option('v3d_order_email_new_content');
    $content_user = get_option('v3d_order_email_new_content_user');
    ?>
    <label>
      Merchant:
      <textarea id="v3d_order_email_new_content" name="v3d_order_email_new_content" class="v3d-wide-textarea"><?php echo isset($content) ? esc_attr($content) : ''; ?></textarea>
      <p class="description">Content of new order notifications sent to you. Use %c for customer name, and %n for order number.</p>
    </label>
    <br>
    <label>
      Customer:
      <textarea id="v3d_order_email_new_content_user" name="v3d_order_email_new_content_user" class="v3d-wide-textarea"><?php echo isset($content_user) ? esc_attr($content_user) : ''; ?></textarea>
      <p class="description">Content of new order notifications sent to your customers. Use %c for customer name, and %n for order number.</p>
    </label>
    <?php
}

function v3d_order_email_new_attach_cb() {
    ?>
    <fieldset class="v3d-one-line-checkers">
      <label>
        <input type="checkbox" name="v3d_order_email_new_attach_custom" value="1" <?php checked(1, get_option('v3d_order_email_new_attach_custom')); ?>>
        Custom (user-generated)
      </label>
      <label>
        <input type="checkbox" name="v3d_order_email_new_attach_quote" value="1" <?php checked(1, get_option('v3d_order_email_new_attach_quote')); ?>>
        Quote PDF
      </label>
      <label>
        <input type="checkbox" name="v3d_order_email_new_attach_invoice" value="1" <?php checked(1, get_option('v3d_order_email_new_attach_invoice')); ?>>
        Invoice PDF
      </label>
      <p class="description">Custom attachments are provided by users. PDF documents are generated on the server.</p>
    </fieldset>
    <?php
}

function v3d_order_email_update_notify_cb() {
    ?>
    <fieldset>
    <label>
      <input type="checkbox" name="v3d_order_email_update_notify" value="1" <?php checked(1, get_option('v3d_order_email_update_notify')); ?>>
      Merchant
    </label>
    <br>
    <label>
      <input type="checkbox" name="v3d_order_email_update_notify_user" value="1" <?php checked(1, get_option('v3d_order_email_update_notify_user')); ?>>
      Customer
    </label>
    </fieldset>
    <?php
}

function v3d_order_email_update_subject_cb() {
    $subject = get_option('v3d_order_email_update_subject');
    $subject_user = get_option('v3d_order_email_update_subject_user');
    ?>
    <label>
      Merchant:
      <input type="text" name="v3d_order_email_update_subject" value="<?php echo isset($subject) ? esc_attr($subject) : ''; ?>" class="v3d-wide-input">
      <p class="description">Subject of order update notifications sent to you.</p>
    </label>
    <br>
    <label>
      Customer:
      <input type="text" name="v3d_order_email_update_subject_user" value="<?php echo isset($subject_user) ? esc_attr($subject_user) : ''; ?>" class="v3d-wide-input">
      <p class="description">Subject of order update notifications sent to your customers.</p>
    </label>
    <?php
}

function v3d_order_email_update_content_cb() {
    $content = get_option('v3d_order_email_update_content');
    $content_user = get_option('v3d_order_email_update_content_user');
    ?>
    <label>
      Merchant:
      <textarea id="v3d_order_email_update_content" name="v3d_order_email_update_content" class="v3d-wide-textarea"><?php echo isset($content) ? esc_attr($content) : ''; ?></textarea>
      <p class="description">Content of order update notifications sent to you. Use $c for customer name, and $n for order number.</p>
    </label>
    <br>
    <label>
      Customer:
      <textarea id="v3d_order_email_update_content_user" name="v3d_order_email_update_content_user" class="v3d-wide-textarea"><?php echo isset($content_user) ? esc_attr($content_user) : ''; ?></textarea>
      <p class="description">Content of order update notifications sent to your customers. Use %c for customer name, and %n for order number.</p>
    </label>
    <?php
}

function v3d_order_email_update_attach_cb() {
    ?>
    <fieldset class="v3d-one-line-checkers">
      <label>
        <input type="checkbox" name="v3d_order_email_update_attach_custom" value="1" <?php checked(1, get_option('v3d_order_email_update_attach_custom')); ?>>
        Custom (user-generated)
      </label>
      <label>
        <input type="checkbox" name="v3d_order_email_update_attach_quote" value="1" <?php checked(1, get_option('v3d_order_email_update_attach_quote')); ?>>
        Quote PDF
      </label>
      <label>
        <input type="checkbox" name="v3d_order_email_update_attach_invoice" value="1" <?php checked(1, get_option('v3d_order_email_update_attach_invoice')); ?>>
        Invoice PDF
      </label>
      <p class="description">Custom attachments are provided by users. PDF documents are generated on the server.</p>
    </fieldset>
    <?php
}

function v3d_order_email_quote_subject_cb() {
    $subject = get_option('v3d_order_email_quote_subject_user');
    ?>
    <input type="text" name="v3d_order_email_quote_subject_user" value="<?php echo isset($subject) ? esc_attr($subject) : ''; ?>" class="v3d-wide-input">
    <p class="description">Subject of sales quote emails sent to your customers.</p>
    <?php
}

function v3d_order_email_quote_content_cb() {
    $content = get_option('v3d_order_email_quote_content_user');
    ?>
    <textarea id="v3d_order_email_quote_content_user" name="v3d_order_email_quote_content_user" class="v3d-wide-textarea"><?php echo isset($content) ? esc_attr($content) : ''; ?></textarea>
    <p class="description">Content of sales quote emails sent to your customers. Use %c for customer name, and %n for order number.</p>
    <?php
}

function v3d_order_email_invoice_subject_cb() {
    $subject = get_option('v3d_order_email_invoice_subject_user');
    ?>
    <input type="text" name="v3d_order_email_invoice_subject_user" value="<?php echo isset($subject) ? esc_attr($subject) : ''; ?>" class="v3d-wide-input">
    <p class="description">Subject of invoice emails sent to your customers.</p>
    <?php
}

function v3d_order_email_invoice_content_cb() {
    $content = get_option('v3d_order_email_invoice_content_user');
    ?>
    <textarea id="v3d_order_email_invoice_content_user" name="v3d_order_email_invoice_content_user" class="v3d-wide-textarea"><?php echo isset($content) ? esc_attr($content) : ''; ?></textarea>
    <p class="description">Content of sales invoice emails sent to your customers. Use %c for customer name, and %n for order number.</p>
    <?php
}

/* Documents settings UI */

function v3d_chrome_path_cb() {
    $chrome_path = get_option('v3d_chrome_path');

    ?>
    <input type="text" id="v3d_chrome_path" name="v3d_chrome_path" value="<?php echo isset($chrome_path) ? esc_attr($chrome_path) : ''; ?>" class="v3d-wide-input">
    <p class="description">Path to the Chrome/Chromium browser to perform PDF conversion. Leave blank if you installed it system-wide.</p>
    <?php
}

function v3d_quote_notes_cb() {
    $notes = get_option('v3d_quote_notes');

    ?>
    <textarea id="v3d_quote_notes" name="v3d_quote_notes" class="v3d-wide-textarea"><?php echo isset($notes) ? esc_attr($notes) : ''; ?></textarea>
    <?php
}

function v3d_quote_valid_cb() {
    $valid = get_option('v3d_quote_valid');
    ?>
    <input type="number" name="v3d_quote_valid" min=0 max=1000 value="<?php echo isset($valid) ? esc_attr($valid) : ''; ?>">
    <p class="description">Number of days the quote is considered valid.</p>
    <?php
}

function v3d_invoice_notes_cb() {
    $notes = get_option('v3d_invoice_notes');

    ?>
    <textarea id="v3d_invoice_notes" name="v3d_invoice_notes" class="v3d-wide-textarea"><?php echo isset($notes) ? esc_attr($notes) : ''; ?></textarea>
    <?php
}


/* Payment settings UI */

function v3d_payment_success_status_cb() {
    // get the value of the setting we've registered with register_setting()
    $status = get_option('v3d_payment_success_status');

    ?>
    <select id="v3d_payment_success_status" name="v3d_payment_success_status">
      <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
      <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>Processing</option>
      <option value="shipped" <?= $status === 'shipped' ? 'selected' : '' ?>>Shipped</option>
      <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
    </select>
    <p class="description">Order status to set for successfull payments.</p>
    <?php
}

function v3d_payment_paypal_cb() {
    global $PAYPAL_SUPPORTED_CURRENCIES;

    $client_id = get_option('v3d_payment_paypal_id');

    ?>
    <fieldset>
      <label>
        <input type="checkbox" id="v3d_payment_paypal" name="v3d_payment_paypal" value="1" <?php checked(1, get_option('v3d_payment_paypal')); ?>>
        Enable
      <p class="description">Enable PayPal payments.</p>
      </label>
      <br>
      <input type="text" id="v3d_payment_paypal_id" name="v3d_payment_paypal_id" value="<?php echo isset($client_id) ? esc_attr($client_id) : ''; ?>" class="v3d-wide-input">
      <p class="description">PayPal business account app client ID. Find this in the <a href="https://developer.paypal.com/developer/applications/" target="_blank">PayPal Developer Dashboard</a></p>
    </fieldset>

    <?php if (!in_array(get_option('v3d_currency'), $PAYPAL_SUPPORTED_CURRENCIES)): ?>
      <p class="error"><?= v3d_currency_name(); ?> is unsupported currency. Please select other currency to use PayPal payments.</p>
    <?php endif; ?>

    <script type="text/javascript">
        const paypalCheckbox = document.getElementById('v3d_payment_paypal');

        function showHidePayPalID() {
            document.getElementById('v3d_payment_paypal_id').disabled =
                !paypalCheckbox.checked;
        }

        paypalCheckbox.onchange = showHidePayPalID;
        showHidePayPalID();
    </script>
    <?php
}

/* Security settings UI */

function v3d_rest_api_cb() {
    ?>
    <fieldset>
    <label>
      <input type="checkbox" name="v3d_order_api" value="1" <?php checked(1, get_option('v3d_order_api')); ?>>
      Order management
      <p class="description">Allow REST API for placing orders.</p>
    </label>
    <br>
    <label>
      <input type="checkbox" name="v3d_file_api" value="1" <?php checked(1, get_option('v3d_file_api')); ?>>
      File storage
      <p class="description">Allow REST API for file uploads.</p>
    </label>
    <br>
    <label>
      <input type="checkbox" name="v3d_product_api" value="1" <?php checked(1, get_option('v3d_product_api')); ?>>
      Product management
      <p class="description">Allow REST API for products, such as <em>get_product_info</em>.</p>
    </label>
    </fieldset>
    <?php
}

function v3d_cross_domain_cb() {
    ?>
    <label>
      <input type="checkbox" name="v3d_cross_domain" value="1" <?php checked(1, get_option('v3d_cross_domain')); ?>>
      Allow
    </label>
    <p class="description">Allow receiving orders from different domains.</p>
    <?php
}

function v3d_custom_products_cb() {
    ?>
    <label>
      <input type="checkbox" name="v3d_custom_products" value="1" <?php checked(1, get_option('v3d_custom_products')); ?>>
      Allow
    </label>
    <p class="description">Allow receiving orders with custom product prices. If disabled, allow only products presented on the <strong>Products</strong> page.</p>
    <?php
}


function v3d_init_custom_styles() {
    wp_enqueue_style('v3d_main', plugin_dir_url( __FILE__ ) . 'css/main.css');
}
add_action('wp_enqueue_scripts', 'v3d_init_custom_styles');

function v3d_init_custom_styles_admin() {
    wp_enqueue_style('v3d_admin', plugin_dir_url( __FILE__ ) . 'css/admin.css');
}
add_action('admin_enqueue_scripts', 'v3d_init_custom_styles_admin');

function v3d_init_custom_scripts_admin($page) {
    wp_enqueue_script('v3d_admin', plugin_dir_url( __FILE__ ) . 'js/admin.js');
}
add_action('admin_enqueue_scripts', 'v3d_init_custom_scripts_admin');

function load_wp_media_files( $page ) {
    // change to the $page where you want to enqueue the script
    if( $page == 'verge3d_page_verge3d_settings' ) {
        // Enqueue WordPress media scripts
        wp_enqueue_media();
        // Enqueue custom script that will interact with wp.media
        wp_enqueue_script('v3d_media_script', plugin_dir_url( __FILE__ ) . 'js/media.js');
    }
}
add_action('admin_enqueue_scripts', 'load_wp_media_files');


function v3d_inline_custom_styles() {
    echo file_get_contents(plugin_dir_url( __FILE__ ) . 'css/main.css');
}

function v3d_inline_image($path) {
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    return $base64;
}

/**
 * Get/create plugin's upload directory
 */
function v3d_get_upload_dir() {
    $upload_dir = wp_upload_dir()['basedir'].'/verge3d/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    return $upload_dir;
}

function v3d_get_upload_url() {
    $upload_url = wp_upload_dir()['baseurl'].'/verge3d/';
    return $upload_url;
}

function v3d_get_template($name) {

    $v3d_theme_dir = get_stylesheet_directory().'/verge3d/';

    if (is_file($v3d_theme_dir.$name))
        return $v3d_theme_dir.$name;
    else
        return plugin_dir_path(__FILE__).'templates/'.$name;
}

function v3d_replace_footer() {
    echo 'Thank you for using Verge3D! Please refer to this <a href="https://www.soft8soft.com/docs/manual/en/introduction/Wordpress-Plugin.html" target="_blank">page</a> to find out how to use this plugin.';
}

function v3d_redirect_same() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
        || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
    header('Location: '.$protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    exit;
}

