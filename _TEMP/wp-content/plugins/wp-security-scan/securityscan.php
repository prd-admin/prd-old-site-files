<?php
/*
Plugin Name: WP Security Scan
Plugin URI: http://semperfiwebdesign.com/plugins/wp-security-scan/
Description: Perform security scan of WordPress installation.
Author: Michael Torbert
Version: 2.7.1.2
Author URI: http://semperfiwebdesign.com/
*/

/*
Copyright (C) 2008-2009 Michael Torbert / semperfiwebdesign.com (michael AT semperfiwebdesign DOT com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if ( ! defined( 'WP_CONTENT_URL' ) )
      define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
      define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
      define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
      define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

require_once(WP_PLUGIN_DIR . "/wp-security-scan/support.php");
require_once(WP_PLUGIN_DIR . "/wp-security-scan/scanner.php");
require_once(WP_PLUGIN_DIR  . "/wp-security-scan/password_tools.php");
require_once(WP_PLUGIN_DIR . "/wp-security-scan/database.php");
require_once(WP_PLUGIN_DIR . "/wp-security-scan/functions.php");
if(!class_exists("SimplePie")){
require_once(WP_PLUGIN_DIR . "/wp-security-scan/simplepie.inc");
}
//require_once(WP_PLUGIN_DIR . "/plugins/wp-security-scan/scripts.js");


add_action( 'admin_notices', mrt_update_notice, 5 );
add_action('admin_head', 'mrt_hd');
add_action("init",mrt_wpdberrors,1);
add_action("parse_query",mrt_wpdberrors,1);
add_action('admin_menu', 'add_men_pg');
add_action("init",mrt_remove_wp_version,1);   //comment out this line to make ddsitemapgen work


remove_action('wp_head', 'wp_generator');
//add_action('admin_head', 'mrt_root_scripts');
function add_men_pg() {
         if (function_exists('add_menu_page')){
            add_menu_page('Security', 'Security', 8, __FILE__, 'mrt_opt_mng_pg',WP_PLUGIN_URL . '/wp-security-scan/lock.png');
            add_submenu_page(__FILE__, 'Scanner', 'Scanner', 8, 'scanner', 'mrt_sub0');
            add_submenu_page(__FILE__, 'Password Tool', 'Password Tool', 8, 'passwordtool', 'mrt_sub1');
            add_submenu_page(__FILE__, 'Database', 'Database', 8, 'database', 'mrt_sub3');
            add_submenu_page(__FILE__, 'Support', 'Support', 8, 'support', 'mrt_sub2');
         }
}

/*function mrt_root_scripts(){
$siteurl = get_option('siteurl');
echo '<script language="JavaScript" type="text/javascript" src="' . WP_PLUGIN_URL . '/wp-security-scan/scripts.js"></script>';
}*/

function mrt_update_notice(){
/*$mrt_version = "2.2.52";
$mrt_latest = fgets(fopen("http://semperfiwebdesign.com/wp-security-scan.html", "r"));
echo $mrt_latest . " and " . $mrt_version;
if($mrt_latest > $mrt_version)
    echo "New Version Available";
   else
      echo "Latest Version";
  */  }


	function wpss_mrt_meta_box(){  
	
		global $wpdb;
		mrt_check_version();
		mrt_check_table_prefix();
		mrt_version_removal();
		mrt_errorsoff();
		echo '<div style="color:green">WP ID META tag removed form WordPress core</div>';

		$name = $wpdb->get_var("SELECT user_login FROM $wpdb->users WHERE user_login='admin'");
		if ($name=="admin"){
		  echo '<a href="http://semperfiwebdesign.com/documentation/wp-security-scan/change-wordpress-admin-username/" title="WordPress Admin" target="_blank"><font color="red">"admin" user exists.</font></a>';
		  }
		  else{
		      echo '<font color="green">No user "admin".</font>';
		      }
		?><br /><?php 
		$filename = '.htaccess';
		if (file_exists($filename)) {
		    echo '<font color="green">.htaccess exists in wp-admin/</font>';
		} else {
		    echo '<font color="red">The file .htaccess does not exist in wp-admin/.</font>';
		}

		?>

		<div style="text-align:center;color:grey;margin-top:20px;"><em>**WP Security Scan plugin must remain active for security features to remain**</em></div>
		
		<div style="text-align:center;font-weight:bold;">Future Releases</div>
		<ul><li>one-click change file/folder permissions</li><li>test for XSS vulnerabilities</li><li>intrusion detection/prevention</li><li>lock out/log incorrect login attempts</li><li>user enumeration protection</li><li>WordPress admin protection/security</li></ul>
		<?php	}
		
	function wpss_mrt_meta_box4(){ ?>
		<div style="padding-left:10px;">
			<div style="padding: 10px 10px 10px 10px;border: 1px solid #ddd;">
			<div style="margin-bottom:30px;">
				We would also like to recommend <a href="http://www.pagelines.com/wpthemes/" target="_blank">PageLines</a> for Professional WordPress Themes.  They are attractive, affordable, performance optimized CMS themes that integrate perfectly with All in One SEO Pack to put your professional website at the top of the rankings.
			</div>

				<a target="_blank" title="iBlogPro" href="http://www.pagelines.com/wpthemes/">
				<img src="<?php echo WP_PLUGIN_URL; ?>/wp-security-scan/images/iblogpro.jpg" alt="<?php _e('iBlogPro theme', 'all_in_one_seo_pack') ?>" />	</a>

				<a target="_blank" title="PageLines Themes" href="http://www.pagelines.com/wpthemes/">	
				<img src="<?php echo WP_PLUGIN_URL; ?>/wp-security-scan/images/pagelines.jpg" alt="<?php _e('Pagelines Themes', 'all_in_one_seo_pack') ?>" /> </a>	

				<a target="_blank" title="WhiteHouse" href="http://www.pagelines.com/wpthemes/">	
				<img src="<?php echo WP_PLUGIN_URL; ?>/wp-security-scan/images/whitehouse.jpg" alt="<?php _e('WhiteHouse theme', 'all_in_one_seo_pack') ?>" />	</a>


			</div>
			
			
		</div>
			
		<?php	}	
		
	function wpss_mrt_meta_box2(){ ?>
		<div style="padding-left:10px;">
			<?php mrt_get_serverinfo(); ?>
		</div>
			
		<?php	}
	
	
	function wpss_mrt_meta_box3(){  
		
		?>
<div style="padding:10px;">
	<div style="font-size:13pt;text-align:center;">Highest</div>		<?php
//		include('WP_PLUGIN_DIR . '/all-in-one-seo-pack/simplepie.inc');

		$feed = new SimplePie();


			$feed->set_feed_url('feed://donations.semperfiwebdesign.com/category/highest-donations/feed/');
		$feed->enable_cache(false);
    	$feed->strip_htmltags(array('p'));
		//					$feed->set_cache_location(WP_PLUGIN_DIR . '/wp-security-scan/');
			$feed->init();
		$feed->handle_content_type();
		?>
				<?php if ($feed->data): ?>
					<?php $items = $feed->get_items(); ?>

					<?php foreach($items as $item): ?>
<p>
		<strong><?php echo $item->get_title(); ?></strong>
		<?php echo $item->get_content(); ?>
</p>

					<?php endforeach; ?>
					</div>
				<?php endif; ?>


	<div style="font-size:13pt;text-align:center;">Recent</div>		<?php
//		include(WP_PLUGIN_DIR . '/all-in-one-seo-pack/simplepie.inc');

		$feed = new SimplePie();


			$feed->set_feed_url('feed://donations.semperfiwebdesign.com/category/wp-security-scan/feed/');
$feed->enable_cache(false);
			$feed->strip_htmltags(array('p'));
//													$feed->set_cache_location(WP_PLUGIN_DIR . '/wp-security-scan/');
  $feed->init();

		$feed->handle_content_type();
		?>
				<?php if ($feed->data): ?>
					<?php $items = $feed->get_items(); ?>

					<?php foreach($items as $item): ?>
<p>
		<strong><?php echo $item->get_title(); ?></strong>
		<?php echo $item->get_content(); ?>
</p>

					<?php endforeach; ?>
					</div>
				<?php endif; ?>
				
				<hr width="75%"/>

					<div style="text-align:center"><em>This plugin is updated as a free service to the WordPress community.  Donations of any size are appreciated.</em>
					<br /><br />
					<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=mrtorbert%40gmail%2ecom&item_name=Support%20WordPress%20Security%20Scan%20Plugin&no_shipping=0&no_note=1&tax=0&currency_code=USD&lc=US&bn=PP%2dDonationsBF&charset=UTF%2d8" target="_blank">Click here to support this plugin.</a>

</div>
	
	<!--	<br /><br /><h4>Highest Donations</h4></div>  -->
	
	<?php 

		/*$ch = curl_init("http://semperfiwebdesign.com/top_donations.php");
		$fp = fopen("top_donations.php", "w");
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
		*/

	/*	$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://semperfiwebdesign.com/top_donations.php");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
*/
		?>
<!--		<br /><br /><div style="text-align:center"><h4>Recent Donations</h4></div>

-->
<?php

/*
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://semperfiwebdesign.com/recent_donations.php");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
*/
		/*
		$ch = curl_init("http://semperfiwebdesign.com/recent_donations.php");
		$fp = fopen("recent_donations.php", "w");
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
		*/
		
		}
	

function mrt_opt_mng_pg() {
        ?>


<div class="wrap">
    <h2>WP - Security Admin Tools</h2>  

<!--<div id='update-nag'>A new version of WP Security Scan is available!</div>-->
<?php //$rss = fetch_rss('http://alexrabe.boelinger.com/?tag=nextgen-gallery&feed=rss2');?>


<?php  

add_meta_box("wpss_mrt", 'Initial Scan', "wpss_mrt_meta_box", "wpss");  
add_meta_box("wpss_mrt", 'System Information Scan', "wpss_mrt_meta_box2", "wpss2");  
add_meta_box("wpss_mrt", 'Donations', "wpss_mrt_meta_box3", "wpss3");  
add_meta_box("wpss_mrt", 'PageLines WordPress Themes', "wpss_mrt_meta_box4", "wpss4");  

?>

<div id="dashboard-widgets-wrap">
<div class="metabox-holder">
	<div style="float:left; width:48%;" class="inner-sidebar1">
		<?php do_meta_boxes('wpss','advanced','');  ?>	
	</div>

	<div style="float:right; width:48%; " class="inner-sidebar1">
		<?php do_meta_boxes('wpss2','advanced',''); ?>	
	</div>
						
<div style="clear:both"></div>
						
	<div style="float:left;width:48%;" class="inner-sidebar1">
		<?php do_meta_boxes('wpss4','advanced','');  ?>	
	</div>
	
	<div style="float:right; width:48%;" class="inner-sidebar1">
		<?php do_meta_boxes('wpss3','advanced',''); ?>	
	</div>
</div>

<div style="clear:both;"></div>
</div>
	
	<br /><em>For comments, suggestions, bug reporting, etc please <a href="http://semperfiwebdesign.com/contact/">click here</a>.</em>

       
             Plugin by <a href="http://semperfiwebdesign.com/" title="Semper Fi Web Design">Semper Fi Web Design</a>
        </div>
<?php } 

function mrt_hd()
{
 $siteurl = get_option('siteurl');?>
<script language="JavaScript" type="text/javascript" src="<?php echo WP_PLUGIN_URL;?>/wp-security-scan/js/scripts.js"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo WP_PLUGIN_URL;?>/wp-security-scan/scripts.js"></script>
<script type="text/javascript">
//window.onload=function(){enableTooltips()};
</script>
<!--<link rel="stylesheet" type="text/css" href="<?php //echo WP_PLUGIN_URL;?>/plugins/wp-security-scan/style.css" />-->
<?php }
?>
