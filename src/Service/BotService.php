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
    public const BANNED_IPS_LIST_NAME='ips';
    public function __construct(
        private KeyValueManagerInterface $kvManager,
        private LoggerInterface $logger,
        private CacheInterface $cache,
        private array $config = []
    )
    {

    }

    public function getProbePaths(): array
    {
        // $this->config[]
        return $this->kvManager->getList(self::PROBE_LIST_NAME);
    }

    public function getBannedIps(): array
    {
        // $this->config[]
        return $this->kvManager->getList(self::BANNED_IPS_LIST_NAME);
    }



    public function isBanned(string $ip): bool
    {
        // check the good list!
        if (in_array($ip, ['127.0.0.1'])) {
            return false;
        }
        // @todo: check cache
        return $this->kvManager->has($ip, self::BANNED_IPS_LIST_NAME);
    }

    public function ban(string $ip): void
    {
        // @todo: check cache
        if (!$this->kvManager->has($ip, self::BANNED_IPS_LIST_NAME)) {
            $this->kvManager->add($ip, self::BANNED_IPS_LIST_NAME);
        }
    }

}
