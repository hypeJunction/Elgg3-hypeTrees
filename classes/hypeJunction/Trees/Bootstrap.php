<?php

namespace hypeJunction\Trees;

use Elgg\PluginBootstrap;

class Bootstrap extends PluginBootstrap {

	/**
	 * Executed during 'plugin_boot:before', 'system' event
	 *
	 * Allows the plugin to require additional files, as well as configure services prior to booting the plugin
	 *
	 * @return void
	 */
	public function load() {

	}

	/**
	 * Executed during 'plugin_boot:before', 'system' event
	 *
	 * Allows the plugin to register handlers for 'plugin_boot', 'system' and 'init', 'system' events,
	 * as well as implement boot time logic
	 *
	 * @return void
	 */
	public function boot() {

	}

	/**
	 * Executed during 'init', 'system' event
	 *
	 * Allows the plugin to implement business logic and register all other handlers
	 *
	 * @return void
	 */
	public function init() {
		$this->elgg()->events->registerHandler('update:after', 'object', SyncNodeTitles::class);
		$this->elgg()->events->registerHandler('delete:after', 'object', DeleteNodes::class);
	}

	/**
	 * Executed during 'ready', 'system' event
	 *
	 * Allows the plugin to implement logic after all plugins are initialized
	 *
	 * @return void
	 */
	public function ready() {

	}

	/**
	 * Executed during 'shutdown', 'system' event
	 *
	 * Allows the plugin to implement logic during shutdown
	 *
	 * @return void
	 */
	public function shutdown() {

	}

	/**
	 * Executed when plugin is activated, after 'activate', 'plugin' event and before activate.php is included
	 *
	 * @return void
	 * @throws \DatabaseException
	 */
	public function activate() {
		$root = dirname(dirname(dirname(dirname(__FILE__))));
		run_sql_script($root . '/install/mysql.sql');
	}

	/**
	 * Executed when plugin is deactivated, after 'deactivate', 'plugin' event and before deactivate.php is included
	 *
	 * @return void
	 */
	public function deactivate() {

	}

	/**
	 * Registered as handler for 'upgrade', 'system' event
	 *
	 * Allows the plugin to implement logic during system upgrade
	 *
	 * @return void
	 */
	public function upgrade() {

	}
}