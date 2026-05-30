<?php

declare(strict_types=1);

/*
 * * AIZEEN Developer

 * * ░█████╗░██╗███████╗███████╗███████╗███╗░░██╗
 * ██╔══██╗██║╚════██║██╔════╝██╔════╝████╗░██║
 * ███████║██║░░███╔═╝█████╗░░█████╗░░██╔██╗██║
 * ██╔══██║██║██╔══╝░░██╔══╝░░██╔══╝░░██║╚████║
 * ██║░░██║██║███████╗███████╗███████╗██║░╚███║
 * ╚═╝░░╚═╝╚═╝╚══════╝╚══════╝╚══════╝╚═╝░░╚══╝

 * * YouTube: aizeen_hi
 * Plugin: Tags v3
 */

namespace AizeenDev\Tags;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\player\Player;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\player\chat\ChatFormatter;
use pocketmine\scheduler\ClosureTask;
use ReflectionClass;

class Main extends PluginBase implements Listener {

    public const Syntax = [
        "{BUILDER}", "{TIKTOK}", "{TWITCH}", "{YOUTUBE}", "{DIAMOND}", "{VIP}", 
        "{HELPER}", "{MOD}", "{HERO}", "{LEGEND}", "{PARTY}", "{GAME}", 
        "{ADMIN}", "{OWNER}", "{JOIN}", "{NOTICE}"
    ];
    
    public const Unicode = [
        "", "", "", "", "", "", 
        "", "", "", "", "", "", 
        "", "", "", ""
    ];

    private const COLOR_MAP = [
        "black" => "§0", "dark_blue" => "§1", "dark_green" => "§2", "dark_aqua" => "§3",
        "dark_red" => "§4", "purple" => "§5", "gold" => "§6", "gray" => "§7",
        "dark_gray" => "§8", "blue" => "§9", "green" => "§a", "aqua" => "§b",
        "red" => "§c", "pink" => "§d", "yellow" => "§e", "white" => "§f"
    ];

    private const RANGOS_ASIGNABLES = [
        "OWNER"     => ["tag" => "{OWNER}", "color" => "§4"],
        "ADMIN"     => ["tag" => "{ADMIN}", "color" => "§c"],
        "MOD"       => ["tag" => "{MOD}", "color" => "§d"],
        "HELPER"    => ["tag" => "{HELPER}", "color" => "§a"],
        "VIP"       => ["tag" => "{VIP}", "color" => "§a"],
        "DIAMOND"   => ["tag" => "{DIAMOND}", "color" => "§b"],
        "TIKTOK"    => ["tag" => "{TIKTOK}", "color" => "§6"],
        "YOUTUBE"   => ["tag" => "{YOUTUBE}", "color" => "§c"],
        "TWITCH"    => ["tag" => "{TWITCH}", "color" => "§5"],
        "LEGEND"    => ["tag" => "{LEGEND}", "color" => "§6"],
        "HERO"      => ["tag" => "{HERO}", "color" => "§c"],
        "BUILDER"   => ["tag" => "{BUILDER}", "color" => "§e"]
    ];

    private const JOIN_MESSAGES = [
        "OWNER"     => ["joined the lobby"],
        "ADMIN"     => ["joined the lobby"],
        "MOD"       => ["joined the lobby"],
        "HELPER"    => ["joined the lobby"],
        "BUILDER"   => ["joined the lobby"],
        "VIP"       => ["joined the lobby"],
        "DIAMOND"   => ["joined the lobby"],
        "TIKTOK"    => ["joined the lobby"],
        "YOUTUBE"   => ["joined the lobby"],
        "TWITCH"    => ["joined the lobby"],
        "LEGEND"    => ["joined the lobby"],
        "HERO"      => ["joined the lobby"]
    ];

    private const QUIT_MESSAGES = [
        "OWNER"     => ["left the lobby"],
        "ADMIN"     => ["left the lobby"],
        "MOD"       => ["left the lobby"],
        "HELPER"    => ["left the lobby"],
        "BUILDER"   => ["left the lobby"],
        "VIP"       => ["left the lobby"],
        "DIAMOND"   => ["left the lobby"],
        "TIKTOK"    => ["left the lobby"],
        "YOUTUBE"   => ["left the lobby"],
        "TWITCH"    => ["left the lobby"],
        "LEGEND"    => ["left the lobby"],
        "HERO"      => ["left the lobby"]
    ];

    protected function onEnable(): void {
        @mkdir($this->getDataFolder() . "players/", 0777, true);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->saveResource("Tags.mcpack", false);
        $manager = $this->getServer()->getResourcePackManager();
        $packPath = $this->getDataFolder() . "Tags.mcpack";

        if (file_exists($packPath)) {
            $pack = new ZippedResourcePack($packPath);
            $reflection = new ReflectionClass($manager);

            $property = $reflection->getProperty("resourcePacks");
            $property->setAccessible(true);
            $currentPacks = $property->getValue($manager);
            $currentPacks[] = $pack;
            $property->setValue($manager, $currentPacks);

            $property = $reflection->getProperty("uuidList");
            $property->setAccessible(true);
            $uuids = $property->getValue($manager);
            $uuids[strtolower($pack->getPackId())] = $pack;
            $property->setValue($manager, $uuids);

            $property = $reflection->getProperty("serverForceResources");
            $property->setAccessible(true);
            $property->setValue($manager, true);
        }
    }

    private function translate(string $text): string {
        return str_replace(self::Syntax, self::Unicode, $text);
    }

    public function getPlayerData(Player $player): Config {
        return new Config($this->getDataFolder() . "players/" . strtolower($player->getName()) . ".yml", Config::YAML);
    }

    public function updateVisuals(Player $player): void {
        if(!$player->isOnline()) return;
        
        $cfg = $this->getPlayerData($player);
        $rankName = strtoupper((string)$cfg->get("rank", "PLAYER"));
        
        $defaultColor = isset(self::RANGOS_ASIGNABLES[$rankName]) ? self::RANGOS_ASIGNABLES[$rankName]["color"] : "§8";
        $nickColor = $cfg->get("nick_color", $defaultColor);

        $tag = isset(self::RANGOS_ASIGNABLES[$rankName]) ? self::RANGOS_ASIGNABLES[$rankName]["tag"] : "";
        $icon = $tag === "" ? "" : $this->translate($tag) . " ";

        $format = $icon . $nickColor . $player->getName() . "§r";
        $player->setNameTag($format);
        $player->setDisplayName($format);
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $path = $this->getDataFolder() . "players/" . strtolower($player->getName()) . ".yml";
        
        if (!file_exists($path)) {
            $cfg = new Config($path, Config::YAML);
            $cfg->setAll([
                "rank" => "PLAYER",
                "nick_color" => "§8",
                "chat_color" => "§7",
                "msg_color" => "§e",
                "join_msg_id" => 0,
                "quit_msg_id" => 0
            ]);
            $cfg->save();
        }

        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player): void {
            if($player->isOnline()) $this->updateVisuals($player);
        }), 20);
        
        $cfg = $this->getPlayerData($player);
        $rankName = strtoupper((string)$cfg->get("rank", "PLAYER"));
        
        $color = $cfg->get("nick_color", isset(self::RANGOS_ASIGNABLES[$rankName]) ? self::RANGOS_ASIGNABLES[$rankName]["color"] : "§8");

        if (isset(self::RANGOS_ASIGNABLES[$rankName])) {
            $msgColor = $cfg->get("msg_color", "§e");
            $msgId = (int)$cfg->get("join_msg_id", 0);
            
            $messages = self::JOIN_MESSAGES[$rankName] ?? ["I enter the server."];
            $customText = $messages[$msgId] ?? $messages[0];

            $icon = $this->translate(self::RANGOS_ASIGNABLES[$rankName]["tag"]) . " ";
            $event->setJoinMessage($icon . $color . $player->getName() . " " . $msgColor . $customText);
        } else {
            $event->setJoinMessage("");
        }
    }

    public function onQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        $cfg = $this->getPlayerData($player);
        $rankName = strtoupper((string)$cfg->get("rank", "PLAYER"));

        $color = $cfg->get("nick_color", isset(self::RANGOS_ASIGNABLES[$rankName]) ? self::RANGOS_ASIGNABLES[$rankName]["color"] : "§8");

        if (isset(self::RANGOS_ASIGNABLES[$rankName])) {
            $msgColor = $cfg->get("msg_color", "§e");
            $msgId = (int)$cfg->get("quit_msg_id", 0);
            
            $messages = self::QUIT_MESSAGES[$rankName] ?? ["Left the server."];
            $customText = $messages[$msgId] ?? $messages[0];

            $icon = $this->translate(self::RANGOS_ASIGNABLES[$rankName]["tag"]) . " ";
            $event->setQuitMessage($icon . $color . $player->getName() . " " . $msgColor . $customText);
        } else {
            $event->setQuitMessage("");
        }
    }

    public function onChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        $cfg = $this->getPlayerData($player);
        $rankName = strtoupper((string)$cfg->get("rank", "PLAYER"));
        
        if ($player->hasPermission("tags.use")) {
            $event->setMessage($this->translate($event->getMessage()));
        }

        $tag = isset(self::RANGOS_ASIGNABLES[$rankName]) ? self::RANGOS_ASIGNABLES[$rankName]["tag"] : "";
        $icon = $tag === "" ? "" : $this->translate($tag) . " ";
        
        $nickColor = $cfg->get("nick_color", "§8");
        $chatColor = $cfg->get("chat_color", "§7");

        $event->setFormatter(new class($icon, $nickColor, $chatColor, $player->getName()) implements ChatFormatter {
            public function __construct(private string $i, private string $nc, private string $cc, private string $n) {}
            public function format(string $username, string $message): string {
                return $this->i . $this->nc . $this->n . "§f: " . $this->cc . $message;
            }
        });
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!$sender instanceof Player && in_array($command->getName(), ["nickcolor", "chatcolor", "joinmsg", "quitmsg", "msgcolor"])) {
            $sender->sendMessage("§cUse this command in-game.");
            return true;
        }

        switch ($command->getName()) {
            case "rank":
                if (!$sender->hasPermission("rank.cmd") || count($args) < 2) {
                    $sender->sendMessage("§eUsage: /rank <player> <OWNER|ADMIN|MOD|HELPER|BUILDER|HERO|LEGEND|VIP|DIAMOND|TIKTOK|YOUTUBE|TWITCH|PLAYER>");
                    return true;
                }
                $targetName = strtolower($args[0]);
                $rankName = strtoupper($args[1]);
                
                if (!isset(self::RANGOS_ASIGNABLES[$rankName]) && $rankName !== "PLAYER") {
                    $sender->sendMessage("§cRank not found.");
                    return true;
                }

                $cfg = new Config($this->getDataFolder() . "players/" . $targetName . ".yml", Config::YAML);
                $cfg->set("rank", $rankName);
                
                if($rankName === "PLAYER") {
                    $cfg->set("nick_color", "§8");
                    $cfg->set("chat_color", "§7");
                } else {
                    $cfg->set("nick_color", self::RANGOS_ASIGNABLES[$rankName]["color"]);
                    $cfg->set("chat_color", "§f");
                }
                $cfg->save();
                
                $sender->sendMessage("§aRank updated successfully.");
                $target = Server::getInstance()->getPlayerExact($targetName);
                if ($target instanceof Player) $this->updateVisuals($target);

                if (in_array($rankName, ["TIKTOK", "YOUTUBE", "TWITCH", "HERO", "LEGEND", "VIP", "DIAMOND"])) {
                    $noticeIcon = $this->translate("{NOTICE}");
                    
                    if (in_array($rankName, ["TIKTOK", "YOUTUBE", "TWITCH"])) {
                        Server::getInstance()->broadcastMessage($noticeIcon . " §fThe player §a" . $targetName . " §fhas obtained the rank §c" . $rankName . " §fand now it's an official partner!");
                    } else {
                        Server::getInstance()->broadcastMessage($noticeIcon . " §fThe player §a" . $targetName . " §fhas bought the rank §e" . $rankName . "§f!");
                    }
                }
                break;

            case "nickcolor":
                if (!$sender->hasPermission("color.nick")) return false;
                if (!isset($args[0])) {
                    $sender->sendMessage("§eUsage: /nickcolor <colorName>\n§7Colors: " . implode(", ", array_keys(self::COLOR_MAP)));
                    return true;
                }
                $color = strtolower($args[0]);
                if (!isset(self::COLOR_MAP[$color])) {
                    $sender->sendMessage("§cInvalid color.");
                    return true;
                }
                $cfg = $this->getPlayerData($sender);
                $cfg->set("nick_color", self::COLOR_MAP[$color]);
                $cfg->save();
                $this->updateVisuals($sender);
                $sender->sendMessage("§aNick color updated to " . $color);
                break;

            case "chatcolor":
                if (!$sender->hasPermission("color.chat")) return false;
                if (!isset($args[0])) {
                    $sender->sendMessage("§eUsage: /chatcolor <colorName>\n§7Colors: " . implode(", ", array_keys(self::COLOR_MAP)));
                    return true;
                }
                $color = strtolower($args[0]);
                if (!isset(self::COLOR_MAP[$color])) {
                    $sender->sendMessage("§cInvalid color.");
                    return true;
                }
                $cfg = $this->getPlayerData($sender);
                $cfg->set("chat_color", self::COLOR_MAP[$color]);
                $cfg->save();
                $sender->sendMessage("§aChat color updated to " . $color);
                break;

            case "joinmsg":
                if (!isset($args[0]) || !in_array($args[0], ["1", "2", "3"])) {
                    $sender->sendMessage("§eUsage: /joinmsg <1|2|3>\n§7Choose your favorite entry message.");
                    return true;
                }
                $cfg = $this->getPlayerData($sender);
                $cfg->set("join_msg_id", (int)$args[0] - 1);
                $cfg->save();
                $sender->sendMessage("§a¡You have updated your entry message to the style " . $args[0] . "!");
                break;

            case "quitmsg":
                if (!isset($args[0]) || !in_array($args[0], ["1", "2", "3"])) {
                    $sender->sendMessage("§eUsage: /quitmsg <1|2|3>\n§7Choose your favorite exit message.");
                    return true;
                }
                $cfg = $this->getPlayerData($sender);
                $cfg->set("quit_msg_id", (int)$args[0] - 1);
                $cfg->save();
                $sender->sendMessage("§a¡You have updated your exit message to the style " . $args[0] . "!");
                break;

            case "msgcolor":
                if (!isset($args[0])) {
                    $sender->sendMessage("§eUsage: /msgcolor <colorName>\n§7Colors: " . implode(", ", array_keys(self::COLOR_MAP)));
                    return true;
                }
                $color = strtolower($args[0]);
                if (!isset(self::COLOR_MAP[$color])) {
                    $sender->sendMessage("§cInvalid color.");
                    return true;
                }
                $cfg = $this->getPlayerData($sender);
                $cfg->set("msg_color", self::COLOR_MAP[$color]);
                $cfg->save();
                $sender->sendMessage("§aYou have changed the color of your ads to " . $color);
                break;
        }
        return true;
    }
}
