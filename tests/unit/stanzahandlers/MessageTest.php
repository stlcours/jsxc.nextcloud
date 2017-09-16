<?php

namespace OCA\OJSXC\StanzaHandlers;

use OCA\OJSXC\Db\Message as MessageEntity;
use OCA\OJSXC\Db\MessageMapper;
use OCA\OJSXC\IUserProvider;
use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class MessageTest extends PHPUnit_Framework_TestCase
{

	/**
	 * @var Message $message
	 */
	private $message;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject | MessageMapper
	 */
	private $messageMapper;

	/**
	 * @var string userId
	 */
	private $userId;

	/**
	 * @var string $host
	 */
	private $host;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject | IUserProvider
	 */
	private $userProvider;

	public function setUp()
	{
		$this->host = 'localhost';
		$this->userId = 'john';
		$this->messageMapper = $this->getMockBuilder('OCA\OJSXC\Db\MessageMapper')->disableOriginalConstructor()->getMock();
		$this->userProvider = $this->getMockBuilder('OCA\OJSXC\IUserProvider')->disableOriginalConstructor()->getMock();
		$this->message = new Message($this->userId, $this->host, $this->messageMapper, $this->userProvider);
	}

	public function messageProvider()
	{
		$values = [
			[
				"name" => "body",
				"value" => 'abcèé³e¹³€{ë',
				"attributes" => ["xmlns" => 'jabber:client']
			],
			[
				"name" => "request",
				"value" => '',
				"attributes" => ["xmlns" => 'urn:xmpp:receipts']
			],
		];

		$expected1 = new MessageEntity();
		$expected1->setTo('derp'); // hostname is stripped
		$expected1->setFrom('john');
		$expected1->setValue($values);
		$expected1->setType('chat');

		return [
			[
				[
					'name' => '{jabber:client}message',
					'value' =>
						[
							'{jabber:client}body' => 'abcèé³e¹³€{ë',
							'{urn:xmpp:receipts}request' => null,
						],
					'attributes' =>
						[
							'to' => 'derp@own.dev',
							'type' => 'chat',
						],
				],
				$expected1
			]
		];
	}

	/**
	 * @dataProvider messageProvider
	 */
	public function testMessage(array $stanza, $expected)
	{
		$this->messageMapper->expects($this->once())
			->method('insert')
			->with($expected);

		$this->userProvider->expects($this->once())
			->method('hasUserByUID')
			->with('derp')
			->willReturn(true); // TODO test return false

		$this->message->handle($stanza);
	}
}
