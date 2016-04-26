<?php new AjaxContentView(_t('Project:').' '.$project->name,'project'); ?>
{include _viewmenu.php}
<div class="content">
	{ife $deployments} {t 'No deployments.'}
	{else}
	<div><h2><? $count=count($deployments)?> <?= _t_p('deployment',$count) ?></h2>
		<ul>
		{f $deployments as $deployment}
			<li>{link $deployment->server->name.' - '.$deployment->server->projects_dir.$deployment->path,'/projectDeployment/view/'.$deployment->id}
				 {iconLink 'delete','Supprimer','/projectDeployment/del/'.$deployment->id,array('confirm'=>_tC('Are you sure ?'))}
				 {iconLink 'edit','Modifier','/projectDeployment/edit/'.$deployment->id}
			</li>
		{/f}
		</ul>
	</div>
	{/if}
	<div><?php $form=HForm::create('Deployment',array('action'=>'/projectDeployment/add'));
		echo $form->hidden('project_id',$project->id);
		echo $form->select('server_id',Server::findListName(),array(),false);
		echo $form->input('path',array('label'=>false),false);
		$form->end(_t('Add a new deployment'));
	?></div>
</div>
