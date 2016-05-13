<?php
/**
*
*/
class OOPHPDF_Row extends OOPHPDF_Object implements OOPHPDF_Drawable {

	protected $cells = array();

	//Indicates whether row should be skipped during drawing routines
	protected $skip = false;

	//Indiciates whether the cursor should drop to a new line after drawing
	protected $postDrawLineBreak = true;

	//Indicates whether autoHeight should be considered during calculations
	protected $considerAutoHeight = true;

	//Reset properties indicate whether to return cursor to origin after drawing
	protected $lnResetX = false;
	protected $lnResetY = false;



	public function __construct(TCPDF $pdf, array $cellsToAdd = array()) {

		parent::__construct($pdf);

		if (sizeof($cellsToAdd)) {
			foreach ($cellsToAdd as $cellToAdd) {
				$this->addCell($cellToAdd);
			}
		} else {
			$cells = array();
		}

	}

	public function __clone() {
		$newCells = array();
		foreach ($this->cells as $cellKey => $cell) {
			$newCell = clone $cell;
			$newCells[] = $newCell;
		}
		$this->cells = $newCells;
	}



	public function addCell(OOPHPDF_Drawable $cellToAdd) {
		$this->cells[] = $cellToAdd;
		return $this;
	}

	//Add cell at specific key, shifting following keys back
	public function addCellAt(OOPHPDF_Drawable $cellToAdd,$insertionKey) {
		array_splice($this->cells,$insertionKey,0,array($cellToAdd));
		return $this;
	}

	public function removeCell($cellKey, $uncheckedCellKey = null) {
		unset($this->cells[$cellKey]);
		return $this;
	}

	public function replaceCell($cellKey, OOPHPDF_Drawable $replacementCell) {
		$this->removeCell($cellKey);
		$this->addCellAt($replacementCell, $cellKey);
		return $this;
	}

	public function clearCells() {
		$this->cells = array();
		return $this;
	}


	//Adjusts cell keys so there are no empty gaps.
	public function adjustCellKeys() {

	}

	public function hasCell($cellKey) {
		return isset($this->cells[$cellKey]);
	}

	public function getCell($cellKey) {
		if ($this->hasCell($cellKey)) {
			return $this->cells[$cellKey];
		} else {
			return null;
		}
	}

	public function getCells() {
		return $this->cells;
	}

	public function getCellCount() {
		return sizeof($this->getCells());
	}



	/**
	*Returns the total auto-width of all cells
	*/
	public function getWidthAuto() {

		$width = 0;

		foreach ($this->getCells() as $cell) {
			$width += $cell->getWidthAuto();
		}

		return $width;

	}


	/**
	*Set all cells' width to (specified width)/(cell count), effectively setting row width
	*/
	public function setWidth($width) {

		$cellWidth = $width/$this->getCellCount();

		foreach ($this->getCells() as $cell) {
			$cell->setWidth($cellWidth);
		}

		return $this;

	}

	/**
	*Set width of all cells to specified value
	*/
	public function setCellWidth($width) {

		foreach ($this->getCells() as $cell) {
			$cell->setWidth($width);
		}

		return $this;

	}
	
	public function getHeight() {
		$height = 0;
		foreach ($this->getCells() as $cell) {
			$cellHeight = $cell->getHeight();
			if ($cellHeight > $height) {
				$height = $cellHeight;
			}
		}
		return $height;
	}
	
	//Returns the height of the tallest cell in the row
	public function getHeightAuto($nestLimit=null) {

		if (!$this->considerAutoHeight) return 0;

		$maxHeight = 0;

		foreach ($this->getCells() as $cell) {

			if ($cell instanceof OOPHPDF_ITable) {
				$cellHeight = $cell->getHeightAuto($nestLimit);
			} else {
				$cellHeight = $cell->getHeightAuto();
			}

			if ($cellHeight > $maxHeight) $maxHeight = $cellHeight;

		}

		return $maxHeight;

	}

	/**
	*Set all cells' heights to the maximum individual cell auto-height found in the row
	*/
	public function setHeightToAuto() {

		if (!$this->considerAutoHeight) return $this;

		$height = $this->getHeightAuto();
		$this->setHeight($height);

		return $this;

	}

	/**
	*Set all cells' heights to specified value, effectively setting row height
	*/
	public function setHeight($height) {

		foreach ($this->getCells() as $cell) {
			if (method_exists($cell,'setHeight')) {
				$cell->setHeight($height);
			}
		}

		return $this;

	}



	public function getPostDrawLineBreak() {
		return $this->postDrawLineBreak;
	}

	public function setPostDrawLineBreak($break) {
		if (is_bool($break)) $this->postDrawLineBreak = $break;
		return $this;
	}


	public function getConsiderAutoHeight($consider) {
		return $this->considerAutoHeight;
	}

	public function setConsiderAutoHeight($consider) {
		if (is_bool($consider)) $this->considerAutoHeight = $consider;
		return $this;
	}



	//If passing a single parameter which is an array, wrap it in another array first!
	//If you don't, it will be passed as an array of parameters instead of a single parameter
	public function run_cell_func($funcName,$params) {

		if (!is_array($params)) $params = array($params);

		foreach ($this->getCells() as $cell) {
			if (method_exists($cell,$funcName)) {
				call_user_func_array(array($cell,$funcName), $params);
			} elseif (method_exists($cell,'run_cell_func')) {
				$cell->run_cell_func($funcName,$params);
			}
		}

		return $this;

	}


	public function getSkip() {
		return $this->skip;
	}

	public function setSkip($skipVal) {
		$this->skip = $skipVal;
		return $this;
	}


	public function setLnResetX($reset) {
		if (is_bool($reset)) $this->lnResetX = $reset;
		return $this;
	}

	public function setLnResetY($reset) {
		if (is_bool($reset)) $this->lnResetY = $reset;
		return $this;
	}


	public function drawAtPosition($x, $y) {
		$this->pdf->setXY($x, $y);
		$this->draw();
	}

	public function draw() {

		$startX = $this->pdf->getX();
		$startY = $this->pdf->getY();


		foreach ($this->getCells() as $cell) {
			$cell->draw();
		}

		if ($this->postDrawLineBreak) $this->pdf->ln();


		if ($this->lnResetX) {
			$setX = $startX;
		} else {
			$setX = $this->pdf->getX();
		}

		if ($this->lnResetY) {
			$setY = $startY;
		} else {
			$setY = $this->pdf->getY();
		}

		$this->pdf->setXY($setX,$setY);


		return $this;

	}

}
