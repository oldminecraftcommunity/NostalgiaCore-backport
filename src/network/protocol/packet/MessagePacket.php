<?php

class MessagePacket extends RakNetDataPacket{
	public $source;
	public $message;
	
	public function pid(){
		return ProtocolInfo::MESSAGE_PACKET;
	}
	
	public function decode(){
		$this->message = $this->getString();
	}	
	
	public function encode(){
		$this->reset();
		$this->putString($this->message);
	}

}