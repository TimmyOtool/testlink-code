<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * @filesource  iam.php
 *
 * iam OAUTH API (authentication)
 *
 * @internal revisions
 * @since 1.9.17
 *
 */

//Get token
function oauth_get_token($authCfg, $code)
{
  $result = new stdClass();
  $result->status = array('status' => tl::OK, 'msg' => null);

  //Params to get token
  $oauthParams = array(
     'code'          => $code,
     'grant_type'    => $authCfg['oauth_grant_type'],
     'client_id'     => $authCfg['oauth_client_id'],
     'redirect_uri'  => $authCfg['redirect_uri'],
     'client_secret' => $authCfg['oauth_client_secret']
  );
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $authCfg['token_url']);
  curl_setopt($curl, CURLOPT_POST, 1);
  curl_setopt($curl, CURLOPT_POSTFIELDS, urldecode(http_build_query($oauthParams)));
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  $result_curl = curl_exec($curl);

  curl_close($curl);
  $tokenInfo = json_decode($result_curl, true);

  //If token is received start session
  if (isset($tokenInfo['access_token'])){

	$aut="Authorization: Bearer " . $tokenInfo['access_token'];
	$opts = [
    		"http" => [
        	"method" => "GET",
        	"header" => $aut
		]
		];

	$context = stream_context_create($opts);
	$userprofile=file_get_contents($authCfg['oauth_profile'] , true,$context);
	$userInfo =  json_decode($userprofile);
    if (isset($userInfo->{'username'})){
      if (isset($authCfg['oauth_domain'])) {
        $domain = substr(strrchr($userInfo->{'email'}, "@"), 1);
        if ($domain !== $authCfg['oauth_domain']){
          $result->status['msg'] = 'User doesn\'t correspond to Oauth policy';
          $result->status['status'] = tl::ERROR;
        }
      }
    } else {
      $result->status['msg'] = 'User ID is empty';
      $result->status['status'] = tl::ERROR;
    }
    print($userprofile);
    $options = new stdClass();
    $options->givenName = $userInfo->{'given_name'};
    $options->familyName = $userInfo->{'family_name'};
    $options->email=$userInfo->{'email'};
    $options->user = $userInfo->{'username'};
    $options->auth = 'oauth';
    $result->options = $options;
  } else {
    $result->status['msg'] = 'An error occurred during getting token';
    $result->status['status'] = tl::ERROR;
  }

  return $result;

}