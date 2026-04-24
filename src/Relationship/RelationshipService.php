<?php
namespace DirectoryCore\Relationship;

use DirectoryCore\Integration\CoreApi;

class RelationshipService {
	public static function syncArtistVenueRelationships( int $artist_id, array $old_venue_ids, array $new_venue_ids ): void {
		$old_venue_ids = CoreApi::sanitizeIdList( $old_venue_ids );
		$new_venue_ids = CoreApi::sanitizeIdList( $new_venue_ids );

		foreach ( array_diff( $old_venue_ids, $new_venue_ids ) as $venue_id ) {
			$artist_ids = CoreApi::sanitizeIdList( get_post_meta( (int) $venue_id, 'mw_related_artist_ids', true ) );
			$artist_ids = array_values( array_diff( $artist_ids, array( $artist_id ) ) );
			update_post_meta( (int) $venue_id, 'mw_related_artist_ids', $artist_ids );
		}

		foreach ( array_diff( $new_venue_ids, $old_venue_ids ) as $venue_id ) {
			$artist_ids   = CoreApi::sanitizeIdList( get_post_meta( (int) $venue_id, 'mw_related_artist_ids', true ) );
			$artist_ids[] = $artist_id;
			update_post_meta( (int) $venue_id, 'mw_related_artist_ids', CoreApi::sanitizeIdList( $artist_ids ) );
		}
	}

	public static function syncVenueArtistRelationships( int $venue_id, array $old_artist_ids, array $new_artist_ids ): void {
		$old_artist_ids = CoreApi::sanitizeIdList( $old_artist_ids );
		$new_artist_ids = CoreApi::sanitizeIdList( $new_artist_ids );

		foreach ( array_diff( $old_artist_ids, $new_artist_ids ) as $artist_id ) {
			$venue_ids = CoreApi::sanitizeIdList( get_post_meta( (int) $artist_id, 'mw_related_venue_ids', true ) );
			$venue_ids = array_values( array_diff( $venue_ids, array( $venue_id ) ) );
			update_post_meta( (int) $artist_id, 'mw_related_venue_ids', $venue_ids );
		}

		foreach ( array_diff( $new_artist_ids, $old_artist_ids ) as $artist_id ) {
			$venue_ids   = CoreApi::sanitizeIdList( get_post_meta( (int) $artist_id, 'mw_related_venue_ids', true ) );
			$venue_ids[] = $venue_id;
			update_post_meta( (int) $artist_id, 'mw_related_venue_ids', CoreApi::sanitizeIdList( $venue_ids ) );
		}
	}
}
