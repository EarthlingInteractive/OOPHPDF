<?php

class OOPHPDF_WriteItem extends OOPHPDF_Object {
	private $text = '';
	
	private $cellPaddingLeft = 0;
	private $cellPaddingTop = 0;
	private $cellPaddingRight = 0;
	private $cellPaddingBottom = 0;
	
	private $fontStyle = '';
	private $fontSize;
	
	private $textColor = array(0, 0, 0);
	
	private $alignHorizontal = 'L';
	
	private $fillColor;
	
	
	
	public function getCellPaddingLeft() {
		return $this->cellPaddingLeft;
	}
	
	public function setCellPaddingLeft($padding) {
		$this->cellPaddingLeft = $padding;
		return $this;
	}
	
	
	
	public function getCellPaddingTop() {
		return $this->cellPaddingTop;
	}
	
	public function setCellPaddingTop($padding) {
		$this->cellPaddingTop = $padding;
		return $this;
	}
	
	
	
	public function getCellPaddingRight() {
		return $this->cellPaddingRight;
	}
	
	public function setCellPaddingRight($padding) {
		$this->cellPaddingRight = $padding;
		return $this;
	}
	
	
	
	public function getCellPaddingBottom() {
		return $this->cellPaddingBottom;
	}
	
	public function setCellPaddingBottom($padding) {
		$this->cellPaddingBottom = $padding;
		return $this;
	}
	
	
	
	public function getFontStyle() {
		return $this->fontStyle;
	}
	
	public function setFontStyle($style) {
		$this->fontStyle = $style;
		return $this;
	}
	
	
	
	public function getFontSize() {
		return $this->fontSize;
	}
	
	public function setFontSize($size) {
		$this->fontSize = $size;
		return $this;
	}
	
	
	
	public function getTextColorArray() {
		return $this->textColor;
	}
	
	public function setTextColorArray($color) {
		$this->textColor = $color;
		return $this;
	}
	
	
	
	public function getAlignHorizontal() {
		return $this->alignHorizontal;
	}
	
	public function setAlignHorizontal($align) {
		$this->alignHorizontal = $align;
		return $this;
	}
	
	
	
	public function getText() {
		return $this->text;
	}
	
	public function setText($text) {
		$this->text = $text;
		return $this;
	}
	
	
	
	public function getFillColorArray() {
		return $this->fillColor;
	}
	
	public function setFillColorArray($color) {
		$this->fillColor = $color;
		return $this;
	}
	
	
	
	public function getWidthAuto() {
		$this->setupContext(false);
		
		return $this->pdf->GetStringWidth($this->getText()) + $this->getCellPaddingLeft() + $this->getCellPaddingRight() + 0.01;
	}
	
	
	
	/**
	 * Returns the height in user units of a line of text written by this object.
	 */
	public function getLineHeight() {
		$this->setupContext(false);
		
		return $this->pdf->getFontSize() * $this->pdf->getCellHeightRatio();
	}
	
	
	
	/**
	 * Returns the distance in user units between the baseline of the text and position where the text is written.
	 */
	public function getBaselineOffset() {
		$fontAscent = $this->pdf->getFontAscent('', $this->getFontStyle(), $this->getFontSize());
		$fontDescent = $this->pdf->getFontDescent('', $this->getFontStyle(), $this->getFontSize());
		$fontRealHeight = $fontAscent + $fontDescent;
		
		return (($this->getLineHeight() - $fontRealHeight) / 2) + $fontAscent;
	}
	
	
	
	/**
	 * Draws a single line from this text item at the given location.
	 * Pass $txt=null to write the initial line of text.
	 * With each call, it returns the remaining text that didn't fit on the line.
	 * With each successive call, pass $txt as the remaining text to write.
	 * Continue calling until the return value is an empty string.
	 * @param number $x - x position of location to write at
	 * @param number $y - y position of location to write at
	 * @param string $txt - null for the first write, or the remaining text to write on each successive line write
	 * @return string - the remaining text that didn't fit on the current line
	 */
	public function drawAtPosition($x, $y, $txt = null) {
		// setup context for writing
		$this->setupContext(true);
		
		// if text wasn't supplied, start with whole text
		if ($txt === null) {
			$txt = $this->getText();
		}
		
		// move to writing location
		$this->pdf->SetXY($x, $y);
		
		// write the line of text
		$remainingText = $this->pdf->Write(0, $txt, '', $this->getFillColorArray() !== null, $this->getAlignHorizontal(), 0, 0, true, false, 0, 0, '');
		
		// trim leading newline character if there is one (it might be why the line ended), the Write function doesn't remove it for us
		$remainingText = preg_replace('/^\n{1}/', '', $remainingText);
		
		// trim leading spaces from remaining text
		$remainingText = $this->pdf->stringLeftTrim($remainingText);
		
		return $remainingText;
	}
	
	
	
	private function setupContext($drawing) {
		$this->pdf->SetFont('', $this->getFontStyle(), $this->getFontSize(), '', 'default', $drawing);
		$this->pdf->SetTextColorArray($this->getTextColorArray());
		
		$this->pdf->setCellPaddings($this->getCellPaddingLeft(), $this->getCellPaddingTop(), $this->getCellPaddingRight(), $this->getCellPaddingBottom());
		
		if ($drawing) {
			$fillColor = $this->getFillColorArray();
			if ($fillColor !== null) {
				$this->pdf->SetFillColorArray($fillColor);
			}
		}
	}
}
