<?php
	/***************************************************************
	*  Copyright notice
	*
	*  (c) 2009 Peter Klein <peter@umloud.dk>
	*  All rights reserved
	*
	*  This script is part of the TYPO3 project. The TYPO3 project is
	*  free software; you can redistribute it and/or modify
	*  it under the terms of the GNU General Public License as published by
	*  the Free Software Foundation; either version 2 of the License, or
	*  (at your option) any later version.
	*
	*  The GNU General Public License can be found at
	*  http://www.gnu.org/copyleft/gpl.html.
	*
	*  This script is distributed in the hope that it will be useful,
	*  but WITHOUT ANY WARRANTY; without even the implied warranty of
	*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	*  GNU General Public License for more details.
	*
	*  This copyright notice MUST APPEAR in all copies of the script!
	***************************************************************/

	/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   55: class tx_pmkttnewstwitter
 *   67:     function processDatamap_afterDatabaseOperations($status, $table, $id, &$fieldArray, &$reference)
 *  100:     function twit($twitter_data)
 *  125:     function makeSingleLink()
 *  147:     function init_tmpl($pageId,$template_uid=0)
 *  167:     function getNewsCategory($uid)
 *  188:     function createTinyUrl($longURL)
 *  199:     function getConfig($pageId)
 *
 * TOTAL FUNCTIONS: 7
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

/* Classes required for creating a tmpl object */
require_once(PATH_t3lib."class.t3lib_extobjbase.php");
require_once(PATH_t3lib."class.t3lib_tsparser_ext.php");
require_once(PATH_t3lib."class.t3lib_page.php");

require_once(t3lib_extMgm::extPath('pagepath').'class.tx_pagepath_api.php');

	/**
	 * Class for posting tt_news entries on twitter.
	 *
	 */
	class tx_pmkttnewstwitter {

		/**
		 * Main function. Hook from t3lib/class.t3lib_tcemain.php
		 *
		 * @param	string		$status: Status of the current operation, 'new' or 'update
		 * @param	string		$table: The table currently processing data for
		 * @param	string		$id: The record uid currently processing data for, [integer] or [string] (like 'NEW...')
		 * @param	array		$fieldArray: The field array of a record
		 * @param	object		$reference: reference to parent object
		 * @return	void
		 */
		function processDatamap_afterDatabaseOperations($status, $table, $id, &$fieldArray, &$reference) {

			// Return if not the tt_news table or if status is not "new" or "update".
			if ($table != 'tt_news' || !($status == 'new' || $status == 'update')) return;
			// Return if "tx_pmkttnewstwitter_notwitter" field is enabled
			if ($fieldArray['tx_pmkttnewstwitter_notwitter'] || (!isset($fieldArray['tx_pmkttnewstwitter_notwitter']) && $reference->checkValue_currentRecord['tx_pmkttnewstwitter_notwitter'])) return;
			// Get config options.
			$this->conf = $this->getConfig($reference->checkValue_currentRecord['pid']);
			// Return if twitter username or password is missing
			if ($this->conf['twitterUser'] == '' || $this->conf['twitterPassword'] == '') return;
			if (isset($fieldArray[$this->conf['postField']])) {
				$singleUrl = '';
				if ($this->conf['linkBack']) {
					$this->uid = ($status == 'new') ?$reference->substNEWwithIDs[$id] : $id;
					$this->status = ($status == 'new') ? 1 : 2;
					$this->tmpl = $this->init_tmpl($reference->checkValue_currentRecord['pid'],0);
					$this->ttnewsConf = $this->tmpl->setup['plugin.']['tt_news.'];
					$this->ttnewsCat = $this->getNewsCategory($this->uid);
					$singleUrl = ' '.$this->createTinyUrl($this->makeSingleLink());
				}
				$singleUrlLen = strlen($singleUrl);
				$msg = htmlspecialchars(strip_tags($fieldArray[$this->conf['postField']]));
				$msg = (strlen($msg)+$singleUrlLen > 136) ? substr($msg, 0, 136-$singleUrlLen).'...': $msg;
				$this->twit($msg.$singleUrl);
			}
		}

		/**
		 * Post data on Twitter
		 *
		 * @param	string		$twitter_data: Data to post on twitter.
		 * @return	void
		 */
		function twit($twitter_data) {
			$twitter_api_url = 'http://twitter.com/statuses/update.xml';
			$twitter_user = $this->conf['twitterUser'];
			$twitter_password = $this->conf['twitterPassword'];
			$ch = curl_init($twitter_api_url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, 'status='.$twitter_data);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERPWD, "{$twitter_user}:{$twitter_password}");
			$twitter_data = curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if ($httpcode != 200) {
				$reference->log('tt_news', $this->uid, $this->status, 0, 1, "pmkttnewstwitter: Something went wrong, and the tweet wasn't posted correctly.");
			}
		}

		/**
		 * Make link to tt_news singleview
		 *
		 * @param	void
		 * @return	string		typolink pointing to singleview page.
		 */
		function makeSingleLink() {
			//  Overwrite the singlePid from config-array with a singlePid given from the first entry in $this->categories
			if ($this->ttnewsConf['useSPidFromCategory'] && is_array($this->ttnewsCat)) {
				$catSPid = $this->ttnewsCat;
			}
			$singlePid = $catSPid['single_pid'] ? $catSPid['single_pid'] : $this->ttnewsConf['singlePid'];
			$parameters = array(
				'tx_ttnews' => array(
					'tt_news' => $this->uid,
					'backPid' => ($this->ttnewsConf['dontUseBackPid'] ? null : $this->ttnewsConf['backPid'])
				));
			$pagepath = tx_pagepath_api::getPagePath($singlePid, $parameters);
			return $pagepath;
		}

		/**
		 * Initialize TMPL object, so we can access Typoscript setups from BE.
		 *
		 * @param	integer		$pageId: tt_news uid.
		 * @param	integer		$template_uid: ?? Not used.
		 * @return	array		$tmpl: TMPL object.
		 */
		function init_tmpl($pageId,$template_uid=0)	{
			//global $tmpl;
			$tmpl = t3lib_div::makeInstance("t3lib_tsparser_ext");	// Defined global here!
			$tmpl->tt_track = 0;	// Do not log time-performance information
			$tmpl->init();
			// Gets the rootLine
			$sys_page = t3lib_div::makeInstance("t3lib_pageSelect");
			$rootLine = $sys_page->getRootLine($pageId);
			// This generates the constants/config + hierarchy info for the template.
			$tmpl->runThroughTemplates($rootLine,$template_uid);
			$tmpl->generateConfig();
			return $tmpl;
		}

		/**
		 * Get current tt_news category
		 *
		 * @param	integer		$uid: tt_news uid.
		 * @return	array		$cat: category array.
		 */
		function getNewsCategory($uid) {
			$cat = '';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query (
				'tt_news_cat.*,tt_news_cat_mm.sorting AS mmsorting',
				'tt_news',
				'tt_news_cat_mm',
				'tt_news_cat',
				' AND tt_news_cat_mm.uid_local='.intval($uid).' AND tt_news_cat.deleted=0'.$this->enableCatFields
				);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
				$cat = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			}
			return $cat;
		}

		/**
		 * Shorten long url by converting it using the tinyurl.com API
		 *
		 * @param	string		$longURL: Long URL.
		 * @return	string		tinyurl version of long URL.
		 */
		function createTinyUrl($longURL) {
			$tinyURL = @file_get_contents("http://tinyurl.com/api-create.php?url=".$longURL);
			return $tinyURL ? $tinyURL : $longURL;
		}

		/**
		 * Returns config array with plugin options from Page TSConfig and Extension Config
		 *
		 * @param	integer		$pageId: id of pages record
		 * @return	array		Array of config options.
		 */
		function getConfig($pageId) {
			$PageTSconfig = t3lib_BEfunc::getPagesTSconfig($pageId);
			debug($PageTSconfig,'PageTSconfig');
			$conf = $PageTSconfig['tx_pmkttnewstwitter.'];
			$conf['twitterUser'] = htmlspecialchars($conf['twitterUser']);
			$conf['twitterPassword'] = htmlspecialchars($conf['twitterPassword']);
			$conf['postField'] = $conf['postField'] ? $conf['postField'] : 'bodytext';
			$conf['linkBack'] = intval($conf['linkBack']);
			return $conf;
		}

	}

	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pmkttnewstwitter/class.tx_pmkttnewstwitter.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pmkttnewstwitter/class.tx_pmkttnewstwitter.php']);
	}
?>
