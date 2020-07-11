<?php

declare(strict_types=1);

namespace App\Tests\Environment\Configuration;

use App\Environment\Configuration\AbstractConfiguration;
use App\Environment\Configuration\ConfigurationInstaller;
use App\Exception\FilesystemException;
use App\Middleware\Binary\Mkcert;
use App\Tests\TestConfigurationTrait;
use App\Tests\TestLocationTrait;
use Ergebnis\Environment\FakeVariables;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @internal
 *
 * @covers \App\Environment\Configuration\AbstractConfiguration
 * @covers \App\Environment\Configuration\ConfigurationInstaller
 */
final class ConfigurationInstallerTest extends TestCase
{
    use ProphecyTrait;
    use TestConfigurationTrait;
    use TestLocationTrait;

    /** @var ObjectProphecy */
    private $mkcert;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mkcert = $this->prophesize(Mkcert::class);
        $this->createLocation();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeLocation();
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     *
     * @throws FilesystemException
     */
    public function testItInstallsConfigurationFilesWithBlackfireCredentials(string $name, string $type, ?string $domains = null): void
    {
        $phpVersion = 'azerty';

        $source = __DIR__."/../../../src/Resources/{$type}";
        $destination = $this->location.AbstractConfiguration::INSTALLATION_DIRECTORY;

        /** @var string $defaultConfiguration */
        $defaultConfiguration = file_get_contents("{$source}/.env");
        static::assertStringNotContainsString($phpVersion, $defaultConfiguration);

        if ($domains !== null) {
            $certificate = sprintf('%s/nginx/certs/custom.pem', $destination);
            $privateKey = sprintf('%s/nginx/certs/custom.key', $destination);

            $this->mkcert->generateCertificate($certificate, $privateKey, explode(' ', $domains))->shouldBeCalledOnce()->willReturn(true);
        } else {
            $this->mkcert->generateCertificate(Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        }

        $credentials = $this->getFakeBlackfireCredentials();

        $installer = new ConfigurationInstaller($this->mkcert->reveal(), FakeVariables::fromArray($credentials));
        $installer->install($name, $this->location, $type, $phpVersion, $domains);

        $this->assertConfigurationIsInstalled($type, $destination, $phpVersion);
        $this->assertConfigurationContainsBlackfireCredentials($destination, $credentials);
    }
}
