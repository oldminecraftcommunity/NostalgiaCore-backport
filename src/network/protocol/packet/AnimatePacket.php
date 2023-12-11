<?php

class AnimatePacket extends RakNetDataPacket{ //is it even needed in 0.7? ._.
	/**
	 * Minecart Hurt, Swing Hand
	 */
	const ANIM_1 = 0x1;
	
	public $action;
	public $eid;
	
	public function pid(){
		return ProtocolInfo::ANIMATE_PACKET;
	}
	
	public function decode(){
		$this->action = $this->getByte();
		$this->eid = $this->getInt();
	}
	
	public function encode(){
		$this->reset();
		$this->putByte($this->action);
		$this->putInt($this->eid);
	}

}