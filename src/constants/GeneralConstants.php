<?php

define("PMF_LEVEL_DEFLATE_LEVEL", 6);

//Gamemodes
define("SURVIVAL", 0);
define("CREATIVE", 1);
define("ADVENTURE", 2);
define("VIEW", 3);
define("VIEWER", 3);
define("SPECTATOR", 3);

//Players
define("MAX_CHUNK_RATE", 20 / arg("max-chunks-per-second", 4)); //Default rate ~256 kB/s
define("PLAYER_MAX_QUEUE", 1024);

define("PLAYER_SURVIVAL_SLOTS", 36);
define("PLAYER_CREATIVE_SLOTS", 112);

//Block Updates
define("BLOCK_UPDATE_NORMAL", 1);
define("BLOCK_UPDATE_RANDOM", 2);
define("BLOCK_UPDATE_SCHEDULED", 3);
define("BLOCK_UPDATE_WEAK", 4);
define("BLOCK_UPDATE_TOUCH", 5);