<?php
#if (!defined ('TYPO3_MODE')) {
#	die ('Access denied.');
#}

$oauth_token_secret	= $_GET['oauth_token_secret'];
$sAuthorizeURL	 	= $_GET['authorize_url'];
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

	<script type="text/javascript">
	<!--
	newwindow = window.open('<?php echo $sAuthorizeURL; ?>', 'TwitterOAuth', 'height=500,width=600');
	if (window.focus) {newwindow.focus()}
	//-->
	</script>
</head>
<body>
	<div style="position: absolute; left: 10px; top: 30px; padding: 10px; background: #FFF; border: 2px solid #F00; width: 600px; height: 500px;">
		<h2>PMK News Twitter (pmkttnewstwitter)</h2>
		<h3>Twitter authentication routine</h3>
		<p>
		<input type="text" name="oauth_token_secret" id="oauth_token_secret" size="50" value="<?php echo $oauth_token_secret; ?>" />
			<label for="oauth_token_secret">oauth_token_secret</label>
		</p>
		<p>
			<h3><a href="<?php echo $sAuthorizeURL; ?>" target="_blank" style="">Click here</a> to call the Twitter authentication routine</h3>
		</p>
		<p>
			<a href="javascript:back();" style="">&lt; BACK</a>
		</p>
	</div>
</body>
</html>
