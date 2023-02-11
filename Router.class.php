<?php
/** op-unit-router:/Router.class.php
 *
 * @created   2019-02-23 Separate from NewWorld.
 * @version   1.0
 * @package   op-unit-router
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */

/** namespace
 *
 * @created   2018-04-13  OP\UNIT\NEWWORLD
 * @updated   2019-02-23  OP\UNIT
 */
namespace OP\UNIT;

/** Use
 *
 */
use OP\OP_CI;
use OP\OP_CORE;
use OP\OP_UNIT;
use OP\IF_UNIT;
use OP\Env;
use function OP\RootPath;
use function OP\ConvertPath;
use OP\UNIT\Router\ROUTER_2018;

/** Include
 *
 */
require(__DIR__.'/ROUTER_2018.trait.php');

/** Router
 *
 * @created   2009 NewWrold was born in Kozhikode.
 * @created   2015-01-30  Born at NewWorld.
 * @updated   2016-11-26  Separate to op-unit-newworld from op-core class.
 * @updated   2019-02-23  Separate to op-unit-router from op-unit-newworld.
 * @version   1.0
 * @package   op-unit-router
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */
class Router implements IF_UNIT
{
	/** trait.
	 *
	 */
	use OP_CORE, OP_UNIT, OP_CI, ROUTER_2018;

	/** Use for route table's associative array key name.
	 *
	 * @var string
	 */
	const _ARGS_ = 'args';

	/** Use for route table's associative array key name.
	 *
	 * @var string
	 */
	const _END_POINT_ = 'end-point';

	/** Route table.
	 *
	 * @var array
	 */
	private $_route;

	/** Init route table.
	 *
	 * <pre>
	 * 1. Search end-point by request uri.
	 * 2. Generate smart-url's arguments by request uri.
	 *
	 * Structure:
	 * {
	 *   "args" : [],
	 *   "end-point" : "/foo/bar/index.php"
	 * }
	 * </pre>
	 */
	function __construct()
	{
		//	...
		$this->_Init();
		return;

		//	...
		require_once('function/CalcRoute2020.php');
		$this->_route = \OP\UNIT\ROUTER\Calculate();
		return;

		//	...
		if(!Env::isHttp() ){
			return;
		};

		//	...
		$config = Env::Get('router');

		//	...
		$this->_route = [];
		$this->_route[self::_ARGS_] = [];
		$this->_route[self::_END_POINT_] = null;

		//	...
		$app_root = RootPath()['app'];

		//	Separate of URL Query.
		if( $pos   = strpos($_SERVER['REQUEST_URI'], '?') ){
			$uri   = substr($_SERVER['REQUEST_URI'], 0, $pos);
			/*
			 $query = substr($_SERVER['REQUEST_URI'], $pos +1);
			 var_dump($pos, $uri, $query);
			 */
		}else{
			$uri   = $_SERVER['REQUEST_URI'];
		};

		//	Generate real full path.
		$full_path = $_SERVER['DOCUMENT_ROOT'].$uri;

		/*
		$full_path = $_SERVER['DOCUMENT_ROOT'].$_SERVER['REQUEST_URI'];

		//	Separate url query.
		if( $pos = strpos($full_path, '?') ){
			//	Separate url query.
			$full_path = substr($full_path, 0, $pos);
		}
		*/

		/*
		//	HTML path through.
		if( $config['html-path-through'] ?? null ){
			//	Get extension.
			$extension = substr($full_path, strrpos($full_path, '.')+1);

			//	In case of html.
			if( $extension === 'html' ){
				if( file_exists($full_path) ){
					$this->_route[self::_END_POINT_] = $full_path;
					return;
				}
			}
		}
		*/

		//	...
		if( file_exists($full_path) ){
			//	Get extension.
			$extension = substr($full_path, strrpos($full_path, '.')+1);

			//	...
			switch( $extension ){
				case 'html':
					//	HTML path through.
					$io = $config['html-path-through'] ?? true;
					break;

				case 'js':
					$io = true;
					Env::Mime('text/javascript');
					break;

				case 'css':
					$io = true;
					Env::Mime('text/css');
					break;
			};

			//	...
			if( $io ?? null ){
				$this->_route[self::_END_POINT_] = $full_path;
				return;
			};
		};

		//	Remove application root: /www/htdocs/api/foo/bar/ --> api/foo/bar/
		$uri = str_replace($app_root, '', $full_path);

		//	Remove slash from tail: api/foo/bar/ --> api/foo/bar
		$uri  = rtrim($uri, '/');

		//	/foo/bar --> ['foo','bar']
		$dirs = explode('/', $uri);

		//	...
		$this->__DebugSet(__FUNCTION__, true);
		$this->__DebugSet(__FUNCTION__, $dirs);

		//	Globalization.
		if( ($g11n = $config['g11n'] ?? null) and $g11n['execute'] ){
			//	...
			if( $dirs[0] == 'webpack' ){
				//	...
				$has_locale = true;
			}else
			if( $has_locale = strpos($dirs[0], ':') ){
				//	Has language code.
				$this->_route['g11n'] = strtolower(array_shift($dirs));
			};

			//	...
			if(!$has_locale ){
				//	...
				if( $pos = strpos($_SERVER['REQUEST_URI'],'?') ){
					$que = substr($_SERVER['REQUEST_URI'], $pos);
				};

				//	...
				$url = "app:/{$g11n['default']}/".join('/',$dirs) . ($que ?? null);

				//	...
				$this->Unit('Http')->Location($url, 307);
			};
		};

		//	...
		$dir = null;

		//	...
		do{
			//	['foo','bar'] --> foo/bar//index.php --> foo/bar/index.php
			$path = trim(join(DIRECTORY_SEPARATOR, $dirs).DIRECTORY_SEPARATOR.'index.php', DIRECTORY_SEPARATOR);

			//	...
			if( isset($dir) ){
				array_unshift($this->_route[self::_ARGS_], \OP\Encode($dir));
			}

			//	...
			$full_path = $app_root.$path;

			//	...
			if( file_exists($full_path) ){
				$this->_route[self::_END_POINT_] = $full_path;
				break;
			}

			//	...
		}while( false !== $dir = array_pop($dirs) );
	}

	/** EndPoint
	 *
	 * @created  2019-02-23
	 * @return   string
	 */
	function EndPoint()
	{
		return $this->_route[self::_END_POINT_];
	}

	/** Args
	 *
	 * @created  2019-02-23
	 * @return   array
	 */
	function Args()
	{
		return $this->_route[self::_ARGS_];
	}

	/** g11n is Globalization.
	 *
	 *  Globalization is not Multilingalization.
	 *  World Wide Web is connecting of world wide people.
	 *  People from all over the world visit your site.
	 *
	 *  Internationalization is not Multilingalization.
	 *  Multilingualization is one manifestation of that policy.
	 *
	 *  Localization is local area unique settings.
	 *  For example currency, tax, holiday.
	 *
	 * @creation 2019-03-19
	 * @return   array
	 */
	function G11n()
	{
		return $this->_route['g11n'] ?? null;
	}

	/** Get route table.
	 *
	 * @created  2022-10-05
	 * @return   array
	 */
	function Table()
	{
		return $this->_route;
	}
}
