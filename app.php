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
                    <input type="checkbox" id="allow_fullscreen" name="allow_fullscreen" <?php checked($allow_fullscreen, 1) ?>>
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
                    <label for="appfiles">Upload folder</label>
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
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty(intval($_REQUEST['app']))) {
            $app_id = intval($_REQUEST['app']);

            $title = (!empty($_REQUEST['title'])) ? sanitize_text_field($_REQUEST['title']) : '';
            $canvas_width = (!empty($_REQUEST['canvas_width'])) ? intval($_REQUEST['canvas_width']) : '';
            $canvas_height = (!empty($_REQUEST['canvas_height'])) ? intval($_REQUEST['canvas_height']) : '';
            $allow_fullscreen = (!empty(sanitize_text_field($_REQUEST['allow_fullscreen']))) ? 1 : 0;

            if (!empty(sanitize_text_field($_REQUEST['title']))) {
                $post_arr = array(
                    'ID'           => $app_id,
                    'post_title'   => $title,
                    'meta_input'   => array(
                        'canvas_width' => $canvas_width,
                        'canvas_height' => $canvas_height,
                        'allow_fullscreen' => $allow_fullscreen,
                    ),
                );
                wp_update_post($post_arr);
            }

            v3d_redirect_app();
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
            'author'	   => '',
            'author_name'	   => '',
            'post_status'      => 'publish',
            'suppress_filters' => true,
            'fields'           => '',
        );
        $q_posts = get_posts($args);

        $posts = array();


        foreach ($q_posts as $q_post) {
            $url = v3d_get_app_url($q_post->ID);
            $posts[] = array(
                'ID'     => $q_post->ID,
                'title'  => $q_post->post_title,
                'shortcode'  => '[verge3d id="' . $q_post->ID . '"]',
                'url' => sprintf('<a href="%s">%s</a>', $url, basename($url)),
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

function v3d_gen_app_iframe_html($app_id) {
    $url = v3d_get_app_url($app_id);
    if (empty($url))
        return '';

    $canvas_width = get_post_meta($app_id, 'canvas_width', true);
    $canvas_height = get_post_meta($app_id, 'canvas_height', true);
    $allow_fullscreen = get_post_meta($app_id, 'allow_fullscreen', true);

    ob_start();
    ?>
    <iframe id="v3d_iframe" class="v3d-iframe" src="<?php echo esc_url($url) ?>"
        width="<?php echo esc_attr($canvas_width) ?>"
        height="<?php echo esc_attr($canvas_height) ?>"
        <?php echo !empty($allow_fullscreen) ? 'allowfullscreen' : '' ?>>
    </iframe>
    <?php
    return ob_get_clean();
}

function v3d_shortcode($atts = [], $content = null, $tag = '')
{
    $atts = array_change_key_case((array)$atts, CASE_LOWER);
    $v3d_atts = shortcode_atts(['id' => ''], $atts, $tag);

    $app_id = $v3d_atts['id'];

    return v3d_gen_app_iframe_html($app_id);
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
