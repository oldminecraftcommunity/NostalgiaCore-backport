<?php

class TNTBlock extends SolidBlock{
	public function __construct(){
		parent::__construct(TNT, 0, "TNT");
		$this->hardness = 0;
		$this->isActivable = true;
	}
	
	public function onActivate(Item $item, Player $player){
		if($item->getID() === FLINT_STEEL){
			if(($player->gamemode & 0x01) === 0){
				$item->useOn($this);
			}
			$data = array(
				"x" => $this->x + 0.5,
				"y" => $this->y + 0.5,
				"z" => $this->z + 0.5,
				"power" => 3,
				"fuse" => 20 * 4, //4 seconds
			);
			$this->level->setBlock($this, new AirBlock(), false, false, true);
			$e = ServerAPI::request()->api->entity->add($this->level, ENTITY_OBJECT, OBJECT_PRIMEDTNT, $data);
			ServerAPI::request()->api->entity->spawnToAll($e);
			return true;
		}
		return false;
	}
}