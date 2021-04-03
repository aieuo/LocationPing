<?php

namespace aieuo\ping;

use aieuo\mineflow\flowItem\FlowItemFactory;
use aieuo\ping\mineflow\action\SetPingPosition;
use aieuo\ping\signal\data\PingData;
use aieuo\ping\signal\SignalFloatingTextCluster;
use jp\mcbe\fuyutsuki\Texter\text\SendType;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use aieuo\mineflow\utils\Language as MineflowLanguage;
use pocketmine\utils\TextFormat;

class LocationPing extends PluginBase {

    /** @var SignalFloatingTextCluster[][] */
    private static $texts = [];

    /** @var int */
    public static $maxDistance = 100;

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function onLoad() {
        if (Server::getInstance()->getPluginManager()->getPlugin("Mineflow") !== null) {
            $this->registerMineflowActions();
        }
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function onEnable() {
        $config = $this->getConfig();
        $config->setDefaults([
            "max_distance" => 100,
            "enable_tip_navigation" => true,
            "tip_navigation_interval" => 4,
        ]);
        $config->save();

        self::$maxDistance = $config->get("max_distance");

        Server::getInstance()->getPluginManager()->registerEvents(new EventListener(), $this);

        if ($config->get("enable_tip_navigation")) {
            $period = $config->get("tip_navigation_interval", 4);
            $this->getScheduler()->scheduleRepeatingTask(new UpdateDistanceTask(), $period <= 0 ? 1 : $period);
        }

        if (Server::getInstance()->getPluginManager()->getPlugin("Mineflow") !== null) {
            $this->registerMineflowMessages();
        }
    }

    public function registerMineflowMessages(): void {
        foreach ($this->getResources() as $resource) {
            $filenames = explode(".", $resource->getFilename());
            if (($filenames[1] ?? "") === "ini") {
                MineflowLanguage::add(parse_ini_file($resource->getPathname()), $filenames[0]);
            }
        }
    }

    public function registerMineflowActions(): void {
        FlowItemFactory::register(new SetPingPosition());
    }

    public static function getAllText(): array {
        return self::$texts;
    }

    public static function getTexts(Player $player): array {
        return self::$texts[$player->getName()] ?? [];
    }

    public static function addText(Player $player, SignalFloatingTextCluster $text): void {
        self::$texts[$player->getName()][$text->name()] = $text;

        $text->sendToPlayer($player, new SendType(SendType::ADD));
    }

    public static function removeText(string $textName): void {
        foreach (self::$texts as $playerName => $texts) {
            if (!isset(self::$texts[$playerName][$textName])) continue;

            $player = Server::getInstance()->getPlayerExact($playerName);
            if ($player === null) continue;

            $text = self::$texts[$playerName][$textName];
            $text->sendToPlayer($player, new SendType(SendType::REMOVE));
            unset(self::$texts[$playerName][$textName]);
        }
    }

    public static function clearTexts(Player $player): void {
        foreach (self::getTexts($player) as $name => $text) {
            if ($name === $player->getName()) {
                self::removeText($name);
            } else {
                if ($player->isOnline()) $text->sendToPlayer($player, new SendType(SendType::REMOVE));
                unset(self::$texts[$player->getName()][$name]);
            }
        }
    }

    public static function setPing(Player $player, Vector3 $pos, PingData $data): void {
        self::removeText($player->getName());

        self::addText($player, new SignalFloatingTextCluster($pos, $player->getName(), $data, [
            $data->getColor().$data->getName()." ".round($player->distance($pos), 2)."m",
            TextFormat::GRAY."click again to cancel"
        ]));

        foreach ($player->getLevelNonNull()->getPlayers() as $levelPlayer) {
            if ($player === $levelPlayer) continue;

            self::addText($levelPlayer, new SignalFloatingTextCluster($pos, $player->getName(), $data, [
                $data->getColor().$data->getName()." ".round($levelPlayer->distance($pos), 2)."m",
                TextFormat::GRAY.$player->getName()
            ]));
        }
    }
}