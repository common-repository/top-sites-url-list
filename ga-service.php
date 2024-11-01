<?php



class GoogleAnalyticsService {

    var $client = false;
    var $accountId;
    var $baseFeed = 'https://www.googleapis.com/analytics/v3';
    var $token = false;


    function GoogleAnalyticsService() {
        # Creates and returns the Analytics service object.

        # Load the Google API PHP Client Library.
        tsul_Require_File('google-api-php-client/src/Google/autoload.php');

        # Create and configure a new client object.
        $config = tsul_get_config();
        $this->client = new Google_Client();
        $this->client->setApprovalPrompt('force');
        $this->client->setAccessType('offline');
        $this->client->setClientId($config['client_id']);
        $this->client->setClientSecret($config['client_secret']);
        $this->client->setRedirectUri($config['redirect_uri']);
        $this->client->setScopes($config['scopes']);

        try {
            $this->analytics = new Google_Service_Analytics($this->client);
        }
        catch ( Google_ServiceException $e ) {
            print '(cas:48) There was an Analytics API service error ' . $e->getCode() . ':' . $e->getMessage();
            return false;
        }

        $this->client->setApplicationName("TOP Sites URL list");
    }


    function checkLogin() {
        $ga_google_authtoken  = get_option('tsul_google_authtoken');

        if ( !empty($ga_google_authtoken) ) {
            try {
                $this->client->setAccessToken($ga_google_authtoken);
            }
            catch( Google_AuthException $e ) {
                print '(cas:72) Google Analyticator was unable to authenticate you with
                        Google using the Auth Token you pasted into the input box on the previous step. <br><br>
                        This could mean either you pasted the token wrong, or the time/date on your server is wrong,
                        or an SSL issue preventing Google from Authenticating. <br><br>
                        <a href="' . admin_url('/options-general.php?page=ga_reset').'"> Try Deauthorizing &amp; Resetting Google Analyticator.</a>
                        <br><br><strong>Tech Info </strong> ' . $e->getCode() . ':' . $e->getMessage();

                return false;
            }
        } else {
            $authCode = get_option('tsul_google_token');
            if (empty($authCode)) return false;

            try {
                $accessToken = $this->client->authenticate($authCode);
            }
            catch( Exception $e ) {
                echo '
                    <form method="post" action="' . tsul_get_option_page_url(false) . '">
                        <p>
                            Google Analyticator was unable to authenticate you with Google using the Auth Token you pasted into the input box on the previous step.
                        </p>
                        <p>
                            This could mean either you pasted the token wrong, or the time/date on your server is wrong, or an SSL issue preventing Google from Authenticating.
                        </p>
                        <p class="submit">
                            <input id="submit" class="button button-default reset-options" type="submit" value="' . __('Reset settings', 'top-sites-url-list') . '" name="tsul_reset_options" title="' .  __('Are you sure that you want to permanently delete the options of TOP Sites URL list?', 'top-sites-url-list') . '">
                        </p>
                    </form>
                ';

                return false;
            }

            if($accessToken) {
                $this->client->setAccessToken($accessToken);
                update_option('tsul_google_authtoken', $accessToken);
            } else {
                return false;
            }
        }

        $this->token = $this->client->getAccessToken();
        return true;
    }


    function getSingleProfile() {
        $tsul_google_uid = get_option('tsul_google_uid');
        list($pre, $account_id, $post) = explode('-',$tsul_google_uid);

        if (empty($tsul_google_uid)) {
            return false;
        }
        try {
            $profiles = $this->analytics->management_profiles->listManagementProfiles($account_id, $tsul_google_uid);
        }
        catch (Google_ServiceException $e) {
            print 'There was an Analytics API service error ' . $e->getCode() . ': ' . $e->getMessage();
            return false;
        }

        $profile_id = $profiles->items[0]->id;
        if (empty($profile_id)) return false;

        $account_array = array();
        array_push($account_array, array('id'=>$profile_id, 'ga:webPropertyId'=>$tsul_google_uid));
        return $account_array;
    }


    function getAllProfiles() {
        $profile_array = array();

        try {
            $profiles = $this->analytics->management_webproperties->listManagementWebproperties('~all');
        }
        catch ( Google_ServiceException $e ) {
            print 'There was an Analytics API service error ' . $e->getCode() . ': ' . $e->getMessage();
        }

        if ( !empty( $profiles->items ) ) {
            foreach( $profiles->items as $profile ) {
                $profile_array[ $profile->id ] = str_replace('http://','',$profile->name );
            }
        }

        return $profile_array;
    }


    function getAnalyticsAccounts() {
        $analytics = new Google_Service_Analytics($this->client);
        $accounts = $analytics->management_accounts->listManagementAccounts();
        $account_array = array();

        $items = $accounts->getItems();

        if ( count($items) > 0 ) {
            foreach ( $items as $key => $item ) {
                $account_id = $item->getId();
                $webproperties = $analytics->management_webproperties->listManagementWebproperties($account_id);

                if ( !empty($webproperties) ) {
                    foreach ( $webproperties->getItems() as $webp_key => $webp_item ) {
                        $profiles = $analytics->management_profiles->listManagementProfiles($account_id, $webp_item->id);

                        $profile_id = $profiles->items[0]->id;
                        array_push($account_array, array('id'=>$profile_id, 'ga:webPropertyId'=>$webp_item->id));
                    }
                }
            }
            return $account_array;
        }
        return false;
    }




    function getResults( $profileId, $args = null ) {
        # Calls the Core Reporting API and queries for the number of sessions
        # for the last seven days.
        $options = array(
            'dimensions' => 'ga:pagePath',
            'metrics' => 'ga:pageviews',
            'sort' => '-ga:pageviews',
        );

        if ( $args['max-results'] ) {
            $options['max-results'] = $args['max-results'];
        }

        if ( isset($args['date-range']) && $args['date-range'] != '' ) {
            $date_range = intval($args['date-range']) . 'daysAgo';
        } else {
            $date_range = '7daysAgo';
        }

        return $this->analytics->data_ga->get(
            'ga:' . $profileId,
            $date_range,
            'today',
            'ga:visits',
            $options
        );
    }

    function printResults(&$results) {
        # Parses the response from the Core Reporting API and prints
        # the profile name and total sessions.
        if (count($results->getRows()) > 0) {

            # Get the profile name.
            $profileName = $results->getProfileInfo()->getProfileName();

            # Get the entry for the first entry in the first row.
            $rows = $results->getRows();
            $sessions = $rows[0][0];

            # Print the results.
            print "First view (profile) found: $profileName\n<br>";
            print "Total sessions: $sessions\n<br>";
        } else {
            print "No results found.\n<br>";
        }
    }

}
