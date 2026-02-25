# Контроллер презентационного слоя (Presentation Controller)

## Определение

**Контроллер презентационного слоя (Presentation Controller)** — класс, связывающий HTTP-/CLI-запрос с
приложением, подготавливающий данные для Application-слоя и формирующий ответ. Подробнее см. руководство
[Symfony Controller](https://symfony.com/doc/current/controller.html).

## Общие правила

- Контроллер объявляем `final`, помечаем `#[AsController]` и храним в `apps/<app>/src/Module/<Module>/Controller`.
- Единственная публичная точка входа — `__invoke()`.
- Внедряем только публичные интерфейсы Application-слоя (CommandBus, QueryBus, DTO-мапперы) и сервисы Presentation.
- Все данные из запроса валидируем до вызова Application (DTO, формы, атрибуты `#[MapQueryString]`).
- Flash-сообщения отправляем через `$this->addFlash()` с переводами, редирект выполняем сразу после успешного действия.

## Зависимости

- Разрешено: `CommandBusComponentInterface`, `QueryBusComponentInterface`, роуты модуля, переводчик, мапперы пагинации и сортировки, формы, value object слоя Presentation.
- Запрещено: зависимости из `Domain/*`, `Infrastructure/*`, `Integration/*`, прямой доступ к репозиториям и ORM.

## Расположение

```
apps/<app>/src/Module/<ModuleName>/Controller/<Context>/<Action>Controller.php
```

## Как используем

1. Принимаем необходимые аргументы (`Request`, DTO из `#[MapQueryString]`, `#[CurrentUser]`).
2. Выполняем проверку прав (см. [«Проверка прав в Presentation»](authorization.md)).
3. Создаём и обрабатываем форму/DTO для входных данных.
4. Вызываем Application-UseCase через CommandBus/QueryBus.
5. Формируем ответ: рендер шаблона, JSON или редирект с flash-сообщениями.
6. Исключения отдаем на обработку `Web\EventSubscriber\ExceptionSubscriber`.

## Пример

```php
<?php

declare(strict_types=1);

namespace Web\Module\Llm\Controller\Provider;

use Common\Application\Component\CommandBus\CommandBusComponentInterface;
use Common\Module\Llm\Application\Enum\ProviderEnum as ApplicationProviderEnum;
use Common\Module\Llm\Application\UseCase\Command\Provider\Create\CreateCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Translation\TranslatorInterface;
use Web\Module\Llm\Controller\Provider\Enum\ProviderActionEnum;
use Web\Module\Llm\Form\Provider\CreateFormModel;
use Web\Module\Llm\Form\Provider\CreateFormType;
use Web\Module\Llm\Route\ProviderRoute;
use Web\Security\UserInterface;

#[Route(path: ProviderRoute::CREATE_PATH, name: ProviderRoute::CREATE, methods: [Request::METHOD_GET, Request::METHOD_POST])]
#[AsController]
final class CreateController extends AbstractController
{
    public function __construct(
        private readonly CommandBusComponentInterface $commandBus,
        private readonly ProviderRoute $providerRoute,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(Request $request, #[CurrentUser] UserInterface $currentUser): Response
    {
        if (!$this->isGranted(ProviderActionEnum::create->value)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(CreateFormType::class, new CreateFormModel());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CreateFormModel $model */
            $model = $form->getData();

            $this->commandBus->execute(new CreateCommand(
                ApplicationProviderEnum::from($model->getName()?->value ?? throw new \LogicException()),
                $model->isAutoCheckBalance(),
                $currentUser->getUuid(),
            ));

            $this->addFlash('success', $this->translator->trans('llm.provider.flash.created'));

            return $this->redirectToRoute(ProviderRoute::LIST);
        }

        return $this->render('@web.llm/provider/create.html.twig', [
            'form' => $form,
            'providerRoute' => $this->providerRoute,
        ]);
    }
}
```

## Обработка загрузки файлов

Для загрузки файлов через формы используется стандартный подход Symfony с сохранением в `var/uploads/`.

### Паттерн загрузки

1. FormModel содержит поле `?UploadedFile $uploadFile`.
2. FormType использует `FileType` с валидацией MIME-type и размера.
3. Контроллер сохраняет файл в `var/uploads/` с уникальным именем.
4. Путь к файлу передаётся в Command для дальнейшей обработки.

### Пример

**FormModel:**

```php
final class CreateFormModel
{
    public function __construct(
        public ?UploadedFile $uploadFile = null,
        public ?string $textData = null,
    ) {
    }
}
```

**FormType:**

```php
$builder
    ->add('uploadFile', FileType::class, [
        'required' => false,
        'label' => 'source.labels.file',
        'constraints' => [
            new File([
                'maxSize' => '100M',
                'mimeTypes' => ['audio/mpeg', 'audio/wav', 'audio/ogg'],
                'mimeTypesMessage' => 'source.constraints.invalid_mime_type',
            ]),
        ],
    ]);
```

**Контроллер:**

```php
if ($formModel->uploadFile instanceof UploadedFile) {
    $projectDir = $this->getParameter('kernel.project_dir');
    if (!is_string($projectDir)) {
        throw new \RuntimeException('Invalid project dir parameter');
    }
    
    $uploadDir = $projectDir . '/var/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $slugger = new AsciiSlugger();
    $originalFilename = pathinfo($formModel->uploadFile->getClientOriginalName(), PATHINFO_FILENAME);
    $safeFilename = $slugger->slug($originalFilename);
    $newFilename = $safeFilename->toString() . '-' . uniqid() . '.' . $formModel->uploadFile->guessExtension();
    
    $uploadFile = $formModel->uploadFile->move($uploadDir, $newFilename);
    
    $this->commandBus->execute(new CreateByFileCommand(
        $projectUuid,
        $uploadFile,
        $formModel->uploadFile->getClientOriginalName(),
    ));
}
```

### Валидация

- **Тип файла:** через `mimeTypes` в constraint `File`.
- **Размер:** через `maxSize` (например, `100M`).
- **Обработка ошибок:** `FileException` пробрасывается или логируется.

## Чек-лист для проведения ревью кода

- [ ] Контроллер хранится в каталоге Presentation и объявлен `final`.
- [ ] Права проверяются через Permission Enum / атрибуты, а не вручную.
- [ ] Внедрены только Presentation- и Application-зависимости.
- [ ] Валидация входных данных выполняется до вызова UseCase.
- [ ] Возврат идёт через рендер/редирект, исключения обрабатывает `ExceptionSubscriber`.
- [ ] Загрузка файлов использует валидацию через constraints и безопасное именование.
