<?php

// Require the Oauth Class file
require_once('../Oauth.php');

$errors = '';

$consumer_key = 'MY_CONSUMER_KEY';
$consumer_secret = 'MY_CONSUMER_SECRET';
$app_name = 'This is my first Twitter Application !';

// Authenticate the Application
try {
  $oauthTwitter = new OauthTwitter($consumer_key, $consumer_secret, $app_name);
} catch (Exception $e) {
  $errors .= '<strong>Could not connect to twitter.</strong> Message: '. $e->getMessage();
}

if ($oauthTwitter) {
  // Get infos about this twitter account
  try {
    $account_infos = $oauthTwitter->getAccountInfos('julienjoye');
  } catch (Exception $e) {
    $errors .= '<strong>Could not retrieve this twitter account.</strong> Message: '. $e->getMessage();
  }

  // Get the last tweets for this twitter account
  try {
    $items = $oauthTwitter->getAccountStatuses('julienjoye');
  } catch (Exception $e) {
    $errors .= '<strong>Could not retrieve twitter statuses.</strong> Message: '. $e->getMessage();
  }
  if (is_array($items)) {
    $tweets = array();
    foreach ($items as $item) {
      $tweet = new Stdclass();
      $tweet->username = $item->user->screen_name;
      $tweet->userphoto = $item->user->profile_image_url;
      $tweet->text = $item->text;
      $tweet->timestamp = strtotime($item->created_at);
      $tweets[] = $tweet;
    }
  }

  $searched_term = 'High-tech';
  // Get the last results for a term
  try {
    $terms = $oauthTwitter->getSearchResults($searched_term);
  } catch (Exception $e) {
    $errors .= '<strong>Could not retrieve twitter results.</strong> Message: '. $e->getMessage();
  }
  if (is_array($terms)) {
    $results = array();
    foreach ($terms as $term) {
      $result = new Stdclass();
      $result->username = $term->user->screen_name;
      $result->userphoto = $term->user->profile_image_url;
      $result->text = $term->text;
      $result->timestamp = strtotime($term->created_at);
      $results[] = $result;
    }
  }
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title><?php print $app_name; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Oauth Twitter demonstration">
    <meta name="author" content="Julien Joye">

    <!-- Le styles -->
    <link href="./bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="./bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
    <link href="./bootstrap/css/style.css" rel="stylesheet">

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="./bootstrap/js/html5shiv.js"></script>
    <![endif]-->
  </head>

  <body>

    <div class="container-narrow">

      <div class="masthead">
        <h3 class="muted"><?php print $app_name; ?></h3>
      </div>

      <hr>

      <?php if ($errors): ?>
        <div class="alert alert-error">
          <?php print $errors; ?>
        </div>
      <?php endif; ?>

      <?php if ($account_infos): ?>
        <div class="jumbotron">
          <h1><?php print $account_infos->name; ?></h1>
          <p class="lead"><?php print $account_infos->description; ?></p>
          <p><?php print $account_infos->name .' has '. $account_infos->followers_count .' followers, and tweeted '. $account_infos->statuses_count .' times.'; ?></p>
        </div>
        <hr>
      <?php endif; ?>

      <div class="row-fluid marketing">

        <div class="span6">
          <?php if (isset($tweets)): ?>
            <div class="row">
              <div class="span6">
                <h3>Last tweets</h3>
              </div>
            </div>
            <?php foreach ($tweets as $tweet): ?>
              <div class="row">
                <div class="span12">
                  <h4><?php print $tweet->username; ?></h4>
                  <p class="date"><small><?php print date('d F Y H:i', $tweet->timestamp); ?></small></p>
                </div>
              </div>
              <div class="row tweet">
                <div class="span1">
                  <img src="<?php print $tweet->userphoto; ?>" alt="Photo of <?php print $tweet->username; ?>" />
                </div>
                <div class="span10">
                  <p><?php print $tweet->text; ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="span6">
          <?php if (isset($results)): ?>
            <div class="row">
              <div class="span12">
                <h3>Results for "<?php print $searched_term; ?>"</h3>
              </div>
            </div>
            <?php foreach ($results as $result): ?>
              <div class="row">
                <div class="span12">
                  <h4><?php print $result->username; ?></h4>
                  <p class="date"><small><?php print date('d F Y H:i', $result->timestamp); ?></small></p>
                </div>
              </div>
              <div class="row tweet">
                <div class="span1">
                  <img src="<?php print $result->userphoto; ?>" alt="Photo of <?php print $result->username; ?>" />
                </div>
                <div class="span10">
                  <p><?php print $result->text; ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </div>
      <hr>

      <div class="footer">
        <p>&copy; julienjoye 2013</p>
      </div>

    </div> <!-- /container -->

  </body>
</html>
