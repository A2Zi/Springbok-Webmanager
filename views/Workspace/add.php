<?php $v=new AjaxContentView(_t('Add a workspace'),'workspaces') ?>
{=$form=Workspace::Form()}
{=$form->fieldsetStart(_t('Add a new workspace'))}
{=$form->input('name')}
{=$form->input('projects_dir')->container()->after(' '._t('With the trailing slash'))}
{=$form->input('db_name')}
{=$form->end()}