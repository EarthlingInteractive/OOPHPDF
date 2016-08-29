<?php
/**
*
*/
class OOPHPDF_Table extends OOPHPDF_Object implements OOPHPDF_Drawable, OOPHPDF_ITable {

	protected $rows;

	//Indicates whether autoHeight should be considered during calculations
	protected $considerAutoHeight = true;

	//Reset properties indicate whether to return cursor to origin after drawing
	protected $lnResetX = false;
	protected $lnResetY = false;

	//Flag indicating whether a page break is currently underway
	protected $midPageBreak;



	public function __construct(TCPDF $pdf, array $rowsToAdd = array()) {

		parent::__construct($pdf);

		if (sizeof($rowsToAdd)) {
			foreach ($rowsToAdd as $rowToAdd) {
				if ($rowToAdd instanceof OOPHPDF_Row) {
					$this->addRow($rowToAdd);
				}
			}
		} else {
			$this->rows = array();
		}

	}

	public function __clone() {
		foreach ($this->getRows() as $rowKey => $row) {
			$this->replaceRow($rowKey, clone $row);
		}
	}



	public function addRow(OOPHPDF_Row $rowToAdd) {
		$this->rows[] = $rowToAdd;
		return $this;
	}

	//Add row at specific key, shifting following keys back
	public function addRowAt(OOPHPDF_Row $rowToAdd,$insertionKey) {
		array_splice($this->rows,$insertionKey,0,array($rowToAdd));
		return $this;
	}

	public function removeRow($rowKey, $uncheckedRowKey = null) {
		if ($this->hasRow($rowKey)) unset($this->rows[$rowKey]);
		return $this;
	}

	public function replaceRow($rowKey, OOPHPDF_Row $replacementRow) {
		$this->removeRow($rowKey);
		$this->addRowAt($replacementRow, $rowKey);
		return $this;
	}

	public function clearRows() {
		$this->rows = array();
		return $this;
	}


	public function hasRow($rowKey) {
		return (isset($this->rows[$rowKey]));
	}

	public function getMaxKey() {
		return end((array_keys($this->getRows())));
	}


	public function getRow($rowKey) {
		if ($this->hasRow($rowKey)) {
			return $this->rows[$rowKey];
		} else {
			return null;
		}
	}

	public function getRows() {
		return $this->rows;
	}

	public function getRowCount() {
		return sizeof($this->getRows());
	}



	/**
	*Returns the width of the widest row in the table
	*/
	public function getWidthAuto() {

		$maxWidth = 0;

		foreach ($this->getRows() as $row) {
			$rowWidth = $row->getWidthAuto();
			if ($rowWidth > $maxWidth) $maxWidth = $rowWidth;
		}

		return $maxWidth;

	}

	/**
	*Set width of all cells to specified value
	*/
	public function setCellWidth($width) {

		foreach ($this->getRows() as $row) {
			$row->setCellWidth($width);
		}

		return $this;

	}

	//Returns the height of the tallest row in the table
	public function getMaxRowHeight() {

		$maxHeight = 0;

		foreach ($this->getRows() as $row) {
			$rowHeight = $row->getHeightAuto();
			if ($rowHeight > $maxHeight) $maxHeight = $rowHeight;
		}

		return $maxHeight;

	}
	
	public function getHeight() {
		$height = 0;
		foreach ($this->getRows() as $row) {
			$height += $row->getHeight();
		}
		return $height;
	}

	//Returns the total auto-height of all rows in the table
	public function getHeightAuto() {

		if (!$this->considerAutoHeight) return 0;

		$height = 0;

		foreach ($this->getRows() as $row) {
			$height += $row->getHeightAuto();
		}

		return $height;

	}


	/**
	*Returns the total auto-height of all rows in a given section of the table
	*/
	public function getChunkHeight($rowStart,$rowCount=null) {

		if (is_null($rowCount)) $rowCount = $this->getRowCount();

		$height = 0;
		$countedRows = 0;
		$i = $rowStart;

		while ($countedRows < $rowCount) {
			if ($this->hasRow($i)) {
				$height += $this->getRow($i)->getHeight();
				++$countedRows;
			} elseif ( $i >= $this->getMaxKey() ) {
				break;
			}
			++$i;
		}

		return $height;

	}

	/**
	*Sets each row to its individual auto-height
	*/
	public function setRowHeightToAuto() {

		foreach ($this->getRows() as $row) {
			$row->setHeightToAuto();
		}

		return $this;

	}

	/**
	*Sets each row to a specified height
	*/
	public function setRowHeight($height) {

		foreach ($this->getRows() as $row) {
			$row->setHeight($height);
		}

		return $this;

	}


	public function getConsiderAutoHeight($consider) {
		return $this->considerAutoHeight;
	}

	public function setConsiderAutoHeight($consider) {
		if (is_bool($consider)) $this->considerAutoHeight = $consider;
		return $this;
	}


	//Runs passed function name and parameters through call_user_func_array on each cell in each row held
	//If passing a single parameter which is an array, wrap it in another array first!
	//If you don't, it will be passed as an array of parameters instead of a single parameter
	public function run_cell_func($funcName,$params) {

		if (!is_array($params)) $params = array($params);

		foreach ($this->getRows() as $row) {
			foreach ($row->getCells() as $cell) {
				if (method_exists($cell,$funcName)) {
					call_user_func_array(array($cell,$funcName), $params);
				} elseif (method_exists($cell,'run_cell_func')) {
					$cell->run_cell_func($funcName,$params);
				}
			}
		}

		return $this;

	}


	//Runs passed function name and parameters through call_user_func_array on each cell with given column index
	//If passing a single parameter which is an array, wrap it in another array first!
	//If you don't, it will be passed as an array of parameters instead of a single parameter
	public function run_column_func($column,$funcName,$params) {

		if (!is_array($params)) $params = array($params);

		foreach ($this->getRows() as $row) {
			$cell = $row->getCell($column);
			if ($cell) {
				if (method_exists($cell,$funcName)) {
					call_user_func_array(array($cell,$funcName), $params);
				} elseif (method_exists($cell,'run_column_func')) {
					$cell->run_column_func($column,$funcName,$params);
				}
			}
		}

		return $this;

	}

	/**
	*Runs run_column_func() on set of columns using function data passed via a multi-dimensional associative array
	*First dimension keys are expected to be column keys
	*Second dimension should contain two keys: func, params
	*Func element should contain name of function to run
	*Params element should contain parameters for use with function passed in Func element
	*Because run_column_func uses call_user_func_array, params will be converted to array
	*If passing a numerically indexed array as the only parameter, you must wrap it in another array
	*/
	public function run_column_funcs_from_data($columnData) {
		foreach ($columnData as $columnKey => $columnFuncs) {
			foreach ($columnFuncs as $columnFunc) {

				$columnParams = $columnFunc['params'];

				if ( !is_array($columnParams) ) {
					$columnParams = array($columnParams);
				}

				$this->run_column_func( $columnKey, $columnFunc['func'], $columnParams );

			}
		}
	}



	public function setLnResetX($reset) {
		if (is_bool($reset)) $this->lnResetX = $reset;
		return $this;
	}

	public function setLnResetY($reset) {
		if (is_bool($reset)) $this->lnResetY = $reset;
		return $this;
	}


	public function getMidPageBreak() {
		return $this->midPageBreak;
	}
	public function setMidPageBreak($break) {
		$this->midPageBreak = $break;
		return $this;
	}


	public function drawAtPosition($x, $y, $minRowCount = 2, $pageBreakFunc = null) {

		$this->pdf->setXY($x, $y);
		$this->draw($minRowCount, $pageBreakFunc);

	}

	/**
	*Some parameters that are not used directly in draw() may be used in hook functions such as $pageBreakFunc
	*
	*$minRowCount indicates the minimum number of rows needed to fit on a page without a page break
	*$pageBreakFunc is an optionally injected drawing routine to run on fresh prints or added pages
	*$calculateHeightNeededFunc is an optionally injected calculation that will override the default to find the minimum height needed to fit the drawing routine on the page
	*$rowDrawFunc is an optionally injected drawing routine to replace the default
	*$startingIndex is the row to begin drawing at
	*$printFresh=true indicates that this table is first being drawn, and likely is not nested in another table
	*/
	public function draw($minRowCount = 2, $pageBreakFunc = null, $calculateHeightNeededFunc = null, $rowDrawFunc = null, $startingIndex = 0, $printFresh=true) {

		$this->setMidPageBreak($printFresh);

		//Allows $this to be passed into anonymous function
		$thisThing = $this;

		//Function returning the minimum height needed to fit an attempted drawing routine on the current page
		$getMinHeightNeeded = function($row) use ($calculateHeightNeededFunc, $minRowCount, $thisThing) {
			if (is_null($calculateHeightNeededFunc)) {
				$minHeightNeeded = $thisThing->getChunkHeight($row,$minRowCount);
			} else {
				$minHeightNeeded = $calculateHeightNeededFunc($row);
			}
			return $minHeightNeeded;
		};

		//Unset $thisThing to prevent accidental misuse
		unset($thisThing);


		//Only perform drawing procedures if the table has rows
		if ($this->getRowCount() && $this->hasRow($startingIndex)) {

			//Record starting cursor positions
			$startX = $this->pdf->getX();
			$startY = $this->pdf->getY();


			$remainingPageHeight = $this->getPageRemainingHeight();
			$minHeightNeeded = $getMinHeightNeeded($startingIndex);

			//Add a new page if needed
			if ($minHeightNeeded > $remainingPageHeight) {
				$this->pdf->addPage();
			}


			//Indicate that fresh print operations are complete
			$printFresh = false;


			//Draw each row
			foreach ($this->getRows() as $rowKey => $row) {

				//If row is set to be skipped or outside valid key range, continue
				if ($row->getSkip() || $rowKey < $startingIndex) continue;

				$remainingPageHeight = $this->getPageRemainingHeight();

				//If there is not enough space on the current page, indicate that a page break is in process and add a page
				if ($getMinHeightNeeded($rowKey) > $remainingPageHeight) {
					$this->setMidPageBreak(true);
					$this->pdf->addPage();
				}

				//If a new page has been added, run a custom page break routine if it was given
				if ($this->getMidPageBreak() && !is_null($pageBreakFunc)) $pageBreakFunc($rowKey);

				//Draw the row
				if (is_null($rowDrawFunc)) {
					$row->draw();
				} else {
					$rowDrawFunc($rowKey);
				}

				//Indicate that any page breaking that may have occurred is over
				$this->setMidPageBreak(false);

			}


			//Reset cursor to original X position if indicated
			if ($this->lnResetX) {
				$this->pdf->setX($startX);
			}

			//Reset cursor to original Y position if indicated
			if ($this->lnResetY) {
				$this->pdf->setY($startY);
			}

		}

		return $this;

	}

}
