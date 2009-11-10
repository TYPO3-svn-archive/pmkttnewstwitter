<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

/*
t3lib_extMgm::addPageTSConfig('
tx_pmkttnewstwitter {
	twitterUser = 
	twitterPassword = 
	postField = bodytext
}
');
*/

/**
  *  Enable hook after saving page or content element
  */

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:pmkttnewstwitter/class.tx_pmkttnewstwitter.php:&tx_pmkttnewstwitter';
?>