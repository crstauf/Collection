<?php

class Collection_Test_Callback extends Collection_UnitTestCase {

	function test_callable() {
		$key = $this->register_collection( __METHOD__ );
		$this->get_runtime( $key );
		$this->assertNull( null );
	}

	function test_empty() {
		$key = $this->register_collection( __METHOD__ );

		add_filter( 'collection:' . $key . '/callback', '__return_false' );

		$runtime = $this->get_runtime( $key );
		$this->assertIsArray( $runtime->items );
		$this->assertEmpty(   $runtime->items );

		remove_filter( 'collection:' . $key . '/callback', '__return_false' );
	}

	function test_uncallable_callback_exception() {
		$key = static::key( __METHOD__ );
		register_collection( $key, '___nonexistent_callback' );
		$this->expectException( 'PHPUnit_Framework_Error_Notice' );
		$this->get_runtime( $key );
	}

	function test_uncallable_callback() {
		$key = static::key( __FUNCTION__ );
		@register_collection( $key, '___nonexistent_callback' );
		$runtime = @$this->get_runtime( $key );

		$this->assertInstanceOf( Collection::class, $runtime );
		$this->assertIsArray( $runtime->items );
		$this->assertEmpty(   $runtime->items );
	}

}
