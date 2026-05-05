{**
 * plugins/generic/gtmSetup/templates/settings.tpl
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Settings form for the GTM Setup plugin.
 *}
<script>
	$(function() {
		$('#gtmSetupSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	});
</script>

<form class="pkp_form" id="gtmSetupSettingsForm" method="post" action="{url router=\PKP\core\PKPApplication::ROUTE_COMPONENT component="grid.settings.plugins.SettingsPluginGridHandler" op="manage" verb="settings" plugin=$pluginName category="generic" save=true}">
	{csrf}
	{include file="common/formErrors.tpl"}

	{fbvFormArea id="gtmSettings"}
		{fbvFormSection title="plugins.generic.gtmSetup.manager.settings.gtmId" description="plugins.generic.gtmSetup.manager.settings.gtmIdDescription"}
			{fbvElement type="text" name="gtmId" id="gtmId" value=$gtmId}
		{/fbvFormSection}
		{fbvFormSection list=true}
			{fbvElement type="checkbox" name="trackRegistration" id="trackRegistration" checked=$trackRegistration label="plugins.generic.gtmSetup.manager.settings.trackRegistrationDescription"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons id="gtmSetupSettingsFormSubmit" submitText="common.save" hideCancel=true}
</form>
