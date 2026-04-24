<?php
namespace DirectoryCore\Content;

use DirectoryCore\Contracts\Service;
use DirectoryCore\Infrastructure\PluginContext;
use DirectoryCore\Integration\CoreApi;

class VenuePostTypeRegistrar implements Service {
	private PluginContext $context;

	public function __construct( PluginContext $context ) {
		$this->context = $context;
	}

	public function register(): void {
		add_action( 'init', array( $this, 'registerPostType' ) );
	}

	public function registerPostType(): void {
		$text_domain = $this->context->textDomain();

		register_post_type(
			'mw_venue',
			array(
				'labels' => array(
					'name'               => __( 'Venues', $text_domain ),
					'singular_name'      => __( 'Venue', $text_domain ),
					'menu_name'          => __( 'Venues', $text_domain ),
					'name_admin_bar'     => __( 'Venue', $text_domain ),
					'add_new'            => __( 'Add New', $text_domain ),
					'add_new_item'       => __( 'Add New Venue', $text_domain ),
					'new_item'           => __( 'New Venue', $text_domain ),
					'edit_item'          => __( 'Edit Venue', $text_domain ),
					'view_item'          => __( 'View Venue', $text_domain ),
					'all_items'          => __( 'All Venues', $text_domain ),
					'search_items'       => __( 'Search Venues', $text_domain ),
					'not_found'          => __( 'No venues found.', $text_domain ),
					'not_found_in_trash' => __( 'No venues found in Trash.', $text_domain ),
				),
				'public'             => false,
				'show_ui'            => true,
				'show_in_rest'       => true,
				'show_in_menu'       => CoreApi::adminMenuSlug(),
				'publicly_queryable' => false,
				'exclude_from_search'=> true,
				'rewrite'            => false,
				'supports'           => array( 'title', 'editor', 'thumbnail' ),
				'menu_icon'          => 'dashicons-store',
				'map_meta_cap'       => true,
			)
		);
	}
}
