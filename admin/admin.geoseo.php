<?php
if (!class_exists('GEOSEOSettingsPage'))
{
class GEOSEOSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
		add_action( 'admin_notices',  array( $this, 'error_messages' )  );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
		add_menu_page( 'Local Geo Search', 'Local Geo Search', 'manage_options', 'geo_seo_admin', array( $this, 'create_admin_page' ), plugins_url( 'geoseo/images/icon.png' ), 6 );

        // This page will be under "Settings"
//        add_options_page(
//            'Settings Admin',
//            'Local Geo Search Settings',
//            'manage_options',
//            'geo_seo_admin',
//            array( $this, 'create_admin_page' )
//        );
    }

	public function error_messages() {
		settings_errors('geo_seo_error');
	}

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'geo_seo_option_name' );
        ?>
        <div class="wrap">
            <h2>Local Geo Search Settings</h2>
			<a href="<?php echo get_site_url(); ?>/<?php echo $this->options['slug']; ?>">View Created Pages</a>
			&nbsp;&nbsp;
			<a href="https://manage.localgeosearch.com">Edit terms, locations, and content</a>
			&nbsp;&nbsp;
			<a href="https://manage.localgeosearch.com">View Analytics</a>
			<hr>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'geo_seo_option_name' );
                do_settings_sections( 'geo_seo_admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'geo_seo_option_name', // Option group
            'geo_seo_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'geo_seo_login_section', // ID
            '1) Log In', // Title
            array( $this, 'section_callback' ), // Callback
            'geo_seo_admin' // Page
        );

			add_settings_field(
				'username', // ID
				'Username', // Title
				array( $this, 'field_username_callback' ), // Callback
				'geo_seo_admin', // Page
				'geo_seo_login_section' // Section
			);

			add_settings_field(
				'password',
				'Password',
				array( $this, 'field_password_callback' ),
				'geo_seo_admin',
				'geo_seo_login_section'
			);


        add_settings_section(
            'geo_seo_website_section', // ID
            '2) Choose Website', // Title
            array( $this, 'section_callback' ), // Callback
            'geo_seo_admin' // Page
        );

			add_settings_field(
				'organization', // ID
				'Organization', // Title
				array( $this, 'field_organization_callback' ), // Callback
				'geo_seo_admin', // Page
				'geo_seo_website_section' // Section
			);

			add_settings_field(
				'website', // ID
				'Website', // Title
				array( $this, 'field_website_callback' ), // Callback
				'geo_seo_admin', // Page
				'geo_seo_website_section' // Section
			);


        add_settings_section(
            'geo_seo_plugin_section', // ID
            '3) Plugin Options', // Title
            array( $this, 'section_plugin_callback' ), // Callback
            'geo_seo_admin' // Page
        );

			add_settings_field(
				'slug', // ID
				'Slug', // Title
				array( $this, 'field_slug_callback' ), // Callback
				'geo_seo_admin', // Page
				'geo_seo_plugin_section' // Section
			);




    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
		//try log in

			$apiURL = geo_seo_getData('api');

			$params = array(
				'url'=>$apiURL.'/authentication',
				'authentication'=>array(
					'basic'	=>	true,
					'user'	=>	$input['username'],
					'password'=>$input['password']
				)
			);

			$user = json_decode(geo_seo_easyCurl($params),true);

			if($user['status']=='OK') {
				$input['organization'] = $user['data']['org_id'];
				if(!isset($input['website']) || $input['website']=='') {
					add_settings_error(
						'geo_seo_error',
						'login-msg',
						__('Authenticated successfully. Choose website.'),
						'updated'
					);
				}
				else {
					$this->options = get_option( 'geo_seo_option_name' );
					$url = get_site_url().'/'.$this->options['slug'];
					add_settings_error(
						'geo_seo_error',
						'login-msg',
						__('Successfully saved. Local GEO Search pages are now available at <a href="'.$url.'">'.$url.'</a>'),
						'updated'
					);
				}

			}
			else {
				$input['organization'] = 0;
				$input['website'] = 0;
				add_settings_error(
					'geo_seo_error',
					'login-msg',
					__('Incorrect username or password'),
					'error'
				);
			}

        return $input;
    }

    /**
     * Print the Section text
     */
    public function section_callback()
    {
        print '';
    }

	public function section_plugin_callback()
    {
        print 'Define the URL slug that your Local GEO Search pages will be created under. This should not be a page that exists on your site, Local Geo Search will create it for you.';
    }


    /**
     * Get the settings option array and print one of its values
     */
    public function field_username_callback()
    {
        printf(
            '<input type="text" id="username" name="geo_seo_option_name[username]" value="%s" />',
            isset( $this->options['username'] ) ? esc_attr( $this->options['username']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function field_password_callback()
    {
        printf(
            '<input type="password" id="password" name="geo_seo_option_name[password]" value="%s" />',
            isset( $this->options['password'] ) ? esc_attr( $this->options['password']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function field_slug_callback()
    {
        printf(
            get_site_url().'/<input type="text" id="slug" name="geo_seo_option_name[slug]" value="%s" />',
            isset( $this->options['slug'] ) ? esc_attr( $this->options['slug']) : 'local'
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function field_website_callback()
    {
		if($this->options['username']!='') {

			$apiURL = geo_seo_getData('api');

			$select = '<select id="website" name="geo_seo_option_name[website]"><option value="">Select site</option>';

			$organization = json_decode(geo_seo_easyCurl(array( 'url'=>$apiURL.'/organization/get/'.$this->options['organization'] )),true);


			foreach($organization['data']['websites'] as $site) {
				$selected = '';
				if($this->options['website']==$site['id']) {
					$selected= 'selected="selected"';
				}
				$select .= '<option value="'.$site['id'].'" '.$selected.'>'.$site['name'].' - '.$site['url'].'</option>';
			}
			$select .= '</select>';

			echo $select;
		}
		else {
			echo 'Log in for list of your websites.';
		}
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function field_organization_callback()
    {

		if($this->options['username']!='') {

			$apiURL = geo_seo_getData('api');

			$organization = json_decode(geo_seo_easyCurl(array( 'url'=>$apiURL.'/organization/get/'.$this->options['organization'] )),true);

			 printf(
				'<input type="hidden" id="organization" name="geo_seo_option_name[organization]" value="%s" /> '.$organization['data']['name'],
				isset( $this->options['organization'] ) ? esc_attr( $this->options['organization']) : ''
			);
		}
		else {
			echo 'Log in for your organization.';
		}

    }
}
}

if( is_admin() ) {
    $my_settings_page = new GEOSEOSettingsPage();
}