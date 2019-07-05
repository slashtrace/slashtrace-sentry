<?php

namespace SlashTrace\Sentry;

use function Sentry\addBreadcrumb;
use function Sentry\captureException;
use function Sentry\configureScope;
use Sentry\ClientBuilder;
use Sentry\ClientInterface;
use Sentry\Breadcrumb;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

use SlashTrace\Context\User;
use SlashTrace\EventHandler\EventHandler;
use SlashTrace\EventHandler\EventHandlerException;

use Exception;
use InvalidArgumentException;

class SentryHandler implements EventHandler
{
    /**
     * @param string|ClientInterface|HubInterface $input
     */
    public function __construct($input)
    {
        if (is_string($input)) {
            $hub = new Hub(ClientBuilder::create([
                'dsn'                  => $input,
                'default_integrations' => false,
            ])->getClient());
        } elseif ($input instanceof ClientInterface) {
            $hub = new Hub($input);
        } elseif ($input instanceof HubInterface) {
            $hub = $input;
        } else {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid constructor argument. Input must be one of: [%s]',
                    implode(', ', ['string', ClientInterface::class, HubInterface::class])
                )
            );
        }

        Hub::setCurrent($hub);
    }

    /**
     * @param Exception $exception
     * @return int
     * @throws EventHandlerException
     */
    public function handleException($exception)
    {
        try {
            captureException($exception);
        } catch (Exception $e) {
            throw new EventHandlerException($e->getMessage(), $e->getCode(), $e);
        }
        return EventHandler::SIGNAL_CONTINUE;
    }

    /**
     * @param User $user
     * @return void
     */
    public function setUser(User $user)
    {
        configureScope(function (Scope $scope) use ($user): void {
            $scope->setUser(array_filter([
                "id"    => $user->getId(),
                "email" => $user->getEmail(),
                "name"  => $user->getName(),
            ]));
        });
    }

    /**
     * @param string $title
     * @param array $data
     * @return void
     */
    public function recordBreadcrumb($title, array $data = []): void
    {
        addBreadcrumb(new Breadcrumb(
            Breadcrumb::LEVEL_INFO,
            Breadcrumb::TYPE_DEFAULT,
            'error_reporting',
            $title,
            $data
        ));
    }

    public function setRelease($release): void
    {
        Hub::getCurrent()->getClient()->getOptions()->setRelease($release);
    }

    /**
     * @param string $path
     * @return void
     */
    public function setApplicationPath($path)
    {
        Hub::getCurrent()->getClient()->getOptions()->setProjectRoot($path);
    }
}