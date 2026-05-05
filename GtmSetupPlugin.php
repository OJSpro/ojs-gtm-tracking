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
	 * Hook callback to inject GTM script and register body filter
	 */
	public function callbackDisplay($hookName, $args) {
		$templateMgr = $args[0];
		$request = Application::get()->getRequest();
		$context = $request->getContext();
		$contextId = $context ? $context->getId() : 0;

		$gtmId = $this->getSetting($contextId, 'gtmId');
		if (!empty($gtmId)) {
			// Strip any accidental <script> tags the user might have pasted
			$gtmId = strip_tags($gtmId);
			$gtmId = preg_replace('/[^a-zA-Z0-9\-]/', '', $gtmId); // Clean ID

			$gtmScript = "<!-- Google Tag Manager -->\n" .
				"<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':\n" .
				"new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],\n" .
				"j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=\n" .
				"'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);\n" .
				"})(window,document,'script','dataLayer','" . htmlspecialchars($gtmId, ENT_QUOTES, 'UTF-8') . "');</script>\n" .
				"<!-- End Google Tag Manager -->";

			$templateMgr->addHeader('gtmSetupScript', $gtmScript);
			$templateMgr->registerFilter('output', [$this, 'callbackNoscriptFilter']);
		}

		return false;
	}

	/**
	 * Smarty filter to inject noscript tag immediately after <body>
	 */
	public function callbackNoscriptFilter($output, $smarty) {
		$request = Application::get()->getRequest();
		$context = $request->getContext();
		$contextId = $context ? $context->getId() : 0;
		
		$gtmId = $this->getSetting($contextId, 'gtmId');
		if (!empty($gtmId)) {
			$gtmId = strip_tags($gtmId);
			$gtmId = preg_replace('/[^a-zA-Z0-9\-]/', '', $gtmId); // Clean ID
			
			$gtmNoscript = "<!-- Google Tag Manager (noscript) -->\n" .
				"<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id=" . htmlspecialchars($gtmId, ENT_QUOTES, 'UTF-8') . "\"\n" .
				"height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>\n" .
				"<!-- End Google Tag Manager (noscript) -->";

			// Search for <body ...> and append after it
			$output = preg_replace('/(<body[^>]*>)/i', '$1' . "\n" . $gtmNoscript, $output);
		}
		return $output;
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
