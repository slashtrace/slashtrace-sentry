<?php

namespace SlashTrace\Sentry;

use SlashTrace\Context\User;
use SlashTrace\EventHandler\EventHandler;
use SlashTrace\EventHandler\EventHandlerException;

use Raven_Client;

use Exception;

class SentryHandler implements EventHandler
{
    /** @var Raven_Client */
    private $sentry;

    /**
     * @param string|Raven_Client $sentry
     */
    public function __construct($sentry)
    {
        $this->sentry = $sentry instanceof Raven_Client ? $sentry : new Raven_Client($sentry);
    }

    /**
     * @param Exception $exception
     * @return int
     * @throws EventHandlerException
     */
    public function handleException($exception)
    {
        try {
            $this->sentry->captureException($exception);
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
        $this->sentry->user_context(array_filter([
            "id"    => $user->getId(),
            "email" => $user->getEmail(),
            "name"  => $user->getName()
        ]));
    }

    /**
     * @param string $title
     * @param array $data
     * @return void
     */
    public function recordBreadcrumb($title, array $data = [])
    {
        $this->sentry->breadcrumbs->record(array_merge($data, [
            "message" => $title
        ]));
    }

    /**
     * @param string $release
     * @return void
     */
    public function setRelease($release)
    {
        $this->sentry->setRelease($release);
    }

    /**
     * @param string $path
     * @return void
     */
    public function setApplicationPath($path)
    {
        $this->sentry->setAppPath($path);
    }
}