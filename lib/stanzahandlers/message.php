<?php

namespace OCA\OJSXC\StanzaHandlers;

use OCA\OJSXC\Db\MessageMapper;
use OCA\OJSXC\IUserProvider;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;
use OCA\OJSXC\Db\Message as MessageEntity;

/**
 * Class Message
 *
 * @package OCA\OJSXC\StanzaHandlers
 */
class Message extends StanzaHandler
{

	/**
	 * @var MessageMapper $messageMapper
	 */
	private $messageMapper;

	/**
	 * @var IUserProvider $userProvider
	 */
	private $userProvider;

	/**
	 * @var string $type
	 */
	private $type;

	/**
	 * @var  array $values
	 */
	private $values;

	/**
	 * @var string $msgId
	 */
	private $msgId;

	/**
	 * Message constructor.
	 *
	 * @param string $userId
	 * @param string $host
	 * @param MessageMapper $messageMapper
	 * @param IUserProvider $userProvider
	 */
	public function __construct($userId, $host, MessageMapper $messageMapper, IUserProvider $userProvider)
	{
		parent::__construct($userId, $host);
		$this->messageMapper = $messageMapper;
		$this->userProvider = $userProvider;
	}

	/**
	 * @param array $stanza
	 */
	public function handle(array $stanza)
	{
		$to = $this->getAttribute($stanza, 'to');
		$pos = strrpos($to, '@');
		$this->to = substr($to, 0, $pos);

		if (!$this->userProvider->hasUserByUID($this->to)) {
			\OC::$server->getLogger()->warning('User ' . $this->userId . ' is trying to send a message to ' . $this->to . ' but this isn\'t allowed');
			return;
		}

		foreach ($stanza['value'] as $keyRaw => $value) {
			// remove namespace from key as it is unneeded and cause problems
			$key = substr($keyRaw, strpos($keyRaw, '}') + 1, strlen($keyRaw));
			// fetch namespace from key to read it
			$ns = substr($keyRaw, 1, strpos($keyRaw, '}') - 1);

			$this->values[] = [
				"name" => $key,
				"value" => (string)$value,
				"attributes" => ["xmlns" => $ns]
			];
		}
		$this->type = $this->getAttribute($stanza, 'type');
		$this->msgId = $this->getAttribute($stanza, 'id');

		$message = new MessageEntity();
		$message->setTo($this->to);
		$message->setFrom($this->from);
		$message->setValue($this->values);
		$message->setType($this->type);
		$this->messageMapper->insert($message);
		$this->values = [];
	}
}
