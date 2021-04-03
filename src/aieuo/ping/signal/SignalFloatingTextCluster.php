<?php

namespace aieuo\ping\signal;

use aieuo\ping\signal\data\PingData;
use jp\mcbe\fuyutsuki\Texter\text\FloatingTextCluster;
use pocketmine\math\Vector3;

class SignalFloatingTextCluster extends FloatingTextCluster {

    public const INDEX_DISTANCE = 0;
    public const INDEX_NAME = 1;

    /* @var PingData */
    private $pingData;

    public function __construct(Vector3 $position, string $name, PingData $data, array $texts = []) {
        parent::__construct($data->processPosition($position), $name, new Vector3(0, -0.3, 0), $texts);
        $this->pingData = $data;
    }

    public function getPingData(): PingData {
        return $this->pingData;
    }
}