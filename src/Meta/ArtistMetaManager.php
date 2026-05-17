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

		foreach ( array( 'mw_artist_first_name', 'mw_artist_last_name', 'mw_artist_studio_name' ) as $meta_key ) {
			register_post_meta(
				'mw_artist',
				$meta_key,
				array(
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => array( $this, 'canEditPosts' ),
				)
			);
		}

		foreach ( $this->textMetaFields() as $meta_key => $sanitize_callback ) {
			register_post_meta(
				'mw_artist',
				$meta_key,
				array(
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => 'string',
					'sanitize_callback' => $sanitize_callback,
					'auth_callback'     => array( $this, 'canEditPosts' ),
				)
			);
		}

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
		$visibility          = CoreApi::getPostVisibility( $post->ID, CoreApi::defaultArtistVisibility() );
		$first_name          = (string) get_post_meta( $post->ID, 'mw_artist_first_name', true );
		$last_name           = (string) get_post_meta( $post->ID, 'mw_artist_last_name', true );
		$studio_name         = (string) get_post_meta( $post->ID, 'mw_artist_studio_name', true );
		$owner_user_id       = (int) get_post_meta( $post->ID, 'mw_owner_user_id', true );
		$related_venue_ids   = CoreApi::sanitizeIdList( get_post_meta( $post->ID, 'mw_related_venue_ids', true ) );
		$profile_artwork_ids = CoreApi::sanitizeIdList( get_post_meta( $post->ID, 'mw_profile_artwork_ids', true ) );
		$contact_preference  = (string) get_post_meta( $post->ID, 'mw_artist_contact_preference', true );
		$public_email        = (string) get_post_meta( $post->ID, 'mw_artist_public_email', true );
		$website_url         = (string) get_post_meta( $post->ID, 'mw_artist_website_url', true );
		$instagram_url       = (string) get_post_meta( $post->ID, 'mw_artist_instagram_url', true );
		$primary_contact     = (string) get_post_meta( $post->ID, 'mw_artist_primary_contact_name', true );
		$accepting_inquiries = (string) get_post_meta( $post->ID, 'mw_artist_accepting_inquiries', true );
		$staff_notes         = (string) get_post_meta( $post->ID, 'mw_artist_staff_notes', true );
		$referral_notes      = (string) get_post_meta( $post->ID, 'mw_artist_referral_notes', true );
		$vetted              = (string) get_post_meta( $post->ID, 'mw_artist_vetted', true );
		$last_reviewed_date  = (string) get_post_meta( $post->ID, 'mw_artist_last_reviewed_date', true );
		$submission_source   = (string) get_post_meta( $post->ID, 'mw_artist_submission_source', true );
		$do_not_refer_reason = (string) get_post_meta( $post->ID, 'mw_artist_do_not_refer_reason', true );
		$venue_options       = CoreApi::getVenueOptions();

		wp_nonce_field( 'mw_save_artist_meta', 'mw_artist_meta_nonce' );
		?>
		<div class="mw-directory-admin">
			<section class="mw-directory-admin__section">
				<h3><?php esc_html_e( 'Identity', $this->context->textDomain() ); ?></h3>
				<div class="mw-directory-admin__grid">
					<label class="mw-directory-admin__field" for="mw_artist_first_name">
						<span><?php esc_html_e( 'First name', $this->context->textDomain() ); ?></span>
						<input type="text" name="mw_artist_first_name" id="mw_artist_first_name" value="<?php echo esc_attr( $first_name ); ?>">
					</label>
					<label class="mw-directory-admin__field" for="mw_artist_last_name">
						<span><?php esc_html_e( 'Last name', $this->context->textDomain() ); ?></span>
						<input type="text" name="mw_artist_last_name" id="mw_artist_last_name" value="<?php echo esc_attr( $last_name ); ?>">
					</label>
					<label class="mw-directory-admin__field mw-directory-admin__field--wide" for="mw_artist_studio_name">
						<span><?php esc_html_e( 'Artist/studio name', $this->context->textDomain() ); ?></span>
						<input type="text" name="mw_artist_studio_name" id="mw_artist_studio_name" value="<?php echo esc_attr( $studio_name ); ?>">
						<em><?php esc_html_e( 'Public display uses this first when present.', $this->context->textDomain() ); ?></em>
					</label>
				</div>
			</section>

			<section class="mw-directory-admin__section">
				<h3><?php esc_html_e( 'Visibility & Account', $this->context->textDomain() ); ?></h3>
				<div class="mw-directory-admin__grid">
					<label class="mw-directory-admin__field" for="mw_visibility_state">
						<span><?php esc_html_e( 'Public visibility', $this->context->textDomain() ); ?></span>
						<select name="mw_visibility_state" id="mw_visibility_state">
							<?php foreach ( CoreApi::visibilityOptions() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $visibility, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<div class="mw-directory-admin__field">
						<span><?php esc_html_e( 'Owner account', $this->context->textDomain() ); ?></span>
						<?php
						wp_dropdown_users(
							array(
								'name'              => 'mw_owner_user_id',
								'selected'          => $owner_user_id,
								'show_option_none'  => __( 'No linked account', $this->context->textDomain() ),
								'option_none_value' => 0,
							)
						);
						?>
						<em><?php esc_html_e( 'Reserved for future invite-only artist account management.', $this->context->textDomain() ); ?></em>
					</div>
				</div>
			</section>

			<section class="mw-directory-admin__section">
				<h3><?php esc_html_e( 'Public Contact & Links', $this->context->textDomain() ); ?></h3>
				<div class="mw-directory-admin__grid">
					<label class="mw-directory-admin__field" for="mw_artist_contact_preference">
						<span><?php esc_html_e( 'Contact preference', $this->context->textDomain() ); ?></span>
						<select name="mw_artist_contact_preference" id="mw_artist_contact_preference">
							<?php foreach ( $this->contactPreferenceOptions() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $contact_preference, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="mw-directory-admin__field" for="mw_artist_accepting_inquiries">
						<span><?php esc_html_e( 'Accepting inquiries', $this->context->textDomain() ); ?></span>
						<select name="mw_artist_accepting_inquiries" id="mw_artist_accepting_inquiries">
							<?php foreach ( $this->acceptingInquiryOptions() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $accepting_inquiries, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="mw-directory-admin__field" for="mw_artist_public_email">
						<span><?php esc_html_e( 'Public or inquiry email', $this->context->textDomain() ); ?></span>
						<input type="email" name="mw_artist_public_email" id="mw_artist_public_email" value="<?php echo esc_attr( $public_email ); ?>">
					</label>
					<label class="mw-directory-admin__field" for="mw_artist_primary_contact_name">
						<span><?php esc_html_e( 'Primary contact person', $this->context->textDomain() ); ?></span>
						<input type="text" name="mw_artist_primary_contact_name" id="mw_artist_primary_contact_name" value="<?php echo esc_attr( $primary_contact ); ?>">
					</label>
					<label class="mw-directory-admin__field" for="mw_artist_website_url">
						<span><?php esc_html_e( 'Website URL', $this->context->textDomain() ); ?></span>
						<input type="url" name="mw_artist_website_url" id="mw_artist_website_url" value="<?php echo esc_attr( $website_url ); ?>">
					</label>
					<label class="mw-directory-admin__field" for="mw_artist_instagram_url">
						<span><?php esc_html_e( 'Instagram or social URL', $this->context->textDomain() ); ?></span>
						<input type="url" name="mw_artist_instagram_url" id="mw_artist_instagram_url" value="<?php echo esc_attr( $instagram_url ); ?>">
					</label>
				</div>
			</section>

			<section class="mw-directory-admin__section">
				<h3><?php esc_html_e( 'Staff Referral Notes', $this->context->textDomain() ); ?></h3>
				<div class="mw-directory-admin__grid">
					<label class="mw-directory-admin__field" for="mw_artist_vetted">
						<span><?php esc_html_e( 'Vetted / recommended by staff', $this->context->textDomain() ); ?></span>
						<select name="mw_artist_vetted" id="mw_artist_vetted">
							<option value="" <?php selected( $vetted, '' ); ?>><?php esc_html_e( 'Not reviewed', $this->context->textDomain() ); ?></option>
							<option value="yes" <?php selected( $vetted, 'yes' ); ?>><?php esc_html_e( 'Yes', $this->context->textDomain() ); ?></option>
							<option value="no" <?php selected( $vetted, 'no' ); ?>><?php esc_html_e( 'No', $this->context->textDomain() ); ?></option>
						</select>
					</label>
					<label class="mw-directory-admin__field" for="mw_artist_last_reviewed_date">
						<span><?php esc_html_e( 'Last reviewed date', $this->context->textDomain() ); ?></span>
						<input type="date" name="mw_artist_last_reviewed_date" id="mw_artist_last_reviewed_date" value="<?php echo esc_attr( $last_reviewed_date ); ?>">
					</label>
					<label class="mw-directory-admin__field" for="mw_artist_submission_source">
						<span><?php esc_html_e( 'Submission source', $this->context->textDomain() ); ?></span>
						<select name="mw_artist_submission_source" id="mw_artist_submission_source">
							<?php foreach ( $this->submissionSourceOptions() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $submission_source, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="mw-directory-admin__field mw-directory-admin__field--wide" for="mw_artist_referral_notes">
						<span><?php esc_html_e( 'Referral notes', $this->context->textDomain() ); ?></span>
						<textarea name="mw_artist_referral_notes" id="mw_artist_referral_notes" rows="4"><?php echo esc_textarea( $referral_notes ); ?></textarea>
						<em><?php esc_html_e( 'Private notes for staff referrals, such as mural experience or teaching strengths.', $this->context->textDomain() ); ?></em>
					</label>
					<label class="mw-directory-admin__field mw-directory-admin__field--wide" for="mw_artist_staff_notes">
						<span><?php esc_html_e( 'Internal staff notes', $this->context->textDomain() ); ?></span>
						<textarea name="mw_artist_staff_notes" id="mw_artist_staff_notes" rows="4"><?php echo esc_textarea( $staff_notes ); ?></textarea>
					</label>
					<label class="mw-directory-admin__field mw-directory-admin__field--wide" for="mw_artist_do_not_refer_reason">
						<span><?php esc_html_e( 'Do not refer reason', $this->context->textDomain() ); ?></span>
						<textarea name="mw_artist_do_not_refer_reason" id="mw_artist_do_not_refer_reason" rows="3"><?php echo esc_textarea( $do_not_refer_reason ); ?></textarea>
					</label>
				</div>
			</section>

			<section class="mw-directory-admin__section">
				<h3><?php esc_html_e( 'Relationships', $this->context->textDomain() ); ?></h3>
				<label class="mw-directory-admin__field" for="mw_related_venue_ids">
					<span><?php esc_html_e( 'Related venues', $this->context->textDomain() ); ?></span>
					<select name="mw_related_venue_ids[]" id="mw_related_venue_ids" multiple size="8">
						<?php foreach ( $venue_options as $venue_id => $venue_title ) : ?>
							<option value="<?php echo esc_attr( (string) $venue_id ); ?>" <?php selected( in_array( $venue_id, $related_venue_ids, true ), true ); ?>><?php echo esc_html( $venue_title ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</section>

			<section class="mw-directory-admin__section">
				<h3><?php esc_html_e( 'Artwork', $this->context->textDomain() ); ?></h3>
				<div class="mw-directory-admin__note">
					<strong><?php esc_html_e( 'Featured artwork', $this->context->textDomain() ); ?></strong>
					<span><?php esc_html_e( 'Use the standard Featured Image panel for the lead card artwork.', $this->context->textDomain() ); ?></span>
				</div>
				<label class="mw-directory-admin__field" for="mw_profile_artwork_ids">
					<span><?php esc_html_e( 'Profile artwork gallery', $this->context->textDomain() ); ?></span>
					<input type="hidden" name="mw_profile_artwork_ids" id="mw_profile_artwork_ids" value="<?php echo esc_attr( implode( ',', $profile_artwork_ids ) ); ?>">
				</label>
				<div class="mw-directory-admin__actions">
					<button type="button" class="button button-primary mw-media-select" data-target="#mw_profile_artwork_ids" data-preview="#mw-profile-artwork-preview" data-count="#mw-profile-artwork-count" data-multiple="true"><?php esc_html_e( 'Select Gallery Images', $this->context->textDomain() ); ?></button>
					<span id="mw-profile-artwork-count" class="mw-directory-admin__count">
						<?php
						printf(
							esc_html( _n( '%d image selected', '%d images selected', count( $profile_artwork_ids ), $this->context->textDomain() ) ),
							count( $profile_artwork_ids )
						);
						?>
					</span>
				</div>
				<div id="mw-profile-artwork-preview" class="mw-directory-admin__preview">
					<?php foreach ( $profile_artwork_ids as $attachment_id ) : ?>
						<?php $thumb = wp_get_attachment_image_url( $attachment_id, 'thumbnail' ); ?>
						<?php if ( $thumb ) : ?>
							<img src="<?php echo esc_url( $thumb ); ?>" alt="">
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</section>
		</div>
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
		update_post_meta( $post_id, 'mw_artist_first_name', isset( $_POST['mw_artist_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['mw_artist_first_name'] ) ) : '' );
		update_post_meta( $post_id, 'mw_artist_last_name', isset( $_POST['mw_artist_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['mw_artist_last_name'] ) ) : '' );
		update_post_meta( $post_id, 'mw_artist_studio_name', isset( $_POST['mw_artist_studio_name'] ) ? sanitize_text_field( wp_unslash( $_POST['mw_artist_studio_name'] ) ) : '' );
		update_post_meta( $post_id, 'mw_owner_user_id', isset( $_POST['mw_owner_user_id'] ) ? absint( wp_unslash( $_POST['mw_owner_user_id'] ) ) : 0 );
		update_post_meta( $post_id, 'mw_artist_contact_preference', $this->sanitizeOptionFromPost( 'mw_artist_contact_preference', array_keys( $this->contactPreferenceOptions() ) ) );
		update_post_meta( $post_id, 'mw_artist_accepting_inquiries', $this->sanitizeOptionFromPost( 'mw_artist_accepting_inquiries', array_keys( $this->acceptingInquiryOptions() ) ) );
		update_post_meta( $post_id, 'mw_artist_public_email', isset( $_POST['mw_artist_public_email'] ) ? sanitize_email( wp_unslash( $_POST['mw_artist_public_email'] ) ) : '' );
		update_post_meta( $post_id, 'mw_artist_website_url', isset( $_POST['mw_artist_website_url'] ) ? esc_url_raw( wp_unslash( $_POST['mw_artist_website_url'] ) ) : '' );
		update_post_meta( $post_id, 'mw_artist_instagram_url', isset( $_POST['mw_artist_instagram_url'] ) ? esc_url_raw( wp_unslash( $_POST['mw_artist_instagram_url'] ) ) : '' );
		update_post_meta( $post_id, 'mw_artist_primary_contact_name', isset( $_POST['mw_artist_primary_contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['mw_artist_primary_contact_name'] ) ) : '' );
		update_post_meta( $post_id, 'mw_artist_vetted', $this->sanitizeOptionFromPost( 'mw_artist_vetted', array( '', 'yes', 'no' ) ) );
		update_post_meta( $post_id, 'mw_artist_last_reviewed_date', isset( $_POST['mw_artist_last_reviewed_date'] ) ? $this->sanitizeDate( wp_unslash( $_POST['mw_artist_last_reviewed_date'] ) ) : '' );
		update_post_meta( $post_id, 'mw_artist_submission_source', $this->sanitizeOptionFromPost( 'mw_artist_submission_source', array_keys( $this->submissionSourceOptions() ) ) );
		update_post_meta( $post_id, 'mw_artist_referral_notes', isset( $_POST['mw_artist_referral_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mw_artist_referral_notes'] ) ) : '' );
		update_post_meta( $post_id, 'mw_artist_staff_notes', isset( $_POST['mw_artist_staff_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mw_artist_staff_notes'] ) ) : '' );
		update_post_meta( $post_id, 'mw_artist_do_not_refer_reason', isset( $_POST['mw_artist_do_not_refer_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mw_artist_do_not_refer_reason'] ) ) : '' );

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
		wp_enqueue_style(
			'directory-core-artist-admin',
			$this->context->assetUrl( 'assets/css/artist-admin.css' ),
			array(),
			$this->context->version()
		);
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
					const count = $(button.data('count'));
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
							preview.append('<img src="' + thumb + '" alt="">');
						});
						if (count.length) {
							count.text(ids.length === 1 ? '1 image selected' : ids.length + ' images selected');
						}
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

	private function textMetaFields(): array {
		return array(
			'mw_artist_contact_preference'    => 'sanitize_key',
			'mw_artist_public_email'          => 'sanitize_email',
			'mw_artist_website_url'           => 'esc_url_raw',
			'mw_artist_instagram_url'         => 'esc_url_raw',
			'mw_artist_primary_contact_name'  => 'sanitize_text_field',
			'mw_artist_accepting_inquiries'   => 'sanitize_key',
			'mw_artist_staff_notes'           => 'sanitize_textarea_field',
			'mw_artist_referral_notes'        => 'sanitize_textarea_field',
			'mw_artist_vetted'                => 'sanitize_key',
			'mw_artist_last_reviewed_date'    => array( $this, 'sanitizeDate' ),
			'mw_artist_submission_source'     => 'sanitize_key',
			'mw_artist_do_not_refer_reason'   => 'sanitize_textarea_field',
		);
	}

	private function contactPreferenceOptions(): array {
		return array(
			''             => __( 'Not specified', $this->context->textDomain() ),
			'direct_email' => __( 'Direct email', $this->context->textDomain() ),
			'form_relay'   => __( 'Contact form relay', $this->context->textDomain() ),
			'staff_only'   => __( 'Staff-mediated only', $this->context->textDomain() ),
			'none'         => __( 'No public contact', $this->context->textDomain() ),
		);
	}

	private function acceptingInquiryOptions(): array {
		return array(
			''      => __( 'Not specified', $this->context->textDomain() ),
			'yes'   => __( 'Yes', $this->context->textDomain() ),
			'maybe' => __( 'Maybe / ask first', $this->context->textDomain() ),
			'no'    => __( 'No', $this->context->textDomain() ),
		);
	}

	private function submissionSourceOptions(): array {
		return array(
			''              => __( 'Not specified', $this->context->textDomain() ),
			'staff_created' => __( 'Staff-created', $this->context->textDomain() ),
			'gravity_forms' => __( 'Gravity Forms', $this->context->textDomain() ),
			'imported'      => __( 'Imported', $this->context->textDomain() ),
			'artist_update' => __( 'Artist update', $this->context->textDomain() ),
		);
	}

	private function sanitizeOptionFromPost( string $key, array $allowed_values ): string {
		$value = isset( $_POST[ $key ] ) ? sanitize_key( wp_unslash( $_POST[ $key ] ) ) : '';

		return in_array( $value, $allowed_values, true ) ? $value : '';
	}

	public function sanitizeDate( $value ): string {
		$value = sanitize_text_field( (string) $value );

		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
	}
}
