<?php
	$timeStart = microtime(true);
?>

<?php
	$messages = array();

	$extractToDirectory = "swaps";
	$backupDirectoryName = "backups";
	$backupFilePreponeName = "backup";
	$githubRepositoryName = "parametermetric";
	$repositoryBranch = "main";
	$fileUrl = "https://github.com/openroot/{$githubRepositoryName}/archive/refs/heads/{$repositoryBranch}.zip";
	$fileName = basename($fileUrl);

	$extractAndBackupDirectoriesExists = false;
	if (!file_exists($extractToDirectory) && mkdir($extractToDirectory)) {
		file_put_contents("{$extractToDirectory}/blank.file", "");
	}
	if (!file_exists($backupDirectoryName) && mkdir($backupDirectoryName)) {
		file_put_contents("{$backupDirectoryName}/blank.file", "");
	}
	$extractAndBackupDirectoriesExists = file_exists("{$extractToDirectory}/blank.file") && file_exists("{$backupDirectoryName}/blank.file") ? true : false;

	$content = file_get_contents($fileUrl);
	if ($extractAndBackupDirectoriesExists && !empty($content)) {
		if (file_put_contents($fileName, $content)) {
			array_push($messages, "File downloaded successfully.");
			$extractedDirectoryName = null;
			$zip = new \ZipArchive;
			if ($zip->open("main.zip")) {
				$zip->extractTo($extractToDirectory);
				if ($zip->close()) {
					$extractedDirectoryName = "{$extractToDirectory}/{$githubRepositoryName}-{$repositoryBranch}";
					array_push($messages, "Downloaded file unzipped successfully.");
				}
			}
			else {
				array_push($messages, "File unzipping was failed.");
			}
			if (file_exists("{$repositoryBranch}.zip")) {
				if (unlink("{$repositoryBranch}.zip")) {
					array_push($messages, "Downloaded zipped file deleted successfully.");
					$backupFileName = $backupFilePreponeName . CurrentTimePlatformSafe();
					$backupPath = "{$backupDirectoryName}/$backupFileName";
					if (CopyDirectoriesIndepth("../", $backupPath)) {
						array_push($messages, "Originals copied successfully.");
						$zipFileName = "{$backupDirectoryName}/{$backupFileName}.zip";
						$zip = new \ZipArchive;
						if($zip->open($zipFileName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) == true) {
							AddFilesToZip($zip, $backupPath);
							if ($zip->close()) {
								array_push($messages, "Copied originals zipped successfully.");
								if (DeleteDirectoriesIndepth($backupPath)) {
									array_push($messages, "Copy of originals deleted successfully.");
									if (DeleteDirectoriesIndepth("../")) {
										array_push($messages, "Originals deleted successfully.");
										if (MoveDirectoriesSeconddepth($extractedDirectoryName, "../")) {
											array_push($messages, "Downloaded files moved to original successfully.");
											if (SanitizeSwapsAndBackupsDirectory()) {
												array_push($messages, "Swaps and backups directory sanitized successfully.");
											}
											else {
												array_push($messages, "Sanitization of swaps and backups directory was failed.");
											}
										}
										else {
											array_push($messages, "Moving downloaded files to original was failed.");
										}
									}
									else {
										array_push($messages, "Deletion of originals was failed.");
									}
								}
								else {
									array_push($messages, "Deletion of copy of originals was failed.");
								}
							}
							else {
								array_push($messages, "Zipping of copied originals was failed.");
							}
						}
					}
					else {
						array_push($messages, "Originals copy was failed.");
					}
				}
				else {
					array_push($messages, "Deletion of downloaded zipped file was failed.");
				}
			}
		}
	}
	else {
		array_push($messages, "File downloading from online source was failed.");
	}

	foreach ($messages as $index => $value) {
		echo ($index + 1) . ": {$value}<br>";
	}

	function CopyDirectoriesIndepth(string $fromDirectory, string $toDirectoryAnother) {
		$result = false;
		if (is_dir($fromDirectory)) {
			$toDirectoryAnotherExists = false;
			$toDirectoryAnotherExists = !is_dir($toDirectoryAnother) ? mkdir($toDirectoryAnother) : true;

			if ($toDirectoryAnotherExists) {
				$directoryIndepthExists = false;
				$directoryIndepthResult = false;

				$oldFilesDeleted = true;
				foreach (scandir($toDirectoryAnother) as $index => $value) {
					if (!(str_starts_with($value, ".") || $value == "install" || $value == "cgi-bin")) {
						$oldFileToDelete = "{$toDirectoryAnother}/{$value}";
						if (is_file($oldFileToDelete)) {
							$oldFilesDeleted = unlink($oldFileToDelete);
						}
					}
				}

				$originalCount = 0;
				foreach (scandir($fromDirectory) as $index => $value) {
					if (!(str_starts_with($value, ".") || $value == "install" || $value == "cgi-bin")) {
						$originalCount++;
						$sourceFilePath = "{$fromDirectory}/{$value}";
						$destinationFilePath = "{$toDirectoryAnother}/{$value}";
						if (is_file($sourceFilePath)) {
							if ($oldFilesDeleted) {
								copy($sourceFilePath, $destinationFilePath);
							}
						}
						if (is_dir($sourceFilePath)) {
							$directoryIndepthExists = true;
							$directoryIndepthResult = CopyDirectoriesIndepth($sourceFilePath, $destinationFilePath);
						}
					}
				}

				$copiedCount = 0;
				foreach (scandir($toDirectoryAnother) as $index => $value) {
					if (!(str_starts_with($value, ".") || $value == "install" || $value == "cgi-bin")) {
						$copiedCount++;
					}
				}

				$result = $originalCount == $copiedCount ? true : false;
				if ($directoryIndepthExists) {
					$result = $directoryIndepthResult && $result;
				}
			}
		}
		return $result;
	}

	function MoveDirectoriesSeconddepth(string $fromDirectory, string $toDirectoryAnother) {
		$result = false;
		if (is_dir($fromDirectory) && is_dir($toDirectoryAnother)) {
			foreach (scandir($fromDirectory) as $index => $value) {
				if (!(str_starts_with($value, ".") || $value == "install" || $value == "cgi-bin")) {
					$result = rename("{$fromDirectory}/{$value}", "{$toDirectoryAnother}/{$value}");
				}
			}
		}
		return $result;
	}

	function DeleteDirectoriesIndepth(string $directoryPath, bool $removeAll = false) {
		$result = false;
		if (is_dir($directoryPath)) {
			$filteredFiles = array();
			$dir = opendir($directoryPath);
			while ($file = readdir($dir)) {
				if ($removeAll) {
					if (!($file == "." || $file == "..")) {
						array_push($filteredFiles, $file);
					}
				}
				else {
					if (!(str_starts_with($file, ".") || $file == "install" || $file == "cgi-bin")) {
						array_push($filteredFiles, $file);
					}
				}
			}
			closedir($dir);
			foreach ($filteredFiles as $index => $value) {
				$fileName = "{$directoryPath}/{$value}";
				if (is_file($fileName)) {
					unlink($fileName);
				}
				if (is_dir($fileName)) {
					DeleteDirectoriesIndepth($fileName);
					rmdir($fileName);
				}
			}
			$countFilesStillExists = 0;
			foreach (scandir($directoryPath) as $index => $value) {
				if (!(str_starts_with($value, ".") || $value == "install" || $value == "cgi-bin")) {
					$countFilesStillExists++;
				}
			}
			if ($countFilesStillExists == 0) {
				$result = true;
			}
		}
		return $result;
	}

	function AddFilesToZip(\ZipArchive $zip, string $directoryPath) {
		if (is_dir($directoryPath)) {
			$count = 0;
			$dir = opendir($directoryPath);
			while ($file = readdir($dir)) {
				if (!(str_starts_with($file, ".") || $file == "install" || $file == "cgi-bin")) {
					$count++;
					$fileName = "{$directoryPath}/{$file}";
					if (is_file($fileName)) {
						$zip->addFile($fileName, $fileName);
					}
					if (is_dir($fileName)) {
						AddFilesToZip($zip, $fileName);
					}
				}
			}
			closedir($dir);
			if ($count == 0) {
				$zip->addEmptyDir($directoryPath);
			}
		}
	}

	function SanitizeSwapsAndBackupsDirectory() {
		$result = false;
		global $extractToDirectory;
		global $backupDirectoryName;
		global $backupFilePreponeName;
		global $githubRepositoryName;
		global $repositoryBranch;
		$result = DeleteDirectoriesIndepth("{$extractToDirectory}/{$githubRepositoryName}-{$repositoryBranch}", true);
		if ($result) {
			$result = rmdir("{$extractToDirectory}/{$githubRepositoryName}-{$repositoryBranch}");
		}
		if ($result) {
			foreach (scandir($backupDirectoryName) as $index => $value) {
				if (!($value == "." || $value == "..")) {
					if (is_dir("{$backupDirectoryName}/{$value}") && str_starts_with($value, $backupFilePreponeName)) {
						if (is_dir("{$backupDirectoryName}/{$value}/cgi-bin")) {
							$result = rmdir("{$backupDirectoryName}/{$value}/cgi-bin");
						}
						$result = rmdir("{$backupDirectoryName}/{$value}");
					}
				}
			}
		}
		return $result;
	}

	function CurrentTimePlatformSafe(?string $timeZone = "UTC") {
		$currentTime = new \DateTime("now", new \DateTimeZone($timeZone));
		if ($currentTime != null) {
			$timeZone = substr($currentTime->format("O"), 1);
			return $currentTime->format("__H_i_s_u__d_m_Y__D__{$timeZone}");
		}
		return false;
	}
?>

<?php
	$timeEnd = microtime(true);
	$executionTime = $timeEnd - $timeStart;
	echo "<br><hr><i><b>Execution Time:</b> {$executionTime} seconds</i><hr><br>";
?>