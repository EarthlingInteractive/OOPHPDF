<?php

class OOPHPDF_MultiCell_Group {

  protected $pdf;

  protected $group = array();

  protected $startingX;
  protected $startingY;

  protected $width = 0;
  protected $height = 0;

  protected $spacing = 0;


  public function __construct($pdf) {

    $this->pdf = $pdf;

    $this->startingX = $this->pdf->getX();
    $this->startingy = $this->pdf->getY();

  }

  public function setGroup($group) {
    $this->group = $group;
    return $this;
  }
  public function addItem($item) {
    $this->group[] = $item;
    return $this;
  }
  public function removeItem($item) {
    foreach ($this->group as $key => $existingItem) {
      if ($existingItem === $item) {
        unset($this->group[$key]);
      }
    }
    return $this;
  }

  public function setX($x) {
    $this->startingX = $x;
    return $this;
  }
  public function setY($y) {
    $this->startingY = $y;
    return $this;
  }
  public function setXY($x, $y) {
    $this->setX($x);
    $this->setY($y);
    return $this;
  }

  public function setWidth($w) {
    $this->width = $w;
    return $this;
  }
  public function setHeight($h) {
    $this->height = $h;
    return $this;
  }
  public function setSize($w, $h) {
    $this->setWidth($w);
    $this->setHeight($h);
    return $this;
  }

  public function setSpacing($spacing) {
    $this->spacing = $spacing;
    return $this;
  }

  public function draw() {

    $currentX = $this->startingX;
    $currentY = $this->startingY;
    $remainingHeight = $this->height;

    foreach ($this->group as $textItem) {

      $multicell = new OOPHPDF_MultiCell($this->pdf);

      $multicell
      ->setWidth($this->width)
      ->setLn(2);

      foreach ($textItem as $textPropName => $textPropVal) {

        $funcName = str_replace(' ','',ucwords(trim($textPropName)));

        if (method_exists($multicell, 'set'.$funcName)) {
          call_user_func_array(array($multicell, 'set'.$funcName), array($textPropVal));
        }

      }

      if ($multicell->getHeightAuto() > $remainingHeight) {
        $multicell->setHeight($remainingHeight);
      }

      $multicell->drawAtPosition($currentX, $currentY);

      $currentX = $this->startingX;
      $currentY = $this->pdf->getY() + $this->spacing;
      $remainingHeight = ($this->startingY + $this->height) - $currentY;

      if ($remainingHeight <= 0) {
        return $this;
      }

    }

    return $this;

  }

}

?>
