<?php

namespace payconiq\lib;

use \DI;
use DI\ContainerBuilder;

Final class Container implements Container_Interface {

	/**
	 * @var \DI\ContainerBuilder
	 *
	 * @since 1.0.0
	 */
	protected $builder;

	/**
	 * @var \DI\Container
	 *
	 * @since 1.0.0
	 */
	public $container;

	/**
	 * @var Container
	 *
	 * @since 1.0.0
	 */
	protected static $instance;

	/**
	 * Build Container.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->builder = new ContainerBuilder();

		$this->build_container();

		$this->set_classes();
	}

	/**
	 * Instance of this class
	 *
	 * @return Container
	 *
	 * @since 1.0.0
	 */
	public static function getInstance() {
		if ( null == static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Define config classes
	 *
	 * @since 1.0.0
	 */
	public function build_container() {
		$this->builder->addDefinitions( array(
			'payconiq_config' => DI\create( 'payconiq\config\Payconiq_Config' )
		) );

		$this->container = $this->builder->build();

		/**
		 * Set init config with container as parameter
		 */
		$this->container->set( 'init_config', DI\create( 'payconiq\config\Init_Config' ) );
	}

	/**
	 * Set classes that needs to be used
	 *
	 * @since 1.0.0
	 */
	public function set_classes() {
		$this->container->set( 'example_controller', \DI\create( 'payconiq\controller\Example_Controller' ) );
		$this->container->set( 'base_controller', \DI\create( 'payconiq\controller\Base_Controller' ) );
		$this->container->set( 'example_model', \DI\create( 'payconiq\model\Example_Model' ) );
	}
}