<?php

declare(strict_types=1);

namespace Web\Module\Health\Form\Incident;

use Common\Module\Health\Application\Enum\IncidentSeverityEnum;
use Common\Module\Health\Application\Enum\IncidentStatusEnum;
use Symfony\Component\Validator\Constraints as Assert;

final class EditFormModel
{
    #[Assert\NotBlank(message: 'health.incident.form.title.not_blank')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'health.incident.form.title.min_length',
        maxMessage: 'health.incident.form.title.max_length',
    )]
    private string $title = '';

    #[Assert\Length(
        max: 2000,
        maxMessage: 'health.incident.form.description.max_length',
    )]
    private ?string $description = null;

    private IncidentStatusEnum $status = IncidentStatusEnum::investigating;

    private IncidentSeverityEnum $severity = IncidentSeverityEnum::minor;

    /** @var array<int, string> */
    private array $affectedServiceNames = [];

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getStatus(): IncidentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(IncidentStatusEnum $status): void
    {
        $this->status = $status;
    }

    public function getSeverity(): IncidentSeverityEnum
    {
        return $this->severity;
    }

    public function setSeverity(IncidentSeverityEnum $severity): void
    {
        $this->severity = $severity;
    }

    /**
     * @return array<int, string>
     */
    public function getAffectedServiceNames(): array
    {
        return $this->affectedServiceNames;
    }

    /**
     * @param array<int, string> $affectedServiceNames
     */
    public function setAffectedServiceNames(array $affectedServiceNames): void
    {
        $this->affectedServiceNames = $affectedServiceNames;
    }
}
