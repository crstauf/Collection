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

		return array_slice( $data, 0, 10 );
	}

	protected static function create_sample_posts() {
		$sample_posts = array();

		foreach ( static::get_sample_data() as $sample_post )
			$sample_posts[] = array(
				'post_content' => $sample_post->body,
				'post_author'  => $sample_post->userId,
				'post_title'   => $sample_post->title,
			);

		return array_map( 'wp_insert_post', $sample_posts );
	}

	static function setUpBeforeClass() {
		static::$post_ids = static::create_sample_posts();
	}

	static function collection_callback() {
		return static::$post_ids;
	}

	/**
	 * @group sample_data
	 */
	function test_posts() {
		$key = $this->register_collection( __METHOD__ );

		add_filter( 'collection:' . $key . '/proper_items', function( $items ) { return array_map( 'get_post', $items ); } );

		$runtime = $this->get_runtime( $key );

		$this->assertEquals( count( static::collection_callback() ), count( $runtime ) );
		$this->assertEquals( array_keys( static::collection_callback() ), array_keys( $runtime->get_proper_items() ) );
		$this->assertEquals( static::collection_callback()[3], $runtime[3] );
		$this->assertEquals( 'integer', gettype( $runtime[3] ) );
		$this->assertTrue( $runtime->has( 0 ) );

		$items = $runtime->get_proper_items();
		$this->assertEquals( count( $runtime ), count( $items ) );
		$this->assertInstanceOf( 'WP_Post', $items[0] );
	}

}
