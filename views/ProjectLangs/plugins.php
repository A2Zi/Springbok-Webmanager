<?php new AjaxContentView(_t('Project:').' '.$project->name.' -- '.$lang,'project'); ?>
{include ../Project/_viewmenu.php}
<div class="content">
	<ul class="compact">
	{f $translations as $pluginName=>$pluginStrings}
		<li>{link $pluginName,'#',array('onclick'=>'$(this).parent().find("> div").slideToggle();return false;')}
		<div class="hidden">
			<?php $form=HForm::create(NULL,array('id'=>'FormLangsPlugin_'.$pluginName,'action'=>'#')); $i=0; ?>
			<ul>
			{f $pluginStrings as $s=>$t}
				<li>
					{=$form->input('data['.$s.']',array('label'=>$s,'id'=>'data_'.$i++,'value'=>$t))}
				</li>
			{/f}
			</ul>
			{=$form->end()}
		</div>
	{/f}
	</ul>
</div>
{jsReady}
{f $translations as $pluginName=>$pluginStrings}
	$("#FormLangsPlugin_{$pluginName}").ajaxForm(baseUrl+"projectLangs/pluginSave/{=$project->id}/{$lang}/{$pluginName}");
{/f}
{/jsReady}