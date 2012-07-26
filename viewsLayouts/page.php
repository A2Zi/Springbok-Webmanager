<?php new AjaxBaseView($layout_title) ?>
<header>
	<div id="logo">Springbok <b>WebManager</b><br />{if CSession::exists('workspace')}<?= CSession::get('workspace')->name ?>{/if}</div>
	<? HMenu::ajaxTop(array(
		_tC('Home')=>false,
		_t('Workspaces')=>'/workspace',
		_t('Projects')=>'/project',
		_t('Servers')=>'/servers',
		_t('Springbok Core')=>'/core',
		_t('Dev Tools')=>'/devtools',
		_t('PHP Doc')=>'/phpdoc',
	),array('startsWith'=>true)); ?>
</header>
{=$layout_content}
<footer>Springbok <b>WebManager</b> | Version du <b><? HHtml::enhanceDate() ?></b> | <? HHtml::powered() ?></footer>