<?php

namespace aieuo\ping;

use aieuo\ping\signal\data\ChestPinglData;
use aieuo\ping\signal\data\EntityPinglData;
use aieuo\ping\signal\data\MovePingData;
use aieuo\ping\signal\SignalFloatingTextCluster;
use jp\mcbe\fuyutsuki\Texter\text\SendType;
use pocketmine\block\Chest;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\ItemIds;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\VoxelRayTrace;
use pocketmine\Player;

class EventListener implements Listener {

    public function onQuit(PlayerQuitEvent $event): void {
        LocationPing::clearTexts($event->getPlayer());
    }

    public function onInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();

        if ($item->getId() !== ItemIds::STICK) return;

        $target = $player->getTargetBlock(LocationPing::$maxDistance);
        if ($target === null) return;

        $start = $player->getLocation()->add(0, $player->getEyeHeight(), 0);
        $texts = LocationPing::getTexts($player);

        $distanceBlock = $target->distanceSquared($player->getPosition());
        $entity = null;

        foreach (VoxelRayTrace::inDirection($start, $player->getDirectionVector(), 50) as $vector3) {
            foreach ($texts as $signal) {
                if ($signal->name() === $player->getName() and $vector3->distanceSquared($signal->position()) <= 1) {
                    LocationPing::removeText($signal->name());
                    return;
                }
            }

            $distance = $vector3->distanceSquared($player->getPosition());
            if ($distance < $distanceBlock and $entity === null) {
                $entities = $player->getLevelNonNull()->getNearbyEntities(new AxisAlignedBB($vector3->x - 0.5, $vector3->y - 0.5, $vector3->z - 0.5, $vector3->x + 0.5, $vector3->y + 0.5, $vector3->z + 0.5));
                while (($entity = array_shift($entities)) !== null) {
                    if (!($entity instanceof Player)) break;
                }
            }
        }

        switch (true) {
            case $entity instanceof Entity:
                $data = new EntityPinglData($entity);
                break;
            case $target instanceof Chest:
                $data = new ChestPinglData();
                break;
            default:
                $data = new MovePingData();
        }
        LocationPing::setPing($player, $target, $data);
    }

    public function onMove(PlayerMoveEvent $event): void {
        $from = $event->getFrom();
        $to = $event->getTo();
        if ($from->distance($to) < 0.1) return;

        $player = $event->getPlayer();
        $texts = LocationPing::getTexts($player);

        foreach ($texts as $text) {
            $data = $text->getPingData();
            $distanceText = $text->get(SignalFloatingTextCluster::INDEX_DISTANCE);
            $distanceText->setText($data->getColor().$data->getName()." ".round($player->distance($text->position()), 2)."m");

            $text->sendToPlayer($player, new SendType(SendType::EDIT));
        }
    }

    public function onTeleport(EntityTeleportEvent $event): void {
        $player = $event->getEntity();
        if (!($player instanceof Player)) return;

        $from = $event->getFrom()->getLevelNonNull();
        $to = $event->getTo()->getLevelNonNull();

        if ($from !== $to) {
            LocationPing::clearTexts($player);
        }
    }

    public function onPickupItem(InventoryPickupItemEvent $event): void {
        $entity = $event->getItem();

        foreach (LocationPing::getAllText() as $texts) {
            foreach ($texts as $name => $text) {
                $data = $text->getPingData();
                if ($data instanceof EntityPinglData and $data->getEntity() === $entity) {
                    LocationPing::removeText($name);
                    break;
                }
            }
        }
    }

    public function onDeath(EntityDeathEvent $event): void {
        $entity = $event->getEntity();

        foreach (LocationPing::getAllText() as $texts) {
            foreach ($texts as $name => $text) {
                $data = $text->getPingData();
                if ($data instanceof EntityPinglData and $data->getEntity() === $entity) {
                    LocationPing::removeText($name);
                    break;
                }
            }
        }
    }

}