<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

// --- Get extension configuration ---
$extConf = array();
if ( strlen($_EXTCONF) ) {
	$extConf = unserialize($_EXTCONF);
}


if (TYPO3_MODE === 'BE') {

	// register bootstrap handler
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
		TCEMAIN.linkHandler {
			bootstrap {
				handler = Laxap\\BootstrapLinks\\Hooks\\BootstrapLinkHandler
				label = LLL:EXT:bootstrap_links/Resources/Private/Language/locallang.xlf:tabtitle
				displayAfter = mail
				scanAfter = mail
				#configuration {
				#	customConfig = passed to the handler
				#}
			}
		}
	');
}

?>