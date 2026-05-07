<?php

namespace hypeJunction\Trees;

use DatabaseException;
use Elgg\Event;

/**
 * SyncNodeTitles class.
 */
class SyncNodeTitles {

	/**
	 * Sync entity title on save
	 *
	 * @param Event $event Event
	 *
	 * @return void
	 * @throws DatabaseException
	 */
	public function __invoke(Event $event) {

		$entity = $event->getObject();
		if (!$entity instanceof \ElggEntity) {
			return;
		}

		\hypeJunction\Trees\TreeService::instance()->syncTitle($entity);
	}
}
