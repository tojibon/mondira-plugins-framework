<?php 
global $mondira_options;

/**
 * Mondira plugins settings get option just like get_option wp itself
 *
 * 
 * @param section string 
 * @param name string
 * @param default string
 * @return string
 */
if (!function_exists('mondira_get_option')) {
    function mondira_get_option($section, $name = NULL, $default = false) {
        global $mondira_options;
        
        if ($name == NULL) {
            if (isset($mondira_options[$section])) {
                $return = $mondira_options[$section];
            } else {
                $return = false;
            }
        } else {
            if (isset($mondira_options[$section][$name])) {
                $return = $mondira_options[$section][$name];
            } else {
                $return = false;
            }
        }
        
        if(!empty($return)){
            return $return;
        } else {
            return $default;
        }
    }
}

/**
 * Mondira plugins settings set option just like add_option or update_option wp itself
 *
 * 
 * @param section string 
 * @param name string
 * @param value string
 * @return null
 */
if (!function_exists('mondira_set_option')) {
    function mondira_set_option($section, $name, $value) {
        global $mondira_options;
        
        $mondira_options[$section][$name] = $value;
        
        update_option($plugins_obj->config[$plugins_obj->plugins_slug]['MONDIRA_PLUGINS_SLUG'] . '_' . $section, $mondira_options[$section]);
    }
}

/**
 * Mondira plugins post id specific thumbnail url
 *
 * 
 * @param pid int
 * @param size_for string
 * @return string
 */
if (!function_exists('mondira_thumbnail_url')) {
    function mondira_thumbnail_url($pid, $size_for='thumbnail'){
        $image_id = get_post_thumbnail_id($pid);  
        $image_url = wp_get_attachment_image_src($image_id, $size_for);  
        return  $image_url[0];  
    }
}

/**
 * Content limit maker which can take a length and update the global active posts content and return it
 *
 * 
 * @param content_length int - number of characters to be visible 
 * @param allowtags boolean - is content can have markup tags
 * @param allowedtags string - allowed markup tags just like strip_tags params
 * @return string
 */
if (!function_exists('limit_content')) {
    function limit_content($content_length = 100, $allowtags = true, $allowedtags = '') {
        global $post;
        $content = $post->post_content;
        $content = apply_filters('the_content', $content);
        if (!$allowtags){
            $allowedtags .= '<style>';
            $content = strip_tags($content, $allowedtags);
        }
        $wordarray = explode(' ', $content, $content_length + 1);
        if(count($wordarray) > $content_length) :
            array_pop($wordarray);
            array_push($wordarray, '...');
            $content = implode(' ', $wordarray);
        endif;

        return $content;
    }
}

if (!function_exists('mondira_get_adjacent_post_plus')) {
    function mondira_get_adjacent_post_plus($r, $previous = true ) {
        global $post, $wpdb;

        extract( $r, EXTR_SKIP );

        if ( empty( $post ) )
            return null;

    //    Sanitize $order_by, since we are going to use it in the SQL query. Default to 'post_date'.
        if ( in_array($order_by, array('post_date', 'post_title', 'post_excerpt', 'post_name', 'post_modified')) ) {
            $order_format = '%s';
        } elseif ( in_array($order_by, array('ID', 'post_author', 'post_parent', 'menu_order', 'comment_count')) ) {
            $order_format = '%d';
        } elseif ( $order_by == 'custom' && !empty($meta_key) ) { // Don't allow a custom sort if meta_key is empty.
            $order_format = '%s';
        } elseif ( $order_by == 'numeric' && !empty($meta_key) ) {
            $order_format = '%d';
        } else {
            $order_by = 'post_date';
            $order_format = '%s';
        }
        
    //    Sanitize $order_2nd. Only columns containing unique values are allowed here. Default to 'post_date'.
        if ( in_array($order_2nd, array('post_date', 'post_title', 'post_modified')) ) {
            $order_format2 = '%s';
        } elseif ( in_array($order_2nd, array('ID')) ) {
            $order_format2 = '%d';
        } else {
            $order_2nd = 'post_date';
            $order_format2 = '%s';
        }
        
    //    Sanitize num_results (non-integer or negative values trigger SQL errors)
        $num_results = intval($num_results) < 2 ? 1 : intval($num_results);

    //    Queries involving custom fields require an extra table join
        if ( $order_by == 'custom' || $order_by == 'numeric' ) {
            $current_post = get_post_meta($post->ID, $meta_key, TRUE);
            $order_by = ($order_by === 'numeric') ? 'm.meta_value+0' : 'm.meta_value';
            $meta_join = $wpdb->prepare(" INNER JOIN $wpdb->postmeta AS m ON p.ID = m.post_id AND m.meta_key = %s", $meta_key );
        } elseif ( $in_same_meta ) {
            $current_post = $post->$order_by;
            $order_by = 'p.' . $order_by;
            $meta_join = $wpdb->prepare(" INNER JOIN $wpdb->postmeta AS m ON p.ID = m.post_id AND m.meta_key = %s", $in_same_meta );
        } else {
            $current_post = $post->$order_by;
            $order_by = 'p.' . $order_by;
            $meta_join = '';
        }

    //    Get the current post value for the second sort column
        $current_post2 = $post->$order_2nd;
        $order_2nd = 'p.' . $order_2nd;
        
    //    Get the list of post types. Default to current post type
        if ( empty($post_type) )
            $post_type = "'$post->post_type'";

    //    Put this section in a do-while loop to enable the loop-to-first-post option
        do {
            $join = $meta_join;
            $excluded_categories = $ex_cats;
            $included_categories = $in_cats;
            $excluded_posts = $ex_posts;
            $included_posts = $in_posts;
            $in_same_term_sql = $in_same_author_sql = $in_same_meta_sql = $ex_cats_sql = $in_cats_sql = $ex_posts_sql = $in_posts_sql = '';

    //        Get the list of hierarchical taxonomies, including customs (don't assume taxonomy = 'category')
            $taxonomies = array_filter( get_post_taxonomies($post->ID), "is_taxonomy_hierarchical" );

            if ( ($in_same_cat || $in_same_tax || $in_same_format || !empty($excluded_categories) || !empty($included_categories)) && !empty($taxonomies) ) {
                $cat_array = $tax_array = $format_array = array();

                if ( $in_same_cat ) {
                    $cat_array = wp_get_object_terms($post->ID, $taxonomies, array('fields' => 'ids'));
                }
                if ( $in_same_tax && !$in_same_cat ) {
                    if ( $in_same_tax === true ) {
                        if ( $taxonomies != array('category') )
                            $taxonomies = array_diff($taxonomies, array('category'));
                    } else
                        $taxonomies = (array) $in_same_tax;
                    $tax_array = wp_get_object_terms($post->ID, $taxonomies, array('fields' => 'ids'));
                }
                if ( $in_same_format ) {
                    $taxonomies[] = 'post_format';
                    $format_array = wp_get_object_terms($post->ID, 'post_format', array('fields' => 'ids'));
                }

                $join .= " INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id INNER JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy IN (\"" . implode('", "', $taxonomies) . "\")";

                $term_array = array_unique( array_merge( $cat_array, $tax_array, $format_array ) );
                if ( !empty($term_array) )
                    $in_same_term_sql = "AND tt.term_id IN (" . implode(',', $term_array) . ")";

                if ( !empty($excluded_categories) ) {
    //                Support for both (1 and 5 and 15) and (1, 5, 15) delimiter styles
                    $delimiter = ( strpos($excluded_categories, ',') !== false ) ? ',' : 'and';
                    $excluded_categories = array_map( 'intval', explode($delimiter, $excluded_categories) );
    //                Three category exclusion methods are supported: 'strong', 'diff', and 'weak'.
    //                Default is 'weak'. See the plugin documentation for more information.
                    if ( $ex_cats_method === 'strong' ) {
                        $taxonomies = array_filter( get_post_taxonomies($post->ID), "is_taxonomy_hierarchical" );
                        if ( function_exists('get_post_format') )
                            $taxonomies[] = 'post_format';
                        $ex_cats_posts = get_objects_in_term( $excluded_categories, $taxonomies );
                        if ( !empty($ex_cats_posts) )
                            $ex_cats_sql = "AND p.ID NOT IN (" . implode($ex_cats_posts, ',') . ")";
                    } else {
                        if ( !empty($term_array) && !in_array($ex_cats_method, array('diff', 'differential')) )
                            $excluded_categories = array_diff($excluded_categories, $term_array);
                        if ( !empty($excluded_categories) )
                            $ex_cats_sql = "AND tt.term_id NOT IN (" . implode($excluded_categories, ',') . ')';
                    }
                }

                if ( !empty($included_categories) ) {
                    $in_same_term_sql = ''; // in_cats overrides in_same_cat
                    $delimiter = ( strpos($included_categories, ',') !== false ) ? ',' : 'and';
                    $included_categories = array_map( 'intval', explode($delimiter, $included_categories) );
                    $in_cats_sql = "AND tt.term_id IN (" . implode(',', $included_categories) . ")";
                }
            }

    //        Optionally restrict next/previous links to same author        
            if ( $in_same_author )
                $in_same_author_sql = $wpdb->prepare("AND p.post_author = %d", $post->post_author );

    //        Optionally restrict next/previous links to same meta value
            if ( $in_same_meta && $r['order_by'] != 'custom' && $r['order_by'] != 'numeric' )
                $in_same_meta_sql = $wpdb->prepare("AND m.meta_value = %s", get_post_meta($post->ID, $in_same_meta, TRUE) );

    //        Optionally exclude individual post IDs
            if ( !empty($excluded_posts) ) {
                $excluded_posts = array_map( 'intval', explode(',', $excluded_posts) );
                $ex_posts_sql = " AND p.ID NOT IN (" . implode(',', $excluded_posts) . ")";
            }
            
    //        Optionally include individual post IDs
            if ( !empty($included_posts) ) {
                $included_posts = array_map( 'intval', explode(',', $included_posts) );
                $in_posts_sql = " AND p.ID IN (" . implode(',', $included_posts) . ")";
            }

            $adjacent = $previous ? 'previous' : 'next';
            $order = $previous ? 'DESC' : 'ASC';
            $op = $previous ? '<' : '>';

    //        Optionally get the first/last post. Disable looping and return only one result.
            if ( $end_post ) {
                $order = $previous ? 'ASC' : 'DESC';
                $num_results = 1;
                $loop = false;
                if ( $end_post === 'fixed' ) // display the end post link even when it is the current post
                    $op = $previous ? '<=' : '>=';
            }

    //        If there is no next/previous post, loop back around to the first/last post.        
            if ( $loop && isset($result) ) {
                $op = $previous ? '>=' : '<=';
                $loop = false; // prevent an infinite loop if no first/last post is found
            }
            
            $join  = apply_filters( "get_{$adjacent}_post_plus_join", $join, $r );

    //        In case the value in the $order_by column is not unique, select posts based on the $order_2nd column as well.
    //        This prevents posts from being skipped when they have, for example, the same menu_order.
            $where = apply_filters( "get_{$adjacent}_post_plus_where", $wpdb->prepare("WHERE ( $order_by $op $order_format OR $order_2nd $op $order_format2 AND $order_by = $order_format ) AND p.post_type IN ($post_type) AND p.post_status = 'publish' $in_same_term_sql $in_same_author_sql $in_same_meta_sql $ex_cats_sql $in_cats_sql $ex_posts_sql $in_posts_sql", $current_post, $current_post2, $current_post), $r );

            $sort  = apply_filters( "get_{$adjacent}_post_plus_sort", "ORDER BY $order_by $order, $order_2nd $order LIMIT $num_results", $r );

            $query = "SELECT DISTINCT p.* FROM $wpdb->posts AS p $join $where $sort";
            $query_key = 'adjacent_post_' . md5($query);
            $result = wp_cache_get($query_key);
            if ( false !== $result )
                return $result;

    //        echo $query . '<br />';

    //        Use get_results instead of get_row, in order to retrieve multiple adjacent posts (when $num_results > 1)
    //        Add DISTINCT keyword to prevent posts in multiple categories from appearing more than once
            $result = $wpdb->get_results("SELECT DISTINCT p.* FROM $wpdb->posts AS p $join $where $sort");
            if ( null === $result )
                $result = '';

        } while ( !$result && $loop );

        wp_cache_set($query_key, $result);
        return $result;
    }
}

/**
 * Display previous post link that is adjacent to the current post.
 *
 * Based on previous_post_link() from wp-includes/link-template.php
 *
 * @param array|string $args Optional. Override default arguments.
 * @return bool True if previous post link is found, otherwise false.
 */
if (!function_exists('mondira_previous_post_link_plus')) {
    function mondira_previous_post_link_plus($args = '', $format = '') {
        if(empty($format))
        return mondira_adjacent_post_link_plus($args, '%link', true);
        else
        return mondira_adjacent_post_link_plus($args, $format, true);
    }
}

/**
 * Display next post link that is adjacent to the current post.
 *
 * Based on next_post_link() from wp-includes/link-template.php
 *
 * @param array|string $args Optional. Override default arguments.
 * @return bool True if next post link is found, otherwise false.
 */
if (!function_exists('mondira_next_post_link_plus')) {
    function mondira_next_post_link_plus($args = '', $format = '') {
        if(empty($format))
        return mondira_adjacent_post_link_plus($args, '%link', false);
        else
        return mondira_adjacent_post_link_plus($args, $format, false);
    }
}

/**
 * Return jQuery Easing Effects lists in array
 *
 * 
 *
 * 
 * @return array
 */
if (!function_exists('mondira_jquery_ui_easing_effects_list')) {
    function mondira_jquery_ui_easing_effects_list(){
        $easing_effect_list = array(
            'linear'=>"linear", 
            'swing'=>"swing", 
            'easeInQuad'=>"easeInQuad", 
            'easeOutQuad'=>"easeOutQuad", 
            'easeInOutQuad'=>"easeInOutQuad", 
            'easeInCubic'=>"easeInCubic", 
            'easeOutCubic'=>"easeOutCubic", 
            'easeInOutCubic'=>"easeInOutCubic", 
            'easeInQuart'=>"easeInQuart", 
            'easeOutQuart'=>"easeOutQuart", 
            'easeInOutQuart'=>"easeInOutQuart", 
            'easeInQuint'=>"easeInQuint", 
            'easeOutQuint'=>"easeOutQuint", 
            'easeInOutQuint'=>"easeInOutQuint", 
            'easeInExpo'=>"easeInExpo", 
            'easeOutExpo'=>"easeOutExpo", 
            'easeInOutExpo'=>"easeInOutExpo", 
            'easeInSine'=>"easeInSine", 
            'easeOutSine'=>"easeOutSine", 
            'easeInOutSine'=>"easeInOutSine", 
            'easeInCirc'=>"easeInCirc", 
            'easeOutCirc'=>"easeOutCirc", 
            'easeInOutCirc'=>"easeInOutCirc", 
            'easeInElastic'=>"easeInElastic", 
            'easeOutElastic'=>"easeOutElastic", 
            'easeInOutElastic'=>"easeInOutElastic", 
            'easeInBack'=>"easeInBack", 
            'easeOutBack'=>"easeOutBack", 
            'easeInOutBack'=>"easeInOutBack", 
            'easeInBounce'=>"easeInBounce", 
            'easeOutBounce'=>"easeOutBounce", 
            'easeInOutBounce'=>"easeInOutBounce"
        );
        return $easing_effect_list;
     }
}

/**
 * Display adjacent post link.
 *
 * Can be either next post link or previous.
 *
 * Based on adjacent_post_link() from wp-includes/link-template.php
 *
 * @param array|string $args Optional. Override default arguments.
 * @param bool $previous Optional, default is true. Whether display link to previous post.
 * @return bool True if next/previous post is found, otherwise false.
 */
if (!function_exists('mondira_adjacent_post_link_plus')) {
    function mondira_adjacent_post_link_plus($args = '', $format = '%link &raquo;', $previous = true) {
        $defaults = array(
            'order_by' => 'post_date', 'order_2nd' => 'post_date', 'meta_key' => '', 'post_type' => '',
            'loop' => false, 'end_post' => false, 'thumb' => false, 'max_length' => 0,
            'format' => '', 'link' => '%title', 'date_format' => '', 'tooltip' => '%title',
            'in_same_cat' => false, 'in_same_tax' => false, 'in_same_format' => false,
            'in_same_author' => false, 'in_same_meta' => false,
            'ex_cats' => '', 'ex_cats_method' => 'weak', 'in_cats' => '', 'ex_posts' => '', 'in_posts' => '',
            'before' => '', 'after' => '', 'num_results' => 1, 'return' => false, 'echo' => true
        );

    //    If Post Types Order plugin is installed, default to sorting on menu_order
        if ( function_exists('CPTOrderPosts') )
            $defaults['order_by'] = 'menu_order';
        
        $r = wp_parse_args( $args, $defaults );
        if ( empty($r['format']) )
            $r['format'] = $format;
        if ( empty($r['date_format']) )
            $r['date_format'] = get_option('date_format');
        if ( !function_exists('get_post_format') )
            $r['in_same_format'] = false;

        if ( $previous && is_attachment() ) {
            $posts = array();
            $posts[] = & get_post($GLOBALS['post']->post_parent);
        } else
            $posts = mondira_get_adjacent_post_plus($r, $previous);

    //    If there is no next/previous post, return false so themes may conditionally display inactive link text.
        if ( !$posts )
            return false;

    //    If sorting by date, display posts in reverse chronological order. Otherwise display in alpha/numeric order.
        if ( ($previous && $r['order_by'] != 'post_date') || (!$previous && $r['order_by'] == 'post_date') )
            $posts = array_reverse( $posts, true );
            
    //    Option to return something other than the formatted link        
        if ( $r['return'] ) {
            if ( $r['num_results'] == 1 ) {
                reset($posts);
                $post = current($posts);
                if ( $r['return'] === 'id')
                    return $post->ID;
                if ( $r['return'] === 'href')
                    return get_permalink($post);
                if ( $r['return'] === 'object')
                    return $post;
                if ( $r['return'] === 'title')
                    return $post->post_title;
                if ( $r['return'] === 'date')
                    return mysql2date($r['date_format'], $post->post_date);
            } elseif ( $r['return'] === 'object')
                return $posts;
        }

        $output = $r['before'];

    //    When num_results > 1, multiple adjacent posts may be returned. Use foreach to display each adjacent post.
        foreach ( $posts as $post ) {
            $title = $post->post_title;
            if ( empty($post->post_title) )
                $title = $previous ? __('Previous Post') : __('Next Post');

            $title = apply_filters('the_title', $title, $post->ID);
            $date = mysql2date($r['date_format'], $post->post_date);
            $author = get_the_author_meta('display_name', $post->post_author);
        
    //        Set anchor title attribute to long post title or custom tooltip text. Supports variable replacement in custom tooltip.
            if ( $r['tooltip'] ) {
                $tooltip = str_replace('%title', $title, $r['tooltip']);
                $tooltip = str_replace('%date', $date, $tooltip);
                $tooltip = str_replace('%author', $author, $tooltip);
                $tooltip = ' title="' . esc_attr($tooltip) . '"';
            } else
                $tooltip = '';

    //        Truncate the link title to nearest whole word under the length specified.
            $max_length = intval($r['max_length']) < 1 ? 9999 : intval($r['max_length']);
            if ( strlen($title) > $max_length )
                $title = substr( $title, 0, strrpos(substr($title, 0, $max_length), ' ') ) . '...';
        
            $rel = $previous ? 'prev' : 'next';

            $anchor = '<a href="'.get_permalink($post).'" rel="'.$rel.'"'.$tooltip.'>';
            $link = str_replace('%title', $title, $r['link']);
            $link = str_replace('%date', $date, $link);
            $link = $anchor . $link . '</a>';
        
            $format = str_replace('%link', $link, $r['format']);
            $format = str_replace('%title', $title, $format);
            $format = str_replace('%date', $date, $format);
            $format = str_replace('%author', $author, $format);
            if ( ($r['order_by'] == 'custom' || $r['order_by'] == 'numeric') && !empty($r['meta_key']) ) {
                $meta = get_post_meta($post->ID, $r['meta_key'], true);
                $format = str_replace('%meta', $meta, $format);
            } elseif ( $r['in_same_meta'] ) {
                $meta = get_post_meta($post->ID, $r['in_same_meta'], true);
                $format = str_replace('%meta', $meta, $format);
            }

    //        Get the category list, including custom taxonomies (only if the %category variable has been used).
            if ( (strpos($format, '%category') !== false) && version_compare(PHP_VERSION, '5.0.0', '>=') ) {
                $term_list = '';
                $taxonomies = array_filter( get_post_taxonomies($post->ID), "is_taxonomy_hierarchical" );
                if ( $r['in_same_format'] && get_post_format($post->ID) )
                    $taxonomies[] = 'post_format';
                foreach ( $taxonomies as &$taxonomy ) {
    //                No, this is not a mistake. Yes, we are testing the result of the assignment ( = ).
    //                We are doing it this way to stop it from appending a comma when there is no next term.
                    if ( $next_term = get_the_term_list($post->ID, $taxonomy, '', ', ', '') ) {
                        $term_list .= $next_term;
                        if ( current($taxonomies) ) $term_list .= ', ';
                    }
                }
                $format = str_replace('%category', $term_list, $format);
            }

    //        Optionally add the post thumbnail to the link. Wrap the link in a span to aid CSS styling.
            if ( $r['thumb'] && has_post_thumbnail($post->ID) ) {
                if ( $r['thumb'] === true ) // use 'post-thumbnail' as the default size
                    $r['thumb'] = 'post-thumbnail';
                $thumbnail = '<a class="post-thumbnail" href="'.get_permalink($post).'" rel="'.$rel.'"'.$tooltip.'>' . get_the_post_thumbnail( $post->ID, $r['thumb'] ) . '</a>';
                $format = $thumbnail . '<span class="post-link">' . $format . '</span>';
            }

    //        If more than one link is returned, wrap them in <li> tags        
            if ( intval($r['num_results']) > 1 )
                $format = '<li>' . $format . '</li>';
            
            $output .= $format;
        }

        $output .= $r['after'];

        //    If echo is false, don't display anything. Return the link as a PHP string.
        if ( !$r['echo'] || $r['return'] === 'output' )
            return $output;

        $adjacent = $previous ? 'previous' : 'next';
        echo apply_filters( "{$adjacent}_post_link_plus", $output, $r );

        return true;
    }
}

/**
 * Retrieve the classes for the body element as an array if wp return empty classes.
 *
 * @since 1.0.0
 *
 * @param string|array $class One or more classes to add to the class list.
 * @return array Array of classes.
 */
if ( !function_exists( 'mondira_get_custom_body_classes' ) ) {
	function mondira_get_custom_body_classes() {
		global $wp_query, $wpdb;

		$classes = array();

		if ( is_rtl() )
			$classes[] = 'rtl';

		if ( is_front_page() )
			$classes[] = 'home';
		if ( is_home() )
			$classes[] = 'blog';
		if ( is_archive() )
			$classes[] = 'archive';
		if ( is_date() )
			$classes[] = 'date';
		if ( is_search() ) {
			$classes[] = 'search';
			$classes[] = $wp_query->posts ? 'search-results' : 'search-no-results';
		}
		if ( is_paged() )
			$classes[] = 'paged';
		if ( is_attachment() )
			$classes[] = 'attachment';
		if ( is_404() )
			$classes[] = 'error404';

		if ( is_single() ) {
			$post_id = $wp_query->get_queried_object_id();
			$post = $wp_query->get_queried_object();

			$classes[] = 'single';
			if ( isset( $post->post_type ) ) {
				$classes[] = 'single-' . sanitize_html_class($post->post_type, $post_id);
				$classes[] = 'postid-' . $post_id;

				// Post Format
				if ( post_type_supports( $post->post_type, 'post-formats' ) ) {
					$post_format = get_post_format( $post->ID );

					if ( $post_format && !is_wp_error($post_format) )
						$classes[] = 'single-format-' . sanitize_html_class( $post_format );
					else
						$classes[] = 'single-format-standard';
				}
			}

			if ( is_attachment() ) {
				$mime_type = get_post_mime_type($post_id);
				$mime_prefix = array( 'application/', 'image/', 'text/', 'audio/', 'video/', 'music/' );
				$classes[] = 'attachmentid-' . $post_id;
				$classes[] = 'attachment-' . str_replace( $mime_prefix, '', $mime_type );
			}
		} elseif ( is_archive() ) {
			if ( is_post_type_archive() ) {
				$classes[] = 'post-type-archive';
				$post_type = get_query_var( 'post_type' );
				if ( is_array( $post_type ) )
					$post_type = reset( $post_type );
				$classes[] = 'post-type-archive-' . sanitize_html_class( $post_type );
			} else if ( is_author() ) {
				$author = $wp_query->get_queried_object();
				$classes[] = 'author';
				if ( isset( $author->user_nicename ) ) {
					$classes[] = 'author-' . sanitize_html_class( $author->user_nicename, $author->ID );
					$classes[] = 'author-' . $author->ID;
				}
			} elseif ( is_category() ) {
				$cat = $wp_query->get_queried_object();
				$classes[] = 'category';
				if ( isset( $cat->term_id ) ) {
					$classes[] = 'category-' . sanitize_html_class( $cat->slug, $cat->term_id );
					$classes[] = 'category-' . $cat->term_id;
				}
			} elseif ( is_tag() ) {
				$tags = $wp_query->get_queried_object();
				$classes[] = 'tag';
				if ( isset( $tags->term_id ) ) {
					$classes[] = 'tag-' . sanitize_html_class( $tags->slug, $tags->term_id );
					$classes[] = 'tag-' . $tags->term_id;
				}
			} elseif ( is_tax() ) {
				$term = $wp_query->get_queried_object();
				if ( isset( $term->term_id ) ) {
					$classes[] = 'tax-' . sanitize_html_class( $term->taxonomy );
					$classes[] = 'term-' . sanitize_html_class( $term->slug, $term->term_id );
					$classes[] = 'term-' . $term->term_id;
				}
			}
		} elseif ( is_page() ) {
			$classes[] = 'page';

			$page_id = $wp_query->get_queried_object_id();

			$post = get_post($page_id);

			$classes[] = 'page-id-' . $page_id;

			if ( get_pages( array( 'parent' => $page_id, 'number' => 1 ) ) ) {
				$classes[] = 'page-parent';
			}

			if ( $post->post_parent ) {
				$classes[] = 'page-child';
				$classes[] = 'parent-pageid-' . $post->post_parent;
			}
			if ( is_page_template() ) {
				$classes[] = 'page-template';
				$classes[] = 'page-template-' . sanitize_html_class( str_replace( '.', '-', get_page_template_slug( $page_id ) ) );
			} else {
				$classes[] = 'page-template-default';
			}
		}

		if ( is_user_logged_in() )
			$classes[] = 'logged-in';

		if ( is_admin_bar_showing() ) {
			$classes[] = 'admin-bar';
			$classes[] = 'no-customize-support';
		}

		if ( get_theme_mod( 'background_color' ) || get_background_image() )
			$classes[] = 'custom-background';

		$page = $wp_query->get( 'page' );

		if ( ! $page || $page < 2 )
			$page = $wp_query->get( 'paged' );

		if ( $page && $page > 1 && ! is_404() ) {
			$classes[] = 'paged-' . $page;

			if ( is_single() )
				$classes[] = 'single-paged-' . $page;
			elseif ( is_page() )
				$classes[] = 'page-paged-' . $page;
			elseif ( is_category() )
				$classes[] = 'category-paged-' . $page;
			elseif ( is_tag() )
				$classes[] = 'tag-paged-' . $page;
			elseif ( is_date() )
				$classes[] = 'date-paged-' . $page;
			elseif ( is_author() )
				$classes[] = 'author-paged-' . $page;
			elseif ( is_search() )
				$classes[] = 'search-paged-' . $page;
			elseif ( is_post_type_archive() )
				$classes[] = 'post-type-paged-' . $page;
		}

		if ( ! empty( $class ) ) {
			if ( !is_array( $class ) )
				$class = preg_split( '#\s+#', $class );
			$classes = array_merge( $classes, $class );
		} else {
			// Ensure that we always coerce class to being an array.
			$class = array();
		}

		$classes = array_map( 'esc_attr', $classes );

		return array_unique( $classes );
	}
}