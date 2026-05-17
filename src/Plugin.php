<?php
namespace DirectoryCore;

use DirectoryCore\Admin\AdminColumns;
use DirectoryCore\Admin\AdminMenu;
use DirectoryCore\Content\ArtistDiscoveryTaxonomies;
use DirectoryCore\Content\ArtistPostTypeRegistrar;
use DirectoryCore\Content\MediaTaxonomy;
use DirectoryCore\Content\VenuePostTypeRegistrar;
use DirectoryCore\Contracts\Service;
use DirectoryCore\Infrastructure\PluginContext;
use DirectoryCore\Meta\ArtistMetaManager;
use DirectoryCore\Meta\VenueMetaManager;

class Plugin {
	private static ?self $instance = null;

	private PluginContext $context;

	/** @var array<string, object> */
	private array $services = array();

	private function __construct( PluginContext $context ) {
		$this->context = $context;
		$this->buildServices();
	}

	public static function boot( PluginContext $context ): self {
		if ( null === self::$instance ) {
			self::$instance = new self( $context );
			self::$instance->register();
		}

		return self::$instance;
	}

	public static function activate( PluginContext $context ): void {
		$artist_registrar = new ArtistPostTypeRegistrar( $context );
		$venue_registrar  = new VenuePostTypeRegistrar( $context );
		$media_taxonomy   = new MediaTaxonomy( $context );
		$artist_taxonomies = new ArtistDiscoveryTaxonomies( $context );

		$artist_registrar->registerPostType();
		$venue_registrar->registerPostType();
		$media_taxonomy->registerTaxonomy();
		$artist_taxonomies->registerTaxonomies();
		$artist_taxonomies->seedDefaultTerms();

		flush_rewrite_rules();
	}

	private function buildServices(): void {
		$this->services = array(
			AdminMenu::class          => new AdminMenu( $this->context ),
			ArtistPostTypeRegistrar::class => new ArtistPostTypeRegistrar( $this->context ),
			VenuePostTypeRegistrar::class  => new VenuePostTypeRegistrar( $this->context ),
			MediaTaxonomy::class      => new MediaTaxonomy( $this->context ),
			ArtistDiscoveryTaxonomies::class => new ArtistDiscoveryTaxonomies( $this->context ),
			ArtistMetaManager::class  => new ArtistMetaManager( $this->context ),
			VenueMetaManager::class   => new VenueMetaManager( $this->context ),
			AdminColumns::class       => new AdminColumns( $this->context ),
		);
	}

	private function register(): void {
		add_action( 'plugins_loaded', array( $this, 'loadTextdomain' ) );

		foreach ( $this->services as $service ) {
			if ( $service instanceof Service ) {
				$service->register();
			}
		}
	}

	public function loadTextdomain(): void {
		load_plugin_textdomain(
			$this->context->textDomain(),
			false,
			dirname( $this->context->pluginBasename() ) . '/languages'
		);
	}
}
