# Testing

Этот документ описывает систему тестирования в проекте TasK, включая виды тестов, инструменты, правила написания и примеры.

## Обзор системы тестирования

Проект использует многоуровневую систему тестирования для обеспечения качества кода и надёжности приложения:

- **Unit-тесты** — проверка бизнес-логики без внешних зависимостей
- **Integration-тесты** — проверка взаимодействия слоёв и инфраструктуры
- **Функциональные тесты** — проверка работы приложения через публичные интерфейсы
- **E2E-тесты** — проверка сценариев через публичные интерфейсы

Все тесты выполняются в окружении `test`, загружая конфигурацию из `config/packages/test/`.

## Виды тестов

### Unit-тесты

Unit-тесты проверяют бизнес-логику в изоляции от внешних зависимостей (БД, файловая система, внешние API).

**Расположение:** `tests/Unit/` (повторяет структуру `src/`)

**Характеристики:**
- Наследуются от `PHPUnit\Framework\TestCase`
- Не используют реальную БД или внешние сервисы
- Используют моки (mocks) и стабы (stubs) для зависимостей
- Быстрые и изолированные

**Пример unit-теста для сущности Domain:**

```php
<?php

declare(strict_types=1);

namespace Common\Test\Unit\Module\Source\Domain\Entity;

use Common\Module\Source\Domain\Entity\SourceModel;
use Common\Module\Source\Domain\Enum\SourceStatusEnum;
use Common\Module\Source\Domain\Enum\SourceTypeEnum;
use Common\Module\Source\Domain\Exception\EmptyFilenameException;
use PHPUnit\Framework\TestCase;

final class SourceModelTest extends TestCase
{
    public function testConstructorRejectsEmptyFilename(): void
    {
        $this->expectException(EmptyFilenameException::class);
        $this->expectExceptionMessage('Filename cannot be empty.');

        new SourceModel(
            uri: 'http://example.com',
            status: SourceStatusEnum::new,
            type: SourceTypeEnum::text,
            publishedAt: null,
            updatedAt: null,
            title: 't',
            description: 'd',
            creator: null,
            additionalParams: null,
            filename: '',
            viewCount: null,
            commentCount: null,
            likeCount: null,
            duration: null,
            size: null,
            channelId: null,
            channelTitle: null,
            coAuthors: null,
            rawTags: null,
        );
    }

    public function testTags(): void
    {
        $model = new SourceModel(
            uri: 'http://example.com',
            status: SourceStatusEnum::new,
            type: SourceTypeEnum::text,
            publishedAt: null,
            updatedAt: null,
            title: 't',
            description: 'd',
            creator: null,
            additionalParams: null,
            filename: null,
            viewCount: null,
            commentCount: null,
            likeCount: null,
            duration: null,
            size: null,
            channelId: null,
            channelTitle: null,
            coAuthors: null,
            rawTags: null,
        );

        $this->assertCount(0, $model->getTags());
        // ... дальнейшие проверки
    }
}
```

**Пример unit-теста для Use Case:**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Module\Source\Application\UseCase\Command\Source\CreateByContent;

use Common\Application\Dto\IdDto;
use Common\Component\Event\EventBusInterface;
use Common\Component\Persistence\PersistenceManagerInterface;
use Common\Module\Source\Application\Event\Source\CreatedEvent;
use Common\Module\Source\Application\UseCase\Command\Source\CreateByContent\CreateByContentCommand;
use Common\Module\Source\Application\UseCase\Command\Source\CreateByContent\CreateByContentCommandHandler;
use Common\Module\Source\Domain\Entity\SourceModel;
use Common\Module\Source\Domain\Service\Source\CreatorByFile\CreatorByFileServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CreateByContentCommandHandlerTest extends TestCase
{
    public function testHandleCreatesSourceAndUpdatesTitle(): void
    {
        $creator = $this->createMock(CreatorByFileServiceInterface::class);
        $persistence = $this->createMock(PersistenceManagerInterface::class);
        $eventBus = $this->createMock(EventBusInterface::class);
        $launchNextStep = $this->createMock(LaunchNextSourceWorkflowStepServiceInterface::class);

        $source = $this->createMock(SourceModel::class);
        $source->expects(self::once())->method('getId')->willReturn(10);
        $uuid = Uuid::v4();
        $source->expects(self::exactly(2))->method('getUuid')->willReturn($uuid);
        $source->expects(self::once())->method('getUri')->willReturn('doc.txt');
        $source->expects(self::once())->method('setTitle')->with('doc');
        $launchNextStep->expects(self::once())->method('launch')->with($source);

        $creator->expects(self::once())
            ->method('create')
            ->willReturn($source);

        $persistence->expects(self::once())->method('persist')->with($source);
        $persistence->expects(self::once())->method('flush');

        $eventBus->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(CreatedEvent::class));

        $handler = new CreateByContentCommandHandler(
            $creator,
            $persistence,
            $eventBus,
            $launchNextStep,
            new ApplicationToDomainSourceDiarizationModeMapper(),
            new ApplicationToDomainSourceLanguageMapper(),
        );

        $command = new CreateByContentCommand(
            Uuid::v4(),
            'content',
            'doc',
            SourceDiarizationModeEnum::no,
            SourceLanguageEnum::en,
            Uuid::v4()
        );

        $result = $handler($command);

        self::assertInstanceOf(IdDto::class, $result);
        self::assertSame(10, $result->id);
        self::assertTrue($uuid->equals($result->uuid));
    }
}
```

### Integration-тесты

Integration-тесты проверяют взаимодействие слоёв и инфраструктуры с использованием тестовой БД и моков для внешних API.

**Расположение:** `tests/Integration/` (повторяет структуру `src/`)

**Характеристики:**
- **Обязательное наследование** от `Common\Component\Test\KernelTestCase`
- Используют реальную тестовую БД (PostgreSQL)
- Инициализируют ядро Symfony через `self::bootKernel()`
- Ядро перезапускается перед каждым тестом для изоляции
- Используют Fake/Mock для внешних API

**Подготовка test DB:**
- Конфигурация подключения находится в `.env.test` (PostgreSQL).
- При необходимости обновить test DB используйте `make test-db-prepare` (создание БД, обновление схемы, `vector` extension).
- Для загрузки DataFixtures используйте `make test-db-fixtures` или `make tests-integration-fixtures`.
- По умолчанию эти команды выполняются внутри контейнера `php-test` и используют `bin/console`.
- Чтобы запустить локально, используйте `USE_DOCKER=0`.

> **Важно:** Все интеграционные и функциональные тесты обязаны наследоваться от `Common\Component\Test\KernelTestCase`. Это гарантирует корректный запуск ядра Symfony и доступ к сервисам контейнера.

**Пример integration-теста:**

```php
<?php

declare(strict_types=1);

namespace Common\Test\Integration\Module\User\Application\UseCase\Command\User\Register;

use Common\Component\Persistence\PersistenceManagerInterface;
use Common\Component\Test\KernelTestCase;
use Common\Exception\AccessDeniedException;
use Common\Module\User\Application\Enum\UserSourceEnum;
use Common\Module\User\Application\Enum\UserStatusEnum;
use Common\Module\User\Application\UseCase\Command\User\Register\RegisterCommand;
use Common\Module\User\Application\UseCase\Command\User\Register\RegisterCommandHandler;
use Common\Module\User\Domain\Entity\InvitationModel;
use Common\Module\User\Domain\Enum\InvitationStatusEnum;
use Common\Module\User\Domain\Repository\Invitation\Criteria\InvitationFindCriteria;
use Common\Module\User\Domain\Repository\Invitation\InvitationRepositoryInterface;
use Common\Module\User\Domain\Repository\User\Criteria\UserFindCriteria;
use Common\Module\User\Domain\Repository\User\UserRepositoryInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

final class RegisterCommandHandlerTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema($entityManager->getMetadataFactory()->getAllMetadata());
    }

    public function testRegistrationWithoutInviteWhenFlagDisabled(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var RegisterCommandHandler $handler */
        $handler = $container->get(RegisterCommandHandler::class);

        $handler(new RegisterCommand(
            email: 'bar@example.com',
            username: 'bar',
            password: 'password',
            phone: null,
            firstName: null,
            lastName: null,
            status: UserStatusEnum::inactive,
            isEmailVerified: true,
            registerSource: UserSourceEnum::form,
            registerIp: null,
            registerUserAgent: null,
            registerData: null,
            inviteCode: null,
            inviteOnly: false,
        ));

        $userRepository = $container->get(UserRepositoryInterface::class);
        $user = $userRepository->getOneByCriteria(new UserFindCriteria(email: 'bar@example.com'));

        self::assertNotNull($user);
    }

    public function testRegistrationByInviteMarksInvitationAccepted(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var PersistenceManagerInterface $persistenceManager */
        $persistenceManager = $container->get(PersistenceManagerInterface::class);
        $invitation = new InvitationModel('integration-accept', 'invite-accepted@example.com');
        $invitation->markrRequested();
        $invitation->markSent();
        $invitation->setExpiresAt(new DateTimeImmutable('+1 day'));
        $persistenceManager->persist($invitation);
        $persistenceManager->flush();

        /** @var RegisterCommandHandler $handler */
        $handler = $container->get(RegisterCommandHandler::class);

        $handler(new RegisterCommand(
            email: 'invite-accepted@example.com',
            username: 'invite-accepted',
            password: 'password',
            phone: null,
            firstName: null,
            lastName: null,
            status: UserStatusEnum::inactive,
            isEmailVerified: true,
            registerSource: UserSourceEnum::form,
            registerIp: null,
            registerUserAgent: null,
            registerData: null,
            inviteCode: 'integration-accept',
            inviteOnly: true,
        ));

        /** @var InvitationRepositoryInterface $invitationRepository */
        $invitationRepository = $container->get(InvitationRepositoryInterface::class);
        $storedInvitation = $invitationRepository->getOneByCriteria(new InvitationFindCriteria(code: 'integration-accept'));

        self::assertNotNull($storedInvitation);
        self::assertSame(InvitationStatusEnum::accepted, $storedInvitation->getStatus());
        self::assertNotNull($storedInvitation->getAcceptedAt());
        self::assertSame(1, $storedInvitation->getEmailSendCount());
    }
}
```

## Фикстуры (DataFixtures)

Фикстуры — это классы для наполнения тестовой базы данных начальными данными. Они используются для создания тестовых сущностей (пользователи, проекты, роли и др.) в изолированной тестовой среде.

### Назначение фикстур

- Подготовка тестовой среды с предопределёнными данными
- Создание зависимостей между сущностями (пользователи → проекты → роли)
- Обеспечение повторяемости тестов
- Упрощение создания сложных сценариев для integration-тестов

### Команды для работы с фикстурами

Для работы с фикстурами в тестовом окружении используются следующие команды:

```bash
# Очистка тестовой схемы БД
php bin/console --env=test doctrine:schema:drop --force

# Обновление схемы БД
php bin/console --env=test doctrine:schema:update --force

# Загрузка фикстур
php bin/console --env=test doctrine:fixtures:load
```

**Важно:** Фикстуры загружаются только в окружении `test` и никогда не должны применяться к production-базе данных.

### Правила создания и поддержки фикстур

#### Структура фикстуры

Все фикстуры должны:

1. Наследоваться от `Doctrine\Bundle\FixturesBundle\Fixture`
2. Реализовывать метод `load(ObjectManager $manager)` для создания данных
3. Использовать `$manager->persist()` для добавления сущностей
4. Вызывать `$manager->flush()` в конце метода `load()`
5. Определять зависимости через интерфейс `DependentFixtureInterface` при необходимости

#### Использование ссылок (references)

Для связывания фикстур между собой используйте механизм ссылок:

```php
// В одной фикстуре добавляем ссылку
$this->addReference('test-user', $user);

// В зависимой фикстуре получаем ссылку
$user = $this->getReference('test-user', UserModel::class);
```

#### Константы для ссылок

Определяйте константы для имён ссылок в начале класса фикстуры:

```php
final class UserFixtures extends Fixture
{
    public const string TEST_USER_REFERENCE = 'test-user';
    public const string TEST_USER_REFERENCE2 = 'test-user2';
    public const string TEST_ADMIN_USER_REFERENCE = 'test-admin-username';
    
    // ...
}
```

Это позволяет использовать ссылки в других фикстурах без опечаток:

```php
$user = $this->getReference(UserFixtures::TEST_USER_REFERENCE, UserModel::class);
```

#### Управление зависимостями

Если фикстура зависит от данных из других фикстур, реализуйте интерфейс `DependentFixtureInterface`:

```php
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class ProjectFixtures extends Fixture implements DependentFixtureInterface
{
    #[Override]
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
    
    // ...
}
```

Doctrine автоматически загрузит фикстуры в правильном порядке.

### Примеры фикстур из проекта

#### UserFixtures — создание пользователей

```php
<?php

declare(strict_types=1);

namespace Common\DataFixtures;

use Common\Module\Billing\Domain\Entity\UserBillingModel;
use Common\Module\User\Domain\Entity\UserModel;
use Common\Module\User\Domain\Enum\UserStatusEnum;
use Common\Module\User\Domain\Enum\UserTypeEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Override;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends Fixture
{
    public const string MAIN_USERNAME = 'test-username';
    public const string MAIN_PASSWORD = '123456';

    public const string SECONDARY_USERNAME = 'test-username2';
    public const string SECONDARY_PASSWORD = '234567';

    public const string ADMIN_USERNAME = 'test-admin-user';
    public const string ADMIN_PASSWORD = '654321';

    public const string TEST_USER_REFERENCE = 'test-user';
    public const string TEST_USER_REFERENCE2 = 'test-user2';
    public const string TEST_ADMIN_USER_REFERENCE = 'test-admin-username';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Override]
    public function load(ObjectManager $manager): void
    {
        [$testUser, $testUserBilling] = $this->createUser(
            'test@example.com',
            self::MAIN_USERNAME,
            null,
            UserStatusEnum::active,
            self::MAIN_PASSWORD,
        );
        $manager->persist($testUser);
        $manager->persist($testUserBilling);
        $this->addReference(self::TEST_USER_REFERENCE, $testUser);

        [$testAdmin, $testAdminBilling] = $this->createUser(
            'test-admin@example.com',
            self::ADMIN_USERNAME,
            null,
            UserStatusEnum::active,
            self::ADMIN_PASSWORD,
        );
        $manager->persist($testAdmin);
        $manager->persist($testAdminBilling);
        $this->addReference(self::TEST_ADMIN_USER_REFERENCE, $testAdmin);

        [$testUser2, $testUser2Billing] = $this->createUser(
            'test2@example.com',
            self::SECONDARY_USERNAME,
            null,
            UserStatusEnum::active,
            self::SECONDARY_PASSWORD,
        );
        $manager->persist($testUser2);
        $manager->persist($testUser2Billing);
        $this->addReference(self::TEST_USER_REFERENCE2, $testUser2);

        $manager->flush();
    }

    /**
     * @return array{UserModel, UserBillingModel}
     */
    private function createUser(
        string $email,
        string $username,
        ?string $phone,
        UserStatusEnum $status,
        string $password,
        UserTypeEnum $type = UserTypeEnum::human,
    ): array {
        $user = new UserModel(
            $email,
            $username,
            $status,
            $type,
            $phone,
        );
        $billing = new UserBillingModel($user->getUuid());
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $password,
        );
        $user->setPassword($hashedPassword);
        $user->setIsEmailVerified(true);

        $user->setAvatar('avatar_filename.png');

        return [$user, $billing];
    }
}
```

**Ключевые моменты:**
- Использует `UserPasswordHasherInterface` для хеширования паролей
- Создаёт связанные сущности (`UserModel` и `UserBillingModel`)
- Определяет константы для имён пользователей и ссылок
- Добавляет ссылки для использования в других фикстурах

#### UserRoleFixture — создание ролей с зависимостями

```php
<?php

declare(strict_types=1);

namespace Common\DataFixtures;

use Common\Module\User\Domain\Entity\UserModel;
use Common\Module\User\Domain\Entity\UserRoleModel;
use Common\Module\User\Domain\Enum\RoleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

final class UserRoleFixture extends Fixture implements DependentFixtureInterface
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        $user = $this->getReference(UserFixtures::TEST_USER_REFERENCE, UserModel::class);
        $userRole = new UserRoleModel(
            $user,
            RoleEnum::user,
        );
        $manager->persist($userRole);

        $user2 = $this->getReference(UserFixtures::TEST_USER_REFERENCE2, UserModel::class);
        $userRole2 = new UserRoleModel(
            $user2,
            RoleEnum::user,
        );
        $manager->persist($userRole2);

        $userAdmin = $this->getReference(UserFixtures::TEST_ADMIN_USER_REFERENCE, UserModel::class);
        $userAdminRole = new UserRoleModel(
            $userAdmin,
            RoleEnum::admin,
        );
        $manager->persist($userAdminRole);

        $manager->flush();
    }

    #[Override]
    public function getDependencies(): array
    {
        return [
            UserFixtures::class
        ];
    }
}
```

**Ключевые моменты:**
- Реализует `DependentFixtureInterface` для указания зависимостей
- Получает пользователей через ссылки из `UserFixtures`
- Создаёт роли для разных пользователей

#### ProjectFixtures — создание проектов с циклами

```php
<?php

declare(strict_types=1);

namespace Common\DataFixtures;

use Common\Module\Project\Domain\Entity\ProjectModel;
use Common\Module\Project\Domain\Entity\ProjectUserModel;
use Common\Module\Project\Domain\Enum\ProjectStatusEnum;
use Common\Module\Project\Domain\Enum\ProjectUserTypeEnum;
use Common\Module\User\Domain\Entity\UserModel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

final class ProjectFixtures extends Fixture implements DependentFixtureInterface
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        $user1 = $this->getReference(UserFixtures::TEST_USER_REFERENCE, UserModel::class);
        $user2 = $this->getReference(UserFixtures::TEST_USER_REFERENCE2, UserModel::class);

        foreach ([$user1, $user2] as $user) {
            // Создаём 10 закрытых проектов для каждого пользователя
            for ($i = 0; $i < 10; $i++) {
                $project = new ProjectModel(
                    ProjectStatusEnum::closed,
                    'closed: title with user ' . $user->getUsername() . ' ' . $i,
                    'closed: description ' . $i,
                    $user,
                    null
                );
                $project->addProjectUser($this->owner($project, $user));
                $manager->persist($project);
            }

            // Создаём 10 новых проектов для каждого пользователя
            for ($i = 0; $i < 10; $i++) {
                $project = new ProjectModel(
                    ProjectStatusEnum::new,
                    'new: title with user ' . $user->getUsername() . ' ' . $i,
                    'new: description ' . $i,
                    $user,
                    null
                );
                $project->addProjectUser($this->owner($project, $user));
                $manager->persist($project);
            }

            // Создаём 10 активных проектов для каждого пользователя
            for ($i = 0; $i < 10; $i++) {
                $project = new ProjectModel(
                    ProjectStatusEnum::active,
                    'visible: title with user ' . $user->getUsername() . ' ' . $i,
                    'visible: description ' . $i,
                    $user,
                    null
                );
                $project->addProjectUser($this->owner($project, $user));
                $manager->persist($project);
            }

            // Создаём 10 активных проектов без владельца
            for ($i = 0; $i < 10; $i++) {
                $project = new ProjectModel(
                    ProjectStatusEnum::active,
                    'visible: title without user ' . $user->getUsername() . ' ' . $i,
                    'visible: description ' . $i,
                    null,
                    null
                );
                $manager->persist($project);
            }
        }

        $manager->flush();
    }

    #[Override]
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }

    private function owner(ProjectModel $project, UserModel $user): ProjectUserModel
    {
        return new ProjectUserModel($project, $user, ProjectUserTypeEnum::owner);
    }
}
```

**Ключевые моменты:**
- Использует циклы для создания множества сущностей
- Создаёт проекты с разными статусами для тестирования различных сценариев
- Добавляет связанные сущности (`ProjectUserModel`) через методы-хелперы
- Получает пользователей через ссылки из `UserFixtures`

### Рекомендации по организации фикстур

1. **Разделяйте фикстуры по доменам:** одна фикстура для пользователей, другая для проектов, третья для ролей и т.д.

2. **Используйте константы для ссылок:** это предотвращает опечатки и упрощает рефакторинг

3. **Создавайте методы-хелперы:** для повторяющегося кода (например, `createUser()`, `owner()`)

4. **Используйте циклы:** для создания множества похожих сущностей с разными параметрами

5. **Следуйте порядку зависимостей:** фикстуры без зависимостей → фикстуры с зависимостями

6. **Не создавайте избыточные данные:** только то, что нужно для тестов

7. **Документируйте фикстуры:** добавляйте PHPDoc для сложных методов и констант

8. **Используйте осмысленные имена:** `TEST_USER_REFERENCE`, `TEST_ADMIN_USER_REFERENCE` и т.д.

### Использование фикстур в тестах

Для использования фикстур в integration-тестах можно загрузить их через контейнер:

```php
use Common\DataFixtures\UserFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

protected function setUp(): void
{
    parent::setUp();

    /** @var EntityManagerInterface $entityManager */
    $entityManager = self::getContainer()->get(EntityManagerInterface::class);
    $schemaTool = new SchemaTool($entityManager);
    $schemaTool->updateSchema($entityManager->getMetadataFactory()->getAllMetadata());
}

public function testSomeScenario(): void
{
    self::bootKernel();
    $container = self::getContainer();

    // Получаем пользователя из фикстур через репозиторий
    $userRepository = $container->get(UserRepositoryInterface::class);
    $user = $userRepository->getOneByCriteria(new UserFindCriteria(username: UserFixtures::MAIN_USERNAME));

    self::assertNotNull($user);
}
```

### Существующие фикстуры в проекте

| Файл | Описание |
|------|----------|
| [`UserFixtures.php`](../../src/DataFixtures/UserFixtures.php) | Создаёт тестовых пользователей (test-user, test-user2, test-admin-user) |
| [`UserRoleFixture.php`](../../src/DataFixtures/UserRoleFixture.php) | Создаёт роли для пользователей (user, admin) |
| [`ProjectFixtures.php`](../../src/DataFixtures/ProjectFixtures.php) | Создаёт проекты с разными статусами (closed, new, active) |
| [`TeamFixtures.php`](../../src/DataFixtures/TeamFixtures.php) | Создаёт команды |
| [`UserAccessTokenFixture.php`](../../src/DataFixtures/UserAccessTokenFixture.php) | Создаёт токены доступа пользователей |

### E2E-тесты

E2E-тесты проверяют полные сценарии работы приложения через публичные интерфейсы (HTTP API, Web UI).

**Расположение:** `apps/*/tests/E2E/`

**Характеристики:**
- **Web E2E:** Используют **Symfony Panther** для тестирования через реальный браузер (поддержка JavaScript, Turbo, Stimulus).
- **API E2E:** Используют стандартный `WebTestCase` (через `ApiTestCase`) для проверки REST API без накладных расходов на браузер.
- Проверяют интеграцию между всеми модулями и инфраструктурой (DB, RabbitMQ, Redis).
- Позволяют делать скриншоты при падении (для Web).

Подробная документация по E2E: [`docs/testing/e2e.md`](../../docs/testing/e2e.md)

## Инструменты тестирования

### PHPUnit

Основной фреймворк для написания и запуска тестов.

**Конфигурация:** [`phpunit.xml.dist`](../../phpunit.xml.dist)

**Основные настройки:**
- Окружение: `test`
- Bootstrap: `tests/bootstrap.php`
- Кэш директория: `tests/_output`
- Testsuites: `unit`, `integration`

**Запуск тестов:**

```bash
# Все тесты
make tests

# Только unit-тесты
make tests-unit

# Только integration-тесты
make tests-integration

# Отключить запуск в контейнере
USE_DOCKER=0 make tests-integration

# Подготовка test DB (по необходимости)
make test-db-prepare

# Загрузка тестовых фикстур
make test-db-fixtures

# Integration-тесты с фикстурами
make tests-integration-fixtures

# С покрытием кода
make coverage
```

**Прямой запуск через PHPUnit:**

```bash
# Unit-тесты
bin/phpunit -c phpunit.xml.dist --testsuite unit

# Integration-тесты
bin/phpunit -c phpunit.xml.dist --testsuite integration

# Конкретный тест
bin/phpunit -c phpunit.xml.dist tests/Unit/Module/Source/Domain/Entity/SourceModelTest.php
```

### Psalm

Статический анализ кода для проверки типов и выявления потенциальных ошибок.

**Конфигурация:** [`psalm.xml`](../../psalm.xml)

**Основные настройки:**
- Уровень ошибок: `2`
- Baseline: `devops/psalm/baseline.xml`
- Плагин: SymfonyPsalmPlugin

**Запуск:**

```bash
make psalm
```

### Deptrac

Статический анализ архитектуры кода для проверки соблюдения слоистой архитектуры и модульной изоляции.

**Конфигурация:** [`depfile.yaml`](../../depfile.yaml)

**Запуск:**

```bash
make deptrac
```

### PHP_CodeSniffer

Проверка стиля кода на соответствие PSR-12 и дополнительным правилам проекта.

**Конфигурация:** [`phpcs.xml.dist`](../../phpcs.xml.dist)

**Основные настройки:**
- Базовый стандарт: PSR-12
- Дополнительные правила: SlevomatCodingStandard, TaskCodingStandard

**Запуск:**

```bash
make phpcs
```

### Composer

Проверка зависимостей и безопасности.

**Запуск:**

```bash
make audit
```

## Правила написания тестов

### Общие правила

1. **Любое изменение кода должно сопровождаться тестами соответствующего уровня.**
2. **Новый код в Domain/Application покрывается unit-тестами (минимум 80% покрытия по затронутым участкам).**
3. **Все интеграционные и функциональные тесты обязаны наследоваться от `Common\Component\Test\KernelTestCase`.**
4. **Для инициализации ядра в integration-тестах используйте `self::bootKernel()`.**
5. **Не используйте реальные внешние сервисы или секретные данные в тестах.**
6. **Соблюдайте общие правила из корневого [`AGENTS.md`](../../AGENTS.md) по стилю кода и покрытию тестами.**

### Структура тестов (AAA + BDD)

#### Паттерн AAA (Arrange-Act-Assert)

Все unit-тесты **обязаны** следовать паттерну AAA для организации кода:

1. **Arrange** — подготовка данных, создание моков, настройка окружения
2. **Act** — выполнение тестируемого действия (вызов метода, обработчика)
3. **Assert** — проверка результатов (assertion-методы)

```php
public function testInvokeValidDataReturnsUserUuid(): void
{
    // Arrange: подготовка моков и данных
    $repository = $this->createMock(UserRepositoryInterface::class);
    $repository->method('exists')->willReturn(false);

    $handler = new CreateCommandHandler($repository, ...);

    // Act: выполнение тестируемого действия
    $result = $handler(new CreateCommand(
        email: 'test@example.com',
        username: 'testuser',
        // ...
    ));

    // Assert: проверка результатов
    self::assertInstanceOf(IdDto::class, $result);
    self::assertTrue($result->uuid->equals($expectedUuid));
}
```

**Правила AAA:**
- Разделяйте секции пустой строкой
- Секция может содержать несколько строк кода
- Избегайте логики в Arrange (кроме настройки моков)
- Act должен быть одной строкой (или минимальным количеством)
- Assert группируйте по логическому смыслу

#### BDD-стиль именования тестов

Имя теста должно описывать **поведение** системы в camelCase:

```
test{WhatIsBeingTested}{Scenario}{ExpectedResult}
```

**Примеры:**
- `testRegistrationWithoutInviteCreatesInactiveUser`
- `testProjectCreationAuthorizedUserReturns201Created`
- `testApiRequestInvalidJsonReturns400BadRequest`

#### Соответствие AAA и BDD

| BDD (поведение) | AAA (структура) |
|-----------------|-----------------|
| **Given** (контекст) | **Arrange** |
| **When** (действие) | **Act** |
| **Then** (результат) | **Assert** |

Для сложных E2E-сценариев используйте `@scenario` в PHPDoc:

```php
/**
 * @scenario Создание проекта авторизованным пользователем
 *
 * Given: пользователь авторизован с валидным JWT токеном
 * When: отправляется POST /v1/projects с корректными данными
 * Then: возвращается 201 CREATED с UUID проекта
 */
public function testProjectCreationAuthorizedUserReturns201Created(): void
{
    // Arrange (Given)
    $client = self::createClient();
    $token = $this->getMainJwtToken();

    // Act (When)
    $client->request(
        method: 'POST',
        uri: '/v1/projects',
        server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        content: JsonHelper::encode(['title' => 'Test Project']),
    );

    // Assert (Then)
    self::assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());
}
```

### Unit-тесты

**Правила:**
- Наследуйтесь от `PHPUnit\Framework\TestCase`
- Используйте `createMock()` для создания моков
- Используйте `self::assertSame()`, `self::assertNotNull()` и другие assert-методы
- Один тест проверяет один сценарий
- **Обязательно следуйте паттерну AAA** (см. выше)
- Название теста должно описывать сценарий в camelCase: `test{WhatIsBeingTested}{Scenario}{ExpectedResult}`

### Integration-тесты и функциональные тесты

**Правила:**
- Наследуйтесь от `Common\Component\Test\KernelTestCase`
- Вызывайте `self::bootKernel()` в начале теста (или в `setUp()`)
- Используйте `self::getContainer()` для получения сервисов из DI-контейнера
- Создавайте схему БД в `setUp()` при необходимости:

```php
protected function setUp(): void
{
    parent::setUp();

    /** @var EntityManagerInterface $entityManager */
    $entityManager = self::getContainer()->get(EntityManagerInterface::class);
    $schemaTool = new SchemaTool($entityManager);
    $schemaTool->updateSchema($entityManager->getMetadataFactory()->getAllMetadata());
}
```

### Покрытие кода

**Требования:**
- Минимум 80% покрытие для нового кода в Domain/Application
- Покрытие измеряется по затронутым участкам кода (не глобально)
- Используйте `make coverage` для генерации отчёта

**Просмотр покрытия:**

```bash
# Генерация HTML-отчёта
make coverage

# Откройте tests/_output/index.html в браузере
```

## Команды для запуска проверок

### Полная проверка (CI)

Команда `make check` запускает все проверки последовательно:

```bash
make check
```

Включает:
1. `make install` — установка зависимостей
2. `make tests` — unit- и integration-тесты
3. `make phpmd-fast` — быстрый PHPMD по diff
4. `make deptrac` — анализ архитектуры
5. `make psalm` — статический анализ
6. `make phpcs` — проверка стиля кода

### Отдельные команды

```bash
# Тесты
make tests              # Все тесты
make tests-unit         # Unit-тесты
make tests-integration  # Integration-тесты
make tests-e2e          # Все E2E тесты (web + api)
make tests-e2e-web      # Только Web E2E (Panther)
make tests-e2e-api      # Только API E2E
make tests-e2e-source-pipeline # Специальные сценарии

# Статический анализ
make deptrac            # Анализ архитектуры
make psalm              # Статический анализ типов
make phpcs              # Проверка стиля кода
make phpmd              # PHP Mess Detector

# Покрытие
make coverage           # Генерация отчёта покрытия

# Окружение
make e2e-up             # Поднять E2E окружение
make e2e-clean-host     # Очистить логи и артефакты E2E
```

## Структура директории tests/

```
tests/
├── bootstrap.php              # Bootstrap-файл PHPUnit
├── _output/                    # Кэш и отчёты PHPUnit
├── Unit/                       # Unit-тесты
│   ├── Console/
│   ├── EventSubscriber/
│   ├── Infrastructure/
│   ├── Module/
│   │   ├── Source/
│   │   │   ├── Domain/
│   │   │   ├── Application/
│   │   │   ├── Infrastructure/
│   │   │   └── Integration/
│   │   └── User/
│   └── ...
├── Integration/               # Integration-тесты
│   ├── Component/
│   ├── Infrastructure/
│   ├── Module/
│   │   ├── User/
│   │   │   └── Application/
│   │   └── ...
│   ├── Routing/
│   └── Translation/
├── Stub/                       # Заглушки и фикстуры
│   ├── ClockContainer.php
│   └── ...
└── Support/                    # Вспомогательные классы
```

## Конфигурационные файлы

| Файл                | Описание                                      |
|---------------------|-----------------------------------------------|
| [`phpunit.xml.dist`](../../phpunit.xml.dist) | Конфигурация PHPUnit                         |
| [`psalm.xml`](../../psalm.xml)             | Конфигурация Psalm                          |
| [`phpcs.xml.dist`](../../phpcs.xml.dist)   | Конфигурация PHP_CodeSniffer                |
| [`phpmd.xml`](../../phpmd.xml)             | Конфигурация PHP Mess Detector              |
| [`Makefile`](../../Makefile)               | Makefile с командами для запуска проверок  |
| [`depfile.yaml`](../../depfile.yaml)       | Конфигурация Deptrac                        |

## Дополнительные ресурсы

- [Symfony Testing Documentation](https://symfony.com/doc/current/testing.html) — официальная документация Symfony по тестированию (unit-тесты, функциональные тесты, моки, фикстуры и др.)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Psalm Documentation](https://psalm.dev/docs/)
- [PHP_CodeSniffer Documentation](https://github.com/squizlabs/PHP_CodeSniffer)

## Связанная документация

- [`docs/testing/e2e.md`](../../docs/testing/e2e.md) — детальное руководство по E2E тестированию
- [`tests/AGENTS.md`](../../tests/AGENTS.md) — детальные правила тестирования (источник истины для тестов)
- [`AGENTS.md`](../../AGENTS.md) — общие правила проекта, включая раздел Tests and Validation
- [`Makefile`](../../Makefile) — команды для запуска тестов и проверок

> **Примечание:** Данный документ (`docs/conventions/testing/index.md`) является полным руководством по тестированию в проекте. Для получения наиболее актуальной информации о правилах тестирования используйте [`tests/AGENTS.md`](../../tests/AGENTS.md) как источник истины.
