<?php
/**
 * unit-router:/calculator.php
 *
 * Calculate of end point route calculator.
 *
 * @created   2020-01-03   Separate from ROUTER.trait.php
 * @version   1.0
 * @package   unit-router
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */

/** namespace
 *
 * @created   2020-01-03
 */
namespace OP\UNIT\ROUTER;

/** use
 *
 * @created   2020-01-03
 */
use function OP\RootPath;

/** Calculate route from URL.
 *
 * <pre>
 * $condition = [
 *   'entry_point'       => \OP\RootPath('app'),
 *   'url'               => $_SERVER['REQUEST_URI'],
 *   'file_name'         => 'index.php',
 *   'is_separate_query' => true, // $_SERVER['REQUEST_URI'] --> $_SERVER['REQUEST_URL'], $_SERVER['REQUEST_URL_QUERY']
 * ]
 * </pre>
 *
 * @created   2020-01-03
 * @param     array        ['root','url','file_name','is_separate_query']
 * @return    array        ['end-point','args']
 */
function Calculate(array $condition=[]):array
{
	//	Directory of entry point.
	$entry_point = $condition['entry_point'] ?? RootPath('app');

	//	End point file name.
	$file_name = $condition['file_name'] ?? 'index.php';

	//	Separate URL Query.
	list($_SERVER['REQUEST_URL'], $_SERVER['REQUEST_URL_QUERY']) = explode('?', $_SERVER['REQUEST_URI'].'?');

	//	Generate not real full path.
	$full_not_real_path = rtrim($_SERVER['DOCUMENT_ROOT'],'/') . ($condition['url'] ?? $_SERVER['REQUEST_URL']);

	//	html-pass-through
	if( file_exists($full_not_real_path) and !is_dir($full_not_real_path) ){
		return ['end-point' => $full_not_real_path, 'args' => []];
	};

	//	Generate path of under of the app root.
	if( strpos($full_not_real_path, $entry_point) === 0 ){
		$path = substr($full_not_real_path, strlen($entry_point));
		$path = trim($path, DIRECTORY_SEPARATOR);
		$dirs = explode(DIRECTORY_SEPARATOR, $path);
	}else{
		throw new \Exception("Calculation of the entry point is failed. ($entry_point)");
	}

	//	Search end point from tail path.
	$arg  = null;
	$args = [];
	do{
		//	SmartURL argument.
		if( $arg ){
			array_unshift($args, $arg);
		}

		//	Generate end point path.
		$path      = $dirs ? join(DIRECTORY_SEPARATOR, $dirs) . DIRECTORY_SEPARATOR: null;
		$end_point = $entry_point . $path . $file_name;

		//	Check if exists
		if( file_exists($end_point) ){
			break;
		}

	}while( ($arg = array_pop($dirs)) !== false );

	//	Unset.
	if(!($condition['is_separate_query'] ?? true) ){
		unset($_SERVER['REQUEST_URL']);
		unset($_SERVER['REQUEST_URL_QUERY']);
	}

	//	Encode.
	$args = \OP\Encode($args);

	//	Result.
	return ['end-point' => $end_point, 'args' => $args];
}
