<?php

declare(strict_types = 1);

use App\Model\CustomUser;
use App\Services\Authy;
use App\Services\RegistrationService;
use Cartalyst\Sentinel\Sentinel;
use Illuminate\Session\Store;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

/**
 * @author Robert Matuszewski <robmatu@gmail.com>
 */
class RegistrationServiceTest extends TestCase
{
    /**
     * @var Sentinel|LegacyMockInterface|MockInterface
     */
    private $sentinelMock;

    /**
     * @var Store|LegacyMockInterface|MockInterface
     */
    private $sessionMock;

    /**
     * @var Authy|LegacyMockInterface|MockInterface
     */
    private $authyMock;

    /**
     * @var RegistrationService
     */
    private $registrationService;

    /**
     * @covers \App\Services\RegistrationService::register
     * @throws Exception
     */
    public function testIfMethodArgumentHasInvalidTypeThenThrowException(): void
    {
        $this->expectException(TypeError::class);

        $this->registrationService->register('invalid argument');
    }

    /**
     * @covers \App\Services\RegistrationService::register
     * @throws Exception
     */
    public function testIfSentinelNotRegisterUserThenThrowException(): void
    {
        $this->expectException(LogicException::class);
        $this->sentinelMock->shouldNotReceive('registerAndActivate')->once()->andReturn(null);

        $this->registrationService->register([]);
    }

    /**
     * @dataProvider registrationDataProvider
     * @param bool $registered
     * @covers       \App\Services\RegistrationService::register
     *
     * @throws Exception
     */
    public function testUserRegistration(bool $registered): void
    {
        $userMock = Mockery::mock(CustomUser::class);
        $userMock->shouldReceive('getAttribute')->withArgs(['id'])->once();
        $userMock->shouldReceive('getAttribute')->withArgs(['email'])->once();
        $userMock->shouldReceive('getAttribute')->withArgs(['phone_number'])->once();
        $userMock->shouldReceive('getAttribute')->withArgs(['country_code'])->once();
        $authyData = Mockery::mock(stdClass::class);

        $this->sentinelMock->shouldReceive('registerAndActivate')->once()->andReturn($userMock);

        $this->sessionMock->shouldReceive('set')->twice();
        $this->authyMock->shouldReceive('register')->once();
        $userMock->shouldReceive('updateAuthyId')->once();

        $authyData->registered = $registered;
        $this->authyMock->shouldReceive('verifyUserStatus')->once()->andReturn($authyData);

        $this->sessionMock->shouldReceive('flash')->once();

        if (!$registered) {
            $this->authyMock->shouldReceive('sendToken')->once();
        }

        $this->assertEmpty($this->registrationService->register([]));
    }

    /**
     * @return array
     */
    public function registrationDataProvider(): array
    {
        return [
            'User has registered APP' => [true],
            'User has not registered APP' => [false],
        ];
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->sentinelMock = Mockery::mock(Sentinel::class);
        $this->sessionMock = Mockery::mock(Store::class);;
        $this->authyMock = Mockery::mock(Authy::class);
        $this->registrationService = new RegistrationService($this->sentinelMock, $this->sessionMock, $this->authyMock);
    }

    public function tearDown()
    {
        parent::tearDown();

        Mockery::close();
    }

}
