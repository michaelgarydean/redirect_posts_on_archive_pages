<?php
/**
 * Plugin Name:     Redirect Posts on Archive Pages
 * Plugin URI:      https://github.com/mykedean/redirect_posts_on_archive_pages
 * Description:     Redirect posts on an archive page with a custom URL.
 * Author:          Michael Gary Dean <contact@michaeldean.ca>
 * Author URI:      https://github.com/mykedean
 * Text Domain:     redirect_posts_on_archive_pages
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Redirect_posts_on_archive_pages
 */

/**
 * Filter the permalink for posts that appear in an archive for a specific term.
 * 
 * @return A URL for a Bandcamp page associated with the post.
 */

function mgd_redirect_posts_url_permalink( $permalink, $post ) {

	/*
     * Get the URL used for redirecting the post.
     */

    $redirect_url = get_post_meta( $post->ID, 'mgd_redirect_url', true );
    $category_for_redirections = get_post_meta( $post->ID, 'mgd_category_for_redirect_url', true );

    // If there isn't a URL set by the user, return the default permalink.
    if ( empty( $redirect_url ) || empty( $category_for_redirections ) ) {
    	return $permalink;
    }

    return "https://michaelgarydean.bandcamp.com/";

	/*
	 * Check if we are on the archive page for the right category
	 */
	if ( is_category( $category_for_redirections ) ) {
        $redirect_permalink = "https://michaelgarydean.bandcamp.com/";
	}

    // Return the value of the URL to replace the permalink with
    return $redirect_permalink;
}

add_filter( 'post_link', 'mgd_redirect_posts_url_permalink', 10, 2 );

/**
 * Add a meta box in the sidebar so users can add a category and URL.
 * 
 * @return void
 */
function mgd_redirect_posts_url_register_meta_boxes() {


    add_meta_box(
	'redirect', 
     __( 'Redirect Post with URL', 'mgd' ), 
    'mgd_redirect_posts_url_render_metabox',
    'post',
    'side' );
}

add_action( 'add_meta_boxes', 'mgd_redirect_posts_url_register_meta_boxes' );

/**
 * Render HTML for the redirect metabox.
 * 
 * Callback function for add_meta_box(). Echos the HTML directly. Does not return anything.
 *
 * @TODO - Be able to check as many categories to be affect as you want.
 * 
 * @return void
 */
function mgd_redirect_posts_url_render_metabox ( $object ) {
    
    /**
     * Store the meta keys for each field that is created so they can be saved later
     * @see hushlamb_save_post_class_meta()
     */
    
    global $mgd_meta_keys;
    $mgd_meta_keys[] = 'mgd_redirect_url';

    wp_nonce_field( basename( __FILE__ ), 'mgd_post_class_nonce' );
    
    /**
     * The category used to select which archive page it affects.
     */
    ?>
        <p>
            <label for="mgd-category-for-redirect-url"><?php _e( "Category", 'mgd' ); ?></label>
            <br />
            <input class="widefat" type="text" name="mgd-category-for-redirect-url" id="mgd-category-for-redirect-url" value="<?php echo esc_attr( get_post_meta( $object->ID, 'mgd_category_for_redirect_url', true ) ); ?>" size="30" />
        </p>
    <?php


    /**
     * The URL to replace the post's permalink with
    */
    ?>
        <p>
            <label for="mgd-redirect-url"><?php _e( "Redirection URL", 'mgd' ); ?></label>
            <br />
            <input class="widefat" type="text" name="mgd-redirect-url" id="mgd-redirect-url" value="<?php echo esc_attr( get_post_meta( $object->ID, 'mgd_redirect_url', true ) ); ?>" size="30" />
        </p>
    <?php
}

/**
 * Save metabox data.
 */

function mgd_redirect_posts_url_save_post_class_meta( $post_id, $post ) {
    
    //Save all the metadata by interating through the saved meta keys.
    $mgd_meta_keys = array();
    
    $mgd_meta_keys[] = 'mgd_redirect_url';
    
    /* Verify the nonce before proceeding. */
    if ( !isset( $_POST['mgd_post_class_nonce'] ) || !wp_verify_nonce( $_POST['mgd_post_class_nonce'], basename( __FILE__ ) ) ) {
        return $post_id;
    }
    /* Get the post type object. */
    $post_type = get_post_type_object( $post->post_type );
    
    /* Check if the current user has permission to edit the post. */
    if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ) {
        return $post_id;
    }
    
    //Go through each of the meta keys registered to metaboxes and update them
    foreach( $mgd_meta_keys as $meta_key ) {
        $class_name = str_replace('_', '-', $meta_key);
        
        /* Get the posted data and sanitize it for use as an HTML class. */
        /**
         * @TODO Sanitize data, but allow hyperlinks to be stored in database
         */
        //$new_meta_value = ( isset( $_POST[ $class_name ] ) ? sanitize_html_class( $_POST[ $class_name ] ) : '' );
        $new_meta_value = $_POST[ $class_name ];
        
        /* Get the meta value of the custom field key. */
        $meta_value = get_post_meta( $post_id, $meta_key, true );
        
        /* If a new meta value was added and there was no previous value, add it. */
        if ( $new_meta_value && '' == $meta_value ) {
            
            add_post_meta( $post_id, $meta_key, $new_meta_value, true );
        
        /* If the new meta value does not match the old value, update it. */
        } elseif ( $new_meta_value && $new_meta_value != $meta_value ) {
            
            update_post_meta( $post_id, $meta_key, $new_meta_value );
            
        /* If there is no new meta value but an old value exists, delete it. */
        } elseif ( '' == $new_meta_value && $meta_value ) {
            
            delete_post_meta( $post_id, $meta_key, $meta_value );
        }  
    }
    
}

    add_action( 'save_post', 'mgd_redirect_posts_url_save_post_class_meta', 10, 2 );

