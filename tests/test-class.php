<?php

class Collection_Test_Class extends Collection_UnitTestCase {

	/**
	 * Test implementations.
	 */
	function test_implements() {
		$this->assertTrue( class_exists( 'Collection' ) );

		$implements = class_implements( 'Collection' );
		$this->assertContains( 'ArrayAccess', $implements );
		$this->assertContains(   'Countable', $implements );
		$this->assertContains(    'Iterator', $implements );
	}

	/**
	 * Test that static methods are callable.
	 */
	function test_static_callables() {
		foreach ( array(
			'format_key',
			'is_registered',
			'register',
			'get',
		) as $static_method_name )
			$this->assertIsCallable( array( Collection::class, $static_method_name ) );
	}

	/**
	 * Test publicly accessible properties.
	 *
	 * @covers Collection::__get()
	 */
	function test_properties() {
		$key = $this->register_collection( __METHOD__ );
		$collection = $this->get_runtime( $key );

		$this->assertNotEmpty( $collection->items );
		$this->assertEmpty( $collection->___empty );
	}

	/**
	 * Test implementation of ArrayAccess.
	 */
	function test_array_access() {
		$key = $this->register_collection( __METHOD__ );
		$collection = $this->get_runtime( $key );

		# Test offsetGet().
		$this->assertEquals( 3, $collection[2] );

		# Test offsetExists().
		$this->assertTrue( isset( $collection[2] ) );

		# Test offsetSet().
		$collection[2] = 10;
		$this->assertNotEquals( 10, $collection[2] );
		$this->assertEquals(     3, $collection[2] );

		# Test offsetUnset().
		unset( $collection[2] );
		$this->assertNotEmpty(  $collection[2] );
		$this->assertEquals( 3, $collection[2] );
		$this->assertTrue( $collection->has( 2 ) );
	}

	/**
	 * Test implementation of Countable.
	 */
	function test_countable() {
		$key = $this->register_collection( __METHOD__ );
		$collection = $this->get_runtime( $key );

		$this->assertEquals( count( static::collection_callback() ), count( $collection ) );
		$this->assertEquals( count( static::collection_callback() ), $collection->count() );
	}

	/**
	 * Test implementation of Iterable.
	 */
	function test_iterable() {
		$key = $this->register_collection( __METHOD__ );
		$collection = $this->get_runtime( $key );

		foreach ( $collection as $i => $v )
			if ( 'rand' !== $i )
				$this->assertEquals( static::collection_callback()[$i], $v );

		$collection->rewind();
		$this->assertEquals( static::collection_callback()[0], $collection->current() );
		$this->assertEquals( 0, $collection->key() );

		$collection->next();
		$this->assertEquals( static::collection_callback()[1], $collection->current() );
		$this->assertEquals( 1, $collection->key() );

		$collection->next();
		$this->assertEquals( static::collection_callback()[2], $collection->current() );
		$this->assertEquals( 2, $collection->key() );

		$collection->rewind();
		$this->assertEquals( static::collection_callback()[0], $collection->current() );
		$this->assertEquals( 0, $collection->key() );

		$this->assertNull( null );
	}

}
