<?php

$dir = 'dir_name';
$toRemove = ['.hg', '.hgtags', 'deploy.bat', 'update.bat', 'deploy.sh', 'pull.sh', 'push.sh', 'tag.sh'];

define('DS', DIRECTORY_SEPARATOR);
$relativePath = '.' . DS . $dir;
$cbDelete = function($fileInfo) use (&$cbDelete) {
	if(false === $fileInfo->getRealPath()) {
		$file = $fileInfo->getPath() . DS . $fileInfo->getBaseName();
		throw new Exception("Can't delete '$file'");
	} elseif (!$fileInfo->isWritable()) {
		throw new Exception("Can't remove:\n\t" + $fileInfo->getRealPath());
	} elseif ($fileInfo->isDir()) {
		foreach (new DirectoryIterator($fileInfo->getRealPath()) as $fi) {
			if ($fileInfo->isDot()) {
				continue;
			} else {
				$cbDelete($fi);
			}
		}
		if(is_dir_empty($fileInfo->getRealPath())) {
			//echo 'Dir: ', $fileInfo->getRealPath(), "<br>";
			rmdir($fileInfo->getRealPath());
		}
	} elseif ($fileInfo->isFile()) {
		//echo 'File: ', $fileInfo->getRealPath(), "<br>";
		unlink($fileInfo->getRealPath());
	}
	return true;
};

function dir_walk($path, $cbCondition, $cbAction) {
	foreach (new DirectoryIterator($path) as $fileInfo) {
		if ($fileInfo->isDot()) {
			continue;
		} elseif ($cbCondition($fileInfo)) {
			$cbAction($fileInfo);
		} elseif ($fileInfo->isDir()) {
			dir_walk($path . DS . $fileInfo->getFilename(), $cbCondition, $cbAction);
		}
	}
}

function is_dir_empty($dir) {
	if(empty($dir)) {
		return false;
	}
	if (!is_readable($dir)) {
		throw new Exception("Can't remove:\n\t" + $dir);
	}
	$handle = opendir($dir);
	while (false !== ($entry = readdir($handle))) {
		if ($entry != "." && $entry != "..") {
			return false;
		}
	}
	return true;
}

$start = microtime(1);

dir_walk($relativePath, 
		function($fileInfo) use($toRemove) {
			return in_array($fileInfo->getFilename(), 
				$toRemove);
		}, 
		$cbDelete
);

dir_walk($relativePath . DS . 'temp', 
		function($fileInfo) {
			return $fileInfo->isFile() && ($fileInfo->getFilename() !== 'index.html');
		}, 
		$cbDelete
);

echo 'Done in ', round(microtime(1) - $start, 2);