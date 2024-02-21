<?php

namespace ObbyBlaster;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;

use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;

use pocketmine\entity\Entity;
use pocketmine\entity\object\PrimedTNT;

use pocketmine\math\Vector3;

use pocketmine\utils\Config;

use pocketmine\player\Player;

class MainClass extends PluginBase implements Listener {

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onBreak(BlockBreakEvent $event) {
        // Cannon launch logic
    }

    public function onInteract(PlayerInteractEvent $event){
        $block = $event->getBlock();
        
        if(!$this->isCannon($block)){
            return;
        }

        $launchDistance = $this->getConfig()->get("cannon-launch-distance", 10);
        $this->launchTNT($block, $launchDistance);
    }

    private function launchTNT(Block $button, int $distance){
        $direction = $this->getCannonDirection($button);

        for($i = 0; $i < $distance; $i++){
            $tnt = new PrimedTNT($button->asVector3()->add($direction->multiply($i)));
            $tnt->spawnToAll();
        }
    }

    public function isCannon(Block $button): bool {
        if($button->getSide(Vector3::SIDE_NORTH)->getId() == BlockLegacyIds::OBSIDIAN) {
            return true;
        }
        
        if($button->getSide(Vector3::SIDE_SOUTH)->getId() == BlockLegacyIds::OBSIDIAN) {
            return true;
        }

        if($button->getSide(Vector3::SIDE_EAST)->getId() == BlockLegacyIds::OBSIDIAN) {
            return true;
        }

        if($button->getSide(Vector3::SIDE_WEST)->getId() == BlockLegacyIds::OBSIDIAN) {
            return true;
        }

        return false;
    }

    private function sendCannonMessage(Player $player, string $direction){
        $config = $this->getConfig();

        if($config->get("cannon-messages")){
            $message = $config->get("cannon-launch-message");
            $message = str_replace("{DIRECTION}", $direction, $message);
            $player->sendMessage($message);
        }

        if($config->get("cannon-launch-popup")){
            $popupMessage = $config->get("cannon-launch-popup"); 
            $player->sendPopup($popupMessage);
        }
    }
}
