<?php

########################################################################
# Extension Manager/Repository config file for ext "pmkttnewstwitter".
#
# Auto generated 11-04-2010 11:56
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'PMK News Twitter',
	'description' => 'Adds Twitter entry when a tt_news record is created or edited. Optional link back to tt_news singleView page can be added to Twitter post.',
	'category' => 'be',
	'shy' => 0,
	'version' => '0.1.7',
	'dependencies' => 'tt_news',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => 'tt_news',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Peter Klein',
	'author_email' => 'pmk@io.dk',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.2.0-4.99.99',
			'php' => '5.2.0-10.0.0',
			'tt_news' => '2.5.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'pagepath' => '0.1.4-0.0.0',
		),
	),
	'_md5_values_when_last_written' => 'a:11:{s:9:"ChangeLog";s:4:"de31";s:10:"README.txt";s:4:"ee2d";s:29:"class.tx_pmkttnewstwitter.php";s:4:"f1e4";s:12:"ext_icon.gif";s:4:"d447";s:17:"ext_localconf.php";s:4:"4372";s:15:"ext_php_api.dat";s:4:"bd83";s:14:"ext_tables.php";s:4:"4f01";s:14:"ext_tables.sql";s:4:"e3d0";s:17:"locallang_csh.xml";s:4:"d6d6";s:16:"locallang_db.xml";s:4:"9e31";s:14:"doc/manual.sxw";s:4:"5f46";}',
	'suggests' => array(
	),
);

?>