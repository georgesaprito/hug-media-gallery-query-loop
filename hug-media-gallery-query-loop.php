<?php
/**
 * Plugin Name:       Hug Media Gallery Query Loop
 * Description:       Example block scaffolded with Create Block tool. Requires Media Library Categories plugin.
 * Version:           2.1.3
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            George Saprito
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.gnu/licenses/gpl-2.0.html
 * Text Domain:       hug-media-gallery-query-loop
 *
 * @package CreateBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// --- 1. DEPENDENCY CHECK AND NOTICES (No Change) ---

function hug_media_gallery_query_loop_check_dependencies() {
    if ( ! function_exists( 'is_plugin_active' ) ) {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    }
    if ( ! is_plugin_active( 'wp-media-library-categories/index.php' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        add_action( 'admin_notices', 'hug_media_gallery_query_loop_missing_dependency_notice' );
    }
}
add_action( 'admin_init', 'hug_media_gallery_query_loop_check_dependencies' );

function hug_media_gallery_query_loop_missing_dependency_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><strong>Hug Media Gallery Query Loop</strong> requires the <strong>Media Library Categories</strong> plugin to be installed and activated before it can be used.</p>
    </div>
    <?php
}

// --- 2. REST EXPOSURE FIX (No Change) ---

function hug_media_gallery_query_loop_manually_register_term_route() {
    
    $media_taxonomy_slug = hug_get_media_taxonomy_slug();

    if ( in_array( $media_taxonomy_slug, array( 'category', 'post_tag' ) ) ) {
        return;
    }
    
    if ( ! taxonomy_exists( $media_taxonomy_slug ) ) {
        return;
    }

    $namespace = 'wp/v2';
    $rest_base = $media_taxonomy_slug;

    register_rest_route( $namespace, '/' . $rest_base, array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => function( $request ) use ( $media_taxonomy_slug ) {
            $args = array(
                'taxonomy' => $media_taxonomy_slug,
                'hide_empty' => false,
                'orderby' => $request['orderby'],
            );
            return get_terms( $args );
        },
        'permission_callback' => function( $request ) {
            $tax = get_taxonomy( hug_get_media_taxonomy_slug() );
            return current_user_can( $tax->cap->edit_terms );
        },
        'args'                => array(
            'context' => array(
                'default' => 'view',
            ),
            'orderby' => array(
                'default' => 'name',
            ),
        ),
    ) );
}
add_action( 'rest_api_init', 'hug_media_gallery_query_loop_manually_register_term_route' );


// --- 3. BLOCK REGISTRATION & ASSET ENQUEUING (No Change) ---
function create_block_hug_media_gallery_query_loop_block_init() {
    $block_metadata_path = __DIR__ . '/build/hug-media-gallery-query-loop';

    register_block_type( $block_metadata_path, [
        'render_callback' => 'hug_media_gallery_query_loop_render_block',
    ] );

    add_filter( 'register_block_type_args', 'hug_media_gallery_query_loop_attach_renderer', 10, 2 );

    add_action( 'enqueue_block_editor_assets', 'hug_media_gallery_query_loop_enqueue_editor_assets' ); 
    
    add_action( 'wp_enqueue_scripts', 'hug_media_gallery_query_loop_deregister_view_script', 9 );
}
add_action( 'init', 'create_block_hug_media_gallery_query_loop_block_init' );

function hug_media_gallery_query_loop_deregister_view_script() {
    $auto_handle_1 = 'create-block-hug-media-gallery-query-loop-view-script'; 
    $auto_handle_2 = 'view';
    
    wp_deregister_script( $auto_handle_1 ); 
    wp_deregister_script( $auto_handle_2 ); 
}

function hug_media_gallery_query_loop_attach_renderer( $args, $block_type ) {
    if ( 'create-block/hug-media-gallery-query-loop' === $block_type ) {
        $args['render_callback'] = 'hug_media_gallery_query_loop_render_block';
    }
    return $args;
}

/**
 * Enqueues view.js for the editor (which now includes fancy-layout logic).
 */
function hug_media_gallery_query_loop_enqueue_editor_assets() {
    wp_enqueue_script( 
        'hmgq-frontend-init', 
        plugins_url( 'build/hug-media-gallery-query-loop/view.js', __FILE__ ), 
        array( 'wp-element', 'wp-api-fetch' ), 
        '1.0', 
        true 
    );
}

// --- HELPER FUNCTION: Collects Image Data for JS (Protected Dimensions) ---
function hug_collect_gallery_image_data( $media_query, $image_size ) {
    $images_data = [];
    $media_query->rewind_posts(); 
    
    while ( $media_query->have_posts() ) {
        $media_query->the_post();
        
        $image_id = get_the_ID();
        $title = get_the_title();
        
        $image_meta = wp_get_attachment_metadata( $image_id );
        
        $image_html = wp_get_attachment_image(
            $image_id,
            $image_size, 
            false,
            [
                'alt' => esc_attr( $title ),
                'style' => 'width: 100%; height: 100%; object-fit: cover;'
            ]
        );
        
        $images_data[] = [
            'id' => $image_id,
            'url' => wp_get_attachment_image_url( $image_id, $image_size ),
            'full_url' => wp_get_attachment_image_url( $image_id, 'full' ),
            
            // CRITICAL FIX: Protected dimensions for Photoswipe attributes
            'original_width' => $image_meta['width'] ?? 0, 
            'original_height' => $image_meta['height'] ?? 0, 
            
            // Dimensions for recursion input (will be overwritten by recursion)
            'width' => $image_meta['width'] ?? 0, 
            'height' => $image_meta['height'] ?? 0, 
            
            'title' => $title,
            'image_html' => $image_html,
        ];
    }
    
    $media_query->rewind_posts(); 
    
    return $images_data;
}


// --- 4. DYNAMIC RENDERING FUNCTION (CRITICAL UPDATE) ---

function hug_media_gallery_query_loop_render_block( $attributes ) {
    
    $media_taxonomy_slug = hug_get_media_taxonomy_slug();
    $category_slug = $attributes['mediaTaxonomy'] ?? '';
	
	// If no category is selected, we want to stop immediately.
    $category_slug = $attributes['mediaTaxonomy'] ?? '';
    
    if ( empty( $category_slug ) ) {
        // If we are in the editor (ServerSideRender call), return null so edit.js 
        // can show its own custom Placeholder instead of "No media found".
        $is_editor_request = false;
        if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
            if ( strpos( $_SERVER['REQUEST_URI'], 'context=edit' ) !== false || strpos( $_SERVER['REQUEST_URI'], '/block-renderer/' ) !== false ) {
                return null; 
            }
        }
        
        // On the frontend, return an empty string so nothing is rendered.
        return '';
    }
    // --- END NEW EARLY EXIT CHECK ---
	
    $sort_option = $attributes['sortOption'] ?? 'date';
	$order_dir = $attributes['order'] ?? 'DESC';
    $image_size = $attributes['imageSize'] ?? 'full';
    $columns = $attributes['columns'] ?? 3;
    $use_lightbox = $attributes['useLightbox'] ?? false;
    $layout_style = $attributes['layoutStyle'] ?? 'grid';
    $showTitles = $attributes['showTitles'] ?? false;
    
    $original_columns = $columns;
    if ( in_array( $layout_style, ['tiled', 'fancy'] ) ) {
        $columns = 6;
    }

    $args = [
        'post_type'          => 'attachment',
        'post_status'        => array('inherit', 'publish'),
        'posts_per_page'     => -1, 
		'orderby'			 => $sort_option,
		'order'				 => $order_dir,
        'tax_query'          => array()
    ];
    
    if ( ! empty( $category_slug ) ) {
        $args['tax_query'][] = array(
            'taxonomy' => $media_taxonomy_slug,
            'field'    => 'slug',
            'terms'    => array( $category_slug ),
            'operator' => 'IN',
        );
        unset($args['post_parent']);
    } else {
        $args['post_parent'] = 0;
    }
    
/*     switch ( $sort_option ) {
        case 'newest_to_oldest': $args['order'] = 'desc'; $args['orderby'] = 'date'; break;
        case 'oldest_to_newest': $args['order'] = 'asc'; $args['orderby'] = 'date'; break;
        case 'a_z': $args['order'] = 'asc'; $args['orderby'] = 'title'; break;
        case 'z_a': $args['order'] = 'desc'; $args['orderby'] = 'title'; break;
    } */
    
    $media_query = new WP_Query( $args );
    $output = '';

    $alignment = $attributes['align'] ?? '';
    $container_class = !empty($alignment) ? "align{$alignment}" : '';
    
    $needs_frontend_js = $use_lightbox || $layout_style === 'fancy';

    // --- START CRITICAL CHECK: RENDER PLACEHOLDER FOR EDITOR (FINAL URI CHECK) ---
    
    // Check if we are running in the editor context using the server request URI
    $is_editor_request = false;
    if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
        // Look for common REST API endpoints used to render blocks in the editor
        if ( strpos( $_SERVER['REQUEST_URI'], 'context=edit' ) !== false || strpos( $_SERVER['REQUEST_URI'], '/block-renderer/' ) !== false ) {
            $is_editor_request = true;
        }
    }
    
    if ( $is_editor_request && $layout_style === 'fancy' ) {
        // Output the static placeholder in the editor/admin view.
        $output .= '<div class="hug-media-query-loop-container ' . esc_attr($container_class) . '" style="padding: 20px; border: 2px dashed #ddd; text-align: center;">';
        $output .= '<p><strong>Placeholder for Fancy Layout:</strong> The complex recursive rendering is disabled in the editor.</p>';
        $output .= '<p>Gallery will display correctly on the live page.</p>';
        $output .= '</div>';
        
        wp_reset_postdata();
        return $output; // EXIT EARLY
    }
    // --- END CRITICAL CHECK ---


    if ( $media_query->have_posts() ) {

        // --- FANCY LAYOUT: Client-Side Rendering (FRONT END ONLY) ---
        if ( $layout_style === 'fancy' ) {
            $images_data = hug_collect_gallery_image_data( $media_query, $image_size );

            $gallery_id = 'gallery-' . get_the_ID() . '-' . wp_rand(1000, 9999);
            $container_class .= ' is-fancy-layout';
            
            $output .= '<div id="' . esc_attr($gallery_id) . '" class="hug-media-query-loop-container ' . esc_attr($container_class) . '">';
            // IMPORTANT: This 'Loading...' line is required for the JS to know where to render.
            $output .= '<div class="hug-media-query-loop-wrapper ' . esc_attr($container_class) . '" data-images="' . esc_attr( json_encode( $images_data ) ) . '" data-settings="' . esc_attr( json_encode( ['maxHeight' => 1000, 'padding' => 10, 'lightbox' => $use_lightbox, 'showTitles' => $showTitles] ) ) . '">';
            //$output .= '<div class="hug-media-query-loop-wrapper" data-images="' . esc_attr( json_encode( $images_data ) ) . '" data-settings="' . esc_attr( json_encode( ['maxHeight' => 1000, 'padding' => 10, 'lightbox' => $use_lightbox, 'showTitles' => $showTitles] ) ) . '">';
            $output .= ''; //'<p style="text-align: center; margin: 50px;">Loading fancy gallery...</p>';
            $output .= '</div>';
            $output .= '</div>';
        
        } else {
            
            // --- Tiled, Grid, Masonry Layouts: Existing Server-Side Rendering (No Change) ---

            $final_row_span_class = '';
            $start_index_for_spanning = 0;
            
            if ( 'grid' === $layout_style || 'tiled' === $layout_style ) {
                $total_posts = $media_query->found_posts;
                $remainder = $total_posts % $columns;
                
                if ($remainder > 0 && $remainder < $columns) {
                    $start_index_for_spanning = $total_posts - $remainder;
                    $final_row_span_class = 'final-' . $remainder . '-items';
                }
            }
            
            $wrapper_classes = 'hug-media-query-loop-wrapper';
            $wrapper_styles = '';

            if ( 'grid' === $layout_style || 'tiled' === $layout_style ) {
                $wrapper_classes .= ' is-grid';
                $wrapper_classes .= ' is-' . $layout_style;
                $wrapper_styles = "display: grid; grid-template-columns: repeat({$columns}, 1fr); gap: 1rem; grid-auto-flow: dense; grid-auto-rows: 1fr; --columns: {$columns};";
            } elseif ( 'masonry' === $layout_style ) {
                $wrapper_classes .= ' is-masonry';
                $wrapper_styles = 'column-count: ' . $original_columns . '; column-gap: 1rem;';
            }

            $output .= '<div class="hug-media-query-loop-container ' . esc_attr($container_class) . '">';
            $output .= '<div class="'. esc_attr($wrapper_classes) . '" style="' . esc_attr($wrapper_styles) . '">';
            
            $gallery_id = 'gallery-' . get_the_ID() . '-' . wp_rand(1000, 9999);

            $counter = 0; 
            $media_query->rewind_posts(); 

            while ( $media_query->have_posts() ) {
                $media_query->the_post();
                
                $image_id = get_the_ID();
                $image_url = wp_get_attachment_image_url( $image_id, $image_size );
                $full_image_url = wp_get_attachment_image_url( $image_id, 'full' );
                
                $image_meta = wp_get_attachment_metadata( $image_id );
                $full_width = $image_meta['width'] ?? 0;
                $full_height = $image_meta['height'] ?? 0;
                $title = get_the_title();
                
                $item_class = 'hug-media-item';
                
                if ( 'tiled' === $layout_style && $full_width > 0 ) {
                    if ( $full_height > $full_width ) { $item_class .= ' span-2-rows'; }
                    elseif ( $full_width > $full_height ) { $item_class .= ' span-2-cols'; }
                }
                
                if ( ('grid' === $layout_style || 'tiled' === $layout_style) && !empty($final_row_span_class) && $counter >= $start_index_for_spanning ) {
                    $item_class .= " {$final_row_span_class}";
                }

                if ( $image_url ) {
                    $output .= '<div class="'. esc_attr($item_class) . '">';
                    
                    if ( $use_lightbox && $full_image_url ) {
                        $output .= '<a href="' . esc_url($full_image_url) . '" ';
                        $output .= 'data-pswp-width="' . esc_attr($full_width) . '" ';
                        $output .= 'data-pswp-height="' . esc_attr($full_height) . '" ';
						$output .= 'data-cropped="true"';
                        $output .= 'class="pswp-gallery__item" ';
                        $output .= 'title="' . esc_attr($title) . '">';
                    }
                    
                    $image_html = wp_get_attachment_image(
                        $image_id,
                        $image_size,
                        false,
                        [
                            'alt' => esc_attr( $title ),
                            'style' => 'width: 100%; height: 100%; object-fit: cover;'
                        ]
                    );
                    
                    if (!$image_html) {
                        $image_html = '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $title ) . '" style="width: 100%; height: 100%; object-fit: cover;"/>';
                    }
                    
                    $output .= $image_html;
                    
                    if ( $use_lightbox ) {
                        $output .= '</a>';
                    }
                    
                    if($showTitles) {
                        $output .= '<h3>' . esc_html( $title ) . '</h3>';
                    }
                    $output .= '</div>';
                }
                $counter++;
            }
            $output .= '</div>';
            $output .= '</div>';
        }
        
        // --- CONDITIONAL ASSET ENQUEUING (No Change) ---
        
        if ($use_lightbox) {
            wp_enqueue_style( 'hmgq-photoswipe-css', plugins_url( 'build/hug-media-gallery-query-loop/photoswipe.css', __FILE__ ), array(), '5.4.3' );
            wp_enqueue_script( 'hmgq-photoswipe-core', plugins_url( 'build/hug-media-gallery-query-loop/photoswipe.umd.min.js', __FILE__ ), array(), '5.4.3', true );
        }
        
        // Only enqueue view.js for front end since fancy layout is only rendered there
        if ($needs_frontend_js) {
             $view_dependencies = array( 'jquery' ); 
             
             wp_enqueue_script( 'hmgq-frontend-init', plugins_url( 'build/hug-media-gallery-query-loop/view.js', __FILE__ ), $view_dependencies, '1.0', true );
        }


    } else {
        $output .= '<p>' . __( 'No media found matching the criteria.', 'hug-media-gallery-query-loop' ) . '</p>';
    }

    wp_reset_postdata();
	
	// Enqueue the frontend styles (Borders, Fancy Layout, Masonry)
	wp_enqueue_style( 
		'hmgq-frontend-styles', 
		plugins_url( 'build/hug-media-gallery-query-loop/style-index.css', __FILE__ ), 
		array(), 
		'2.1.0' 
	);

	// Enqueue the common/editor styles
	wp_enqueue_style( 
		'hmgq-common-styles', 
		plugins_url( 'build/hug-media-gallery-query-loop/index.css', __FILE__ ), 
		array(), 
		'2.1.0' 
	);
		
	// Load the local PhotoSwipe CSS
	wp_enqueue_style( 
		'photoswipe-core-css', 
		plugins_url( 'assets/photoswipe.css', __FILE__ ), 
		array(), 
		'5.4.3' 
	);

	// Load the local PhotoSwipe JS (if your view.js isn't already bundling it)
	wp_enqueue_script( 
		'hmgq-photoswipe-core', 
		plugins_url( 'assets/photoswipe.umd.min.js', __FILE__ ), 
		array(), 
		'5.4.3', 
		true 
	);

	return $output;
}

// --- 5. REST ROUTE FOR JS CONFIGURATION (No Change) ---
function hug_register_media_taxonomy_slug_route() {
    register_rest_route( 'hug/v1', '/media-taxonomy-slug', array(
        'methods'  => 'GET',
        'callback' => 'hug_rest_get_media_taxonomy_slug',
        'permission_callback' => function () {
            return current_user_can( 'edit_posts' );
        }
    ) );
}
add_action( 'rest_api_init', 'hug_register_media_taxonomy_slug_route' );

function hug_rest_get_media_taxonomy_slug() {
    return new WP_REST_Response( array( 
        'slug' => hug_get_media_taxonomy_slug() 
    ), 200 );
}

// --- 6. CORRECT TAXONOMY RETRIEVAL METHOD (No Change) ---

function hug_get_media_taxonomy_slug() {
    global $wpmedialibrarycategories;

    if ( is_a( $wpmedialibrarycategories, 'wpMediaLibraryCategories' ) && method_exists( $wpmedialibrarycategories, 'get_wpmlc_taxonomy' ) ) {
        return $wpmedialibrarycategories->get_wpmlc_taxonomy();
    }
    
    return 'media_category'; 
}