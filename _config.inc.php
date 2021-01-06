<?php

/* main configuration file for script */
define("_CONFIG_SITE_HOST_URL", "localhost/cloud");  /* site url host without the http:// and no trailing forward slash - i.e. www.mydomain.com or links.mydomain.com */
define("_CONFIG_SITE_FULL_URL", "localhost/cloud");  /* full site url without the http:// and no trailing forward slash - i.e. www.mydomain.com/links or the same as the _CONFIG_SITE_HOST_URL */

/* database connection details */
define("_CONFIG_DB_HOST", "localhost");  /* database host name */
define("_CONFIG_DB_NAME", "test");    /* database name */
define("_CONFIG_DB_USER", "root");    /* database username */
define("_CONFIG_DB_PASS", "");    /* database password */

/* set these to the main site host if you're using direct web server uploads/downloads to remote servers */
define("_CONFIG_CORE_SITE_HOST_URL", "localhost/cloud");  /* site url host without the http:// and no trailing forward slash - i.e. www.mydomain.com or links.mydomain.com */
define("_CONFIG_CORE_SITE_FULL_URL", "localhost/cloud");  /* full site url without the http:// and no trailing forward slash - i.e. www.mydomain.com/links or the same as the _CONFIG_SITE_HOST_URL */

define("_CONFIG_SCRIPT_VERSION", "1.5.2");    /* script version */

/* show database degug information on fail */
define("_CONFIG_DB_DEBUG", true);    /* this will display debug information when something fails in the DB - leave this as true if you're not sure */

/* which protcol to use, default is http */
define("_CONFIG_SITE_PROTOCOL", "http");

/* key used for encoding data within the site */
define("_CONFIG_UNIQUE_ENCRYPTION_KEY", "cWblI8r9bA3VHSYtk8sEHwQqNSq9xYVuiisnpiRydYBapzSfMFlouIZ5EtYIbIuUFFLkQ0tpvjDmbPXoMMD6YTcbj8urtIZtKQnlZV2P5SmGq6TEvigbudMmRPqLxxbc");

/* toggle demo mode */
define("_CONFIG_DEMO_MODE", false);    /* always leave this as false */

// Razorpay credential
// Live
define("RAZORPAY_KEY_ID", "rzp_live_7OESDm4wHHJSXU"); 
define("RAZORPAY_SECRET_KEY", "9kWDljri6XEbn3w7mkyFIQUH"); 
// Test
// define("RAZORPAY_KEY_ID", "rzp_test_aoVzfY8PCnKRZv"); 
// define("RAZORPAY_SECRET_KEY", "oU7OYVKorWK1PIzbUizyXpSg"); 