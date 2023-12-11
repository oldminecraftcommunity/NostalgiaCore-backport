<?php

class MobController
{
	/**
	 * @var Entity
	 */
	public $entity;

	protected $jumping;
	protected $jumpTimeout;

	public function __construct($e){
		$this->entity = $e;
	}

	public function isJumping(){
		return $this->jumping;
	}

	public function setJumping($b){
		$this->jumping = $b;
	}
	
	public function moveNonInstant($x, $y, $z){
		if($x == 0 && $y == 0 && $z == 0){
			return false;
		}
		
		$ox = ($x > 0 ? 1 : ($x < 0 ? -1 : 0));
		$oy = ($y > 0 ? 1 : ($y < 0 ? -1 : 0));
		$oz = ($z > 0 ? 1 : ($z < 0 ? -1 : 0));
		$xf = $this->entity->x + ($this->entity->getSpeedModifer() * $ox * $this->entity->getSpeed());
		$zf = $this->entity->z + ($this->entity->getSpeedModifer() * $oz * $this->entity->getSpeed());
		if($this->entity->onGround){
			$bs = [ //TODO simplify(somehow)
				$this->entity->level->getBlockWithoutVector(ceil($xf), floor($this->entity->y), ceil($zf)),
				$this->entity->level->getBlockWithoutVector(ceil($xf), floor($this->entity->y), $zf - ($oz < 0 ? 1 : 0)),
				$this->entity->level->getBlockWithoutVector($xf - ($ox < 0 ? 1 : 0), floor($this->entity->y), $zf - ($oz < 0 ? 1 : 0)),
				$this->entity->level->getBlockWithoutVector($xf - ($ox < 0 ? 1 : 0), floor($this->entity->y), ceil($zf)),
			];
			foreach($bs as $b){
				if($b->isSolid){
					if(!$b->getSide(1)->isSolid){
						$oy = 1;
						break;
					}
				}
			}
		}
		
		if($this->entity->knockbackTime <= 0){
		    $this->entity->moveEntityWithOffset($ox, $oy, $oz);
		}
		
		
		$this->faceEntity($this->entity->add($ox, $oy, $oz));
		return true;
	}

	public function movementTick(){
		if($this->isJumping() && $this->jumpTimeout <= 0){
			$this->jumpTimeout = 10;
			$this->entity->speedY = 0.42;
		}

		if($this->jumpTimeout > 0) --$this->jumpTimeout;
	}
	
	public function moveTo($x, $y, $z){
		return $this->moveNonInstant($x - floor($this->entity->x), $y - floor($this->entity->y), $z - floor($this->entity->z));
	}
	
	public function faceEntity(Vector3 $v){
		$d = $v->subtract($this->entity)->normalize();
		$dx = $d->x;
		$dz = $d->z;
		
		$tan = $dz == 0 ? ($dx < 0 ? 180 : 0) : (90 - rad2deg(atan($dx / $dz))); 
		$thetaOffset = $dz < 0 ? 90 : 270;
		$calcYaw = ($thetaOffset + $tan);
		$this->entity->yaw = $calcYaw;
	}
	
	public function lookOffset($x, $y, $z, $pitch = true){
		$tan = $z == 0 ? ($x < 0 ? 180 : 0) : (90 - rad2deg(atan($x / $z))); /*arctan(infinity) = pi/2 = (90deg) - 90 = 0*/
		$thetaOffset = $z < 0 ? 90 : 270;
		$calcYaw = $tan + $thetaOffset;
		
		$this->entity->yaw= $calcYaw;
		
		if($pitch){
			$diff = sqrt($x * $x + $z * $z);
			$calcPitch = $diff == 0 ? ($y < 0 ? -90 : 90) : rad2deg(atan($y / $diff));
			$this->entity->pitch = $this->entity->type === MOB_CHICKEN ? -$calcPitch : $calcPitch;
		}
		
		$this->entity->server->query("UPDATE entities SET pitch = ".$this->entity->pitch.", yaw = ".$this->entity->yaw." WHERE EID = ".$this->entity->eid.";");
		return true;
	}
	
	public function lookOn($x, $y = 0, $z = 0, $pitch = true){
		if($x instanceof Vector3){
			return $this->lookOn($x->x, $x->y + $x->getEyeHeight(), $x->z, $pitch);
		}
		return $this->lookOffset($x - $this->entity->x, ($this->entity->y + $this->entity->height) - $y, $z - $this->entity->z, $pitch);
	}
	
	public function __destruct(){
		unset($this->entity);
	}
}

