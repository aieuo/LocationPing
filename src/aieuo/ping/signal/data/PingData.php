<?php

namespace aieuo\ping\signal\data;

use pocketmine\math\Vector3;

abstract class PingData {

    /** @var string */
    private $name;
    /** @var string */
    private $nameTip;
    /** @var string */
    private $color;

    public function __construct(string $name, string $nameTip, string $color) {
        $this->name = $name;
        $this->nameTip = $nameTip;
        $this->color = $color;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getNameTip(): string {
        return $this->nameTip;
    }

    public function getColor(): string {
        return $this->color;
    }

    abstract public function processPosition(Vector3 $position): Vector3;

}