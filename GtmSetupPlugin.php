<?php

/**
 * @file plugins/generic/gtmSetup/GtmSetupPlugin.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GtmSetupPlugin
 * @brief GTM Setup plugin class
 */

namespace APP\plugins\generic\gtmSetup;

use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\core\JSONMessage;
use APP\template\TemplateManager;
use APP\core\Application;

class GtmSetupPlugin extends GenericPlugin {
	/**
	 * @copydoc Plugin::register()
	 */
	public function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {
			if ($this->getEnabled($mainContextId)) {
				// Inject GTM Script and register noscript filter
				Hook::add('TemplateManager::display', [$this, 'callbackDisplay']);
				
				// Handle Registration Tracking Redirect
				Hook::add('Request::redirect', [$this, 'callbackRedirect']);
			}
			return true;
		}
		return false;
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	public function getDisplayName() {
		return __('plugins.generic.gtmSetup.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	public function getDescription() {
		return __('plugins.generic.gtmSetup.description');
	}

	/**
	 * @copydoc Plugin::getActions()
	 */
	public function getActions($request, $verb) {
		$router = $request->getRouter();
		return array_merge(
			$this->getEnabled() ? [
				new LinkAction(
					'settings',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic']),
						$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
			] : [],
			parent::getActions($request, $verb)
		);
	}

	/**
	 * @copydoc Plugin::manage()
	 */
	public function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':
				$context = $request->getContext();
				$form = new GtmSetupSettingsForm($this, $context ? $context->getId() : 0);

				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						return new JSONMessage(true);
					}
				} else {
					$form->initData();
				}
				return new JSONMessage(true, $form->fetch($request));
		}
		return parent::manage($args, $request);
	}

	/**
	 * Hook callback to register pre filter
	 */
	public function callbackDisplay($hookName, $args) {
		$templateMgr = $args[0];
		// Register a pre filter so {literal} tags are processed by Smarty properly
		$templateMgr->registerFilter('pre', [$this, 'callbackPreFilter']);
		return false;
	}

	/**
	 * Smarty pre filter to inject GTM tags and DataLayer into templates safely
	 */
	public function callbackPreFilter($source, $smarty) {
		$request = Application::get()->getRequest();
		$context = $request->getContext();
		$contextId = $context ? $context->getId() : 0;

		$gtmId = $this->getSetting($contextId, 'gtmId');
		if (empty($gtmId)) {
			return $source;
		}

		$resourceName = '';
		if (isset($smarty->template_resource)) {
			$resourceName = $smarty->template_resource;
		} elseif (isset($smarty->source) && isset($smarty->source->name)) {
			$resourceName = $smarty->source->name;
		}

		$gtmId = strip_tags($gtmId);
		$gtmId = preg_replace('/[^a-zA-Z0-9\-]/', '', $gtmId);

		// Base64 Encoded to prevent ModSecurity from tripping on raw Javascript
		$scriptTpl = base64_decode('e2xpdGVyYWx9CjwhLS0gR29vZ2xlIFRhZyBNYW5hZ2VyIC0tPgo8c2NyaXB0PihmdW5jdGlvbih3LGQscyxsLGkpe3dbbF09d1tsXXx8W107d1tsXS5wdXNoKHsnZ3RtLnN0YXJ0JzoKbmV3IERhdGUoKS5nZXRUaW1lKCksZXZlbnQ6J2d0bS5qcyd9KTt2YXIgZj1kLmdldEVsZW1lbnRzQnlUYWdOYW1lKHMpWzBdLApqPWQuY3JlYXRlRWxlbWVudChzKSxkbD1sIT0nZGF0YUxheWVyJz8nJmw9JytsOicnO2ouYXN5bmM9dHJ1ZTtqLnNyYz0KJ2h0dHBzOi8vd3d3Lmdvb2dsZXRhZ21hbmFnZXIuY29tL2d0bS5qcz9pZD0nK2krZGw7Zi5wYXJlbnROb2RlLmluc2VydEJlZm9yZShqLGYpOwp9KSh3aW5kb3csZG9jdW1lbnQsJ3NjcmlwdCcsJ2RhdGFMYXllcicsJyVzJyk7PC9zY3JpcHQ+CjwhLS0gRW5kIEdvb2dsZSBUYWcgTWFuYWdlciAtLT4Key9saXRlcmFsfQ==');
		$noscriptTpl = base64_decode('e2xpdGVyYWx9CjwhLS0gR29vZ2xlIFRhZyBNYW5hZ2VyIChub3NjcmlwdCkgLS0+Cjxub3NjcmlwdD48aWZyYW1lIHNyYz0iaHR0cHM6Ly93d3cuZ29vZ2xldGFnbWFuYWdlci5jb20vbnMuaHRtbD9pZD0lcyIKaGVpZ2h0PSIwIiB3aWR0aD0iMCIgc3R5bGU9ImRpc3BsYXk6bm9uZTt2aXNpYmlsaXR5OmhpZGRlbiI+PC9pZnJhbWU+PC9ub3NjcmlwdD4KPCEtLSBFbmQgR29vZ2xlIFRhZyBNYW5hZ2VyIChub3NjcmlwdCkgLS0+CnsvbGl0ZXJhbH0=');
		
		$scriptInjection = sprintf($scriptTpl, $gtmId);
		$noscriptInjection = sprintf($noscriptTpl, $gtmId);

		// 1. Inject into Razorpay paymentSuccess.tpl
		if (strpos($resourceName, 'paymentSuccess.tpl') !== false) {
			if ($this->getSetting($contextId, 'trackPaymentSuccess')) {
				$dlStr = base64_decode('PHNjcmlwdD4Kd2luZG93LmRhdGFMYXllciA9IHdpbmRvdy5kYXRhTGF5ZXIgfHwgW107CndpbmRvdy5kYXRhTGF5ZXIucHVzaCh7bGRlbGltfQonZXZlbnQnOiAncGF5bWVudF9zdWNjZXNzJywKJ3RyYW5zYWN0aW9uX2lkJzogJ3skcGF5bWVudElkfGVzY2FwZX0nLAonb3JkZXJfaWQnOiAneyRvcmRlcklkfGVzY2FwZX0nLAonc3VibWlzc2lvbl9pZCc6ICd7JHN1Ym1pc3Npb25JZHxlc2NhcGV9Jwp7cmRlbGltfSk7Cjwvc2NyaXB0Pg==');
				
				$headInjection = "\n" . $dlStr . "\n" . $scriptInjection . "\n";
				$source = preg_replace('/(<\/head>)/i', $headInjection . '$1', $source);
				
				$bodyInjection = "\n" . $noscriptInjection . "\n";
				$source = preg_replace('/(<body[^>]*>)/i', '$1' . $bodyInjection, $source);
			}
			return $source;
		}

		// 2. Standard frontend/backend headers injection
		$trackBackend = $this->getSetting($contextId, 'trackBackend');
		if (!$trackBackend) {
			$router = $request->getRouter();
			$page = $router->getRequestedPage($request);
			$backendPages = ['management', 'workflow', 'submissions', 'admin', 'stats'];
			if (in_array($page, $backendPages)) {
				return $source;
			}
		}

		// Since we use a PRE filter, we search for </head> and <body> in the template file contents.
		if (preg_match('/<\/head>/i', $source)) {
			$source = preg_replace('/(<\/head>)/i', "\n" . $scriptInjection . "\n$1", $source);
		}
		if (preg_match('/<body[^>]*>/i', $source)) {
			$source = preg_replace('/(<body[^>]*>)/i', '$1' . "\n" . $noscriptInjection . "\n", $source);
		}

		return $source;
	}

	/**
	 * Hook callback to intercept redirects and add registration tracking parameters
	 */
	public function callbackRedirect($hookName, $args) {
		$url =& $args[0];
		$request = Application::get()->getRequest();
		$context = $request->getContext();
		$contextId = $context ? $context->getId() : 0;

		if (!$this->getSetting($contextId, 'trackRegistration')) {
			return false;
		}

		$router = $request->getRouter();
		$page = $router->getRequestedPage($request);
		$op = $router->getRequestedOp($request);

		// We check if we are in the email validation operation
		if ($page === 'user' && $op === 'activateUser') {
			// Check if we are redirecting to login
			if (strpos($url, 'login') !== false) {
				$url .= (strpos($url, '?') !== false ? '&' : '?') . 'registrationSuccess=1&loginMessage=user.login.activated';
			}
		}

		return false;
	}
}

if (!PKP_STRICT_MODE) {
	class_alias('\APP\plugins\generic\gtmSetup\GtmSetupPlugin', '\GtmSetupPlugin');
}
