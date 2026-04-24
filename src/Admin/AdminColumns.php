<?php
namespace DirectoryCore\Admin;

use DirectoryCore\Contracts\Service;
use DirectoryCore\Infrastructure\PluginContext;
use DirectoryCore\Integration\CoreApi;
use WP_Query;

class AdminColumns implements Service {
	private PluginContext $context;

	public function __construct( PluginContext $context ) {
		$this->context = $context;
	}

	public function register(): void {
		add_filter( 'manage_mw_artist_posts_columns', array( $this, 'artistColumns' ) );
		add_action( 'manage_mw_artist_posts_custom_column', array( $this, 'renderArtistColumn' ), 10, 2 );
		add_filter( 'manage_mw_venue_posts_columns', array( $this, 'venueColumns' ) );
		add_action( 'manage_mw_venue_posts_custom_column', array( $this, 'renderVenueColumn' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'renderFilters' ) );
		add_action( 'pre_get_posts', array( $this, 'applyAdminFilters' ) );
	}

	public function artistColumns( array $columns ): array {
		$columns['mw_visibility_state'] = __( 'Visibility', $this->context->textDomain() );
		$columns['mw_media']            = __( 'Media', $this->context->textDomain() );
		$columns['mw_related_venues']   = __( 'Venues', $this->context->textDomain() );

		return $columns;
	}

	public function renderArtistColumn( string $column, int $post_id ): void {
		if ( 'mw_visibility_state' === $column ) {
			$options = CoreApi::visibilityOptions();
			$key     = CoreApi::getPostVisibility( $post_id, CoreApi::defaultArtistVisibility() );
			echo esc_html( $options[ $key ] ?? $key );
			return;
		}

		if ( 'mw_media' === $column ) {
			$terms = get_the_terms( $post_id, 'mw_media' );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				echo '&mdash;';
				return;
			}

			echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
			return;
		}

		if ( 'mw_related_venues' === $column ) {
			$venue_ids = CoreApi::sanitizeIdList( get_post_meta( $post_id, 'mw_related_venue_ids', true ) );
			echo esc_html( (string) count( $venue_ids ) );
		}
	}

	public function venueColumns( array $columns ): array {
		$columns['mw_visibility_state'] = __( 'Visibility', $this->context->textDomain() );
		$columns['mw_related_artists']  = __( 'Artists', $this->context->textDomain() );

		return $columns;
	}

	public function renderVenueColumn( string $column, int $post_id ): void {
		if ( 'mw_visibility_state' === $column ) {
			$options = CoreApi::visibilityOptions();
			$key     = CoreApi::getPostVisibility( $post_id, CoreApi::defaultVenueVisibility() );
			echo esc_html( $options[ $key ] ?? $key );
			return;
		}

		if ( 'mw_related_artists' === $column ) {
			$artist_ids = CoreApi::sanitizeIdList( get_post_meta( $post_id, 'mw_related_artist_ids', true ) );
			echo esc_html( (string) count( $artist_ids ) );
		}
	}

	public function renderFilters(): void {
		global $typenow;

		if ( ! in_array( $typenow, array( 'mw_artist', 'mw_venue' ), true ) ) {
			return;
		}

		$current_visibility = isset( $_GET['mw_visibility_state'] ) ? sanitize_key( wp_unslash( $_GET['mw_visibility_state'] ) ) : '';
		?>
		<select name="mw_visibility_state">
			<option value=""><?php esc_html_e( 'All visibility states', $this->context->textDomain() ); ?></option>
			<?php foreach ( CoreApi::visibilityOptions() as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_visibility, $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php

		if ( 'mw_artist' === $typenow ) {
			wp_dropdown_categories(
				array(
					'show_option_all' => __( 'All media', $this->context->textDomain() ),
					'taxonomy'        => 'mw_media',
					'name'            => 'mw_media',
					'orderby'         => 'name',
					'selected'        => isset( $_GET['mw_media'] ) ? absint( wp_unslash( $_GET['mw_media'] ) ) : 0,
					'hierarchical'    => true,
					'hide_empty'      => false,
				)
			);
		}
	}

	public function applyAdminFilters( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$post_type = $query->get( 'post_type' );
		if ( ! in_array( $post_type, array( 'mw_artist', 'mw_venue' ), true ) ) {
			return;
		}

		$visibility = isset( $_GET['mw_visibility_state'] ) ? sanitize_key( wp_unslash( $_GET['mw_visibility_state'] ) ) : '';
		if ( $visibility ) {
			$meta_query = array(
				array(
					'key'   => 'mw_visibility_state',
					'value' => $visibility,
				),
			);

			if (
				( 'mw_artist' === $post_type && CoreApi::defaultArtistVisibility() === $visibility ) ||
				( 'mw_venue' === $post_type && CoreApi::defaultVenueVisibility() === $visibility )
			) {
				$meta_query = array(
					'relation' => 'OR',
					array(
						'key'     => 'mw_visibility_state',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'   => 'mw_visibility_state',
						'value' => $visibility,
					),
				);
			}

			$query->set( 'meta_query', $meta_query );
		}

		if ( 'mw_artist' === $post_type ) {
			$media_id = isset( $_GET['mw_media'] ) ? absint( wp_unslash( $_GET['mw_media'] ) ) : 0;
			if ( $media_id > 0 ) {
				$query->set(
					'tax_query',
					array(
						array(
							'taxonomy' => 'mw_media',
							'field'    => 'term_id',
							'terms'    => array( $media_id ),
						),
					)
				);
			}
		}
	}
}
