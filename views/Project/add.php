<?php $v=new AjaxContentView(_t('Add project'),'project') ?>

{=$form=Project::Form()}
{=$form->fieldsetStart(_t('Add a new project'))}
{=$form->input('name')}
{=$form->input('path')->container()->after(' '._t('Without the trailing slash'))}
{=$form->end()}