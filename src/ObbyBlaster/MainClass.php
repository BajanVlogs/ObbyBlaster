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

}
