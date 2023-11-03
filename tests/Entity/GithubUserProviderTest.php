<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Security\GithubUserProvider;
use GuzzleHttp\Client;
use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class GithubUserProviderTest extends TestCase
{
    private MockObject | Client | null $client;
    private MockObject | SerializerInterface | null $serializer;
    private MockObject | StreamInterface | null $streamedResponse;
    private MockObject | ResponseInterface | null $response;

    public function setUp(): void
    {
        $this->client = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $this->serializer = $this
            ->getMockBuilder('JMS\Serializer\SerializerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->streamedResponse = $this
            ->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->getMock();
        $this->response = $this
            ->getMockBuilder('Psr\Http\Message\ResponseInterface')
            ->getMock();
    }


    public function testLoadUserByUsernameReturnsCorrectUser()
    {
        $this->client
            ->expects($this->once())
            ->method('get')
            ->willReturn($this->response);
        $this->response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($this->streamedResponse);
        $this->streamedResponse
            ->expects($this->once())
            ->method('getContents')
            ->willReturn('foo');
        $userData = [
            'login' => 'xyz',
            'name' => 'name',
            'email' => 'name',
            'avatar_url' => 'name.fr',
            'html_url' => 'name.fr',
        ];
        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn($userData);

        $githubUserProvider = new GithubUserProvider($this->client, $this->serializer);
        $user = $githubUserProvider->loadUserByUsername('an-access-token');

        $expectedUser = new User($userData['login'], $userData['name'], $userData['email'], $userData['avatar_url'], $userData['html_url']);
        $this->assertEquals($expectedUser, $user);
        $this->assertEquals('App\Entity\User', get_class($user));
    }

    public function testLoadUserByUsernameThrowsExceptionOnNullResponse()
    {
        $this->client
            ->expects($this->once())
            ->method('get')
            ->willReturn($this->response);
        $this->response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($this->streamedResponse);
        $this->streamedResponse
            ->expects($this->once())
            ->method('getContents')
            ->willReturn('foo');
        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn(null);

        $githubUserProvider = new GithubUserProvider($this->client, $this->serializer);

        $this->expectException('LogicException');
        $githubUserProvider->loadUserByUsername('an-access-token');
    }

    public function tearDown() : void
    {
        $this->client = null;
        $this->serializer = null;
        $this->streamedResponse = null;
        $this->response = null;
    }
}
