<?php
namespace DirectoryCore\Integration;

class CoreApi {
	public const ADMIN_MENU_SLUG = 'mw-creative-directory';

	public static function adminMenuSlug(): string {
		return self::ADMIN_MENU_SLUG;
	}

	public static function visibilityOptions(): array {
		return array(
			'internal'  => __( 'Internal only', DIRECTORY_CORE_TEXT_DOMAIN ),
			'directory' => __( 'Public directory listing', DIRECTORY_CORE_TEXT_DOMAIN ),
			'profile'   => __( 'Public directory + profile', DIRECTORY_CORE_TEXT_DOMAIN ),
		);
	}

	public static function defaultArtistVisibility(): string {
		return 'directory';
	}

	public static function defaultVenueVisibility(): string {
		return 'internal';
	}

	public static function normalizeVisibility( string $visibility, string $fallback ): string {
		return array_key_exists( $visibility, self::visibilityOptions() ) ? $visibility : $fallback;
	}

	public static function getPostVisibility( int $post_id, string $fallback ): string {
		$visibility = (string) get_post_meta( $post_id, 'mw_visibility_state', true );

		return self::normalizeVisibility( $visibility, $fallback );
	}

	public static function isArtistPubliclyListed( int $artist_id ): bool {
		return in_array( self::getPostVisibility( $artist_id, self::defaultArtistVisibility() ), array( 'directory', 'profile' ), true );
	}

	public static function isArtistPubliclyViewable( int $artist_id ): bool {
		return 'profile' === self::getPostVisibility( $artist_id, self::defaultArtistVisibility() );
	}

	public static function getArtistOptions(): array {
		return self::getRecordOptions( 'mw_artist' );
	}

	public static function getVenueOptions(): array {
		return self::getRecordOptions( 'mw_venue' );
	}

	public static function getRecordOptions( string $post_type ): array {
		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => array( 'publish', 'draft', 'private', 'pending' ),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$options = array();

		foreach ( $posts as $post ) {
			$options[ (int) $post->ID ] = get_the_title( $post );
		}

		return $options;
	}

	public static function getLinkedArtistData( int $artist_id ): ?array {
		$post = get_post( $artist_id );
		if ( ! $post || 'mw_artist' !== $post->post_type ) {
			return null;
		}

		$visibility = self::getPostVisibility( $artist_id, self::defaultArtistVisibility() );

		return array(
			'id'         => (int) $artist_id,
			'title'      => get_the_title( $post ),
			'url'        => self::isArtistPubliclyViewable( $artist_id ) ? get_permalink( $post ) : '',
			'visibility' => $visibility,
		);
	}

	public static function getArtistDisplayName( int $artist_id ): string {
		$studio_name = trim( (string) get_post_meta( $artist_id, 'mw_artist_studio_name', true ) );
		if ( '' !== $studio_name ) {
			return $studio_name;
		}

		$first_name = trim( (string) get_post_meta( $artist_id, 'mw_artist_first_name', true ) );
		$last_name  = trim( (string) get_post_meta( $artist_id, 'mw_artist_last_name', true ) );
		$full_name  = trim( $first_name . ' ' . $last_name );

		if ( '' !== $full_name ) {
			return $full_name;
		}

		return get_the_title( $artist_id );
	}

	public static function getArtistSortName( int $artist_id ): string {
		$last_name = trim( (string) get_post_meta( $artist_id, 'mw_artist_last_name', true ) );
		if ( '' !== $last_name ) {
			return $last_name;
		}

		return self::getArtistDisplayName( $artist_id );
	}

	public static function getArtistSortInitial( int $artist_id ): string {
		$sort_name = remove_accents( self::getArtistSortName( $artist_id ) );
		$initial   = strtoupper( substr( trim( $sort_name ), 0, 1 ) );

		return preg_match( '/[A-Z]/', $initial ) ? $initial : '#';
	}

	public static function sanitizeIdList( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
	}
}
