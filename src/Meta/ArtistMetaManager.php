<?php
namespace DirectoryCore\Meta;

use DirectoryCore\Contracts\Service;
use DirectoryCore\Infrastructure\PluginContext;
use DirectoryCore\Integration\CoreApi;
use DirectoryCore\Relationship\RelationshipService;

class ArtistMetaManager implements Service {
	private PluginContext $context;

	public function __construct( PluginContext $context ) {
		$this->context = $context;
	}

	public function register(): void {
		add_action( 'init', array( $this, 'registerMetaFields' ) );
		add_action( 'add_meta_boxes', array( $this, 'addMetaBox' ) );
		add_action( 'save_post_mw_artist', array( $this, 'saveMetaFields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminAssets' ) );
		add_action( 'admin_footer', array( $this, 'renderMediaFrameScript' ) );
	}

	public function registerMetaFields(): void {
		register_post_meta(
			'mw_artist',
			'mw_visibility_state',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'default'       => CoreApi::defaultArtistVisibility(),
				'auth_callback' => array( $this, 'canEditPosts' ),
			)
		);

		register_post_meta(
			'mw_artist',
			'mw_owner_user_id',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'integer',
				'auth_callback' => array( $this, 'canEditPosts' ),
			)
		);

		register_post_meta(
			'mw_artist',
			'mw_related_venue_ids',
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

		register_post_meta(
			'mw_artist',
			'mw_profile_artwork_ids',
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
			'mw_artist_directory_settings',
			__( 'Artist Directory Settings', $this->context->textDomain() ),
			array( $this, 'renderMetaBox' ),
			'mw_artist',
			'normal',
			'default'
		);
	}

	public function renderMetaBox( $post ): void {
		$visibility         = CoreApi::getPostVisibility( $post->ID, CoreApi::defaultArtistVisibility() );
		$owner_user_id      = (int) get_post_meta( $post->ID, 'mw_owner_user_id', true );
		$related_venue_ids  = CoreApi::sanitizeIdList( get_post_meta( $post->ID, 'mw_related_venue_ids', true ) );
		$profile_artwork_ids = CoreApi::sanitizeIdList( get_post_meta( $post->ID, 'mw_profile_artwork_ids', true ) );
		$venue_options      = CoreApi::getVenueOptions();

		wp_nonce_field( 'mw_save_artist_meta', 'mw_artist_meta_nonce' );
		?>
		<p>
			<label for="mw_visibility_state"><strong><?php esc_html_e( 'Public visibility', $this->context->textDomain() ); ?></strong></label><br>
			<select name="mw_visibility_state" id="mw_visibility_state" style="min-width:260px;">
				<?php foreach ( CoreApi::visibilityOptions() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $visibility, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="mw_owner_user_id"><strong><?php esc_html_e( 'Owner account', $this->context->textDomain() ); ?></strong></label><br>
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
			<span class="description" style="display:block;margin-top:6px;"><?php esc_html_e( 'Reserved for future invite-only artist account management.', $this->context->textDomain() ); ?></span>
		</p>
		<p>
			<label for="mw_related_venue_ids"><strong><?php esc_html_e( 'Related venues', $this->context->textDomain() ); ?></strong></label><br>
			<select name="mw_related_venue_ids[]" id="mw_related_venue_ids" multiple size="8" style="width:100%;max-width:480px;">
				<?php foreach ( $venue_options as $venue_id => $venue_title ) : ?>
					<option value="<?php echo esc_attr( (string) $venue_id ); ?>" <?php selected( in_array( $venue_id, $related_venue_ids, true ), true ); ?>><?php echo esc_html( $venue_title ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<strong><?php esc_html_e( 'Featured artwork', $this->context->textDomain() ); ?></strong><br>
			<span class="description"><?php esc_html_e( 'Use the standard Featured Image panel for the lead artwork image.', $this->context->textDomain() ); ?></span>
		</p>
		<p>
			<label for="mw_profile_artwork_ids"><strong><?php esc_html_e( 'Profile artwork gallery', $this->context->textDomain() ); ?></strong></label><br>
			<input type="hidden" name="mw_profile_artwork_ids" id="mw_profile_artwork_ids" value="<?php echo esc_attr( implode( ',', $profile_artwork_ids ) ); ?>">
			<button type="button" class="button mw-media-select" data-target="#mw_profile_artwork_ids" data-preview="#mw-profile-artwork-preview" data-multiple="true"><?php esc_html_e( 'Select Gallery Images', $this->context->textDomain() ); ?></button>
			<div id="mw-profile-artwork-preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;">
				<?php foreach ( $profile_artwork_ids as $attachment_id ) : ?>
					<?php $thumb = wp_get_attachment_image_url( $attachment_id, 'thumbnail' ); ?>
					<?php if ( $thumb ) : ?>
						<img src="<?php echo esc_url( $thumb ); ?>" alt="" style="width:70px;height:70px;object-fit:cover;">
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</p>
		<?php
	}

	public function saveMetaFields( int $post_id ): void {
		if ( ! $this->canSaveMeta( $post_id ) ) {
			return;
		}

		$old_venue_ids = CoreApi::sanitizeIdList( get_post_meta( $post_id, 'mw_related_venue_ids', true ) );

		$visibility = isset( $_POST['mw_visibility_state'] ) ? sanitize_key( wp_unslash( $_POST['mw_visibility_state'] ) ) : CoreApi::defaultArtistVisibility();
		$visibility = CoreApi::normalizeVisibility( $visibility, CoreApi::defaultArtistVisibility() );
		update_post_meta( $post_id, 'mw_visibility_state', $visibility );
		update_post_meta( $post_id, 'mw_owner_user_id', isset( $_POST['mw_owner_user_id'] ) ? absint( wp_unslash( $_POST['mw_owner_user_id'] ) ) : 0 );

		$new_venue_ids = isset( $_POST['mw_related_venue_ids'] ) ? CoreApi::sanitizeIdList( wp_unslash( $_POST['mw_related_venue_ids'] ) ) : array();
		update_post_meta( $post_id, 'mw_related_venue_ids', $new_venue_ids );
		RelationshipService::syncArtistVenueRelationships( $post_id, $old_venue_ids, $new_venue_ids );

		$gallery_ids = array();
		if ( isset( $_POST['mw_profile_artwork_ids'] ) ) {
			$gallery_ids = array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['mw_profile_artwork_ids'] ) ) ) ) );
		}
		update_post_meta( $post_id, 'mw_profile_artwork_ids', array_values( $gallery_ids ) );
	}

	public function enqueueAdminAssets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'mw_artist' !== get_post_type() ) {
			return;
		}

		wp_enqueue_media();
	}

	public function renderMediaFrameScript(): void {
		$screen_id = '';
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen ) {
				$screen_id = (string) $screen->id;
			}
		}

		if ( ! in_array( $screen_id, array( 'mw_artist', 'mw_artist_page' ), true ) && 'mw_artist' !== get_post_type() ) {
			return;
		}
		?>
		<script>
			jQuery(function($) {
				$('.mw-media-select').on('click', function(e) {
					e.preventDefault();
					const button = $(this);
					const target = $(button.data('target'));
					const preview = $(button.data('preview'));
					const multiple = !!button.data('multiple');
					const frame = wp.media({
						title: <?php echo wp_json_encode( __( 'Select artwork images', $this->context->textDomain() ) ); ?>,
						button: { text: <?php echo wp_json_encode( __( 'Use selected images', $this->context->textDomain() ) ); ?> },
						multiple: multiple
					});
					frame.on('select', function() {
						const selection = frame.state().get('selection').toJSON();
						const ids = selection.map(function(item) { return item.id; });
						target.val(ids.join(','));
						preview.empty();
						selection.forEach(function(item) {
							const thumb = item.sizes && item.sizes.thumbnail ? item.sizes.thumbnail.url : item.url;
							preview.append('<img src="' + thumb + '" alt="" style="width:70px;height:70px;object-fit:cover;">');
						});
					});
					frame.open();
				});
			});
		</script>
		<?php
	}

	private function canSaveMeta( int $post_id ): bool {
		if ( ! isset( $_POST['mw_artist_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mw_artist_meta_nonce'] ) ), 'mw_save_artist_meta' ) ) {
			return false;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		return current_user_can( 'edit_post', $post_id );
	}
}
