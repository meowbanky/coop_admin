<?php
// config/tcpdf_config.php

// DOCUMENT_ROOT is the root directory of your web server
if (!defined('K_PATH_MAIN')) {
    define('K_PATH_MAIN', dirname(__FILE__) . '/vendor/tcpdf/');
}

// URL path to tcpdf installation folder (http://localhost/tcpdf/)
if (!defined('K_PATH_URL')) {
    define('K_PATH_URL', '/vendor/tcpdf/');
}

// Path for PDF fonts
if (!defined('K_PATH_FONTS')) {
    define('K_PATH_FONTS', K_PATH_MAIN . 'fonts/');
}

// Default images directory
if (!defined('K_PATH_IMAGES')) {
    define('K_PATH_IMAGES', K_PATH_MAIN . 'images/');
}

// Cache directory for temporary files
if (!defined('K_PATH_CACHE')) {
    define('K_PATH_CACHE', K_PATH_MAIN . 'cache/');
}

// Default layout settings
define('PDF_PAGE_FORMAT', 'A4');
define('PDF_PAGE_ORIENTATION', 'P');
define('PDF_CREATOR', 'TASCE Payroll System');
define('PDF_AUTHOR', 'TASCE');
define('PDF_UNIT', 'mm');
define('PDF_MARGIN_HEADER', 5);
define('PDF_MARGIN_FOOTER', 10);
define('PDF_MARGIN_TOP', 27);
define('PDF_MARGIN_BOTTOM', 25);
define('PDF_MARGIN_LEFT', 15);
define('PDF_MARGIN_RIGHT', 15);
define('PDF_FONT_NAME_MAIN', 'helvetica');
define('PDF_FONT_SIZE_MAIN', 10);
?>