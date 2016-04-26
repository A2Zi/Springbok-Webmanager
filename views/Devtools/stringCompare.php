<?php $v=new AjaxContentView('String compare') ?>

<?php $form=HForm::create(NULL); ?>
<? $form->input('string1',array('label'=>false)) ?>
<? $form->input('string2',array('label'=>false)) ?>
<? $form->end('Ok') ?>

{if!e $_POST['string1'] && $_POST['string2']}
	<div class="mt20">
		<?php $string1=$_POST['string1']; $string2=$_POST['string2']; ?>
		<ul class="spaced">
			<li>levenshtein= <?= levenshtein($string1,$string2) ?></li>
			<li>jaroWinkler= <?= HString::jaroWinkler($string1,$string2) ?></li>
			<li>dice= <?= HString::dice($string1,$string2) ?></li>
		</ul>
		<h5>Normalized</h5>
		<?php $normalized1=UString::normalize($string1); $normalized2=UString::normalize($string2); ?>
		<div>string1={$normalized1}, string2={$normalized2}</div>
		<ul class="spaced">
			<li>levenshtein= <?= levenshtein($normalized1,$normalized2) ?></li>
			<li>jaroWinkler= <?= HString::jaroWinkler($normalized1,$normalized2) ?></li>
			<li>dice= <?= HString::dice($normalized1,$normalized2) ?></li>
		</ul>
	</div>
{/if}
