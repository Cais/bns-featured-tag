<?php
/*
Plugin Name: BNS Featured Tag
Plugin URI: http://buynowshop.com/plugins/bns-featured-tag/
Description: Plugin with multi-widget functionality that displays most recent posts from specific tag or tags (set with user options). Also includes user options to display: Tag Description; Author and meta details; comment totals; post categories; post tags; and either full post or excerpt (or any combination).
Version: 1.9.2
Author: Edward Caissie
Author URI: http://edwardcaissie.com/
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
 * @version     1.9.2
 * @author      Edward Caissie <edward.caissie@gmail.com>
 * @copyright   Copyright (c) 2009-2012, Edward Caissie
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
 * Last revised May 5, 2012
 * @version 1.9.2
 * Fixed featured image post thumbnail not showing
 *
 * @todo Updates similar to BNS Featured Category - version 2.0 time-line
 */

/** Check if current WordPress version meets the plugin requirements */
global $wp_version;
$exit_message = 'BNS Featured Tag requires WordPress version 2.9 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please Update!</a>';
if ( version_compare( $wp_version, "2.9", "<") )
    exit ( $exit_message );

/**
 * BNS Featured Tag TextDomain
 * Make plugin text available for translation (i18n)
 *
 * @package BNS Featured Tag
 * @since   1.9
 *
 * Note: Translation files are expected to be found in the plugin root folder / directory.
 * `bns-ft` is being used in place of `bns-featured-tag`
 *
 * Last revised October 31, 2011
 */
load_plugin_textdomain( 'bns-ft' );
// End: BNS Featured Tag TextDomain

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
 * @return  string
 */
// Begin the mess of Excerpt Length fiascoes
function bnsft_custom_excerpt( $text, $length = 55 ) {
        $text = strip_tags( $text );
        $words = explode( ' ', $text, $length + 1 );

        /** Create link to full post for end of custom length excerpt output */
        $bnsft_link = ' <strong><a class="bnsft-link" href="' . get_permalink() . '" title="' . the_title_attribute( array( 'before' => __( 'Permalink to: ', 'bns-ft' ), 'after' => '', 'echo' => false ) ) . '">&infin;</a></strong>';

        if ( ( ! $length ) || ( count( $words ) < $length ) ) {
            $text .= $bnsft_link;
            return $text;
        } else {
            array_pop( $words );
            array_push( $words, '...' );
            $text = implode( ' ', $words );
        }
        $text .= $bnsft_link;
        return $text;
}
// End BNS Featured Tag Custom Excerpt

/**
 * Enqueue Plugin Scripts and Styles
 *
 * @package BNS_Featured_Tag
 * @since   1.9
 *
 * Last revised December 14, 2011
 * @version 1.9.1
 * Fixed 404 error when 'bnsft-custom-style.css' is not available
 */
function BNSFT_Scripts_and_Styles() {
        /** Enqueue Scripts */
        /** Enqueue Styles */
        wp_enqueue_style( 'BNSFT-Style', plugin_dir_url( __FILE__ ) . 'bnsft-style.css', array(), '1.9.1', 'screen' );
        if ( is_readable( plugin_dir_path( __FILE__ ) . 'bnsft-custom-style.css' ) ) {
            wp_enqueue_style( 'BNSFT-Custom-Style', plugin_dir_url( __FILE__ ) . 'bnsft-custom-style.css', array(), '1.9.1', 'screen' );
        }
}
add_action( 'wp_enqueue_scripts', 'BNSFT_Scripts_and_Styles' );

/** Function that registers our widget. */
function load_bnsft_widget() {
        register_widget( 'BNS_Featured_Tag_Widget' );
}

/** Add load_bnsft_widget function to the widgets_init hook */
add_action( 'widgets_init', 'load_bnsft_widget' );

class BNS_Featured_Tag_Widget extends WP_Widget {
        function BNS_Featured_Tag_Widget() {
                /** Widget settings */
                $widget_ops = array( 'classname' => 'bns-featured-tag', 'description' => __( 'Displays most recent posts from a specific featured tag or tags.', 'bns-ft' ) );

                /** Widget control settings */
                $control_ops = array( 'width' => 200, 'id_base' => 'bns-featured-tag' );

                /** Create the widget */
                $this->WP_Widget( 'bns-featured-tag', 'BNS Featured Tag', $widget_ops, $control_ops );
        }

        function widget( $args, $instance ) {
                extract( $args );

                /** User-selected settings */
                $title          = apply_filters( 'widget_title', $instance['title'] );
                $tag_choice     = $instance['tag_choice'];
                $show_count     = $instance['show_count'];
                $use_thumbnails = $instance['use_thumbnails'];
                $content_thumb  = $instance['content_thumb'];
                $excerpt_thumb  = $instance['excerpt_thumb'];
                $show_tag_desc  = $instance['show_tag_desc'];
                $show_meta      = $instance['show_meta'];
                $show_comments  = $instance['show_comments'];
                $show_cats      = $instance['show_cats'];
                $show_tags      = $instance['show_tags'];
                $only_titles    = $instance['only_titles'];
                $show_full      = $instance['show_full'];
                $excerpt_length = $instance['excerpt_length'];
                /** Plugin requires counter variable to be part of its arguments?! */
                $count          = $instance['count'];

                /** @var    $before_widget  string - defined by theme */
                echo $before_widget;

                /** Widget $title $before_widget and $after_widget defined by theme */
                if ( $title )
                    /** @var    $before_title   string - defined by theme */
                    /** @var    $after_title    string - defined by theme */
                    echo $before_title . $title . $after_title;

                /** Display posts from widget settings */
                /** Replace spaces with hyphens to create tag slugs from the name */
                $tag_choice = str_replace( ' ', '-', $tag_choice );

                /** Remove leading hyphens from tag slugs if multiple tag names are entered with leading spaces */
                $tag_choice = str_replace( ',-', ', ', $tag_choice );

                query_posts( "tag=$tag_choice&posts_per_page=$show_count" );
                if ( $show_tag_desc ) {
                    echo '<div class="bnsft-tag-desc">' . tag_description() . '</div>';
                }
                if ( have_posts()) : while ( have_posts() ) : the_post();
                        // static $count = 0; /* see above */
                        if ( $count == $show_count ) {
                            break;
                        } else { ?>
                            <div <?php post_class(); ?>>
                                <strong><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'bns-ft' ); ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a></strong>
                                <div class="post-details">
                                    <?php if ( $show_meta ) {
                                        printf( __( 'by %1$s on %2$s', 'bns-ft' ), get_the_author(), get_the_time( get_option( 'date_format' ) ) ); ?><br />
                                    <?php }
                                    if ( ( $show_comments ) && ( ! post_password_required() ) ) {
                                        comments_popup_link( __( 'with No Comments', 'bns-ft' ), __( 'with 1 Comment', 'bns-ft' ), __( 'with % Comments', 'bns-ft' ), '', __( 'with Comments Closed', 'bns-ft' ) ); ?><br />
                                    <?php }
                                    if ( $show_cats ) {
                                        printf( __( 'in %s', 'bns-ft' ), get_the_category_list( ', ' ) ); ?><br />
                                    <?php }
                                    if ( $show_tags ) {
                                        the_tags( __( 'as ', 'bns-ft' ), ', ', '' ); ?><br />
                                    <?php } ?>
                                </div> <!-- .post-details -->
                                <?php if ( ! $only_titles ) { ?>
                                    <div class="bnsft-content">
                                        <?php if ( $show_full ) {
                                        /** Conditions: Theme supports post-thumbnails -and- there is a post-thumbnail -and- the option to show the post thumbnail is checked */
                                        if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail() && ( $use_thumbnails ) )
                                            the_post_thumbnail( array( $content_thumb, $content_thumb ) , array( 'class' => 'alignleft' ) );
                                        the_content(); ?>
                                        <div class="bnsft-clear"></div>
                                        <?php wp_link_pages( array( 'before' => '<p><strong>' . __( 'Pages: ', 'bns-ft') . '</strong>', 'after' => '</p>', 'next_or_number' => 'number' ) );
                                    } elseif ( isset( $instance['excerpt_length']) && $instance['excerpt_length'] > 0 ) {
                                        if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail() && ( $use_thumbnails ) )
                                            the_post_thumbnail( array( $excerpt_thumb, $excerpt_thumb ) , array( 'class' => 'alignleft' ) );
                                        echo bnsft_custom_excerpt( get_the_content(), $instance['excerpt_length'] );
                                    } else {
                                        if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail() && ( $use_thumbnails ) )
                                            the_post_thumbnail( array( $excerpt_thumb, $excerpt_thumb ) , array( 'class' => 'alignleft' ) );
                                        the_excerpt();
                                    } ?>
                                    </div> <!-- .bnsft-content -->
                                <?php } ?>
                            </div> <!-- .post #post-ID -->
                        <?php $count++;
                        }
                     endwhile;
                else :
                    _e( 'Yes, we have no bananas, or posts, today.', 'bns-ft' );
                endif;

                /** @var    $after_widget   string - defined by theme */
                echo $after_widget;
                wp_reset_query();
        }

        function update( $new_instance, $old_instance ) {
                $instance = $old_instance;

                /** Strip tags (if needed) and update the widget settings */
                $instance['title']          = strip_tags( $new_instance['title'] );
                $instance['tag_choice']	  	= strip_tags( $new_instance['tag_choice'] );
                $instance['show_count']     = $new_instance['show_count'];
                $instance['use_thumbnails']	= $new_instance['use_thumbnails'];
                $instance['content_thumb']	= $new_instance['content_thumb'];
                $instance['excerpt_thumb']	= $new_instance['excerpt_thumb'];
                $instance['show_tag_desc']	= $new_instance['show_tag_desc'];
                $instance['show_meta']      = $new_instance['show_meta'];
                $instance['show_comments']	= $new_instance['show_comments'];
                $instance['show_cats']      = $new_instance['show_cats'];
                $instance['show_tags']      = $new_instance['show_tags'];
                $instance['only_titles']  	= $new_instance['only_titles'];
                $instance['show_full']      = $new_instance['show_full'];
                $instance['excerpt_length']	= $new_instance['excerpt_length'];
                /** added to be able to reset count to zero for every instance of the plugin */
                $instance['count']          = $new_instance['count'];

                return $instance;
        }

        function form( $instance ) {
                /** Set up default widget settings */
                $defaults = array(
                    'title'             => __( 'Featured Tag', 'bns-ft' ),
                    'tag_choice'        => '',
                    'count'             => '0',
                    'show_count'        => '3',
                    'use_thumbnails'    => true,
                    'content_thumb'     => '100',
                    'excerpt_thumb'     => '50',
                    'show_tag_desc'     => false,
                    'show_meta'         => false,
                    'show_comments'     => false,
                    'show_cats'         => false,
                    'show_tags'         => false,
                    'only_titles'       => false,
                    'show_full'         => false,
                    'excerpt_length'    => ''
                );

                $instance = wp_parse_args( ( array ) $instance, $defaults );
                ?>

                <p>
                    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'bns-ft' ); ?></label>
                    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
                </p>

                <p>
                    <label for="<?php echo $this->get_field_id( 'tag_choice' ); ?>"><?php _e( 'Tag Names, separated by commas:', 'bns-ft' ); ?></label>
                    <input class="widefat" id="<?php echo $this->get_field_id( 'tag_choice' ); ?>" name="<?php echo $this->get_field_name( 'tag_choice' ); ?>" value="<?php echo $instance['tag_choice']; ?>" />
                </p>

                <p>
                    <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['show_tag_desc'], true ); ?> id="<?php echo $this->get_field_id( 'show_tag_desc' ); ?>" name="<?php echo $this->get_field_name( 'show_tag_desc' ); ?>" />
                    <label for="<?php echo $this->get_field_id( 'show_tag_desc' ); ?>"><?php _e( 'Show first Tag choice description?', 'bns-ft' ); ?></label>
                </p>

                <p>
                    <input class="checkbox" type="checkbox" <?php checked( (bool) $instance['use_thumbnails'], true ); ?> id="<?php echo $this->get_field_id( 'use_thumbnails' ); ?>" name="<?php echo $this->get_field_name( 'use_thumbnails' ); ?>" />
                    <label for="<?php echo $this->get_field_id( 'use_thumbnails' ); ?>"><?php _e( 'Use Featured Image / Post Thumbnails?', 'bns-ft' ); ?></label>
                </p>

                <p>
                    <label for="<?php echo $this->get_field_id( 'content_thumb' ); ?>"><?php _e( 'Content Thumbnail Size (in px):', 'bns-ft' ); ?></label>
                    <input class="widefat" id="<?php echo $this->get_field_id( 'content_thumb' ); ?>" name="<?php echo $this->get_field_name( 'content_thumb' ); ?>" value="<?php echo $instance['content_thumb']; ?>" />

                </p>

                <p>
                    <label for="<?php echo $this->get_field_id( 'excerpt_thumb' ); ?>"><?php _e( 'Excerpt Thumbnail Size (in px):', 'bns-ft' ); ?></label>
                    <input class="widefat" id="<?php echo $this->get_field_id( 'excerpt_thumb' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_thumb' ); ?>" value="<?php echo $instance['excerpt_thumb']; ?>" />
                </p>

                <p>
                    <label for="<?php echo $this->get_field_id( 'show_count' ); ?>"><?php _e( 'Total Posts to Display:', 'bns-ft' ); ?></label>
                    <input class="widefat" id="<?php echo $this->get_field_id( 'show_count' ); ?>" name="<?php echo $this->get_field_name( 'show_count' ); ?>" value="<?php echo $instance['show_count']; ?>" />
                </p>

                <table width="100%">
                    <tr>
                        <td>
                            <p>
                                <input class="checkbox" type="checkbox" <?php checked( ( bool ) $instance['show_meta'], true ); ?> id="<?php echo $this->get_field_id( 'show_meta' ); ?>" name="<?php echo $this->get_field_name( 'show_meta' ); ?>" />
                                <label for="<?php echo $this->get_field_id( 'show_meta' ); ?>"><?php _e( 'Display Author Meta Details?', 'bns-ft' ); ?></label>
                            </p>
                        </td>
                        <td>
                            <p>
                                <input class="checkbox" type="checkbox" <?php checked( ( bool ) $instance['show_comments'], true ); ?> id="<?php echo $this->get_field_id( 'show_comments' ); ?>" name="<?php echo $this->get_field_name( 'show_comments' ); ?>" />
                                <label for="<?php echo $this->get_field_id( 'show_comments' ); ?>"><?php _e( 'Display Comment Totals?', 'bns-ft' ); ?></label>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>
                                <input class="checkbox" type="checkbox" <?php checked( ( bool ) $instance['show_cats'], true ); ?> id="<?php echo $this->get_field_id( 'show_cats' ); ?>" name="<?php echo $this->get_field_name( 'show_cats' ); ?>" />
                                <label for="<?php echo $this->get_field_id( 'show_cats' ); ?>"><?php _e( 'Display the Post Categories?', 'bns-ft' ); ?></label>
                            </p>
                        </td>
                        <td>
                            <p>
                                <input class="checkbox" type="checkbox" <?php checked( ( bool ) $instance['show_tags'], true ); ?> id="<?php echo $this->get_field_id( 'show_tags' ); ?>" name="<?php echo $this->get_field_name( 'show_tags' ); ?>" />
                                <label for="<?php echo $this->get_field_id( 'show_tags' ); ?>"><?php _e( 'Display the Post Tags?', 'bns-ft' ); ?></label>
                            </p>
                        </td>
                    </tr>
                </table>

                <hr /> <!-- separates meta details display from content/excerpt display options -->
                <p><?php _e( 'The excerpt is shown by default; or, the first 55 words if it does not exist.', 'bns-ft' ); ?></p>

                <p>
                    <label for="<?php echo $this->get_field_id( 'excerpt_length' ); ?>"><?php _e( 'Set your word count if you want more or less than 55.', 'bns-ft' ); ?></label>
                    <input class="widefat" id="<?php echo $this->get_field_id( 'excerpt_length' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_length' ); ?>" value="<?php echo $instance['excerpt_length']; ?>" />
                </p>

                <p>
                    <input class="checkbox" type="checkbox" <?php checked( ( bool ) $instance['only_titles'], true ); ?> id="<?php echo $this->get_field_id( 'only_titles' ); ?>" name="<?php echo $this->get_field_name( 'only_titles' ); ?>" />
                    <label for="<?php echo $this->get_field_id( 'only_titles' ); ?>"><?php _e( 'Display only post Titles?', 'bns-ft' ); ?></label>
                </p>

                <p>
                    <input class="checkbox" type="checkbox" <?php checked( ( bool ) $instance['show_full'], true ); ?> id="<?php echo $this->get_field_id( 'show_full' ); ?>" name="<?php echo $this->get_field_name( 'show_full' ); ?>" />
                    <label for="<?php echo $this->get_field_id( 'show_full' ); ?>"><?php _e( 'Display entire post?', 'bns-ft' ); ?></label>
                </p>
        <?php }
}
// End class BNS_Featured_Tag_Widget

/**
 * BNSFT Shortcode Start
 * - May the Gods of programming protect us all!
 *
 * @param $atts
 *
 * @return string ob_get_contents
 */
function bnsft_shortcode( $atts ) {
        /** Get ready to capture the elusive widget output */
        ob_start();
        the_widget(
                'BNS_Featured_Tag_Widget',
                $instance = shortcode_atts( array(
                                                'title'             => __( 'Featured Tag', 'bns-ft' ),
                                                'tag_choice'        => '',
                                                'count'             => '0',
                                                'show_count'        => '3',
                                                'use_thumbnails'    => true,
                                                'content_thumb'  => '100',
                                                'excerpt_thumb'     => '50',
                                                'show_tag_desc'     => false,
                                                'show_meta'         => false,
                                                'show_comments'     => false,
                                                'show_cats'         => false,
                                                'show_tags'         => false,
                                                'only_titles'       => false,
                                                'show_full'      => false,
                                                'excerpt_length'    => ''
                                            ), $atts),
                $args = array(
                            /** clear variables defined by theme for widgets */
                            $before_widget  = '',
                            $after_widget   = '',
                            $before_title   = '',
                            $after_title    = '',
                ) );
        /** Get the_widget output and put into its own container */
        $bnsft_content = ob_get_contents();
        ob_end_clean();
        // All your snipes belong to us!

        return $bnsft_content;
}
add_shortcode( 'bnsft', 'bnsft_shortcode' );
// BNSFT Shortcode End - Say your prayers ...
?>