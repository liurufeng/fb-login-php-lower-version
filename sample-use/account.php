<?php

      require_once $_SERVER['DOCUMENT_ROOT'] . '/Facebook/autoload.php';

      $fb = new Facebook\Facebook(array(
        'app_id' => 'YOUR-FB-ID',
        'app_secret' => 'YOUR-FB-secret',
        'default_graph_version' => 'v2.5'
      ));

      $helper = $fb->getJavaScriptHelper();

      try {
        $accessToken = $helper->getAccessToken();
      } catch(Facebook\Exceptions\FacebookResponseException $e) {
        // When Graph returns an error
        echo 'Graph returned an error: ' . $e->getMessage();
		header('Location: /login.php');        
      } catch(Facebook\Exceptions\FacebookSDKException $e) {
        // When validation fails or other local issues
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        header('Location: /login.php');   
      }

      if (isset($accessToken)) {
        $fb->setDefaultAccessToken($accessToken);
        try {
          $requestProfile = $fb->get("/me?fields=name,first_name,last_name,email");
          $profile = $requestProfile->getGraphNode()->asArray();
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
          // When Graph returns an error
          echo 'Graph returned an error: ' . $e->getMessage();
          header('Location: /login.php');   
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
          // When validation fails or other local issues
          echo 'Facebook SDK returned an error: ' . $e->getMessage();
          header('Location: /login.php');   
        }
        //now let's check if the user is existing
        if($profile['email']) {
          $db = Database::getInstance();
          $userInfo = PasswordManagement::login_and_get_info($profile['email'], '', $db, false, true);
          if ($userInfo !== false) {
            $cust_acct = new CustomerAccount($profile['email']);
            $login = $cust_acct->login();

			header('Location: /account.php');   
            exit();
          } else {
            // didn't find this email (user account) in user database, create the new FB account
            $sql = sprintf("
                    INSERT INTO users (login, password, date_regd, verified_account, fname, lname, fb_user)
                    VALUES ('%s', '', CURDATE(), 1, '%s', '%s', 1)",                    
                  );
            if(!$db->query($sql)){
              header('Location: /login.php');   
            }
            else{
              // load the new customer's account info
              $cust_acct = new CustomerAccount($profile['email']);
              // log the customer into the site
              $cust_acct->login();
              header('Location: /account.php');   
              exit();
            }
          }
        } else {
          header('Location: /login.php');   
          exit();
        }
        exit;
      } else {
        header('Location: /login.php');   
        exit();
      }

     