<?php
/*
Plugin Name: Verge3D
Plugin URI: https://www.soft8soft.com/verge3d
Description: Verge3D is the most artist-friendly toolkit for creating interactive web-based experiences. It can be used to create product configurators, 3D presentations, online stores, e-learning apps, 3D portfolios, browser games and more.
Version: 3.6.0
Author: Soft8Soft LLC
Author URI: https://www.soft8soft.com
License: GPLv2 or later
*/

include plugin_dir_path(__FILE__) . 'app.php';
include plugin_dir_path(__FILE__) . 'file_storage.php';
include plugin_dir_path(__FILE__) . 'order.php';
include plugin_dir_path(__FILE__) . 'woo_product.php';


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
        'E-Commerce',
        'manage_verge3d',
        'verge3d_order',
        'v3d_order_menu'
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

    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('verge3d');
            do_settings_sections('verge3d');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

function v3d_settings_init()
{
    add_option('v3d_order_email', '');
    register_setting('verge3d', 'v3d_order_email');

    add_option('v3d_order_email_from_name', '');
    register_setting('verge3d', 'v3d_order_email_from_name');

    add_option('v3d_order_email_from_email', '');
    register_setting('verge3d', 'v3d_order_email_from_email');

    add_option('v3d_order_email_subject', 'Order notification');
    register_setting('verge3d', 'v3d_order_email_subject');

    add_option('v3d_order_email_attach_pdf', 0);
    register_setting('verge3d', 'v3d_order_email_attach_pdf');

    add_option('v3d_chrome_path', '');
    register_setting('verge3d', 'v3d_chrome_path');

    add_option('v3d_order_api', 1);
    register_setting('verge3d', 'v3d_order_api');

    add_option('v3d_file_api', 1);
    register_setting('verge3d', 'v3d_file_api');

    add_option('v3d_cross_domain', 1);
    register_setting('verge3d', 'v3d_cross_domain');


    add_settings_section(
        'v3d_ecommerce_settings',
        'E-Commerce',
        '',//'v3d_ecommerce_settings_cb',
        'verge3d'
    );

    add_settings_field(
        'v3d_order_email',
        'Order notification e-mail',
        'v3d_order_email_cb',
        'verge3d',
        'v3d_ecommerce_settings'
    );

    add_settings_field(
        'v3d_order_email_from',
        'Order e-mail "From"',
        'v3d_order_email_from_cb',
        'verge3d',
        'v3d_ecommerce_settings'
    );

    add_settings_field(
        'v3d_order_email_subject',
        'Order e-mail subject',
        'v3d_order_email_subject_cb',
        'verge3d',
        'v3d_ecommerce_settings'
    );

    add_settings_field(
        'v3d_order_email_pdf',
        'Order e-mail PDF attachment',
        'v3d_order_email_pdf_cb',
        'verge3d',
        'v3d_ecommerce_settings'
    );


    add_settings_section(
        'v3d_security_settings',
        'Security',
        '',
        'verge3d'
    );

    add_settings_field(
        'v3d_rest_api',
        'Enable REST APIs',
        'v3d_rest_api_cb',
        'verge3d',
        'v3d_security_settings'
    );

    add_settings_field(
        'v3d_cross_domain',
        'Cross-domain requests',
        'v3d_cross_domain_cb',
        'verge3d',
        'v3d_security_settings'
    );


}
add_action('admin_init', 'v3d_settings_init');

/* E-commerce settings UI */

function v3d_ecommerce_settings_cb() {
    echo 'Order notification settings:';
}

function v3d_order_email_cb() {
    // get the value of the setting we've registered with register_setting()
    $email = get_option('v3d_order_email');
    ?>
    <input type="email" name="v3d_order_email" value="<?php echo isset($email) ? esc_attr($email) : ''; ?>">
    <p class="description">You will be notified about new orders on this e-mail.</p>
    <?php
}

function v3d_order_email_from_cb() {
    // get the value of the setting we've registered with register_setting()
    $name = get_option('v3d_order_email_from_name');
    $email = get_option('v3d_order_email_from_email');

    ?>
    <input type="text" name="v3d_order_email_from_name" value="<?php echo isset($name) ? esc_attr($name) : ''; ?>">
    <p class="description">From whom customers will be receiving e-mail confirmations. For example John Smith.</p>
    <input type="email" name="v3d_order_email_from_email" value="<?php echo isset($email) ? esc_attr($email) : ''; ?>">
    <p class="description">From what e-mail customers will be receiving confirmations. For example john.smith@yourcompany.com</p>
    <?php
}

function v3d_order_email_subject_cb() {
    // get the value of the setting we've registered with register_setting()
    $subject = get_option('v3d_order_email_subject');
    ?>
    <input type="text" name="v3d_order_email_subject" value="<?php echo isset($subject) ? esc_attr($subject) : ''; ?>">
    <p class="description">Subject of confirmation e-mails sent to your customers.</p>
    <?php
}

function v3d_order_email_pdf_cb() {
    // get the value of the setting we've registered with register_setting()
    $chrome_path = get_option('v3d_chrome_path');

    ?>
    <fieldset>
    <label>
      <input type="checkbox" id="v3d_order_email_attach_pdf" name="v3d_order_email_attach_pdf" value="1" <?php checked(1, get_option('v3d_order_email_attach_pdf')); ?>">
      Attach
    <p class="description">Please install Chrome/Chromium browser on the server in order to use this feature.</p>
    </label>
    <br>
    <input type="text" id="v3d_chrome_path" name="v3d_chrome_path" value="<?php echo isset($chrome_path) ? esc_attr($chrome_path) : ''; ?>">
    <p class="description">Path to the Chrome/Chromium browser to perform PDF conversion. Leave blank if you installed it system-wide.</p>
    </fieldset>

    <script type="text/javascript">
        var attachPdfCheckbox = document.getElementById("v3d_order_email_attach_pdf");

        function showHideChromePath() {
            document.getElementById("v3d_chrome_path").disabled =
                !attachPdfCheckbox.checked;
        }

        attachPdfCheckbox.onchange = showHideChromePath;
        showHideChromePath();

    </script>
    <?php
}

/* Security settings UI */

function v3d_rest_api_cb() {
    ?>
    <fieldset>
    <label>
      <input type="checkbox" name="v3d_order_api" value="1" <?php checked(1, get_option('v3d_order_api')); ?>">
      Order management
    </label>
    <br>
    <label>
      <input type="checkbox" name="v3d_file_api" value="1" <?php checked(1, get_option('v3d_file_api')); ?>">
      File storage
    </label>
    </fieldset>
    <?php
}

function v3d_cross_domain_cb() {
    ?>
    <label>
      <input type="checkbox" name="v3d_cross_domain" value="1" <?php checked(1, get_option('v3d_cross_domain')); ?>">
      Allow
    </label>
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

function v3d_init_custom_scripts_admin() {
    wp_enqueue_script('v3d_admin', plugin_dir_url( __FILE__ ) . 'js/admin.js');
}
add_action('admin_enqueue_scripts', 'v3d_init_custom_scripts_admin');

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

