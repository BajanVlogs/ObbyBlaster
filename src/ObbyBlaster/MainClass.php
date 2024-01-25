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

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
    }

    /////////// API ///////////

    public function isCannon(Block $button){
        $cannonIds = [49, 246];
        $sides = [
            Vector3::SIDE_NORTH,
            Vector3::SIDE_NORTH_WEST,
            Vector3::SIDE_SOUTH,
            Vector3::SIDE_SOUTH_EAST,
            Vector3::SIDE_EAST,
            Vector3::SIDE_EAST_WEST,
            Vector3::SIDE_WEST
        ];

        foreach ($sides as $side) {
            if (in_array($button->getSide($side)->getId(), $cannonIds)) {
                return true;
            }
        }

        return false;
    }

    public function isCannonLoaded(Block $button){
        if (!$this->isCannon($button)) {
            return false;
        }

        $load = $this->getCannonLoadLoc($button);
        return $load->getId() === 46;
    }

    public function getCannonLoadLoc(Block $button){
        $side = $this->getCannonOrientation($button);
        $obsidian = $button->getSide($side);
        return $obsidian->getSide(Vector3::SIDE_UP);
    }

    public function getCannonBaseLoc(Block $button){
        $side = $this->getCannonOrientation($button);
        return $button->getSide($side);
    }

    public function getCannonOrientation(Block $button){
        if (!$this->isCannon($button)) {
            return false;
        }

        $orientation = null;
        $buttonIds = $button->getSide(Vector3::SIDE_NORTH)->getId();
        $buttonIds |= $button->getSide(Vector3::SIDE_SOUTH)->getId();
        $buttonIds |= $button->getSide(Vector3::SIDE_EAST)->getId();
        $buttonIds |= $button->getSide(Vector3::SIDE_WEST)->getId();

        if ($buttonIds === 49) {
            $orientation = "north";
        } elseif ($buttonIds === 246) {
            $orientation = "south";
        }

        return $orientation ? $this->getCannonOrientationValue($orientation) : false;
    }

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

    public function launchCannon(Block $button, Player $launcher){
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

    private function getCannonYaw(Block $button): int {
        $yawMap = [
            2 => 180,
            5 => 270,
            3 => 0,
            4 => 90
        ];

        return $yawMap[$this->getCannonOrientation($button)];
    }

    /////////////


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
