<?php
/** op-unit-router:/ROUTER_2018.trait.php
 *
 * @created   2022-10-07 Separated from Router.class.php
 * @version   1.0
 * @package   op-unit-router
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */

/** namespace
 *
 */
namespace OP\UNIT\Router;

/** use
 *
 */
use OP\Env;
use function OP\RootPath;
use function OP\ConvertPath;

/** ROUTER_2018
 *
 */
trait ROUTER_2018 {

	/** Routing 2018
	 *
	 */
	private function _Init()
	{
		//	...
		$config = Env::Get('router');

		//	...
		$this->_route = [];
		$this->_route[self::_ARGS_] = [];
		$this->_route[self::_END_POINT_] = null;

		//	...
		$app_root = RootPath('app');

		//	Generate real full path.
		if( Env::isHttp() ){

			//	Separate of URL Query.
			if( $pos   = strpos($_SERVER['REQUEST_URI'], '?') ){
				$uri   = substr($_SERVER['REQUEST_URI'], 0, $pos);
			}else{
				$uri   = $_SERVER['REQUEST_URI'];
			};

			//	HTTP
			$full_path = $_SERVER['DOCUMENT_ROOT'].$uri;

		}else{
			//	Shell
			$path = $_SERVER['argv'][1] ?? '';
			$full_path = $app_root . $path;
		};

		//	HTML pass through.
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
}