<?php

/**
 * Intended to provide a normalized, object-oriented approach to drawing images
 * Elimintates behavior differences between SVG and other graphics formats
 */
class OOPHPDF_Image extends OOPHPDF_Object {

  // The file path to the image to be drawn
  protected $filePath;

  // The MIME type of the image being drawn
  protected $fileType;

  // x/y values determine upper left corner of image
  protected $x;
  protected $y;

  // regular width/height values define the ratio to draw the image at
  protected $width;
  protected $height;

  // Retrieved from file
  protected $widthRatio = null;
  protected $heightRatio = null;

  // scaler values will be used for image zoom if necessary
  protected $scalerWidth = null;
  protected $scalerHeight = null;

  // scaled values are calculated from regular values and scaler values
  protected $scaledWidth;
  protected $scaledHeight;
  
  // border passed to TCPDF
  protected $border = 0;


  public function __construct($pdf, $filePath, $x = null, $y = null, $width = null, $height = null) {

    parent::__construct($pdf);

    // Set file path and file type
    $this->setImage($filePath);

    // Set image position
    if (!is_null($x)) {
      $this->setX($x);
    } else {
      $this->setX($this->pdf->getX());
    }
    if (!is_null($y)) {
      $this->setY($y);
    } else {
      $this->setY($this->pdf->getY());
    }

    // Set width and height, basing off of file size if not given
    if (!is_null($width)) {
      $this->setWidth($width);
    }
    if (!is_null($height)) {
      $this->setHeight($height);
    }

  }


  public function setX($x) {
    $this->x = $x;
    return $this;
  }
  public function setY($y) {
    $this->y = $y;
    return $this;
  }

  public function setWidth($width) {

    $this->width = $width;
    
    // recalculate the scaled size
    $this->calculateScaledSize();

    return $this;

  }
  public function getWidth() {
    return $this->width;
  }
  public function setHeight($height) {

    $this->height = $height;
    
    // recalculate the scaled size
    $this->calculateScaledSize();

    return $this;

  }
  public function getHeight() {
    return $this->height;
  }

  public function setScalerWidth($width) {
    $this->scalerWidth = $width;
    
    // recalculate the scaled size
    $this->calculateScaledSize();
    
    return $this;
  }
  public function getScalerWidth() {
    return $this->scalerWidth;
  }
  public function setScalerHeight($height) {
    $this->scalerHeight = $height;
    
    // recalculate the scaled size
    $this->calculateScaledSize();
    
    return $this;
  }
  public function getScalerHeight() {
    return $this->scalerHeight;
  }
  
  public function getScaledWidth() {
  	return $this->scaledWidth;
  }
  public function getScaledHeight() {
  	return $this->scaledHeight;
  }
  
  public function setBorder($border) {
    $this->border = $border;
    return $this;
  }
  public function getBorder() {
    return $this->border;
  }

  public function setImage($filePath) {

    // Throw an error if file does not exist
    if (!file_exists($filePath)) {
      throw new Exception('Attempted to set image to non-existent file path.');
    }

    // Get file type
    $mimeInfo = finfo_open(FILEINFO_MIME);
    $fileType = finfo_file($mimeInfo, $filePath);

    // Throw an error if file path doesn't lead to valid image
    if (strtolower(substr($fileType, 0, 5)) !== 'image') {
      throw new Exception('Attempted to set image path to a non-image.');
    }

    // Set file type and path on object
    $this->fileType = substr( $fileType, (strpos($fileType, '/') + 1) );
    $this->filePath = $filePath;
    
    // Get and store image dimensions
    $dimensions = $this->getSizeFromFile();
    $this->widthRatio = $dimensions['width'];
    $this->heightRatio = $dimensions['height'];
    
    // recalculate the scaled size
    $this->calculateScaledSize();

    return $this;

  }

  protected function calculateScaledSize() {
  	
  	if (!$this->widthRatio || !$this->heightRatio) {
  	  return;
  	}

    $scalerWidth = $this->scalerWidth;
    $scalerHeight = $this->scalerHeight;

    $width = $this->width;
    $height = $this->height;

    if (!$width && !$height) {

      $width = $this->widthRatio;
      $height = $this->heightRatio;

    } else if (!$height) {

      $height = $width * ($this->heightRatio / $this->widthRatio);

    } elseif (!$width) {

      $width = $height * ($this->widthRatio / $this->heightRatio);

    }

    $widthHeightRatio = $width / $height;

    if (!is_null($scalerWidth)) {
      //scaler width exists

      if (is_null($scalerHeight)) {
        //scaler height does not exist

        $width = $scalerWidth;
        $height = $width / $widthHeightRatio;

      } else {
        //scaler height exists

        $scalerWidthHeightRatio = $scalerWidth / $scalerHeight;

        if ($scalerWidthHeightRatio > $widthHeightRatio) {
          // scaler is proportionally wider than image

          $height = $scalerHeight;
          $width = $height * $widthHeightRatio;

        } else {
          // scaler is proportionally taller than image

          $width = $scalerWidth;
          $height = $width / $widthHeightRatio;

        }

      }

    } else {
      //scaler width does not exist

      if (!is_null($scalerHeight)) {
        //scaler height exists

        $height = $scalerHeight;
        $width = $height * $widthHeightRatio;

      }

    }

    $this->scaledWidth = $width;
    $this->scaledHeight = $height;

  }


  /**
   * Determines whether the image is an SVG
   * @return Boolean
   */
  public function isSVG() {

    // Check if stored file type starts with "svg" - if so, file is SVG
    if (strtolower(substr($this->fileType, 0, 3)) === 'svg') {
      return true;
    }

    // File is not SVG
    return false;

  }


  /**
   * Returns image data
   * @return SimpleXMLElement [if SVG]
   * @return array [if not SVG]
   */
  protected function processImageData($imageData = null) {

    $imageSizes = null;

    if ($this->isSVG()) {

      // Load SVG XML from file if not provided
      if (is_null($imageData)) {
        $imageSizes = simplexml_load_file($this->filePath);
      } elseif (is_string($imageData)) {
        try {
          $imageSizes = simplexml_load_string($imageData);
        } catch (Exception $e) {
          throw new Exception('Image Data string for SVG must be valid XML.');
        }
      } elseif ( !($imageData instanceof SimpleXMLElement) ) {
        // Image data is not of a recognized type
        throw new Exception('Invalid image data.');
      } else {
        // Image Data is a SimpleXMLElement
        $imageSizes = $imageData;
      }

    } else {

      if (is_null($imageData)) {
        $imageSizes = getimagesize($this->filePath);
      } elseif (is_array($imageData)) {
        $imageSizes = $imageData;
      } else {
        // Image Data supplied to function must be an array
        throw new Exception('Non-SVG Image data must be supplied as an array.');
      }

      // Throw an error if the image sizes data is invalid
      if ($imageSizes === false) {
        // getimagesize($filePath) did not find a valid image at the path
        throw new Exception('File at '.$this->filePath.' is not an image.');
      }

    }

    return $imageSizes;

  }

  /**
    * Retrieves width of image from file
    * @return Number (int/float)
    */
  protected function getWidthFromFile($imageData = null) {

    // Get size data
    $imageSizes = $this->processImageData($imageData);

    // SVG's get their own behavior
    if ($this->isSVG()) {

      // If width isn't present, something is wrong with the SVG data
      if (!isset($imageSizes['width'])) {
        throw new Exception('Invalid SVG data provided - missing width.');
      }

      return $imageSizes['width'];

    } else {

      if (!isset($imageSizes[0])) {
        // Expecting width data at index 0, as returned by getimagesize($filePath)
        throw new Exception('No width data found for image');
      }

      return $imageSizes[0];

    }

  }

  /**
    * Retrieves height of image from file
    * @return Number (int/float)
    */
  protected function getHeightFromFile($imageData = null) {

    $imageSizes = $this->processImageData($imageData);

    // SVG's get their own behavior
    if ($this->isSVG()) {

      // If height isn't present, something is wrong with the SVG data
      if (!isset($imageSizes['height'])) {
        throw new Exception('Invalid SVG data provided - missing height.');
      }

      return $imageSizes['height'];

    } else {

      if (!isset($imageSizes[1])) {
        // Expecting height data at index 1, as returned by getimagesize($filePath)
        throw new Exception('No height data found for image');
      }

      return $imageSizes[1];

    }

  }

  /**
    * Retrieves size of image from file
    * Useful if retrieving both width and height because file is only opened once
    * @return array('width' => Number, 'Height' => Number)
    */
  protected function getSizeFromFile() {

    $imageSizes = $this->processImageData();

    // SVG's get their own behavior
    if ($this->isSVG()) {

      // If width or height isn't present, something is wrong with the SVG data
      if (!isset($imageSizes['width']) || !isset($imageSizes['height'])) {
        throw new Exception('Invalid SVG data provided - missing width or height.');
      }

      return array(
        'width' => $imageSizes['width'],
        'height' => $imageSizes['height']
      );

    } else {

      //Expecting width at index 0 and height at index 1
      if (!isset($imageSizes[0]) || !isset($imageSizes[1])) {
        throw new Exception('Unable to retrieve a width and height from the image file.');
      }

      return array(
        'width' => $imageSizes[0],
        'height' => $imageSizes[1]
      );

    }

  }


  public function draw() {
    $this->drawAtPosition($this->x, $this->y);
  }

  public function drawAtPosition($x, $y) {

    $this->calculateScaledSize();

    $width = $this->scaledWidth;
    $height = $this->scaledHeight;
    
    if (!$width || !$height) {
      throw new Exception('Unable to draw image without a width and height.');
    }

    if ($this->isSVG()) {
      $this->pdf->imageSVG($this->filePath, $x, $y, $width, $height, '', '', '', $this->border);
    } else {
      $this->pdf->image($this->filePath, $x, $y, $width, $height, '', '', '', false, 300, '', false, false, $this->border);
    }

  }

}

?>
