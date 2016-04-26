<?php
class TempTestController extends AController{
	/** */
	static function index(){
		require CLIBS.'PHP-Parser/bootstrap.php';
		ini_set('xdebug.max_nesting_level', 2000);
		//$code=file_get_contents(__FILE__);
		$code='<?php /* test */ $tab=[2=>"test2"];';
		$parser     = new PHPParser_Parser(new PHPParser_Lexer);
		$nodeDumper = new PHPParser_NodeDumper;
		try {
		    $stmts = $parser->parse($code);
			echo '<pre>' . htmlspecialchars($nodeDumper->dump($stmts)) . '</pre>';
		} catch (PHPParser_Error $e) {
		    echo 'Parse Error: ', $e->getMessage();
		}
	}
}