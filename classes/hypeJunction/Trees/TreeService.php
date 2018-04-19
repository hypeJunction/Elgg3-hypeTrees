<?php

namespace hypeJunction\Trees;

use DatabaseException;
use Elgg\Application\Database;
use Elgg\Database\Delete;
use Elgg\Database\Insert;
use Elgg\Database\QueryBuilder;
use Elgg\Database\Select;
use Elgg\Database\Update;
use Elgg\Di\ServiceFacade;
use ElggEntity;

class TreeService {

	use ServiceFacade;

	const TABLE = 'trees';

	/**
	 * @var Database
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param Database $db
	 */
	public function __construct(Database $db) {
		$this->db = $db;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function name() {
		return 'trees';
	}

	/**
	 * Checks if a node is a descendant of a root
	 *
	 * @param ElggEntity $root Root node
	 * @param ElggEntity $node Descendant node
	 *
	 * @return int|false
	 * @throws DatabaseException
	 */
	public function isNode(ElggEntity $root, ElggEntity $node) {

		$qb = Select::fromTable(self::TABLE);
		$qb->select('id')
			->where($qb->merge([
				$qb->compare('root_guid', '=', $root, ELGG_VALUE_GUID),
				$qb->compare('node_guid', '=', $node, ELGG_VALUE_GUID),
			]));

		$row = $this->db->getDataRow($qb);

		return $row ? $row->id : false;
	}

	/**
	 * Adds a new node
	 * Optionally, as a node of a parent within root's tree
	 *
	 * @param ElggEntity $root   Root node
	 * @param ElggEntity $node   Descendant node
	 * @param ElggEntity $parent Optional parent node for the descendant
	 * @param int        $weight Weight/priority of the descendant
	 *
	 * @return int|false
	 * @throws DatabaseException
	 */
	public function addNode(ElggEntity $root, ElggEntity $node, ElggEntity $parent = null, $weight = null) {

		if ($parent && ($node->guid == $parent->guid || $node->guid == $root->guid)) {
			return false;
		}

		if ($this->isNode($root, $node)) {
			$this->removeNode($root, $node);
		}

		if (!$parent) {
			$parent = $root;
		} else if (!$this->isNode($root, $parent)) {
			// Make sure parent is also in the tree
			$this->addNode($root, $parent);
		}

		if (!$weight) {
			$weight = $this->getWeight($root, $node);
		}

		$qb = Insert::intoTable(self::TABLE);
		$qb->values([
			'root_guid' => $qb->param($root->guid, ELGG_VALUE_INTEGER),
			'parent_guid' => $qb->param($parent->guid, ELGG_VALUE_INTEGER),
			'node_guid' => $qb->param($node->guid, ELGG_VALUE_INTEGER),
			'weight' => $qb->param($weight, ELGG_VALUE_INTEGER),
			'title' => $qb->param($node->title ? : '', ELGG_VALUE_STRING),
		]);

		return $this->db->insertData($qb);
	}

	/**
	 * Remove a descendant node
	 *
	 * @param ElggEntity $root Root node
	 * @param ElggEntity $node Descendant node
	 *
	 * @return bool
	 * @throws DatabaseException
	 */
	public function removeNode(ElggEntity $root, ElggEntity $node) {
		$qb = Delete::fromTable(self::TABLE);
		$qb->where($qb->merge([
			$qb->compare('root_guid', '=', $root, ELGG_VALUE_GUID),
			$qb->compare('node_guid', '=', $node, ELGG_VALUE_GUID),
		]));

		return $this->db->deleteData($qb);
	}

	/**
	 * Prepare ege* options
	 *
	 * @param array      $options Getter options
	 * @param ElggEntity $root    Root entity
	 * @param ElggEntity $parent  Optional parent node
	 *                            If set, will only return direct descendants of a parent
	 *                            Otherwise, will return all descendants of a root regardless of a parent node
	 *
	 * @return array
	 */
	public function getNodesQueryOptions(array $options = [], ElggEntity $root, ElggEntity $parent = null) {
		$defaults = [
			'limit' => 0,
		];

		$options = array_merge($defaults, $options);

		$options['wheres'][] = function (QueryBuilder $qb, $alias) use ($root, $parent) {
			$join_condition = $qb->compare("$alias.guid", '=', 'frs.node_guid');
			$qb->innerJoin($alias, self::TABLE, 'frs', $join_condition);

			$qb->addSelect('frs.*');

			//$qb->addGroupBy('e.guid');

			return $qb->merge([
				$parent ? $qb->compare('frs.parent_guid', '=', $parent, ELGG_VALUE_GUID) : null,
				$qb->compare('frs.root_guid', '=', $root, ELGG_VALUE_GUID),
				$qb->compare('frs.node_guid', '!=', $root, ELGG_VALUE_GUID),
			]);
		};

		return $options;
	}

	/**
	 * Get descendant nodes
	 *
	 * @param ElggEntity $root    Root entity
	 * @param ElggEntity $parent  Optional parent node
	 *                            If set, will only return direct descendants of a parent
	 *                            Otherwise, will return all descendants of a root regardless of a parent node
	 * @param array      $options Getter options
	 *
	 * @return ElggEntity[]
	 */
	public function getNodes(ElggEntity $root, ElggEntity $parent = null, array $options = []) {

		$options = $this->getNodesQueryOptions($options, $root, $parent);

		return elgg_get_entities($options) ? : [];
	}

	/**
	 * Get parent of a descendant node
	 *
	 * @param ElggEntity $root Root node
	 * @param ElggEntity $node Descendant node
	 *
	 * @return ElggEntity
	 */
	public function getParentNode(ElggEntity $root, ElggEntity $node) {

		$nodes = $this->getNodes($root);

		$parent_guid = $root->guid;

		foreach ($nodes as $n) {
			if ($n->guid == $node->guid) {
				$parent_guid = (int) $node->getVolatileData('select:parent_guid');
				break;
			}
		}

		foreach ($nodes as $n) {
			if ($n->guid == $parent_guid) {
				return $n;
			}
		}

		return $root;
	}

	/**
	 * Returns a weight of a node, if it's part of the tree
	 * Or next available weight for new nodes
	 *
	 * @param ElggEntity $root Root node
	 * @param ElggEntity $node Descendant node
	 *
	 * @return int
	 */
	public function getWeight(ElggEntity $root, ElggEntity $node) {
		$nodes = $this->getNodes($root);

		if (empty($nodes)) {
			return 1;
		}

		foreach ($nodes as $n) {
			if ($n->guid == $node->guid) {
				return (int) $n->getVolatileData('select:weight');
			}
		}

		$weights = array_map(function ($e) {
			return (int) $e->getVolatileData('select:weight');
		}, $nodes);

		$max = max($weights);

		return $max + 1;
	}

	/**
	 * Returns ancestors of the node (breadcrumbs style)
	 *
	 * @param ElggEntity $root Root node
	 * @param ElggEntity $node Descendant node
	 *
	 * @return ElggEntity[]
	 */
	public function getAncestors(ElggEntity $root, ElggEntity $node) {

		$ancestors = [$node];

		$parent = $this->getParentNode($root, $node);
		while ($parent && $parent->guid != $node->guid) {
			$ancestors[] = $parent;
			$parent = $this->getParentNode($root, $parent);
		}

		return array_reverse($ancestors);
	}

	/**
	 * Get roots a node belongs to
	 *
	 * @param ElggEntity $entity Entity
	 * @param array      $options ege* options
	 * @return ElggEntity[]|int|false
	 */
	public function getRoots(ElggEntity $entity, array $options = []) {

		$options['wheres'][] = function (QueryBuilder $qb, $alias) use ($entity) {
			$join_condition = $qb->compare("$alias.guid", '=', 'frs.root_guid');
			$qb->innerJoin($alias, self::TABLE, 'frs', $join_condition);

			$qb->addSelect('frs.*');

			return $qb->merge([
				$qb->compare('frs.node_guid', '=', $entity, ELGG_VALUE_GUID),
			]);
		};

		return elgg_get_entities($options);
	}

	/**
	 * Sync node title in the trees table
	 *
	 * @param ElggEntity $node Node
	 *
	 * @return void
	 * @throws DatabaseException
	 */
	public function syncTitle(ElggEntity $node) {

		$qb = Update::table(self::TABLE);
		$qb->set('title', $qb->param($node->title, ELGG_VALUE_STRING))
			->where($qb->compare('node_guid', '=', $node, ELGG_VALUE_GUID));

		$this->db->updateData($qb);
	}

	/**
	 * Remove nodes from tree
	 *
	 * @param ElggEntity $node Node
	 *
	 * @return void
	 * @throws DatabaseException
	 */
	public function delete(ElggEntity $node) {

		$qb = Delete::fromTable(self::TABLE);
		$qb->where($qb->merge([
			$qb->compare('root_guid', '=', $node, ELGG_VALUE_GUID),
			$qb->compare('parent_guid', '=', $node, ELGG_VALUE_GUID),
			$qb->compare('node_guid', '=', $node, ELGG_VALUE_GUID),
		], 'OR'));

		$this->db->deleteData($qb);
	}
}