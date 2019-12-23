<?php

/**
 * Collection CLI commands.
 */
class Collection_CLI {

	/**
	 * @var string[]
	 */
	protected static $commands = array(
		'list',
		'get',
		'items',
		'clear',
		'refresh',
	);

	/**
	 * @var string[]
	 */
	protected static $registered = array();

	/**
	 * Action: collection_registered
	 *
	 * - capture keys of registered Collection
	 *
	 * @param mixed $key
	 */
	static function action__collection_registered( $key ) {
		static::$registered[] = $key;
	}

	/**
	 * Get Collection.
	 *
	 * @param string $key Collection key.
	 * @uses get_collection()
	 * @return Collection
	 */
	protected function get_collection( $key ) {
		$collection = get_collection( $key );

		if ( empty( $collection ) )
			WP_CLI::error( 'No Collection found.' );

		return $collection;
	}

	/**
	 * Invoke.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	function __invoke( $args, $assoc_args = array() ) {
		$command = array_shift( $args );

		if ( !in_array( $command, static::$commands ) )
			return;

		call_user_func( array( $this, $command ), $args, $assoc_args );
	}

	/**
	 * Get Collection fields.
	 *
	 * @param Collection $collection
	 * @return array
	 */
	protected function get_fields( Collection $collection ) {
		# Get and format Collection's expiration.
		$expiration = is_a( $collection->expiration, 'DateTime' )
			? $collection->expiration->format( DATE_ISO8601 )
			: 'none';

		# Get Collection's callback.
		$callback = $collection->callback;

		# Format Collection's callback.
		if ( is_array( $callback ) ) {
			$class = $callback[0];
			$access = '::';

			if ( is_object( $callback[0] ) ) {
				$class  = get_class( $class );
				$access = '->';
			}

			$callback = $class . $access . $callback[1];
		}

		return array(
			'key'        => $collection->key,
			'created'    => $collection->created->format( DATE_ISO8601 ),
			'expiration' => $expiration,
			'callback'   => $callback . '()',
			'items'      => $collection->get_items(),
			'source'     => $collection->source,
		);
	}

	protected function list( $args, $assoc_args = array() ) {
		$fields = array( 'i', 'key', 'callback', 'items', 'source' );
		$formatter = new WP_CLI\Formatter( $assoc_args, $fields );$collection_keys = $this->get_collection_keys( $args[0] );

		$items = array();

		foreach ( static::$registered as $i => $key ) {
			$collection = get_collection( $key );

			if ( empty( $collection ) )
				continue;

			$items[] = array_merge( array( 'i' => ( $i + 1 ) ), $this->get_fields( $collection ) );
		}

		if ( 'ids' === $formatter->format ) {
			echo implode( ' ', array_column( $items, 'key' ) );
			return;
		}

		$formatter->display_items( $items );
	}

	protected function get( $args, $assoc_args = array() ) {
		$key = $args[0];
		$fields = array( 'key', 'items', 'expiration' );
		$calls = ( int ) WP_CLI\Utils\get_flag_value( $assoc_args, 'calls', 1 );

		# If calling more than once.
		if ( 1 !== $calls ) {
			array_unshift( $fields, 'i' );
			array_push( $fields, 'load_time' );
		}

		$formatter = new WP_CLI\Formatter( $assoc_args, $fields );
		$items = array();

		# Get the Collection and store data.
		for ( $i = 0; $i < $calls; $i++ ) {
			$start = microtime( true );
			$collection = $this->get_collection( $key );

			$item = $this->get_fields( $collection );

			if ( 1 !== $calls ) {
				$item = array_merge( array( 'i' => $i + 1 ), $item );
				$item['load_time'] = ( microtime( true ) - $start ) . 's';
			}

			$items[] = $item;
		}

		# If calling once and format is 'table'.
		if (
			'table' === $formatter->format
			&& 1 === $calls
		) {
			$item = array_pop( $items );
			$items = array();
			$fields = array( 'property', 'value' );
			$formatter = new WP_CLI\Formatter( $assoc_args, $fields );

			unset( $item['i'] );

			foreach ( $item as $property => $value )
				$items[] = array(
					'property' => $property,
					'value' => $value,
				);
		}

		# Display items.
		$formatter->display_items( $items );

		return $collection;
	}

	protected function clear( $args, $assoc_args = array() ) {
		$collection = $this->get_collection( $args[0] );
		$collection->clear();

		WP_CLI::success( 'Cleared ' . $collection->key . ' Collection items.' );
	}

	protected function refresh( $args, $assoc_args = array() ) {
		$collection = $this->get_collection( $args[0] );
		$collection->refresh();

		WP_CLI::success( 'Refreshed ' . $collection->key . ' Collection items.' );
		$this->get( $args, $assoc_args );
	}

}

add_action( 'collection_registered', array( 'Collection_CLI', 'action__collection_registered' ) );

?>