<?php


function tsul_get_config() {
    // See https://developers.google.com/identity/protocols/OAuth2InstalledApp#formingtheurl for more details about these fields.
    $config = array(
        'application_name' => 'TOP Sites URL list',
        'client_id'        => '946205211738-gdoqvbr67jtd4dtbdiiolpspju9okuta.apps.googleusercontent.com',
        'client_secret'    => 'HN4FqJ_9x0kq8Yk-L6B2qjcl',
        'redirect_uri'     => 'urn:ietf:wg:oauth:2.0:oob',
        'scopes'           => 'https://www.googleapis.com/auth/analytics.readonly'
    );

    return $config;
}


function tsul_get_option_page_url ( $echo = true ) {
    $url = 'options-general.php?page=tsul_settings_page';

    if ( $echo ) {
        echo $url;
    } else {
        return $url;
    }
}


function tsul_get_post_types () {
    $builtin_types = array('post', 'page');

    $custom_post_types_args = array(
        'public'   => true,
        '_builtin' => false
    );

    $custom_post_types = get_post_types($custom_post_types_args);

    if ( is_array($custom_post_types) ) {
        asort($custom_post_types);
        $post_types = array_merge($builtin_types, $custom_post_types);
    } else {
        $post_types = $builtin_types;
    }

    return $post_types;
}
