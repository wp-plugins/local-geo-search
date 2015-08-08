<?php
/**
* Plugin Name: Local Geo Search
* Plugin URI: https://www.localgeosearch.com
* Description: Local GEO Search creates hundreds of location specific pages on your site to target your services in your market.
* Version: 0.40
* Author: Elite Impressions, LLC
* Author URI: http://www.localgeosearch.com
**/

include_once('classes/class.virtualpage.php');
include_once('admin/admin.geoseo.php');

/* Runs when plugin is activated */
//register_activation_hook(__FILE__,'geo_seo_install');

/* Runs on plugin deactivation*/
//register_deactivation_hook( __FILE__, 'geo_seo_uninstall' );

//make the pages work
add_action('init', 'geo_seo_pageNew');

//add pages to sitemap
//yoast
add_filter( 'wpseo_sitemap_page_content', 'geo_seo_yoastSitemap' );
add_filter( 'wpseo_canonical', 'geo_seo_yoastCanonicalTag' );
//all in one seo
add_filter( 'option_aioseop_options', 'geo_seo_allinoneSitemap' );
add_filter( 'aioseop_canonical_url', 'geo_seo_allinoneCanonicalTag' );

//add link to settings page
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'geo_seo_settings_link' );

if (!session_id()) {
    session_start();
}


if(!isset($_SESSION['geoseoPlugin'])) {
	$_SESSION['geoseoPlugin'] = array(
		'sitemap'=>array(),
		'organization'=>array(),
		'website'=>array()
	);
}


// Add settings link on plugin page
function geo_seo_settings_link($links) {
	$settings_link = '<a href="options-general.php?page=geo_seo_admin">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
  	return $links;
}


function geo_seo_getData( $key=null ) {

	$settings = get_option('geo_seo_option_name');

	$data = array(
		'slug'	=>	isset($settings['slug']) ? $settings['slug'] : 'local',
		'api'	=>	'https://api.localgeosearch.com',
		'organization'	=>	$settings['organization'],
		'website'		=>	$settings['website']
	);

	if($key===null) {
		return $data;
	}
	else {
		return $data[$key];
	}

}

function geo_seo_getTerms( $termSID=null ) {

	$apiURL = geo_seo_getData('api');

	if($termSID==null) {
		$results = json_decode(geo_seo_easyCurl(array( 'url'=>$apiURL.'/terms/get/'.geo_seo_getData('organization').'/'.geo_seo_getData('website').'/' )),true);
	}
	else {
		$params = array( 'url'=>$apiURL.'/magic/'.geo_seo_getData('organization').'/'.geo_seo_getData('website').'/'.$termSID );
		$rsp = geo_seo_easyCurl($params);
		$results = json_decode($rsp, true);
	}

	return $results;

}

function geo_seo_pageNew() {

    $slug = geo_seo_getData('slug');
	$apiURL = geo_seo_getData('api');

	$url = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    if (substr($url,0,strlen($slug)) == $slug)
    {

		remove_action('wp_head', 'rel_canonical');

		//load the organization into the session
		$orgID = geo_seo_getData('organization');
		$websiteID = geo_seo_getData('website');
		if(count($_SESSION['geoseoPlugin']['organization'])==0) {
			$rsp = json_decode(geo_seo_easyCurl(array( 'url'=>$apiURL.'/organization/get/'.$orgID )),true);
			if($rsp['status']=='OK') {

				$_SESSION['geoseoPlugin']['organization'] = $rsp['data'];

				foreach($_SESSION['geoseoPlugin']['organization']['websites'] as $website) {
					if($website['id']==$websiteID) {
						$_SESSION['geoseoPlugin']['website'] = $website;
						break;
					}
				}

			}
			else {
				return;
			}
		}

		if($_SESSION['geoseoPlugin']['organization']['active']==0 || $_SESSION['geoseoPlugin']['website']['active']==0) {
			return;
		}


		//check the url to figure out where we're at
		$urlParts = explode('/', $url);
//		foreach($urlParts as $i=>$urlPart) {
//			error_log('URL Part '.$i.': '.$urlPart);
//		}

		//get term and location
		$termSID = isset($urlParts[1]) ? $urlParts[1] : '';
		$locationSID = isset($urlParts[2]) ? $urlParts[2] : '';

		//if term is blank, we get a list of terms and locations
		if($termSID=='') {
			$pageType = 1;
			$rawTerms = json_decode(geo_seo_easyCurl(array( 'url'=>$apiURL.'/terms/get/'.geo_seo_getData('organization').'/'.geo_seo_getData('website').'/' )),true);
			$rawLocations = json_decode(geo_seo_easyCurl(array( 'url'=>$apiURL.'/locations/get/'.geo_seo_getData('organization').'/'.geo_seo_getData('website').'/' )), true);
			$results['status']='OK';
		}
		//if $term is filled but location is blank, $term can be a location or a term
		elseif($locationSID=='') {
			$pageType = 2;
			$params = array( 'url'=>$apiURL.'/magic/'.geo_seo_getData('organization').'/'.geo_seo_getData('website').'/'.$termSID );
			$rsp = geo_seo_easyCurl($params);
			$results = json_decode($rsp, true);
		}
		//both term and location are in
		else {
			$pageType = 3;
			$params = array( 'url'=>$apiURL.'/magic/'.geo_seo_getData('organization').'/'.geo_seo_getData('website').'/'.$termSID.'/'.$locationSID );
			$rsp = geo_seo_easyCurl( $params );
			$results = json_decode($rsp, true);
		}

		//return error if the status is not ok
		if($results['status']!='OK') {
			return;
		}


		//geo-search list
		if($pageType==1) {
			$title = get_bloginfo();

			$data = array(
				'term_links'	=>	array(),
				'location_links'=>	array()
			);

			foreach($rawTerms['data']['terms'] as $term) {
				$data['term_links'][] = array(
					'text'	=>	$term['term'],
					'href'	=>	'/'.$term['sid']
				);
			}

			foreach($rawLocations['data']['locations'] as $location) {
				$data['location_links'][] = array(
					'text'	=>	$location['location'],
					'href'	=>	'/'.$location['sid']
				);
			}

			$content = geo_seo_createView('view.links_blank.php', $data);

		}
		//location or terms links
		elseif($pageType==2) {
			if($results['data']['type']=='term') {
				$title = $results['data']['term']['text'];
			}
			elseif($results['data']['type']=='location') {
				$title = $results['data']['location']['name'];
			}

			$content = geo_seo_createView('view.links.php', $results['data']);

		}
		//magic page
		elseif($pageType==3) {
			$title = $results['data']['term'].' in '.$results['data']['location'];
			$content = geo_seo_createView('view.page.php', $results['data']);
		}


        $args = array(
						'slug'		=> $slug,
						'url'		=> $url,
						'title'		=> $title,
						'content'	=> $content);
        $pg = new GEOSEOVirtualPage($args);

	}
}



function geo_seo_createGenericSiteMapData() {

	$slug = geo_seo_getData('slug');
	$apiURL = geo_seo_getData('api');

	if(count($_SESSION['geoseoPlugin']['sitemap'])==0) {
		$rawTerms = json_decode(geo_seo_easyCurl(array( 'url'=>$apiURL.'/terms/get/'.geo_seo_getData('organization').'/'.geo_seo_getData('website').'/' )),true);

		$rawLocations = json_decode(geo_seo_easyCurl(array('url'=>$apiURL.'/locations/get/'.geo_seo_getData('organization').'/'.geo_seo_getData('website').'/' )), true);

		$lastChanged = date('Y-m-d H:i:s', strtotime('-1 day') );

		$url = array();

		$url[] = array(
			'url'				=> get_site_url().'/'.$slug.'/',
			'url_parts'			=>	'/'.$slug.'/',
			'priority'			=> '1',
			'frequency'			=> 'always',
			'modification_date' => $lastChanged
		);

		if(isset($rawTerms) && isset($rawTerms['data']) && isset($rawTerms['data']['terms'])) {
			foreach($rawTerms['data']['terms'] as $term) {

				$url[] = array(
					'url'				=> get_site_url().'/'.$slug.'/'.$term['sid'].'/',
					'url_parts'			=>	'/'.$slug.'/'.$term['sid'].'/',
					'priority'			=> '1',
					'frequency'			=> 'always',
					'modification_date' => $lastChanged
				);

				foreach($rawLocations['data']['locations'] as $location) {
					$url[] = array(
						'url'				=> get_site_url().'/'.$slug.'/'.$term['sid'].'/'.$location['sid'].'/',
						'url_parts'			=> '/'.$slug.'/'.$term['sid'].'/'.$location['sid'].'/',
						'priority'			=> '1',
						'frequency'			=> 'always',
						'modification_date' => $lastChanged
					);
				}

			}
		}

		if(isset($rawTerms) && isset($rawTerms['data']) && isset($rawTerms['data']['locations'])) {
			foreach($rawLocations['data']['locations'] as $location) {
				$url[] = array(
					'url'				=> get_site_url().'/'.$slug.'/'.$location['sid'].'/',
					'url_parts'			=> '/'.$slug.'/'.$location['sid'].'/',
					'priority'			=> '1',
					'frequency'			=> 'always',
					'modification_date' => $lastChanged
				);
			}
		}

		$_SESSION['geoseoPlugin']['sitemap'] = $url;

	}
	else {
		$url = $_SESSION['geoseoPlugin']['sitemap'];
	}

	return $url;

}


function geo_seo_yoastCanonicalTag( $canonical ) {
	$slug = geo_seo_getData('slug');

	$url = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if (substr($url,0,strlen($slug)) == $slug)
    {
		$canonical = false;
	}

	return $canonical;
}

function geo_seo_allinoneCanonicalTag( $canonical ) {
	$slug = geo_seo_getData('slug');

	$url = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if (substr($url,0,strlen($slug)) == $slug)
    {
		$canonical = false;
	}

	return $canonical;
}

//add pages to sitemap
function geo_seo_yoastSitemap( $content ) {

	global $wpseo_sitemaps;

	$urls = geo_seo_createGenericSiteMapData();

	foreach( $urls as $data ) {

		$url = array(
			'loc' => $data['url'],
			'pri' => $data['priority'],
			'chf' => $data['frequency'],
			'mod' => $data['modification_date']
		);
		$content .= $wpseo_sitemaps->sitemap_url( $url );

	}

	return $content;
}


function geo_seo_allinoneSitemap( $option ) {

    if( !empty( $option ) && !empty( $option['modules'] ) && !empty( $option['modules']['aiosp_sitemap_options'])) {

		$settings = geo_seo_getData();

		$urls = geo_seo_createGenericSiteMapData();

		$my_sitemap_entries = array();

		//remove old geo seo stuff
		foreach($option['modules']['aiosp_sitemap_options']['aiosp_sitemap_addl_pages'] as $urlIndex=>$entry) {
			if(strpos($urlIndex, '/'.$settings['slug'])!==false) {
				unset($option['modules']['aiosp_sitemap_options']['aiosp_sitemap_addl_pages'][$urlIndex]);
			}
			elseif(strpos($urlIndex, '/'.$settings['slug'])!==false) {
				unset($option['modules']['aiosp_sitemap_options']['aiosp_sitemap_addl_pages'][$urlIndex]);
			}
		}


		foreach( $urls as $data ) {

			$my_sitemap_entries[ $data['url_parts'] ] = array(
				'prio' => $data['priority'],
				'freq' => $data['frequency'],
				'mod'  => $data['modification_date']
			);

		}

        if ( empty( $option['modules']['aiosp_sitemap_options']['aiosp_sitemap_addl_pages'] ) ) {
            $option['modules']['aiosp_sitemap_options']['aiosp_sitemap_addl_pages'] = array();
        }

        foreach( $my_sitemap_entries as $k => $v ) {
            $option['modules']['aiosp_sitemap_options']['aiosp_sitemap_addl_pages'][$k] = $v;
        }

    }

    return $option;

}




function geo_seo_easyCurl($params) {

	$options = get_option('geo_seo_option_name');

	if(!isset($params['authentication'])) {
		$params['authentication'] = array(
			'basic'	=>	true,
			'user'	=>	$options['username'],
			'password'=>$options['password']
		);
	}

	//$params = [
	//	'url' => 'string',
	//	'fields' => 'array',
	//	'header' => 'array',
	//	'authentication' => [ 'user'=>'string', 'password'=>'string'],
	//];

	if(!isset($params['fields']) || !is_array($params['fields'])) {
		$params['fields'] = array();
	}
	$fields_string = http_build_query($params['fields']);

	//open connection
	$ch = curl_init();

	//set the url, number of POST vars, POST data
	curl_setopt($ch, CURLOPT_URL, $params['url']);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

	if(isset($params['header'])) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $params['header']);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	}

	if(isset($params['authentication'])) {
		if(!isset($params['authentication']['basic']) || $params['authentication']['basic']!=true) {
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
		}
		curl_setopt($ch, CURLOPT_USERPWD, $params['authentication']['user'].':'.$params['authentication']['password']);
	}

	//execute post
	$result = curl_exec($ch);

	//capture any errors from curl
	$curl_errno = curl_errno($ch);
	if($curl_errno) {
//		error_log('easyCURL error '.$curl_errno.' on url '.$params['url']);
	}
	else {
//		error_log('easyCURL success to url '.$params['url']);
	}

	//close connection
	curl_close($ch);

	return $result;

}

function geo_seo_createView( $view, $data ) {

	$settings = geo_seo_getData();

	foreach($data as $k=>$v) {
		$$k = $v;
	}

	ob_start();
		include($view);
		$content = ob_get_contents();
	ob_end_clean();

	return $content;

}