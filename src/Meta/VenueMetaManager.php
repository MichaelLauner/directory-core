<?php
namespace DirectoryCore\Meta;

use DirectoryCore\Contracts\Service;
use DirectoryCore\Infrastructure\PluginContext;
use DirectoryCore\Integration\CoreApi;
use DirectoryCore\Relationship\RelationshipService;

class VenueMetaManager implements Service {
	private PluginContext $context;

	public function __construct( PluginContext $context ) {
		$this->context = $context;
	}

	public function register(): void {
		add_action( 'init', array( $this, 'registerMetaFields' ) );
		add_action( 'add_meta_boxes', array( $this, 'addMetaBox' ) );
		add_action( 'save_post_mw_venue', array( $this, 'saveMetaFields' ) );
	}

	public function registerMetaFields(): void {
		register_post_meta(
			'mw_venue',
			'mw_visibility_state',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'default'       => CoreApi::defaultVenueVisibility(),
				'auth_callback' => array( $this, 'canEditPosts' ),
			)
		);

		register_post_meta(
			'mw_venue',
			'mw_owner_user_id',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'integer',
				'auth_callback' => array( $this, 'canEditPosts' ),
			)
		);

		register_post_meta(
			'mw_venue',
			'mw_related_artist_ids',
			array(
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				),
				'single'            => true,
				'type'              => 'array',
				'sanitize_callback' => array( CoreApi::class, 'sanitizeIdList' ),
				'auth_callback'     => array( $this, 'canEditPosts' ),
			)
		);
	}

	public function canEditPosts(): bool {
		return current_user_can( 'edit_posts' );
	}

	public function addMetaBox(): void {
		add_meta_box(
			'mw_venue_directory_settings',
			__( 'Venue Directory Settings', $this->context->textDomain() ),
			array( $this, 'renderMetaBox' ),
			'mw_venue',
			'normal',
			'default'
		);
	}

	public function renderMetaBox( $post ): void {
		$visibility        = CoreApi::getPostVisibility( $post->ID, CoreApi::defaultVenueVisibility() );
		$owner_user_id     = (int) get_post_meta( $post->ID, 'mw_owner_user_id', true );
		$related_artist_ids = CoreApi::sanitizeIdList( get_post_meta( $post->ID, 'mw_related_artist_ids', true ) );
		$artist_options    = CoreApi::getArtistOptions();

		wp_nonce_field( 'mw_save_venue_meta', 'mw_venue_meta_nonce' );
		?>
		<p>
			<label for="mw_venue_visibility_state"><strong><?php esc_html_e( 'Visibility state', $this->context->textDomain() ); ?></strong></label><br>
			<select name="mw_visibility_state" id="mw_venue_visibility_state" style="min-width:260px;">
				<?php foreach ( CoreApi::visibilityOptions() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $visibility, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="mw_venue_owner_user_id"><strong><?php esc_html_e( 'Owner account', $this->context->textDomain() ); ?></strong></label><br>
			<?php
			wp_dropdown_users(
				array(
					'name'             => 'mw_owner_user_id',
					'selected'         => $owner_user_id,
					'show_option_none' => __( 'No linked account', $this->context->textDomain() ),
					'option_none_value'=> 0,
				)
			);
			?>
		</p>
		<p>
			<label for="mw_related_artist_ids"><strong><?php esc_html_e( 'Related artists', $this->context->textDomain() ); ?></strong></label><br>
			<select name="mw_related_artist_ids[]" id="mw_related_artist_ids" multiple size="8" style="width:100%;max-width:480px;">
				<?php foreach ( $artist_options as $artist_id => $artist_title ) : ?>
					<option value="<?php echo esc_attr( (string) $artist_id ); ?>" <?php selected( in_array( $artist_id, $related_artist_ids, true ), true ); ?>><?php echo esc_html( $artist_title ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	public function saveMetaFields( int $post_id ): void {
		if ( ! $this->canSaveMeta( $post_id ) ) {
			return;
		}

		$old_artist_ids = CoreApi::sanitizeIdList( get_post_meta( $post_id, 'mw_related_artist_ids', true ) );

		$visibility = isset( $_POST['mw_visibility_state'] ) ? sanitize_key( wp_unslash( $_POST['mw_visibility_state'] ) ) : CoreApi::defaultVenueVisibility();
		$visibility = CoreApi::normalizeVisibility( $visibility, CoreApi::defaultVenueVisibility() );
		update_post_meta( $post_id, 'mw_visibility_state', $visibility );
		update_post_meta( $post_id, 'mw_owner_user_id', isset( $_POST['mw_owner_user_id'] ) ? absint( wp_unslash( $_POST['mw_owner_user_id'] ) ) : 0 );

		$new_artist_ids = isset( $_POST['mw_related_artist_ids'] ) ? CoreApi::sanitizeIdList( wp_unslash( $_POST['mw_related_artist_ids'] ) ) : array();
		update_post_meta( $post_id, 'mw_related_artist_ids', $new_artist_ids );
		RelationshipService::syncVenueArtistRelationships( $post_id, $old_artist_ids, $new_artist_ids );
	}

	private function canSaveMeta( int $post_id ): bool {
		if ( ! isset( $_POST['mw_venue_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mw_venue_meta_nonce'] ) ), 'mw_save_venue_meta' ) ) {
			return false;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		return current_user_can( 'edit_post', $post_id );
	}
}
