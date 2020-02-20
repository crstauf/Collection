<?php

class Collection_Test_Get extends Collection_UnitTestCase {

	function test_get() {
		$key = $this->register_collection( __METHOD__ );
		$this->assertInstanceOf( Collection::class, $this->get_runtime( $key ) );

		$this->assertTrue( ( bool ) did_action( 'collection_constructed' ) );
		$this->assertTrue( ( bool ) did_action( 'collection:' . $key . '/constructed' ) );
	}

	function test_get_from_cache() {
		$key = $this->register_collection( __FUNCTION__ );
		$this->get_runtime( $key );

		# Directly check the WP Object Cache.
		$found = false;
		$cached = wp_cache_get( $key, Collection::class, false, $found );

		$this->assertTrue( $found );
		$this->assertNotEmpty( $cached );
		$this->assertInstanceOf( Collection::class, $cached );
		$this->assertEquals( static::collection_callback()[2], $cached[2] );
		$this->assertEquals( 'object_cache', $cached->source );

		# Verify Collection::get() gets from cache.
		$collection = get_collection( $key );
		$this->assertInstanceOf( Collection::class, $collection );
		$this->assertEquals( static::collection_callback()[2], $collection[2] );
		$this->assertEquals( 'object_cache', $collection->source );

		# Compare the two.
		$this->assertEquals( $cached[1], $collection[1] );
		$this->assertEquals( $cached['rand'], $collection['rand'] );
	}

}
