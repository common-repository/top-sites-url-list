<?php
/**
 * Plugin Name: TOP Sites URL list
 * Plugin URI: http://www.mvmtrade.sk
 * Description: This plugin provides a list of top visited sites on your web based on Google Analytics API.
 * Version: 1.7.1
 * Author: Marek Vrtich
 * Author URI: http://www.mvmtrade.sk
 * License: GPL2
 */

add_action( 'wp_enqueue_scripts', 'reg_tsul_scripts_and_styles' );
add_action( 'admin_enqueue_scripts', 'reg_tsul_admin_scripts_and_styles' );

function reg_tsul_scripts_and_styles() {
    wp_register_style( 'tsul-styles', plugins_url( 'css/tsul_styles.css', __FILE__ ), false, '1.0.0' );
}

function reg_tsul_admin_scripts_and_styles() {
    wp_register_style( 'tsul-admin-styles', plugins_url( 'css/tsul_admin_styles.css', __FILE__ ), false, '1.0.0' );
    wp_register_script( 'tsul-admin-scripts', plugins_url( 'js/tsul_admin_script.js', __FILE__ ), [ 'jquery', 'wp-color-picker' ], '1.0.0', true );

    wp_enqueue_style( 'jquery-ui-style', plugins_url( 'css/jquery-ui.css', __FILE__ ), false, '1.10.4' );
    wp_enqueue_style( 'tsul-timepicker-styles', plugins_url( 'css/jquery-ui-timepicker-addon.min.css', __FILE__ ), [ 'jquery-ui-style' ], '1.4.3' );

    wp_enqueue_script( 'tsul-timepicker-script', plugins_url( 'js/jquery-ui-timepicker-addon.min.js', __FILE__ ), [ 'jquery-ui-core', 'jquery-ui-datepicker' ], '1.4.3', true );
}



# Add default options
add_option( 'tsul_google_token' );
add_option( 'tsul_google_authtoken' );
add_option( 'tsul_google_uid' );
add_option( 'tsul_google_profile_name' );
add_option( 'tsul_first_fetch' );
add_option( 'tsul_cron_recurrance', 120 );
add_option( 'tsul_show_in_admin_tables', 1 );
add_option( 'tsul_full_stats' );
add_option( 'tsul_stats' );



# Function for including files
function tsul_Require_File( $file ) {
    static $list = [];

    if ( isset( $file ) && ! in_array( $file, $list ) ) {
        $list[]    = $file;
        $file_path = plugin_dir_path( __FILE__ ) . $file;

        if ( file_exists( $file_path ) ) {
            require_once $file_path;
        }
    }
}

# Load main Functions
tsul_Require_File( 'functions.php' );

# Load Settings page
tsul_Require_File( 'settings-page-callback.php' );

# Load Widget
tsul_Require_File( 'widget.php' );



# Add admin menu item
add_action( 'admin_menu', 'tsul_register_menu' );
function tsul_register_menu() {
    add_submenu_page( 'options-general.php', 'TOP Sites URL list Options', 'TOP Sites URL list', 'manage_options', 'tsul_settings_page', 'tsul_settings_page_callback' );
}



function myscript() {
    if ( isset( $_POST['tsul_google_wpnonce'] ) ) {
        ?>
        <script type="text/javascript">
            jQuery('body').prepend('<div class="tsul-loading"><div class="error settings-error notice"><p><strong>Please wait until page will show your Google Analytics profiles.</strong></p></div></div>');

            jQuery(document).ready(function () {
                jQuery('.settings_page_tsul_settings_page').find('.error.settings-error.notice').hide();
            })
        </script>
        <?php
    }
}

add_action( 'adminmenu', 'myscript' );




add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'add_action_links' );
function add_action_links( $links ) {
    $mylinks = [
        '<a href="' . admin_url( 'options-general.php?page=tsul_settings_page' ) . '">Settings</a>',
    ];

    return array_merge( $mylinks, $links );
}




# Add recurrance to cron
add_filter( 'cron_schedules', 'cron_add_recurrance' );

function cron_add_recurrance( $schedules ) {
    $cron_recurrance                   = intval( get_option( 'tsul_cron_recurrance' ) ) * 60;
    $schedules['tsul_cron_recurrance'] = [
        'interval' => $cron_recurrance,
        'display'  => __( 'TOP Sites URL list Cron' ),
    ];

    return $schedules;
}


# Add cron function
add_action( 'tsul_cron_hook', 'tsul_cron_hook_function' );

function tsul_cron_hook_function() {
    # Load Google analytics service
    tsul_Require_File( 'ga-service.php' );
    $ga = new GoogleAnalyticsService();
    $ga->checkLogin();
    $ga_single_profile = $ga->getSingleProfile();

    $post_types  = tsul_get_post_types();
    $widget_tsul = get_option( 'widget_tsul' );

    # For each widget get analytics data
    if ( isset( $widget_tsul ) && is_array( $widget_tsul ) ) {
        foreach ( $widget_tsul as $widget_id => $widget ) {
            if ( ! is_int( $widget_id ) ) {
                continue;
            }

            $used_post_types = [];
            if ( unserialize( $widget['tsul_post_types'] ) ) {
                $used_post_types = unserialize( $widget['tsul_post_types'] );
            }

            $max_results = intval( $widget['tsul_lines'] );

            $ga_args = [
                'date-range'  => $widget['tsul_date_range'],
                'max-results' => false,
            ];

            # Get data from analytics
            $results = $ga->getResults( $ga_single_profile[0]['id'], $ga_args );

            if ( isset( $results->rows ) && is_array( $results->rows ) ) {
                $i             = 0;
                $included_urls = [];
                foreach ( $results->rows as $row ) {
                    $post_save_confirm = false;
                    $hp_page_exist     = true;

                    # If exclude homepage is set, then skip this page
                    if ( isset( $widget['tsul_exclude_hp'] ) && $widget['tsul_exclude_hp'] == 'true' && $row[0] == '/' ) {
                        continue;
                    }

                    $post_result = false;
                    preg_match( '/^(\/[^#?]+)/', $row[0], $clean_url );

                    if ( ( isset( $clean_url[1] ) && ! in_array( $clean_url[1], $included_urls ) ) || ( ( ! isset( $widget['tsul_exclude_hp'] ) || $widget['tsul_exclude_hp'] != 1 ) && $row[0] == '/' ) ) {
                        # Fix for Suburl Multidomain Instalations
                        $home_url = home_url( '', 'relative' ) . '/';
                        if ( $clean_url[1] !== $home_url ) {
                            $clean_url[1] = preg_replace( '/^' . str_replace( '/', '\/', home_url( '', 'relative' ) ) . '\//', '/', $clean_url[1] );
                        }

                        # Get post by post_name
                        if ( $row[0] == '/' ) {
                            $hp_page_id = get_option( 'page_on_front' );

                            if ( $hp_page_id == 0 ) {
                                $hp_page_exist = false;
                            } else {
                                $post_result = get_page( $hp_page_id );
                            }
                        } else {
                            $post_result     = get_page_by_path( basename( untrailingslashit( $clean_url[1] ) ), OBJECT, $post_types );
                            $included_urls[] = $clean_url[1];
                        }

                        $post_title = false;
                        if ( function_exists( qtranxf_use ) && $hp_page_exist ) {
                            preg_match( '/^\/([a-zA-Z]{2})\//', $row[0], $lang_url );
                            if ( isset( $lang_url[1] ) ) {
                                $post_title = qtranxf_use( $lang_url[1], $post_result->post_title, true );
                            }
                        }

                        if ( ! $post_title ) {
                            $post_title = $post_result->post_title;
                        }

                        # Set post to the array, which will be serialized and saved
                        if ( $post_result && $hp_page_exist && ( ( is_array( $used_post_types ) && count( $used_post_types ) > 0 && in_array( $post_result->post_type, $used_post_types ) ) || ( is_array( $used_post_types ) && count( $used_post_types ) == 0 ) ) ) {
                            $post_save_confirm                   = true;
                            $post_save[ 'tsul-' . $widget_id ][] = [
                                'post_id'        => $post_result->ID,
                                'post_title'     => $post_title,
                                'post_permalink' => rtrim(esc_url( home_url('', 'relative') ), '/') . $clean_url[1],
                                'post_views'     => $row[1],
                            ];
                        } elseif ( ! $hp_page_exist ) {
                            $post_save_confirm                   = true;
                            $post_save[ 'tsul-' . $widget_id ][] = [
                                'post_id'        => null,
                                'post_title'     => __( 'Homepage', 'top-sites-url-list' ),
                                'post_permalink' => rtrim(esc_url( home_url('', 'relative') ), '/') .'/',
                                'post_views'     => $row[1],
                            ];
                        }
                    }

                    if ( $post_save_confirm ) {
                        $i ++;
                    }

                    # If post type was selected and number of lines is set, end listing post after this number of lines
                    if ( isset( $max_results ) && $i == $max_results ) {
                        break;
                    }
                }
            }
        }


        # Check, if we have something to save, and save the data
        if ( is_array( $post_save ) ) {
            $post_save_serialized = serialize( $post_save );
            update_option( 'tsul_stats', $post_save_serialized );
        }
    }



    if ( get_option( 'tsul_show_in_admin_tables' ) == 1 ) {
        $post_ids = [];

        $ga_args = [
            'date-range'  => 1820,
            'max-results' => false,
        ];

        # Get data from analytics
        $results = $ga->getResults( $ga_single_profile[0]['id'], $ga_args );
        if ( is_array( $results->rows ) ) {
            foreach ( $results->rows as $row ) {
                $post_result = false;
                preg_match( '/^(\/[^#?]+\/)/', $row[0], $clean_url );

                if ( isset( $clean_url[1] ) || $row[0] == '/' ) {
                    # Get post by post_name
                    if ( $row[0] == '/' ) {
                        $hp_page_id = get_option( 'page_on_front' );

                        if ( $hp_page_id != 0 ) {
                            $post_result = get_page( $hp_page_id );
                        }
                    } else {
                        $post_result = get_page_by_path( basename( untrailingslashit( $clean_url[1] ) ), OBJECT, $post_types );
                    }

                    if ( ! in_array( $post_result->ID, $post_ids ) ) {
                        # Set post to the array, which will be serialized and saved
                        if ( $post_result ) {
                            $all_post_save[] = [
                                'post_id'    => $post_result->ID,
                                'post_views' => $row[1],
                            ];
                        }

                        $post_ids[] = $post_result->ID;
                    }
                }
            }

            # Check, if we have something to save, and save the data
            if ( is_array( $all_post_save ) ) {
                $all_post_save_serialized = serialize( $all_post_save );
                update_option( 'tsul_full_stats', $all_post_save_serialized );
            }
        }
    }
}

// Add stats to a column in WP-Admin
if ( get_option( 'tsul_full_stats' ) != '' && get_option( 'tsul_show_in_admin_tables' ) == 1 ) {
    $used_post_types = tsul_get_post_types();
    if ( is_array( $used_post_types ) ) {

        foreach ( $used_post_types as $post_type ) {
            switch ( $post_type ) {
                case 'post':
                    $post_type = 'posts';
                    break;
                case 'page':
                    $post_type = 'pages';
                    break;
                default:
                    break;
            }

            add_filter( 'manage_' . $post_type . '_columns', 'tsul_column_views' );
            add_action( 'manage_' . $post_type . '_custom_column', 'tsul_custom_column_views', 5, 2 );
        }
    }
}

function tsul_column_views( $defaults ) {
    $defaults['tsul_post_views'] = __( 'Views', 'top-sites-url-list' );

    return $defaults;
}

function tsul_custom_column_views( $column_name, $id ) {
    static $stat = false;

    if ( $column_name === 'tsul_post_views' ) {
        if ( ! $stat ) {
            $tsul_full_stats = get_option( 'tsul_full_stats' );
            if ( unserialize( $tsul_full_stats ) ) {
                $full_stats = unserialize( $tsul_full_stats );
            }

            if ( is_array( $full_stats ) ) {
                foreach ( $full_stats as $stat_tmp ) {
                    $stat[ $stat_tmp['post_id'] ] = $stat_tmp['post_views'];
                }
            }
        }

        if ( isset( $stat[ $id ] ) ) {
            echo $stat[ $id ];
        } else {
            echo __( 'Undefined', 'top-sites-url-list' );
        }
    }
}
