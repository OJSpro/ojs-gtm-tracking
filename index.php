<?php
/**
 * @file plugins/generic/googleAdsSetup/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_googleAdsSetup
 * @brief Wrapper for Google Ads Setup plugin.
 *
 */

require_once('GtmSetupPlugin.php');

return new \APP\plugins\generic\gtmSetup\GtmSetupPlugin();
