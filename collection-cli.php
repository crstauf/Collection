<?php
/**
 * Plugin name: Collection CLI
 * Plugin URI: https://github.com/crstauf/Collection
 * Description: CLI commands to access Collections.
 * Author: Caleb Stauffer
 * Author URI: https://develop.calebstauffer.com
 * Version: 1.0
 */

defined( 'WPINC' ) || die();

if ( ! defined( 'WP_CLI' ) || ! constant( 'WP_CLI' ) ) {
	return;
}

class Collection_CLI {

	public const DATE_FORMAT = 'Y-m-d H:i:s';
	public const ALL_FIELDS  = array(
		'#',
		'ID',
		'items',
		'refreshed',
		'expiration',
		'callback',
		'life',
	);

	/**
	 * List Collections.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--orderby=<orderby>]
	 * : Order rows by single column value.
	 *
	 * [--order=<order>]
	 * : Specify ascending or descending.
	 * ---
	 * default: asc
	 * options:
	 *   - asc
	 *   - desc
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each Collection:
	 *
	 * * # (registration order)
	 * * ID (registration key)
	 * * callback
	 * * refreshed
	 *
	 * These fields are optionally available:
	 *
	 * * items
	 * * expiration
	 * * callback
	 * * life
	 *
	 * @param array<int, string> $args
	 * @param array<string, string> $assoc
	 */
	public function list( array $args, array $assoc = array() ) : void {
		$default_fields = array(
			'#',
			'ID',
			'callback',
			'refreshed',
		);

		$format  = WP_CLI\Utils\get_flag_value( $assoc, 'format', 'table' );
		$fields  = WP_CLI\Utils\get_flag_value( $assoc, 'fields', $default_fields );
		$orderby = WP_CLI\Utils\get_flag_value( $assoc, 'orderby', '#' );
		$order   = strtolower( WP_CLI\Utils\get_flag_value( $assoc, 'order', 'asc' ) );

		$orderby = explode( ',', $orderby );
		$orderby = $orderby[0];
		$orderby = trim( $orderby );

		$rows = array();
		$sort = array();

		if ( ! in_array( $orderby, self::ALL_FIELDS ) ) {
			$orderby = '#';
		}

		if ( ! in_array( $order, array( 'asc', 'desc' ) ) ) {
			$order = 'asc';
		}

		if ( 'asc' === $order ) {
			$order = SORT_ASC;
		} else {
			$order = SORT_DESC;
		}

		$i = 0;

		foreach ( Collection::registered() as $key => $collection ) {
			if ( empty( $collection->callback ) ) {
				continue;
			}

			if ( 'ids' === $format ) {
				$rows[] = $collection->key;
				continue;
			}

			$row = array(
				'#'          => ++$i,
				'ID'         => $collection->key,
				'refreshed'  => '',
				'expiration' => '',
				'callback'   => json_encode( $collection->callback ),
				'life'       => $collection->life,
			);

			if ( ! empty( $collection->refreshed ) ) {
				$row['refreshed'] = $collection->refreshed->format( self::DATE_FORMAT );
			}

			if ( ! empty( $collection->expiration ) ) {
				$row['expiration'] = $collection->expiration->format( self::DATE_FORMAT );
			}

			if ( '#' !== $orderby ) {
				$sort[] = $row[ $orderby ];
			}

			$rows[] = $row;
		}

		if ( ! empty( $sort ) ) {
			array_multisort( $sort, $order, SORT_NATURAL, $rows );
		}

		WP_CLI\Utils\format_items( $format, $rows, $fields );
	}

	/**
	 * Gets details about a Collection.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The key of the registered Collection to get.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole Collection, returns the value of a single property.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * * # (registration order)
	 * * ID (registration key)
	 * * items
	 * * refreshed
	 * * expiration
	 * * callback
	 * * life
	 *
	 * @param array<int, string> $args
	 * @param array<string, mixed> $assoc
	 */
	public function get( array $args, array $assoc = array() ) : void {
		$collection = Collection::get( $args[0] );

		if ( empty( $collection->callback ) ) {
			WP_CLI::error( sprintf( 'Collection %s is not registered.', $args[0] ) );
		}

		$format = WP_CLI\Utils\get_flag_value( $assoc, 'format', 'table' );
		$field  = WP_CLI\Utils\get_flag_value( $assoc, 'field', null );
		$fields = WP_CLI\Utils\get_flag_value( $assoc, 'fields', self::ALL_FIELDS );

		if ( is_string( $fields ) ) {
			$fields = explode( ',', $fields );
			$fields = array_map( 'trim', $fields );
		}

		if ( count( $fields ) === 1 ) {
			$field = array_pop( $fields );
		}

		if ( 'key' === $field ) {
			$field = 'ID';
		}

		if ( in_array( $field, self::ALL_FIELDS ) ) {
			if ( 'ID' === $field ) {
				$field = 'key';
			}

			if ( 'json' === $format ) {
				WP_CLI::line( ( string ) json_encode( $collection->$field ) );
				exit;
			}

			WP_CLI::line( print_r( $collection->$field, true ) );
			exit;
		}

		$rows = array(
			array(
				'field' => 'ID',
				'value' => $args[0],
			),
			array(
				'field' => 'callback',
				'value' => json_encode( $collection->callback ),
			),
			array(
				'field' => 'items',
				'value' => json_encode( $collection->items() ),
			),
		);

		$value = '';

		if ( ! empty( $collection->refreshed ) ) {
			$value = $collection->refreshed->format( self::DATE_FORMAT );
		}

		$rows[] = array(
			'field' => 'refreshed',
			'value' => $value,
		);

		$value = '';

		if ( ! empty( $collection->expiration ) ) {
			$value = $collection->expiration->format( self::DATE_FORMAT );
		}

		$rows[] = array(
			'field' => 'expiration',
			'value' => $value,
		);

		$rows[] = array(
			'field' => 'life',
			'value' => $collection->life,
		);

		if ( $fields !== self::ALL_FIELDS ) {
			$rows = array_filter( $rows, static function ( $row ) use ( $fields ) {
				return in_array( $row['field'], $fields );
			} );
		}

		WP_CLI\Utils\format_items( $format, $rows, array( 'field', 'value' ) );
	}

	/**
	 * Expire a Collection's items.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The key of the registered Collection to expire.
	 *
	 * @param array<int, string> $args
	 */
	public function expire( array $args ) : void {
		$collection = Collection::get( $args[0] );

		if ( empty( $collection->callback ) ) {
			WP_CLI::error( sprintf( 'Collection %s is not registered.', $args[0] ) );
		}

		add_action( 'collection_expired', static function ( $key ) use ( $args ) {
			if ( $key !== $args[0] ) {
				return;
			}

			WP_CLI::success( 'Expired Collection.' );
			exit;
		} );

		$collection->expire();

		WP_CLI::error( 'Could not expire Collection.' );
	}

	/**
	 * Refresh a Collection's items.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The key of the registered Collection to refresh.
	 *
	 * @param array<int, string> $args
	 */
	public function refresh( array $args ) : void {
		$collection = Collection::get( $args[0] );

		if ( empty( $collection->callback ) ) {
			WP_CLI::error( sprintf( 'Collection %s is not registered.', $args[0] ) );
		}

		add_action( 'collection_refreshed', static function ( $key ) use ( $args ) {
			if ( $key !== $args[0] ) {
				return;
			}

			WP_CLI::success( 'Refreshed Collection.' );
			exit;
		} );

		$collection->refresh();

		WP_CLI::error( 'Could not refresh Collection.' );
	}

}

add_action( 'muplugins_loaded', static function () : void {
	if ( ! class_exists( 'Collection' ) ) {
		return;
	}

	$cli = new Collection_CLI();

	WP_CLI::add_command( 'collection', $cli );
} );