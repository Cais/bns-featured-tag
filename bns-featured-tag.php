<?php
/*
Plugin Name: BNS Featured Tag
Plugin URI: http://buynowshop.com/plugins/bns-featured-tag/
Description: Plugin with multi-widget functionality that displays most recent posts from specific tag or tags (set with user options). Also includes user options to display: Tag Description; Author and meta details; comment totals; post categories; post tags; and either full post or excerpt (or any combination).
Version: 2.5
Author: Edward Caissie
Author URI: http://edwardcaissie.com/
Textdomain: bns-ft
License: GNU General Public License v2
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

/**
 * BNS Featured Tag WordPress plugin
 *
 * Plugin with multi-widget functionality that displays most recent posts from
 * specific tag or tags (set with user options). Also includes user options to
 * display: Tag Description; Author and meta details; comment totals; post
 * categories; post tags; and either full post or excerpt (or any combination).
 *
 * @package     BNS_Featured_Tag
 * @link        http://buynowshop.com/plugins/bns-featured-tag/
 * @link        https://github.com/Cais/bns-featured-tag/
 * @link        http://wordpress.org/extend/plugins/bns-featured-tag/
 * @version     2.5
 * @author      Edward Caissie <edward.caissie@gmail.com>
 * @copyright   Copyright (c) 2009-2013, Edward Caissie
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2, as published by the
 * Free Software Foundation.
 *
 * You may NOT assume that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to:
 *
 *      Free Software Foundation, Inc.
 *      51 Franklin St, Fifth Floor
 *      Boston, MA  02110-1301  USA
 *
 * The license for this software can also likely be found here:
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @version 2.4
 * @date    July 14, 2013
 * Added feature as requested http://wordpress.org/support/topic/is-it-possible-to-exclude-current-post
 * Completed use current post tags in single view option / functionality
 *
 * @version 2.4.1
 * @date    November 2013
 *
 * @version 2.5
 * @date    ...
 * Added new "union" option so posts must be in all tags chosen
 *
 * @todo Add Link to title option
 */
class BNS_Featured_Tag_Widget extends WP_Widget {

    /**
     * Constructor
     */
    function BNS_Featured_Tag_Widget() {
        /** Widget settings */
        $widget_ops = array( 'classname' => 'bns-featured-tag', 'description' => __( 'Displays most recent posts from a specific featured tag or tags.', 'bns-ft' ) );

        /** Widget control settings */
        $control_ops = array( 'width' => 200, 'id_base' => 'bns-featured-tag' );

        /** Create the widget */
        $this->WP_Widget( 'bns-featured-tag', 'BNS Featured Tag', $widget_ops, $control_ops );

        /**
         * Check installed WordPress version for compatibility
         * @internal    Requires WordPress version 2.9
         * @internal    @uses current_theme_supports
         * @internal    @uses the_post_thumbnail
         * @internal    @uses has_post_thumbnail
         */
        global $wp_version;
        $exit_message = 'BNS Featured Tag requires WordPress version 2.9 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please Update!</a>';
        if ( version_compare( $wp_version, "2.9", "<") ) {
            exit ( $exit_message );
        } /** End if - version compare */

        /** Add Scripts and Styles */
        add_action( 'wp_enqueue_scripts', array( $this, 'BNSFT_Scripts_and_Styles' ) );

        /** Add Options Scripts and Styles */
        add_action( 'admin_enqueue_scripts', array( $this, 'BNSFT_Options_Scripts_and_Styles' ) );

        /** Add Shortcode */
        add_shortcode( 'bnsft', array( $this, 'bnsft_shortcode' ) );

        /** Add load_bnsft_widget function to the widgets_init hook */
        add_action( 'widgets_init', array( $this, 'load_bnsft_widget' ) );

    } /** End function - constructor */


    /**
     * BNS Featured Tag Custom Excerpt
     *
     * Strips the post content of tags and returns the entire post content if there
     * are less than $length words; otherwise the amount of words equal to $length
     * is returned. In both cases, the returned text is appended with a permalink to
     * the full post.
     *
     * @package BNS_Featured_Tag
     * @since   1.9
     *
     * @param   $text - post content
     * @param   int $length - user defined amount of words
     *
     * @uses    apply_filters
     * @uses    get_permalink
     * @uses    the_title_attribute
     *
     * @return  string
     *
     * @version 2.2
     * @date    December 1, 2012
     * Added filter to full post link element
     */
    function bnsft_custom_excerpt( $text, $length = 55 ) {
        $text = strip_tags( $text );
        $words = explode( ' ', $text, $length + 1 );

        /** @var $link_symbol - default: infinity symbol */
        $link_symbol = apply_filters( 'bnsft_link_symbol', '&infin;' );

        /** Create link to full post for end of custom length excerpt output */
        $bnsft_link = ' <strong>
            <a class="bnsft-link" href="' . get_permalink() . '" title="' . the_title_attribute( array( 'before' => __( 'Permalink to: ', 'bns-ft' ), 'after' => '', 'echo' => false ) ) . '">'
                . $link_symbol .
            '</a>
        </strong>';

        if ( ( ! $length ) || ( count( $words ) < $length ) ) {
            $text .= $bnsft_link;
            return $text;
        } else {
            array_pop( $words );
            array_push( $words, '...' );
            $text = implode( ' ', $words );
        } /** End if - not length */

        $text .= $bnsft_link;

        return $text;

    } /** End function - custom excerpt */


    /**
     * Enqueue Plugin Scripts and Styles
     *
     * @package BNS_Featured_Tag
     * @since   1.9
     *
     * @uses    get_plugin_data
     * @uses    plugin_dir_path
     * @uses    plugin_dir_url
     * @uses    wp_enqueue_style
     *
     * @internal Used with action: wp_enqueue_styles
     *
     * @version 1.9.1
     * @date    December 14, 2011
     * Fixed 404 error when 'bnsft-custom-style.css' is not available
     *
     * @version 2.2
     * @date    December 1, 2012
     * Programmatically add version number to enqueue calls
     */
    function BNSFT_Scripts_and_Styles() {
        /** Call the wp-admin plugin code */
        require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
        /** @var $bnsfc_data - holds the plugin header data */
        $bnsft_data = get_plugin_data( __FILE__ );

        /** Enqueue Scripts */
        /** Enqueue Styles */
        wp_enqueue_style( 'BNSFT-Style', plugin_dir_url( __FILE__ ) . 'bnsft-style.css', array(), $bnsft_data['Version'], 'screen' );
        if ( is_readable( plugin_dir_path( __FILE__ ) . 'bnsft-custom-style.css' ) ) {
            wp_enqueue_style( 'BNSFT-Custom-Style', plugin_dir_url( __FILE__ ) . 'bnsft-custom-style.css', array(), $bnsft_data['Version'], 'screen' );
        } /** End if - is readable */

    } /** End function - scripts and styles */


    /**
     * Enqueue Options Plugin Scripts and Styles
     *
     * Add plugin options scripts and stylesheet(s) to be used only on the Administration Panels
     *
     * @package BNS_Featured_Category
     * @since   2.0
     *
     * @uses    plugin_dir_path
     * @uses    plugin_dir_url
     * @uses    wp_enqueue_script
     * @uses    wp_enqueue_style
     *
     * @internal 'jQuery' is enqueued as a dependency of the 'bnsft-options.js' enqueue
     * @internal Used with action: admin_enqueue_scripts
     */
    function BNSFT_Options_Scripts_and_Styles() {
        /** Call the wp-admin plugin code */
        require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
        /** @var $bnsfc_data - holds the plugin header data */
        $bnsft_data = get_plugin_data( __FILE__ );

        /** Enqueue Options Scripts */
        wp_enqueue_script( 'bnsft-options', plugin_dir_url( __FILE__ ) . 'bnsft-options.js', array( 'jquery' ), $bnsft_data['Version'] );
        /** Enqueue Options Style Sheets */
        wp_enqueue_style( 'BNSFT-Option-Style', plugin_dir_url( __FILE__ ) . 'bnsft-option-style.css', array(), $bnsft_data['Version'], 'screen' );
        if ( is_readable( plugin_dir_path( __FILE__ ) . 'bnsft-options-custom-style.css' ) ) {
            wp_enqueue_style( 'BNSFT-Options-Custom-Style', plugin_dir_url( __FILE__ ) . 'bnsft-options-custom-style.css', array(), $bnsft_data['Version'], 'screen' );
        } /** End if - is readable */

    } /** End function - options scripts and styles */


    /**
     * Widget
     *
     * @param   array $args
     * @param   array $instance
     *
     * @version 2.3.1
     * @date    February 17, 2013
     * Fixed where content and excerpt post thumbnail sizes are used
     *
     * @version 2.4
     * @date    July 14, 2013
     * Added exclude current post option
     * Completed use current post tags option
     */
    function widget( $args, $instance ) {
        extract( $args );

        /** User-selected settings */
        $title              = apply_filters( 'widget_title', $instance['title'] );
        $tag_choice         = $instance['tag_choice'];
        $union              = $instance['union'];
        $use_current        = $instance['use_current'];
        $exclude_current    = $instance['exclude_current'];
        $show_count         = $instance['show_count'];
        $offset             = $instance['offset'];
        $sort_order         = $instance['sort_order'];
        $use_thumbnails     = $instance['use_thumbnails'];
        $content_thumb      = $instance['content_thumb'];
        $excerpt_thumb      = $instance['excerpt_thumb'];
        $show_meta          = $instance['show_meta'];
        $show_comments      = $instance['show_comments'];
        $show_cats          = $instance['show_cats'];
        $show_tags          = $instance['show_tags'];
        $show_tag_desc      = $instance['show_tag_desc'];
        $only_titles        = $instance['only_titles'];
        $no_titles          = $instance['no_titles'];
        $show_full          = $instance['show_full'];
        $excerpt_length     = $instance['excerpt_length'];
        $no_excerpt         = $instance['no_excerpt'];
        /** Plugin requires counter variable to be part of its arguments?! */
        $count              = $instance['count'];

        /** @var    $before_widget  string - defined by theme */
        echo $before_widget;

        /** Widget $title $before_widget and $after_widget defined by theme */
        if ( $title ) {
            /** @var string $before_title - defined by theme */
            /** @var string $after_title - defined by theme */
            echo $before_title . $title . $after_title;
        } /** End if - title */

        /** Use current post tag(s) when in single view */
        if ( is_single() && $use_current ) {
            /** Get the global post object to find the tags */
            global $post;
            /** @var $tag_choice - clear current choice(s) */
            $tag_choice = '';
            /** Loop through current post tags and add them to tag choice */
            foreach( get_the_tags( $post->ID ) as $current_tag ) {
                $tag_choice .= $current_tag->name . ', ';
            } /** End foreach - get the tags */
        } /** End if - is single and use current */

        /** Replace spaces with hyphens to create tag slugs from the name */
        $tag_choice = str_replace( ' ', '-', $tag_choice );

        /** Remove leading hyphens from tag slugs if multiple tag names are entered with leading spaces */
        $tag_choice = str_replace( ',-', ', ', $tag_choice );

        /** @var array $query_args - holds query arguments to be passed */
        $query_args = array(
            'tag'               => $tag_choice,
            'posts_per_page'    => $show_count,
            'offset'            => $offset
        );

        /** Do not include current post in single view */
        if ( is_single() && $exclude_current ) {
            $excluded_post = get_the_ID();
            $query_args = array_merge( $query_args, array( 'post__not_in' => array( $excluded_post ) ) );
        } /** End if - is single and exclude current */

        /**
         * Check if $sort_order is set to rand (random) and use the `orderby`
         * parameter; otherwise use the `order` parameter
         */
        if ( 'rand' == $sort_order ) {
            $query_args = array_merge( $query_args, array( 'orderby' => $sort_order ) );
        } else {
            $query_args = array_merge( $query_args, array( 'order' => $sort_order ) );
        } /** End if - set query argument parameter */

        /**
         * Check if post need to be in *all* tags and make necessary
         * changes to the data so it can be correctly used
         */
        if ( $union ) {

            /** Remove the default use any tag parameter */
            unset( $query_args['tag'] );

            /** @var string $union_tag_choice - $tag_choice without spaces */
            $union_tag_choice = preg_replace( '/\s+/', '', $tag_choice );
            /** @var array $tag_choice_union - derived from the string */
            $tag_choice_union = explode( ",", $union_tag_choice );

            /** Sanity testing? - Change strings to integer values */
            foreach ( $tag_choice_union AS $index => $value )
                $tag_choice_union[$index] = (int)$value;

            /** @var array $query_args - merged new query arguments */
            $query_args = array_merge( $query_args, array( 'tag__and' => $tag_choice_union ) );

        } /** End if - do we want to use a union of the categories */

        if ( is_single() && $union ) {

            /** Remove the use all tags parameter */
            unset( $query_args['tag__and'] );

            /** @var array $query_args - add the all tags parameter back */
            $query_args = array_merge( $query_args, array( 'tag' => $tag_choice ) );

        } /** End if - is single and union */

        /** @var $bnsft_query - object of posts matching query criteria */
        $bnsft_query = false;

        /** Allow query to be completely over-written via `bnsft_query` hook */
        apply_filters( 'bnsft_query', $bnsft_query );

        if ( false == $bnsft_query ) {
            $bnsft_query = new WP_Query( $query_args );
        } /** End if - bnsft query is false, use plugin arguments */

        /** @var $bnsft_output - hook test */
        $bnsft_output = false;

        /** Allow entire output to be filtered via hook `bnsft_output` */
        apply_filters( 'bnsft_output', $bnsft_output );

        if ( false == $bnsft_output ) {

            if ( $show_tag_desc ) {
                echo '<div class="bnsft-tag-desc">' . tag_description() . '</div>';
            } /** End if - show tag description */

            /** Display posts from widget settings */
            if ( $bnsft_query->have_posts()) : while ( $bnsft_query->have_posts() ) : $bnsft_query->the_post();

                if ( $count == $show_count ) {
                    break;
                } else { ?>

                    <div <?php post_class(); ?>>

                        <?php if ( ! $no_titles ) { ?>
                            <strong><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-ft' ); ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a></strong>
                        <?php } ?>

                        <div class="post-details">

                            <?php if ( $show_meta ) {
                                echo apply_filters( 'bnsfc_show_meta', sprintf( __( 'by %1$s on %2$s', 'bns-fc' ), get_the_author(), get_the_time( get_option( 'date_format' ) ) ) ); ?><br />
                            <?php } /** End if - show meta */

                            if ( ( $show_comments ) && ( ! post_password_required() ) ) {
                                comments_popup_link( __( 'with No Comments', 'bns-ft' ), __( 'with 1 Comment', 'bns-ft' ), __( 'with % Comments', 'bns-ft' ), '', __( 'with Comments Closed', 'bns-ft' ) ); ?><br />
                            <?php } /** End if - show comments */

                            if ( $show_cats ) {
                                echo apply_filters( 'bnsfc_show_cats', sprintf( __( 'in %s', 'bns-fc' ), get_the_category_list( ', ' ) ) ); ?><br />
                            <?php } /** End if - show categories */

                            if ( $show_tags ) {
                                the_tags( __( 'as ', 'bns-ft' ), ', ', '' ); ?><br />
                            <?php } /** End if - show tags */ ?>

                        </div> <!-- .post-details -->

                        <?php if ( ! $only_titles ) { ?>

                            <div class="bnsft-content">

                                <?php if ( $show_full ) {

                                    /** Conditions: Theme supports post-thumbnails -and- there is a post-thumbnail -and- the option to show the post thumbnail is checked */
                                    if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail() && ( $use_thumbnails ) ) { ?>
                                        <a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-ft' ); ?> <?php the_title_attribute(); ?>"><?php the_post_thumbnail( array( $content_thumb, $content_thumb ) , array( 'class' => 'alignleft' ) ); ?></a>
                                    <?php } /** End if  */

                                    the_content(); ?>

                                    <div class="bnsft-clear"></div>

                                    <?php wp_link_pages( array( 'before' => '<p><strong>' . __( 'Pages: ', 'bns-ft') . '</strong>', 'after' => '</p>', 'next_or_number' => 'number' ) );

                                } elseif ( isset( $instance['excerpt_length']) && $instance['excerpt_length'] > 0 ) {

                                    if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail() && ( $use_thumbnails ) ) { ?>
                                        <a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-ft' ); ?> <?php the_title_attribute(); ?>"><?php the_post_thumbnail( array( $excerpt_thumb, $excerpt_thumb ) , array( 'class' => 'alignleft' ) ); ?></a>
                                    <?php } /** End if */

                                    echo $this->bnsft_custom_excerpt( get_the_content(), $instance['excerpt_length'] );

                                } elseif ( ! $instance['no_excerpt'] ) {

                                    if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail() && ( $use_thumbnails ) ) { ?>
                                        <a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-ft' ); ?> <?php the_title_attribute(); ?>"><?php the_post_thumbnail( array( $excerpt_thumb, $excerpt_thumb ) , array( 'class' => 'alignleft' ) ); ?></a>
                                    <?php } /** End if */

                                    the_excerpt();

                                } else {

                                    if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail() && ( $use_thumbnails ) ) { ?>
                                        <a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-ft' ); ?> <?php the_title_attribute(); ?>"><?php the_post_thumbnail( array( $content_thumb, $content_thumb ) , array( 'class' => 'alignleft' ) ); ?></a>
                                    <?php } /** End if */

                                    the_excerpt();

                                } /** End if - show full */ ?>

                            </div> <!-- .bnsft-content -->

                        <?php } /** End if - not only titles */ ?>

                    </div> <!-- .post #post-ID -->

                <?php $count++;

                } /** End if - count */

            endwhile; else :

                _e( 'Yes, we have no bananas, or posts, today.', 'bns-ft' );

            endif; /** End if - have posts */

        } /** End if - replace entire output when hook `bnsft_output` is used */

        /** @var $after_widget string - defined by theme */
        echo $after_widget;

        /** Make sure to clean up after ourselves */
        wp_reset_postdata();

    } /** End function - widget */


    /**
     * Update
     *
     * @param   array $new_instance
     * @param   array $old_instance
     *
     * @return  array
     */
    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;

        /** Strip tags (if needed) and update the widget settings */
        $instance['title']              = strip_tags( $new_instance['title'] );
        $instance['tag_choice']         = strip_tags( $new_instance['tag_choice'] );
        $instance['union']              = $new_instance['union'];
        $instance['use_current']        = $new_instance['use_current'];
        $instance['exclude_current']    = $new_instance['exclude_current'];
        $instance['show_count']         = $new_instance['show_count'];
        $instance['offset']             = $new_instance['offset'];
        $instance['sort_order']         = $new_instance['sort_order'];
        $instance['use_thumbnails']     = $new_instance['use_thumbnails'];
        $instance['content_thumb']      = $new_instance['content_thumb'];
        $instance['excerpt_thumb']      = $new_instance['excerpt_thumb'];
        $instance['show_meta']          = $new_instance['show_meta'];
        $instance['show_comments']      = $new_instance['show_comments'];
        $instance['show_cats']          = $new_instance['show_cats'];
        $instance['show_tags']          = $new_instance['show_tags'];
        $instance['show_tag_desc']      = $new_instance['show_tag_desc'];
        $instance['only_titles']        = $new_instance['only_titles'];
        $instance['no_titles']          = $new_instance['no_titles'];
        $instance['show_full']          = $new_instance['show_full'];
        $instance['excerpt_length']     = $new_instance['excerpt_length'];
        $instance['no_excerpt']         = $new_instance['no_excerpt'];
        /** added to be able to reset count to zero for every instance of the plugin */
        $instance['count']              = $new_instance['count'];

        return $instance;

    } /** End function - update */


    /**
     * Form
     *
     * @param   array $instance
     *
     * @return  string|void
     */
    function form( $instance ) {
        /** Set up default widget settings */
        $defaults = array(
            'title'             => __( 'Featured Tag', 'bns-ft' ),
            'tag_choice'        => '',
            'union'             => false,
            'use_current'       => false,
            'exclude_current'   => false,
            'count'             => '0',
            'show_count'        => '3',
            'offset'            => '0',
            'sort_order'        => 'desc',
            'use_thumbnails'    => true,
            'content_thumb'     => '100',
            'excerpt_thumb'     => '50',
            'show_meta'         => false,
            'show_comments'     => false,
            'show_cats'         => false,
            'show_tags'         => false,
            'show_tag_desc'     => false,
            'only_titles'       => false,
            'no_titles'         => false,
            'show_full'         => false,
            'excerpt_length'    => '',
            'no_excerpt'        => false,
        );
        $instance = wp_parse_args( ( array ) $instance, $defaults );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'bns-ft' ); ?></label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'tag_choice' ); ?>"><?php _e( 'Tag Names, separated by commas:', 'bns-ft' ); ?></label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'tag_choice' ); ?>" name="<?php echo $this->get_field_name( 'tag_choice' ); ?>" value="<?php echo $instance['tag_choice']; ?>" />
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['union'], true ); ?> id="<?php echo $this->get_field_id( 'union' ); ?>" name="<?php echo $this->get_field_name( 'union' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'union' ); ?>"><?php _e( '<em>(beta)</em> <strong>ONLY</strong> show posts that have <strong>ALL</strong> of the Tag IDs (You <strong>MUST</strong> only use <em>Tag IDs</em> in the Tag Names field above.)', 'bns-ft' ); ?></label>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_tag_desc'], true ); ?> id="<?php echo $this->get_field_id( 'show_tag_desc' ); ?>" name="<?php echo $this->get_field_name( 'show_tag_desc' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'show_tag_desc' ); ?>"><?php _e( 'Show first Tag choice description?', 'bns-ft' ); ?></label>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['use_current'], true ); ?> id="<?php echo $this->get_field_id( 'use_current' ); ?>" name="<?php echo $this->get_field_name( 'use_current' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'use_current' ); ?>"><?php _e( 'Use current tag(s) in single view?<br />Recommended if using "...ALL of the Tag IDs" option above.', 'bns-ft' ); ?></label>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['exclude_current'], true ); ?> id="<?php echo $this->get_field_id( 'exclude_current' ); ?>" name="<?php echo $this->get_field_name( 'exclude_current' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'exclude_current' ); ?>"><?php _e( 'Exclude current post in single view?', 'bns-ft' ); ?></label>
        </p>

        <table class="bnsft-counts">
            <tr>
                <td>
                    <p>
                        <label for="<?php echo $this->get_field_id( 'show_count' ); ?>"><?php _e( 'Posts to Display:', 'bns-ft' ); ?></label>
                        <input type="text" id="<?php echo $this->get_field_id( 'show_count' ); ?>" name="<?php echo $this->get_field_name( 'show_count' ); ?>" value="<?php echo $instance['show_count']; ?>" style="width:85%;" />
                    </p>
                </td>
                <td>
                    <p>
                        <label for="<?php echo $this->get_field_id( 'offset' ); ?>"><?php _e( 'Posts Offset:', 'bns-ft' ); ?></label>
                        <input type="text" id="<?php echo $this->get_field_id( 'offset' ); ?>" name="<?php echo $this->get_field_name( 'offset' ); ?>" value="<?php echo $instance['offset']; ?>" style="width:85%;" />
                    </p>
                </td>
            </tr>

            <tr>
                <td>
                    <p>
                        <label for="<?php echo $this->get_field_id( 'sort_order' ); ?>"><?php _e( 'Sort Order:', 'bns-ft' ); ?></label>
                        <select id="<?php echo $this->get_field_id( 'sort_order' ); ?>" name="<?php echo $this->get_field_name( 'sort_order' ); ?>" style="width:85%;" >
                            <option <?php selected( 'asc', $instance['sort_order'], true ); ?>>asc</option>
                            <option <?php selected( 'desc', $instance['sort_order'], true ); ?>>desc</option>
                            <option <?php selected( 'rand', $instance['sort_order'], true ); ?>>rand</option>
                        </select>
                    </p>
                </td>
            </tr>

        </table>

        <hr />
        <!-- The following option choices may affect the widget option panel layout -->
        <p><?php _e( 'NB: Some options may not be available depending on which ones are selected.', 'bns-fc'); ?></p>
        <p class="bnsfc-display-all-posts-check">
            <input class="checkbox" type="checkbox" <?php checked( ( bool ) $instance['only_titles'], true ); ?> id="<?php echo $this->get_field_id( 'only_titles' ); ?>" name="<?php echo $this->get_field_name( 'only_titles' ); ?>" />
            <?php $all_options_toggle = ( checked( (bool) $instance['only_titles'], true, false ) ) ? 'closed' : 'open'; ?>
            <label for="<?php echo $this->get_field_id( 'only_titles' ); ?>"><?php _e( 'ONLY display post Titles?', 'bns-ft' ); ?></label>
        </p>

        <p class="bnsft-all-options-<?php echo $all_options_toggle; ?> bnsft-no-titles">
            <input class="checkbox" type="checkbox" <?php checked( ( bool ) $instance['no_titles'], true ); ?> id="<?php echo $this->get_field_id( 'no_titles' ); ?>" name="<?php echo $this->get_field_name( 'no_titles' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'no_titles' ); ?>"><?php _e( 'Do NOT display Post Titles?', 'bns-ft' ); ?></label>
        </p>

        <!-- If the theme supports post-thumbnails carry on; otherwise hide the thumbnails section -->
        <?php if ( ! current_theme_supports( 'post-thumbnails' ) ) echo '<div class="bnsfc-thumbnails-closed">'; ?>
            <p class="bnsft-all-options-<?php echo $all_options_toggle; ?> bnsft-display-thumbnail-sizes"><!-- Hide all options below if ONLY post titles are to be displayed -->
                <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['use_thumbnails'], true ); ?> id="<?php echo $this->get_field_id( 'use_thumbnails' ); ?>" name="<?php echo $this->get_field_name( 'use_thumbnails' ); ?>" />
                <?php $thumbnails_toggle = ( checked( (bool) $instance['use_thumbnails'], true, false ) ) ? 'open' : 'closed'; ?>
                <label for="<?php echo $this->get_field_id( 'use_thumbnails' ); ?>"><?php _e( 'Use Featured Image Thumbnails?', 'bns-ft' ); ?></label>
            </p>

            <table class="bnsft-thumbnails-<?php echo $thumbnails_toggle; ?> bnsft-all-options-<?php echo $all_options_toggle; ?>"><!-- Hide table if featured image / thumbnails are not used -->
                <tr>
                    <td>
                        <p>
                            <label for="<?php echo $this->get_field_id( 'content_thumb' ); ?>"><?php _e( 'Content Thumbnail Size (in px):', 'bns-ft' ); ?></label>
                            <input type="text" id="<?php echo $this->get_field_id( 'content_thumb' ); ?>" name="<?php echo $this->get_field_name( 'content_thumb' ); ?>" value="<?php echo $instance['content_thumb']; ?>" style="width:85%;" />
                        </p>
                    </td>
                    <td>
                        <p>
                            <label for="<?php echo $this->get_field_id( 'excerpt_thumb' ); ?>"><?php _e( 'Excerpt Thumbnail Size (in px):', 'bns-ft' ); ?></label>
                            <input type="text" id="<?php echo $this->get_field_id( 'excerpt_thumb' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_thumb' ); ?>" value="<?php echo $instance['excerpt_thumb']; ?>" style="width:85%;" />
                        </p>
                    </td>
                </tr>
            </table>
        <?php if ( ! current_theme_supports( 'post-thumbnails' ) ) echo '</div>'; ?>
        <!-- Carry on from here if there is no thumbnail support -->

        <p class="bnsft-all-options-<?php echo $all_options_toggle; ?>">
            <input class="checkbox" type="checkbox" <?php checked( ( bool ) $instance['show_meta'], true ); ?> id="<?php echo $this->get_field_id( 'show_meta' ); ?>" name="<?php echo $this->get_field_name( 'show_meta' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'show_meta' ); ?>"><?php _e( 'Display Author Meta Details?', 'bns-ft' ); ?></label>
        </p>


        <p class="bnsft-all-options-<?php echo $all_options_toggle; ?>">
            <input class="checkbox" type="checkbox" <?php checked( ( bool ) $instance['show_comments'], true ); ?> id="<?php echo $this->get_field_id( 'show_comments' ); ?>" name="<?php echo $this->get_field_name( 'show_comments' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'show_comments' ); ?>"><?php _e( 'Display Comment Totals?', 'bns-ft' ); ?></label>
        </p>

        <p class="bnsft-all-options-<?php echo $all_options_toggle; ?>">
            <input class="checkbox" type="checkbox" <?php checked( ( bool ) $instance['show_cats'], true ); ?> id="<?php echo $this->get_field_id( 'show_cats' ); ?>" name="<?php echo $this->get_field_name( 'show_cats' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'show_cats' ); ?>"><?php _e( 'Display the Post Categories?', 'bns-ft' ); ?></label>
        </p>

        <p class="bnsft-all-options-<?php echo $all_options_toggle; ?>">
            <input class="checkbox" type="checkbox" <?php checked( ( bool ) $instance['show_tags'], true ); ?> id="<?php echo $this->get_field_id( 'show_tags' ); ?>" name="<?php echo $this->get_field_name( 'show_tags' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'show_tags' ); ?>"><?php _e( 'Display the Post Tags?', 'bns-ft' ); ?></label>
        </p>

        <p class="bnsft-all-options-<?php echo $all_options_toggle; ?> bnsft-excerpt-option-open-check">
            <input class="checkbox" type="checkbox" <?php checked( ( bool ) $instance['show_full'], true ); ?> id="<?php echo $this->get_field_id( 'show_full' ); ?>" name="<?php echo $this->get_field_name( 'show_full' ); ?>" />
            <?php $show_full_toggle = ( checked( (bool) $instance['show_full'], true, false ) ) ? 'closed' : 'open'; ?>
            <label for="<?php echo $this->get_field_id( 'show_full' ); ?>"><?php _e( 'Display entire post?', 'bns-ft' ); ?></label>
        </p>

        <hr />
        <!-- Hide excerpt explanation and word count option if entire post is displayed -->
        <p class="bnsft-all-options-<?php echo $all_options_toggle; ?> bnsft-excerpt-option-<?php echo $show_full_toggle; ?>">
            <?php _e( 'The post excerpt is shown by default, if it exists; otherwise the first 55 words of the post are shown as the excerpt ...', 'bns-ft'); ?>
        </p>

        <p class="bnsft-all-options-<?php echo $all_options_toggle; ?> bnsft-excerpt-option-<?php echo $show_full_toggle; ?>">
            <label for="<?php echo $this->get_field_id( 'excerpt_length' ); ?>"><?php _e( '... or set the amount of words you want to show:', 'bns-ft' ); ?></label>
            <input type="text" id="<?php echo $this->get_field_id( 'excerpt_length' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_length' ); ?>" value="<?php echo $instance['excerpt_length']; ?>" style="width:95%;" />
        </p>

        <p class="bnsft-all-options-<?php echo $all_options_toggle; ?> bnsft-excerpt-option-<?php echo $show_full_toggle; ?>">
            <label for="<?php echo $this->get_field_id( 'no_excerpt' ); ?>"><?php _e( '... or have no excerpt at all!', 'bns-ft' ); ?></label>
            <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['no_excerpt'], true ); ?> id="<?php echo $this->get_field_id( 'no_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'no_excerpt' ); ?>" />
        </p>

    <?php
    } /** End function - form */


    /**
     * BNSFT Shortcode Start
     * - May the Gods of programming protect us all!
     *
     * @package BNS_Featured_Tag
     *
     * @param   $atts
     *
     * @uses    shortcode_atts
     * @uses    the_widget
     *
     * @return  string ob_get_contents
     *
     * @version 2.1
     * @date    August 4, 2012
     * Add 'no_titles' option
     *
     * @version 2.2
     * @date    December 1, 2012
     * Add use current option
     * Add offset option
     * Add sort order option
     * Optimize output buffer closure in shortcode function
     */
    function bnsft_shortcode( $atts ) {
        /** Get ready to capture the elusive widget output */
        ob_start();
        the_widget(
            'BNS_Featured_Tag_Widget',
            $instance = shortcode_atts( array(
                'title'             => __( 'Featured Tag', 'bns-ft' ),
                'tag_choice'        => '',
                'union'             => false,
                'use_current'       => false,
                'exclude_current'   => false,
                'count'             => '0',
                'show_count'        => '3',
                'offset'            => '',
                'sort_order'        => 'DESC',
                'use_thumbnails'    => true,
                'content_thumb'     => '100',
                'excerpt_thumb'     => '50',
                'show_tag_desc'     => false,
                'show_meta'         => false,
                'show_comments'     => false,
                'show_cats'         => false,
                'show_tags'         => false,
                'only_titles'       => false,
                'no_titles'         => false,
                'show_full'         => false,
                'excerpt_length'    => '',
                'no_excerpt'        => false,
            ), $atts),
            $args = array(
                /** clear variables defined by theme for widgets */
                $before_widget  = '',
                $after_widget   = '',
                $before_title   = '',
                $after_title    = '',
            ) );
        /** Get the_widget output and put into its own container */
        $bnsft_content = ob_get_clean();

        return $bnsft_content;

    } /** End function - shortcode */


    /**
     * Load Widget
     * Register widget
     */
    function load_bnsft_widget() {
        register_widget( 'BNS_Featured_Tag_Widget' );
    } /** End function - register widget */


} /** End class BNS_Featured_Tag_Widget */


/** @var $bnsft - instantiate the class */
$bnsft = new BNS_Featured_Tag_Widget();


/**
 * BNSFT Plugin Meta
 * Adds additional links to plugin meta links
 *
 * @package BNS_Featured_Tag
 * @since   2.4.1
 *
 * @uses    __
 * @uses    plugin_basename
 *
 * @param   $links
 * @param   $file
 *
 * @return  array $links
 */
function bnsft_plugin_meta( $links, $file ) {

    $plugin_file = plugin_basename( __FILE__ );

    if ( $file == $plugin_file ) {

        $links[] = '<a href="https://github.com/Cais/BNS-Featured-Tag">' . __( 'Fork on Github', 'bns-ft' ) . '</a>';

    } /** End if - file is the same as plugin */

    return $links;

} /** End function - plugin meta */

/** Add Plugin Row Meta details */
add_filter( 'plugin_row_meta', 'bnsft_plugin_meta', 10, 2 );