<?php

class EntityAPI{
	public $entities;
	private $server;
	private $eCnt = 1;
	private $serverSpawnAnimals, $serverSpawnMobs;
	
	function __construct(){
		$this->entities = [];
		$this->server = ServerAPI::request();
		
		$this->serverSpawnAnimals = $this->server->api->getProperty("spawn-animals");
		$this->serverSpawnMobs = $this->server->api->getProperty("spawn-mobs");
	}
	
	public function init(){
		$this->server->api->console->register("summon", "<mob>", [$this, "commandHandler"]);
		$this->server->api->console->register("spawnmob", "<mob>", [$this, "commandHandler"]);
		$this->server->api->console->register("despawn", "", [$this, "CommandHandler"]);
		$this->server->api->console->register("entcnt", "", [$this, "CommandHandler"]);
	}
	
	
	public function commandHandler($cmd, $args, $issuer, $alias){
		$mob = [
			"chicken" => 10,
			"cow" => 11,
			"pig" => 12,
			"sheep" => 13,
			
			"zombie" => 32,
			"creeper" => 33,
			"skeleton" => 34,
			"spider" => 35,
			"pigman" => 36
		];
		$output = "";
		switch($cmd){
			case "entcnt":
				return "Total amount of entities: ". count($this->entities);
			case "summon":
			case "spawnmob":
				if(!($issuer instanceof Player)){
					return "Please run this command in-game.";
				}
				if((count($args) < 1) or (count($args) > 3)){
					return "Usage: /$cmd <mob> [amount] [baby]";
				}
				
				if(is_int($args[0])) $type = $args[0];
				else $type = $mob[strtolower($args[0])];
				if($type < 10 || $type > 36){
					return "Unknown mob.";
				}
				$mobName = ucfirst(array_flip($mob)[$type]);
				
				if(((isset($args[1]) && strtolower($args[1]) === "baby") || (isset($args[2]) && strtolower($args[2]) === "baby")) && !Utils::in_range($type, 10, 13)){
					return "$mobName cannot be a baby!";
				}
				
				$x = round($issuer->entity->x, 2, PHP_ROUND_HALF_UP);
				$y = round($issuer->entity->y, 2, PHP_ROUND_HALF_UP);
				$z = round($issuer->entity->z, 2, PHP_ROUND_HALF_UP);
				$level = $issuer->entity->level;
				$pos = new Position($x, $y, $z, $level);
				
				if(count($args) === 1){//summon <mob>
					$this->summon($pos, ENTITY_MOB, $type);
					return "$mobName spawned in $x, $y, $z.";
				}
				elseif(is_numeric($args[1])){//summon <mob> [amount]
					$amount = (int) $args[1];
					if($amount > 100){
						return "Cannot spawn > 100 mobs";
					}
					$isBaby = false;
					if(isset($args[2]) and strtolower($args[2]) === 'baby'){//summon <mob> [amount] [baby]
						$isBaby = true;
					}
					
					for($cnt = $amount; $cnt > 0; --$cnt){
						$this->summon($pos, ENTITY_MOB, $type, ["IsBaby" => $isBaby]);
					}
					
					return "$amount ".($isBaby === 1 ? "Baby" : "")." $mobName(s) spawned in $x, $y, $z.";
				}
				elseif(strtolower($args[1]) == "baby"){//summon <mob> [baby]
					$this->summon($pos, ENTITY_MOB, $type, ["IsBaby" => 1]);
					return "Baby $mobName spawned in $x, $y, $z.";
				}
				break;
			case "despawn":
				$cnt = 0;
				if(!isset($args[0])){
					$output .= "/despawn <all or (mobs,objects,items,fallings)>";
					break;
				}else{
					if($args[0] === "all"){
						$cnt = 0;
						foreach($this->entities as $e){
							if(isset($e) && $e != null && !$e->isPlayer()){ //if player, not despawning
								$this->remove($e->eid);
								$cnt++;
							}
						}
					}else{
						$array = explode(",", strtolower($args[0]));
						if(count($array) > 4){
							$output .= "Many arguments!";
							break;
						}
						//terrible code
						$list = "";
						$temp = ["mobs" => "2", "objects" => "3", "items" => "4", "fallings" => "5"];
						foreach($array as $value){
							$list .= $temp[$value]." or ";
						}
						$despawning = substr($list, 0, -4);
						$l = $this->server->query("SELECT EID FROM entities WHERE class = ".$despawning.";");
						if($l !== false and $l !== true){
							while(($e = $l->fetchArray(SQLITE3_ASSOC)) !== false){
								$e = $this->get($e["EID"]);
								if($e instanceof Entity){
									$this->remove($e->eid);
									$cnt++;
								}
							}
						}
					}
				}
				
				$output = "$cnt entities have been despawned!";
				break;
		}
		return $output;
	}
	
	public function summon(Position $pos, $class, $type, array $data = []){
		$entity = $this->add($pos->level, $class, $type, [
			"x" => $pos->x,
			"y" => $pos->y,
			"z" => $pos->z
		] + $data);
		$this->spawnToAll($entity, $pos->level);
	}
	
	public function add(Level $level, $class, $type = 0, $data = []){
		$eid = $this->eCnt++;
		$efl = EntityRegistry::$entityList->getEntityFromTypeAndClass($type, $class);
		if($efl instanceof PropertyEntity){
			$class = $efl->getEntityName();
			$this->entities[$eid] = new $class($level, $eid, $efl->getEntityClass(), $efl->getEntityClass() === ENTITY_ITEM ? $type : $efl->getEntityType(), $data);
		}else{
			$this->entities[$eid] = new Entity($level, $eid, $class, $type, $data);
		}
		$level->entityList[$eid] = &$this->entities[$eid];
		$this->server->handle("entity.add", $this->entities[$eid]);
		return $this->entities[$eid];
	}
	
	public function spawnToAll(Entity $e){
		foreach($this->server->api->player->getAll($e->level) as $player){
			if($player->eid !== false and $player->eid !== $e->eid and $e->class !== ENTITY_PLAYER and $e instanceof Entity){
				$e->spawn($player);
			}
		}
	}
	
	public function get($eid){
		return nullsafe($this->entities[$eid], false);
	}
	
	public function remove($eid){
		if(isset($this->entities[$eid])){
			$this->entities[$eid]->closed = true;
			if($this->entities[$eid]->isPlayer()){
				$pk = new RemovePlayerPacket;
				$pk->eid = $eid;
				$pk->clientID = 0;
				$this->server->api->player->broadcastPacket($this->entities[$eid]->level->players, $pk);
			}else{
				$pk = new RemoveEntityPacket;
				$pk->eid = $eid;
				$this->server->api->player->broadcastPacket($this->entities[$eid]->level->players, $pk);
			}
			$this->server->api->dhandle("entity.remove", $this->entities[$eid]);
			unset($this->entities[$eid]->level->entityList[$eid]);
			unset($this->entities[$eid]);
			$this->server->query("DELETE FROM entities WHERE EID = " . $eid . ";");
		}
	}
	
	public function updateRadius(Position $center, $radius = 15, $class = false){
		$this->server->query("UPDATE entities SET hasUpdate = 1 WHERE level = '" . $center->level->getName() . "' " . ($class !== false ? "AND class = $class " : "") . "AND abs(x - {$center->x}) <= $radius AND abs(y - {$center->y}) <= $radius AND abs(z - {$center->z}) <= $radius;");
	}
	
	public function getRadius(Position $center, $radius = 15, $class = false){
		$entities = [];
		$l = $this->server->query("SELECT EID FROM entities WHERE level = '" . $center->level->getName() . "' " . ($class !== false ? "AND class = $class " : "") . "AND abs(x - {$center->x}) <= $radius AND abs(y - {$center->y}) <= $radius AND abs(z - {$center->z}) <= $radius;");
		if($l !== false and $l !== true){
			while(($e = $l->fetchArray(SQLITE3_ASSOC)) !== false){
				$e = $this->get($e["EID"]);
				if($e instanceof Entity){
					$entities[$e->eid] = $e;
				}
			}
		}
		return $entities;
	}
	
	public function heal($eid, $heal, $cause){
		$this->harm($eid, -$heal, $cause);
	}
	
	public function harm($eid, $attack, $cause, $force = false){
		$e = $this->get($eid);
		if($e === false or $e->dead === true){
			return false;
		}
		$e->setHealth($e->getHealth() - $attack, $cause, $force);
	}
	
	public function dropRawPos(Position $pos, $item, $speedX, $speedY, $speedZ){
		if($item->getID() === AIR or $item->count <= 0){
			return;
		}
		$data = [
			"x" => $pos->x,
			"y" => $pos->y,
			"z" => $pos->z,
			"level" => $pos->level,
			"speedX" => $speedX,
			"speedY" => $speedY,
			"speedZ" => $speedZ,
			"item" => $item,
		];
		if($this->server->api->handle("item.drop", $data) !== false){
			for($count = $item->count; $count > 0;){
				$item->count = min($item->getMaxStackSize(), $count);
				$count -= $item->count;
				$e = $this->add($pos->level, ENTITY_ITEM, $item->getID(), $data);
				$this->spawnToAll($e);
				$this->server->api->handle("entity.motion", $e);
			}
		}
	}
	
	public function drop(Position $pos, Item $item){
		if($item->getID() === AIR or $item->count <= 0){
			return;
		}
		$data = [
			"x" => $pos->x + mt_rand(-10, 10) / 50,
			"y" => $pos->y + 0.19,
			"z" => $pos->z + mt_rand(-10, 10) / 50,
			"level" => $pos->level,
			"speedX" => Utils::randomFloat() * 0.2 - 0.1,
			"speedY" => 0.2,
			"speedZ" => Utils::randomFloat() * 0.2 - 0.1,
			"item" => $item,
		];
		if($this->server->api->handle("item.drop", $data) !== false){
			for($count = $item->count; $count > 0;){
				$item->count = min($item->getMaxStackSize(), $count);
				$count -= $item->count;
				$e = $this->add($pos->level, ENTITY_ITEM, $item->getID(), $data);
				$this->spawnToAll($e);
				$this->server->api->handle("entity.motion", $e);
			}
		}
	}
	
	public function spawnAll(Player $player){
		foreach($this->getAll($player->level) as $e){
			if($e->class !== ENTITY_PLAYER){
				$e->spawn($player);
			}
		}
	}
	
	public function getAll($level = null){
		if($level instanceof Level){
			return $level->entityList;
		}
		return $this->entities;
	}
}
