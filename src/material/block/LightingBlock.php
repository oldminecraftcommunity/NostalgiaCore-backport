<?php
/*
BLOCKS:
BurningFurnace, Fire, GlowingObsidian, GlowingRedstoneOre, Glowstone, Lava, Torch
*/
interface LightingBlock{
	/*Max light distance (Used to melt ice)*/
	public function getMaxLightValue(); /*number of blocks must always be Integer */
}