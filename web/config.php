<?php declare(strict_types=1);

const REDIS_HOST = 'localhost';
const REDIS_PORT = 6379;
const REDIS_DB_INDEX = 0;
const REDIS_PASSWORD = null;

/** When no URL params are specified, show results for this predicate, e. g. 'instance-of' */
const HEXASTORE_FALLBACK_PREDICATE = null;
const HEXASTORE_OBJECT_KEY_PREFIX = 'hexastore:objects:';
const HEXASTORE_TRIPLES_KEY = 'hexastore:triples';
const HEXASTORE_TRIPLE_SEPARATOR = ':';
const HEXASTORE_TRIPLE_ESCAPE = '#';
