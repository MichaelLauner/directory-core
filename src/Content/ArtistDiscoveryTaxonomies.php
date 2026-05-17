<?php
namespace DirectoryCore\Content;

use DirectoryCore\Contracts\Service;
use DirectoryCore\Infrastructure\PluginContext;

class ArtistDiscoveryTaxonomies implements Service {
	private PluginContext $context;

	public function __construct( PluginContext $context ) {
		$this->context = $context;
	}

	public function register(): void {
		add_action( 'init', array( $this, 'registerTaxonomies' ) );
	}

	public function registerTaxonomies(): void {
		$text_domain = $this->context->textDomain();

		foreach ( $this->taxonomyDefinitions( $text_domain ) as $taxonomy => $definition ) {
			register_taxonomy(
				$taxonomy,
				array( 'mw_artist' ),
				array(
					'labels'            => $definition['labels'],
					'hierarchical'      => true,
					'show_ui'           => true,
					'show_admin_column' => true,
					'show_in_rest'      => true,
					'rewrite'           => array( 'slug' => $definition['rewrite_slug'] ),
				)
			);
		}
	}

	public function seedDefaultTerms(): void {
		foreach ( $this->defaultTerms() as $taxonomy => $terms ) {
			foreach ( $terms as $term ) {
				if ( term_exists( $term, $taxonomy ) ) {
					continue;
				}

				$result = wp_insert_term( $term, $taxonomy );
				if ( is_wp_error( $result ) ) {
					continue;
				}
			}
		}
	}

	private function taxonomyDefinitions( string $text_domain ): array {
		return array(
			'mw_artist_service'      => array(
				'rewrite_slug' => 'artist-services',
				'labels'       => $this->labels( __( 'Services & Opportunities', $text_domain ), __( 'Service / Opportunity', $text_domain ), $text_domain ),
			),
			'mw_artist_audience'     => array(
				'rewrite_slug' => 'artist-audiences',
				'labels'       => $this->labels( __( 'Audiences & Settings', $text_domain ), __( 'Audience / Setting', $text_domain ), $text_domain ),
			),
			'mw_artist_project_scale' => array(
				'rewrite_slug' => 'artist-project-scales',
				'labels'       => $this->labels( __( 'Project Scales', $text_domain ), __( 'Project Scale', $text_domain ), $text_domain ),
			),
			'mw_artist_availability' => array(
				'rewrite_slug' => 'artist-availability',
				'labels'       => $this->labels( __( 'Availability', $text_domain ), __( 'Availability', $text_domain ), $text_domain ),
			),
			'mw_artist_service_area' => array(
				'rewrite_slug' => 'artist-service-areas',
				'labels'       => $this->labels( __( 'Service Areas', $text_domain ), __( 'Service Area', $text_domain ), $text_domain ),
			),
		);
	}

	private function labels( string $plural, string $singular, string $text_domain ): array {
		return array(
			'name'              => $plural,
			'singular_name'     => $singular,
			'search_items'      => sprintf( __( 'Search %s', $text_domain ), $plural ),
			'all_items'         => sprintf( __( 'All %s', $text_domain ), $plural ),
			'parent_item'       => sprintf( __( 'Parent %s', $text_domain ), $singular ),
			'parent_item_colon' => sprintf( __( 'Parent %s:', $text_domain ), $singular ),
			'edit_item'         => sprintf( __( 'Edit %s', $text_domain ), $singular ),
			'update_item'       => sprintf( __( 'Update %s', $text_domain ), $singular ),
			'add_new_item'      => sprintf( __( 'Add New %s', $text_domain ), $singular ),
			'new_item_name'     => sprintf( __( 'New %s Name', $text_domain ), $singular ),
			'menu_name'         => $plural,
		);
	}

	private function defaultTerms(): array {
		return array(
			'mw_artist_service'      => array(
				'Murals',
				'Commissions',
				'Public Art',
				'Teaching Artist',
				'Workshops',
				'Installations',
				'Performances',
				'Speaking / Panels',
				'Consulting',
			),
			'mw_artist_audience'     => array(
				'Schools',
				'Families',
				'Adults',
				'Corporate',
				'Public Spaces',
				'Festivals',
				'Community Workshops',
			),
			'mw_artist_project_scale' => array(
				'Small Commissions',
				'Large Murals',
				'Public Installations',
				'Temporary Works',
				'Permanent Works',
			),
			'mw_artist_availability' => array(
				'Accepting Commissions',
				'Available for Teaching',
				'Available for Public Art',
				'Not Currently Accepting Inquiries',
			),
			'mw_artist_service_area' => array(
				'Cheyenne',
				'Laramie County',
				'Southeast Wyoming',
				'Statewide',
				'Regional / Travel Available',
			),
		);
	}
}
