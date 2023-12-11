<?php

/***REM_START***/
require_once("TreeObject.php");

/***REM_END***/
class SmallTreeObject extends TreeObject{

private static $leavesHeight = 4;
	private static $leafRadii = [1, 1.41, 2.83, 2.24];
		public $type = 0; // All trees appear to be 4 tall
	public $treeHeight = 7;
	private $trunkHeight = 5;
	private $addLeavesVines = false;
	private $addLogVines = false;
	private $addCocoaPlants = false;

	public function canPlaceObject(Level $level, Vector3 $pos, Random $random){
		$radiusToCheck = 0;
		for($yy = 0; $yy < $this->trunkHeight + 3; ++$yy){
			if($yy == 1 or $yy === $this->trunkHeight){
				++$radiusToCheck;
			}
			for($xx = -$radiusToCheck; $xx < ($radiusToCheck + 1); ++$xx){
				for($zz = -$radiusToCheck; $zz < ($radiusToCheck + 1); ++$zz){
					if(!isset($this->overridable[$level->level->getBlockID($pos->x + $xx, $pos->y + $yy, $pos->z + $zz)])){
						return false;
					}
				}
			}
		}
		return true;
	}

	public function placeObject(Level $level, Vector3 $pos, Random $random){
		$this->treeHeight = mt_rand(0, 3) + 4; //randomized tree height
		$x = $pos->getX();
		$y = $pos->getY();
		$z = $pos->getZ();

		for($yy = $y - 3 + $this->treeHeight; $yy <= $y + $this->treeHeight; ++$yy){
			$yOff = $yy - ($y + $this->treeHeight);
			$mid = (int) (1 - $yOff / 2);
			for($xx = $x - $mid; $xx <= $x + $mid; ++$xx){
				$xOff = abs($xx - $x);
				for($zz = $z - $mid; $zz <= $z + $mid; ++$zz){
					$zOff = abs($zz - $z);
					if($xOff === $mid and $zOff === $mid and ($yOff === 0 or mt_rand(0, 2) === 0)){
						continue;
					}
					if(!$level->getBlock(new Vector3($xx, $yy, $zz))->isSolid){
						$leafpos = new Vector3($xx, $yy, $zz);
						$level->setBlockRaw($leafpos, new LeavesBlock($this->type));
					}
				}
			}
		}
		$this->placeTrunk($level, $x, $y, $z, $random, $this->treeHeight - 1);

	}

	protected function placeTrunk(Level $level, $x, $y, $z, Random $random, $trunkHeight){
		// The base dirt block
		$dirtpos = new Vector3($x, $y - 1, $z);
		$level->setBlockRaw($dirtpos, new DirtBlock());

		for($yy = 0; $yy < $this->treeHeight; ++$yy){
			$blockId = $level->getBlock(new Vector3($x, $y + $yy, $z))->getID();
			if(isset($this->overridable[$blockId])){
				$trunkpos = new Vector3($x, $y + $yy, $z);
				$level->setBlockRaw($trunkpos, new WoodBlock($this->type));
			}
		}
	}

}