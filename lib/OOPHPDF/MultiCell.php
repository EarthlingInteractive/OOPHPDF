<?php

class OOPHPDF_MultiCell extends OOPHPDF_Object implements OOPHPDF_Drawable {
	private $width;
	private $height;

	//Effective sizes will be the ones that are viewable externally at all times.
	//Effective sizes will be the same as their simple size counterparts at 0 or 180 degrees rotation.
	//Effective sizes will be the inverse of their simple size counterparts at 90 or 270 degrees rotation.
	private $effectiveWidth;
	private $effectiveHeight;

	private $text = '';

	private $rotation = 0;

	private $cellPaddingLeft = 0;
	private $cellPaddingTop = 0;
	private $cellPaddingRight = 0;
	private $cellPaddingBottom = 0;

	private $fontFamily = '';
	private $fontStyle = '';
	private $fontSize;

	private $textColor = array(0, 0, 0);

	private $alignHorizontal = 'L';
	private $alignVertical = 'T';

	private $fillColor;

	private $borderColor = array(0, 0, 0);
	private $borderWidth = 0;
	private $border = '';

	private $ln = 1;

	private $fitCell = false;

	private $translationMap = array(
		0 => 'B',
		1 => 'L',
		2 => 'T',
		3 => 'R'
	);

	public function getRotation() {
		return $this->rotation;
	}

	/**
	*$rot is the number of 90 degree clockwise intervals to rotate.  Accepted values are 0-3
	*/
	public function setRotation($rot) {

		if (!is_int($rot) || $rot < 0 || $rot > 3) {
			throw new Exception('Invalid rotation value supplied to Multicell.  Accepted values are (int)0-3');
		}

		$this->rotation = $rot;

		return $this;

	}


	/**
	*Translates sequentially indexed arrays, shifting all keys in a specific direction.
	*Keys loop to the other side if they exceed upper or lower limit - ((maxKey+1) === minKey) && ((minKey-1) === maxKey)
	*/
	protected function translateSequentialArray($minKey, $maxKey, $arr, $mod=1) {
		$arrCopy = $arr;
		$newArr = array();
		foreach ($arrCopy as $key=>$val) {

			if (!is_int($key)) continue;

			if ($key+$mod > $maxKey) {
				$dif = ($key+$mod)-$maxKey;
				$newKey = $minKey+($dif-1);
			} elseif ($key+$mod < $minKey) {
				$dif = $minKey-($key+$mod);
				$newKey = $maxKey-($dif-1);
			} else {
				$newKey = $key+$mod;
			}

			$newArr[$newKey] = $val;

		}
		return $newArr;

	}

	/**
	*Interprets border data and returns modified data based on $this->translationMap
	*/
	public function getTranslatedBorder() {

		$border = $this->border;
		$rotation = $this->getRotation();

		//Do not perform translations if object is not rotated or border is nonexistent or the same on all sides
		if ($rotation === 0 || is_int($border)) return $border;


		$keymap = $this->translationMap;


		if (empty($border)) return $border;

		//String borders are lists containing any or all of the letters L(eft), T(op), R(ight), B(ottom)
		if (is_string($border)) {

			$border = str_split($border);

			//Convert string border to array, ignoring unrecognized letters (assumed as errors)
			$bordersToTranslate = array();
			foreach ($border as $key => $letter) {
				$letter = strtoupper($letter);
				if (!in_array($letter,$keymap)) continue;
				$bordersToTranslate[array_search($letter,$keymap)] = $letter;
			}

			//Translate border array keys
			$border = $this->translateSequentialArray(0,count($keymap)-1,$bordersToTranslate,$rotation);

			//Use translation map to return values to their correct letter values based on key
			foreach ($border as $key=>$val) $border[$key] = $keymap[$key];

			//Return border to original (string) format
			$border = strtoupper(implode('',$border));

		} elseif (is_array($border)) {

			$bordersToTranslate = array();

			//Fill translation array with border data, with a key for each side
			foreach ($border as $side => $sideData) {
				if (strlen($side) > 1) {
					$newSides = str_split($side);
					foreach ($newSides as $newSide) {
						$bordersToTranslate[array_search($newSide,$keymap)] = $sideData;
					}
				} else {
					$bordersToTranslate[array_search($side,$keymap)] = $sideData;
				}
			}

			//Translate border array keys
			$border = $this->translateSequentialArray(0,count($keymap)-1,$bordersToTranslate,$rotation);

			//Use translation map to return values to their correct letter values based on key
			foreach ($border as $sideNum=>$sideData) {
				$border[$keymap[$sideNum]] = $sideData;
				unset($border[$sideNum]);
			}

		}

		return $border;

	}

	public function getTranslatedCellPaddings() {

		$paddings = $this->getCellPaddings();
		$rotation = $this->getRotation();

		$keymap = $this->translationMap;

		//Fill translation array
		$paddingsToTranslate = array();
		foreach ($paddings as $side => $sideData) {
			$paddingsToTranslate[array_search($side,$keymap)] = $sideData;
		}

		//Translate array keys
		$paddings = $this->translateSequentialArray(0,count($keymap)-1,$paddingsToTranslate,$rotation);

		//Use translation map to return values to their correct letter values based on key
		foreach ($paddings as $sideNum => $sideData) {
			$paddings[$keymap[$sideNum]] = $sideData;
			unset($paddings[$sideNum]);
		}

		return $paddings;

	}



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



	public function getCellPaddings() {
		return array(
			'L' => $this->getCellPaddingLeft(),
			'T' => $this->getCellPaddingTop(),
			'R' => $this->getCellPaddingRight(),
			'B' => $this->getCellPaddingBottom()
		);
	}

	public function setCellPaddings($left = '', $top = '', $right = '', $bottom = '') {

		foreach (get_defined_vars() as $key=>$val) {
			if ($val !== '') {
				$funcName = 'setCellPadding'.$key;
				if (method_exists($this,$funcName)) {
					call_user_func_array(array($this,$funcName),array($val));
				}
			}
		}

		return $this;

	}



	public function getFontFamily() {
		return $this->fontFamily;
	}

	public function setFontFamily($family) {
		$this->fontFamily = $family;
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



	public function getAlignVertical() {
		return $this->alignVertical;
	}

	public function setAlignVertical($align) {
		$this->alignVertical = $align;
		return $this;
	}


	//True if rotation is 1 or 3 (90 or 270 degrees)
	//False if rotation is 0 or 2 (0 or 180 degrees)
	public function isRotationVertical() {
		return ($this->getRotation()%2);
	}


	protected function getActualWidth() {
		return $this->width;
	}

	public function getWidth() {
		return $this->effectiveWidth;
	}

	public function setWidth($width) {
		$this->effectiveWidth = $width;
		return $this;
	}



	protected function getActualHeight() {
		return $this->height;
	}

	public function getHeight() {
		return isset($this->effectiveHeight) ? $this->effectiveHeight : 0;
	}

	public function setHeight($height) {
		$this->effectiveHeight = $height;
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



	public function getBorderColorArray() {
		return $this->borderColor;
	}

	public function setBorderColorArray($color) {
		$this->borderColor = $color;
		return $this;
	}



	public function getBorderWidth() {
		return $this->borderWidth;
	}

	public function setBorderWidth($borderWidth) {
		$this->borderWidth = $borderWidth;
		return $this;
	}



	public function getBorder() {
		return $this->border;
	}

	public function setBorder($border) {
		$this->border = $border;
		return $this;
	}



	public function getLn() {
		return $this->ln;
	}

	public function setLn($ln) {
		$this->ln = $ln;
		return $this;
	}



	public function getFitCell() {
		return $this->fitCell;
	}

	public function setFitCell($fitCell) {
		$this->fitCell = $fitCell;
		return $this;
	}


	//Doesn't work properly for rotated objects at this point
	public function getWidthAuto() {
		$this->setupContext(false);
		$paddings = $this->getTranslatedCellPaddings();

		return $this->pdf->GetStringWidth($this->getText()) + $paddings['L'] + $paddings['R'] + 0.01;
	}

	public function setWidthToAuto() {
		$this->setWidth($this->getWidthAuto());
		return $this;
	}



	public function getHeightAuto() {
		$this->setupContext(false);

		if (!$this->isRotationVertical()) {
			return $this->pdf->getStringHeight($this->getWidth(), $this->getText(), true, true, '', $this->getBorder()) + 0.01;
		} else {
			return $this->getWidthAuto();
		}
	}

	public function setHeightToAuto() {
		$this->setHeight($this->getHeightAuto());
		return $this;
	}



	public function drawAtPosition($x, $y) {
		$this->setupContext(true);

		$startingX = $x;
		$startingY = $y;

		$width = $this->getWidth();
		$height = $this->getHeight();

		$border = $this->getTranslatedBorder();


		if ($this->rotation != 0) {

			//Set rotation anchor coordinates to middle of cell
			$anchorX = $x+($width/2);
			$anchorY = $y+($height/2);

			$this->pdf->startTransform();
			$this->pdf->rotate($this->getRotation()*90, $anchorX, $anchorY);

			//If rotation is vertical, adjust values for drawing routine
			if ($this->isRotationVertical()) {

				//Flip width and height
				$tempWidth = $width;
				$width = $height;
				$height = $tempWidth;
				unset($tempWidth);

				//Adjust drawing start point
				$y = $anchorY-($height/2);
				$x = $anchorX-($width/2);

			}

		}

		$this->pdf->MultiCell($width, $height, $this->getText(), $border, $this->getAlignHorizontal(), $this->getFillColorArray() !== null, $this->getLn(), $x, $y, true, 0, false, true, $height, $this->getAlignVertical(), $this->getFitCell());

		if ($this->rotation != 0) {
			$this->pdf->stopTransform();
			$this->pdf->setXY($startingX+$height, $startingY);
		}

		return $this;
	}

	public function draw() {
		$this->drawAtPosition($this->pdf->getX(), $this->pdf->getY());
	}



	private function setupContext($drawing) {

		$paddings = $this->getTranslatedCellPaddings();

		$this->pdf->SetFont($this->getFontFamily(), $this->getFontStyle(), $this->getFontSize(), '', 'default', $drawing);
		$this->pdf->SetTextColorArray($this->getTextColorArray());

		$this->pdf->setCellPaddings($paddings['L'], $paddings['T'], $paddings['R'], $paddings['B']);

		$this->pdf->SetDrawColorArray($this->getBorderColorArray());
		$this->pdf->SetLineWidth($this->getBorderWidth());

		if ($drawing) {
			$fillColor = $this->getFillColorArray();
			if ($fillColor !== null) {
				$this->pdf->SetFillColorArray($fillColor);
			}
		}

	}
}
