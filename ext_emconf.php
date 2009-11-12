<?php

########################################################################
# Extension Manager/Repository config file for ext: "pmkttnewstwitter"
#
# Auto generated 12-11-2009 14:25
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'PMK News Twitter',
	'description' => 'Adds Twitter entry when a tt_news record is created or edited. Optional link back to singleView page can be added to Twitter post.',
	'category' => 'be',
	'author' => 'Peter Klein',
	'author_email' => 'pmk@io.dk',
	'shy' => '',
	'dependencies' => 'tt_news,pagepath',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => 'tt_news',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.1.1',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.2.0-4.99.99',
			'php' => '5.2.0-10.0.0',
			'tt_news' => '2.5.0-0.0.0',
			'pagepath' => '0.1.4-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:10:{s:9:"ChangeLog";s:4:"de31";s:10:"README.txt";s:4:"ee2d";s:29:"class.tx_pmkttnewstwitter.php";s:4:"ca1b";s:12:"ext_icon.gif";s:4:"d447";s:17:"ext_localconf.php";s:4:"aa8f";s:15:"ext_php_api.dat";s:4:"844f";s:14:"ext_tables.php";s:4:"0b18";s:14:"ext_tables.sql";s:4:"e3d0";s:16:"locallang_db.xml";s:4:"7fad";s:14:"doc/manual.sxw";s:4:"3726";}',
	'suggests' => array(
	),
);

?>