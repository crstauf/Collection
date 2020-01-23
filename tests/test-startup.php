<?php

class Collection_Startup_Test extends WP_UnitTestCase {

	protected static $classes = array( 'Collection' );

	static function setUpBeforeClass() {
		if ( Collection::class() !== 'Collection' )
			static::$classes[] = Collection::class();
	}

	function test_constants_defined() {
		$this->assertTrue( defined( 'COLLECTION__DUPLICATES_CHECK' ) );
		$this->assertTrue( defined( 'COLLECTION__LOG_ACCESS' ) );
	}

	function test_classes_exist() {
		$this->assertTrue( class_exists( 'Collection' ) );

		if ( Collection::class() !== 'Collection' )
			$this->assertTrue( class_exists( Collection::class() ) );
	}

	function test_implements() {
		$implements = array(
			'ArrayAccess',
			'Countable',
			'Iterator',
		);

		foreach ( static::$classes as $class ) {
			$class_implements = class_implements( $class );

			$this->assertContains( 'ArrayAccess', $class_implements );
			$this->assertContains(   'Countable', $class_implements );
			$this->assertContains(    'Iterator', $class_implements );
		}
	}

	function test_statics_callable() {
		$static_methods = array(
			'class',
			'transient_name',
			'register',
			'get',
			'check_duplicate',
		);

		$classes = array( 'Collection' );

		if ( 'Collection' !== Collection::class() )
			$classes[] = Collection::class();

		foreach ( $classes as $class )
			foreach ( $static_methods as $static_method )
				$this->assertIsCallable( array( $class, $static_method ) );
	}

	function test_helpers_exist() {
		$this->assertTrue( function_exists( 'register_collection' ) );
		$this->assertTrue( function_exists(      'get_collection' ) );
	}

	function test_did_action() {
		$this->assertTrue( ( bool ) did_action( 'collections_available' ) );
	}

}
