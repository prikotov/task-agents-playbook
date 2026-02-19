<?php

declare(strict_types=1);

namespace Web\Module\Health\Form\Dashboard;

use Common\Module\Health\Application\Enum\ServiceCategoryEnum;
use Common\Module\Health\Application\Enum\ServiceStatusEnum;
use Override;
use Symfony\Component\Form\AbstractType;
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
            ->add('category', EnumType::class, [
                'class' => ServiceCategoryEnum::class,
                'required' => false,
                'label' => 'health.dashboard.filter.category.label',
                'placeholder' => 'health.dashboard.filter.category.placeholder',
            ])
            ->add('status', EnumType::class, [
                'class' => ServiceStatusEnum::class,
                'required' => false,
                'label' => 'health.dashboard.filter.status.label',
                'placeholder' => 'health.dashboard.filter.status.placeholder',
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
