CREATE TABLE IF NOT EXISTS `prefix_trees` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `root_guid` bigint(20) unsigned NOT NULL,
  `parent_guid` bigint(20) unsigned NOT NULL,
  `node_guid` bigint(20) unsigned NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `title` longtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `node` (`root_guid`, `node_guid`)
) ENGINE=InnoDb AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;