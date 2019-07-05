<?php

namespace SlashTrace\Sentry\Tests;

use InvalidArgumentException;
use Sentry\Breadcrumb;
use Sentry\ClientInterface;
use Sentry\Options;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use SlashTrace\Context\User;
use SlashTrace\EventHandler\EventHandlerException;
use SlashTrace\Sentry\SentryHandler;

use PHPUnit\Framework\TestCase;

use Exception;

class SentryHandlerTest extends TestCase
{
    public function testExceptionIsThrownForInvalidConstructorArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        new SentryHandler($this);
    }

    public function testExceptionIsPassedToSentryClient()
    {
        $exception = new Exception();

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method("captureException")
            ->with($exception);

        $handler = new SentryHandler($client);
        $handler->handleException($exception);
    }

    public function testSentryExceptionsAreHandled()
    {
        $originalException = new Exception();
        $sentryException = new Exception();

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method("captureException")
            ->with($originalException)
            ->willThrowException($sentryException);

        $handler = new SentryHandler($client);
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

        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->once())
            ->method("configureScope")
            ->with($this->callback(function (callable $scopeCallback) use ($user): bool {
                $scope = new Scope();
                $scopeCallback($scope);
                $this->assertEquals([
                    "id"    => $user->getId(),
                    "email" => $user->getEmail(),
                    "name"  => $user->getName(),
                ], $scope->getUser());

                return true;
            }));

        $handler = new SentryHandler($hub);
        $handler->setUser($user);
    }

    public function testBreadcrumbsArePassedToSentryClient()
    {
        $title = "Something happened!";
        $data = ["foo" => "bar"];

        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->once())
            ->method("addBreadcrumb")
            ->with($this->callback(function (Breadcrumb $breadcrumb) use ($title, $data): bool {
                $this->assertEquals($title, $breadcrumb->getMessage());
                $this->assertEquals($data, $breadcrumb->getMetadata());
                return true;
            }));

        $handler = new SentryHandler($hub);
        $handler->recordBreadcrumb($title, $data);
    }

    public function testReleaseIsPassedToSentryClient()
    {
        $release = "1.0.0";
        $options = new Options();

        $client = $this->createMock(ClientInterface::class);
        $client->method("getOptions")->willReturn($options);

        $handler = new SentryHandler($client);
        $handler->setRelease($release);

        $this->assertEquals($release, $options->getRelease());
    }

    public function testApplicationPathIsPassedToSentryClient()
    {
        $path = __DIR__;

        $options = new Options();

        $client = $this->createMock(ClientInterface::class);
        $client->method("getOptions")->willReturn($options);

        $handler = new SentryHandler($client);
        $handler->setApplicationPath($path);

        $this->assertEquals($path, $options->getProjectRoot());
    }
}