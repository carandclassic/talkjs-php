<?php

declare(strict_types=1);

namespace CarAndClassic\TalkJS\Tests\Feature;

use CarAndClassic\TalkJS\Api\MessageApi;
use CarAndClassic\TalkJS\Enumerations\MessageType;
use CarAndClassic\TalkJS\Models\Message;
use CarAndClassic\TalkJS\Models\MessageCreated;
use CarAndClassic\TalkJS\Models\MessageDeleted;
use Symfony\Component\HttpClient\Response\MockResponse;

final class MessageTest extends TestCase
{
    private string $conversationId;

    private string $senderId;

    private array $messages;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conversationId = 'testConversationId';
        $this->senderId = 'testSenderId';
        $this->messages = [
            [
                'id' => '2', // At time of writing results are returned descending
                'type' => MessageType::USER,
                'conversationId' => $this->conversationId,
                'sender' => $this->senderId,
                'text' => 'Test User Message',
                'readBy' => [],
                'origin' => 'rest',
                'location' => null,
                'custom' => ['test' => 'test'],
                'createdAt' => (time() + 1) * 1000, // At time of writing TalkJS returns timestamp in milliseconds
                'attachment' => null
            ],
            [
                'id' => '1',
                'type' => MessageType::SYSTEM,
                'conversationId' => $this->conversationId,
                'sender' => null,
                'text' => 'Test System Message',
                'readBy' => [],
                'origin' => 'rest',
                'location' => null,
                'custom' => ['test' => 'test'],
                'createdAt' => time() * 1000,
                'attachment' => null
            ]
        ];
    }

    public function testGet(): void
    {
        $api = $this->createApiWithMockHttpClient(
            [
                new MockResponse(
                    json_encode(['data' => $this->messages]),
                    ['response_headers' => $this->defaultMockResponseHeaders]
                )
            ],
            MessageApi::class
        );

        $messages = $api->get($this->conversationId);

        $this->assertIsArray($messages);
        $this->assertCount(2, $messages);
        foreach ($messages as $message) {
            $this->assertInstanceOf(Message::class, $message);
        }
        $this->assertTrue($messages[0]->isUserMessage());
        $this->assertTrue($messages[1]->isSystemMessage());
    }

    public function testFind(): void
    {
        $api = $this->createApiWithMockHttpClient(
            [
                new MockResponse(
                    json_encode(['data' => $this->messages[0]]),
                    ['response_headers' => $this->defaultMockResponseHeaders]
                )
            ],
            MessageApi::class
        );

        $message = $api->find($this->conversationId, $this->messages[0]['id']);

        $this->assertInstanceOf(Message::class, $message);
        foreach ($this->messages[0] as $key => $value)
        {
            $this->assertEquals($value, $message->$key);
        }
    }

    public function testCreateSystemMessage(): void
    {
        $text = 'Test System Message';
        $custom = ['test' => 'test'];
        $api = $this->createApiWithMockHttpClient(
            [
                new MockResponse(
                    json_encode(['data' => []]),
                    ['response_headers' => $this->defaultMockResponseHeaders]
                )
            ],
            MessageApi::class
        );

        $messageCreated = $api
            ->createSystemMessage($this->conversationId, $text, $custom);

        $this->assertInstanceOf(MessageCreated::class, $messageCreated);
        $this->assertTrue($messageCreated->isSystemMessage());
        $this->assertEquals(null, $messageCreated->sender);
        $this->assertEquals($text, $messageCreated->text);
        $this->assertEquals($custom, $messageCreated->custom);

    }

    public function testCreateUserMessage(): void
    {
        $text = 'Test User Message';
        $custom = ['test' => 'test'];
        $api = $this->createApiWithMockHttpClient(
            [
                new MockResponse(
                    json_encode(['data' => []]),
                    ['response_headers' => $this->defaultMockResponseHeaders]
                )
            ],
            MessageApi::class
        );

        $messageCreated = $api
            ->createUserMessage($this->conversationId, $this->senderId, $text, $custom);

        $this->assertInstanceOf(MessageCreated::class, $messageCreated);
        $this->assertTrue($messageCreated->isUserMessage());
        $this->assertEquals($this->senderId, $messageCreated->sender);
        $this->assertEquals($text, $messageCreated->text);
        $this->assertEquals($custom, $messageCreated->custom);
    }

    //TODO: testSendFile

    public function testDelete(): void
    {
        $api = $this->createApiWithMockHttpClient(
            [
                new MockResponse(
                    json_encode(['data' => []]),
                    ['response_headers' => $this->defaultMockResponseHeaders]
                )
            ],
            MessageApi::class
        );

        $messageDeleted = $api->delete($this->conversationId, $this->messages[0]['id']);

        $this->assertInstanceOf(MessageDeleted::class, $messageDeleted);
    }
}