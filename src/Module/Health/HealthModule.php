<?php

declare(strict_types=1);

namespace Common\Module\Health;

use Common\Component\ModuleSystem\Extension\DoctrineInterface;
use Common\Component\ModuleSystem\ModuleInterface;
use Override;

final class HealthModule implements ModuleInterface, DoctrineInterface
{
    #[Override]
    public function getModuleDir(): string
    {
        return __DIR__;
    }

    #[Override]
    public function getModuleConfigPath(): string
    {
        return $this->getModuleDir() . '/Resource/config';
    }

    #[Override]
    public function getEntityNamespace(): string
    {
        return __NAMESPACE__ . '\Domain\Entity';
    }

    #[Override]
    public function getMappingPath(): string
    {
        return $this->getModuleDir() . '/Domain/Entity';
    }
}
