<?php declare(strict_types = 1);

/**
 * NodeDatabaseExtension.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NodeDatabase!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           27.05.20
 */

namespace FastyBird\NodeDatabase\DI;

use Contributte\Translation;
use FastyBird\NodeDatabase\Events;
use FastyBird\NodeDatabase\Middleware;
use FastyBird\NodeWebServer\Commands as NodeWebServerCommands;
use Nette;
use Nette\DI;

/**
 * Microservice node helpers extension container
 *
 * @package        FastyBird:NodeDatabase!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class NodeDatabaseExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	/**
	 * {@inheritDoc}
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition(null)
			->setType(Middleware\JsonApiMiddleware::class)
			->setTags([
				'middleware' => [
					'priority' => 10,
				],
			]);

		$builder->addDefinition(null)
			->setType(Middleware\DbErrorMiddleware::class)
			->setTags([
				'middleware' => [
					'priority' => 50,
				],
			]);

		$builder->addDefinition($this->prefix('event.serverStart'))
			->setType(Events\ServerStartHandler::class);

		$builder->addDefinition($this->prefix('event.request'))
			->setType(Events\RequestHandler::class);

		$builder->addDefinition($this->prefix('event.response'))
			->setType(Events\ResponseHandler::class);

		$builder->addDefinition($this->prefix('event.afterConsume'))
			->setType(Events\AfterConsumeHandler::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * SERVER EVENTS
		 */

		$serverCommandServiceName = $builder->getByType(NodeWebServerCommands\HttpServerCommand::class);

		if ($serverCommandServiceName !== null) {
			/** @var DI\Definitions\ServiceDefinition $serverCommandService */
			$serverCommandService = $builder->getDefinition($serverCommandServiceName);

			$serverCommandService
				->addSetup('?->$onServerStart[] = ?', [$serverCommandService, '@' . $this->prefix('event.serverStart')])
				->addSetup('?->$onRequest[] = ?', [$serverCommandService, '@' . $this->prefix('event.request')])
				->addSetup('?->$onResponse[] = ?', [$serverCommandService, '@' . $this->prefix('event.response')])
				->addSetup('?->$onAfterConsumeMessage[] = ?', [$serverCommandService, '@' . $this->prefix('event.afterConsume')]);
		}
	}

	/**
	 * @return string[]
	 */
	public function getTranslationResources(): array
	{
		return [
			__DIR__ . DS . '..' . DS . 'Translations',
		];
	}

	/**
	 * @param Nette\Configurator $config
	 * @param string $extensionName
	 *
	 * @return void
	 */
	public static function register(
		Nette\Configurator $config,
		string $extensionName = 'nodeDatabase'
	): void {
		$config->onCompile[] = function (Nette\Configurator $config, DI\Compiler $compiler) use ($extensionName): void {
			$compiler->addExtension($extensionName, new NodeDatabaseExtension());
		};
	}

}
