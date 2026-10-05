<?php // phpcs:ignoreFile -- Forked from the WpRentals theme with a hotel-card swap; reformatting would hide the diff.

global $prop_selection ;
global $post;
global $wpestate_book_from;
global $wpestate_book_to;
global $wpestate_listing_type;
global $wpestate_property_unit_slider;
global $schema_flag;
$wpestate_property_unit_slider =   esc_html ( wprentals_get_option('wp_estate_prop_list_slider','') ); 
$wpestate_listing_type         =   wprentals_get_option('wp_estate_listing_unit_type','');
$listing_unit_style_half       =   wprentals_get_option('wp_estate_listing_unit_style_half','');
$wpestate_header_type          =   'header_'. esc_html(wprentals_get_option('wp_estate_logo_header_type', ''));
$map_position                  =   wprentals_get_option('wp_estate_align_style_half', '') ;
$map_position_class            =    '';

if($map_position==2){
    $map_position="half_map_on_right";
}
global $post;
$page_template='';
if(isset($post->ID)){
    $page_template = get_post_meta( $post->ID, '_wp_page_template', true );
}


ob_start(); 
    while ($prop_selection->have_posts()): $prop_selection->the_post(); 
        $schema_flag=1;
        echo \StaySuite\Companion\Blocks\Renderer::render_hotel_card( get_the_ID(), $listing_unit_style_half == 1 );
    endwhile;

$templates = ob_get_contents();
ob_end_clean(); 
wp_reset_query(); 
$schema_flag=0;
?>

<div class="row <?php echo esc_attr($map_position); ?>">
    <div  id="google_map_prop_list_wrapper" class="google_map_prop_list <?php echo esc_html('half_'. $wpestate_header_type); ?>">
        <?php  include(locate_template('templates/google_maps_base_map_list.php') );?>
    </div>    
    
    
    <div id="google_map_prop_list_sidebar" class="<?php echo esc_html('half_'. $wpestate_header_type); ?>">
        <?php  include(locate_template('templates/compare_list.php')); ?> 
        <?php  include(locate_template('templates/advanced_search_map_list.php'));?>
        <?php  include(locate_template('templates/spiner.php') ); ?> 
            
        <div id="listing_ajax_container" class="ajax-map" itemscope itemtype ="http://schema.org/ItemList"> 
            
            <?php if( !is_tax() ){?>
                <?php while (have_posts()) : the_post(); ?>
                <?php 
                    if (esc_html( get_post_meta($post->ID, 'page_show_title', true) ) == 'yes') { 
                        if( $page_template=='advanced_search_results.php' ){?>
                            <h1 class="entry-title title_prop"><?php the_title(); print ': '.esc_html($prop_selection->found_posts).' '.esc_html__( 'results','wprentals');?></h1>
                        <?php }else{ ?>
                            <h1 class="entry-title title_prop"><?php the_title();?></h1>   
                        <?php } 
                
                    }
                ?>
                <div class="single-content half-single-content"><?php the_content();?></div>
                <?php endwhile; // end of the loop.  ?>  
            <?php }else if( $page_template == 'advanced_search_results.php'  ){
                print '<h1 class="entry-title title_prop">'.esc_html__( 'Search Results','wprentals').'</h1>';
            }else{ ?>
                <h1 class="entry-title title_prop"> 
                    <?php 
                    esc_html_e('Listings in ','wprentals');echo '"';single_cat_title();echo '" ';
                    ?>
                </h1>
        
            <?php }            
       print trim($templates);
                           
        ?>
        </div>
        <!-- Listings Ends  here --> 
        <?php
        if($prop_selection->have_posts()):
            wprentals_pagination($prop_selection->max_num_pages, $range =2); 
        endif;
        ?>       
    </div><!-- end 8col container-->
</div>   