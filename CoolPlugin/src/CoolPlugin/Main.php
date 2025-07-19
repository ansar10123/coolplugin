<?php

declare(strict_types=1);

namespace CoolPlugin;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\particle\FlameParticle;
use pocketmine\world\sound\PopSound;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\ParticleIds;

class Main extends PluginBase implements Listener {

    private array $playerStats = [];
    private array $teleportRequests = [];

    protected function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info(TextFormat::GREEN . "CoolPlugin has been enabled!");
        
        // Start a repeating task for cool effects
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function(): void {
                $this->spawnRandomParticles();
            }
        ), 60); // Every 3 seconds (20 ticks per second)
    }

    protected function onDisable(): void {
        $this->getLogger()->info(TextFormat::RED . "CoolPlugin has been disabled!");
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();
        
        // Initialize player stats
        $this->playerStats[$name] = [
            'blocks_broken' => 0,
            'blocks_placed' => 0,
            'distance_traveled' => 0,
            'last_position' => $player->getPosition()
        ];

        // Cool welcome message with formatting
        $player->sendMessage(TextFormat::GOLD . "★═══════════════════════════════════════★");
        $player->sendMessage(TextFormat::AQUA . "       Welcome to the Cool Server!");
        $player->sendMessage(TextFormat::YELLOW . "    Type " . TextFormat::WHITE . "/cool" . TextFormat::YELLOW . " for awesome commands!");
        $player->sendMessage(TextFormat::GOLD . "★═══════════════════════════════════════★");

        // Spawn welcome particles
        $pos = $player->getPosition();
        for ($i = 0; $i < 10; $i++) {
            $particle = new FlameParticle();
            $player->getWorld()->addParticle(
                $pos->add(
                    mt_rand(-2, 2),
                    mt_rand(0, 3),
                    mt_rand(-2, 2)
                ),
                $particle,
                [$player]
            );
        }

        // Play welcome sound
        $player->getWorld()->addSound($pos, new PopSound());
    }

    public function onPlayerChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        $message = $event->getMessage();

        // Add cool formatting to chat messages
        if (str_contains(strtolower($message), 'cool')) {
            $event->setFormat(
                TextFormat::BOLD . TextFormat::BLUE . "❄ " . 
                TextFormat::RESET . TextFormat::AQUA . $player->getName() . 
                TextFormat::WHITE . ": " . TextFormat::LIGHT_PURPLE . $message
            );
        }
    }

    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();

        if (!isset($this->playerStats[$name])) return;

        $from = $event->getFrom();
        $to = $event->getTo();
        
        // Calculate distance traveled
        $distance = $from->distance($to);
        $this->playerStats[$name]['distance_traveled'] += $distance;
        $this->playerStats[$name]['last_position'] = $to;

        // Create trail effect when running
        if ($player->isSprinting() && $distance > 0.1) {
            $particle = new FlameParticle();
            $player->getWorld()->addParticle(
                $from->add(0, 0.1, 0),
                $particle,
                [$player]
            );
        }
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $block = $event->getBlock();

        // Special interaction with diamond
        if ($item->getTypeId() === VanillaItems::DIAMOND()->getTypeId()) {
            $event->cancel();
            
            $pos = $block->getPosition();
            
            // Create explosion of particles
            for ($i = 0; $i < 15; $i++) {
                $particle = new FlameParticle();
                $player->getWorld()->addParticle(
                    $pos->add(
                        mt_rand(-3, 3),
                        mt_rand(0, 4),
                        mt_rand(-3, 3)
                    ),
                    $particle
                );
            }

            $player->sendMessage(TextFormat::AQUA . "✨ Diamond magic activated! ✨");
            $player->getWorld()->addSound($pos, new PopSound());
        }
    }

    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();

        if (isset($this->playerStats[$name])) {
            $this->playerStats[$name]['blocks_broken']++;
            
            // Milestone celebrations
            $broken = $this->playerStats[$name]['blocks_broken'];
            if ($broken % 50 === 0) {
                $player->sendMessage(
                    TextFormat::GOLD . "🎉 Milestone reached! " . 
                    TextFormat::YELLOW . "You've broken " . TextFormat::WHITE . $broken . 
                    TextFormat::YELLOW . " blocks!"
                );
                
                // Celebrate with particles
                $pos = $event->getBlock()->getPosition();
                for ($i = 0; $i < 20; $i++) {
                    $particle = new FlameParticle();
                    $player->getWorld()->addParticle(
                        $pos->add(
                            mt_rand(-2, 2),
                            mt_rand(0, 3),
                            mt_rand(-2, 2)
                        ),
                        $particle
                    );
                }
            }
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();

        if (isset($this->playerStats[$name])) {
            $this->playerStats[$name]['blocks_placed']++;
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used in-game!");
            return true;
        }

        switch ($command->getName()) {
            case "cool":
                return $this->handleCoolCommand($sender, $args);
            case "stats":
                return $this->handleStatsCommand($sender);
            case "tpa":
                return $this->handleTpaCommand($sender, $args);
            case "tpaccept":
                return $this->handleTpAcceptCommand($sender);
            default:
                return false;
        }
    }

    private function handleCoolCommand(Player $player, array $args): bool {
        if (empty($args)) {
            $player->sendMessage(TextFormat::AQUA . "=== Cool Commands ===");
            $player->sendMessage(TextFormat::YELLOW . "/cool particles - Spawn cool particles");
            $player->sendMessage(TextFormat::YELLOW . "/cool fly - Toggle flight mode");
            $player->sendMessage(TextFormat::YELLOW . "/cool heal - Heal yourself");
            $player->sendMessage(TextFormat::YELLOW . "/cool speed [level] - Set speed");
            return true;
        }

        switch (strtolower($args[0])) {
            case "particles":
                $this->spawnCoolParticles($player);
                break;
                
            case "fly":
                $this->toggleFlight($player);
                break;
                
            case "heal":
                $this->healPlayer($player);
                break;
                
            case "speed":
                $level = isset($args[1]) ? (int)$args[1] : 1;
                $this->setPlayerSpeed($player, $level);
                break;
                
            default:
                $player->sendMessage(TextFormat::RED . "Unknown subcommand! Use /cool for help.");
        }

        return true;
    }

    private function handleStatsCommand(Player $player): bool {
        $name = $player->getName();
        
        if (!isset($this->playerStats[$name])) {
            $player->sendMessage(TextFormat::RED . "No stats available!");
            return true;
        }

        $stats = $this->playerStats[$name];
        $player->sendMessage(TextFormat::GOLD . "=== Your Stats ===");
        $player->sendMessage(TextFormat::YELLOW . "Blocks Broken: " . TextFormat::WHITE . $stats['blocks_broken']);
        $player->sendMessage(TextFormat::YELLOW . "Blocks Placed: " . TextFormat::WHITE . $stats['blocks_placed']);
        $player->sendMessage(TextFormat::YELLOW . "Distance Traveled: " . TextFormat::WHITE . round($stats['distance_traveled'], 2) . " blocks");

        return true;
    }

    private function handleTpaCommand(Player $sender, array $args): bool {
        if (empty($args)) {
            $sender->sendMessage(TextFormat::RED . "Usage: /tpa <player>");
            return true;
        }

        $targetPlayer = $this->getServer()->getPlayerByPrefix($args[0]);
        if ($targetPlayer === null) {
            $sender->sendMessage(TextFormat::RED . "Player not found!");
            return true;
        }

        if ($targetPlayer === $sender) {
            $sender->sendMessage(TextFormat::RED . "You cannot teleport to yourself!");
            return true;
        }

        $this->teleportRequests[$targetPlayer->getName()] = $sender->getName();
        
        $sender->sendMessage(TextFormat::GREEN . "Teleport request sent to " . $targetPlayer->getName());
        $targetPlayer->sendMessage(
            TextFormat::YELLOW . $sender->getName() . TextFormat::AQUA . 
            " wants to teleport to you. Type " . TextFormat::WHITE . "/tpaccept" . 
            TextFormat::AQUA . " to accept."
        );

        // Auto-expire request after 30 seconds
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
            function() use ($targetPlayer, $sender): void {
                if (isset($this->teleportRequests[$targetPlayer->getName()]) && 
                    $this->teleportRequests[$targetPlayer->getName()] === $sender->getName()) {
                    unset($this->teleportRequests[$targetPlayer->getName()]);
                    $sender->sendMessage(TextFormat::RED . "Teleport request to " . $targetPlayer->getName() . " expired.");
                }
            }
        ), 600); // 30 seconds

        return true;
    }

    private function handleTpAcceptCommand(Player $player): bool {
        $name = $player->getName();
        
        if (!isset($this->teleportRequests[$name])) {
            $player->sendMessage(TextFormat::RED . "No pending teleport requests!");
            return true;
        }

        $requesterName = $this->teleportRequests[$name];
        $requester = $this->getServer()->getPlayerExact($requesterName);
        
        if ($requester === null) {
            $player->sendMessage(TextFormat::RED . "The player who requested teleport is no longer online!");
            unset($this->teleportRequests[$name]);
            return true;
        }

        $requester->teleport($player->getPosition());
        $requester->sendMessage(TextFormat::GREEN . "Teleported to " . $player->getName() . "!");
        $player->sendMessage(TextFormat::GREEN . $requester->getName() . " has been teleported to you!");
        
        unset($this->teleportRequests[$name]);
        return true;
    }

    private function spawnCoolParticles(Player $player): void {
        $pos = $player->getPosition();
        
        // Create a spiral of particles
        for ($i = 0; $i < 30; $i++) {
            $angle = $i * 0.5;
            $x = cos($angle) * 2;
            $z = sin($angle) * 2;
            $y = $i * 0.1;
            
            $particle = new FlameParticle();
            $player->getWorld()->addParticle(
                $pos->add($x, $y, $z),
                $particle,
                [$player]
            );
        }
        
        $player->sendMessage(TextFormat::AQUA . "✨ Cool particles spawned! ✨");
    }

    private function toggleFlight(Player $player): void {
        if ($player->getAllowFlight()) {
            $player->setAllowFlight(false);
            $player->setFlying(false);
            $player->sendMessage(TextFormat::RED . "Flight disabled!");
        } else {
            $player->setAllowFlight(true);
            $player->sendMessage(TextFormat::GREEN . "Flight enabled!");
        }
    }

    private function healPlayer(Player $player): void {
        $player->setHealth($player->getMaxHealth());
        $player->getHungerManager()->setFood($player->getHungerManager()->getMaxFood());
        $player->sendMessage(TextFormat::GREEN . "You have been healed!");
        
        // Healing particles
        $pos = $player->getPosition();
        for ($i = 0; $i < 10; $i++) {
            $particle = new FlameParticle();
            $player->getWorld()->addParticle(
                $pos->add(
                    mt_rand(-1, 1),
                    mt_rand(0, 2),
                    mt_rand(-1, 1)
                ),
                $particle,
                [$player]
            );
        }
    }

    private function setPlayerSpeed(Player $player, int $level): void {
        $level = max(1, min(10, $level)); // Clamp between 1-10
        $speed = 0.1 * $level;
        
        $player->setMovementSpeed($speed);
        $player->sendMessage(TextFormat::GREEN . "Speed set to level " . $level . "!");
    }

    private function spawnRandomParticles(): void {
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            if (mt_rand(1, 10) === 1) { // 10% chance
                $pos = $player->getPosition();
                $particle = new FlameParticle();
                $player->getWorld()->addParticle(
                    $pos->add(
                        mt_rand(-5, 5),
                        mt_rand(0, 3),
                        mt_rand(-5, 5)
                    ),
                    $particle,
                    [$player]
                );
            }
        }
    }
} 
