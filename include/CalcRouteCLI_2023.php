<?php
/** op-unit-router:/include/CalcRouteCLI_2023.php
 *
 * Calculate of the End-Point at CLI
 *
 * @created		2023-12-28
 * @version		1.0
 * @package		op-unit-router
 * @author		Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright	Tomoaki Nagahara All right reserved.
 */

//	...
$root = $_SERVER['PWD'].'/';
$path = $_SERVER['argv'][1] ?? 'index.php';
$file = $root . $path;

//	...
return $file;
