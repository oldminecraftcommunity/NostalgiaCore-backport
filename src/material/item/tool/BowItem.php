<?php

class BowItem extends Item{
	public function __construct($meta = 0, $count = 1){
		parent::__construct(BOW, 0, $count, "Bow");
	}
	public function isTool(){ //it is tool too
		return true;
	}
}