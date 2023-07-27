<?php
class V3D_Elementor_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'v3d_elementor_widget';
    }

    public function get_title() {
        return 'Verge3D';
    }

    public function get_icon() {
        return 'eicon-image-rollover';
    }

    public function get_categories() {
        return ['general'];
    }

    public function get_keywords() {
        return ['verge3d', 'webgl', '3dweb', 'web3d', 'ecommerce', 'elearning'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_title',
            [
                'label' => 'Application',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        global $post;

        $app_posts = get_posts(array(
            'posts_per_page' => -1,
            'post_type'      => 'v3d_app',
            'post_status'    => 'publish',
        ));

        $app_options[''] = __('None (select a value)', 'verge3d');

        foreach ($app_posts as $app_post)
            $app_options[$app_post->ID] = $app_post->post_title;

        $this->add_control(
            'v3d_app_id',
            array(
                'label'   => esc_html__('Application', 'verge3d'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => '',
                'options' => $app_options,
            )
        );

        $this->end_controls_section();
    }

    protected function render() {
        $app_posts = get_posts(array(
            'posts_per_page' => -1,
            'post_type'      => 'v3d_app',
            'post_status'    => 'publish',
        ));

        if (!empty($app_posts)) {
            $settings = $this->get_settings_for_display();
            $app_id = $settings['v3d_app_id'];
            if (!empty($app_id) && !empty(get_post($app_id))) {
                echo v3d_gen_app_iframe_html($app_id);
            }
        }
    }
}
