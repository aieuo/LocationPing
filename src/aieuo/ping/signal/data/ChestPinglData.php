<?php

namespace aieuo\ping\signal\data;

use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

class ChestPinglData extends PingData {

    public function __construct() {
        parent::__construct("Chest", "Chest", TextFormat::AQUA);
    }

    public function processPosition(Vector3 $position): Vector3 {
        return $position->add(0.5, 0.5, 0.5);
    }

}