<?php
/*
Plugin Name: Linkable Title Html and Php Widget by PepLamb
Plugin URI: http://peplamb.com/linkable-title-html-and-php-widget/
Description: <a href="http://peplamb.com/linkable-title-html-and-php-widget/" target="_blank">Linkable Title Html and Php Widget</a> by <a href="http://peplamb.com/linkable-title-html-and-php-widget/" target="_blank">PepLamb</a>! Using this plugin you may have Text, HTML, Javascript, Flash and/or Php as content in this widget with linkable widget titles, so this is a plus compared to the default wordpress' text widget. 
Version: 1.0.4
Author: PepLamb
Author URI: http://peplamb.com/
*/
/*  Copyright 2010  PepLamb  (email : contact@peplamb.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
class LinkableTitleHtmlAndPhpWidget extends WP_Widget {

    function LinkableTitleHtmlAndPhpWidget() {
        $widget_ops = array('classname' => 'widget_text', 'description' => __('Linkable Title Html and Php Widget by PepLamb'));
        $control_ops = array('width' => 400, 'height' => 350);
        $this->WP_Widget('LinkableTitleHtmlAndPhpWidget', __('Linkable Title Html and Php Widget'), $widget_ops, $control_ops);
    }

    function widget( $args, $instance ) {
        extract($args);
        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title']);
        $titleUrl = apply_filters('widget_title', empty($instance['titleUrl']) ? '' : $instance['titleUrl']);
        $newWindow = $instance['newWindow'] ? '1' : '0';
        $text = apply_filters( 'widget_text', $instance['text'] );
        echo $before_widget;
        if( $titleUrl && $title )
            $title = '<a href="'.$titleUrl.'"'.($newWindow == '1'?' target="_blank"':'').' title="'.$title.'">'.$title.'</a>';
        if ( !empty( $title ) ) { echo $before_title . $title . $after_title; } ?>
            <div class="textwidget"><?php echo $instance['filter'] ? wpautop(eval("?>".$text."<?php ")) : eval("?>".$text."<?php "); ?></div>
        <?php
        echo $after_widget;
    }

    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['titleUrl'] = strip_tags($new_instance['titleUrl']);
        $instance['newWindow'] = $new_instance['newWindow'] ? 1 : 0;
        if ( current_user_can('unfiltered_html') )
            $instance['text'] =  $new_instance['text'];
        else
            $instance['text'] = wp_filter_post_kses( $new_instance['text'] );
        $instance['filter'] = isset($new_instance['filter']);
        return $instance;
    }

    function form( $instance ) {
        $instance = wp_parse_args( (array) $instance, array( 'title' => '', 'titleUrl' => '', 'text' => '' ) );
        $title = strip_tags($instance['title']);
        $titleUrl = strip_tags($instance['titleUrl']);
        $newWindow = $instance['newWindow'] ? 'checked="checked"' : '';
        $text = format_to_edit($instance['text']);
?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>
        <p><label for="<?php echo $this->get_field_id('titleUrl'); ?>"><?php _e('Title Url:'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('titleUrl'); ?>" name="<?php echo $this->get_field_name('titleUrl'); ?>" type="text" value="<?php echo esc_attr($titleUrl); ?>" /></p>
        <p><input class="checkbox" type="checkbox" <?php echo $newWindow; ?> id="<?php echo $this->get_field_id('newWindow'); ?>" name="<?php echo $this->get_field_name('newWindow'); ?>" />
        <label for="<?php echo $this->get_field_id('newWindow'); ?>"><?php _e('Open the link/url in a new window'); ?></label></p>
        
        <p><label for="<?php echo $this->get_field_id('text'); ?>"><?php _e('Text, Html, Javascript, Flash and/or Php:'); ?></label>
        <textarea class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea>

        <p><input id="<?php echo $this->get_field_id('filter'); ?>" name="<?php echo $this->get_field_name('filter'); ?>" type="checkbox" <?php checked($instance['filter']); ?> />&nbsp;<label for="<?php echo $this->get_field_id('filter'); ?>"><?php _e('Automatically add paragraphs.'); ?></label></p>
          <p>
            <center>
              If you like this widget and find it useful, help keep this plugin free and actively developed by clicking the donate button<br />
              <a style="text-decoration:none;" href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TV873GDVX3MQC&lc=US&item_name=PepLamb&item_number=Linkable%20Title%20Html%20and%20Php%20Widget&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted">
                <img src="<?php echo WP_PLUGIN_URL; ?>/linkable-title-html-and-php-widget/images/paypal.gif" />
              </a>
            </center>
            <div id="sideblock" style="float:left;width:220px;margin-left:10px;">
              <h2>Information</h2>
              <div id="dbx-content" style="text-decoration:none;">
                <img src="<?php echo WP_PLUGIN_URL; ?>/linkable-title-html-and-php-widget/images/home.png"><a target="_blank" style="text-decoration:none;" href="http://peplamb.com/linkable-title-html-and-php-widget/"> Plugin Home</a><br />
                <img src="<?php echo WP_PLUGIN_URL; ?>/linkable-title-html-and-php-widget/images/rate.png"><a target="_blank" style="text-decoration:none;" href="http://wordpress.org/extend/plugins/linkable-title-html-and-php-widget/"> Rate this plugin</a><br />
                <img src="<?php echo WP_PLUGIN_URL; ?>/linkable-title-html-and-php-widget/images/help.png"><a target="_blank" style="text-decoration:none;" href="http://peplamb.com/linkable-title-html-and-php-widget/#respond"> Support and Help</a><br />
                <br />
                <a target="_blank" style="text-decoration:none;" href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TV873GDVX3MQC&lc=US&item_name=PepLamb&item_number=Linkable%20Title%20Html%20and%20Php%20Widget&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted">
                  <img src="<?php echo WP_PLUGIN_URL; ?>/linkable-title-html-and-php-widget/images/paypal.gif">
                </a>
                <br /><br />
                <img src="<?php echo WP_PLUGIN_URL; ?>/linkable-title-html-and-php-widget/images/twit.png"><a target="_blank" style="text-decoration:none;" href="http://twitter.com/peplamb/"> Follow updates on Twitter</a><br />
              </div>
            </div>
          </p><!-- Linkable Title Html and Php Widget by PepLamb (PepLamb.com) -->
<?php
    }
}
function LinkableTitleHtmlAndPhpWidgetInit() {
    register_widget('LinkableTitleHtmlAndPhpWidget');
}

add_action('widgets_init', 'LinkableTitleHtmlAndPhpWidgetInit');
?>