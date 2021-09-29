<?php
function log_message($message) {
	$date = new DateTime();
	$date = $date->format("h:i:s on d/m/y");
	$message = $date.": ".$message."\n\n";
    error_log($message, 3, __DIR__ . '/log.txt');   
}

log_message("running");
function mass_image_import() {
    log_message("inside mass image action");

    // get all images on date date !!!!!!
    global $wpdb;
    $images = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND post_date LIKE '%2021-03-31%'") ); // March 25 for tires, 30th for rims

    $images_r = print_r($images, 1);
    //log_message("images dump looks $images_r");
    
    // for each image: apply it to the correct product
    foreach ($images as $image) {
        $image_title = $image->post_title;
        $image_id = $image->ID;
        // log_message("image id is $image_id");
        // trim off the name of the file type and the number near the end denoting position of the image within the product
        // $sku = substr($image_title, 0, strpos($image_title, ".") - 3);

        // if the last 5 characters of title contains 2 dashes (eg. -01-1) then it is a duplicate upload that doesn't follow naming convention, skip and let the first upload be assigned instead
        $image_tite_last_5_char = substr($image_title, -5);
        if (substr_count($image_tite_last_5_char, "-") > 1){
            log_message("duplicate found, name is $image_title");
            continue;
        }

        // trim off the number near the end denoting position of the image within the product
        $sku = substr($image_title, 0, -2);

        $position = substr($image_title, -1);

        log_message("sku is $sku");

        $product = get_product_by_sku($sku);

        $product_r = print_r($product, 1);
        $product_id = $product->id;
        //log_message("product object for $image_title looks like $product_r");
        log_message("product post ID is $product_id");

        // if this is the first image for the product, set it as the main product image
        if ($position === "1"){
            log_message("setting thumbnail for $product_id to image with post id of $image_id");
            //set_post_thumbnail( $product_id, $image_id ); //-------------------------------uncomment to go live
        }

        // if there is a main image add it to the gallery
        // if there is already meta data then update it
        // else add_post_meta
        $product_meta = get_post_meta ($product_id);
        $product_meta_r = print_r($product_meta, 1);

        $_product_image_gallery = $product_meta['_product_image_gallery'];
        
        if (is_array($_product_image_gallery)===FALSE){
            log_message("setting image gallery meta to blank array because it is not in array format");
            $_product_image_gallery = [];
        }
        array_push($_product_image_gallery, $image_id);

        $string_image_id = strval($image_id);
        //$_product_image_gallery = []; //-------------------------------uncomment to delete product image galleries
        update_post_meta($product_id, '_product_image_gallery', implode(',', $_product_image_gallery)); //--------uncomment to go live
        
        log_message ("product meta looks like $product_meta_r");       

    }
}

add_action('wp_loaded', 'mass_image_import', 10, 2);


function get_product_by_sku($sku) {

    global $wpdb;

    $product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku ) );

    log_message();
    if ( $product_id ) return new WC_Product( $product_id );

    return null;
}