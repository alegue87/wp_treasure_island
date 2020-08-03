<?php
namespace TreasureIsland\Frontend;

/**
 *
 * Frontend_Init
 *
 * Main class for managing frontend apps
 *
 */
class Frontend_Init {


	/**
	 * Class constructor
	 */
	public function __construct()
	{
		$this->load_app();
	}

	/**
	 *
	 * Method that loads the mobile web application theme.
	 *
	 */
	public function load_app()
	{
		add_filter( 'stylesheet', [ $this, 'app_theme' ], 11 );
		add_filter( 'template', [ $this, 'app_theme' ], 11);

		add_filter( 'theme_root', [ $this, 'app_theme_root' ], 11 );
		add_filter( 'theme_root_uri', [ $this, 'app_theme_root' ], 11 );
	}


	/**
	 * Return the theme name
	 */
	public function app_theme($desktop_theme)
	{
		return 'app';
	}

	/**
	 * Return path to the mobile themes folder
	 */
	public function app_theme_root($destkop_theme_root)
	{
		return TREASUREISLAND_PLUGIN_PATH . 'frontend/themes';
	}

}

