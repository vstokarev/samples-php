<?php

/**
 * Class Image
 * @author Viacheslav Tokarev
 */
class Image extends CComponent
{
	protected $imagePath = '';		// Image file with full path
	protected $ext = '';			// Extension
	protected $imageFile = '';		// Full image file name (with extension)
	protected $dirPath = '';		// Full dir path
	protected $fileName = '';		// File name (without extension)
	protected $imageType = '';		// EXIF image type
	protected $thumbnail = '';		// Full path to thumbnail image

	protected $resource = null;		// Image resource (from imagecreate*)

	const RESIZE_AS_IS = 1;
	const RESIZE_WITH_PROPORTIONS = 2;
	const RESIZE_WITH_CROP = 3;

	public function __construct( $imagePath='' )
	{
		if ( $imagePath != '' )
			$this->setImage( $imagePath );
	}

	public function setImage( $imagePath )
	{
		$this->imagePath = $imagePath;

		$pathParts = pathinfo( $this->imagePath );
		$this->ext = $pathParts['extension'];
		$this->imageFile = $pathParts['basename'];
		$this->dirPath = $pathParts['dirname'];
		$this->fileName = $pathParts['filename'];

		$this->imageType = @exif_imagetype( $this->imagePath );
	}

	public function confirmType( $types )
	{
		if ( $this->imagePath == '' )
			throw new Exception( 'You need to specify image first' );

		if ( $this->imageType === false || !in_array( $this->imageType, $types ) )
			return false;

		switch( $this->imageType )
		{
			case IMAGETYPE_GIF:
				if ( $this->ext != 'gif' )
					$newFileName = $this->fileName . '.' . 'gif';
			break;
			case IMAGETYPE_JPEG:
				if ( $this->ext != 'jpg' )
					$newFileName = $this->fileName . '.' . '.jpg';
			break;
			case IMAGETYPE_PNG:
				if ( $this->ext != 'png' )
					$newFileName = $this->fileName . '.' . '.png';
			break;
			case IMAGETYPE_BMP:
				if ( $this->ext != 'bmp' )
					$newFileName = $this->fileName . '.' . '.bmp';
			break;
			default:
				return false;
		}

		if ( isset( $newFileName ) )
		{
			rename( $this->imagePath, $this->dirPath . DIRECTORY_SEPARATOR . $newFileName );
			$this->setImage( $this->dirPath . DIRECTORY_SEPARATOR . $newFileName );

			return $newFileName;
		}

		return $this->imageFile;
	}

	public function checkMinSize( $width, $height )
	{
		if ( is_null( $this->resource ) )
		{
			$res = $this->_openImage();
			if ( !$res )
				throw new Exception( 'Unable to open image: ' . $this->imageFile );
		}

		$sourceWidth = imagesx( $this->resource );
		$sourceHeight = imagesy( $this->resource );

		if ( $sourceWidth < $width || $sourceHeight < $height )
			return false;
		else
			return true;
	}

	public function resize( $width, $height, $resizeType, $keepSide='none' )
	{
		if ( is_null( $this->resource ) )
		{
			$res = $this->_openImage();
			if ( !$res )
				throw new Exception( 'Unable to open image: ' . $this->imageFile );
		}

		// Get original size
		$originalWidth = imagesx( $this->resource );
		$originalHeight = imagesy( $this->resource );

		if ( $width > $originalWidth || $height > $originalHeight )
			throw new Exception( 'Target image size is bigger the source image size' );

		if ( $resizeType == self::RESIZE_AS_IS || $resizeType == self::RESIZE_WITH_CROP )
			$resultImage = imagecreatetruecolor( $width, $height );
		else
		{
			$ratio = $originalWidth / $originalHeight;
			if ( $width / $height > $ratio )
				$width = $height * $ratio;
			else
				$height = $width / $ratio;

			$resultImage = imagecreatetruecolor( $width, $height );
		}

		if( $resizeType == self::RESIZE_WITH_CROP )
		{
			if ( $keepSide == 'width' )
				$originalHeight = $originalWidth * $height / $width;
			elseif( $keepSide == 'height' )
				$originalWidth = $originalHeight * $width / $height;
			else
			{
				$ratio = $width / $height;
				if ( $originalWidth / $originalHeight )
					$originalWidth = $originalHeight * $ratio;
				else
					$originalWidth = $originalWidth * $ratio;
			}
		}

		imagecopyresampled( $resultImage, $this->resource, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight );
		unlink( $this->imagePath );

		switch( $this->imageType )
		{
			case IMAGETYPE_GIF:
				imagegif( $resultImage, $this->imagePath );
			break;
			case IMAGETYPE_JPEG:
				imagejpeg( $resultImage, $this->imagePath, 95 );
			break;
			case IMAGETYPE_PNG:
				imagepng( $resultImage, $this->imagePath );
			break;
			case IMAGETYPE_BMP:
				imagewbmp( $resultImage, $this->imagePath );
			break;
		}

		return true;
	}

	/**
	 * @param $newFile
	 * @param $srcX
	 * @param $srcY
	 * @param $width
	 * @param $height
	 * @return Image
	 * @throws Exception
	 */
	public function crop( $newFile, $srcX, $srcY, $width, $height )
	{
		if ( is_null( $this->resource ) )
		{
			$res = $this->_openImage();
			if ( !$res )
				throw new Exception( 'Unable to open image: ' . $this->imageFile );
		}

		$resultImage = imagecreatetruecolor( $width, $height );

		imagecopy( $resultImage, $this->resource, 0, 0, $srcX, $srcY, $width, $height );

		$newPath = $this->dirPath . DIRECTORY_SEPARATOR . $newFile;
		switch( $this->imageType )
		{
			case IMAGETYPE_GIF:
				imagegif( $resultImage, $newPath );
				break;
			case IMAGETYPE_JPEG:
				imagejpeg( $resultImage, $newPath, 95 );
				break;
			case IMAGETYPE_PNG:
				imagepng( $resultImage, $newPath );
				break;
			case IMAGETYPE_BMP:
				imagewbmp( $resultImage, $newPath );
				break;
		}

		unlink( $this->imagePath );

		return new Image( $newPath );
	}

	public function makeThumbnail( $width, $height, $resizeType, $keepSide='none' )
	{
		if ( is_null( $this->resource ) )
		{
			$res = $this->_openImage();
			if ( !$res )
				throw new Exception( 'Unable to open image: ' . $this->imageFile );
		}

		$copyRes = copy( $this->imagePath, $this->dirPath . DIRECTORY_SEPARATOR . 'sm' . $this->imageFile );
		if ( !$copyRes )
			throw new Exception( 'Unable to make a copy of file' );

		$newImage = new Image( $this->dirPath . DIRECTORY_SEPARATOR . 'sm' . $this->imageFile );
		$this->thumbnail = $this->dirPath . DIRECTORY_SEPARATOR . 'sm' . $this->imageFile;

		return $newImage->resize( $width, $height, $resizeType, $keepSide );
	}

	public function getThumbnailPath()
	{
		return $this->thumbnail;
	}

	public function getImagePath()
	{
		return $this->imagePath;
	}

	public function getAspect()
	{
		if ( is_null( $this->resource ) )
		{
			$res = $this->_openImage();
			if ( !$res )
				throw new Exception( 'Unable to open image: ' . $this->imageFile );
		}

		return imagesx( $this->resource ) / imagesy( $this->resource );
	}

	public function getWidth()
	{
		if ( is_null( $this->resource ) )
		{
			$res = $this->_openImage();
			if ( !$res )
				throw new Exception( 'Unable to open image: ' . $this->imageFile );
		}

		return imagesx( $this->resource );
	}

	protected function _openImage()
	{
		switch( $this->imageType )
		{
			case IMAGETYPE_GIF:
				$this->resource = imagecreatefromgif( $this->imagePath );
			break;
			case IMAGETYPE_JPEG:
				$this->resource = imagecreatefromjpeg( $this->imagePath );
			break;
			case IMAGETYPE_PNG:
				$this->resource = imagecreatefrompng( $this->imagePath );
			break;
			case IMAGETYPE_BMP:
				$this->resource = imagecreatefromwbmp( $this->imagePath );
			break;
			default:
				return false;
		}

		return true;
	}
}