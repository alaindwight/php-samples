<?php

/**
 * Sync manufacturers rep fields based on Reps manufacturers fields
 */
function acf_syncValues_repToManufacturer( $value, $post_id, $field  ) {
    // vars
    $field_name = $field['name'];
    $field_key = $field['key'];
    $global_name = 'is_updating_' . $field_name;


    // bail early if this filter was triggered from the update_field() function called within the loop below
    // - this prevents an inifinte loop
    if( !empty($GLOBALS[ $global_name ]) ) return $value;


    // set global variable to avoid inifite loop
    // - could also remove_filter() then add_filter() again, but this is simpler
    $GLOBALS[ $global_name ] = 1;


    // loop over selected posts and add this $post_id
    if( is_array($value) ) {

        // each value in the reps "manufacturer" field is assigned as $post_id2
        foreach( $value as $post_id2 ) {

            // load existing related posts -- use $post_id2 (each value in rep's 'manufacturer' field) to load that manufacturer's 'the_representative' field
            $value2 = get_field('the_representative', $post_id2, false);


            // allow for selected posts to not contain a value
            if( empty($value2) ) {

                $value2 = array();

            }


            // bail early if the current (rep) $post_id is already found in selected (manufacturer) post's $value2 ('the_reprsentative' field)
            if( in_array($post_id, $value2) ) continue;


            // append the current $post_id to the selected post's 'related_posts' value
            $value2[] = $post_id;


            // update the selected post's value (use field's key for performance) -- field key for 'the_representative' is the same on all 'manufacturer' posts
            update_field('field_60467e578ad4b', $value2, $post_id2);

        }

    }


    // if a representative post dropped a manufacturer then update the manufacturer post to mirror the change (drop the rep from manufacturer's 'the_representative' field)
    // $old_value loads 'manufacturer' field's value from representative post (which hasn't been updated because this WP/ACF filter fires before the field is updated)
    $old_value = get_field($field_name, $post_id, false);

    if( is_array($old_value) ) {
        // each value in the reps OLD "manufacturer" field is assigned as $post_id2 so that we can load each previously assigned manufacturer by post ID
        foreach( $old_value as $post_id2 ) {

            // if the entry from the old manufacturor field (presented in this iteration of the loop) remains present in the new value passed to the filter then 
            // bail early (skip to nextloop iteration) because this value has not been removed from the rep post and therefore there is no corresponding value to remove from the manufacturer post
            if( is_array($value) && in_array($post_id2, $value) ) continue;


            // load existing related posts
            $value2 = get_field('the_representative', $post_id2, false);


            // bail early if no value
            if( empty($value2) ) continue;


            // find the position of $post_id(ID for representative) within $value2 (from manufacturer post)so we can remove it
            $pos = array_search($post_id, $value2);


            // remove
            unset( $value2[ $pos] );


            // update the un-selected post's value (use field's key for performance) -- field key for 'the_representative' is same on all 'manufacturer' posts
            // save $value2 back the original source now the rep's post ID has been removed from the array
            update_field('field_60467e578ad4b', $value2, $post_id2);

        }

    }


    // reset global varibale to allow this filter to function as per normal
    $GLOBALS[ $global_name ] = 0;


    // return
    return $value;

}
// this runs if the field being updated is named "manufacturers" (this field only appears on the field group called for "representatives")
add_filter('acf/update_value/name=manufacturers', 'acf_syncValues_repToManufacturer', 10, 3);

/**
 * Sync rep's manufacturer fields based on manufacturer's rep fields
 */
function acf_syncValues_manufacturerToRep( $value, $post_id, $field  ) {
    // vars
    $field_name = $field['name'];
    $field_key = $field['key'];
    $global_name = 'is_updating_' . $field_name;
    // prevent/auto correct duplicate relationships - forces the return value to contain only the latest entry in the array for Manufacturer's 'the_representative' field
    // -- this then causes all other relationships to this manufacturer to be removed both on the manufacturer post and the rep posts
    $finalValue = array_slice($value, -1); 

    // bail early if this filter was triggered from the update_field() function called within the loop below
    // - this prevents an inifinte loop
    if( !empty($GLOBALS[ $global_name ])  ) {
        return $finalValue;
    }

    // set global variable to avoid inifite loop
    // - could also remove_filter() then add_filter() again, but this is simpler
    $GLOBALS[ $global_name ] = 1;

    
    // loop over (jump to) associated rep post
    if( is_array($finalValue) ) {

        // each value in the manufacturer's "the_representative" field is assigned as $post_id2
        foreach( $finalValue as $post_id2 ) {

            // load existing related posts -- use $post_id2 (each value in manufacturer's 'the_representative' field) to load that rep's 'manufacturers' field
            $value2 = get_field('manufacturers', $post_id2, false);

            // allow for selected posts to not contain a value
            if( empty($value2) ) {

                $value2 = array();

            }


            // bail early if the current (manufacturer) $post_id is already found in selected (rep) post's $value2 ('manufacturers' field)
            if( in_array($post_id, $value2) ) continue;


            // append the current $post_id to the selected post's 'related_posts' value
            $value2[] = $post_id;


            // update the selected post's value (use field's key for performance) -- field key for 'manufacturers' is the same on all 'represntative' posts
            update_field('field_60467eb16ccab', $value2, $post_id2);

        }

    }


    // all values in the $value field before the latest value are previously associated reps, loop through and update all dissasociated rep posts to no longer have the dissasociated manu id in ther manu field
    $i = 0;
    $length = count($value);
    foreach( $value as $post_id2 ) {
        // if this is the final iteration then bail because this ID is for the newest rep and should not be dissasociated
        if ($i == $length - 1) {
            continue;
        }

        // load reps manufacturer field
        $value2 = get_field('manufacturers', $post_id2, false);
        // bail early if no value
        if( empty($value2) ){
            $i++;
            continue;
        }
        
        // find the position of $post_id (ID for manufacturer) within $value2 (from rep post)so we can remove it
        $pos = array_search($post_id, $value2);
        // remove
        unset( $value2[$pos] );

        // update the un-selected post's value (use field's key for performance) -- field key for 'manufacturers' is the same on all 'represntative' posts
        // save $value2 back the original source now the rep's post ID has been removed from the array
        update_field('field_60467eb16ccab', $value2, $post_id2);
        $i++;
    }
    // reset global varibale to allow this filter to function as per normal
    $GLOBALS[ $global_name ] = 0;

    // return
    return $finalValue;

}
// this runs if the field being updated is named "the_representative" (this field only appears on the field group called for "manufacturers")
add_filter('acf/update_value/name=the_representative', 'acf_syncValues_manufacturerToRep', 10, 3);