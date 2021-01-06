<?php

/* main configuration file for script */
define("_CONFIG_SITE_HOST_URL", "<SITE_HOST_URL>");  /* site url host without the http:// and no trailing forward slash - i.e. www.mydomain.com or links.mydomain.com */
define("_CONFIG_SITE_FULL_URL", "<SITE_FULL_URL>");  /* full site url without the http:// and no trailing forward slash - i.e. www.mydomain.com/links or the same as the _CONFIG_SITE_HOST_URL */

/* database connection details */
define("_CONFIG_DB_HOST", "<DB_HOST>");  /* database host name */
define("_CONFIG_DB_NAME", "<DB_NAME>");    /* database name */
define("_CONFIG_DB_USER", "<DB_USER>");    /* database username */
define("_CONFIG_DB_PASS", "<DB_PASSWORD>");    /* database password */

/* set these to the main site host if you're using direct web server uploads/downloads to remote servers */
define("_CONFIG_CORE_SITE_HOST_URL", "<SITE_HOST_URL>");  /* site url host without the http:// and no trailing forward slash - i.e. www.mydomain.com or links.mydomain.com */
define("_CONFIG_CORE_SITE_FULL_URL", "<SITE_FULL_URL>");  /* full site url without the http:// and no trailing forward slash - i.e. www.mydomain.com/links or the same as the _CONFIG_SITE_HOST_URL */

define("_CONFIG_SCRIPT_VERSION", "1.5.2");    /* script version */

/* show database degug information on fail */
define("_CONFIG_DB_DEBUG", true);    /* this will display debug information when something fails in the DB - leave this as true if you're not sure */

/* which protcol to use, default is http */
define("_CONFIG_SITE_PROTOCOL", "<SITE_PROTOCOL>");

/* key used for encoding data within the site */
define("_CONFIG_UNIQUE_ENCRYPTION_KEY", "<SECRET_KEY>");

/* toggle demo mode */
define("_CONFIG_DEMO_MODE", false);    /* always leave this as false */