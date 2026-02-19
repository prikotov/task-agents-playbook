<?php

declare(strict_types=1);

namespace Common\Module\Health\Domain\Entity;

use Common\Component\Clock\ClockFactory;
use Common\Component\Doctrine\Model\IdModelInterface;
use Common\Component\Doctrine\Model\InsTsModelInterface;
use Common\Component\Doctrine\Model\UpdTsModelInterface;
use Common\Component\Doctrine\Model\UuidModelInterface;
use Common\Component\Doctrine\Trait\IdTrait;
use Common\Component\Doctrine\Trait\InsTsTrait;
use Common\Component\Doctrine\Trait\UpdTsTrait;
use Common\Component\Doctrine\Trait\UuidTrait;
use Common\Module\Health\Domain\Enum\IncidentSeverityEnum;
use Common\Module\Health\Domain\Enum\IncidentStatusEnum;
use Common\Module\Health\Domain\ValueObject\ServiceNameVo;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Entity для хранения инцидента в системе мониторинга.
 */
#[ORM\Entity]
#[ORM\Table(name: 'health_incident')]
class IncidentModel implements IdModelInterface, UuidModelInterface, InsTsModelInterface, UpdTsModelInterface
{
    use IdTrait;
    use UuidTrait;
    use InsTsTrait;
    use UpdTsTrait;

    /**
     * @param array<int, string> $affectedServiceNames
     */
    public function __construct(
        #[ORM\Column(type: Types::STRING, length: 255)]
        private string $title,
        #[ORM\Column(type: Types::TEXT, nullable: true)]
        private ?string $description = null,
        #[ORM\Column(type: Types::STRING, length: 20, enumType: IncidentStatusEnum::class)]
        private IncidentStatusEnum $status = IncidentStatusEnum::investigating,
        #[ORM\Column(type: Types::STRING, length: 20, enumType: IncidentSeverityEnum::class)]
        private IncidentSeverityEnum $severity = IncidentSeverityEnum::minor,
        /** @var array<int, string> List of service names affected by this incident */
        #[ORM\Column(type: Types::JSON, nullable: true)]
        private array $affectedServiceNames = [],
        #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
        private ?\DateTimeImmutable $resolvedAt = null,
    ) {
        $this->insTs = ClockFactory::create()->now();
        $this->uuid = Uuid::v7();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        if ($this->title !== $title) {
            $this->updTs = ClockFactory::create()->now();
            $this->title = $title;
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        if ($this->description !== $description) {
            $this->updTs = ClockFactory::create()->now();
            $this->description = $description;
        }

        return $this;
    }

    public function getStatus(): IncidentStatusEnum
    {
        return $this->status;
    }

    /**
     * Обновляет статус инцидента.
     */
    public function updateStatus(IncidentStatusEnum $status): static
    {
        if ($this->status !== $status) {
            $this->updTs = ClockFactory::create()->now();
            $this->status = $status;
        }

        return $this;
    }

    public function getSeverity(): IncidentSeverityEnum
    {
        return $this->severity;
    }

    public function setSeverity(IncidentSeverityEnum $severity): static
    {
        if ($this->severity !== $severity) {
            $this->updTs = ClockFactory::create()->now();
            $this->severity = $severity;
        }

        return $this;
    }

    /**
     * @return array<int, ServiceNameVo>
     */
    public function getAffectedServiceNames(): array
    {
        return array_map(
            static fn(string $name) => new ServiceNameVo($name),
            $this->affectedServiceNames,
        );
    }

    /**
     * @param array<int, string> $affectedServiceNames
     */
    public function setAffectedServiceNames(array $affectedServiceNames): static
    {
        if ($this->affectedServiceNames !== $affectedServiceNames) {
            $this->updTs = ClockFactory::create()->now();
            $this->affectedServiceNames = $affectedServiceNames;
        }

        return $this;
    }

    /**
     * Добавляет затронутый сервис.
     */
    public function addAffectedService(ServiceNameVo $serviceName): static
    {
        $serviceNameString = $serviceName->value;
        if (!in_array($serviceNameString, $this->affectedServiceNames, true)) {
            $this->updTs = ClockFactory::create()->now();
            $this->affectedServiceNames[] = $serviceNameString;
        }

        return $this;
    }

    /**
     * Удаляет затронутый сервис.
     */
    public function removeAffectedService(ServiceNameVo $serviceName): static
    {
        $serviceNameString = $serviceName->value;
        $key = array_search($serviceNameString, $this->affectedServiceNames, true);
        if ($key !== false) {
            $this->updTs = ClockFactory::create()->now();
            unset($this->affectedServiceNames[$key]);
            $this->affectedServiceNames = array_values($this->affectedServiceNames);
        }

        return $this;
    }

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    /**
     * Разрешает инцидент, устанавливая статус resolved и время разрешения.
     */
    public function resolve(): static
    {
        if ($this->status !== IncidentStatusEnum::resolved) {
            $this->updTs = ClockFactory::create()->now();
            $this->status = IncidentStatusEnum::resolved;
            $this->resolvedAt = ClockFactory::create()->now();
        }

        return $this;
    }

    /**
     * Проверяет, является ли инцидент разрешённым.
     */
    public function isResolved(): bool
    {
        return $this->status === IncidentStatusEnum::resolved;
    }

    /**
     * Проверяет, является ли инцидент активным.
     */
    public function isActive(): bool
    {
        return $this->status !== IncidentStatusEnum::resolved;
    }

    /**
     * Проверяет, является ли инцидент критическим.
     */
    public function isCritical(): bool
    {
        return $this->severity === IncidentSeverityEnum::critical;
    }
}
