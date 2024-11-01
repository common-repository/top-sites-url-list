<?php

function tsul_admin_options_scripts_and_styles() {
    wp_enqueue_script( 'tsul-admin-scripts' );
}

function tsul_settings_page_callback() {
    add_action( 'admin_enqueue_scripts', 'tsul_admin_options_scripts_and_styles' );

    if ( isset( $_POST['tsul_reset_options'] ) && wp_verify_nonce( $_POST['tsul_options_wpnonce'], 'tsul_options' ) ) {
        update_option( 'tsul_google_token', '' );
        update_option( 'tsul_google_authtoken', '' );
        update_option( 'tsul_google_uid', '' );
        update_option( 'tsul_hide_profiles', '' );
    }

    echo '<div class="wrap">';

    if ( get_option( 'tsul_google_token' ) == '' && ( ! isset( $_POST['tsul_google_submit'] ) || ( isset( $_POST['tsul_google_submit'] ) && $_POST['tsul_google_token'] == "" ) || ( isset( $_POST['tsul_google_submit'] ) && ! wp_verify_nonce( $_POST['tsul_google_wpnonce'], 'google_authentication' ) ) ) ) {

        echo '<h2>' . __( 'Google Authentication', 'top-sites-url-list' ) . '</h2>';

        if ( ! function_exists( 'curl_init' ) ) {
            print( 'Google PHP API Client requires the CURL PHP extension' );

            return;
        }

        if ( ! function_exists( 'json_decode' ) ) {
            print( 'Google PHP API Client requires the JSON PHP extension' );

            return;
        }

        if ( ! function_exists( 'http_build_query' ) ) {
            print( 'Google PHP API Client requires http_build_query()' );

            return;
        }

        $config = tsul_get_config();

        $url = http_build_query(
            [
                'next'          => tsul_get_option_page_url( false ),
                'scope'         => $config['scopes'],
                'response_type' => 'code',
                'redirect_uri'  => $config['redirect_uri'],
                'client_id'     => $config['client_id'],
            ]
        );
        ?>

        <div class="wrap">
            <?php if ( isset( $_POST['tsul_google_submit'] ) && $_POST['tsul_google_token'] == "" ) { ?>
                <div id="message" class="error notice is-dismissible">
                    <p><strong><?php _e( 'Fill the input field with Google Authentication Code.', 'top-sites-url-list' ); ?></strong></p>
                </div>
            <?php } ?>

            <p><?php _e( 'Please, click the button bellow to access this plugin to your Google Analytics account', 'top-sites-url-list' ); ?></p>
            <p>
                <a class="button button-primary" onclick="window.open('https://accounts.google.com/o/oauth2/auth?<?php echo $url ?>', 'activate','width=700, height=600, menubar=0, status=0, location=0, toolbar=0')" href="javascript:void(0);"><?php _e( 'Authenticate', 'top-sites-url-list' ); ?></a><br>
                <small><?php printf( __( 'Or %1$sclick here%2$s if you have popups blocked', 'top-sites-url-list' ), '<a target="_blank" href="https://accounts.google.com/o/oauth2/auth?' . $url . '">', '</a>' ); ?></small>
            </p>
            <br>

            <p><?php _e( 'Enter your Google Authentication Code in the input field bellow. This code will be used to get an Authentication Token so you can access your website stats.', 'top-sites-url-list' ); ?></p>
            <form method="post" action="<?php tsul_get_option_page_url(); ?>">
                <?php wp_nonce_field( 'google_authentication', 'tsul_google_wpnonce' ); ?>
                <input type="text" name="tsul_google_token" value="" style="width:450px;">
                <input type="submit" class="button button-primary" name="tsul_google_submit" value="<?php _e( 'Save &amp; Continue', 'top-sites-url-list' ); ?>">
            </form>
        </div>
        <?php
    } else {
        if ( isset( $_POST['tsul_google_submit'] ) && wp_verify_nonce( $_POST['tsul_google_wpnonce'], 'google_authentication' ) ) {
            update_option( 'tsul_google_token', esc_attr( $_POST['tsul_google_token'] ) );
        }

        if ( isset( $_POST['tsul_submit_options'] ) && wp_verify_nonce( $_POST['tsul_options_wpnonce'], 'tsul_options' ) ) {
            if ( isset( $_POST['tsul_first_fetch'] ) ) {
                $tsul_google_uid = explode( '|=|', $_POST['tsul_google_uid'] );
                update_option( 'tsul_google_uid', esc_attr( $tsul_google_uid[0] ) );
                update_option( 'tsul_google_profile_name', esc_attr( $tsul_google_uid[1] ) );
                update_option( 'tsul_first_fetch', ( isset( $_POST['tsul_first_fetching'] ) ? esc_attr( $_POST['tsul_first_fetching'] ) : '' ) );
            }
            update_option( 'tsul_cron_recurrance', esc_attr( $_POST['tsul_cron_recurrance'] ) );
            update_option( 'tsul_show_in_admin_tables', ( isset( $_POST['tsul_show_in_admin_tables'] ) ? 1 : '' ) );

            if ( wp_next_scheduled( 'tsul_cron_hook' ) ) {
                $timestamp     = wp_next_scheduled( 'tsul_cron_hook' );
                $original_args = [];
                wp_unschedule_event( $timestamp, 'tsul_cron_hook', $original_args );
            }

            if ( isset( $_POST['tsul_first_fetching'] ) && $_POST['tsul_first_fetching'] != '' ) {
                $time_tmp = date_create( date( $_POST['tsul_first_fetching'] ) );
                date_add( $time_tmp, date_interval_create_from_date_string( '-' . get_option( 'gmt_offset' ) . ' hours' ) );
                $time = date_timestamp_get( $time_tmp );
            } else {
                $time = time();
            }
            wp_schedule_event( $time, 'tsul_cron_recurrance', 'tsul_cron_hook' );

            $tsul_google_uid = esc_attr( $_POST['tsul_google_uid'] );
        }

        echo '<h2>' . __( 'TOP Sites URL list Settings', 'top-sites-url-list' ) . '</h2>';

        # Load Google analytics service
        tsul_Require_File( 'ga-service.php' );
        $ga = new GoogleAnalyticsService();
        if ( ! isset( $tsul_google_uid ) ) {
            $tsul_google_uid = get_option( 'tsul_google_uid' );
        }
        ?>

        <form method="post" action="<?php echo tsul_get_option_page_url(); ?>">
            <?php wp_nonce_field( 'tsul_options', 'tsul_options_wpnonce' ); ?>

            <?php if ( $tsul_google_uid == '' || get_option( 'tsul_google_uid' ) == '' ) {
                $get_check_login = true;
                $check_login     = $ga->checkLogin();

                if ( $check_login != false ) {
                    $ga->getAnalyticsAccounts();
                    $tsul_google_profiles = $ga->getAllProfiles();
                    asort( $tsul_google_profiles );
                    ?>

                    <table class="form-table">
                        <tr>
                            <th>
                                <label for="tsul_google_uid"><?php _e( 'Choose GA Profile', 'top-sites-url-list' ); ?></label>
                            </th>
                            <td>
                                <select name="tsul_google_uid">
                                    <?php foreach ( $tsul_google_profiles as $tsul_google_profile_id => $tsul_google_profile_name ) { ?>
                                        <option value="<?php echo $tsul_google_profile_id . '|=|' . $tsul_google_profile_name; ?>"><?php echo $tsul_google_profile_name; ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <?php if ( $tsul_google_uid == '' ) { ?>
                        <table class="form-table">
                            <tr>
                                <th>
                                    <label for="tsul_first_fetch"><?php _e( 'Start first fetching', 'top-sites-url-list' ); ?></label>
                                </th>
                                <td>
                                    <label for="tsul_first_fetch1"><input id="tsul_first_fetch1" name="tsul_first_fetch" type="radio" value="immediately" checked="checked"> Immediately</label><br>
                                    <label for="tsul_first_fetch2">
                                        <input id="tsul_first_fetch2" name="tsul_first_fetch" type="radio" value="at_time"> At specific time
                                        <input class="datepicker" name="tsul_first_fetching" type="text" value="">
                                    </label>
                                </td>
                            </tr>
                        </table>
                    <?php } ?>
                <?php } ?>

            <?php } else { ?>
                <input name="tsul_google_uid" type="hidden" value="<?php echo $tsul_google_uid; ?>">
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="tsul_google_uid"><?php _e( 'GA Profile', 'top-sites-url-list' ); ?></label>
                        </th>
                        <td>
                            <?php echo get_option( 'tsul_google_profile_name' ); ?><br>
                            <?php echo get_option( 'tsul_google_uid' ); ?>
                        </td>
                    </tr>
                </table>
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="tsul_next_execution"><?php _e( 'Next execution', 'top-sites-url-list' ); ?></label>
                        </th>
                        <td>
                            <?php
                            $actual_date_tmp = date_create( date( 'Y-m-d H:i:s' ) );
                            date_add( $actual_date_tmp, date_interval_create_from_date_string( get_option( 'gmt_offset' ) . ' hours' ) );

                            $cron_date_tmp = date_create( date( 'Y-m-d H:i:s', wp_next_scheduled( 'tsul_cron_hook' ) ) );
                            date_add( $cron_date_tmp, date_interval_create_from_date_string( get_option( 'gmt_offset' ) . ' hours' ) );

                            $actual_date = date_format( $actual_date_tmp, 'Y-m-d H:i:s' );
                            $cron_date   = date_format( $cron_date_tmp, 'Y-m-d H:i:s' );

                            if ( $cron_date <= $actual_date ) {
                                echo _e( 'Cron will be executed after next page refresh', 'top-sites-url-list' );
                            } else {
                                echo date_format( $cron_date_tmp, get_option( 'date_format' ) . ', H:i:s' );
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            <?php } ?>

            <?php if ( ! isset( $get_check_login ) || $check_login != false ) { ?>
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="tsul_cron_recurrance"><?php _e( 'Check new results every', 'top-sites-url-list' ); ?></label>
                        </th>
                        <td>
                            <input id="tsul_cron_recurrance" name="tsul_cron_recurrance" type="text" value="<?php echo get_option( 'tsul_cron_recurrance' ); ?>"> minutes
                        </td>
                    </tr>
                </table>

                <table class="form-table">
                    <tr>
                        <th>
                            <label for="tsul_show_in_admin_tables"><?php _e( 'Show stats in posts admin tables', 'top-sites-url-list' ); ?></label>
                        </th>
                        <td>
                            <input id="tsul_show_in_admin_tables" name="tsul_show_in_admin_tables" type="checkbox" value="1" <?php echo( get_option( 'tsul_show_in_admin_tables' ) == 1 ? 'checked="checked"' : '' ); ?>>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input id="submit" class="button button-primary" type="submit" value="<?php _e( 'Save changes', 'top-sites-url-list' ); ?>" name="tsul_submit_options">
                    <input id="submit" class="button button-default reset-options" style="margin-left: 20px" type="submit" value="<?php _e( 'Reset settings', 'top-sites-url-list' ); ?>" name="tsul_reset_options" title="<?php _e( 'Are you sure that you want to permanently delete the options of TOP Sites URL list?', 'top-sites-url-list' ); ?>">
                </p>
            <?php } ?>
        </form>


        <?php
    }

    echo '</div>';
}
