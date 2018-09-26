<?php
/*
Plugin Name: Verge3D
Plugin URI: https://www.soft8soft.com/verge3d
Description: Verge3D is the most artist-friendly toolkit for creating interactive web-based experiences. It can be used to create product configurators, 3D presentations, online stores, e-learning apps, 3D portfolios, browser games and more.
Version: 2.7.1
Author: Soft8Soft LLC
Author URI: https://www.soft8soft.com
License: GPLv2 or later
*/

include plugin_dir_path(__FILE__) . 'app.php';
include plugin_dir_path(__FILE__) . 'order.php';


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
               'public'      => true,
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
               'public'      => true,
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
    register_setting('verge3d', 'v3d_order_email');
    register_setting('verge3d', 'v3d_order_email_from_name');
    register_setting('verge3d', 'v3d_order_email_from_email');

    add_settings_section(
        'v3d_ecommerce_settings',
        '',//'E-Commerce',
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
}
add_action('admin_init', 'v3d_settings_init');
 
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


function v3d_init_custom_styles() {
    wp_enqueue_style('v3d_main', plugin_dir_url( __FILE__ ) . 'css/main.css');
}
add_action('wp_enqueue_scripts', 'v3d_init_custom_styles');

function v3d_init_custom_styles_admin() {
    wp_enqueue_style('v3d_admin', plugin_dir_url( __FILE__ ) . 'css/admin.css');
}
add_action('admin_enqueue_scripts', 'v3d_init_custom_styles_admin');

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
    return plugin_dir_path(__FILE__).'templates/'.$name;
}

