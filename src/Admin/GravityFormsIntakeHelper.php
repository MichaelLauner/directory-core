<?php
namespace DirectoryCore\Admin;

use DirectoryCore\Contracts\Service;
use DirectoryCore\Infrastructure\PluginContext;

class GravityFormsIntakeHelper implements Service {
	private const DOWNLOAD_ACTION = 'mw_directory_download_artist_intake_form';
	private const TEMPLATE_FILE = DIRECTORY_CORE_PLUGIN_DIR . 'assets/forms/artist-directory-intake-starter.json';

	private PluginContext $context;

	public function __construct( PluginContext $context ) {
		$this->context = $context;
	}

	public function register(): void {
		add_filter( SettingsHub::TABS_FILTER, array( $this, 'registerSettingsTab' ) );
		add_action( SettingsHub::RENDER_ACTION_PREFIX . 'gravity-forms-intake', array( $this, 'renderPage' ) );
		add_action( 'admin_post_' . self::DOWNLOAD_ACTION, array( $this, 'downloadTemplate' ) );
	}

	public function registerSettingsTab( array $tabs ): array {
		$tabs['gravity-forms-intake'] = array(
			'label'    => __( 'Gravity Forms Intake', $this->context->textDomain() ),
			'priority' => 90,
		);

		return $tabs;
	}

	public function renderPage(): void {
		$gravity_forms = $this->getPluginStatus( 'gravityforms/gravityforms.php', 'gravityforms' );
		$post_creation = $this->getPluginStatus( '', 'gravityformsadvancedpostcreation' );
		$download_url  = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::DOWNLOAD_ACTION ),
			self::DOWNLOAD_ACTION
		);
		?>
		<div>
			<h2><?php esc_html_e( 'Gravity Forms Artist Intake', $this->context->textDomain() ); ?></h2>
			<p><?php esc_html_e( 'Download a starter Gravity Forms artist intake form and use Gravity Forms Advanced Post Creation to map submissions into pending artist records.', $this->context->textDomain() ); ?></p>

			<h2><?php esc_html_e( 'Status', $this->context->textDomain() ); ?></h2>
			<table class="widefat striped" style="max-width: 900px;">
				<tbody>
					<?php $this->renderStatusRow( __( 'Gravity Forms', $this->context->textDomain() ), $gravity_forms ); ?>
					<?php $this->renderStatusRow( __( 'Advanced Post Creation', $this->context->textDomain() ), $post_creation ); ?>
				</tbody>
			</table>

			<?php if ( 'active' !== $post_creation['state'] ) : ?>
				<div class="notice notice-warning inline" style="max-width: 900px;margin-top:16px;">
					<p>
						<?php esc_html_e( 'Advanced Post Creation is optional, but recommended if staff wants Gravity Forms to create pending artist records without custom intake code.', $this->context->textDomain() ); ?>
					</p>
				</div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Starter Form', $this->context->textDomain() ); ?></h2>
			<p><?php esc_html_e( 'The download is a Gravity Forms JSON export. Import it from Forms > Import/Export > Import Forms.', $this->context->textDomain() ); ?></p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $download_url ); ?>">
					<?php esc_html_e( 'Download Artist Intake Form JSON', $this->context->textDomain() ); ?>
				</a>
			</p>

			<h2><?php esc_html_e( 'Recommended Post Creation Feed', $this->context->textDomain() ); ?></h2>
			<ol style="max-width: 900px;">
				<li><?php esc_html_e( 'Import the starter form JSON in Gravity Forms.', $this->context->textDomain() ); ?></li>
				<li><?php esc_html_e( 'Create an Advanced Post Creation feed for the imported form.', $this->context->textDomain() ); ?></li>
				<li><?php esc_html_e( 'Set Post Type to mw_artist and Post Status to Pending.', $this->context->textDomain() ); ?></li>
				<li><?php esc_html_e( 'Map Post Title to Artist / Public Display Name and Post Content to Biography / Artist Statement.', $this->context->textDomain() ); ?></li>
				<li><?php esc_html_e( 'Map the featured image upload to the post featured image, and keep additional artwork uploads on the entry for staff review.', $this->context->textDomain() ); ?></li>
				<li><?php esc_html_e( 'Map custom fields and taxonomy fields using the reference below.', $this->context->textDomain() ); ?></li>
			</ol>

			<h2><?php esc_html_e( 'Mapping Reference', $this->context->textDomain() ); ?></h2>
			<table class="widefat striped" style="max-width: 900px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Form field admin label', $this->context->textDomain() ); ?></th>
						<th><?php esc_html_e( 'Directory target', $this->context->textDomain() ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $this->mappingRows() as $label => $target ) : ?>
						<tr>
							<td><code><?php echo esc_html( $label ); ?></code></td>
							<td><code><?php echo esc_html( $target ); ?></code></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function downloadTemplate(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to download this form template.', $this->context->textDomain() ) );
		}

		check_admin_referer( self::DOWNLOAD_ACTION );

		if ( ! is_readable( self::TEMPLATE_FILE ) ) {
			wp_die( esc_html__( 'The artist intake form template is unavailable.', $this->context->textDomain() ) );
		}

		nocache_headers();
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		header( 'Content-Disposition: attachment; filename=artist-directory-intake-starter.json' );
		header( 'Content-Length: ' . filesize( self::TEMPLATE_FILE ) );

		readfile( self::TEMPLATE_FILE ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}

	private function renderStatusRow( string $label, array $status ): void {
		?>
		<tr>
			<th scope="row" style="width: 240px;"><?php echo esc_html( $label ); ?></th>
			<td>
				<strong><?php echo esc_html( $status['label'] ); ?></strong>
				<?php if ( '' !== $status['detail'] ) : ?>
					<p class="description"><?php echo esc_html( $status['detail'] ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	private function getPluginStatus( string $plugin_file, string $slug ): array {
		$this->loadPluginFunctions();
		$active_plugins = (array) get_option( 'active_plugins', array() );
		$network_active = is_multisite() ? array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) : array();
		$all_active     = array_merge( $active_plugins, $network_active );
		$installed      = false;

		foreach ( array_keys( get_plugins() ) as $basename ) {
			if ( ( '' !== $plugin_file && $basename === $plugin_file ) || false !== strpos( $basename, $slug ) ) {
				$installed = true;
				if ( in_array( $basename, $all_active, true ) ) {
					return array(
						'state'  => 'active',
						'label'  => __( 'Active', $this->context->textDomain() ),
						'detail' => $basename,
					);
				}
			}
		}

		if ( $installed ) {
			return array(
				'state'  => 'inactive',
				'label'  => __( 'Installed but inactive', $this->context->textDomain() ),
				'detail' => __( 'Activate it before configuring the intake workflow.', $this->context->textDomain() ),
			);
		}

		return array(
			'state'  => 'missing',
			'label'  => __( 'Missing', $this->context->textDomain() ),
			'detail' => __( 'Not required by Directory Core, but may be needed for the recommended Gravity Forms workflow.', $this->context->textDomain() ),
		);
	}

	private function loadPluginFunctions(): void {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	private function mappingRows(): array {
		return array(
			'mw_artist_display_name'        => 'post_title',
			'mw_artist_biography'           => 'post_content',
			'mw_artist_primary_contact_name' => 'mw_artist_primary_contact_name',
			'mw_artist_public_email'        => 'mw_artist_public_email',
			'mw_artist_website_url'         => 'mw_artist_website_url',
			'mw_artist_instagram_url'       => 'mw_artist_instagram_url',
			'mw_artist_contact_preference'  => 'mw_artist_contact_preference',
			'mw_artist_accepting_inquiries' => 'mw_artist_accepting_inquiries',
			'mw_visibility_state'           => 'internal',
			'mw_artist_submission_source'   => 'gravity_forms',
			'mw_media'                      => 'mw_media taxonomy',
			'mw_artist_service'             => 'mw_artist_service taxonomy',
			'mw_artist_audience'            => 'mw_artist_audience taxonomy',
			'mw_artist_project_scale'       => 'mw_artist_project_scale taxonomy',
			'mw_artist_availability'        => 'mw_artist_availability taxonomy',
			'mw_artist_service_area'        => 'mw_artist_service_area taxonomy',
			'mw_artist_featured_image'      => 'featured image',
			'mw_profile_artwork_uploads'    => 'entry files for staff review',
		);
	}
}
