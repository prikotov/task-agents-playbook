<?php

declare(strict_types=1);

namespace Web\Module\Health\Form\Incident;

use Common\Module\Health\Application\Enum\IncidentSeverityEnum;
use Common\Module\Health\Application\Enum\IncidentStatusEnum;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<FilterFormModel>
 */
final class FilterFormType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', EnumType::class, [
                'class' => IncidentStatusEnum::class,
                'required' => false,
                'label' => 'health.incident.filter.status.label',
                'placeholder' => 'health.incident.filter.status.placeholder',
            ])
            ->add('severity', EnumType::class, [
                'class' => IncidentSeverityEnum::class,
                'required' => false,
                'label' => 'health.incident.filter.severity.label',
                'placeholder' => 'health.incident.filter.severity.placeholder',
            ])
            ->add('activeOnly', CheckboxType::class, [
                'required' => false,
                'label' => 'health.incident.filter.active_only.label',
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FilterFormModel::class,
            'method' => 'GET',
            'csrf_protection' => false,
            'translation_domain' => 'messages',
        ]);
    }
}
