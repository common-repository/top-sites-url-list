<?php


# Creating the widget
class CategoryArchiveLabelListWidget extends WP_Widget {
    function __construct() {
        parent::__construct(
            # Base ID of your widget
            'tsul',

            # Widget name will appear in UI
            __('TOP Sites URL list', 'top-sites-url-list'),

            # Widget description
            array( 'description' => __( 'Show your TOP visited pages in your Sidebar.', 'top-sites-url-list' ), )
        );

        # Register scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'tsul_scripts_and_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'tsul_admin_scripts_and_styles' ) );
    }

    function tsul_scripts_and_styles() {
        wp_enqueue_style( 'tsul-styles' );

        # Widget custom css generation
        $settings_array = get_option('widget_tsul');
        if ( is_array($settings_array) ) {

            # Fill array with css classes and styles
            $styling_option_array = array(
                '.widget-title' => array(
                    'title_size' => 'font-size',
                    'title_color' => 'color'
                ),
                '.tsul a' => array(
                    'links_size' => 'font-size',
                    'links_color' => 'color'
                ),
                '.tsul .badge' => array(
                    'label_size' => 'font-size',
                    'label_color' => 'color',
                    'label_bg_color' => 'background-color'
                )
            );

            $custom_css = '';
            foreach ($settings_array as $widget_id => $widget_settings) {
                if ( $widget_settings['tsul_styling'] == 'true' ) {
                    foreach ($styling_option_array as $styling_option_class => $styling_option_items) {
                        $class_styles = '';
                        foreach ($styling_option_items as $styling_option_item => $styling_option_style) {
                            if ( $widget_settings['tsul_styling_options'][$styling_option_item] != '' ) {
                                $class_styles .= $styling_option_style . ': ' . $widget_settings['tsul_styling_options'][$styling_option_item] . ';';
                            }
                        }

                        if ( $class_styles != '' ) {
                            $custom_css .= '#tsul-' .  $widget_id . ' ' . $styling_option_class . '{' . $class_styles . '}';
                        }

                        if ( $widget_settings['tsul_styling_options']['custom_css'] != '' ) {
                            $custom_css .= $widget_settings['tsul_styling_options']['custom_css'];
                        }
                    }
                }
            }

            if ( $custom_css != '' ) {
                wp_add_inline_style( 'tsul-styles', $custom_css );
            }
        }
    }


    function tsul_admin_scripts_and_styles() {
        # Color picker
        wp_enqueue_style( 'wp-color-picker' );

        # Admin scripts and styles
        wp_enqueue_style( 'tsul-admin-styles');
        wp_enqueue_script( 'tsul-admin-scripts');
    }


    # Creating widget front-end
    public function widget( $args, $instance ) {
        # Before and after widget arguments are defined by themes
        if ( isset($args['before_widget']) ) {
            echo $args['before_widget'];
        }

        # Apply title filters
        $title = apply_filters( 'widget_title', $instance['tsul_title'] );
        if ( !empty( $title ) ) {
            if ( isset($args['before_title']) ) {
                echo $args['before_title'];
            }

            echo $title;

            if ( isset($args['after_title']) ) {
                echo $args['after_title'];
            }
        }

        # Get TSUL data
        $tsul_stats_serialize = get_option('tsul_stats');
        if ( isset($tsul_stats_serialize) && $tsul_stats_serialize != '' && unserialize($tsul_stats_serialize) ) {
            $tsul_stats = unserialize($tsul_stats_serialize);

            if ( is_array($tsul_stats) ) {
                $show_number = false;
                $post_views_number = '';

                if ( isset($instance['tsul_visits_number']) && $instance['tsul_visits_number'] == 'true' ) {
                    $show_number = true;
                }

                $list = '';

                # Fill list with TSUL data
                if ( isset($tsul_stats[$args['widget_id']]) && is_array($tsul_stats[$args['widget_id']]) ) {
                    foreach ($tsul_stats[$args['widget_id']] as $tsul_item) {

                        if ( $show_number ) {
                            $post_views_number = '<span class="tsul-post-views ' . ( $instance['tsul_visits_number_label'] == 'true' ? 'badge' . ( $instance['tsul_visits_number_front'] == 'false' ? ' badge-right' : '' ) : '' ) . '">';
                            if ( $instance['tsul_visits_number_txt_before'] != '' ) {
                                $post_views_number .= $instance['tsul_visits_number_txt_before'] . '';
                            }

                            $post_views_number .= $tsul_item['post_views'];

                            if ( $instance['tsul_visits_number_txt_after'] != '' ) {
                                $post_views_number .= $instance['tsul_visits_number_txt_after'];
                            }

                            $post_views_number .= '</span>';
                        }

                        $list .= '<li>';
                            $list .= '<a href="' . $tsul_item['post_permalink'] . '">';
                                if ( $show_number && ($instance['tsul_visits_number_front'] == 'true' || $instance['tsul_visits_number_label'] == 'true') ) {
                                    $list .= $post_views_number;
                                }
                                $list .= '<span class="tsul-post-title ' . ( $instance['tsul_visits_number_label'] == 'true' ? 'tsul-post-title-badge' : '' ) . '">' . $tsul_item['post_title'] . '</span>';
                                if ( $show_number && $instance['tsul_visits_number_front'] == 'false' && $instance['tsul_visits_number_label'] == 'false') {
                                    $list .= $post_views_number;
                                }
                            $list .= '</a>';
                        $list .= '</li>';
                    }
                } ?>

                <ul class="tsul">
                    <?php echo $list ?>
                </ul>
            <?php }
        }

        if ( isset($args['after_widget']) ) {
            echo $args['after_widget'];
        }
    }



    # Widget Backend
    public function form( $instance ) {

        if ( get_option('tsul_google_uid') == "" ) {
            echo '<br><strong>You have to <a href="' . admin_url( 'options-general.php?page=tsul_settings_page' ) . '">set up your settings</a> at first.</strong><br><br><br><br>';
        } else {

            $data = array();

            # Fill array with widget options
            foreach ($instance as $key => $value) {
                if ( isset( $value ) ) {
                    $data[$key] = $value;
                }
            }

            $post_types = tsul_get_post_types();
            if ( isset($data['tsul_post_types']) && unserialize($data['tsul_post_types']) ) {
                $used_post_types = unserialize($data['tsul_post_types']);
            } else {
                $used_post_types = $post_types;
            }

            # If own styles are selected, fill array with these options
            if ( isset($data['tsul_styling_options']) ) {
                foreach ($data['tsul_styling_options'] as $styling_key => $styling_value) {
                    $data['tsul_styling_'.$styling_key] = $styling_value;
                }
            }

            # Widget admin form
            ?>
            <div class="tsul-widget-options"><br>
                <div class="form-line">
                    <div class="form-label">
                        <label for="<?php echo $this->get_field_id( 'tsul_title' ); ?>"><?php _e( 'Title:', 'top-sites-url-list' ); ?></label>
                    </div>
                    <div class="form-field">
                        <input class="widefat" id="<?php echo $this->get_field_id( 'tsul_title' ); ?>" name="<?php echo $this->get_field_name( 'tsul_title' ); ?>" type="text" value="<?php echo (isset($data['tsul_title']) ? esc_attr( $data['tsul_title']) : '' ); ?>" />
                    </div>
                </div>
                <div class="form-line">
                    <div class="form-label">
                        <label for="<?php echo $this->get_field_id( 'tsul_post_types' ); ?>"><?php _e( 'Post types:', 'top-sites-url-list' ); ?></label>
                    </div>
                    <div class="form-field form-labels">
                        <?php foreach ($post_types as $post_type) { ?>
                            <label>
                                <input class="widefat" id="<?php echo $this->get_field_id( 'tsul_post_types' ); ?>" name="<?php echo $this->get_field_name( 'tsul_post_types' ); ?>[]" type="checkbox" value="<?php echo $post_type; ?>" <?php echo ( in_array($post_type, $used_post_types) ? 'checked="checked"' : '' ); ?> />
                                <?php echo $post_type; ?>
                            </label>
                        <?php } ?>
                    </div>
                </div>
                <div class="form-line">
                    <div class="form-label">
                        <label for="<?php echo $this->get_field_id( 'tsul_exclude_hp' ); ?>"><?php _e( 'Exclude Homepage:', 'top-sites-url-list' ); ?></label>
                    </div>
                    <div class="form-field form-labels">
                        <label>
                            <input class="widefat" id="<?php echo $this->get_field_id( 'tsul_exclude_hp' ); ?>" name="<?php echo $this->get_field_name( 'tsul_exclude_hp' ); ?>" type="checkbox" <?php echo ( isset($data['tsul_exclude_hp']) && $data['tsul_exclude_hp'] == 'true' ? 'checked="checked"' : '' ); ?> />
                            <?php _e('Exclude Homepage from listing', 'top-sites-url-list'); ?>
                        </label>
                    </div>
                </div>
                <div class="form-line">
                    <div class="form-label">
                        <label for="<?php echo $this->get_field_id( 'tsul_date_range' ); ?>"><?php _e( 'Number of days for fetching data:', 'top-sites-url-list' ); ?></label>
                    </div>
                    <div class="form-field">
                        <input id="<?php echo $this->get_field_id( 'tsul_date_range' ); ?>" class="field-sm" name="<?php echo $this->get_field_name( 'tsul_date_range' ); ?>" type="text" value="<?php echo (isset($data['tsul_date_range']) && $data['tsul_date_range'] != '' ? esc_attr( $data['tsul_date_range']) : '7' ); ?>" size="3" />
                    </div>
                </div>
                <div class="form-line">
                    <div class="form-label">
                        <label for="<?php echo $this->get_field_id( 'tsul_lines' ); ?>"><?php _e( 'Number of lines to show:', 'top-sites-url-list' ); ?></label>
                    </div>
                    <div class="form-field">
                        <input id="<?php echo $this->get_field_id( 'tsul_lines' ); ?>" class="field-sm" name="<?php echo $this->get_field_name( 'tsul_lines' ); ?>" type="text" value="<?php echo (isset($data['tsul_lines']) ? esc_attr( $data['tsul_lines']) : '' ); ?>" size="3" />
                    </div>
                </div>
                <div class="form-line tsul-widget-numbers-checkbox">
                    <div class="post-sort">
                        <div class="form-label">
                            <label for="<?php echo $this->get_field_id( 'tsul_visits_number' ); ?>"><?php _e( 'Show number of visits:', 'top-sites-url-list' ); ?></label>
                        </div>
                        <div class="form-field">
                            <input id="<?php echo $this->get_field_id( 'tsul_visits_number' ); ?>" class="checkbox" name="<?php echo $this->get_field_name( 'tsul_visits_number' ); ?>" type="checkbox" <?php echo (isset($data['tsul_visits_number']) && $data['tsul_visits_number'] == 'true' ? 'checked="checked"' : '' ) ?>>
                        </div>
                    </div>
                </div>
                <div class="tsul-widget-numbers">
                    <div class="form-line">
                        <div class="post-sort">
                            <div class="form-label">
                                <label for="<?php echo $this->get_field_id( 'tsul_visits_number_label' ); ?>"><?php _e( 'Show number as label:', 'top-sites-url-list' ); ?></label>
                            </div>
                            <div class="form-field">
                                <input id="<?php echo $this->get_field_id( 'tsul_visits_number_label' ); ?>" class="checkbox" name="<?php echo $this->get_field_name( 'tsul_visits_number_label' ); ?>" type="checkbox" <?php echo (isset($data['tsul_visits_number_label']) && $data['tsul_visits_number_label'] == 'true' ? 'checked="checked"' : '' ) ?>>
                            </div>
                        </div>
                    </div>
                    <div class="form-line">
                        <div class="post-sort">
                            <div class="form-label">
                                <label for="<?php echo $this->get_field_id( 'tsul_visits_number_front' ); ?>"><?php _e( 'Show the number at the front:', 'top-sites-url-list' ); ?></label>
                            </div>
                            <div class="form-field">
                                <input id="<?php echo $this->get_field_id( 'tsul_visits_number_front' ); ?>" class="checkbox" name="<?php echo $this->get_field_name( 'tsul_visits_number_front' ); ?>" type="checkbox" <?php echo (isset($data['tsul_visits_number_front']) && $data['tsul_visits_number_front'] == 'true' ? 'checked="checked"' : '' ) ?>>
                            </div>
                        </div>
                    </div>
                    <div class="form-line">
                        <div class="form-label">
                            <label for="<?php echo $this->get_field_id( 'tsul_visits_number_txt_before' ); ?>"><?php _e( 'Text before the number:', 'top-sites-url-list' ); ?></label>
                        </div>
                        <div class="form-field">
                            <input class="widefat" id="<?php echo $this->get_field_id( 'tsul_visits_number_txt_before' ); ?>" name="<?php echo $this->get_field_name( 'tsul_visits_number_txt_before' ); ?>" type="text" value="<?php echo (isset($data['tsul_visits_number_txt_before']) ? esc_attr( $data['tsul_visits_number_txt_before']) : '' ); ?>" />
                        </div>
                    </div>
                    <div class="form-line">
                        <div class="form-label">
                            <label for="<?php echo $this->get_field_id( 'tsul_visits_number_txt_after' ); ?>"><?php _e( 'Text after the number:', 'top-sites-url-list' ); ?></label>
                        </div>
                        <div class="form-field">
                            <input class="widefat" id="<?php echo $this->get_field_id( 'tsul_visits_number_txt_after' ); ?>" name="<?php echo $this->get_field_name( 'tsul_visits_number_txt_after' ); ?>" type="text" value="<?php echo (isset($data['tsul_visits_number_txt_after']) ? esc_attr( $data['tsul_visits_number_txt_after']) : '' ); ?>" />
                        </div>
                    </div>
                </div>




                <div class="form-line styling-options-title">
                    <div class="form-label">
                        <label for="<?php echo $this->get_field_id( 'tsul_styling' ); ?>"><?php _e( 'Use own styles', 'top-sites-url-list' ); ?>:</label>
                    </div>
                    <div class="form-field">
                        <input id="<?php echo $this->get_field_id( 'tsul_styling' ); ?>" class="checkbox" name="<?php echo $this->get_field_name( 'tsul_styling' ); ?>" type="checkbox" <?php echo (isset($data['tsul_styling']) && $data['tsul_styling'] == 'true' ? 'checked="checked"' : '' ) ?>>
                    </div>
                </div>

                <div class="styling-options">
                    <div class="form-line">
                        <div class="form-label">
                            <label for="<?php echo $this->get_field_id( 'tsul_styling_title_size' ); ?>"><?php _e( 'Title font size:', 'top-sites-url-list' ); ?></label>
                        </div>
                        <div class="form-field">
                            <input id="<?php echo $this->get_field_id( 'tsul_styling_title_size' ); ?>" class="field-sm" name="<?php echo $this->get_field_name( 'tsul_styling_title_size' ); ?>" type="text" value="<?php echo (isset($data['tsul_styling_title_size']) ? esc_attr( $data['tsul_styling_title_size']) : '' ); ?>" size="3" />
                        </div>
                    </div>

                    <div class="form-line margin-bottom">
                        <div class="form-label">
                            <label for="<?php echo $this->get_field_id( 'tsul_styling_title_color' ); ?>"><?php _e( 'Title color:', 'top-sites-url-list' ); ?></label>
                        </div>
                        <div class="form-field">
                            <input id="<?php echo $this->get_field_id( 'tsul_styling_title_color' ); ?>" name="<?php echo $this->get_field_name( 'tsul_styling_title_color' ); ?>" type="text" value="<?php echo (isset($data['tsul_styling_title_color']) ? esc_attr( $data['tsul_styling_title_color']) : '' ); ?>" class="my-color-field" />
                        </div>
                    </div>

                    <div class="form-line">
                        <div class="form-label">
                            <label for="<?php echo $this->get_field_id( 'tsul_styling_links_size' ); ?>"><?php _e( 'Links font size:', 'top-sites-url-list' ); ?></label>
                        </div>
                        <div class="form-field">
                            <input id="<?php echo $this->get_field_id( 'tsul_styling_links_size' ); ?>" class="field-sm" name="<?php echo $this->get_field_name( 'tsul_styling_links_size' ); ?>" type="text" value="<?php echo (isset($data['tsul_styling_links_size']) ? esc_attr( $data['tsul_styling_links_size']) : '' ); ?>" size="3" />
                        </div>
                    </div>

                    <div class="form-line margin-bottom">
                        <div class="form-label">
                            <label for="<?php echo $this->get_field_id( 'tsul_styling_links_color' ); ?>"><?php _e( 'Links color:', 'top-sites-url-list' ); ?></label>
                        </div>
                        <div class="form-field">
                            <input id="<?php echo $this->get_field_id( 'tsul_styling_links_color' ); ?>" name="<?php echo $this->get_field_name( 'tsul_styling_links_color' ); ?>" type="text" value="<?php echo (isset($data['tsul_styling_links_color']) ? esc_attr( $data['tsul_styling_links_color']) : '' ); ?>" class="my-color-field" />
                        </div>
                    </div>


                    <div class="form-line">
                        <div class="form-label">
                            <label for="<?php echo $this->get_field_id( 'tsul_styling_label_size' ); ?>"><?php _e( 'Label font size:', 'top-sites-url-list' ); ?></label>
                        </div>
                        <div class="form-field">
                            <input id="<?php echo $this->get_field_id( 'tsul_styling_label_size' ); ?>" class="field-sm" name="<?php echo $this->get_field_name( 'tsul_styling_label_size' ); ?>" type="text" value="<?php echo (isset($data['tsul_styling_label_size']) ? esc_attr( $data['tsul_styling_label_size']) : '' ); ?>" size="3" />
                        </div>
                    </div>

                    <div class="form-line">
                        <div class="form-label">
                            <label for="<?php echo $this->get_field_id( 'tsul_styling_label_color' ); ?>"><?php _e( 'Label color:', 'top-sites-url-list' ); ?></label>
                        </div>
                        <div class="form-field">
                            <input id="<?php echo $this->get_field_id( 'tsul_styling_label_color' ); ?>" name="<?php echo $this->get_field_name( 'tsul_styling_label_color' ); ?>" type="text" value="<?php echo (isset($data['tsul_styling_label_color']) ? esc_attr( $data['tsul_styling_label_color']) : '' ); ?>" class="my-color-field" />
                        </div>
                    </div>

                    <div class="form-line">
                        <div class="form-label">
                            <label for="<?php echo $this->get_field_id( 'tsul_styling_label_bg_color' ); ?>"><?php _e( 'Label background color:', 'top-sites-url-list' ); ?></label>
                        </div>
                        <div class="form-field">
                            <input id="<?php echo $this->get_field_id( 'tsul_styling_label_bg_color' ); ?>" name="<?php echo $this->get_field_name( 'tsul_styling_label_bg_color' ); ?>" type="text" value="<?php echo (isset($data['tsul_styling_label_bg_color']) ? esc_attr( $data['tsul_styling_label_bg_color']) : '' ); ?>" class="my-color-field" />
                        </div>
                    </div>

                    <div class="form-line">
                        <div class="form-label">
                            <label for="<?php echo $this->get_field_id( 'tsul_styling_custom_css' ); ?>"><?php _e( 'Custom CSS:', 'top-sites-url-list' ); ?></label>
                        </div>
                        <div class="form-field">
                            <textarea id="<?php echo $this->get_field_id( 'tsul_styling_custom_css' ); ?>" name="<?php echo $this->get_field_name( 'tsul_styling_custom_css' ); ?>" rows="5"><?php echo (isset($data['tsul_styling_custom_css']) ? esc_attr( $data['tsul_styling_custom_css']) : '' ); ?></textarea>
                        </div>
                    </div>
                </div>
            </div><br><br>
            <?php

            if( isset($_POST) && is_array($_POST) && count($_POST) > 0) {
                if ( wp_next_scheduled( 'tsul_cron_hook' ) ) {
                    $timestamp = wp_next_scheduled( 'tsul_cron_hook' );
                    $original_args = array();
                    wp_unschedule_event( $timestamp, 'tsul_cron_hook', $original_args );
                }
                wp_schedule_event( time(), 'tsul_cron_recurrance', 'tsul_cron_hook' );
            }
        }
    }

    # Updating widget replacing old instances with new
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['tsul_title']                      = ( ! empty( $new_instance['tsul_title'] ) ) ? strip_tags( $new_instance['tsul_title'] ) : '';
        $instance['tsul_post_types']                 = ( ! empty( $new_instance['tsul_post_types'] ) ) ? serialize( $new_instance['tsul_post_types'] ) : '';
        $instance['tsul_exclude_hp']                 = ( ! empty( $new_instance['tsul_exclude_hp'] ) ) ? 'true' : 'false';
        $instance['tsul_date_range']                 = ( ! empty( $new_instance['tsul_date_range'] ) && is_int(intval($instance['tsul_date_range'])) ) ? strip_tags( $new_instance['tsul_date_range'] ) : '';
        $instance['tsul_lines']                      = ( ! empty( $new_instance['tsul_lines'] ) && is_int(intval($instance['tsul_lines'])) ) ? strip_tags( $new_instance['tsul_lines'] ) : '';
        $instance['tsul_visits_number']              = ( ! empty( $new_instance['tsul_visits_number'] ) ) ? 'true' : 'false';
        $instance['tsul_visits_number_label']        = ( ! empty( $new_instance['tsul_visits_number_label'] ) ) ? 'true' : 'false';
        $instance['tsul_visits_number_front']        = ( ! empty( $new_instance['tsul_visits_number_front'] ) ) ? 'true' : 'false';
        $instance['tsul_visits_number_txt_before']   = ( ! empty( $new_instance['tsul_visits_number_txt_before'] ) ) ? strip_tags( $new_instance['tsul_visits_number_txt_before'] ) : '';
        $instance['tsul_visits_number_txt_after']    = ( ! empty( $new_instance['tsul_visits_number_txt_after'] ) ) ? strip_tags( $new_instance['tsul_visits_number_txt_after'] ) : '';
        $instance['tsul_styling']                    = ( ! empty( $new_instance['tsul_styling'] ) ) ? 'true' : '';

        $instance['tsul_styling_options'] = array(
            'title_size'        => $new_instance['tsul_styling_title_size'],
            'title_color'       => $new_instance['tsul_styling_title_color'],
            'links_size'        => $new_instance['tsul_styling_links_size'],
            'links_color'       => $new_instance['tsul_styling_links_color'],
            'label_size'        => $new_instance['tsul_styling_label_size'],
            'label_color'       => $new_instance['tsul_styling_label_color'],
            'label_bg_color'    => $new_instance['tsul_styling_label_bg_color'],
            'custom_css'        => $new_instance['tsul_styling_custom_css']
        );

        return $instance;
    }
}

# Register and load the widget
function CategoryArchiveLabelWidget_load() {
    register_widget( 'CategoryArchiveLabelListWidget' );
}
add_action( 'widgets_init', 'CategoryArchiveLabelWidget_load' );
