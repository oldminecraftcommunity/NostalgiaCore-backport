<?php

class PlanksBlock extends SolidBlock{
	public function __construct($meta = 0){
		parent::__construct(PLANKS, $meta, "Wooden Planks");
		$this->hardness = 15;
	}
	
}