<?php

class DoubleSlabBlock extends SolidBlock{
	public function __construct($meta = 0){
		parent::__construct(DOUBLE_SLAB, $meta, "Double Slab");
		$names = array(
			0 => "Stone",
			1 => "Sandstone",
			2 => "Wooden",
			3 => "Cobblestone",
			4 => "Brick",
			5 => "Stone Brick",
			6 => "Quartz",
		);
		$this->name = "Double " . $names[$this->meta & 0x07] . " Slab";
		$this->hardness = 30;
	}

	public function getBreakTime(Item $item, Player $player){
		if(($player->gamemode & 0x01) === 0x01){
			return 0.20;
		}		
		switch($item->getPickaxeLevel()){
			case 5:
				return 0.4;
			case 4:
				return 0.5;
			case 3:
				return 0.75;
			case 2:
				return 0.25;
			case 1:
				return 1.5;
			default:
				return 10;
		}
	}
	
	public function getDrops(Item $item, Player $player){
		if($item->getPickaxeLevel() >= 1){
			return array(
				array(SLAB, $this->meta & 0x07, 2),
			);
		}else{
			return array();
		}
	}
	
}
