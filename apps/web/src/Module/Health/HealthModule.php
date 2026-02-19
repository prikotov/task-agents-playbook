<?php

declare(strict_types=1);

namespace Web\Module\Health;

use Common\Component\ModuleSystem\Extension\TranslationInterface;
use Common\Component\ModuleSystem\Extension\TwigInterface;
use Common\Component\ModuleSystem\ModuleInterface;
use Common\Component\ModuleSystem\Trait\TwigWidgetModuleTrait;

final class HealthModule implements ModuleInterface, TwigInterface, TranslationInterface
{
    use TwigWidgetModuleTrait;

    #[\Override]
    public function getModuleDir(): string
    {
        return __DIR__;
    }

    #[\Override]
    public function getModuleConfigPath(): string
    {
        return $this->getModuleDir() . '/Resource/config';
    }

    #[\Override]
    public function getBaseTemplatesPath(): string
    {
        return $this->getModuleDir() . '/Resource/templates';
    }

    #[\Override]
    public function getBaseTwigNamespace(): string
    {
        return 'web.health';
    }

    #[\Override]
    public function getBaseTranslationsPath(): string
    {
        return $this->getModuleDir() . '/Resource/translations';
    }

    #[\Override]
    public function getAdditionalTranslationsPaths(): array
    {
        return [];
    }
}
