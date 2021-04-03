<?php

namespace aieuo\ping\signal\data;

use pocketmine\entity\Entity;
use pocketmine\entity\Monster;
use pocketmine\entity\object\ItemEntity;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

class EntityPinglData extends PingData {

    /** @var Entity */
    private $entity;

    public function __construct(Entity $entity) {
        $this->entity = $entity;

        $color = TextFormat::GREEN;
        if ($entity instanceof ItemEntity) {
            $name = "Item";
            $tip = $entity->getItem()->getName();
        } elseif ($entity instanceof Monster) {
            $name = "Monster";
            $tip = $entity->getName();
            $color = TextFormat::RED;
        } else {
            $name = "Entity";
            $tip = empty($entity->getNameTag()) ? "Entity" : $entity->getNameTag();
        }
        parent::__construct($name, $tip, $color);
    }

    public function getEntity(): Entity {
        return $this->entity;
    }

    public function processPosition(Vector3 $position): Vector3 {
        return $this->entity->getPosition()->add(0, $this->entity->height, 0);
    }
}