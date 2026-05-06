<?php

/**
 * @file plugins/generic/gtmSetup/GtmSetupSettingsForm.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GtmSetupSettingsForm
 * @brief Form for journal managers to modify GTM Setup plugin settings
 */

namespace APP\plugins\generic\gtmSetup;

use PKP\form\Form;

class GtmSetupSettingsForm extends Form {
	/** @var int Context ID */
	public $_contextId;

	/** @var GtmSetupPlugin Plugin */
	public $_plugin;

	/**
	 * Constructor
	 * @param $plugin GtmSetupPlugin
	 * @param $contextId int
	 */
	public function __construct($plugin, $contextId) {
		$this->_plugin = $plugin;
		$this->_contextId = $contextId;

		parent::__construct($plugin->getTemplateResource('settings.tpl'));

		$this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
		$this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
	}

	/**
	 * @copydoc Form::initData()
	 */
	public function initData() {
		$this->_data = [
			'gtmId' => $this->_plugin->getSetting($this->_contextId, 'gtmId'),
			'trackRegistration' => $this->_plugin->getSetting($this->_contextId, 'trackRegistration'),
			'trackBackend' => $this->_plugin->getSetting($this->_contextId, 'trackBackend'),
			'trackPaymentSuccess' => $this->_plugin->getSetting($this->_contextId, 'trackPaymentSuccess'),
		];
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	public function readInputData() {
		$this->readUserVars(['gtmId', 'trackRegistration', 'trackBackend', 'trackPaymentSuccess']);
	}

	/**
	 * @copydoc Form::fetch()
	 *
	 * @param null|mixed $template
	 */
	public function fetch($request, $template = null, $display = false) {
		$templateMgr = \APP\template\TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->_plugin->getName());
		return parent::fetch($request, $template, $display);
	}

	/**
	 * @copydoc Form::execute()
	 */
	public function execute(...$functionArgs) {
		$gtmId = trim($this->getData('gtmId'));
		
		$this->_plugin->updateSetting($this->_contextId, 'gtmId', $gtmId);
		$this->_plugin->updateSetting($this->_contextId, 'trackRegistration', (bool) $this->getData('trackRegistration'));
		$this->_plugin->updateSetting($this->_contextId, 'trackBackend', (bool) $this->getData('trackBackend'));
		$this->_plugin->updateSetting($this->_contextId, 'trackPaymentSuccess', (bool) $this->getData('trackPaymentSuccess'));

		parent::execute(...$functionArgs);
	}
}
