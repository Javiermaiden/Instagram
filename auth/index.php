<?php
	require_once('instagram_basic_display_api.php');
	
	$params = array(
		'get_code' => isset($_GET['code']) ? $_GET['code'] : '',
	);
	$ig = new instagram_basic_display_api($params);
?>
<h1>Instagram Basic Display API</h1>
<hr>

<?php if($ig->hasUserAccessToken) : ?>
	<h4>
		IG INFO
	</h4>
	<h6>
		Access Token
	</h6>
	<?php echo $ig->getUserAccessToken(); ?>
	<h6>
		Expires in : <?php echo ceil($ig->getUserAccessTokenExpires()/86400); ?> days
	</h6>
	<?php
		var_dump($ig->getUsersMedia());
	?>
<?php else : ?>
	<a href="<?php echo $ig->authorizationUrl; ?>">
		Authorize w/Instagram
	</a>
<?php endif; ?>
<!-- ?fields=business_discovery.username(javierbarrancoalmiron){followers_count,media_count,media{comments_count,like_count,media_url}} -->