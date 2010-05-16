<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}
$tempColumns = array (
	'tx_pmkttnewstwitter_notwitter' => array (		
		'exclude' => 1,		
		'label' => 'LLL:EXT:pmkttnewstwitter/locallang_db.xml:tt_news.tx_pmkttnewstwitter_notwitter',		
		'config' => array (
			'type' => 'check',
		)
	),
);

t3lib_div::loadTCA('tt_news');
t3lib_extMgm::addTCAcolumns('tt_news',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('tt_news','tx_pmkttnewstwitter_notwitter;;;;1-1-1','','after:type');
// initalize "context sensitive help" (csh)
t3lib_extMgm::addLLrefForTCAdescr('tt_news','EXT:pmkttnewstwitter/locallang_csh.php');

?>