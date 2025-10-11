<?php
/**
 * Gift Category Taxonomy
 *
 * Registers and manages the gift category taxonomy.
 *
 * @package BP_Gifts
 * @since   2.1.0
 * @author  SuitePlugins
 * @license GPL-2.0-or-later
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BP_Gifts_Taxonomy
 *
 * Handles gift category taxonomy registration and management.
 *
 * @since 2.1.0
 */
class BP_Gifts_Taxonomy {

	/**
	 * Taxonomy name.
	 *
	 * @since 2.1.0
	 * @var   string
	 */
	public $taxonomy = 'bp_gift_category';

	/**
	 * Post type for gifts.
	 *
	 * @since 2.1.0
	 * @var   string
	 */
	private $post_type;

	/**
	 * Constructor.
	 *
	 * @since 2.1.0
	 * @param string $post_type Post type for gifts.
	 */
	public function __construct( string $post_type = 'bp_gifts' ) {
		$this->post_type = $post_type;
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 2.1.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( 'created_' . $this->taxonomy, array( $this, 'clear_category_cache' ) );
		add_action( 'edited_' . $this->taxonomy, array( $this, 'clear_category_cache' ) );
		add_action( 'deleted_' . $this->taxonomy, array( $this, 'clear_category_cache' ) );
	}

	/**
	 * Register the gift category taxonomy.
	 *
	 * @since 2.1.0
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Gift Categories', 'taxonomy general name', 'bp-gifts' ),
			'singular_name'              => _x( 'Gift Category', 'taxonomy singular name', 'bp-gifts' ),
			'search_items'               => __( 'Search Gift Categories', 'bp-gifts' ),
			'popular_items'              => __( 'Popular Gift Categories', 'bp-gifts' ),
			'all_items'                  => __( 'All Gift Categories', 'bp-gifts' ),
			'parent_item'                => __( 'Parent Gift Category', 'bp-gifts' ),
			'parent_item_colon'          => __( 'Parent Gift Category:', 'bp-gifts' ),
			'edit_item'                  => __( 'Edit Gift Category', 'bp-gifts' ),
			'update_item'                => __( 'Update Gift Category', 'bp-gifts' ),
			'add_new_item'               => __( 'Add New Gift Category', 'bp-gifts' ),
			'new_item_name'              => __( 'New Gift Category Name', 'bp-gifts' ),
			'separate_items_with_commas' => __( 'Separate gift categories with commas', 'bp-gifts' ),
			'add_or_remove_items'        => __( 'Add or remove gift categories', 'bp-gifts' ),
			'choose_from_most_used'      => __( 'Choose from the most used gift categories', 'bp-gifts' ),
			'not_found'                  => __( 'No gift categories found.', 'bp-gifts' ),
			'menu_name'                  => __( 'Gift Categories', 'bp-gifts' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => false,
			'publicly_queryable' => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => false,
			'show_in_rest'      => false,
			'capabilities'      => array(
				'manage_terms' => 'manage_options',
				'edit_terms'   => 'manage_options',
				'delete_terms' => 'manage_options',
				'assign_terms' => 'edit_posts',
			),
		);

		register_taxonomy( $this->taxonomy, $this->post_type, $args );
	}

	/**
	 * Clear category cache when taxonomy terms are modified.
	 *
	 * @since 2.1.0
	 */
	public function clear_category_cache() {
		delete_transient( 'bp_gifts_categories' );
	}

	/**
	 * Get all gift categories.
	 *
	 * @since 2.1.0
	 * @param array $args Query arguments.
	 * @return array Array of categories.
	 */
	public function get_categories( array $args = array() ) {
		$defaults = array(
			'taxonomy'   => $this->taxonomy,
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );

		$cache_key = 'bp_gifts_categories_' . md5( serialize( $args ) );
		$categories = get_transient( $cache_key );

		if ( false === $categories ) {
			$terms = get_terms( $args );

			if ( is_wp_error( $terms ) ) {
				$categories = array();
			} else {
				$categories = array();
				foreach ( $terms as $term ) {
					$categories[] = array(
						'id'          => $term->term_id,
						'name'        => $term->name,
						'slug'        => $term->slug,
						'description' => $term->description,
						'count'       => $term->count,
						'parent'      => $term->parent,
					);
				}
			}

			set_transient( $cache_key, $categories, HOUR_IN_SECONDS );
		}

		return $categories;
	}

	/**
	 * Create default categories.
	 *
	 * @since 2.1.0
	 */
	public function create_default_categories() {
		$default_categories = array(
			array(
				'name'        => __( 'Holiday Gifts', 'bp-gifts' ),
				'slug'        => 'holiday-gifts',
				'description' => __( 'Gifts for holidays and special occasions', 'bp-gifts' ),
			),
			array(
				'name'        => __( 'Birthday Gifts', 'bp-gifts' ),
				'slug'        => 'birthday-gifts',
				'description' => __( 'Perfect gifts for birthdays', 'bp-gifts' ),
			),
			array(
				'name'        => __( 'Thank You Gifts', 'bp-gifts' ),
				'slug'        => 'thank-you-gifts',
				'description' => __( 'Express gratitude with these gifts', 'bp-gifts' ),
			),
			array(
				'name'        => __( 'Just Because', 'bp-gifts' ),
				'slug'        => 'just-because',
				'description' => __( 'Gifts for no special reason', 'bp-gifts' ),
			),
		);

		foreach ( $default_categories as $category ) {
			// Check if category already exists
			$existing = term_exists( $category['slug'], $this->taxonomy );
			
			if ( ! $existing ) {
				wp_insert_term(
					$category['name'],
					$this->taxonomy,
					array(
						'slug'        => $category['slug'],
						'description' => $category['description'],
					)
				);
			}
		}
	}
}