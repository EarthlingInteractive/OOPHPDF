<?php

/**
 * This class writes a collection of OOPHPDF_WriteItem's to a PDF,
 * ensuring that the baseline of all text on a given line matches.
 *
 * Example usage:
		$items = array();
		for ($i = 0; $i < 10; $i++) {
			$color = 240 - (15 * ($i % 10));
			$size = 72 - (($i % 3) * 24);
			$style = (($i % 2 === 0)? '': 'B');

			$item = new OOPHPDF_WriteItem($pdf);
			$item
				->setFillColorArray(array($color, $color, $color))
				->setText($i . ' Ag Ag ' . $i)
				->setFontSize($size)
				->setFontStyle($style);

			$items[] = $item;
		}

		$textGroup = new OOPHPDF_WriteGroup($pdf);
		$textGroup
			->addWriteItems($items)
			->setWidth(6.5);
		$textGroup->drawAtPosition(1, 1);
 */
class OOPHPDF_WriteGroup extends OOPHPDF_Object {
	private $width;
	private $height;
	private $hangingIndent = 0;

	private $writeItems = array();



	public function getWidth() {
		return $this->width;
	}

	public function setWidth($width) {
		$this->width = $width;
		return $this;
	}



	public function getHeight() {
		return $this->height;
	}

	public function setHeight($height) {
		$this->height = $height;
		return $this;
	}
	
	
	
	public function getHangingIndent() {
		return $this->hangingIndent;
	}
	
	public function setHangingIndent($handingIndent) {
		$this->hangingIndent = $handingIndent;
		return $this;
	}



	/**
	 * Call this to add an object to write as part of this group of text.
	 * @param OOPHPDF_WriteItem $writeItem - an item to write
	 */
	public function addWriteItem(OOPHPDF_WriteItem $writeItem) {
		$this->writeItems[] = $writeItem;
		return $this;
	}

	/**
	 * Call this to add an array of objects to write as part of this group of text.
	 * @param array $writeItems - an array of items to write
	 */
	public function addWriteItems(array $writeItems) {
		foreach ($writeItems as $writeItem) {
			$this->addWriteItem($writeItem);
		}
		return $this;
	}
	
	
	
	/**
	 * Returns the calculated width of all text that this group will write.
	 */
	public function getWidthAuto() {
		$width = 0;
		
		// add up width of all write items
		foreach ($this->writeItems as $writeItem) {
			/* @var $writeItem OOPHPDF_WriteItem */
			$width += $writeItem->getWidthAuto();
		}
		
		return $width;
	}
	
	public function setWidthToAuto() {
		$this->setWidth($this->getWidthAuto());
		return $this;
	}



	/**
	 * Returns the calculated height of all text that this group will write.
	 * Note that this function is relatively resource intensive due to needing to perform test writes.
	 */
	public function getHeightAuto() {
		// find metrics for the content
		$rowHeights = null;
		$rowBaselines = null;
		$lastLineWidth = null;
		$this->getMetrics($rowHeights, $rowBaselines, $lastLineWidth);

		// sum up the row heights to get auto height
		$height = 0.01;
		foreach ($rowHeights as $rowHeight) {
			$height += $rowHeight;
		}

		return $height;
	}

	public function setHeightToAuto() {
		$this->setHeight($this->getHeightAuto());
		return $this;
	}
	
	
	
	public function getLastLineWidthAuto() {
		// find metrics for the content
		$rowHeights = null;
		$rowBaselines = null;
		$lastLineWidth = null;
		$this->getMetrics($rowHeights, $rowBaselines, $lastLineWidth);
		
		return $lastLineWidth;
	}



	/**
	 * Draws this group's content of text at the given location
	 * @param number $x - x position of location to write at
	 * @param number $y - y position of location to write at
	 */
	public function drawAtPosition($x, $y) {
		// setup context for writing area
		$previousContext = $this->setupContext(true, $x, $this->getWidth());

		// find metrics for the content
		$rowHeights = null;
		$rowBaselines = null;
		$lastLineWidth = null;
		$this->getContentMetrics($x, $y, $rowHeights, $rowBaselines, $lastLineWidth);

		// actually write text
		$rowIndex = 0;
		$curX = $x;
		$rowY = $y;
		$abort = false;
		foreach ($this->writeItems as $writeItem) {
			/* @var $writeItem OOPHPDF_WriteItem */
			$remainingText = null;
			do {
				// if the content for this row would exceed the set height, abort
				if (($this->height !== null) && ($rowY + $rowHeights[$rowIndex]  - $y > $this->height)) {
					$abort = true;
					break;
				}

				// get baseline offset for this text
				$baselineOffset = $writeItem->getBaselineOffset();

				// calculate y position for the text, based on the row y and baseline of the text
				// so that this item's baseline lines up with the other items on the row
				$curY = $rowY + ($rowBaselines[$rowIndex] - $baselineOffset);

				// draw the text on the current line and get remaining text
				$remainingText = $writeItem->drawAtPosition($curX, $curY, $remainingText);
				$curX = $this->pdf->GetX();

				// if there is more text than fits on the line, move to next row
				if ($remainingText !== '') {
					$curX = $x + $this->hangingIndent;
					$rowY += $rowHeights[$rowIndex];
					$rowIndex++;
				}
			} while ($remainingText !== '');

			// if aborting, abort
			if ($abort) {
				break;
			}
		}

		// restore writing area context
		$this->restoreContext($previousContext);

		return $this;
	}
	
	
	
	private function getMetrics(&$rowHeights, &$rowBaselines, &$lastLineWidth) {
		// setup context for writing area
		$previousContext = $this->setupContext(false, 0, $this->getWidth());
		
		// find metrics for the content
		$rowHeights = null;
		$rowBaselines = null;
		$lastLineWidth = null;
		$this->getContentMetrics(0, 0, $rowHeights, $rowBaselines, $lastLineWidth);
		
		// restore writing area context
		$this->restoreContext($previousContext);
	}



	private function getContentMetrics($x, $y, &$rowHeights, &$rowBaselines, &$lastLineWidth) {
		// create a scratch page so we can test writing
		$scratchPageInfo = $this->createScratchPage();

		// for each write item, find metrics
		$rowHeights = array();
		$rowBaselines = array();
		$rowIndex = 0;
		$curX = $x;
		$curY = $y;
		foreach ($this->writeItems as $writeItem) {
			/* @var $writeItem OOPHPDF_WriteItem */
			$remainingText = null;
			do {
				// draw the text on the current line and get remaining text
				$remainingText = $writeItem->drawAtPosition($curX, $curY, $remainingText);
				$curX = $this->pdf->GetX();

				// get text height for this line for this item
				$textHeight = $writeItem->getLineHeight();

				// get baseline offset for this line for this item
				$baselineOffset = $writeItem->getBaselineOffset();

				// update the overall row height to take into account this item
				if (array_key_exists($rowIndex, $rowHeights)) {
					$rowHeights[$rowIndex] = max($rowHeights[$rowIndex], $textHeight);
				} else {
					$rowHeights[$rowIndex] = $textHeight;
				}

				// update the overall row baseline to take into account this item
				if (array_key_exists($rowIndex, $rowBaselines)) {
					$rowBaselines[$rowIndex] = max($rowBaselines[$rowIndex], $baselineOffset);
				} else {
					$rowBaselines[$rowIndex] = $baselineOffset;
				}

				// if there is more text than fits on the line, move to next row
				if ($remainingText !== '') {
					$curX = $x + $this->hangingIndent;
					$rowIndex++;
				}
			} while ($remainingText !== '');
		}
		
		// record the width of the last line
		$lastLineWidth = $curX - $x;

		// destroy scratch page
		$this->destroyScratchPage($scratchPageInfo);
	}



	private function setupContext($drawing, $x, $width) {
		// save off current context
		$previousContext = array(
			'margins' => $this->pdf->getMargins()
		);

		// set margins for writing in area we want
		$this->pdf->SetLeftMargin($x);
		$this->pdf->SetRightMargin($this->pdf->getPageWidth() - $x - $width);

		// return current context
		return $previousContext;
	}

	private function restoreContext($previousContext) {
		$this->pdf->SetMargins($previousContext['margins']['left'], $previousContext['margins']['top'], $previousContext['margins']['right'], true);
	}



	private function createScratchPage() {
		// start a pdf transaction so we can revert our testing
		$this->pdf->startTransaction();
		
		// set starting location
		$margins = $this->pdf->getMargins();
		$this->pdf->SetY($margins['top'], true);
	}

	private function destroyScratchPage($scratchPageInfo) {
		// rollback the pdf transaction
		$this->pdf->rollbackTransaction(true);
	}
}
