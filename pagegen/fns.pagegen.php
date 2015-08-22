<?php
function geo_seo_pageNew() {
	$slug = geo_seo_getData('slug');
	$vp = new geoseo_Virtual_Themed_Pages();
	$vp->add('/'.$slug.'\/*+/i', 'geo_seoMagic');
}

function geo_seoMagic($v, $url) {

	$settings = geo_seo_getData();

	$fullURL = geoseotools::full_url($_SERVER);

	$params = [
		'url'			=> $settings['api'].'/pluginhtml/route',
		'method'		=> 'post',
		'fields'		=>	[
			'url'		=>	$settings['host'],
			'slug'		=>	$settings['slug'],
			'urlType'	=>	'rewrite',
			'fullURL'	=>	$fullURL
		],
		'authentication'=> [
			'basic'		=>	true,
			'user'		=>	'api',
			'password'	=>	$settings['token']
		]
	];

	$html = geoseotools::easyCURL($params);

	if($html=='') {
		geo_seoThrowErrorPage();
	}
	else {

		//if this parses as valid json when routing a url, it means we got an API error back
		$checkforErrors = json_decode($html, true);
		if(json_last_error()===JSON_ERROR_NONE){
			$html = '';
			//save error message so we can pull it out on the admin site
			error_log($checkforErrors['data']['msg']);
			delete_option( 'geo_seo_error' );
			add_option( 'geo_seo_error', $checkforErrors['data']['msg'], '', false );
			geo_seoThrowErrorPage();
		}
		else {
			delete_option( 'geo_seo_error' );
		}

		preg_match('/<h1.*?>(.*?)<\/h1>/i', $html, $match);

		if(isset($match[1]) && $match[1]!='') {
			$v->title = $match[1];
			$html = preg_replace('/<h1.*?>(.*?)<\/h1>/i', '', $html);
		}
		$v->body = $html;
		$v->template = 'page';
		$v->slug = $url;
	}

}

function geo_seoThrowErrorPage() {
	status_header(404);
	nocache_headers();
	include( get_404_template() );
	exit;
}