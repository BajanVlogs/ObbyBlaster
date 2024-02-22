<?php

declare(strict_types=1);

namespace ObbyBlaster;

use pocketmine\block\Block;
use pocketmine\world\Position;

final class ObsidianData
{
    /**
     * ObsidianData Constructor.
     *
     * @param Block $block
     * @param int $count
     *
     */
    public function __construct(
        private Block $block,
        private int $count = 1
    ){}

    public function getBlock() : Block
    {
        return $this->block;
    }

    public function getPosition() : Position
    {
        return $this->block->getPosition();
    }

    public function getCount() : int
    {
        return $this->count;
    }

    public function addCount(int $count = 1) : void
    {
        $this->count += $count;
    }
}
