<?php

define('V3D_DEFAULT_CANVAS_WIDTH', 800);
define('V3D_DEFAULT_CANVAS_HEIGHT', 500);

if (!class_exists('WP_List_Table')){
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

function v3d_app_menu() {

    if (!current_user_can('manage_verge3d')) {
        echo 'Access denied';
        return;
    }

    add_filter('admin_footer_text', 'v3d_replace_footer');

    $action = (!empty($_REQUEST['action'])) ? sanitize_text_field($_REQUEST['action']) : '';

    switch ($action) {
    case 'create':
        ?>

        <div class="wrap">
          <h1 class="wp-heading-inline">New Verge3D application</h1>
          <form method="get" class="validate">
            <input type="hidden" name="page" value="<?php echo sanitize_text_field($_REQUEST['page']) ?>" />
            <input type="hidden" name="action" value="createapp" />
            <table class="form-table">
              <tbody>
                <tr class="form-field form-required">
              	<th scope="row"><label for="title">Title <span class="description">(required)</span></label></th>
                  <td>
                    <input name="title" type="text" id="title" value="" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="200">
                  </td>
                </tr>
              </tbody>
            </table>
            <p class="submit"><input type="submit" class="button button-primary" value="Next"></p>
          </form>
        </div>

        <?php
        break;
    case 'createapp':
        $post_arr = array(
            'post_title'   => (!empty($_REQUEST['title'])) ?
                    sanitize_text_field($_REQUEST['title']) : 'My App',
            'post_status'  => 'publish',
            'post_type'    => 'v3d_app',
            'meta_input'   => array(
                'canvas_width' => V3D_DEFAULT_CANVAS_WIDTH,
                'canvas_height' => V3D_DEFAULT_CANVAS_HEIGHT,
                'allow_fullscreen' => 1,
                'xr_spatial_tracking' => 1,
                'loading' => 'auto',
            ),
        );
        $app_id = wp_insert_post($post_arr);
        v3d_redirect_app($app_id);
        break;
    case 'edit':

        $app_id = intval($_REQUEST['app']);

        if (empty($app_id)) {
            echo 'Bad request';
            return;
        }

        $title = get_the_title($app_id);

        $canvas_width = get_post_meta($app_id, 'canvas_width', true);
        $canvas_height = get_post_meta($app_id, 'canvas_height', true);
        $allow_fullscreen = get_post_meta($app_id, 'allow_fullscreen', true);
        $xr_spatial_tracking = get_post_meta($app_id, 'xr_spatial_tracking', true);
        $loading = get_post_meta($app_id, 'loading', true);
        $cover_att_id = get_post_meta($app_id, 'cover_attachment_id', true);

        $cover_src = wp_get_attachment_image_src($cover_att_id, 'full');
        $has_cover = is_array($cover_src);

        $upload_stats = v3d_get_upload_stats($app_id);

        wp_enqueue_media();
        wp_enqueue_script('v3d_media_script', plugin_dir_url( __FILE__ ) . 'js/media.js');

        ?>
        <div class="wrap">
          <h1 class="wp-heading-inline">Manage Verge3D Application</h1>
          <h2>Settings</h2>

          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="page" value="<?php echo sanitize_text_field($_REQUEST['page']) ?>" />
            <input type="hidden" name="action" value="editapp" />
            <input type="hidden" name="app" value="<?php echo $app_id ?>" />
            <table class="form-table">
              <tbody>
                <tr class="form-field">
                  <th scope="row">
                    <label for="title">Title</label>
                  </th>
                  <td>
                    <input name="title" type="text" id="title" value="<?php echo $title ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="200">
                  </td>
                </tr>
                <tr class="form-field">
                  <th scope="row">
                    <label for="canvas_width">iframe width</label>
                  </th>
                  <td>
                    <input type="number" id="canvas_width" name="canvas_width" value="<?php echo $canvas_width ?>" required>
                  </td>
                </tr>
                <tr class="form-field">
                  <th scope="row">
                    <label for="canvas_height">iframe height</label>
                  </th>
                  <td>
                    <input type="number" id="canvas_height" name="canvas_height" value="<?php echo $canvas_height ?>" required>
                  </td>
                </tr>
                <tr class="form-field">
                  <th scope="row">
                    <label for="allow_fullscreen">Allow fullscreen mode</label>
                  </th>
                  <td>
                    <input type="checkbox" id="allow_fullscreen" name="allow_fullscreen" value="1" <?php checked($allow_fullscreen, 1) ?>>
                  </td>
                </tr>
                <tr class="form-field">
                  <th scope="row">
                    <label for="xr_spatial_tracking">Allow AR/VR</label>
                  </th>
                  <td>
                    <input type="checkbox" id="xr_spatial_tracking" name="xr_spatial_tracking" value="1" <?php checked($xr_spatial_tracking, 1) ?>>
                  </td>
                </tr>
                <tr class="form-field">
                  <th scope="row">
                    <label for="loading">Loading</label>
                  </th>
                  <td>
                    <select id="loading" name="loading">
                      <option value="auto" <?= $loading == 'auto' ? 'selected' : '' ?>>Auto</option>
                      <option value="lazy" <?= $loading == 'lazy' ? 'selected' : '' ?>>Lazy</option>
                      <option value="eager" <?= $loading == 'eager' ? 'selected' : '' ?>>Eager</option>
                    </select>
                  </td>
                </tr>
                <tr class="form-field">
                  <th scope="row">
                    <label for="app_image">App Image</label>
                  </th>
                  <td>
                    <div id='image_preview_wrapper'>
                      <?php if ($has_cover): ?>
                        <img id='image_preview_image' src='<?= $cover_src[0]; ?>' style='max-width: 200px;'>
                      <?php endif; ?>
                    </div>
                    <input id="upload_image_button" type="button" class="button <?= $has_cover ? 'hidden' : ''; ?>" value="Select image" />
                    <input id="clear_image_button" type="button" class="button <?= $has_cover ? '' : 'hidden'; ?>" value="Clear image" />
                    <input type='hidden' name='cover_attachment_id' id='image_attachment_id' value='<?= esc_attr($cover_att_id); ?>'>
                  </td>
                </tr>
              </tbody>
            </table>
            <p class="submit"><input type="submit" class="button button-primary" value="Save"></p>
          </form>

          <h2>Files</h2>
          <form method="post" enctype="multipart/form-data" onsubmit="v3d_handle_uploads('<?php echo $app_id ?>'); return false;">
            <table class="form-table">
              <tbody>
                <tr class="form-field">
                  <th scope="row">
                    <label for="appfiles">Status</label>
                  </th>
                  <td>
                    <?php if ($upload_stats): ?>
                      Stored <?= $upload_stats[0]; ?> files with total size <?= v3d_format_size($upload_stats[1]); ?>.
                    <?php else: ?>
                      No files uploaded yet.
                    <?php endif; ?>
                  </td>
                </tr>
                <tr class="form-field">
                  <th scope="row">
                    <label for="appfiles">Upload app folder</label>
                  </th>
                  <td>
                    <input type="file" name="appfiles[]" id="appfiles" multiple="" directory="" webkitdirectory="" mozdirectory="">
                    <input type="submit" class="button button-primary" value="Upload">
                    <span id="upload_progress" class="v3d-upload-progress"></span>
                    <span id="upload_status" class="v3d-upload-status"></span>
                  </td>
                </tr>
              </tbody>
            </table>
          </form>
        </div>
        <?php
        break;
    case 'editapp':
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty(intval($_POST['app']))) {
            $app_id = intval($_POST['app']);

            $title = (!empty($_POST['title'])) ? sanitize_text_field($_POST['title']) : '';
            $canvas_width = (!empty($_POST['canvas_width'])) ? intval($_POST['canvas_width']) : '';
            $canvas_height = (!empty($_POST['canvas_height'])) ? intval($_POST['canvas_height']) : '';
            $allow_fullscreen = !empty($_POST['allow_fullscreen']) ? 1 : 0;
            $xr_spatial_tracking = !empty($_POST['xr_spatial_tracking']) ? 1 : 0;
            $loading = !empty($_POST['loading']) ? $_POST['loading'] : 'auto';
            $cover_att_id = !empty($_POST['cover_attachment_id']) ? absint($_POST['cover_attachment_id']) : 0;

            if (!empty(sanitize_text_field($_POST['title']))) {
                $post_arr = array(
                    'ID'           => $app_id,
                    'post_title'   => $title,
                    'meta_input'   => array(
                        'canvas_width' => $canvas_width,
                        'canvas_height' => $canvas_height,
                        'allow_fullscreen' => $allow_fullscreen,
                        'xr_spatial_tracking' => $xr_spatial_tracking,
                        'loading' => $loading,
                        'cover_attachment_id' => $cover_att_id,
                    ),
                );
                wp_update_post($post_arr);
            }

            v3d_redirect_app($app_id);
        } else {
            echo 'Bad request';
            return;
        }

        break;
    case 'delete':
        if (!empty($_REQUEST['app'])) {
            $app = $_REQUEST['app'];

            // process bulk request
            if (is_array($app)) {
                foreach ($app as $a)
                    if (!empty(intval($a)))
                        v3d_delete_app(intval($a));
            } else {
                if (!empty(intval($app)))
                    v3d_delete_app(intval($app));
            }

            v3d_redirect_app();
        } else {
            echo 'Bad request';
            return;
        }

        break;
    default:
        $appTable = new V3D_App_List_Table();
        $appTable->prepare_items();

        ?>
        <div class="wrap">
          <div id="icon-users" class="icon32"><br/></div>
          <h1 class='wp-heading-inline'>Verge3D Applications</h1>
          <a href="?page=verge3d_app&action=create" class="page-title-action">Add New</a>

          <div class="v3d-hint">
            <p>Use <code>[verge3d id=""]</code> shortcode to embed Verge3D applications in your pages/posts.</p>
          </div>

          <form id="apps-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo sanitize_text_field($_REQUEST['page']) ?>" />

            <style type="text/css">
              .manage-column.column-title { width: 30%; }
              .manage-column.column-shortcode { width: 20%; }
              .manage-column.column-url { width: 30%; }
              .manage-column.column-date { width: 20%; }
            </style>
            <?php $appTable->display() ?>
          </form>
        </div>
        <?php
        break;
    }
}

function v3d_foldersize($path) {
    $num_files = 0;
    $total_size = 0;

    $files = scandir($path);

    foreach ($files as $t) {
        if (is_dir(rtrim($path, '/') . '/' . $t)) {
            if ($t <> '.' && $t <> '..') {
                list($num, $size) = v3d_foldersize(rtrim($path, '/') . '/' . $t);
                $num_files += $num;
                $total_size += $size;
            }
        } else {
            $num_files +=1;
            $total_size += filesize(rtrim($path, '/') . '/' . $t);
        }
    }
    return [$num_files, $total_size];
}

function v3d_get_upload_stats($app_id) {
    $upload_dir = v3d_get_upload_dir();
    $upload_app_dir = $upload_dir.$app_id;

    if (is_dir($upload_app_dir)) {
        return v3d_foldersize($upload_app_dir);
    } else
        return null;
}

function v3d_format_size($size) {
    $mod = 1024;
    $units = explode(' ', 'B KB MB GB TB PB');
    for ($i = 0; $size > $mod; $i++) {
        $size /= $mod;
    }
    return round($size, 2) . ' ' . $units[$i];
}


function v3d_delete_app($app_id) {
    wp_delete_post($app_id);

    $upload_dir = v3d_get_upload_dir();
    $upload_app_dir = $upload_dir.$app_id;

    if (is_dir($upload_app_dir))
        v3d_rmdir($upload_app_dir);
}


class V3D_App_List_Table extends WP_List_Table {

    function __construct(){
        global $status, $page;

        // Set parent defaults
        parent::__construct( array(
            'singular'  => 'app',
            'plural'    => 'apps',
            'ajax'      => false
        ) );

    }

    function column_default($item, $column_name) {
        switch( $column_name){
        case 'shortcode':
        case 'url':
        case 'date':
            return $item[$column_name];
        default:
            return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    function column_title($item) {

        // Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&app=%s">Edit</a>',
                    sanitize_text_field($_REQUEST['page']), 'edit', $item['ID']),
            'delete'    => sprintf('<a href="?page=%s&action=%s&app=%s">Delete</a>',
                    sanitize_text_field($_REQUEST['page']), 'delete', $item['ID']),
        );

        // Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item['title'],
            /*$2%s*/ $item['ID'],
            /*$3%s*/ $this->row_actions($actions)
        );
    }

    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['ID']                // The value of the checkbox should be the record's id
        );
    }

    function get_columns(){
        $columns = array(
            'cb'      => '<input type="checkbox" />', //Render a checkbox instead of text
            'title'   => 'Title',
            'shortcode' => 'Shortcode',
            'url'      => 'URL',
            'date'    => 'Date',
        );
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'title'     => array('title',false),     //true means it's already sorted
            'date'    => array('date',false),
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
        // Detect when a bulk action is being triggered...
        if ('delete' === $this->current_action()) {
            wp_die('Items deleted (or they would be if we had items to delete)!');
        }
    }

    function prepare_items() {
        $per_page = 5;

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
            'post_type'        => 'v3d_app',
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
            $url = v3d_get_app_url($q_post->ID);
            $id = $q_post->ID;
            $posts[] = array(
                'ID'     => $id,
                'title'  => $q_post->post_title,
                'shortcode'  => '[verge3d id="' . $q_post->ID . '"]',
                'url' => sprintf('<a href="%s">%s</a>', $url, basename($url)),
                'date' => get_the_time(get_option('date_format').' '.get_option('time_format'), $id),
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

function v3d_iframe_allow_html($name, $value) {
    if (!empty($value))
        return $name.';';
    else
        return $name.' \'none\';';
}

function v3d_gen_app_iframe_html($app_id, $wrap_to_figure=false) {
    $url = v3d_get_app_url($app_id);
    if (empty($url))
        return '';

    $canvas_width = get_post_meta($app_id, 'canvas_width', true);
    $canvas_height = get_post_meta($app_id, 'canvas_height', true);
    $allow_fullscreen = get_post_meta($app_id, 'allow_fullscreen', true);
    $xr_spatial_tracking = get_post_meta($app_id, 'xr_spatial_tracking', true);
    $loading = get_post_meta($app_id, 'loading', true);

    ob_start();
    ?>
    <?= $wrap_to_figure ? '<figure>' : ''; ?>
      <iframe id="v3d_iframe" class="v3d-iframe" src="<?php echo esc_url($url) ?>"
        width="<?php echo esc_attr($canvas_width) ?>"
        height="<?php echo esc_attr($canvas_height) ?>"
        allow="<?= v3d_iframe_allow_html('fullscreen', $allow_fullscreen) ?> <?= v3d_iframe_allow_html('xr-spatial-tracking', $xr_spatial_tracking) ?>"
        loading="<?= !empty($loading) ? esc_attr($loading) : 'auto' ?>">
      </iframe>
    <?= $wrap_to_figure ? '</figure>' : ''; ?>
    <?php
    return ob_get_clean();
}

function v3d_shortcode($atts = [], $content = null, $tag = '')
{
    $atts = array_change_key_case((array)$atts, CASE_LOWER);
    $v3d_atts = shortcode_atts(['id' => ''], $atts, $tag);

    $app_id = $v3d_atts['id'];

    return v3d_gen_app_iframe_html($app_id, true);
}

function v3d_shortcodes_init() {
    add_shortcode('verge3d', 'v3d_shortcode');
}
add_action('init', 'v3d_shortcodes_init');


function v3d_redirect_app($app_id=-1) {

    $params = '?page=verge3d_app';

    if ($app_id > -1) {
        $params .= ('&action=edit&app='.$app_id);
    }

    ?>
    <script type="text/javascript">
          document.location.href="<?php echo $params ?>";
    </script>
    <?php
}

function v3d_get_app_url($app_id) {

    $app_dir = v3d_get_app_dir($app_id);
    if (empty($app_dir))
        return '';

    $htmls = array();

    foreach (glob($app_dir.'/*.html') as $file) {
        $htmls[] = basename($file);
    }

    if (empty($htmls))
        return '';

    $html_idx = array_search('index.html', $htmls);

    if ($html_idx === false)
       $html = $htmls[0];
    else
       $html = $htmls[$html_idx];

    $url = v3d_get_upload_url().$app_id.'/'.$html;

    return $url;

}

function v3d_get_app_dir($app_id) {
    $upload_dir = v3d_get_upload_dir();
    $upload_app_dir = $upload_dir.$app_id;

    if (is_dir($upload_dir) && is_dir($upload_app_dir))
        return $upload_app_dir;
    else
        return '';
}

add_action('wp_ajax_v3d_upload_app_file', 'v3d_upload_app_file');

function v3d_upload_app_file() {

    $count = 0;

    if (!empty($_REQUEST['app'])) {
        $app_id = $_REQUEST['app'];
    } else {
        wp_die('error');
    }

    $upload_dir = v3d_get_upload_dir();
    $upload_app_dir = $upload_dir.$app_id;

    //if (is_dir($upload_app_dir))
    //    v3d_rmdir($upload_app_dir);

    if (!is_dir($upload_app_dir))
        mkdir($upload_app_dir, 0777, true);

    if (!empty($_FILES['appfile'])) {
        if (strlen($_FILES['appfile']['name']) > 1 && $_FILES['appfile']['error'] == UPLOAD_ERR_OK) {
            $fullpath = strip_tags(sanitize_text_field($_REQUEST['apppath']));

            // strip first directory name
            $fullpath = explode("/", $fullpath);
            array_shift($fullpath);
            $fullpath = implode("/", $fullpath);

            $path = dirname($fullpath);

            if (!is_dir($upload_app_dir.'/'.$path)) {
                mkdir($upload_app_dir.'/'.$path);
            }

            if (move_uploaded_file($_FILES['appfile']['tmp_name'], $upload_app_dir.'/'.$fullpath)) {
                wp_die('ok');
            }
        }
    }

    wp_die('error');
}
