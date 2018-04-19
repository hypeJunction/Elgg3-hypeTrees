<?php

namespace hypeJunction\Trees;

use Elgg\Application;
use Elgg\IntegrationTestCase;

/**
 * @group Plugins
 * @group Trees
 */
class TreeServiceTest extends IntegrationTestCase {

	/**
	 * @var TreeService
	 */
	protected $service;

	public function up() {
		$db = _elgg_services()->publicDb;
		$this->service = new TreeService($db);
	}

	public function down() {

	}

	public function testCanAddNodes() {

		$root = $this->createObject();
		$parent = $this->createObject();
		$node = $this->createObject();
		$orphan = $this->createObject();

		$this->service->addNode($root, $node, $parent, 10);
		$this->service->addNode($root, $orphan, null, 5);

		$this->assertNotFalse($this->service->isNode($root, $node));
		$this->assertNotFalse($this->service->isNode($root, $parent));
		$this->assertNotFalse($this->service->isNode($root, $orphan));

		$nodes = $this->service->getNodes($root);

		$this->assertContainsEntity($node, $nodes);
		$this->assertEquals(10, $this->service->getWeight($root, $node));
		$this->assertEquals(11, $this->service->getWeight($root, new \ElggObject()));
		$this->assertContainsEntity($orphan, $nodes);
		$this->assertEquals(5, $this->service->getWeight($root, $orphan));
		$this->assertEquals(11, $this->service->getWeight($root, new \ElggObject()));

		$child_nodes = $this->service->getNodes($root, $parent);
		$this->assertContainsEntity($node, $child_nodes);
		$this->assertNotContainsEntity($orphan, $child_nodes);

		$this->assertEqualEntities($parent, $this->service->getParentNode($root, $node));
		$this->assertEqualEntities($root, $this->service->getParentNode($root, $orphan));

		$ancestors = $this->service->getAncestors($root, $node);
		$this->assertEqualEntities([$root, $parent, $node], $ancestors);

		$orphan_ancestors = $this->service->getAncestors($root, $orphan);
		$this->assertEqualEntities([$root, $orphan], $orphan_ancestors);

		dump('Remove node 1');

		$this->service->removeNode($root, $parent);

		dump('Remove node 2');

		$this->assertFalse($this->service->isNode($root, $node));
		$this->assertFalse($this->service->isNode($root, $parent));

		$this->service->removeNode($root);
		$this->assertFalse($this->service->isNode($root, $orphan));

		dump('Remove node 3');
	}

	public function mapEntities($e) {
		return $e instanceof \ElggEntity ? $e->guid : 0;
	}

	public function assertEqualEntities($array1, $array2) {
		$array1 = (array) $array1;
		$array2 = (array) $array2;

		$map = [$this, 'mapEntities'];

		$array1 = array_map($map, $array1);
		$array2 = array_map($map, $array2);

		$this->assertEquals($array1, $array2);
	}

	public function assertContainsEntity($entity, array $array) {
		$map = [$this, 'mapEntities'];

		$array = array_map($map, $array);

		$this->assertContains($entity->guid, $array);
	}

	public function assertNotContainsEntity($entity, array $array) {
		$map = [$this, 'mapEntities'];

		$array = array_map($map, $array);

		$this->assertNotContains($entity->guid, $array);
	}
}