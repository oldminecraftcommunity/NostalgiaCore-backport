<?php

class TreeObject{

	public $overridable = [
		0 => true,
		6 => true,
		18 => true,
	];

	public static function growTree(Level $level, Vector3 $pos, Random $random, $type = 0){
		switch($type & 0x03){
			case SaplingBlock::OAK:
				/*if($random->nextRange(0, 9) === 0){
					$tree = new BigTreeObject();
				}else{*/
				$tree = new SmallTreeObject();
				//}
				break;
		}
		if($tree->canPlaceObject($level, $pos, $random)){
			$tree->placeObject($level, $pos, $random);
		}
	}
}