<?php

declare(strict_types=1);

namespace ObbyBlaster;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener
{
    /** @var ObsidianData[] */
    private array $obsidian = [];

    public function onEnable() : void
    {
        $this->saveDefaultConfig();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onEntityExplode(EntityExplodeEvent $event) : void
    {
        $entity = $event->getEntity();

        if ($entity instanceof PrimedTNT) {
            $center = $entity->getWorld()->getBlockAt(
                $entity->getPosition()->getFloorX(),
                $entity->getPosition()->getFloorY(),
                $entity->getPosition()->getFloorZ()
            );

            // Cancel TNT Explosion in water
            if ($center instanceof Water) {
                return;
            }

            $affected = array_map(fn($i) => $center->getSide($i), range(0, 6));

            foreach ($affected as $block) {
                // If block isn't obsidian
                if(!$block instanceof (VanillaBlocks::OBSIDIAN())) {
                    continue;
                }

                // If ObsidianData already exists, then add count if it does.
                $obsidianData = $this->getObsidianData($block);
                if ($obsidianData) {
                    $obsidianData->addCount();
                } else {
                    $this->obsidian[] = new ObsidianData($block);
                }

                $this->obsidian = array_filter($this->obsidian, [$this, 'filterObsidianData']);
            }
        }
    }

    /**
     * Remove ObsidianData if the obsidian is broken by hand
     */
    public function onBlockBreak(BlockBreakEvent $event) : void
    {
        $this->obsidian = array_filter(
            $this->obsidian, fn($object) => !$object->getPosition()->equals($event->getBlock()->getPosition())
        );
    }

    private function getObsidianData(Block $block) : ?ObsidianData
    {
        foreach ($this->obsidian as $obsidianData) {
            if ($obsidianData->getPosition()->equals($block->getPosition())) {
                return $obsidianData;
            }
        }
        return null;
    }

    private function filterObsidianData(ObsidianData $object) : bool
    {
        if($object->getCount() >= $this->getConfig()->getNested("hit-count")) {
            $object->getPosition()->getWorld()->setBlockAt(
                $object->getPosition()->getFloorX(),
                $object->getPosition()->getFloorY(),
                $object->getPosition()->getFloorZ(),
                VanillaBlocks::AIR()
            );
            return false; // returning false will remove the ObsidianData from the array
        }
        return true;
    }
}
