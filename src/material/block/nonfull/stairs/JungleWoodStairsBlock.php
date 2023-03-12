<?php

class JungleWoodStairsBlock extends StairBlock{
	public function __construct($meta = 0){
		parent::__construct(JUNGLE_WOOD_STAIRS, $meta, "Jungle Wood Stairs");
	}

	public function getDrops(Item $item, Player $player){
		return array(
			array($this->id, 0, 1),
		);
	}
}