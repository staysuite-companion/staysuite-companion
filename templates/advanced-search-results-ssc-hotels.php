<?php // phpcs:ignoreFile -- Forked from the WpRentals theme with a hotel-card swap; reformatting would hide the diff.

// Template Name: Advanced Search Results
// Wp Estate Pack

get_header();
$current_user           =   wp_get_current_user();
$wpestate_options       =   wpestate_page_details($post->ID);
$show_compare           =   1;
$area_array             =   ''; 
$city_array             =   '';  
$action_array           =   '';
$categ_array            =   '';
$tax_query              =   '';
$allowed_html           =   array();
$compare_submit         =   wpestate_get_template_link('compare_listings.php');
$wpestate_currency      =   esc_html( wprentals_get_option('wp_estate_currency_label_main') );
$wpestate_where_currency=   esc_html( wprentals_get_option('wp_estate_where_currency_symbol') );
$prop_no                =   intval ( wprentals_get_option('wp_estate_prop_no') );
$show_compare_link      =   'yes';
$userID                 =   $current_user->ID;
$user_option            =   'favorites'.$userID;
$wpestate_curent_fav    =   get_option($user_option);
$meta_query             =   array();
           
$adv_search_what        =   '';
$adv_search_how         =   '';
$adv_search_label       =   '';             
$adv_search_type        =   '';   

$wpestate_book_from     =   '';
$wpestate_book_to       =   '';
$allowed_html           =   array();
                


$is_half=0;
if(isset($_GET['is_half'])){
  $is_half = intval($_GET['is_half']);  
}




$compute        =   wpestate_argumets_builder($_REQUEST,$is_half); 
$prop_selection =   $compute[0];
$args           =   $compute[1];

//print_r($args);




if (    ! isset( $_GET['wpestate_regular_search_nonce'] )  || ! wp_verify_nonce( $_GET['wpestate_regular_search_nonce'], 'wpestate_regular_search' ) ) {
}

////////////////////////////////////////////////////////////////////////////////////////////////////
/// get template and display results
///////////////////////////////////////////////////////////////////////////////////////////////////


$property_list_type_status =    esc_html(wprentals_get_option('wp_estate_property_list_type_adv'));
if ( $property_list_type_status == 2 ){
    include(SSC_PATH . 'templates/advsearch-half-map-core.php');
}else{
    include(SSC_PATH . 'templates/advsearch-normal-map-core.php');
}



if (wp_script_is( 'wpestate_googlecode_regular', 'enqueued' )) {
    $mapargs                    =   $args;
    $max_pins                   =   intval( wprentals_get_option('wp_estate_map_max_pins') );
    $mapargs['posts_per_page']  =   $max_pins;
    $mapargs['offset']          =   ($paged-1)*$prop_no;
  
    $args['fields']='ids';
    $selected_pins  = wpestate_listing_pins('blank',0,$args,1,1);//call the new pins  

    wp_localize_script('wpestate_googlecode_regular', 'googlecode_regular_vars2', 
        array(  
            'markers2'           => $selected_pins,
        )
    );

}
get_footer(); 
?>