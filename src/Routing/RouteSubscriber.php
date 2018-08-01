<?php

namespace Drupal\rit_user_utils\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use \Drupal\Core\Url;
use Psr\Log\LoggerInterface;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\rit_user_utils\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {
   protected $configFactory;

   /**
   * RouteSubscriber constructor.
   * @param ConfigFactoryInterface $config_factory
   */
   public function __construct(ConfigFactoryInterface $config_factory) {
      $this->configFactory = $config_factory;
   }
  
  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
	
	/*if ($route = $collection->get('user.page')) {
		$route->setDefaults(array(
			'_title' => 'Login',
			'_controller' => '\Drupal\rit_user_utils\Controller\RITuserutilsAuthController::auth_check_user',
		));
		$route->setRequirement('_user_is_logged_in', 'FALSE');
		
    }*/
    if ($route = $collection->get('user.login')) {
    	$route->setDefaults(array(
			'_title' => 'Login',
			'_controller' => '\Drupal\rit_user_utils\Controller\RITuserutilsAuthController::auth_check_user_login',
		));
		
    }
    if ($route = $collection->get('user.logout')) {
    	$route->setDefaults(array(
			'_controller' => '\Drupal\rit_user_utils\Controller\RITuserutilsAuthController::rit_logout',
		));
    }
  }

}
