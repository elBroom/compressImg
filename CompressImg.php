<?php
/**
 * Compression of images
 *
 * @author Zarina Sayfullina
 */
class CompressImg{
	/**
	 * image Compression
	 * @param string $path_from_file - the path to the file
	 * @param string $path_to_file - save path
	 * @param int $max_quality - compression quality
	 * @return int - the percentage of compression
	 */
	public function compress($path_from_file, $path_to_file='', $max_quality=0){
		$result = false;
		$rename = false;
		$size_from_file = filesize($path_from_file);
		if($path_to_file=='' || $path_to_file==$path_from_file){
			$rename = true;
			$path_to_file = $path_from_file.'.tmp';
		}

		switch (pathinfo($path_from_file, PATHINFO_EXTENSION)) {
			case 'png':
				$max_quality = $max_quality?$max_quality:90;
				$result = $this->compressPng($path_from_file, $path_to_file, $max_quality);
				break;
			case 'jpg':
			case 'jpeg':
				$max_quality = $max_quality?$max_quality:50;
				$result = $this->compressJpg($path_from_file, $path_to_file, $max_quality);
				break;
		}
		if($result){
			$size_to_file = filesize($path_to_file);
			if($rename)
				rename($path_to_file, $path_from_file);
			return (1-round($size_to_file/$size_from_file, 2))*100;
		} else
			return false;
	}

	/**
	 * Compress png
	 * @return boolean
	 */
	private function compressPng($path_from_file, $path_to_file, $max_quality=90){
		if (!file_exists($path_from_file)) {
			throw new Exception("File does not exist: $path_from_file");
		}

		$min_quality = 60;

		$compressed_png_content = shell_exec("pngquant --quality=$min_quality-$max_quality - < ".escapeshellarg($path_from_file));
		if (!$compressed_png_content) {
			throw new Exception("Conversion to compressed PNG failed. Is pngquant 1.8+ installed on the server?");
		}

		if(file_put_contents($path_to_file, $compressed_png_content))
			return true;
		else
			return false;
	}

	/**
	 * Compress jpg
	 * @return boolean
	 */
	private function compressJpg($path_from_file, $path_to_file, $max_quality=50){
		if (!file_exists($path_from_file)) {
			throw new Exception("File does not exist: $path_from_file");
		}

		$compressed_jpg_content = imagecreatefromjpeg($path_from_file);

		if(imagejpeg($compressed_jpg_content,$path_to_file, $max_quality))
			return true;
		else
			return false;
	}
}
