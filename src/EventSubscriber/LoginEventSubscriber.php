<?php

/**
 * @file
 * Contains \Drupal\rit_user_utils\EventSubscriber\LoginEventSubscriber.
 */

namespace Drupal\rit_user_utils\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use \Drupal\Core\Url;
use Psr\Log\LoggerInterface;

class LoginEventSubscriber implements EventSubscriberInterface {
	/**
	* The rit_user_utils.admin config object.
	*
	* @var \Drupal\Core\Config\Config;
	*/
	protected $config;

	/**
	* The current user.
	*
	* @var \Drupal\Core\Session\AccountInterface
	*/
	protected $account;

	/**
	* The module handler.
	*
	* @var \Drupal\Core\Extension\ModuleHandlerInterface
	*/
	protected $moduleHandler;

	/**
	* @var \Drupal\Core\Routing\UrlGeneratorInterface
	*/
	protected $urlGenerator;
	
	/**
	* A logger instance.
	*
	* @var \Psr\Log\LoggerInterface
	*/
	protected $logger;

	/**
	* Constructs a LoginEventSubscriber object.
	*/
	public function __construct(ConfigFactoryInterface $config, AccountInterface $account, ModuleHandlerInterface $module_handler, UrlGeneratorInterface $url_generator, LoggerInterface $logger) {
		$this->config = $config->get('rit_user_utils.admin');
		$this->account = $account;
		$this->moduleHandler = $module_handler;
		$this->urlGenerator = $url_generator;
		$this->logger = $logger;
	}
	
	/**
  * Initializes rit_user_utils module requirements.
  */
  public function externalauthLogin(GetResponseEvent $event) {

		$enable_auth = \Drupal::state()->get('rit_user_utils_auth_enable');
		$enable_auth_user = \Drupal::state()->get('rit_user_utils_auth_enable_user');
		$path = $_SERVER['REQUEST_URI'];
		$find = 'user';
		$pos = strpos($path, $find);
		$currentUrl = Url::fromRoute('<current>');
		$current_path = $currentUrl->getInternalPath();
		$is_admin = \Drupal::service('router.admin_context')->isAdminRoute();
		$current_user = \Drupal::currentUser();
		$tempstore = \Drupal::service('user.private_tempstore')->get('rit_user_utils');
		$clear_cache_login = $tempstore->get('clear_cache_login');
		
		if(!isset($enable_auth)){
			$enable_auth = 1;
		}
		if(!isset($enable_auth_user)){
			$enable_auth_user = 1;
		}
		


		if(($current_user->id())&&($is_admin)&&($pos===FALSE)) {
			if($_SERVER['REMOTE_USER']==''){

				$url = $GLOBALS['base_path'] . 'ritlogin?destination=' . $current_path;


				//Comment out until the rest of the module works
				$event = new TrustedRedirectResponse('https://' . $_SERVER['HTTP_HOST'] . '/' . $url);
				return $event;

			}
		}

		$authname = '';

		// Make sure we get the remote user whichever way it is available.
		if (isset($_SERVER['REDIRECT_REMOTE_USER'])) {
			$authname = $_SERVER['REDIRECT_REMOTE_USER'];
		}
		elseif (isset($_SERVER['REMOTE_USER'])) {
			$authname = $_SERVER['REMOTE_USER'];
		}

		$authname = trim($authname);

		//if webdev is used for login, switch to webteam
		if($authname == 'webdev') $authname = 'webteam';

		// Perform some cleanup so plaintext passwords aren't available under
		// mod_auth_kerb.
		unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

		//$user_obj = user_load_by_name($authname);
		$uid = db_query("SELECT uid FROM {users_field_data} WHERE name = :name", [
			'name' => $authname
		])->fetchField();

		
		$redirect_user = FALSE;
		
		if(isset($current_user) && $current_user->id() === $uid) {
			//do nothing
		}else if(!empty($authname)) {
			if ($this->config->get('debug')) {
				$this->logger->debug('Trying to login SSO-authenticated user with authname %authname', [
				  '%authname' => $authname,
				]);
            }
            
			$user = user_load($uid);

			if (!($uid)&&($authname != 'webteam')) {
				//print_r($_SERVER);

				$language = \Drupal::languageManager()->getCurrentLanguage()->getId();

				$user = \Drupal\user\Entity\User::create();
				$user->setPassword('');
				$user->enforceIsNew();
				$user->setEmail((isset($_SERVER['mail'])) ? $_SERVER['mail'] : trim($authname) . '@rit.edu');
				$user->setUsername(trim(strtolower($authname)));//This username must be unique and accept only a-Z,0-9, - _ @ .
				$user->set("init", trim(strtolower($authname)));
				$user->set("langcode", $language);
				$user->set("preferred_langcode", $language);
				$user->set("preferred_admin_langcode", $language);
				$user->activate();

				//Save user account
				$user->save();

				// No email verification required; log in user immediately.
				//_user_mail_notify('register_no_approval_required', $user);

				drupal_set_message(t('Registration successful. You are now logged in.'));
			}

			user_login_finalize($user);

			drupal_flush_all_caches();
			
			//do role assign stuff
			$redirect_user = TRUE;
    
			
		}
		
		if(!isset($user)){
			$user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
		}
		
		$num_roles = 0;
		$shib_vars = array();

		//get the shib variables from the database, default to ritEduAccountType if it's blank
		$shib_variables = \Drupal::state()->get('shib_role_assign_shib_variable', 'ritEduAffliation');

		//loop through the variables
		if(is_array($shib_variables)){
			foreach($shib_variables as $header){
				if($header!=''){
					//set the value to an array and append REDIRECT_ to the variable
					$shib_vars[] = 'REDIRECT_' . $header;
				}
			}
		}else{
			if($shib_variables!=''){
				$shib_vars[] = 'REDIRECT_' . $shib_variables;
			}
		}

		//if we haven't checked for affiliations yet
		$affil_check = $tempstore->get('affiliation_check');
		
		//if(!isset($affil_check) || $affil_check == false){
			// Make sure we get the remote user whichever way it is available.
			if (isset($_SERVER['REDIRECT_REMOTE_USER'])) {
				$authname = $_SERVER['REDIRECT_REMOTE_USER'];
			}
			elseif (isset($_SERVER['REMOTE_USER'])) {
				$authname = $_SERVER['REMOTE_USER'];
			}
	
			if(isset($authname)){
				$authname = trim($authname);
	
	
				//if webdev is used for login, switch to webteam
				if($authname == 'webdev') $authname = 'webteam';

				// Perform some cleanup so plaintext passwords aren't available under
				// mod_auth_kerb.
				unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

				if (!empty($authname)) {
					//loop  through the shib variables
					foreach($shib_vars as $variable){
				
						//if the $_SERVER variable is being set by Shibboleth
						if(isset($_SERVER[$variable])){
					
							//set the shib variable to a variable
							$userroles = $_SERVER[$variable];
							//set the roles to an array
							$userroles = explode(';', $userroles);
		
							//loop through the roles
							foreach($userroles as $myrole){
								//if the role exists in Drupal
								$user->addRole(strtolower($myrole));
								$user->save();
								//increment our num_roles variable
								$num_roles = $num_roles + 1;
								
					
							}
				
					
						}
					}
					//if we added roles to the user
					if($num_roles>0){
						//set the affiliation_check session to true
						$tempstore->set('affiliation_check', TRUE);
					}
				 }
			}
		//}
		
		
		
		//end role assign

		if($redirect_user){
			$goto = \Drupal::request()->query->get('q');
			$dest = 'destination';

			if($goto==''){
				$goto = \Drupal::url('<front>');
				$dest = 'front';
			}

			if(\Drupal::request()->query->get('destination')!='') {
				$goto = \Drupal::request()->query->get('destination');
				$dest = 'destination';	
			}
			
			if(($goto=="user%2Flogin")||($goto=="ritlogin")){
				$goto = '';
			}
				
			if($goto!=''){
				$response = new TrustedRedirectResponse('https://' . $_SERVER['HTTP_HOST'] . '/' . $GLOBALS['base_path'] . $goto);
			}else{
				$response = new TrustedRedirectResponse('https://' . $_SERVER['HTTP_HOST'] . '/' . $GLOBALS['base_path']);
			}

			return $response;
		}

	}

	/**
	* Implements EventSubscriberInterface::getSubscribedEvents().
	*
	* @return array
	*   An array of event listener definitions.
	*/
	static function getSubscribedEvents() {
		$events[KernelEvents::REQUEST][] = ['externalauthLogin'];
		return $events;
	}
}
