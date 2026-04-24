<?php
namespace DirectoryCore\Content;

use DirectoryCore\Contracts\Service;
use DirectoryCore\Infrastructure\PluginContext;
use DirectoryCore\Integration\CoreApi;

class ArtistPostTypeRegistrar implements Service {
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
			'mw_artist',
			array(
				'labels' => array(
					'name'               => __( 'Artists', $text_domain ),
					'singular_name'      => __( 'Artist', $text_domain ),
					'menu_name'          => __( 'Artists', $text_domain ),
					'name_admin_bar'     => __( 'Artist', $text_domain ),
					'add_new'            => __( 'Add New', $text_domain ),
					'add_new_item'       => __( 'Add New Artist', $text_domain ),
					'new_item'           => __( 'New Artist', $text_domain ),
					'edit_item'          => __( 'Edit Artist', $text_domain ),
					'view_item'          => __( 'View Artist', $text_domain ),
					'all_items'          => __( 'All Artists', $text_domain ),
					'search_items'       => __( 'Search Artists', $text_domain ),
					'not_found'          => __( 'No artists found.', $text_domain ),
					'not_found_in_trash' => __( 'No artists found in Trash.', $text_domain ),
				),
				'public'             => true,
				'has_archive'        => true,
				'rewrite'            => array(
					'slug'       => 'artists',
					'with_front' => false,
				),
				'show_in_rest'       => true,
				'show_in_menu'       => CoreApi::adminMenuSlug(),
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'menu_icon'          => 'dashicons-groups',
				'map_meta_cap'       => true,
			)
		);
	}
}
