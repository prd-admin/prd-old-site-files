<?php
/*
Plugin Name: Post videos and photo galleries
Plugin URI: http://www.cincopa.com/wpplugin/wordpress-plugin.aspx
Description: Post rich videos and photos galleries from your cincopa account
Author: Cincopa 
Version: 1.58
*/


function cincopa_plugin_ver()
{
	return 'wp1.58';
}

function cincopa_afc()
{
	$cincopa_afc = get_site_option('CincopaAFC');
	
	if ($cincopa_afc == '')
		return '';
	
	return '&afc='.$cincopa_afc;
}

function cincopa_url()
{
	return 'http://www.cincopa.com';
}

if (strpos($_SERVER['REQUEST_URI'], 'media-upload.php') && strpos($_SERVER['REQUEST_URI'], '&type=cincopa') && !strpos($_SERVER['REQUEST_URI'], '&wrt='))
{
	header('Location: '.cincopa_url().'/wpplugin/start.aspx?ver='.cincopa_plugin_ver().cincopa_afc().'&rdt='.urlencode(cincopa_selfURL()));
	exit;
}

function cincopa_selfURL()
{
	$s = empty ( $_SERVER ["HTTPS"] ) ? '' : ($_SERVER ["HTTPS"] == "on") ? "s" : "";

	$protocol =  strtolower ( $_SERVER ["SERVER_PROTOCOL"] );
	$protocol =  substr($protocol, 0, strpos($protocol, "/"));
	$protocol .= $s;

	$port = ($_SERVER ["SERVER_PORT"] == "80") ? "" : (":" . $_SERVER ["SERVER_PORT"]);
	$ret = $protocol . "://" . $_SERVER ['SERVER_NAME'] . $port . $_SERVER ['REQUEST_URI'];

	return $ret;
}

function cincopa_pluginURI()
{
	return get_option('siteurl').'/wp-content/plugins/'.dirname(plugin_basename(__FILE__));
}

function WpMediaCincopa_init() // constructor
{
//		load_plugin_textdomain('wp-media-cincopa', PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)));

	add_action('media_buttons', 'cincopa_addMediaButton', 20);

	add_action('media_upload_cincopa', 'media_upload_cincopa');
	// No longer needed in WP 2.6
	if ( !function_exists('wp_enqueue_style') )
	{
		add_action('admin_head_media_upload_type_cincopa', 'media_admin_css');
	}
      
	// check auth enabled
	//if(!function_exists('curl_init') && !ini_get('allow_url_fopen')) {}
}

function cincopa_addMediaButton($admin = true)
{
	global $post_ID, $temp_ID;
	$uploading_iframe_ID = (int) (0 == $post_ID ? $temp_ID : $post_ID);

	$media_upload_iframe_src = get_option('siteurl').'/wp-admin/media-upload.php?post_id=$uploading_iframe_ID';

	$media_cincopa_iframe_src = apply_filters('media_cincopa_iframe_src', "$media_upload_iframe_src&amp;type=cincopa&amp;tab=cincopa");
	$media_cincopa_title = __('Add Cincopa photo', 'wp-media-cincopa');

	echo "<a class=\"thickbox\" href=\"{$media_cincopa_iframe_src}&amp;TB_iframe=true&amp;height=500&amp;width=640\" title=\"$media_cincopa_title\"><img src=\"".cincopa_pluginURI()."/media-cincopa.gif\" alt=\"$media_cincopa_title\" /></a>";
}

function cincopa_modifyMediaTab($tabs)
{
	return array(
		'cincopa' =>  __('Cincopa photo', 'wp-media-cincopa'),
	);
}

function media_upload_cincopa()
{
	wp_iframe('media_upload_type_cincopa');
}


function media_upload_type_cincopa()
{
	global $wpdb, $wp_query, $wp_locale, $type, $tab, $post_mime_types;
	add_filter('media_upload_tabs', 'cincopa_modifyMediaTab');
?>

<br />
<br />
<h2>&nbsp;&nbsp;Please Wait...</h2>

<script>

	function cincopa_stub()
	{
	var i = location.href.indexOf("&wrt=");

	if (i > -1)
	{
	top.send_to_editor(unescape(location.href.substring(i+5)));
	}

	top.tb_remove();
	}

	window.onload = cincopa_stub;

</script>

<?php
}

WpMediaCincopa_init();

define("CINCOPA_REGEXP", "/\[cincopa ([[:print:]]+?)\]/");

function cincopa_plugin_callback($match)
{
	$uni = uniqid('');
	$ret = '<div id="_cp_widget_'.$uni.'"><img alt="Powered by Cincopa WordPress plugin" src="http://www.cincopa.com/wpplugin/runtime/loading.gif" style="border:0;"/>';

	if (strpos($_SERVER['REQUEST_URI'], '?p=') || strpos($_SERVER['REQUEST_URI'], '?') == 0)
	{
		$ret .= 'Another great product from Cincopa <a href="http://www.cincopa.com/mediasend/start.aspx">Send Files</a>. ';
		$ret .= 'Cincopa <a href="http://www.cincopa.com/wpplugin/wordpress-plugin.aspx">video hosting</a> solution for your website.';
	}

	$ret = $ret.'
</div>
<!-- Powered by Cincopa WordPress plugin '.cincopa_plugin_ver().': http://www.cincopa.com/wpplugin/wordpress-plugin.aspx -->
<script type="text/javascript">document.getElementById("_cp_widget_'.$uni.'").innerHTML=';

	$ret = $ret."'<img src=\"http://www.cincopa.com/wpplugin/runtime/loading.gif\" />';</script>";
	
	$ret = $ret.'
<script src="http://www.cincopa.com/wpplugin/runtime/libasync.js" type="text/javascript"></script>
<script type="text/javascript">
cp_load_widget("'.urlencode($match[0]).'", "_cp_widget_'.$uni.'");
</script>
';

	return $ret;
}


function cincopa_plugin($content)
{
	return (preg_replace_callback(CINCOPA_REGEXP, 'cincopa_plugin_callback', $content));
}

add_filter('the_content', 'cincopa_plugin');
add_filter('the_content_rss', 'cincopa_plugin');
add_filter('comment_text', 'cincopa_plugin'); 

/////////////////////////////////
// dashboard widget
//////////////////////////////////
function cincopa_dashboard()
{
	if(function_exists('wp_add_dashboard_widget'))
		wp_add_dashboard_widget('cincopa', 'Cincopa', 'cincopa_dashboard_content');
}

function cincopa_dashboard_content()
{

	echo "<iframe src='http://www.cincopa.com/wpplugin/wordpress-dashboard-content.aspx?ver=".cincopa_plugin_ver().cincopa_afc()."&src=".urlencode(cincopa_selfURL())."' width='100%' height='370px' scrolling='no'></iframe>";

}


add_action('wp_dashboard_setup', 'cincopa_dashboard'); 





// Hook for adding admin menus
// http://codex.wordpress.org/Adding_Administration_Menus
add_action('admin_menu', 'mt_cincopa_add_pages');

// action function for above hook
function mt_cincopa_add_pages() {
	// Add a new submenu under Options:
	
	if (function_exists('is_site_admin') && is_site_admin() == true)
		add_options_page('Cincopa Options', 'Cincopa Options', 8, 'cincopaoptions', 'mt_cincopa_options_page');

    // Add a new submenu under Manage:
//	add_management_page('Test Manage', 'Test Manage', 8, 'testmanage', 'mt_manage_page');

	if(function_exists('add_menu_page'))
	{
		// Add a new top-level menu (ill-advised):
		add_menu_page('Cincopa', 'Cincopa', 8, __FILE__, 'mt_cincopa_toplevel_page');

		// kill the first menu item that is usually the the identical to the menu itself
		add_submenu_page(__FILE__, '', '', 8, __FILE__);

		add_submenu_page(__FILE__, 'Monitor Galleries', 'Monitor Galleries', 8, 'sub-page', 'mt_cincopa_sublevel_monitor');

		add_submenu_page(__FILE__, 'Create Gallery', 'Create Gallery', 8, 'sub-page2', 'mt_cincopa_sublevel_create');

		add_submenu_page(__FILE__, 'My Account', 'My Account', 8, 'sub-page3', 'mt_cincopa_sublevel_myaccount');

		add_submenu_page(__FILE__, 'Support Forum', 'Support Forum', 8, 'sub-page4', 'mt_cincopa_sublevel_forum');
	}
}



function mt_cincopa_options_page() {

	if( is_site_admin() == false ) {
		wp_die( __('You do not have permission to access this page.') );
	}

	$cincopa_afc = get_site_option('CincopaAFC');

	if ( isset($_POST['submit']) ) {
		if (isset($_POST['cincopaafc']))
		{
			$cincopa_afc = $_POST['cincopaafc'];
		}
		else
		{
			$cincopa_afc = '';
		}
		update_site_option('CincopaAFC', $cincopa_afc);
		echo "<div id=\"updatemessage\" class=\"updated fade\"><p>Cincopa settings updated.</p></div>\n";
		echo "<script type=\"text/javascript\">setTimeout(function(){jQuery('#updatemessage').hide('slow');}, 3000);</script>";	
	}
	?>
<div class="wrap">
	<h2>Cincopa AFC Configuration</h2>
	<div class="postbox-container">
		<div class="metabox-holder">
			<div class="meta-box-sortables">
				<form action="" method="post" id="cincopa-conf">
					<div id="cincopa_settings" class="postbox">
						<div class="handlediv" title="Click to toggle">
							<br />
						</div>
						<h3 class="hndle">
							<span>Cincopa Settings</span>
						</h3>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th valign="top" scrope="row">
										<label for="cincopaafc">
											Cincopa AFC (<a target="_blank" href="http://support.cincopa.com/index.php?title=Cincopa_Multimedia_Platform/Wordpress_AFC">what?</a>):
										</label>
									</th>
									<td valign="top">
										<input id="cincopaafc" name="cincopaafc" type="text" size="20" value=""<?php echo $cincopa_afc; ?>"/>
									</td>
								</tr>
							</table>
						</div>
					</div>
					<div class="submit">
						<input type="submit" class="button-primary" name="submit" value="Update &raquo;" />
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php    
    
    
    
    
    
    
}
/*
// mt_manage_page() displays the page content for the Test Manage submenu
function mt_manage_page() {
    echo "<h2>Test Manage</h2>";
}
*/

function mt_cincopa_toplevel_page() {
    echo "<iframe src='http://www.cincopa.com/wpplugin/start.aspx?ver=".cincopa_plugin_ver().cincopa_afc()."&src=".urlencode(cincopa_selfURL())."' width='98%' height='2000px'></iframe>";
}

function mt_cincopa_sublevel_create() {
    echo "<iframe src='http://www.cincopa.com/wpplugin/wizard_name.aspx?ver=".cincopa_plugin_ver().cincopa_afc()."&src=".urlencode(cincopa_selfURL())."' width='98%' height='2000px'></iframe>";
}

function mt_cincopa_sublevel_monitor() {
    echo "<iframe src='http://www.cincopa.com/wpplugin/wizard_edit.aspx?ver=".cincopa_plugin_ver().cincopa_afc()."&src=".urlencode(cincopa_selfURL())."' width='98%' height='2000px'></iframe>";
}

function mt_cincopa_sublevel_myaccount() {
    echo "<iframe src='http://www.cincopa.com/cincopaManager/ManageAccount.aspx?ver=".cincopa_plugin_ver().cincopa_afc()."&src=".urlencode(cincopa_selfURL())."' width='98%' height='2000px'></iframe>";
}

function mt_cincopa_sublevel_forum() {
    echo "<iframe src='http://support.cincopa.com/index.php?title=Cincopa_Multimedia_Platform' width='98%' height='2000px'></iframe>";
}




/**
 * CincopaWidget Class
 */
class CincopaWidget extends WP_Widget {
    /** constructor */
    function CincopaWidget() {
        parent::WP_Widget(false, $name = 'Cincopa Gallery Widget');	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract( $args );
        $gallery = cincopa_plugin('[cincopa '.$instance['galleryid'].']');
        echo $gallery;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
        return $new_instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {				
        $galleryid = esc_attr($instance['galleryid']);
        ?>
<p>
	<label for=""
		<?php echo $this->get_field_id('galleryid'); ?>"><?php _e('Gallery ID:'); ?> <a target="_blank" href="http://support.cincopa.com/index.php?title=Cincopa_Multimedia_Platform/How_To#How_do_I_add_a_gallery_to_my_Wordpress_sidebar_.3F">what?</a> <input class="widefat" id=""<?php echo $this->get_field_id('galleryid'); ?>" name="<?php echo $this->get_field_name('galleryid'); ?>" type="text" value="<?php echo $galleryid; ?>" />
	</label>
</p>
<?php 
    }

} // class CincopaWidget

// register CincopaWidget widget
add_action('widgets_init', create_function('', 'return register_widget("CincopaWidget");'));





?>