<?php

namespace Survos\BadBotBundle\Service;

use Inspector\Inspector;
use Psr\Log\LoggerInterface;
use Survos\KeyValueBundle\Entity\KeyValueManagerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\CacheInterface;

class BotService
{
    // todo: make configurable, but for now just get it working.
    public const PROBE_LIST_NAME='probe';
    public function __construct(
        private KeyValueManagerInterface $kvManager,
        private array $config = []
    )
    {

    }

    public function getProbePaths(): array
    {
        // $this->config[]
        return $this->kvManager->getList(self::PROBE_LIST_NAME);
    }


}
