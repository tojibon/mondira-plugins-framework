jQuery(document).ready( function($) {
    //alert(jQuery);
	// Uploading files
var file_frame;
var wp_media_post_id = 1; // Store the old id
var set_to_post_id = 10; // Set this
var target = "";
 
  jQuery('.upload_image_button_latest').live('click', function( event ){
    if(wp.media=="undefined")
    return;  
    target = $(this).attr('data-target');  
    
      
    event.preventDefault();
 
    // If the media frame already exists, reopen it.
    if ( file_frame ) {
      // Set the post ID to what we want
      file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
      // Open frame
      file_frame.open();
      return;
    } else {
      // Set the wp.media post id so the uploader grabs the ID we want when initialised
              
      wp.media.model.settings.post.id = set_to_post_id;
    }
 
    // Create the media frame. 
    file_frame = wp.media.frames.file_frame = wp.media({
      title: jQuery( this ).data( 'uploader_title' ),
      button: {
        text: jQuery( this ).data( 'uploader_button_text' ),
      },
      multiple: false  // Set to true to allow multiple files to be selected
    });
 
    // When an image is selected, run a callback.
    file_frame.on( 'select', function() {
      // We set multiple to false so only get one image from the uploader
      attachment = file_frame.state().get('selection').first().toJSON();
 
      // Do something with attachment.id and/or attachment.url here
      mondiraAdmin.themeGetNDisplayImage(attachment.id,target); 
      
      
      // Restore the main post ID
      wp.media.model.settings.post.id = wp_media_post_id;
    });
 
    // Finally, open the modal
    file_frame.open();
  });
  
  // Restore the main ID when the add media button is pressed
  jQuery('a.add_media').on('click', function() {
    wp.media.model.settings.post.id = wp_media_post_id;
  });
})



