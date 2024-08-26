<?php

namespace Teleporter;

use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener {

    public function onEnable() {
        $this->getLogger()->info("Teleporter plugin enabled");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "teleport") {
            if ($sender instanceof Player) {
                if (isset($args[0]) && isset($args[1])) {
                    $ip = $args[0];
                    $port = intval($args[1]);
                    $this->transferPlayer($sender, $ip, $port);
                    $sender->sendMessage(TextFormat::GREEN . "Teletransportando a $ip:$port...");
                } else {
                    $sender->sendMessage(TextFormat::RED . "Uso: /teleport <ip> <puerto>");
                }
                return true;
            } else {
                $sender->sendMessage(TextFormat::RED . "Este comando solo puede ser usado por jugadores.");
                return false;
            }
        }
        return false;
    }

    public function onSignChange(SignChangeEvent $event) {
        $player = $event->getPlayer();
        $lines = $event->getLines();

        // Solo configurar los carteles si el primer renglón es [Teleport]
        if (strtolower($lines[0]) === "[teleport]" && isset($lines[1], $lines[2])) {
            $ip = $lines[1];
            $port = intval($lines[2]);

            // Configura el cartel con la IP y puerto
            $event->setLine(0, TextFormat::YELLOW . "[Teleport]");
            $event->setLine(1, $ip);
            $event->setLine(2, $port);
            $event->setLine(3, TextFormat::GREEN . "Click to teleport");
            $player->sendMessage(TextFormat::GREEN . "Cartel configurado con IP: $ip y Puerto: $port");
        }
    }

    public function onPlayerInteract(PlayerInteractEvent $event) {
        $block = $event->getBlock();
        $player = $event->getPlayer();

        if ($block->getId() === \pocketmine\block\BlockIds::SIGN_POST ||
            $block->getId() === \pocketmine\block\BlockIds::WALL_SIGN) {
            $sign = $block->getLevel()->getTile($block);

            if ($sign instanceof \pocketmine\tile\Sign) {
                $lines = $sign->getText();

                // Solo procesar si el primer renglón es [Teleport]
                if (strtolower($lines[0]) === "[teleport]" && isset($lines[1], $lines[2])) {
                    $ip = $lines[1];
                    $port = intval($lines[2]);

                    $this->transferPlayer($player, $ip, $port);
                    $player->sendMessage(TextFormat::GREEN . "Teletransportando a $ip:$port...");
                }
            }
        }
    }

    public function transferPlayer(Player $player, string $ip, int $port) {
        $pk = new TransferPacket();
        $pk->address = $ip;
        $pk->port = $port;
        $player->dataPacket($pk);
    }
}
