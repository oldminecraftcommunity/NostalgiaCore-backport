<?php

class TaskSwimming extends TaskBase
{
	public function onStart(EntityAI $ai)
	{
		$this->selfCounter = 1;
	}

	public function onEnd(EntityAI $ai)
	{
		
	}

	public function onUpdate(EntityAI $ai)
	{
		if(!$ai->entity->inWater){
			$this->reset();
			$ai->mobController->setJumping(false);
			return;
		}
		
		if(Utils::randomFloat() < 0.8){ #1.5.2 method
			$ai->mobController->setJumping(true);
		}
		
		
	}
	
	public function canBeExecuted(EntityAI $ai)
	{
		return $ai->entity->inWater;
	}

}

