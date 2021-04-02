<?php

namespace aieuo\ping;

use aieuo\ping\signal\SignalFloatingTextCluster;
use jp\mcbe\fuyutsuki\Texter\text\SendType;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class EventListener implements Listener {

    public function onQuit(PlayerQuitEvent $event): void {
        LocationPing::removeText($event->getPlayer()->getName());
    }

    public function onInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();

        if ($item->getId() !== ItemIds::STICK) return;

        $target = $player->getTargetBlock(LocationPing::$maxDistance);
        if ($target === null) return;

        $start = $player->getLocation()->add(0, $player->getEyeHeight(), 0);
        $texts = LocationPing::getTexts($player);

        $entity = null;

        foreach (VoxelRayTrace::inDirection($start, $player->getDirectionVector(), 50) as $vector3) {
            foreach ($texts as $signal) {
                if ($signal->name() === $player->getName() and $vector3->distanceSquared($signal->position()) <= 1) {
                    LocationPing::removeText($signal->name());
                    return;
                }
            }
        }

        LocationPing::setPing($player, $target);
    }

    public function onMove(PlayerMoveEvent $event): void {
        $from = $event->getFrom();
        $to = $event->getTo();
        if ($from->distance($to) < 0.1) return;

        $player = $event->getPlayer();
        $texts = LocationPing::getTexts($player);

        foreach ($texts as $text) {
            $distanceText = $text->get(SignalFloatingTextCluster::INDEX_DISTANCE);
            $distanceText->setText(TextFormat::GOLD."Move ".round($player->distance($text->position()), 2)."m");

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

}