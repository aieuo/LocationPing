<?php

namespace aieuo\ping;

use aieuo\ping\signal\SignalFloatingTextCluster;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class UpdateDistanceTask extends Task {

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function onRun(int $currentTick) {
        $signals = LocationPing::getAllText();
        if (count($signals) === 0) return;

        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if (empty($signals[$player->getName()])) continue;

            $texts = $signals[$player->getName()];
            $messages = [];
            foreach ($texts as $text) {
                $messages[] = $this->getTipMessage($player, $text);
            }

            $player->sendTip(implode("\n", $messages));
        }
    }

    public function getTipMessage(Player $player, SignalFloatingTextCluster $text): string {
        $xDist = $text->position()->x - $player->x;
        $zDist = $text->position()->z - $player->z;
        $deg = (atan2($zDist, $xDist) / M_PI * 180 + 630 - $player->getYaw()) % 360;

        if (($deg >= 5 and $deg <= 150) or ($deg >= 210 and $deg <= 355)) {
            $left = str_repeat("<", 9 - floor(min(90, abs(270 - $deg)) / 10));
            $right = str_repeat(">", 9 - floor( min(90, abs(90 - $deg)) / 10));
        } elseif ($deg > 150 and $deg < 210) {
            $left = "§e<<<§f";
            $right = "§e>>>§f";
        } else {
            $left = "";
            $right = "";
        }

        $distance = round($text->position()->distance($player), 2);

        $data = $text->getPingData();
        return $left." ".$text->name()." ".$data->getColor().$data->getNameTip()." ".$distance."m §f".$right;
    }
}