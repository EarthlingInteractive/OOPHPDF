<?php

/**
 * A simple extension to MultiCell that creates an Image object and draws it
 * within the bounds of the MultiCell (optionally with some padding).
 */
class OOPHPDF_ImageMultiCell extends OOPHPDF_MultiCell {

	// path to image file to draw at location of this multicell
	private $imageFilename;
	// allow the container you place the image into to be smaller than the multicell by some amount of padding
	private $imagePaddingX = 0;
	private $imagePaddingY = 0;

	public function __construct(TCPDF $pdf, $imageFilename, $imagePaddingX = 0, $imagePaddingY = 0) {

		parent::__construct($pdf);

		$this->imageFilename = $imageFilename;
		$this->imagePaddingX = $imagePaddingX;
		$this->imagePaddingY = $imagePaddingY;

	}

	public function drawAtPosition($x, $y) {
		parent::drawAtPosition($x, $y);

		$startingX = $x;
		$startingY = $y;

		$width = $this->getWidth();
		$height = $this->getHeight();

		$imageStartX = $startingX + $this->imagePaddingX;
		$imageStartY = $startingY + $this->imagePaddingY;

		$paddedWidth = $width - $this->imagePaddingX * 2.0;
		$paddedHeight = $height - $this->imagePaddingY * 2.0;

		// We want to fill the MultiCell in one direction and center in the other direction, without distorting the aspect ratio.

		// first, try scaling to height of cell
		$image = new OOPHPDF_Image($this->pdf, $this->imageFilename, $imageStartX, $imageStartY, null, $paddedHeight);
		if ($image->getScaledWidth() > $paddedWidth) {
			// scaled image is wider than cell, so scale to width instead
			$image = new OOPHPDF_Image($this->pdf, $this->imageFilename, $imageStartX, $imageStartY, $paddedWidth, null);
		}

		// center scaled image
		$image->setX($imageStartX + ($paddedWidth - $image->getScaledWidth()) / 2.0);
		$image->setY($imageStartY + ($paddedHeight - $image->getScaledHeight()) / 2.0);
		
		$image->draw();

		return $this;
	}

}
