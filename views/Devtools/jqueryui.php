<?php $v=new AjaxContentView('JQuery UI') ?>

<div class="ui-widget">
	<label for="tags">Tags: </label>
	<input id="tags">
</div>

<p>Date: <input id="datepicker" type="text"></p>


<?php HHtml::jsInlineStart() ?>
$(function() {
		var availableTags = ["ActionScript","AppleScript","Asp","BASIC","C","C++","Clojure","COBOL","ColdFusion","Erlang","Fortran","Groovy","Haskell","Java","JavaScript","Lisp","Perl","PHP","Python","Ruby","Scala","Scheme"];
		$( "#tags" ).autocomplete({source: availableTags});
		$( "#datepicker" ).datepicker();
	});
<? HHtml::jsInlineEnd() ?>

