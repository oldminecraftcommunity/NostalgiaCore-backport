<?php

abstract class Populator{

	public abstract function populate(Level $level, $chunkX, $chunkZ, Random $random);
}