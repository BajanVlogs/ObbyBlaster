<?php

namespace ObbyBlaster;

use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\level\sound\GhastShootSound;
use pocketmine\level\sound\ClickSound;
use pocketmine\level\particle\MobSpawnParticle;
use pocketmine\tile\Tile;

class MainClass extends PluginBase implements Listener{

    // ... (other methods)

    private function getCannonOrientationValue(string $orientation): int {
        $orientationMap = [
            "north" => 2,
            "south" => 3,
            "east" => 5,
            "west" => 4
        ];

        return $orientationMap[$orientation];
    }

    public function getCannonOrientationName(Block $button){
        $orientation = $this->getCannonOrientation($button);
        $name = null;
        if ($orientation !== false) {
            $name = array_flip($this->getCannonOrientationValue($orientation));
        }

        return $name;
    }

    private function getCannonYaw(Block $button): int {
        $yawMap = [
            2 => 180,
            5 => 270,
            3 => 0,
            4 => 90
        ];

        return $yawMap[$this->getCannonOrientation($button)];
    }

    public function onInteract(PlayerInteractEvent $e){
        $p = $e->getPlayer();
        $b = $e->getBlock();

        if ($b->getId() === 77 || $b->getId() === 143) {
            if ($this->isCannon($b)) {
                $e->setCancelled();
                $p->getLevel()->addSound(new ClickSound($p));

                if ($this->isCannonLoaded($b)) {
                    $this->launchCannon($b, $p);
                    $b->getLevel()->addParticle(new MobSpawnParticle($this->getCannonLoadLoc($b)->add(0.5, 0, 0.5)));

                    if ($this->getConfig()->get("trigger-detach") == true) {
                        $b->getLevel()->dropItem($b->add(0.5, 0, 0.5), Item::get($b->getId(), $b->getDamage(), 1));
                        $b->getLevel()->setBlock($b, Block::get(0, 0));
                    }

                    if ($this->getConfig()->get("cannon-messages") == true) {
                        $p->sendMessage(str_replace("{DIRECTION}", $this->getCannonOrientationName($b), $this->getConfig()->get("cannon-launch-message")));
                    }

                    foreach ($this->getServer()->getOnlinePlayers() as $pl) {
                        if ($pl->getLevel() == $b->getLevel()) {
                            if ($pl->getPosition()->distance($b) <= $this->getConfig()->get("cannon-sense-distance")) {
                                $pl->getLevel()->addSound(new GhastShootSound($pl->subtract(0, 2)));

                                if ($this->getConfig()->get("cannon-messages") == true) {
                                    if ($pl !== $p) {
                                        $pl->sendPopup($this->getConfig()->get("cannon-launch-popup"));
                                    }
                                }
                            }
                        }
                    }
                } else {
                    if ($this->getConfig()->get("cannon-messages") == true) {
                        $p->sendMessage($this->getConfig()->get("cannon-unloaded-message"));
                    }
                }
            }
        }
    }

    private function isDispenser(Block $block): bool {
        $dispenserIds = [23, 158, 154, 149];
        return in_array($block->getId(), $dispenserIds) && $block->getLevel()->getTile($block) instanceof Tile;
    }

    private function launchFromDispenser(Block $dispenser, Player $launcher){
        $tile = $dispenser->getLevel()->getTile($dispenser);

        if ($tile instanceof Tile) {
            $inventory = $tile->getInventory();

            if ($inventory->canAddItem(Item::get(Item::TNT))) {
                $inventory->addItem(Item::get(Item::TNT));
            } else {
                // Handle the case where the dispenser inventory is full
                // You may want to customize this part based on your plugin's requirements
            }
        }
    }

    private function getAdjacentDispenser(Block $button): ?Block {
        $sides = [
            Vector3::SIDE_NORTH,
            Vector3::SIDE_SOUTH,
            Vector3::SIDE_EAST,
            Vector3::SIDE_WEST
        ];

        foreach ($sides as $side) {
            $sideBlock = $button->getSide($side);
            if ($this->isDispenser($sideBlock)) {
                return $sideBlock;
            }
        }

        return null;
    }

    private function launchCannon(Block $button, Player $launcher){
        $dispenser = $this->getAdjacentDispenser($button);

        if ($dispenser !== null) {
            $this->launchFromDispenser($dispenser, $launcher);
        } else {
            $load = $this->getCannonLoadLoc($button);
            $load->getLevel()->setBlock($load, Block::get(0, 0));

            $yaw = $this->getCannonYaw($button);
            $pitch = 1;

            $nbt = new CompoundTag("", [
                "Pos" => new ListTag("Pos", [
                    new DoubleTag("", $load->getX() + 0.5),
                    new DoubleTag("", $load->getY()),
                    new DoubleTag("", $load->getZ() + 0.5)
                ]),
                "Motion" => new ListTag("Motion", [
                    new DoubleTag("", - \sin($yaw / 280 * M_PI) * \cos($pitch / 180 * M_PI)),
                    new DoubleTag("", - \sin($pitch / 280 * M_PI)),
                    new DoubleTag("", \cos($yaw / 280 * M_PI) * \cos($pitch / 180 * M_PI))
                ]),
                "Rotation" => new ListTag("Rotation", [
                    new FloatTag("", $yaw),
                    new FloatTag("", $pitch)
                ])
            ]);

            $cannonLoad = Entity::createEntity("PrimedTNT", $launcher->getLevel(), $nbt, true);
            $cannonLoad->setMotion($cannonLoad->getMotion()->multiply($this->getConfig()->get("launch-speed")));
            $cannonLoad->spawnToAll();
        }
    }

    // ... (other methods)
}
