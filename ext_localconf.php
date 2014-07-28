<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

// --- Get extension configuration ---
$extConf = array();
if ( strlen($_EXTCONF) ) {
	$extConf = unserialize($_EXTCONF);
}

// --- Hooks in sysext/core/Classes/Html/RteHtmlParser.php ---
// Add linkhandler
// additional available hooks: removeParams($parameters, $this), modifyParamsLinksDb($p, $this), modifyParamsLinksRte($p, $this)
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['bootstrap']  = 'EXT:bootstrap_links/Classes/Hooks/LinkHandler.php:&Simplicity\BootstrapLinks\Hooks\LinkHandler';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksRte_PostProc'][] = 'EXT:bootstrap_links/Classes/Hooks/LinkHandler.php:&Simplicity\BootstrapLinks\Hooks\LinkHandler';
// --- Hook in sysext/rtehtmlarea/Classes/BrowseLinks.php
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']['browseLinksHook'][] = 'EXT:bootstrap_links/Classes/Hooks/ElementBrowser.php:Simplicity\BootstrapLinks\Hooks\ElementBrowser';
// --- Hook in sysext/recordlist/Classes/Browser/ElementBrowser.php
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'][] = 'EXT:bootstrap_links/Classes/Hooks/ElementBrowser.php:Simplicity\BootstrapLinks\Hooks\ElementBrowser';

?>