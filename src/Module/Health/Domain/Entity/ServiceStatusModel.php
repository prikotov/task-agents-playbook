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
use Common\Module\Health\Domain\Enum\ServiceCategoryEnum;
use Common\Module\Health\Domain\Enum\ServiceStatusEnum;
use Common\Module\Health\Domain\ValueObject\ServiceNameVo;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Entity для хранения статуса сервиса в мониторинге.
 */
#[ORM\Entity]
#[ORM\Table(name: 'health_service_status')]
#[ORM\UniqueConstraint(name: 'uniq_service_name', columns: ['name'])]
class ServiceStatusModel implements IdModelInterface, UuidModelInterface, InsTsModelInterface, UpdTsModelInterface
{
    use IdTrait;
    use UuidTrait;
    use InsTsTrait;
    use UpdTsTrait;

    public function __construct(
        #[ORM\Column(type: Types::STRING, length: 255)]
        private string $name,
        #[ORM\Column(type: Types::STRING, length: 50, enumType: ServiceCategoryEnum::class)]
        private ServiceCategoryEnum $category,
        #[ORM\Column(type: Types::STRING, length: 20, enumType: ServiceStatusEnum::class)]
        private ServiceStatusEnum $status,
        #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
        private ?\DateTimeImmutable $lastCheckAt = null,
        #[ORM\Column(type: Types::TEXT, nullable: true)]
        private ?string $message = null,
        #[ORM\Column(type: Types::FLOAT, nullable: true)]
        private ?float $responseTimeMs = null,
    ) {
        $this->insTs = ClockFactory::create()->now();
        $this->uuid = Uuid::v7();
    }

    /**
     * Создаёт новый статус сервиса из ValueObject ServiceNameVo.
     */
    public static function create(
        ServiceNameVo $name,
        ServiceCategoryEnum $category,
        ServiceStatusEnum $status = ServiceStatusEnum::operational,
    ): self {
        return new self(
            name: $name->value,
            category: $category,
            status: $status,
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getServiceName(): ServiceNameVo
    {
        return new ServiceNameVo($this->name);
    }

    public function setName(string $name): static
    {
        if ($this->name !== $name) {
            $this->updTs = ClockFactory::create()->now();
            $this->name = $name;
        }

        return $this;
    }

    public function getCategory(): ServiceCategoryEnum
    {
        return $this->category;
    }

    public function setCategory(ServiceCategoryEnum $category): static
    {
        if ($this->category !== $category) {
            $this->updTs = ClockFactory::create()->now();
            $this->category = $category;
        }

        return $this;
    }

    public function getStatus(): ServiceStatusEnum
    {
        return $this->status;
    }

    public function setStatus(ServiceStatusEnum $status): static
    {
        if ($this->status !== $status) {
            $this->updTs = ClockFactory::create()->now();
            $this->status = $status;
        }

        return $this;
    }

    public function getLastCheckAt(): ?\DateTimeImmutable
    {
        return $this->lastCheckAt;
    }

    public function setLastCheckAt(?\DateTimeImmutable $lastCheckAt): static
    {
        $this->updTs = ClockFactory::create()->now();
        $this->lastCheckAt = $lastCheckAt;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        if ($this->message !== $message) {
            $this->updTs = ClockFactory::create()->now();
            $this->message = $message;
        }

        return $this;
    }

    public function getResponseTimeMs(): ?float
    {
        return $this->responseTimeMs;
    }

    public function setResponseTimeMs(?float $responseTimeMs): static
    {
        $this->responseTimeMs = $responseTimeMs;

        return $this;
    }

    /**
     * Проверяет, является ли сервис операционным.
     */
    public function isOperational(): bool
    {
        return $this->status === ServiceStatusEnum::operational;
    }

    /**
     * Проверяет, требует ли сервис внимания.
     */
    public function requiresAttention(): bool
    {
        return $this->status === ServiceStatusEnum::degraded
            || $this->status === ServiceStatusEnum::outage;
    }
}
