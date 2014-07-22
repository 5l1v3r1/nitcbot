<?php
/*
* The code that powers @nitcbot (http://twitter.com/nitcbot)
*/
/***********************************************************************************************
 * Tweetledee  - Incredibly easy access to Twitter data
 *   searchrss.php -- Tweet search query results formatted as RSS feed
 *   Version: 0.3.3
 * Copyright 2013 Christopher Simpkins
 * MIT License
 ************************************************************************************************/
/*
* Modified by Arjun Sreedharan (@arjun024) to suit @nitcbot.
*/
$TLD_DEBUG = 0;
if ($TLD_DEBUG == 1){
    ini_set('display_errors', 'On');
    error_reporting(E_ALL | E_STRICT);
}

/*******************************************************************
*  Includes
********************************************************************/
// Matt Harris' Twitter OAuth library
require 'tldlib/tmhOAuth.php';
require 'tldlib/tmhUtilities.php';

// include user keys
require 'tldlib/keys/tweetledee_keys.php';

// include Geoff Smith's utility functions
require 'tldlib/tldUtilities.php';


$query = '"nit calicut" OR "nitcalicut" OR #NITCalicut OR "National Institute of Technology Calicut" OR "tathva nit" OR "ragam nit"'
.' OR @NITCalicut OR @NITcalicut OR #tedxnitcalicut -Iran -vacancy -jobs -job -position -positions -interview -vacancies'
.' -GRE -nangor -nitcbot -"curry club" -Manchester -"Throwback Thursday" -NITCConfessions'
.' -Detroit -Windsor -#jobsforDetroiters -Detroiters -DetroitChamber'
.' -"Ad-hoc" -Sarkari -Naukri -"Faculty Posts" -"Spot Admission"'
.' -RestlessMystic -NITCEvents -SarkariBankJobs';

/*******************************************************************
*  OAuth
********************************************************************/
$tmhOAuth = new tmhOAuth(array(
            'consumer_key'        => $my_consumer_key,
            'consumer_secret'     => $my_consumer_secret,
            'user_token'          => $my_access_token,
            'user_secret'         => $my_access_token_secret,
            'curl_ssl_verifypeer' => false
        ));

// request the user information
$code = $tmhOAuth->user_request(array(
            'url' => $tmhOAuth->url('1.1/account/verify_credentials')
          )
        );

// Display error response if do not receive 200 response code
if ($code <> 200) {
    if ($code == 429) {
        die("Exceeded Twitter API rate limit");
    }
    echo $tmhOAuth->response['error'];
    die("verify_credentials connection failure");
}

// Decode JSON
$data = json_decode($tmhOAuth->response['response'], true);

// Parse information from response
$twitterName = $data['screen_name'];
$fullName = $data['name'];
$twitterAvatarUrl = $data['profile_image_url'];

/*******************************************************************
*  Defaults
********************************************************************/
$count = 25;  //default tweet number = 25
$result_type = 'mixed'; //default to mixed popular and realtime results


/*******************************************************************
*   Optional Parameters
*    - can pass via URL to web server
*    - or as a short or long switch at the command line
********************************************************************/

// Command line parameter definitions //
if (defined('STDIN')) {
    // check whether arguments were passed, if not there is no need to attempt to check the array
    if (isset($argv)){
        $shortopts = "c:";
        $longopts = array(
            "rt",
        );
        $params = getopt($shortopts, $longopts);
        if (isset($params['c'])){
            if ($params['c'] > 0 && $params['c'] <= 200)
                $count = $params['c'];  //assign to the count variable
        }
        if (isset($params['rt'])){
            $result_type = $params['rt'];
        }
    }
}
// Web server URL parameter definitions //
else{
    // c = tweet count ( possible range 1 - 200 tweets, else default = 25)
    if (isset($_GET["c"])){
        if ($_GET["c"] > 0 && $_GET["c"] <= 200){
            $count = $_GET["c"];
        }
    }
    // rt = response type
    if (isset($_GET["rt"])){
        if ($_GET["rt"] == 'popular' || $_GET["rt"] == 'recent'){
            $result_type = $_GET["rt"];
        }
    }
}

//Create the feed title with the query
$feedTitle = 'Twitter search for "' . $query . '"';

// URL encode the search query
$urlquery = urlencode($query);

/*******************************************************************
*  Request
********************************************************************/
$code = $tmhOAuth->user_request(array(
            'url' => $tmhOAuth->url('1.1/search/tweets'),
            'params' => array(
                'include_entities' => true,
                'count' => $count,
                'result_type' => $result_type,
                'lang' => "en",
                'q' => $urlquery,
            )
        ));

// Anything except code 200 is a failure to get the information
if ($code <> 200) {
    echo $tmhOAuth->response['error'];
    die("tweet_search connection failure");
}

//concatenate the URL for the atom href link
if (defined('STDIN')) {
    $thequery = $_SERVER['PHP_SELF'];
} else {
    $thequery = $_SERVER['PHP_SELF'] .'?'. urlencode($_SERVER['QUERY_STRING']);
}

$searchResultsObj = json_decode($tmhOAuth->response['response'], true);

// Start the output
header("Content-Type: application/rss+xml");
header("Content-type: text/xml; charset=utf-8");
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <atom:link href="<?php echo $my_domain ?><?php echo $thequery ?>" rel="self" type="application/rss+xml" />
        <lastBuildDate><?php echo date(DATE_RSS); ?></lastBuildDate>
        <language>en</language>
        <title><?php echo $feedTitle; ?></title>
        <description>A Twitter search for the query "<?php echo $query; ?>" with the <?php echo $result_type?> search result type</description>
        <link>http://www.twitter.com/search/?q=<?php echo $query; ?></link>
        <ttl>960</ttl>
        <generator>Tweetledee</generator>
        <category>Personal</category>
        <image>
        <title><?php echo $feedTitle; ?></title>
        <link>http://www.twitter.com/<?php echo $twitterName; ?></link>
        <url>http://www.twitter.com/search/?q=<?php echo $urlquery; ?></url>
        </image>
        <?php
        foreach ($searchResultsObj['statuses'] as $currentitem) : 
            // avoid recursive hell. I don't RT myself.
            if ($currentitem['retweeted_status']['user']['screen_name'] == "nitcbot") {
                    continue;
            }
            $parsedTweet = tmhUtilities::entify_with_options(
                    objectToArray($currentitem),
                    array(
                        'target' => 'blank',
                    )
            );

            if (isset($currentitem['retweeted_status'])) :
                //echo "<designation>retweet</designation>";
                continue;
            else :
                //echo "<designation>tweet</designation>";
                echo "<item>";
                $avatar = $currentitem['user']['profile_image_url'];
                $rt = '';
                $tweeter = $currentitem['user']['screen_name'];
                $fullname = $currentitem['user']['name'];
                $tweetTitle = $currentitem['text'];
            endif;
                ?>
                <title>
                    <![CDATA[ <?php echo "[@".$tweeter."]\r\n".$tweetTitle; ?> ]]>
                </title>
                <link>https://twitter.com/<?php echo $currentitem['user']['screen_name'] ?>/statuses/<?php echo $currentitem['id_str']; ?></link>
                <guid isPermaLink='false'><?php echo $currentitem['id_str']; ?></guid>
                <description>
                    <![CDATA[
                        <div style='float:left;margin: 0 6px 6px 0;'>
                            <a href='https://twitter.com/<?php echo $tweeter ?>/statuses/<?php echo $currentitem['id_str']; ?>' border=0 target='blank'>
                                <img src='<?php echo $avatar; ?>' border=0 />
                            </a>
                        </div>
                        <strong><?php echo $fullname; ?></strong> <a href='https://twitter.com/<?php echo $tweeter; ?>' target='blank'>@<?php echo $tweeter;?></a><?php echo $rt ?><br />
                        <?php echo $parsedTweet; ?>
                    ]]>
               </description>
               <pubDate><?php echo reformatDate($currentitem['created_at']); ?></pubDate>
            </item>
        <?php endforeach; ?>
    </channel>
</rss>
