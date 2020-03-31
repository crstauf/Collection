<?php

class Collection_Test_SamplePosts extends Collection_UnitTestCase {

	protected static $post_ids;

	protected static function get_sample_data() {
		for ( $i = 0; $i < 5; $i++ ) {
			$response = wp_remote_get( 'https://jsonplaceholder.typicode.com/posts' );

			if ( !is_wp_error( $response ) )
				break;
		}

		if ( is_wp_error( $response ) )
			return array();

		$data = json_decode( wp_remote_retrieve_body( $response ) );
		shuffle( $data );

		return array_slice( array_values( $data ), 0, 10 );
	}

	protected static function create_sample_posts() {
		$sample_posts = array();

		foreach ( static::get_sample_data() as $sample_post )
			$sample_posts[] = array(
				'post_content' => $sample_post->body,
				'post_author'  => $sample_post->userId,
				'post_title'   => $sample_post->title,
			);

		$post_ids = array_map( 'wp_insert_post', $sample_posts );
echo __LINE__ . ': ' . print_r( $post_ids, true );
		return $post_ids;
	}

	static function setUpBeforeClass() {
		static::$post_ids = static::create_sample_posts();
echo __LINE__ . ': ' . print_r( static::$post_ids, true );
	}

	static function collection_callback() {
echo __LINE__ . ': ' . print_r( static::$post_ids, true );
		return static::$post_ids;
	}

	/**
	 * @group sample_data
	 */
	function test_posts() {
		$key = $this->register_collection( __METHOD__ );
echo __LINE__ . ': ' . print_r( static::$post_ids, true );
echo __LINE__ . ': ' . print_r( static::collection_callback(), true );
		add_filter( 'collection:' . $key . '/proper_items', function( $items ) { return array_map( 'get_post', $items ); } );

		$runtime = $this->get_runtime( $key );

		$this->assertEquals( count( static::collection_callback() ), count( $runtime ) );
		$this->assertEquals( static::collection_callback()[3], $runtime[3] );
		$this->assertEquals( 'integer', gettype( $runtime[3] ) );
		$this->assertTrue( $runtime->has( 0 ) );

		$items = $runtime->get_proper_items();
		$this->assertEquals( count( $runtime ), count( $items ) );
		$this->assertInstanceOf( 'WP_Post', $items[0] );
	}

}
