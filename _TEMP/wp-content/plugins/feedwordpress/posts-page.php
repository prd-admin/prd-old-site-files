<?php
require_once(dirname(__FILE__) . '/admin-ui.php');
require_once(dirname(__FILE__) . '/updatedpostscontrol.class.php');

class FeedWordPressPostsPage extends FeedWordPressAdminPage {
	var $link = NULL;
	var $updatedPosts = NULL;

	/**
	 * Construct the posts page object.
	 *
	 * @param mixed $link An object of class {@link SyndicatedLink} if created for one feed's settings, NULL if created for global default settings
	 */
	function FeedWordPressPostsPage ($link = NULL) {
		FeedWordPressAdminPage::FeedWordPressAdminPage('feedwordpresspostspage', $link);
		$this->dispatch = 'feedwordpress_posts_settings';
		$this->filename = __FILE__;
		$this->updatedPosts = new UpdatedPostsControl($this);
	} /* FeedWordPressPostsPage constructor */

	function accept_POST ($post) {
		global $wpdb;
		
		$link_id = $this->link->id;

		// User mashed a Save Changes button
		if (isset($post['save']) or isset($post['submit'])) :
			// custom post settings
			foreach ($post['notes'] as $mn) :
				$mn['key0'] = trim($mn['key0']);
				$mn['key1'] = trim($mn['key1']);
	
				if (strlen($mn['key0']) > 0) :
					unset($custom_settings[$mn['key0']]); // out with the old
				endif;
				
				if (($mn['action']=='update') and (strlen($mn['key1']) > 0)) :
					$custom_settings[$mn['key1']] = $mn['value']; // in with the new
				endif;
			endforeach;
	
			$this->updatedPosts->accept_POST($post);
			if ($this->for_feed_settings()) :
				$alter = array ();
	
				$this->link->settings['postmeta'] = serialize($custom_settings);
	
				if (isset($post['resolve_relative'])) :
					$this->link->settings['resolve relative'] = $post['resolve_relative'];
				endif;
				
				// Post status, comment status, ping status
				foreach (array('post', 'comment', 'ping') as $what) :
					$sfield = "feed_{$what}_status";
					if (isset($post[$sfield])) :
						if ($post[$sfield]=='site-default') :
							unset($this->link->settings["{$what} status"]);
						else :
							$this->link->settings["{$what} status"] = $post[$sfield];
						endif;
					endif;
				endforeach;

				$alter[] = "link_notes = '".$wpdb->escape($this->link->settings_to_notes())."'";
				$alter_set = implode(", ", $alter);
	
				// issue update query
				$result = $wpdb->query("
				UPDATE $wpdb->links
				SET $alter_set
				WHERE link_id='$link_id'
				");
				$this->updated = true;
			
				// reload link information from DB
				if (function_exists('clean_bookmark_cache')) :
					clean_bookmark_cache($link_id);
				endif;
				$link =& new SyndicatedLink($link_id);
			else :
				// update_option ...
				if (isset($post['feed_post_status'])) :
					update_option('feedwordpress_syndicated_post_status', $post['feed_post_status']);
				endif;

				update_option('feedwordpress_custom_settings', serialize($custom_settings));
	
				update_option('feedwordpress_munge_permalink', $_REQUEST['munge_permalink']);
				update_option('feedwordpress_use_aggregator_source_data', $_REQUEST['use_aggregator_source_data']);
				update_option('feedwordpress_formatting_filters', $_REQUEST['formatting_filters']);
	
				if (isset($post['resolve_relative'])) :
					update_option('feedwordpress_resolve_relative', $post['resolve_relative']);
				endif;
				if (isset($_REQUEST['feed_comment_status']) and ($_REQUEST['feed_comment_status'] == 'open')) :
					update_option('feedwordpress_syndicated_comment_status', 'open');
				else :
					update_option('feedwordpress_syndicated_comment_status', 'closed');
				endif;

				if (isset($_REQUEST['feed_ping_status']) and ($_REQUEST['feed_ping_status'] == 'open')) :
					update_option('feedwordpress_syndicated_ping_status', 'open');
				else :
					update_option('feedwordpress_syndicated_ping_status', 'closed');
				endif;
		
				$this->updated = true;
			endif;

		// Probably a "Go" button for the drop-down
		else :
			$this->updated = false;
		endif;		
	}

	/**
	 * Outputs "Publication" settings box.
	 *
	 * @since 2009.0713
	 * @param object $page of class FeedWordPressPostsPage tells us whether this is
	 *	a page for one feed's settings or for global defaults
	 * @param array $box
	 *
	 * @uses FeedWordPressPostsPage::these_posts_phrase()
	 * @uses FeedWordPress::syndicated_status()
	 * @uses SyndicatedLink::syndicated_status()
	 * @uses SyndicatedPost::use_api()
	 * @uses fwp_option_box_opener()
	 * @uses fwp_option_box_closer()
	 */ 
	/*static*/ function publication_box ($page, $box = NULL) {
		global $fwp_path;
	
		$post_status_global = FeedWordPress::syndicated_status('post', /*default=*/ 'publish');
		$thesePosts = $page->these_posts_phrase();
	
		// Set up array for selector
		$setting = array(
			'publish' => array ('label' => "Publish %s immediately", 'checked' => ''),
			'draft' => array('label' => "Save %s as drafts", 'checked' => ''),
			'private' => array('label' => "Save %s as private posts", 'checked' => ''),
		);
		if (SyndicatedPost::use_api('post_status_pending')) :
			$setting['pending'] = array('label' => "Hold %s for review; mark as Pending", 'checked' => '');
		endif;


		if ($page->for_feed_settings()) :
			$href = $fwp_path.'/'.basename(__FILE__);
			$currently = str_replace('%s', '', strtolower(strtok($setting[$post_status_global]['label'], ';')));
			$setting['site-default'] = array('label' => "Use <a href=\"admin.php?page=${href}\">site-wide setting</a>", 'checked' => '');
			$setting['site-default']['label'] .= " (currently: <strong>${currently}</strong>)";
	
			$checked = $page->link->syndicated_status('post', 'site-default', /*fallback=*/ false);
		else :
			$checked = $post_status_global;
		endif;
	
		// Re-order appropriately
		$selector = array();
		$order = array(
			'site-default',
			'publish',
			'pending',
			'draft',
			'private',
		);
		foreach ($order as $line) :
			if (isset($setting[$line])) :
				$selector[$line] = $setting[$line];
			endif;
		endforeach;
		$selector[$checked]['checked'] = ' checked="checked"';
	
		// Hey ho, let's go...
		?>
		<style type="text/css">
		#syndicated-publication-form th { width: 27%; vertical-align: top; }
		#syndicated-publication-form td { width: 73%; vertical-align: top; }
		</style>
	
		<table id="syndicated-publication-form" class="form-table" cellspacing="2" cellpadding="5">
		<tr><th scope="row"><?php _e('New posts:'); ?></th>
		<td><ul class="options">
		<?php foreach ($selector as $code => $li) : ?>
			<li><label><input type="radio" name="feed_post_status"
			value="<?php print $code; ?>"<?php print $li['checked']; ?> />
			<?php print str_replace('%s', $thesePosts, $li['label']); ?></label></li>
		<?php endforeach; ?>
		</ul></td>
		</tr>

		<?php $page->updatedPosts->display(); ?>
		</table>
	
		<?php
	} /* FeedWordPressPostsPage::publication_box () */
	
	/**
	 * Outputs "Formatting" settings box
	 *
	 * @since 2009.0713
	 * @param object $page of class FeedWordPressPostsPage tells us whether this is
	 *	a page for one feed's settings or for global defaults
	 * @param array $box
	 *
	 * @uses fwp_option_box_opener()
	 * @uses fwp_option_box_closer()
	 */ 
	function formatting_box ($page, $box = NULL) {
		global $fwp_path;
		$thesePosts = $page->these_posts_phrase();
		$global_resolve_relative = get_option('feedwordpress_resolve_relative', 'yes');
		if ($page->for_feed_settings()) :
			$formatting_filters = null;
			$resolve_relative = $page->link->setting('resolve relative', NULL, 'default');
			$url = preg_replace('|/+$|', '', $page->link->homepage());
			$setting = array(
				'yes' => __('resolve relative URIs'),
				'no' => __('leave relative URIs unresolved'),
			);
			$href = $fwp_path.'/'.basename(__FILE__);
		else :
			$formatting_filters = get_option('feedwordpress_formatting_filters', 'no');
			$resolve_relative = $global_resolve_relative;
			$url = 'http://example.com';
		endif;
		?>
		<table class="form-table" cellspacing="2" cellpadding="5">
		<?php if (!is_null($formatting_filters)) : ?>

		  <tr><th scope="row">Formatting filters:</th>
		  <td><select name="formatting_filters" size="1">
		  <option value="no"<?php echo ($formatting_filters!='yes')?' selected="selected"':''; ?>>Protect syndicated posts from formatting filters</option>
		  <option value="yes"<?php echo ($formatting_filters=='yes')?' selected="selected"':''; ?>>Expose syndicated posts to formatting filters</option>
		  </select>
		  <p class="setting-description">If you have trouble getting plugins to work that are supposed to insert
		  elements after posts (like relevant links or a social networking
		  <q>Share This</q> button), try changing this option to see if it fixes your
		  problem.</p>
		  </td></tr>

		<?php endif; ?>
		
		<tr><th scope="row">Relative URIs:</th>
		<td>If link or image in a syndicated post from <code><?php print $url; ?></code>
		refers to a partial URI like <code>/about</code>, where should
		the syndicated copy point to?</p>

		<ul>
		<?php if ($page->for_feed_settings()) : ?>
		<li><p><label><input type="radio" name="resolve_relative" value='default' <?php echo ($resolve_relative=='default')?' checked="checked"':''; ?>/> Use <a href="admin.php?page=<?php print $href; ?>">site-wide setting</a><br/>
		<small style="margin-left: 2.0em;">Currently: <strong><?php print $setting[$global_resolve_relative]; ?></strong></small></label></p></li>
		<?php endif; ?>
		<li><p><label><input type="radio" name="resolve_relative" value="yes"<?php echo ($resolve_relative!='no' and $resolve_relative!='default')?' checked="checked"':''; ?>/> Resolve the URI so it points to <code><?php print $url; ?></code><br/>
		<small style="margin-left: 2.0em;"><code>/contact</code> is rewritten as <code><?php print $url; ?>/contact</code></label></small></p></li>
		<li><p><label><input type="radio" name="resolve_relative" value="no"<?php echo ($resolve_relative=='no')?' checked="checked"':''; ?>/> Leave relative URIs unchanged, so they point to this site<br/>
		<small style="margin-left: 2.0em;"><code>/contact</code> is left as <code>/contact</code></small></label></li>
		</ul>
		</td></tr>

		</table>
		<?php
	} /* FeedWordPressPostsPage::formatting_box() */
	
	/**
	 * Output "Links" settings box
	 *
	 * @since 2009.0713
	 * @param object $page of class FeedWordPressPostsPage tells us whether this is
	 *	a page for one feed's settings or for global defaults
	 * @param array $box
	 *
	 * @uses fwp_option_box_opener()
	 * @uses fwp_option_box_closer()
	 */
	/*static*/ function links_box ($page, $box = NULL) {
		$munge_permalink = get_option('feedwordpress_munge_permalink');
		$use_aggregator_source_data = get_option('feedwordpress_use_aggregator_source_data');
		?>
		<table class="form-table" cellspacing="2" cellpadding="5">
		<tr><th  scope="row">Permalinks:</th>
		<td><select name="munge_permalink" size="1">
		<option value="yes"<?php echo ($munge_permalink=='yes')?' selected="selected"':''; ?>>point to the copy on the original website</option>
		<option value="no"<?php echo ($munge_permalink=='no')?' selected="selected"':''; ?>>point to the local copy on this website</option>
		</select></td>
		</tr>
		
		<tr><th scope="row">Posts from aggregator feeds:</th>
		<td><ul class="options">
		<li><label><input type="radio" name="use_aggregator_source_data" value="no"<?php echo ($use_aggregator_source_data!="yes")?' checked="checked"':''; ?>> Give the aggregator itself as the source of posts from an aggregator feed.</label></li>
		<li><label><input type="radio" name="use_aggregator_source_data" value="yes"<?php echo ($use_aggregator_source_data=="yes")?' checked="checked"':''; ?>> Give the original source of the post as the source, not the aggregator.</label></li>
		</ul>
		<p class="setting-description">Some feeds (for example, those produced by FeedWordPress) aggregate content from several different sources, and include information about the original source of the post.
		This setting controls what FeedWordPress will give as the source of posts from
		such an aggregator feed.</p>
		</td></tr>
		</table>

		<?php
	} /* FeedWordPressPostsPage::links_box() */

	/**
	 * Output "Comments & Pings" settings box
	 *
	 * @since 2009.0713
	 * @param object $page of class FeedWordPressPostsPage tells us whether this is
	 *	a page for one feed's settings or for global defaults
	 * @param array $box
	 *
	 * @uses fwp_option_box_opener()
	 * @uses fwp_option_box_closer()
	 */
	/*static*/ function comments_and_pings_box ($page, $box = NULL) {
		$setting = array();
		$selector = array();

		$whatsits = array(
			'comment' => array('label' => __('Comments'), 'accept' => 'Allow comments'),
			'ping' => array('label' => __('Pings'), 'accept' => 'Accept pings'),
		);
		$onThesePosts = 'on '.$page->these_posts_phrase();

		foreach ($whatsits as $what => $how) :
			$whatsits[$what]['default'] = FeedWordPress::syndicated_status($what, /*default=*/ 'closed');

			// Set up array for selector
			$setting = array(
				'open' => array ('label' => "{$how['accept']} %s", 'checked' => ''),
				'closed' => array('label' => "Don't ".strtolower($how['accept'])." %s", 'checked' => ''),
			);
			if ($page->for_feed_settings()) :
				$href = $fwp_path.'/'.basename(__FILE__);
				$currently = trim(str_replace('%s', '', strtolower(strtok($setting[$whatsits[$what]['default']]['label'], ';'))));
				$setting['site-default'] = array('label' => "Use <a href=\"admin.php?page=${href}\">site-wide setting</a>", 'checked' => '');
				$setting['site-default']['label'] .= " (currently: <strong>${currently}</strong>)";
		
				$checked = $page->link->syndicated_status($what, 'site-default', /*fallback=*/ false);
			else :
				$checked = $whatsits[$what]['default'];
			endif;

			// Re-order appropriately
			$selector[$what] = array();
			$order = array(
				'site-default',
				'open',
				'closed',
			);
			foreach ($order as $line) :
				if (isset($setting[$line])) :
					$selector[$what][$line] = $setting[$line];
				endif;
			endforeach;
			$selector[$what][$checked]['checked'] = ' checked="checked"';
		endforeach;

		// Hey ho, let's go...
		?>
		<table class="form-table" cellspacing="2" cellpadding="5">
		<?php foreach ($whatsits as $what => $how) : ?>
		  <tr><th scope="row"><?php print $how['label']; ?>:</th>
		  <td><ul class="options">
		  <?php foreach ($selector[$what] as $code => $li) : ?>
		    <li><label><input type="radio" name="feed_<?php print $what; ?>_status"
		    value="<?php print $code; ?>"<?php print $li['checked']; ?> />
		    <?php print trim(str_replace('%s', $onThesePosts, $li['label'])); ?></label></li>
		  <?php endforeach; ?>
		  </ul></td></tr>
		<?php endforeach; ?>
		</table>

		<?php
	} /* FeedWordPressPostsPage::comments_and_pings_box() */
	
	/**
	 * Output "Custom Post Settings" settings box
	 *
	 * @since 2009.0713
	 * @param object $page of class FeedWordPressPostsPage tells us whether this is
	 *	a page for one feed's settings or for global defaults
	 * @param array $box
	 *
	 * @uses fwp_option_box_opener()
	 * @uses fwp_option_box_closer()
	 */
	/*static*/ function custom_post_settings_box ($page, $box = NULL) {
		if ($page->for_feed_settings()) :
			$custom_settings = $page->link->settings["postmeta"];
			if ($custom_settings and !is_array($custom_settings)) :
				$custom_settings = unserialize($custom_settings);
			endif;
			
			if (!is_array($custom_settings)) :
				$custom_settings = array();
			endif;
		else :
			$custom_settings = get_option('feedwordpress_custom_settings');
			if ($custom_settings and !is_array($custom_settings)) :
				$custom_settings = unserialize($custom_settings);
			endif;
	
			if (!is_array($custom_settings)) :
				$custom_settings = array();
			endif;
		endif;

		?>
		<div id="postcustomstuff">
		<table id="meta-list" cellpadding="3">
		<tr>
		<th>Key</th>
		<th>Value</th>
		<th>Action</th>
		</tr>

		<?php
		$i = 0;
		foreach ($custom_settings as $key => $value) :
		?>
		  <tr style="vertical-align:top">
		    <th width="30%" scope="row"><input type="hidden" name="notes[<?php echo $i; ?>][key0]" value="<?php echo wp_specialchars($key, 'both'); ?>" />
		    <input id="notes-<?php echo $i; ?>-key" name="notes[<?php echo $i; ?>][key1]" value="<?php echo wp_specialchars($key, 'both'); ?>" /></th>
		    <td width="60%"><textarea rows="2" cols="40" id="notes-<?php echo $i; ?>-value" name="notes[<?php echo $i; ?>][value]"><?php echo wp_specialchars($value, 'both'); ?></textarea></td>
		    <td width="10%"><select name="notes[<?php echo $i; ?>][action]">
		    <option value="update">save changes</option>
		    <option value="delete">delete this setting</option>
		    </select></td>
		  </tr>

		<?php
			$i++;
		endforeach;
		?>

		  <tr>
		    <th scope="row"><input type="text" size="10" name="notes[<?php echo $i; ?>][key1]" value="" /></th>
		    <td><textarea name="notes[<?php echo $i; ?>][value]" rows="2" cols="40"></textarea></td>
		    <td><em>add new setting...</em><input type="hidden" name="notes[<?php echo $i; ?>][action]" value="update" /></td>
		  </tr>
		</table>
		</div> <!-- id="postcustomstuff" -->

		<?php
	 } /* FeedWordPressPostsPage::custom_post_settings_box() */
}

function fwp_posts_page () {
	global $wpdb, $wp_db_version;
	global $fwp_post;

	if (FeedWordPress::needs_upgrade()) :
		fwp_upgrade_page();
		return;
	endif;

	// If this is a POST, validate source and user credentials
	FeedWordPressCompatibility::validate_http_request(/*action=*/ 'feedwordpress_posts_settings', /*capability=*/ 'manage_links');

	$link = FeedWordPressAdminPage::submitted_link();
	$link_id = $link->id;
	$postsPage = new FeedWordPressPostsPage($link);

	$mesg = null;

	////////////////////////////////////////////////
	// Process POST request, if any ////////////////
	////////////////////////////////////////////////
	if (strtoupper($_SERVER['REQUEST_METHOD'])=='POST') :
		$postsPage->accept_POST($fwp_post);
		do_action('feedwordpress_admin_page_posts_save', $fwp_post, $postsPage);
	endif;
	
	$postsPage->ajax_interface_js();

	if ($postsPage->updated) : ?>
<div class="updated"><p>Syndicated posts settings updated.</p></div>
<?php elseif (!is_null($mesg)) : ?>
<div class="updated"><p><?php print wp_specialchars($mesg, 1); ?></p></div>
<?php endif; ?>

<?php
	$links = FeedWordPress::syndicated_links();
	$postsPage->open_sheet('Syndicated Posts & Links');
?>
<style type="text/css">
	table.edit-form th, table.form-table th { width: 27%; vertical-align: top; }
	table.edit-form td, table.form-table td { width: 73%; vertical-align: top; }
	ul.options { margin: 0; padding: 0; list-style: none; }
</style>
<div id="post-body">
<?php
$boxes_by_methods = array(
	'publication_box' => __('Syndicated Posts'),
	'links_box' => __('Links'),
	'formatting_box' => __('Formatting'),
	'comments_and_pings_box' => __('Comments & Pings'),
	'custom_post_settings_box' => __('Custom Post Settings (to apply to each syndicated post)'),
);

// Feed-level settings don't exist for these.
if ($postsPage->for_feed_settings()) :
	unset($boxes_by_methods['links_box']);
endif;

	foreach ($boxes_by_methods as $method => $title) :
		fwp_add_meta_box(
			/*id=*/ 'feedwordpress_'.$method,
			/*title=*/ $title,
			/*callback=*/ array('FeedWordPressPostsPage', $method),
			/*page=*/ $postsPage->meta_box_context(),
			/*context=*/ $postsPage->meta_box_context()
		);
	endforeach;
	do_action('feedwordpress_admin_page_posts_meta_boxes', $postsPage);
?>
	<div class="metabox-holder">
<?php
	fwp_do_meta_boxes($postsPage->meta_box_context(), $postsPage->meta_box_context(), $postsPage);
?>
	</div> <!-- class="metabox-holder" -->
</div> <!-- id="post-body" -->
<?php
	$postsPage->close_sheet();
} /* function fwp_posts_page () */

	fwp_posts_page();

