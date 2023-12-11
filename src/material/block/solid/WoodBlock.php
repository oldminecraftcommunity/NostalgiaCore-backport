<?php

class WoodBlock extends SolidBlock{
	const OAK = 0;
	const SPRUCE = 1;
	const BIRCH = 2;
	
	public function __construct($meta = 0){
		parent::__construct(WOOD, $meta, "Wood");
		$names = array(
			WoodBlock::OAK => "Oak Wood",
			WoodBlock::SPRUCE => "Spruce Wood",
			WoodBlock::BIRCH => "Birch Wood",
		);
		$this->name = $names[$this->meta & 0x03];
		$this->hardness = 10;
	}
	
	public function place(Item $item, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		$faces = array(
			0 => 0,
			1 => 0,
			2 => 0b1000,
			3 => 0b1000,
			4 => 0b0100,
			5 => 0b0100,
		);

		$this->meta = ($this->meta & 0x03) | $faces[$face];
		$this->level->setBlock($block, $this, true, false, true);
		return true;
	}

	public function getDrops(Item $item, Player $player){
		return array(
			array($this->id, $this->meta & 0x03, 1),
		);
	}
}