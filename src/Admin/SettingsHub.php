<?php
namespace DirectoryCore\Admin;

use DirectoryCore\Contracts\Service;
use DirectoryCore\Infrastructure\PluginContext;
use DirectoryCore\Integration\CoreApi;

class SettingsHub implements Service {
	public const PAGE_SLUG = 'mw-creative-directory-settings';
	public const TABS_FILTER = 'directory_core_settings_tabs';
	public const RENDER_ACTION_PREFIX = 'directory_core_render_settings_tab_';

	private PluginContext $context;

	public function __construct( PluginContext $context ) {
		$this->context = $context;
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'registerSettingsPage' ) );
	}

	public function registerSettingsPage(): void {
		add_submenu_page(
			CoreApi::adminMenuSlug(),
			__( 'Directory Settings', $this->context->textDomain() ),
			__( 'Settings', $this->context->textDomain() ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'renderSettingsPage' )
		);
	}

	public function renderSettingsPage(): void {
		$tabs = $this->getTabs();
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		if ( empty( $tabs ) ) {
			$current_tab = '';
		} elseif ( '' === $current_tab || ! isset( $tabs[ $current_tab ] ) ) {
			$current_tab = (string) array_key_first( $tabs );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Directory Settings', $this->context->textDomain() ); ?></h1>
			<?php if ( ! empty( $tabs ) ) : ?>
				<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Directory settings sections', $this->context->textDomain() ); ?>">
					<?php foreach ( $tabs as $slug => $tab ) : ?>
						<a
							class="nav-tab <?php echo $slug === $current_tab ? 'nav-tab-active' : ''; ?>"
							href="<?php echo esc_url( add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => $slug ), admin_url( 'admin.php' ) ) ); ?>"
						>
							<?php echo esc_html( $tab['label'] ); ?>
						</a>
					<?php endforeach; ?>
				</nav>
				<div style="margin-top:24px;">
					<?php do_action( self::RENDER_ACTION_PREFIX . $current_tab ); ?>
				</div>
			<?php else : ?>
				<p><?php esc_html_e( 'No directory settings tabs are currently registered.', $this->context->textDomain() ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private function getTabs(): array {
		$tabs = apply_filters( self::TABS_FILTER, array() );
		$tabs = is_array( $tabs ) ? $tabs : array();
		$tabs = array_filter(
			$tabs,
			static function ( $tab ): bool {
				return is_array( $tab ) && ! empty( $tab['label'] );
			}
		);

		uasort(
			$tabs,
			static function ( array $a, array $b ): int {
				return ( $a['priority'] ?? 10 ) <=> ( $b['priority'] ?? 10 );
			}
		);

		return $tabs;
	}
}
