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
	*   44: class tx_pmkttnewstwitter
	*   56:     function processDatamap_afterDatabaseOperations($status, $table, $id, &$fieldArray, &$reference)
	*   80:     function twit($twitter_data)
	*  104:     function getConfig($pageId)
	*
	* TOTAL FUNCTIONS: 3
	* (This index is automatically created/updated by the extension "extdeveval")
	*
	*/
	 
	/**
	* Class for posting tt_news entries on twitter.
	*
	*/
	class tx_pmkttnewstwitter {
		 
		/**
		* Main function. Hook from t3lib/class.t3lib_tcemain.php
		*
		* @param string  $status: Status of the current operation, 'new' or 'update
		* @param string  $table: The table currently processing data for
		* @param string  $id: The record uid currently processing data for, [integer] or [string] (like 'NEW...')
		* @param array  $fieldArray: The field array of a record
		* @param object  $reference: reference to parent object
		* @return void
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
				$this->uid = ($status == 'new') ?$reference->substNEWwithIDs[$id] : $id;
				$this->status = ($status == 'new') ? 1 : 2;
				$msg = htmlspecialchars(strip_tags($fieldArray[$this->conf['postField']]));
				$msg = strlen($msg) > 130 ? substr($msg, 0, 130).'...': $msg;
				$this->twit($msg);
			}
		}
		 
		/**
		* Post data on Twitter
		*
		* @param string  $twitter_data: Data to post on twitter.
		* @return void
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
		* Returns config array with plugin options from Page TSConfig and Extension Config
		*
		* @param integer  $pageId: id of pages record
		* @return array  Array of config options.
		*/
		function getConfig($pageId) {
			$PageTSconfig = t3lib_BEfunc::getPagesTSconfig($pageId);
			$conf = $PageTSconfig['tx_pmkttnewstwitter.'];
			$conf['twitterUser'] = htmlspecialchars($conf['twitterUser']);
			$conf['twitterPassword'] = htmlspecialchars($conf['twitterPassword']);
			$conf['postField'] = $conf['postField'] ? $conf['postField'] : 'bodytext';
			return $conf;
		}
	 
	}

	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pmkttnewstwitter/class.tx_pmkttnewstwitter.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pmkttnewstwitter/class.tx_pmkttnewstwitter.php']);
	}
?>
