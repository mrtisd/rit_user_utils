<?php

namespace Drupal\rit_user_utils\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;


class RITuserutilsAuthController extends ControllerBase implements ContainerInjectionInterface {

  /*
  * Function to authenticate using /ritlogin
  */
  public function rit_login() {
	//get variables from the database
	$custom_config = \Drupal::state()->get('rit_user_utils_custom_config');
	
	$goto = '';
	
    if(isset($_GET['destination'])) {
		$goto = $_GET['destination'];
		unset($_GET['destination']);
	}

	//if the site has a custom configuration
	if($custom_config==1){
		//add the REQUESTED_SITE to the redirect
		$url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUESTED_SITE'] . "/Shibboleth.sso/Login?target=" . $_SERVER['REQUESTED_SITE'] . "/" . $goto;
	}else{
		$url = "https://" . $_SERVER['HTTP_HOST'] . "/Shibboleth.sso/Login?target=" . $_SERVER['REQUESTED_SITE'] . "/" . $goto;
	}
	
	//flush all caches to avoid permissions issues when coming back due to caching
	drupal_flush_all_caches();
    
	//set a new TrustedRedirectResponse to the url defined above
	$response = new TrustedRedirectResponse($url);
	return $response->send();
  }
  
  public function authenticate() {
	//get variables from the database
	$custom_config = \Drupal::state()->get('rit_user_utils_custom_config');
	
	$goto = '';

	if(isset($_GET['destination'])) {
		$goto = $_GET['destination'];
		unset($_GET['destination']);
	}

	if($custom_config){
		$url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUESTED_SITE'] . "/Shibboleth.sso/Login?target=" . $_SERVER['REQUESTED_SITE'] . "/" . $goto;
	}else{
		$url = "https://" . $_SERVER['HTTP_HOST'] . "/Shibboleth.sso/Login?target=" . $_SERVER['REQUESTED_SITE'] . "/" . $goto;
	}
	
	drupal_flush_all_caches();

	$response = new TrustedRedirectResponse($url);
	return $response->send();
	
  }
  
  public function rit_logout() {
  	user_logout();
  	
	$enable_auth = \Drupal::state()->get('rit_user_utils_auth_enable');
	$enable_auth_user = \Drupal::state()->get('rit_user_utils_auth_enable_user');


	if(($enable_auth==1)||($enable_auth_user==1)){
		$url = "https://shibboleth.main.ad.rit.edu/logout.html";
	
		drupal_flush_all_caches();

		$response = new TrustedRedirectResponse($url);
		return $response->send();
	}
  }
  public function auth_check_user() {
	$enable_auth = \Drupal::state()->get('rit_user_utils_auth_enable');
	
	if($enable_auth==0){
		$form = \Drupal::formBuilder()->getForm(\Drupal\user\Form\UserLoginForm::class);
   
		return $form;
	}else{
		$this->authenticate();
	}
  }
  public function auth_check_user_login() {
	$enable_auth_user = \Drupal::state()->get('rit_user_utils_auth_enable_user');
	
	if($enable_auth_user==0){
		$form = \Drupal::formBuilder()->getForm(\Drupal\user\Form\UserLoginForm::class);
   
		return $form;
	}else{
		$this->authenticate();
	}
  }

}
