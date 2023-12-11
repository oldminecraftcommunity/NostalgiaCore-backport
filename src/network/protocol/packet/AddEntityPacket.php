<?php

class AddEntityPacket extends RakNetDataPacket{
	public $eid;
	public $type;
	public $x;
	public $y;
	public $z;
	public $did;
	public $speedX;
	public $speedY;
	public $speedZ;
	
	public function pid(){
		return ProtocolInfo::ADD_ENTITY_PACKET;
	}
	
	public function decode(){

	}
	
	public function encode(){
		$this->reset();
		$this->putInt($this->eid);
		$this->putByte($this->type);
		$this->putFloat($this->x);
		$this->putFloat($this->y);
		$this->putFloat($this->z);
		$this->putInt($this->did);
		if($this->did > 0){
			$this->putShort($this->speedX);
			$this->putShort($this->speedY);
			$this->putShort($this->speedZ);
		}
	}

}