<div class="geoseo_logo" style="<?php if($_SESSION['geoseoPlugin']['organization']['show_brand_on_pages']==0) { ?>visibility:hidden;<?php } ?>">
	<a href="<?php echo $_SESSION['geoseoPlugin']['organization']['url']; ?>" title="Provided by <?php echo $_SESSION['geoseoPlugin']['organization']['name']; ?>"><img src="http://images.localgeosearch.com/1/logo/logo.png" alt="" /></a><a href="http://www.localgeosearch.com" title="Powered by Local Geo Search - combining keywords with geo technology to maximize search results" class="localgeosearch"><img src="http://images.localgeosearch.com/localgeosearch-logo.png" alt="Local Geo Search" /></a>
</div>

<link href="/wp-content/plugins/geoseo/style/style.css" rel="stylesheet" />
<div class="geoseo_piwik"><?php echo $_SESSION['geoseoPlugin']['website']['analytics_script']; ?></div>