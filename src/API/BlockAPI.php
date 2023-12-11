<?php

class BlockAPI{
	private $server;
	private $scheduledUpdates = [];
	private $randomUpdates = [];

	function __construct(){
		$this->server = ServerAPI::request();
	}

	public static function get($id, $meta = 0, $v = false){
		if(isset(Block::$class[$id])){
			$classname = Block::$class[$id];
			$b = new $classname($meta);
		}else{
			$b = new GenericBlock((int) $id, $meta);
		}
		if($v instanceof Position){
			$b->position($v);
		}
		return $b;
	}

	public function init(){
		$this->server->schedule(1, [$this, "blockUpdateTick"], [], true);
		$this->server->api->console->register("setblock", "<x> <y> <z> <block[:damage]>", [$this, "commandHandler"]);
		$this->server->api->console->register("give", "<player> <item[:damage]> [amount]", [$this, "commandHandler"]);
		$this->server->api->console->register("id", "Check id of item", [$this, "commandHandler"]);
		$this->server->api->console->cmdWhitelist("id");
	}

	public function commandHandler($cmd, $args, $issuer, $alias){
		$output = "";
		switch($cmd){
			case "id":
				if(!($issuer instanceof Player)){
					$output .= "Run this command in-game!";
					break;
				}
				$itemheld = $issuer->getSlot($issuer->slot);
				$output = self::getItem($itemheld->getID(), $itemheld->getMetadata())."";
				break;
			case "setblock":
				if(!($issuer instanceof Player)){
					if(count($args) < 5){
						$output .= "Usage: /setblock <x> <y> <z> <level> <block[:damage]>";
						break;
					}else{
						if(!is_numeric($args[0]) or !is_numeric($args[1]) or !is_numeric($args[2])){
							$output .= "You need to use a numeric coords!";
							break;
						}
						$level = $this->server->api->level->get($args[3]);
						if($level === false){
							$output .= "Unknown level";
							break;
						}
						$block = self::fromString($args[4])->getBlock();
					}
				}else{
					if(count($args) < 4){
						$output .= "Usage: /setblock <x> <y> <z> <block[:damage]>";
						break;
					}
					
					$level = $issuer->entity->level;
					$block = self::fromString($args[3])->getBlock();
				}

				$coords = [];
				for($i = 0; $i < 3; $i++){
					if($args[$i] === '~'){
						if($i === 0) $coord = $issuer->entity->x;
						elseif($i === 1) $coord = $issuer->entity->y;
						else $coord = $issuer->entity->z;
						$coords[$i] = round($coord, 0);
					}
					elseif(is_numeric($args[$i])){
						if($args[$i] > 0 and $args[$i] < 255){
							$coords[$i] = $args[$i];
						}
					}
				}
				if(!isset($coords[2])){
					$output .= "Usage: /setblock <x> <y> <z> <block[:damage]>";
					break;
				}

				$pos = new Position($coords[0], $coords[1], $coords[2], $level);
				if(!($block instanceof Block)){
					$output .= "Usage: /setblock <x> <y> <z> <block[:damage]>";
					break;
				}else{
					$level->setBlock($pos, $block, true, false, true);
					$output .= "Placed $block in ".implode(", ", $coords).", w:".$level->getName();
				}
				break;
			case "give":
				$player = $this->server->api->player->get(nullsafe($args[0], ""));
				
				if($player instanceof Player){			
					$item = self::fromString(nullsafe($args[1], ""));
					if(($player->gamemode & 0x01) === 0x01){
						$output .= "Player is in creative mode.";
						break;
					}
					if($item->getID() === 0){
						$output .= "You cannot give an air block to a player.";
						break;
					}
					
					if(!isset($args[2])){
						$item->count = $item->getMaxStackSize();
					}else{
						$item->count = (int) $args[2];
					}
					
					$player->addItem($item->getID(), $item->getMetadata(), $item->count);
					$output .= "Giving ".$item->count." of ".$item->getName()." (".$item->getID().":".$item->getMetadata().") to ".$player->username;
					break;
				}else{
					$item = self::fromString(nullsafe($args[0], ""));	
					if(!($issuer instanceof Player)){
						return "You cant give an item to a non-player.";
					}
					if(($issuer->gamemode & 0x01) === 0x01){
						$output .= "You are in creative mode.";
						break;
					}
					if($item->getID() === 0){
						$output .= "You cannot give an air block to a player.";
						break;
					}

					if(!isset($args[1])){
						$item->count = $item->getMaxStackSize();
					}else{
						$item->count = (int) $args[1];
					}

					$issuer->addItem($item->getID(), $item->getMetadata(), $item->count);
					$output .= "Giving ".$item->count." of ".$item->getName()." (".$item->getID().":".$item->getMetadata().") to ".$issuer->username;
					break;
				}
				break;
		}
		return $output;
	}

	public static function fromString($str, $multiple = false){
		if($multiple === true){
			$blocks = [];
			foreach(explode(",", $str) as $b){
				$blocks[] = BlockAPI::fromString($b, false);
			}
			return $blocks;
		}else{
			$b = explode(":", str_replace(" ", "_", trim($str)));
			if(!isset($b[1])){
				$meta = 0;
			}else{
				$meta = ((int) $b[1]) & 0xFFFF;
			}

			if(defined(strtoupper($b[0]))){
				$explodedString = explode(":", constant(strtoupper($b[0])));
				if(count($explodedString) === 2){
					$meta = (int) $explodedString[1];
				}
				$item = BlockAPI::getItem(constant(strtoupper($b[0])), $meta);
				if($item->getID() === AIR and strtoupper($b[0]) !== "AIR"){
					$item = BlockAPI::getItem(((int) $b[0]) & 0xFFFF, $meta);
				}
			}else{
				$item = BlockAPI::getItem(((int) $b[0]) & 0xFFFF, $meta);
			}
			return $item;
		}
	}

	public static function getItem($id, $meta = 0, $count = 1){
		$id = (int) $id;
		if(isset(Item::$class[$id])){
			$classname = Item::$class[$id];
			$i = new $classname($meta, $count);
		}else{
			$i = new Item($id, $meta, $count);
		}
		return $i;
	}

	public function playerBlockBreak(Player $player, Vector3 $vector){
		$target = $player->level->getBlock($vector);
		$item = $player->getSlot($player->slot);

		if($this->server->api->dhandle("player.block.touch", ["type" => "break", "player" => $player, "target" => $target, "item" => $item]) === false){
			if($this->server->api->dhandle("player.block.break.bypass", ["player" => $player, "target" => $target, "item" => $item]) !== true){
				return $this->cancelAction($target, $player, false);
			}
		}

		if((!$target->isBreakable($item, $player) and $this->server->api->dhandle("player.block.break.invalid", ["player" => $player, "target" => $target, "item" => $item]) !== true) or ($player->gamemode & 0x02) === 0x02 or (($player->lastBreak - $player->getLag() / 1000) + $target->getBreakTime($item, $player) - 0.2) >= microtime(true)){
			if($this->server->api->dhandle("player.block.break.bypass", ["player" => $player, "target" => $target, "item" => $item]) !== true){
				return $this->cancelAction($target, $player, false);
			}
		}
		$player->lastBreak = microtime(true);

		if($this->server->api->dhandle("player.block.break", ["player" => $player, "target" => $target, "item" => $item]) !== false){
			$drops = $target->getDrops($item, $player);
			if($target->onBreak($item, $player) === false){
				return $this->cancelAction($target, $player, false);
			}
			if(($player->gamemode & 0x01) === 0 and $item->useOn($target) and $item->getMetadata() >= $item->getMaxDurability()){
				$player->setSlot($player->slot, new Item(AIR, 0, 0), false);
			}
		}else{
			return $this->cancelAction($target, $player, false);
		}


		if(is_array($drops) && ($player->gamemode & 0x01) === 0x00 and count($drops) > 0){
			foreach($drops as $drop){
				$this->server->api->entity->drop(new Position($target->x + 0.5, $target->y, $target->z + 0.5, $target->level), BlockAPI::getItem($drop[0] & 0xFFFF, $drop[1] & 0xFFFF, $drop[2]));
			}
		}
		return false;
	}

	private function cancelAction(Block $block, Player $player, $send = true){
		$pk = new UpdateBlockPacket;
		$pk->x = $block->x;
		$pk->y = $block->y;
		$pk->z = $block->z;
		$pk->block = $block->getID();
		$pk->meta = $block->getMetadata();
		$player->dataPacket($pk);
		if($send === true){
			$player->sendInventorySlot($player->slot);
		}
		return false;
	}

	public function playerBlockAction(Player $player, Vector3 $vector, $face, $fx, $fy, $fz){
		if($face < 0 or $face > 5){
			return false;
		}

		$target = $player->level->getBlock($vector);
		$block = $target->getSide($face);
		$item = $player->getSlot($player->slot);

		if($target->getID() === AIR and $this->server->api->dhandle("player.block.place.invalid", ["player" => $player, "block" => $block, "target" => $target, "item" => $item]) !== true){ //If no block exists or not allowed in CREATIVE
			if($this->server->api->dhandle("player.block.place.bypass", ["player" => $player, "block" => $block, "target" => $target, "item" => $item]) !== true){
				$this->cancelAction($target, $player);
				return $this->cancelAction($block, $player);
			}
		}

		if($this->server->api->dhandle("player.block.touch", ["type" => "place", "player" => $player, "block" => $block, "target" => $target, "item" => $item]) === false){
			if($this->server->api->dhandle("player.block.place.bypass", ["player" => $player, "block" => $block, "target" => $target, "item" => $item]) !== true){
				return $this->cancelAction($block, $player);
			}
		}
		$this->blockUpdate($target, BLOCK_UPDATE_TOUCH);

		if($target->isActivable === true){
			if($this->server->api->dhandle("player.block.activate", ["player" => $player, "block" => $block, "target" => $target, "item" => $item]) !== false and $target->onActivate($item, $player) === true){
				return false;
			}
		}

		if(($player->gamemode & 0x02) === 0x02){ //Adventure mode!!
			if($this->server->api->dhandle("player.block.place.bypass", ["player" => $player, "block" => $block, "target" => $target, "item" => $item]) !== true){
				return $this->cancelAction($block, $player, false);
			}
		}

		if($block->y > 127 or $block->y < 0){
			return false;
		}

		if($item->isActivable === true and $item->onActivate($player->level, $player, $block, $target, $face, $fx, $fy, $fz) === true){
			if($item->count <= 0){
				$player->setSlot($player->slot, BlockAPI::getItem(AIR, 0, 0), false);
			}
			return false;
		}

		if($item->isPlaceable()){
			$hand = $item->getBlock();
			$hand->position($block);
		}elseif($block->getID() === FIRE){
			$player->level->setBlock($block, new AirBlock(), true, false, true);
			return false;
		}else{
			return $this->cancelAction($block, $player, false);
		}

		if(!($block->isReplaceable === true or ($hand->getID() === SLAB and $block->getID() === SLAB))){
			return $this->cancelAction($block, $player, false);
		}

		if($target->isReplaceable === true){
			$block = $target;
			$hand->position($block);
			//$face = -1;
		}

		if($hand->isSolid === true and $player->entity->inBlock($block)){
			return $this->cancelAction($block, $player, false); //Entity in block
		}

		if($this->server->api->dhandle("player.block.place", ["player" => $player, "block" => $block, "target" => $target, "item" => $item]) === false){
			return $this->cancelAction($block, $player);
		}elseif($hand->place($item, $player, $block, $target, $face, $fx, $fy, $fz) === false){
			return $this->cancelAction($block, $player, false);
		}
		if($hand->getID() === SIGN_POST or $hand->getID() === WALL_SIGN){
			$t = $this->server->api->tile->addSign($player->level, $block->x, $block->y, $block->z);
			$t->data["creator"] = $player->username;
		}

		if(($player->gamemode & 0x01) === 0x00){
			--$item->count;
			if($item->count <= 0){
				$player->setSlot($player->slot, BlockAPI::getItem(AIR, 0, 0), false);
			}
		}

		return false;
	}

	public function blockUpdate(Position $pos, $type = BLOCK_UPDATE_NORMAL){
		if(!($pos instanceof Block)){
			$block = $pos->level->getBlock($pos);
		}else{
			$pos = new Position($pos->x, $pos->y, $pos->z, $pos->level);
			$block = $pos->level->getBlock($pos);
		}
		if($block === false){
			return false;
		}

		$level = $block->onUpdate($type);
		if($level === BLOCK_UPDATE_NORMAL){
			$this->blockUpdateAround($block, $level);
			$this->server->api->entity->updateRadius($pos, 1);
		}elseif($level === BLOCK_UPDATE_RANDOM){
			//$this->nextRandomUpdate($pos); old version, change back if any issues will start to happen with this one(please note that old version makes a lot of lag)
			$this->scheduleBlockUpdate($pos, (mt_rand(25, 75) + Utils::getRandomUpdateTicks() * 0.05) / 0.05, BLOCK_UPDATE_RANDOM);
		}
		return $level;
	}
	public function blockUpdateAround(Position $pos, $type = BLOCK_UPDATE_NORMAL, $delay = false){
		if($delay !== false){
			$this->scheduleBlockUpdate($pos->getSide(0), $delay, $type);
			$this->scheduleBlockUpdate($pos->getSide(1), $delay, $type);
			$this->scheduleBlockUpdate($pos->getSide(2), $delay, $type);
			$this->scheduleBlockUpdate($pos->getSide(3), $delay, $type);
			$this->scheduleBlockUpdate($pos->getSide(4), $delay, $type);
			$this->scheduleBlockUpdate($pos->getSide(5), $delay, $type);
		}else{
			$this->blockUpdate($pos->getSide(0), $type);
			$this->blockUpdate($pos->getSide(1), $type);
			$this->blockUpdate($pos->getSide(2), $type);
			$this->blockUpdate($pos->getSide(3), $type);
			$this->blockUpdate($pos->getSide(4), $type);
			$this->blockUpdate($pos->getSide(5), $type);
		}
	}

	public function scheduleBlockUpdate(Position $pos, $delay, $type = BLOCK_UPDATE_SCHEDULED){
		$type = (int) $type;
		if($delay < 0){
			return false;
		}

		$index = $pos->x . "." . $pos->y . "." . $pos->z . "." . $pos->level->getName() . "." . $type;
		$delay = microtime(true) + $delay * 0.05;
		if(!isset($this->scheduledUpdates[$index])){
			$this->scheduledUpdates[$index] = $pos;
			$this->server->query("INSERT INTO blockUpdates (x, y, z, level, type, delay) VALUES (" . $pos->x . ", " . $pos->y . ", " . $pos->z . ", '" . $pos->level->getName() . "', " . $type . ", " . $delay . ");");
			return true;
		}
		return false;
	}

	public function nextRandomUpdate(Position $pos){
		if(!isset($this->scheduledUpdates[$pos->x . "." . $pos->y . "." . $pos->z . "." . $pos->level->getName() . "." . BLOCK_UPDATE_RANDOM])){
			$time = microtime(true);
			$offset = 0;
			while(true){
				$t = $offset + Utils::getRandomUpdateTicks() * 0.05;
				$update = $this->server->query("SELECT COUNT(*) FROM blockUpdates WHERE level = '" . $pos->level->getName() . "' AND type = " . BLOCK_UPDATE_RANDOM . " AND delay >= " . ($time + $t - 1) . " AND delay <= " . ($time + $t + 1) . ";");
				if($update instanceof SQLite3Result){
					$update = $update->fetchArray(SQLITE3_NUM);
					if($update[0] < 3){
						break;
					}
				}else{
					break;
				}
				$offset += mt_rand(25, 75);
			}
			$this->scheduleBlockUpdate($pos, $t / 0.05, BLOCK_UPDATE_RANDOM);
		}
	}

	public function blockUpdateTick(){
		$time = microtime(true);
		if(count($this->scheduledUpdates) > 0){
			$update = $this->server->query("SELECT x,y,z,level,type FROM blockUpdates WHERE delay <= " . $time . ";");
			if($update instanceof SQLite3Result){
				$upp = [];
				while(($up = $update->fetchArray(SQLITE3_ASSOC)) !== false){
					$index = $up["x"] . "." . $up["y"] . "." . $up["z"] . "." . $up["level"] . "." . $up["type"];
					if(isset($this->scheduledUpdates[$index])){
						$upp[] = [(int) $up["type"], $this->scheduledUpdates[$index]];
						unset($this->scheduledUpdates[$index]);
					}
				}
				$this->server->query("DELETE FROM blockUpdates WHERE delay <= " . $time . ";");
				foreach($upp as $b){
					$this->blockUpdate($b[1], $b[0]);
				}
			}
		}
	}
}