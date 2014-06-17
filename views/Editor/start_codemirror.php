<? HHtml::doctype() ?>
<html>
	<head>
		<? HHtml::metaCharset() ?>
		<title>Editor</title>
		<?php HHtml::cssLink(); HHtml::jsLink(); HHtml::jsI18n(); HHtml::jsLink('/editor_codemirror'); ?>
	</head>
	<body>
		<div id="container">
			<header>
				<div id="logo">Springbok WebManager<br /><b>{$project->name}</b></div>
				<? HMenu::top(array(
					_tC('Home')=>false,
					_t('Workspaces')=>'/workspace',
					_t('Projects')=>'/project',
					_t('Servers')=>'/servers',
					_t('Databases')=>'/database',
					_t('Springbok Core')=>'/core',
					_t('Dev Tools')=>'/devtools',
					_t('PHP Doc')=>'/phpdoc',
					_t('MySQL Doc')=>'/mysqldoc'
				),array('startsWith'=>true)); ?>
				<br class="clear" />
			</header>
			<div id="page" class="absolute ml200">
				<div class="fixed left w200">
					<div id="projectName" class="bold center">{$project->name}</div>
					<div id="fileTree"></div>
				</div>
				<div id="editorParent" class="variable">
				</div>
			</div>
			<footer>...</footer>
		</div>
		<!-- Right Click Menu -->
<ul id="projectMenu" class="contextMenu">
    <li class="insert">{icon folder_add}<a href="#folder_add">{t 'Add new folder'}</a></li>
</ul>
<ul id="folderMenu" class="contextMenu">
    <li class="insert">{icon pageAdd}<a href="#file_add">{t 'Add new file'}</a></li>
    <li class="insert">{icon folder_add}<a href="#folder_add">{t 'Add new folder'}</a></li>
    <li class="edit">{icon folder_edit}<a href="#folder_edit">{t 'Edit folder'}</a></li>
    <li class="delete">{icon folder_delete}<a href="#folder_delete">{t 'Delete folder'}</a></li>
</ul>
		
		<div id="dialog-ask-save-file" title="{t 'Save the current file ?'}">
			<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>{t 'This file has been modified. Save changes ?'}</p>
		</div>
	</body>
	<? HHtml::jsInline('var projectId='.$project->id.',projectType="'.$projectType.'";') ?>
</html>