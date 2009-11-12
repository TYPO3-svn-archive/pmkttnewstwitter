<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

/**
  *  Enable hook after saving tt_news element
  */

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:pmkttnewstwitter/class.tx_pmkttnewstwitter.php:&tx_pmkttnewstwitter';
?>