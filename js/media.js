jQuery(document).ready(function($) {
    let image_frame;
    const imgContainer = $('#image_preview_wrapper');
    const addImgLink = $('#upload_image_button');
    const delImgLink = $('#clear_image_button');
    const imgIdInput = $('#image_attachment_id');

    addImgLink.on('click', function(event) {

        event.preventDefault();

        // If the media image_frame already exists, reopen it.
        if (image_frame) {
            image_frame.open();
            return;
        }

        // Create a new media frame
        image_frame = wp.media({
            title: 'Select or Upload Media Of Your Chosen Persuasion',
            button: {
                text: 'Use this media'
            },
            multiple: false  // Set to true to allow multiple files to be selected
        });

        // When an image is selected in the media frame...
        image_frame.on('select', function() {
            // Get media attachment details from the frame state
            const attachment = image_frame.state().get('selection').first().toJSON();

            // Send the attachment URL to our custom image input field.
            imgContainer.html('');
            imgContainer.append('<img src="'+attachment.url+'" style="max-width:200px;"/>');

            // Send the attachment id to our hidden input
            imgIdInput.val(attachment.id);

            // Hide the add image link
            addImgLink.addClass('hidden');

            // Unhide the remove image link
            delImgLink.removeClass('hidden');
        });

        // Finally, open the modal on click
        image_frame.open();

    });

    delImgLink.on('click', function(event) {

        event.preventDefault();

        // Clear out the preview image
        imgContainer.html('');

        // Un-hide the add image link
        addImgLink.removeClass('hidden');

        // Hide the delete image link
        delImgLink.addClass('hidden');

        // Delete the image id from the hidden input
        imgIdInput.val('');

    });

});
