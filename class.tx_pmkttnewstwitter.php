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
	 *   54: class tx_pmkttnewstwitter
	 *   66:     function processDatamap_afterDatabaseOperations($status, $table, $id, &$fieldArray, &$reference)
	 *  125:     function twit($twitter_data)
	 *  152:     function makeSingleLink()
	 *  190:     function init_tmpl($pageId,$template_uid=0)
	 *  210:     function getNewsCategory($uid)
	 *  233:     function createShortUrl($longURL,$login='',$apiKey='')
	 *  263:     function getConfig($pageId)
	 *  283:     function isUTF8($str)
	 *
	 * TOTAL FUNCTIONS: 8
	 * (This index is automatically created/updated by the extension "extdeveval")
	 *
	 */

/* Classes required for creating a tmpl object */
require_once(PATH_t3lib."class.t3lib_extobjbase.php");
require_once(PATH_t3lib."class.t3lib_tsparser_ext.php");
require_once(PATH_t3lib."class.t3lib_page.php");

require_once dirname(__FILE__) . '/res/twitteroauth.php';

	/**
	 * Class for posting tt_news entries on twitter.
	 *
	 */
	class tx_pmkttnewstwitter {

		function silentDebug($debugdata) {
			if (!empty($this->conf['twitterdebug']))
				file_put_contents(dirname(__FILE__). '/'. date('is'). '.txt', print_r($debugdata, 1). "\n", FILE_APPEND);
		}

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
			// Return if "hidden" field is enabled
			if ($fieldArray['hidden'] || (!isset($fieldArray['hidden']) && $reference->checkValue_currentRecord['hidden'])) return;
			// Get config options.
			$this->conf = $this->getConfig($reference->checkValue_currentRecord['pid']);

			// Return if twitter username or password is missing
			if ($this->conf['twitterUser'] == '' || $this->conf['twitterPassword'] == '') return;

			##################################################
			# HK # 2011-05-18, 2011-11-18
			# do initial TwitterOAuth
			##################################################
			if ($this->conf['doInitialTwitterOAuthOnTestNews'])
			{
				if ($this->conf['twitteraccesstoken'] == '' || $this->conf['twitteraccesstokensecret'] == '')
				{
					$twitter = new TwitterOAuth(
						$this->conf['twitterconsumerkey'],
						$this->conf['twitterconsumersecret']
						);

					$aRequestToken = $twitter->getRequestToken();
					$oauth_token		= $aRequestToken['oauth_token'];
					$oauth_token_secret	= $aRequestToken['oauth_token_secret'];

					$sAuthorizeURL = $twitter->getAuthorizeURL($oauth_token);


					$http_host		= $_SERVER["HTTP_HOST"];
					#$extRelPath		= t3lib_extMgm::extRelPath('pmkttnewstwitter');
					$extPath		= t3lib_extMgm::extPath('pmkttnewstwitter');
					$siteRelPath		= t3lib_extMgm::siteRelPath('pmkttnewstwitter');
					$typo3DocumentRoot 	= t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT');

					$path_ext		= $siteRelPath;
					$thisExtRoot		= "http://$http_host/$path_ext";

					##################################################
					# if Typo3 called through SSL-Proxy
					# by BG 2011-07-07
					##################################################
					if (isset($_SERVER['HTTP_X_FORWARDED_SERVER']))
					{
						$reverseProxyPrefixSSL	= $TYPO3_CONF_VARS['SYS']['reverseProxyPrefixSSL'];

						$path_ext = substr($extPath, strlen($typo3DocumentRoot), strlen($extPath));
						$path_ext = (substr($path_ext, 0, 1) != '/' ? '/' . $path_ext : $path_ext);
						$path_ext = $reverseProxyPrefixSSL . $path_ext;

						$thisExtRoot	= "https://$http_host/$path_ext";
					}


					$pmkttnewstwitter_twitter_oauth_call = $thisExtRoot . "pmkttnewstwitter_twitter_oauth_call.php"
						. "?oauth_token_secret=" . urlencode($oauth_token_secret)
						. "&authorize_url=" . urlencode($sAuthorizeURL);

					$this->silentDebug($aRequestToken);
					$this->silentDebug($sAuthorizeURL);
					$this->silentDebug($pmkttnewstwitter_twitter_oauth_call);

					header("Location: $pmkttnewstwitter_twitter_oauth_call");
					die();
				}
			}


			$fieldsInArray = false;
			$fields = t3lib_div::trimExplode(',', $this->conf['postField']);
			foreach ($fields as $field) {
				if (isset($fieldArray[$field])) {
					$fieldsInArray = true;
					break;
				}
			}

			$this->uid = ($status == 'new') ?$reference->substNEWwithIDs[$id] : $id;
			if ($fieldsInArray) {
				$this->reference = $reference;
				$this->status = ($status == 'new') ? 1 : 2;
				$singleUrl = '';

				if ($this->conf['linkBack']) {
					$this->tmpl = $this->init_tmpl($reference->checkValue_currentRecord['pid'],0);
					$this->ttnewsConf = $this->tmpl->setup['plugin.']['tt_news.'];
					$this->ttnewsCat = $this->getNewsCategory($this->uid);
					$singleUrl = ' '.$this->createShortUrl($this->makeSingleLink(),$this->conf['bitlyLogin'],$this->conf['bitlyApiKey']);
				}

				$singleUrlLen = strlen($singleUrl);
				$postFields = t3lib_div::trimExplode(',', $this->conf['postField']);
				$msg = '';
				$actualFields = $reference->datamap[$table][$id];
				foreach ($postFields as $postField) {
					$msg .= htmlspecialchars_decode(strip_tags($actualFields[$postField]),ENT_QUOTES);
					if ($postField == 'title') $msg .= ': ';
				}
				$msg = preg_replace('/\s\s+/', ' ', $msg);
				// Translate chars that the Twitter API doesn't like. (<,> and &)
				//debug($reference->checkValue_currentRecord['sys_language_uid']);
				// $andLabel = $GLOBALS['LANG']->getLLL('tx_pmkttnewstwitter_and',$GLOBALS['LANG']->readLLfile(t3lib_extMgm::extPath('pmkttnewstwitter').'locallang_db.xml'));
				$andLabel = $GLOBALS['LANG']->sL('LLL:EXT:pmkttnewstwitter/locallang_db.xml:tx_pmkttnewstwitter_and');
				$msg = str_replace(array('<','>','&'), array(' ',' ',' '.$andLabel.' '), $msg);
				$msg = $this->isUTF8($msg) ? $msg : utf8_encode($msg);
				$msg = (strlen($msg)+$singleUrlLen > 136) ? substr($msg, 0, 136-$singleUrlLen).'...': $msg;
				$msg .= "\n";

				if ($this->conf['useKeywordsAsHashTags']) {
					$keywords = $fieldArray['keywords'] ? $fieldArray['keywords'] : $reference->checkValue_currentRecord['keywords'];
					if ($keywords) {
						$keywords = $this->isUTF8($keywords) ? $keywords : utf8_encode($keywords);
						$keywords = t3lib_div::trimExplode(',', $keywords, 1);
						foreach ($keywords as $keyword) {
							if (strlen($msg)+strlen(' #'.$keyword)+$singleUrlLen > 137) {
								break;
							}
							else {
								$msg.=' #'.$keyword;
							}
						}
					}
				}

				$this->twit($msg.$singleUrl);
			}

			if ($this->conf['setToNoTweet']) {
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'tt_news',
					'uid = ' . $this->uid,
					array('tx_pmkttnewstwitter_notwitter' => '1')
				);
			}
		}

		/**
		 * Post data on Twitter using Curl.
		 *
		 * @param	string		$twitter_data: Data to post on twitter.
		 * @return	void
		 */
		function twit_basic($twitter_data) {
			$twitter_api_url = 'http://twitter.com/statuses/update.xml';
			$twitter_user = $this->conf['twitterUser'];
			$twitter_password = $this->conf['twitterPassword'];
			$ch = curl_init($twitter_api_url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, 'status='.$twitter_data);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
			//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_TRANSFERTEXT, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERPWD, "{$twitter_user}:{$twitter_password}");
			$twitter_data = curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if ($httpcode != 200) {
				$this->reference->log('tt_news', $this->uid, $this->status, 0, 1, "pmkttnewstwitter: Errorcode: ".$httpcode." Something went wrong, and the tweet wasn't posted correctly.");
			}
		}

		function twit($twitter_data) {
			$twitter = new TwitterOAuth(
				$this->conf['twitterconsumerkey'],
				$this->conf['twitterconsumersecret'],
				$this->conf['twitteraccesstoken'],
				$this->conf['twitteraccesstokensecret']
			);
			$reply= $twitter->post(
				'statuses/update',
				array('status' => $twitter_data)
			);

			$this->silentDebug($reply);
		}

		/**
		 * Make link to tt_news singleview
		 *
		 * @param	void
		 * @return	string		typolink pointing to singleview page.
		 */
		function makeSingleLink() {
			//  Overwrite the singlePid from config-array with a singlePid given from $this->ttnewsCat
			if ($this->ttnewsConf['useSPidFromCategory'] && is_array($this->ttnewsCat)) {
				$catSPid = $this->ttnewsCat;
			}
			$singlePid = $catSPid['single_pid'] ? intval($catSPid['single_pid']) : intval($this->ttnewsConf['singlePid']);
			$this->silentDebug("makeSingleLink(): \$singlePid=$singlePid\n");
			if (!$singlePid) return '';
			$parameters['tx_ttnews']['tt_news'] = $this->uid;
			if (!$this->ttnewsConf['dontUseBackPid'] && $this->ttnewsConf['backPid']) {
				$parameters['tx_ttnews']['backPid'] = $this->ttnewsConf['backPid'];
			}

			// Include PagePath API if available:
			if (t3lib_extMgm::isLoaded('pagepath') && !$this->conf['noPagePath']) {
				require_once(t3lib_extMgm::extPath('pagepath').'class.tx_pagepath_api.php');
				$url = tx_pagepath_api::getPagePath($singlePid, $parameters);
			}
			else {
				$url = 'index.php?id='.$singlePid.'&'.http_build_query($parameters, '', '&').'&no_cache=1';
				if ($this->domain) {
					$url = 'http://'.$this->domain.'/'.$url;
				}
				else {
					$url = t3lib_div::getIndpEnv( 'TYPO3_SITE_URL' ).$url;
				}
			}

			$this->silentDebug("makeSingleLink(): \$url=$url\n");
			return $url;
		}

		/**
		 * Initialize TMPL object, so we can access Typoscript setups from BE.
		 *
		 * @param	integer		$pageId: tt_news uid.
		 * @param	integer		$template_uid: ?? Not used.
		 * @return	array		$tmpl: TMPL object.
		 */
		function init_tmpl($pageId,$template_uid=0)	{
			$tmpl = t3lib_div::makeInstance("t3lib_tsparser_ext");
			$tmpl->tt_track = 0;	// Do not log time-performance information
			$tmpl->init();
			// Gets the rootLine
			$sys_page = t3lib_div::makeInstance("t3lib_pageSelect");
			$rootLine = $sys_page->getRootLine($pageId);
			// Pickup the domain record while we have a rootline.
			$this->domain =	t3lib_BEfunc::firstDomainRecord($rootLine);
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
		 * Shorten long url by converting it using tinyurl.com or bit.ly API
		 *
		 * @param	string		$longURL: Long URL.
		 * @param	string		$login: Optional bit.ly login.
		 * @param	string		$apiKey: Optional bit.ly API key.
		 * @return	string		Short version of long URL.
		 */
		function createShortUrl($longURL,$login='',$apiKey='') {
			// tinyurl.com
			$url = 'http://tinyurl.com/api-create.php?url='.$longURL;
			// Bit.ly
			if ($login!='' && $apiKey!='') {
				$url = 'http://api.bit.ly/shorten?version=2.0.1&longUrl='.urlencode($longURL).'&login='.$login.'&apiKey='.$apiKey.'&format=json&history=1';
			}
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL, $url);
			curl_setopt($ch,CURLOPT_HEADER,false);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			$shortURL = @curl_exec($ch);
			curl_close($ch);

			$this->silentDebug("createShortUrl(): \$shortURL=$shortURL\n");
			// Bit.ly
			if ($login!='' && $apiKey!='') {
				$obj = json_decode($shortURL, true);
				$shortURL = ($obj['statusCode'] =='OK') ? $obj['results'][$longURL]['shortUrl'] : '';
			}
			$shortURL = $shortURL ? $shortURL : $longURL;
			// Replace "http://www." with "www.", saving extra 7 bytes/chars.
			$shortURL = preg_replace('%^((http://)(www\.))%', '$3', $shortURL);

			$this->silentDebug("createShortUrl(): \$shortURL=$shortURL\n");
			return $shortURL;
		}

		/**
		 * Returns config array with plugin options from Page TSConfig and Extension Config
		 *
		 * @param	integer		$pageId: id of pages record
		 * @return	array		Array of config options.
		 */
		function getConfig($pageId) {
			$PageTSconfig = t3lib_BEfunc::getPagesTSconfig($pageId);
			$conf = $PageTSconfig['tx_pmkttnewstwitter.'];
			$conf['twitterUser'] = trim($conf['twitterUser']);
			$conf['twitterPassword'] = trim($conf['twitterPassword']);
			$conf['postField'] = $conf['postField'] ? trim($conf['postField']) : 'title';
			$conf['linkBack'] = intval($conf['linkBack']);
			$conf['useKeywordsAsHashTags'] = $conf['useKeywordsAsHashTags'] ? 1 : 0;
			$conf['noPagePath'] = $conf['noPagePath'] ? 1 : 0;
			$conf['bitlyLogin'] = trim($conf['bitlyLogin']);
			$conf['bitlyApiKey'] = trim($conf['bitlyApiKey']);
			return $conf;
		}

		/**
		 * Check if string is in UTF-8 format
		 *
		 * @param	array	string to check
		 * @return	boolean	true if string is valid utf-8
		 */
		function isUTF8($str) {
			return preg_match('/\A(?:([\09\0A\0D\x20-\x7E]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})*)\Z/x', $str);
		}

	}

	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pmkttnewstwitter/class.tx_pmkttnewstwitter.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pmkttnewstwitter/class.tx_pmkttnewstwitter.php']);
	}
?>
