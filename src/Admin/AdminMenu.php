<?php
namespace DirectoryCore\Admin;

use DirectoryCore\Contracts\Service;
use DirectoryCore\Infrastructure\PluginContext;
use DirectoryCore\Integration\CoreApi;

class AdminMenu implements Service {
	private PluginContext $context;

	public function __construct( PluginContext $context ) {
		$this->context = $context;
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'registerAdminMenu' ) );
	}

	public function registerAdminMenu(): void {
		add_menu_page(
			__( 'Creative Directory', $this->context->textDomain() ),
			__( 'Creative Directory', $this->context->textDomain() ),
			'edit_posts',
			CoreApi::adminMenuSlug(),
			array( $this, 'renderDashboard' ),
			'dashicons-id-alt',
			24
		);
	}

	public function renderDashboard(): void {
		$artist_counts = wp_count_posts( 'mw_artist' );
		$venue_counts  = wp_count_posts( 'mw_venue' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Creative Directory', $this->context->textDomain() ); ?></h1>
			<p><?php esc_html_e( 'Shared records for the artist directory, public art map, and future event products.', $this->context->textDomain() ); ?></p>
			<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;max-width:900px;margin-top:24px;">
				<div style="background:#fff;border:1px solid #dcdcde;padding:18px;">
					<strong><?php esc_html_e( 'Artists', $this->context->textDomain() ); ?></strong>
					<p style="font-size:28px;line-height:1.1;margin:12px 0 0;"><?php echo esc_html( (string) ( $artist_counts->publish + $artist_counts->draft + $artist_counts->private + $artist_counts->pending ) ); ?></p>
				</div>
				<div style="background:#fff;border:1px solid #dcdcde;padding:18px;">
					<strong><?php esc_html_e( 'Venues', $this->context->textDomain() ); ?></strong>
					<p style="font-size:28px;line-height:1.1;margin:12px 0 0;"><?php echo esc_html( (string) ( $venue_counts->publish + $venue_counts->draft + $venue_counts->private + $venue_counts->pending ) ); ?></p>
				</div>
			</div>
			<p style="margin-top:24px;"><?php esc_html_e( 'Use Artists and Venues from this menu as the canonical records for sibling products.', $this->context->textDomain() ); ?></p>
		</div>
		<?php
	}
}
