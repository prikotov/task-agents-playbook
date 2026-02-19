<?php

declare(strict_types=1);

namespace Web\Module\Health\Form\Incident;

use Common\Module\Health\Application\Enum\IncidentSeverityEnum;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<CreateFormModel>
 */
final class CreateFormType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Валидация определена в CreateFormModel через Assert attributes
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'label' => 'health.incident.form.title.label',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'health.incident.form.description.label',
            ])
            ->add('severity', EnumType::class, [
                'class' => IncidentSeverityEnum::class,
                'required' => true,
                'label' => 'health.incident.form.severity.label',
            ])
            ->add('affectedServiceNames', TextType::class, [
                'required' => false,
                'label' => 'health.incident.form.affected_services.label',
                'help' => 'health.incident.form.affected_services.help',
                'mapped' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'health.incident.form.create.submit',
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateFormModel::class,
            'translation_domain' => 'messages',
        ]);
    }
}
