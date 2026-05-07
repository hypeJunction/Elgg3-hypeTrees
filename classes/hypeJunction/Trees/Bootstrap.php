<?php

namespace hypeJunction\Trees;

use Elgg\PluginBootstrap;

/**
 * Bootstrap class.
 */
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
		// (4.x) run_sql_script() was removed. Execute the install SQL
		// directly via the doctrine connection. The script consists of
		// a single CREATE TABLE IF NOT EXISTS statement so running it
		// repeatedly is safe. The dbprefix is substituted in PHP
		// because the raw SQL template uses the 'prefix_' literal.
		$root = dirname(dirname(dirname(dirname(__FILE__))));
		$sql = file_get_contents($root . '/install/mysql.sql');
		if ($sql === false) {
			return;
		}

		$dbprefix = _elgg_services()->config->dbprefix;
		$sql = str_replace('prefix_', $dbprefix, $sql);
		$conn = _elgg_services()->db->getConnection('write');
		foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
			$conn->executeStatement($statement);
		}
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
