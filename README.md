# CoolPlugin - PocketMine-MP

[![PocketMine-MP](https://img.shields.io/badge/PocketMine--MP-5.0.0+-blue)](https://github.com/pmmp/PocketMine-MP)
[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

A feature-rich PocketMine-MP plugin demonstrating modern API usage and clean architecture.

---

## Features

### Core Systems
- **Particle Effects**: Welcome animations, spiral effects, movement trails
- **Player Enhancement**: Flight toggle, speed adjustment, instant healing
- **Statistics**: Block tracking, distance measurement, milestone notifications  
- **Teleportation**: Request-based system with auto-expiry
- **Chat Enhancements**: Custom formatting and color coding

### Commands
```
/cool                    - Show available subcommands
/cool particles          - Spawn spiral particle effects  
/cool fly               - Toggle flight mode
/cool heal              - Instant heal with particles
/cool speed [1-10]      - Adjust movement speed
/stats                  - View personal statistics
/tpa <player>           - Request teleport to player
/tpaccept               - Accept teleport request
```

---

## Architecture

The plugin follows a modular structure with separated concerns:

```
src/CoolPlugin/
├── Main.php                    # Plugin initialization
├── Commands/                   # Command handlers
│   ├── BaseCommand.php         
│   ├── CoolCommand.php         
│   ├── StatsCommand.php        
│   └── TeleportCommand.php     
├── Events/                     # Event listeners
│   ├── PlayerEventListener.php 
│   ├── BlockEventListener.php  
│   └── ChatEventListener.php   
├── Tasks/                      # Scheduled tasks
│   ├── ParticleTask.php        
│   └── TeleportExpiryTask.php  
├── Utils/                      # Helper classes
│   ├── ParticleUtils.php       
│   ├── MessageUtils.php        
│   └── ConfigUtils.php         
└── Manager/                    # Business logic
    ├── StatsManager.php        
    └── TeleportManager.php     
```

---

## Code Quality

This plugin demonstrates:

- **Modern PHP**: Strict typing, PSR compliance, proper error handling
- **PocketMine API**: Current 5.0.0+ methods, no deprecated code
- **Clean Architecture**: Separation of concerns, testable components
- **Performance**: Optimized event handling and particle systems

### Example Implementation

```php
<?php

declare(strict_types=1);

namespace CoolPlugin\Events;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\FlameParticle;
use pocketmine\world\sound\PopSound;

class PlayerEventListener implements Listener {

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $pos = $player->getPosition();
        
        $player->sendMessage(
            TextFormat::GOLD . "Welcome to the server!"
        );

        // Welcome particle effect
        for ($i = 0; $i < 10; $i++) {
            $particle = new FlameParticle();
            $player->getWorld()->addParticle(
                $pos->add(mt_rand(-2, 2), mt_rand(0, 3), mt_rand(-2, 2)),
                $particle,
                [$player]
            );
        }
        
        $player->getWorld()->addSound($pos, new PopSound());
    }
}
```

---

## Installation

1. Download the plugin from releases
2. Place in your server's `plugins/` directory
3. Restart the server
4. Configure as needed in `plugin_data/CoolPlugin/`

---

## Requirements

- PocketMine-MP 5.0.0 or higher
- PHP 8.0 or higher

---

**Discord**: [discord.gg/pocketkimi](https://discord.gg/pocketkimi) for questions and discussion.
