<?php

const FILES_SUBDIR = 'files/';

function v3d_api_upload_file(WP_REST_Request $request) {

    if (!empty($request->get_body())) {

        $upload_dir = v3d_get_upload_dir().FILES_SUBDIR;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $id = time();

        $filename = $upload_dir.$id.'.json';
        $success = file_put_contents($filename, $request->get_body());

        if ($success)
            $response = new WP_REST_Response(array(
                'id' => $id,
                'link' => rest_url('verge3d/v1/get_file/'.$id),
                'size' => filesize($filename)
            ));
        else
            $response = new WP_REST_Response(array('error' => 'Unable to store file'), 500);

    } else {
        $response = new WP_REST_Response(array('error' => 'Bad request'), 400);
    }

    if (get_option('v3d_cross_domain'))
        $response->header('Access-Control-Allow-Origin', '*');

    return $response;

}

function v3d_api_get_file(WP_REST_Request $request) {

    $id = intval($request->get_param('id'));

    if (!empty($id)) {

        $upload_dir = v3d_get_upload_dir().FILES_SUBDIR;
        $file = $upload_dir.$id.'.json';

        if (is_file($file)) {
            // hack to prevent excessive JSON encoding
            header('Content-Type: application/json');
            if (get_option('v3d_cross_domain'))
                header('Access-Control-Allow-Origin: *');
            print_r(file_get_contents($file));
            exit();
        } else
            $response = new WP_REST_Response(array('error' => 'File not found'), 500);

    } else {

        $response = new WP_REST_Response(array('error' => 'Bad request'), 400);

    }

    if (get_option('v3d_cross_domain'))
        $response->header('Access-Control-Allow-Origin', '*');

    return $response;

}

add_action('rest_api_init', function () {
    if (get_option('v3d_file_api')) {

        register_rest_route('verge3d/v1', '/upload_file', array(
            'methods' => 'POST',
            'callback' => 'v3d_api_upload_file',
            'permission_callback' => '__return_true',
        ));

        register_rest_route('verge3d/v1', '/get_file/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => 'v3d_api_get_file',
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
            'permission_callback' => '__return_true',
        ));
    }

});
