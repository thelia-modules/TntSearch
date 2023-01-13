LOCK TABLES `tnt_search_index` WRITE;
/*!40000 ALTER TABLE `tnt_search_index` DISABLE KEYS */;

INSERT INTO `tnt_search_index` (`id`, `index`, `is_translatable`, `created_at`, `updated_at`)
VALUES
    (1,'customer',0,NULL,NULL),
    (2,'order',0,NULL,NULL),
    (3,'brand',1,NULL,NULL),
    (4,'content',1,NULL,NULL),
    (5,'category',1,NULL,NULL),
    (6,'folder',1,NULL,NULL),
    (7,'product',1,NULL,NULL);

/*!40000 ALTER TABLE `tnt_search_index` ENABLE KEYS */;
UNLOCK TABLES;
