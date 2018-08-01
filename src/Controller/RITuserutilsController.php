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


class RITuserutilsController extends ControllerBase {
	public function settings() {
		
		return $response;
	}
}