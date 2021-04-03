<?php

namespace aieuo\ping\signal\data;

use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

class MovePingData extends PingData {

    public function __construct() {
        parent::__construct("Move", "Move", TextFormat::GOLD);
    }

    public function processPosition(Vector3 $position): Vector3 {
        return $position;
    }

}