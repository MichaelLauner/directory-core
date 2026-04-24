<?php
namespace DirectoryCore\Content;

use DirectoryCore\Contracts\Service;
use DirectoryCore\Infrastructure\PluginContext;

class MediaTaxonomy implements Service {
	private PluginContext $context;

	public function __construct( PluginContext $context ) {
		$this->context = $context;
	}

	public function register(): void {
		add_action( 'init', array( $this, 'registerTaxonomy' ) );
	}

	public function registerTaxonomy(): void {
		$text_domain = $this->context->textDomain();

		register_taxonomy(
			'mw_media',
			array( 'mw_artist' ),
			array(
				'labels' => array(
					'name'              => __( 'Media', $text_domain ),
					'singular_name'     => __( 'Medium', $text_domain ),
					'search_items'      => __( 'Search Media', $text_domain ),
					'all_items'         => __( 'All Media', $text_domain ),
					'parent_item'       => __( 'Parent Medium', $text_domain ),
					'parent_item_colon' => __( 'Parent Medium:', $text_domain ),
					'edit_item'         => __( 'Edit Medium', $text_domain ),
					'update_item'       => __( 'Update Medium', $text_domain ),
					'add_new_item'      => __( 'Add New Medium', $text_domain ),
					'new_item_name'     => __( 'New Medium Name', $text_domain ),
					'menu_name'         => __( 'Media', $text_domain ),
				),
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'artist-media' ),
			)
		);
	}
}
