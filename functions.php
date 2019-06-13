<?php
/**
 * Callback for WordPress 'posts_search' filter.
 * 
 * Extend only if current search is for 
 * 'Post' or 'somethingElse' custom post type
 * 
 * @author Jenish Shrestha
 * 
 * @param string $search The posts search string
 * @param WP_Query $query The current WP_Query object
 * @return string $search The posts search string
 */

function wp_search_extended( $search, $wp_query ){
	
	if( ( ('post' == $wp_query->get( 'post_type' ) || 'somethingElse' == $wp_query->get( 'post_type' ) ) && is_search() && is_main_query()) ){
		global $wpdb;
 
		if ( empty( $search ) )
			return $search; // skip processing - If no search term in query or suppress_filters is true
	
		$q = $wp_query->query_vars;    
		$n = ! empty( $q['exact'] ) ? '' : '%';
	
		$search = $searchand = '';
	
		foreach ( (array) $q['search_terms'] as $term ) {

			$term = $n . $wpdb->esc_like( $term ) . $n;

            /* change query as per plugin settings */
			$OR = '';

			$search .= "{$searchand} (";
			
			$search .= $wpdb->prepare("($wpdb->posts.post_title LIKE '%s')", $term);
			$OR = ' OR ';

			$tax_OR = '';
			$search .= $OR;
			$tax = 'post_tag';
			$search .= $wpdb->prepare("$tax_OR (wset.taxonomy = '%s' AND est.name LIKE '%s')", $tax, $term);

			$search .= ")";
			$searchand = ' AND ';
		}
	
		if ( ! empty( $search ) ) {
			$search = " AND ({$search}) ";
			if ( ! is_user_logged_in() )
				$search .= " AND ($wpdb->posts.post_password = '') ";
        }
        
		add_filter( 'posts_join', 'WPSE_join_taxonomies');
		add_filter('posts_distinct_request', 'WPSE_distinct');
		
		return $search;
	}
}

add_filter( 'posts_search', 'wp_search_extended', 10, 2 );

/**
 * Callback for WordPress 'posts_join' filter.
 * 
 * Join term tables to a WordPress search
 * 
 * 
 * @author Jenish Shrestha
 * 
 * @param string $join The current where clause JOIN string.
 * @param WP_Query $query The current WP_Query object.
 * @return string $join The current where clause JOIN string.
 */
function WPSE_join_taxonomies($join){
	global $wpdb;
	$join .= " INNER JOIN $wpdb->term_relationships wsetr ON ($wpdb->posts.ID = wsetr.object_id) ";
	$join .= " INNER JOIN $wpdb->term_taxonomy wset ON (wsetr.term_taxonomy_id = wset.term_taxonomy_id) ";
	$join .= " INNER JOIN $wpdb->terms est ON (wset.term_id = est.term_id) ";

	return $join;

}

/**
 * Request distinct results
 * 
 * @author Jenish Shrestha
 * 
 * @param string $distinct
 * @return string $distinct
 */
function WPSE_distinct($distinct) {
    $distinct = 'DISTINCT';
    return $distinct;
}