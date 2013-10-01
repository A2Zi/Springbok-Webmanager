<?php
Controller::$defaultLayout='dev_tools';
class TestsController extends Controller{
	/** */
	static function index(){
		$tests=array_diff(get_class_methods(__CLASS__),array('index','test','push'),get_class_methods('Controller'));
		mset($tests);
		render();
	}
	
	/** */
	static function test($name){
		mset($name);
		set('res',call_user_func(array('self',$name)));
		render();
	}
	
	/** */
	static function jsToString(){
		echo HHtml::doctype();
		echo '<html><body>';
		echo HHtml::jsInline('var test={ toString:function(){ return "toString works !"; } }; alert(test);');
		echo '</body></html>';
	}
	
	/** */
	static function rand(){
		/*$array=array('a','b');
		debugVar(array_rand($array,2));*/
		debugVar(UGenerator::randomCode(12));
	}
	
	/** */
	static function httpClient(){
		/*$array=array('a','b');
		debugVar(array_rand($array,2));*/
		$httpClient=new CHttpClient();
		$httpClient->get('http://localhost/springbok');
		debugVar($httpClient->getLastUrl());
	}
	
	/** */
	static function testpush(){
		/*self::allowFlush();
		$i=10;
		while($i-->0){
			self::push($i);
			sleep(1);
		}*/
	}
	/** */
	static function testmysqllongquery(){
		$db=EMCategory::$__modelDb;
		set_time_limit(2); // 2 secondes max pour exécuter la requete
		$db->doUpdate('SELECT SLEEP(90)');
	}
	
	/** */
	static function UFileApi(){
		echo $test;
		unlink('/var/unexistant_file');
		UFile::rm('/var/unexistant_file');
		/*debug*/(UFile::getContents('/etc/hosts'));
		/*debugVar*/(UFile::getContents('/var/unexistant_file'));
	}
	
	private static function getContents(){
	 return UProfiling::compare(60000,function(){
        	return file_get_contents('/etc/hosts');
        },function(){
        	return UFile::getContents('/etc/hosts');
        });
	}
	
	
    private static function shuffle(){
        return UProfiling::compare(30000,function(){
        	$str=str_shuffle('abcdefghijklmnopqrstuvwxyz');
            return str_split($str);
        },function(){
        	$array=array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','p','q','r','s','t','u','v','x','y','z');
            return shuffle($array);
        });
    }
	
    
	private static function defaultOptions(){
		return array(
		UProfiling::compare(9000,function(){
			$options=array();
            return $options+array('startsWith'=>false);
        },function(){
			$options=array('startsWith'=>false);
            return $options+array('startsWith'=>false);
        },function(){
        	$options=array();
			if(!isset($options['startsWith']))$options['startsWith']=false;
            return $options;
        },function(){
			$options=array('startsWith'=>false);
			if(!isset($options['startsWith']))$options['startsWith']=false;
            return $options;
        })
		,
		UProfiling::compare(9000,function(){
			$options=array();
            return $options+array('lioptions'=>array(),'linkoptions'=>array(),'startsWith'=>false);
        },function(){
			$options=array('startsWith'=>false);
            return $options+array('lioptions'=>array(),'linkoptions'=>array(),'startsWith'=>false);
        },function(){
        	$options=array();
			if(!isset($options['startsWith']))$options['startsWith']=false;
			if(!isset($options['lioptions']))$options['lioptions']=array();
			if(!isset($options['linkoptions']))$options['linkoptions']=array();
            return $options;
        },function(){
			$options=array('startsWith'=>false);
			if(!isset($options['startsWith']))$options['startsWith']=false;
			if(!isset($options['lioptions']))$options['lioptions']=array();
			if(!isset($options['linkoptions']))$options['linkoptions']=array();
            return $options;
        })
		);
	}
	
    private static function md5(){
        return UProfiling::compare(3000,function(){
            return md5_file('/home/christophe/TODO');
        },function(){
            return UExec::exec('md5sum /home/christophe/TODO');
        });
    }
	
	private static function botlist(){
		return UProfiling::compare(7000,function(){
			$botlist=array(
			'bot',
			//'Googlebot',
			'Google Web Preview', // Google - www.google.com
			//'msnbot',
			'Yahoo',
			//'VoilaBot',
			//'WebCrawler',
			'crawler',
			
			'Scooter', // Alta Vita - www.altavista.com
			//'Ask Jeeves\/Teoma', // Ask - www.ask.com & Teoma - ww.teoma.com
			'Lycos_Spider_\(T-Rex\)', // Lycos - www.lycos.com
			'Slurp', // Inktomi - www.inktomi.com
			'HenryTheMiragorobot', // Mirago - www.mirago.com
			'FAST\-WebCrawler', // AlltheWeb - www.alltheweb.com
			'W3C_Validator',
			
			'Teoma', 'alexa', 'froogle', 'inktomi',
			'looksmart', 'URL_Spider_SQL', 'Firefly', 'NationalDirectory',
			'Ask Jeeves', 'TECNOSEEK', 'InfoSeek', 
			'www.galaxy.com','appie', 'FAST', 'WebBug', 'Spade', 'ZyBorg', 'rabaz',
			'Baiduspider', 'Feedfetcher-Google', 'TechnoratiSnoop',
			'Mediapartners-Google', 'Sogou web spider',
			'Butterfly','Twitturls','Me.dium','Twiceler'
		);
			foreach($botlist as $bot) if(stripos($_SERVER['HTTP_USER_AGENT'], $bot) !== false) return true;
		},function(){
			return (bool)preg_match('/'./* EVAL implode('|',array(
			'bot',
			//'Googlebot',
			'Google Web Preview', // Google - www.google.com
			//'msnbot',
			'Yahoo',
			//'VoilaBot',
			//'WebCrawler',
			'crawler',
			
			'Scooter', // Alta Vita - www.altavista.com
			//'Ask Jeeves\/Teoma', // Ask - www.ask.com & Teoma - ww.teoma.com
			'Lycos_Spider_\(T-Rex\)', // Lycos - www.lycos.com
			'Slurp', // Inktomi - www.inktomi.com
			'HenryTheMiragorobot', // Mirago - www.mirago.com
			'FAST\-WebCrawler', // AlltheWeb - www.alltheweb.com
			'W3C_Validator',
			
			'Teoma', 'alexa', 'froogle', 'inktomi',
			'looksmart', 'URL_Spider_SQL', 'Firefly', 'NationalDirectory',
			'Ask Jeeves', 'TECNOSEEK', 'InfoSeek', 
			'www.galaxy.com','appie', 'FAST', 'WebBug', 'Spade', 'ZyBorg', 'rabaz',
			'Baiduspider', 'Feedfetcher-Google', 'TechnoratiSnoop',
			'Mediapartners-Google', 'Sogou web spider',
			'Butterfly','Twitturls','Me.dium','Twiceler'
		)) /EVAL */''.'/i',$_SERVER['HTTP_USER_AGENT']);
			return false;
		});
	}
	
	private static function date_vs_strftime(){
		return UProfiling::compare(70000,function(){
			return strftime('%Y-%m-%d %H:%M:%S');
		},function(){
			return date('Y-m-d H:i:s');
		},function(){
			return strftime('%F %T');
		},function(){
			$dt=new DateTime();
			return $dt->format('Y-m-d H:i:s');
		});
	}
	
	private static function gfc_vs_incl(){
		$filename=dirname(CORE).'/env.php';
		return UProfiling::compare(90000,function() use(&$filename){
			return file_get_contents($filename);
		},function() use(&$filename){
			return include $filename;
		});
	}
	
	private static function copy_array(){
		$array=array(1=>'val 1','2'=>'val 2','3'=>'val 3',4=>'val 4',5=>'val 5','6'=>'val 6');
		return UProfiling::compare(70000,function() use(&$array){
			$data=array();
			foreach($array as $k=>$v) $data[$k]=$v;
			return $data;
		},function() use(&$array){
			return array_map(array('TestsController','return_value'),$array);
		},function() use(&$array){
			return array_map(function($v){return $v;},$array);
		},function() use(&$array){
			reset($array);
			$data=array();
			while(($v=current($array))!==false){
				$data[key($array)]=$v;
				next($array);
			}
			return $data;
		},function() use(&$array){
			reset($array);
			$data=array();
			$v=current($array);
			while($v!==false){
				$data[key($array)]=$v;
				$v=next($array);
			}
			return $data;
		});
	}
	
	public static function return_value($v){return $v;}
	
	
	private static function logs(){
		$exception=new Exception;
		$message=$exception->__toString();
		if(isset($_SERVER['REQUEST_URI']))
			$message.=' REQUEST_URI='.$_SERVER['REQUEST_URI'];
		return UProfiling::compare(1000,function() use(&$message){
			//CLogger::
		},function() use(&$message){
			
		});
	}
	
	public static function comp_string(){
		return array(
			'levenshtein'=>levenshtein($_GET['1'],$_GET['2']),
			'dice'=>HString::dice($_GET['1'],$_GET['2']),
			'jaroWinkler'=>HString::jaroWinkler($_GET['1'],$_GET['2'])
		);
	}
	
	public static function form(){
		return UProfiling::compare(10000,function(){
			ob_start();
			$form=HForm::create('Project',array('action','/projects/add'));
			echo $form->input('name');
			$form->end(true,array(),array());
			ob_end_clean();
		},function(){
			ob_start();
			echo $form=Project::Form()->action('/projects/add');
			echo $form->input('name');
			echo $form->end();
			ob_end_clean();
		});
		
	}
	
	
    private static function list_vs_directAccess_2(){
        return UProfiling::compare(999999,function(){
        	$array=array('1111111111','2222222222');
			return array($array[0],$array[1]);
        },function(){
        	$array=array('1111111111','2222222222');
			return array(&$array[0],&$array[1]);
        },function(){
        	$array=array('1111111111','2222222222');
			list($v1,$v2)=$array;
            return array($v1,$v2);
        },function(){
        	$array=array('1111111111','2222222222');
			$v1=$array[0];
			$v2=$array[1];
	        return array($v1,$v2);
        },function(){
        	$array=array('1111111111','2222222222');
			$v1=&$array[0];
			$v2=&$array[1];
	        return array($v1,$v2);
        });
    }
    private static function list_vs_directAccess_3(){
        return UProfiling::compare(999999,function(){
        	$array=array('1111111111','2222222222','3333333333');
			return array($array[0],$array[1],$array[2]);
        },function(){
        	$array=array('1111111111','2222222222','3333333333');
			return array(&$array[0],&$array[1],&$array[2]);
        },function(){
        	$array=array('1111111111','2222222222','3333333333');
			list($v1,$v2,$v3)=$array;
            return array($v1,$v2,$v3);
        },function(){
        	$array=array('1111111111','2222222222','3333333333');
			$v1=$array[0];
			$v2=$array[1];
			$v3=$array[2];
            return array($v1,$v2,$v3);
        },function(){
        	$array=array('1111111111','2222222222','3333333333');
			$v1=&$array[0];
			$v2=&$array[1];
			$v3=&$array[2];
            return array($v1,$v2,$v3);
        });
    }
	
	
    private static function list_vs_directAccess_3_multiple(){
        return UProfiling::compare(999999,function(){
        	$array=array('1111111111','2222222222','3333333333');
			return array($array[0],$array[1],$array[2],$array[0],$array[1],$array[2],$array[0],$array[1],$array[2]);
        },function(){
        	$array=array('1111111111','2222222222','3333333333');
			return array(&$array[0],&$array[1],&$array[2],&$array[0],&$array[1],&$array[2],&$array[0],&$array[1],&$array[2]);
        },function(){
        	$array=array('1111111111','2222222222','3333333333');
			list($v1,$v2,$v3)=$array;
            return array($v1,$v2,$v3,$v1,$v2,$v3,$v1,$v2,$v3);
        },function(){
        	$array=array('1111111111','2222222222','3333333333');
			$v1=$array[0];
			$v2=$array[1];
			$v3=$array[2];
            return array($v1,$v2,$v3,$v1,$v2,$v3,$v1,$v2,$v3);
        },function(){
        	$array=array('1111111111','2222222222','3333333333');
			$v1=&$array[0];
			$v2=&$array[1];
			$v3=&$array[2];
            return array($v1,$v2,$v3,$v1,$v2,$v3,$v1,$v2,$v3);
        });
    }


    private static function var_vs_ref(){
        return UProfiling::compare(999999,function(){
        	$var1=new HElementForm; $var2=new HElementForm;
			$v1=&$var1; $v2=&$var2;
			return array($v1,$v2);
        },function(){
        	$var1=new HElementForm; $var2=new HElementForm;
			$v1=$var1; $v2=$var2;
			return array($v1,$v2);
        },function(){
        	$var1=new HElementForm; $var2=new HElementForm;
			list($v1,$v2)=array($var1,$var2);
			return array($v1,$v2);
        });
    }
	
	
    private static function h_vs_h2(){
        return UProfiling::compare(999999,function(){
        	$var1='test';
			return h($var1);
        },function(){
        	$var1='test';
			return h2($var1);
        });
    }
	
	private static function h_vs_h2_2(){
        $var1=str_repeat('test',99999);
		return UProfiling::compare(9999,function() use(&$var1){
        	return h($var1);
        },function() use($var1){
        	return h($var1);
        },function() use($var1){
        	return h2($var1);
        });
    }
	
	
	private static function assign_test(){
		return UProfiling::compare(999999,function(){
			$a=10;$b=10;$c=10;$d=10;$e=10;$f=10;
			return $a;
        },function(){
			$a=$b=$c=$d=$e=$f=10;
			return $a;
        },function(){
			$a=10;$b=10;$c=10;$d=10;$e=10;$f=10;
			return $a+$b;
        },function(){
			$a=$b=$c=$d=$e=$f=10;
			return $a+$b;
        });
    }
	
	private static function sprintf(){
		return UProfiling::compare(999999,function(){
			return sprintf('/^[%s]+|[%s]+$/','-','-');
       },function(){
			return '/^['.'-'.']+|['.'-'.']+$/';
        });
	}
	
	private static function compact(){
		return UProfiling::compare(999999,function(){
			$v1=1;$v2=2;
			return compact('v1','v2');
       },function(){
			$v1=1;$v2=2;
			return array('v1'=>$v1,'v2'=>$v2);
        });
	}
}
