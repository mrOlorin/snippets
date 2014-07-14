<?php

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__ . DS);

$src_dir = 'src_dir';
$dst_dir = 'dst_dir';
$result_dir = 'result';
$skip = ['files', 'and', 'dirs', 'to', 'skip'];

$src_dir = ROOT . $src_dir;
$dst_dir = ROOT . $dst_dir;
$result_dir = ROOT . $result_dir;

if (!is_dir($result_dir)) {
	mkdir($result_dir);
}
$stat = ['checked' => 0, 'copied' => 0];
function dir_walk($path, $skip, $cbCondition, $cbAction) {
	foreach (new DirectoryIterator($path) as $fileInfo) {
		if ($fileInfo->isDot() || in_array($fileInfo->getFilename(), $skip)) {
			continue;
		} elseif ($cbCondition($fileInfo)) {
			$cbAction($fileInfo);
		} elseif ($fileInfo->isDir()) {
			dir_walk($path . DS . $fileInfo->getFilename(), $skip, $cbCondition, $cbAction);
		}
	}
}

$checkFile = function($fileInfo) use ($src_dir, $dst_dir) {
	global $stat;
	$stat['checked']++;
	if ($fileInfo->isDir()) {
		return false;
	}
	$dst_dir_file = str_ireplace($dst_dir, $src_dir, $fileInfo->getRealPath());
	if (!file_exists($dst_dir_file)) {
		return true;
	}
	return md5_file($fileInfo->getRealPath()) !== md5_file($dst_dir_file);
};

$copyFile = function($fileInfo) use ($dst_dir, $result_dir) {
	if (!$fileInfo->isReadable()) {
		throw new Exception("Can't move:\n\t" + $fileInfo->getRealPath());
	} else {
		$to_file = str_ireplace($dst_dir, $result_dir, $fileInfo->getRealPath());
		$to_dir = substr($to_file, 0, -(strlen($fileInfo->getBaseName()) + 1));
		if (!is_dir($to_dir)) {
			mkdir($to_dir, 0777, true);
		}
		copy($fileInfo->getRealPath(), $to_file);
		global $stat;
		$stat['copied']++;
	}
	return true;
};

$start = microtime(1);
dir_walk($dst_dir, $skip, $checkFile, $copyFile);

echo 'Dir 1: ', $src_dir, '<br>
Dir 2: ', $dst_dir, '<br>
Result: ', $result_dir, '<br><br>', 
'Done in ', round(microtime(1) - $start, 2), 'sec 
(Files checked: ', $stat['checked'], ', 
different: ', $stat['copied'], ')';