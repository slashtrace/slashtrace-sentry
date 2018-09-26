<?php

namespace SlashTrace\Sentry\Tests;

use SlashTrace\Context\User;
use SlashTrace\EventHandler\EventHandlerException;
use SlashTrace\Sentry\SentryHandler;

use Raven_Breadcrumbs;
use Raven_Client;
use PHPUnit\Framework\TestCase;

use Exception;

class SentryHandlerTest extends TestCase
{
    public function testExceptionIsPassedToSentryClient()
    {
        $exception = new Exception();

        $sentry = $this->createMock(Raven_Client::class);
        $sentry->expects($this->once())
            ->method("captureException")
            ->with($exception);

        $handler = new SentryHandler($sentry);
        $handler->handleException($exception);
    }

    public function testSentryExceptionsAreHandled()
    {
        $originalException = new Exception();
        $sentryException = new Exception();

        $sentry = $this->createMock(Raven_Client::class);
        $sentry->expects($this->once())
            ->method("captureException")
            ->with($originalException)
            ->willThrowException($sentryException);

        $handler = new SentryHandler($sentry);
        try {
            $handler->handleException($originalException);
            $this->fail("Expected exception: " . EventHandlerException::class);
        } catch (EventHandlerException $e) {
            $this->assertSame($sentryException, $e->getPrevious());
        }
    }

    public function testUserIsPassedToSentryClient()
    {
        $user = new User();
        $user->setId(12345);
        $user->setEmail("pfry@planetexpress.com");
        $user->setName("Philip J. Fry");

        $sentry = $this->createMock(Raven_Client::class);
        $sentry->expects($this->once())
            ->method("user_context")
            ->with([
                "id"    => $user->getId(),
                "email" => $user->getEmail(),
                "name"  => $user->getName()
            ]);

        $handler = new SentryHandler($sentry);
        $handler->setUser($user);
    }

    public function testPartialUserData()
    {
        $user = new User();
        $user->setEmail("pfry@planetexpress.com");

        $sentry = $this->createMock(Raven_Client::class);
        $sentry->expects($this->once())
            ->method("user_context")
            ->with([
                "email" => $user->getEmail(),
            ]);

        $handler = new SentryHandler($sentry);
        $handler->setUser($user);
    }

    public function testBreadcrumbsArePassedToSentryClient()
    {
        $breadcrumbs = $this->createMock(Raven_Breadcrumbs::class);
        $breadcrumbs->expects($this->once())
            ->method("record")
            ->with([
                "message" => "Something happened!",
                "foo"     => "bar",
                "bar"     => "baz"
            ]);

        /** @var Raven_Client $sentry */
        $sentry = $this->createMock(Raven_Client::class);
        $sentry->breadcrumbs = $breadcrumbs;

        $handler = new SentryHandler($sentry);
        $handler->recordBreadcrumb("Something happened!", [
            "foo" => "bar",
            "bar" => "baz"
        ]);
    }

    public function testReleaseIsPassedToSentryClient()
    {
        $release = "1.0.0";

        $sentry = $this->createMock(Raven_Client::class);
        $sentry->expects($this->once())
            ->method("setRelease")
            ->with($release);

        $handler = new SentryHandler($sentry);
        $handler->setRelease($release);
    }

    public function testApplicationPathIsPassedToSentryClient()
    {
        $path = __DIR__;

        $sentry = $this->createMock(Raven_Client::class);
        $sentry->expects($this->once())
            ->method("setAppPath")
            ->with($path);

        $handler = new SentryHandler($sentry);
        $handler->setApplicationPath($path);
    }
}