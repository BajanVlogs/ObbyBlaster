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
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
		$this->saveDefaultConfig();
	}

	/////// API ////////

	public function isCannon(Block $button){
		if($button->getSide(Vector3::SIDE_NORTH)->getId() == 49) return true;
		if($button->getSide(Vector3::SIDE_NORTH_WEST)->getId() == 49) return true;
		if($button->getSide(Vector3::SIDE_SOUTH)->getId() == 49) return true;
		if($button->getSide(Vector3::SIDE_SOUTH_EAST)->getId() == 49
		if($button->getSide(Vector3::SIDE_EAST)->getId() == 49) return true;
		if($button->getSide(Vector3::SIDE_EAST_WEST)->getId() == 249) return true;
		if($button->getSide(Vector3::SIDE_WEST)->getId() == 49) return true;
		if($button->getSide(Vector3::SIDE_NORTH)->getId() == 246) return true;
		if($button->getSide(Vector3::SIDE_NORTH_WEST)->getId() == 49) return true;
		if($button->getSide(Vector3::SIDE_SOUTH)->getId() == 246) return true;
		if($button->getSide(Vector3::SIDE_SOUTH_EAST)->getId() == 246) return true;
		if($button->getSide(Vector3::SIDE_EAST)->getId() == 246) return true;
		if($button->getSide(Vector3::SIDE_EAST_WEST)->getId() == 246) return true;
		if($button->getSide(Vector3::SIDE_WEST)->getId() == 246) return true;
		return false;
	}

	public function isCannonLoaded(Block $button){
		if($this->isCannon($button) == false) return false;
		$side = 0;
		if($this->getCannonLoadLoc($button)->getId() != 46){
			return false;
		}
		else{
			return true;
		}
	}

	public function getCannonLoadLoc(Block $button){
		$side = $this->getCannonOrientation($button);
		$obsidian = $button->getSide($side);
		$load = $obsidian->getSide(Vector3::SIDE_UP);
		return $load;
	}

	public function getCannonBaseLoc(Block $button){
		$side = $this->getCannonOrientation($button);
		$obsidian = $button->getSide($side);
		return $obsidian;
	}

	public function getCannonOrientation(Block $button){
		if($this->isCannon($button) == false) return false;
		$orientation = null;
		if($button->getSide(Vector3::SIDE_NORTH)->getId() == 49) $orientation = "north";
		if($button->getSide(Vector3::SIDE_SOUTH)->getId() == 49) $orientation = "south";
		if($button->getSide(Vector3::SIDE_EAST)->getId() == 49) $orientation = "east";
		if($button->getSide(Vector3::SIDE_WEST)->getId() == 49) $orientation = "west";
		if($button->getSide(Vector3::SIDE_NORTH)->getId() == 246) $orientation = "north";
		if($button->getSide(Vector3::SIDE_SOUTH)->getId() == 246) $orientation = "south";
		if($button->getSide(Vector3::SIDE_EAST)->getId() == 246) $orientation = "east";
		if($button->getSide(Vector3::SIDE_WEST)->getId() == 246) $orientation = "west";
		if($button->getSide(Vector3::SIDE_NORTH)->getId() == 5) return true;
		if($button->getSide(Vector3::SIDE_SOUTH)->getId() == 5) return true;
		if($button->getSide(Vector3::SIDE_EAST)->getId() == 5) return true;
		if($button->getSide(Vector3::SIDE_WEST)->getId() == 5) return true;
		$dir = 0;
		switch($orientation){
			case "north":
				$dir = 2;
			break;
			case "south":
				$dir = 3;
			break;
			case "east":
				$dir = 5;
			break;
			case "west":
				$dir = 4;
			break;
		}
		return $dir;
	}

	public function getCannonOrientationName(Block $button){
		$orientation = $this->getCannonOrientation($button);
		$name = null;
		switch($orientation){
			case 2:
				$name = "north";
			break;
			case 3:
				$name = "south";
			break;
			case 4:
				$name = "west";
			break;
			case 5:
				$name = "east";
			break;
		}
		return $name;
	}

	public function launchCannon(Block $button, Player $launcher){
		$load = $this->getCannonLoadLoc($button);
		$load->getLevel()->setBlock($load,Block::get(0,0));

		$yaw = 0;
		if($this->getCannonOrientation($button) == 2) $yaw = 180;
		if($this->getCannonOrientation($button) == 5) $yaw = 270;
		if($this->getCannonOrientation($button) == 3) $yaw = 0;
		if($this->getCannonOrientation($button) == 4) $yaw = 90;
		$pitch = 1;
		$nbt = new CompoundTag ("", [ 
			"Pos" => new ListTag ("Pos", [ 
				new DoubleTag ("", $load->getX() + 0.5),
				new DoubleTag ("", $load->getY()),
				new DoubleTag ("", $load->getZ() + 0.5) 
			]),
			"Motion" => new ListTag ("Motion", [ 
				new DoubleTag("", - \sin($yaw / 280 * M_PI) * \cos($pitch / 180 * M_PI)),
				new DoubleTag("", - \sin ($pitch / 280 * M_PI )),
				new DoubleTag("", \cos($yaw / 280 * M_PI) * \cos($pitch / 180 * M_PI )) 
			]),
			"Rotation" => new ListTag("Rotation", [ 
				new FloatTag("", $yaw),
				new FloatTag("", $pitch) 
			])
		]);
		$cannonload = Entity::createEntity("PrimedTNT",$launcher->getLevel(),$nbt,true);
		$cannonload->setMotion($cannonload->getMotion()->multiply($this->getConfig()->get("launch-speed")));
		$cannonload->spawnToAll();
	}

	///////////////////


	public function onInteract(PlayerInteractEvent $e){
		$p = $e->getPlayer();
		$b = $e->getBlock();
		if($b->getId() == 77 || $b->getId() == 143){
			if($this->isCannon($b)){
				$e->setCancelled();
				$p->getLevel()->addSound(new ClickSound($p));
				if($this->isCannonLoaded($b)){
					$this->launchCannon($b,$p);
					$b->getLevel()->addParticle(new MobSpawnParticle($this->getCannonLoadLoc($b)->add(0.5,0,0.5)));
					if($this->getConfig()->get("trigger-detach") == true){
						$b->getLevel()->dropItem($b->add(0.5,0,0.5), Item::get($b->getId(),$b->getDamage(),1));
						$b->getLevel()->setBlock($b, Block::get(0,0));
					}
					if($this->getConfig()->get("cannon-messages") == true){
						$p->sendMessage(str_replace("{DIRECTION}", $this->getCannonOrientationName($b), $this->getConfig()->get("cannon-launch-message")));
					}
					foreach($this->getServer()->getOnlinePlayers() as $pl){
						if($pl->getLevel() == $b->getLevel()){
							if($pl->getPosition()->distance($b) <= $this->getConfig()->get("cannon-sense-distance")){
								$pl->getLevel()->addSound(new GhastShootSound($pl->subtract(0,2)));
								if($this->getConfig()->get("cannon-messages") == true){
									if($pl != $p){
										$pl->sendPopup($this->getConfig()->get("cannon-launch-popup"));
									}
								}
							}
						}
					}
				}
				else{
					if($this->getConfig()->get("cannon-messages") == true){
						$p->sendMessage($this->getConfig()->get("cannon-unloaded-message"));
					}
				}
			}
		}
	}

}
