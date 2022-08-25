<?php

function v3d_handle_downloads() {
    if (!isset($_REQUEST['v3d_download_file']) || empty($_REQUEST['order']))
        return;

    $order_id = intval($_REQUEST['order']);

    $downloads = v3d_get_order_downloads($order_id);
    if (!empty($downloads)) {
        $file_id = esc_attr($_REQUEST['v3d_download_file']);
        $file = $downloads[$file_id];

        if (!empty($file)) {
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename="'.basename($file['link']).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            //header('Content-Type: ' . mime_content_type($file['link']));
            //header('Content-Length: ' . filesize($file['link']));

            readfile($file['link']);
        }
    }
}
add_action('init', 'v3d_handle_downloads');
