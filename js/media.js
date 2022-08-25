jQuery(document).ready( function($) {
    jQuery('input#v3d_merchant_logo_select').click(function(e) {
        e.preventDefault();
        let image_frame;

        // define image_frame as wp.media object
        image_frame = wp.media({
            title: 'Select Media',
            multiple : false,
            library : {
                 type : 'image',
            }
        });

        image_frame.on('close',function() {
            // on close, get selections and save to the hidden input
            // plus other AJAX stuff to refresh the image preview
            const selection = image_frame.state().get('selection');
            const gallery_ids = new Array();
            let my_index = 0;
            selection.each(function(attachment) {
                gallery_ids[my_index] = attachment['id'];
                my_index++;
            });
            const ids = gallery_ids.join(",");
            jQuery('input#v3d_merchant_logo').val(ids);
            refresh_image(ids);
        });

        image_frame.on('open',function() {
            // on open, get the id from the hidden input
            // and select the appropiate images in the media manager
            const selection = image_frame.state().get('selection');
            const ids = jQuery('input#v3d_merchant_logo').val().split(',');
            ids.forEach(function(id) {
                const attachment = wp.media.attachment(id);
                attachment.fetch();
                selection.add( attachment ? [ attachment ] : [] );
            });

        });

        image_frame.open();
    });

    jQuery('input#v3d_merchant_logo_clear').click(function(e) {
        jQuery('input#v3d_merchant_logo').val('');
        jQuery('#v3d_preview_image').attr('src', '');
        jQuery('#v3d_preview_image').attr('width', 0);
        jQuery('#v3d_preview_image').attr('height', 0);
        jQuery('div#v3d_merchant_logo_buttons').css('display', 'inline');
    });
});

// ajax request to refresh the image preview
function refresh_image(the_id) {
    const data = {
        action: 'v3d_get_merchant_logo',
        id: the_id
    };

    jQuery.get(ajaxurl, data, function(response) {
        if (response.success === true) {
            jQuery('#v3d_preview_image').replaceWith(response.data.image);
            jQuery('div#v3d_merchant_logo_buttons').css('display', 'block');
        }
    });
}
