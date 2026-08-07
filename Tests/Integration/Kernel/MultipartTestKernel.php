<?php
/*
* This file is part of the auto1-oss/service-api-client-bundle.
*
* (c) AUTO1 Group SE https://www.auto1-group.com
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Tests\Integration\Kernel;

use Auto1\ServiceAPIClientBundle\Auto1ServiceAPIClientBundle;
use Auto1\ServiceAPIComponentsBundle\Auto1ServiceAPIComponentsBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Minimal kernel that boots FrameworkBundle + the components/client bundles, so the
 * integration test exercises the real DI wiring (booting it at all asserts the
 * bundle loads and compiles cleanly).
 */
class MultipartTestKernel extends Kernel
{
    /**
     * @var bool
     */
    private $withLogger;

    public function __construct(string $environment, bool $debug, bool $withLogger = true)
    {
        parent::__construct($environment, $debug);

        $this->withLogger = $withLogger;
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new Auto1ServiceAPIComponentsBundle(),
            new Auto1ServiceAPIClientBundle(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container): void {
            $frameworkConfig = [
                'secret' => 'test',
                'test' => true,
                'http_method_override' => false,
                'php_errors' => ['log' => true],
                'serializer' => ['enabled' => true],
                'property_info' => ['enabled' => true],
            ];

            // Valid only on Symfony 6.4+, where leaving it unset is also deprecated.
            // Older supported versions (5.4 / 6.0–6.3) reject the option, so gate it.
            if (Kernel::VERSION_ID >= 60400) {
                $frameworkConfig['handle_all_throwables'] = true;
            }

            $container->loadFromExtension('framework', $frameworkConfig);

            // The bundle discovers PSR-17 factories at runtime; nyholm/psr7 (dev dep)
            // provides them, so no HTTP factory wiring is needed here.
            if ($this->withLogger) {
                $container->register('logger', NullLogger::class)->setPublic(true);
            }

            $container->register(TestEndpointProvider::class)
                ->addTag('auto1.api.endpoint_provider', ['priority' => 0]);

            // Expose the services the integration test fetches.
            $container->setAlias('test.request_factory', 'auto1.api.request.factory')->setPublic(true);
            $container->setAlias('test.request_serializer', 'auto1.api.request.serializer')->setPublic(true);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir(): string
    {
        return sprintf(
            '%s/auto1_multipart_test/cache/%s_%d_%s',
            sys_get_temp_dir(),
            $this->environment,
            Kernel::VERSION_ID,
            $this->withLogger ? 'logger' : 'no_logger'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/auto1_multipart_test/log';
    }
}
