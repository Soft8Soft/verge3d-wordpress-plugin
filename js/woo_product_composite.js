(function ($, window, document, undefined) {
    $('.composite_data').on('wc-composite-initializing', function(event, composite) {
        composite.actions.add_action('component_selection_changed', function(c) {
            if (window.v3d_on_product_update)
                window.v3d_on_product_update();
        }, 100);

        composite.actions.add_action('component_quantity_changed', function(c) {
            if (window.v3d_on_product_update)
                window.v3d_on_product_update();
        }, 100);

        window.v3d_woo_composite = composite;

    });
})(jQuery, window, document);
