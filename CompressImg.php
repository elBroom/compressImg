<?php
/**
 * Compression of images
 *
 * Требует установки библиотеки pngquant для сжатия png http://pkgs.org/search/pngquant
 * Требует установки библиотеки ImageMagick для сжатия jpg http://www.imagemagick.org/script/binary-releases.php
 *
 * @author Zarina Sayfullina
 */
define("PATH",          $_SERVER["DOCUMENT_ROOT"].((substr($_SERVER["DOCUMENT_ROOT"],-1)!="/")?"/":""),true);
class CompressImg{
	public $enableLog = true;
	public $logPath = '_admin/_logs/compress_pictures.log';
	private $pngquantPath = '/usr/bin/pngquant';
	private $convertPath = '/usr/bin/convert';
	private $qualityPng = 90;
	private $qualityJpg = 90;

	public function init(){
	}

	/**
	 * Сжатие изображения
	 * @param string $path_from_file - путь к файлу
	 * @param string $path_to_file - путь для сохранения
	 * @param int $max_quality - качество сжатия
	 * @return int - процент сжатия
	 */
	public function compress($path_from_file, $path_to_file='', $max_quality=0){
		$result = false;
		$rename = false;
		if(!file_exists($path_from_file)){
			$this->writeLog("File does not exist: $path_from_file");
			return false;
		}
		$size_from_file = filesize($path_from_file);
		if($path_to_file=='' || $path_to_file==$path_from_file){
			$rename = true;
			$path_to_file = $path_from_file.'.tmp';
		}

		switch ($mime_type = image_type_to_mime_type(exif_imagetype($path_from_file))) {
			case 'image/png':
				$max_quality = $max_quality?$max_quality:$this->qualityPng;
				$result = $this->compressPng($path_from_file, $path_to_file, $max_quality);
				if($result) break;
				//else compress png as jpg
				$this->writeLog("Try compress png as jpg");
			case 'image/jpeg':
			case 'image/pjpeg':
				$max_quality = $max_quality?$max_quality:$this->qualityJpg;
				$result = $this->compressJpg($path_from_file, $path_to_file, $max_quality);
				break;
			default:
				$this->writeLog("File $path_from_file has mime_type: $mime_type");
		}
		if($result){
			$size_to_file = filesize($path_to_file);
			$percent = (1-round($size_to_file/$size_from_file, 2))*100;
			if($percent > 0 && $rename){
				rename($path_to_file, $path_from_file);
			}
			else if($percent <= 0 && $rename)
				unlink($path_to_file);
			return $percent > 0 ? $percent : 0;
		} else
			return false;
	}

	/**
	 * Сжатие png
	 */
	private function compressPng($path_from_file, $path_to_file, $max_quality=100){
		$min_quality = 60;

		//"2>&1" перенаправление stderr(2) в stdout(&1)
		$compressed_png_content = shell_exec($this->pngquantPath." --quality=$min_quality-$max_quality - < ".escapeshellarg($path_from_file)." 2>&1");
		if (preg_grep("/error:/", $compressed_png_content)) {
			$this->writeLog("File: ".$path_from_file);
			$this->writeLog($compressed_png_content);
			return false;
		}

		if(file_put_contents($path_to_file, $compressed_png_content))
			return true;
		else
			return false;
	}

	/**
	 * Сжатие jpg
	 */
	private function compressJpg($path_from_file, $path_to_file, $max_quality=100){
		//"2>&1" перенаправление stderr(2) в stdout(&1)
		$compressed_jpg_content = shell_exec($this->convertPath." -strip -quality $max_quality $path_from_file $path_to_file 2>&1");

		if (preg_grep("/error:/", $compressed_jpg_content)) {
			$this->writeLog("File: ".$path_from_file);
			$this->writeLog($compressed_jpg_content);
			return false;
		}

		if(file_exists($path_to_file)) 
			return true;
		else
			return false;
	}

	private function writeLog($message){
		if($this->enableLog){
			error_log("\n" . date('Y-m-d H:i:s') ." ". $message, 3, PATH.$this->logPath);
		}
	}
}
