Twitter-Application-only-Authentication-App---OAuth-
====================================================

Provides a PHP Class that authenticates a Twitter Application.
- $oauthTwitter = new OauthTwitter($consumer_key, $consumer_secret);

For now, it allows you to launch 3 request types to the Twitter API :
- $oauthTwitter->getAccountInfos('screen_name');
- $oauthTwitter->getAccountStatus('screen_name');
- $oauthTwitter->getSearchResults('query_string');
