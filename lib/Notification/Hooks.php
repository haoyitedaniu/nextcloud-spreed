<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Spreed\Notification;

use OCA\Spreed\Room;
use OCP\Notification\IManager;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserSession;

class Hooks {

	/** @var IManager */
	protected $notificationManager;

	/** @var IUserSession */
	protected $userSession;

	/** @var ILogger */
	protected $logger;

	/**
	 * @param IManager $notificationManager
	 * @param IUserSession $userSession
	 * @param ILogger $logger
	 */
	public function __construct(IManager $notificationManager, IUserSession $userSession, ILogger $logger) {
		$this->notificationManager = $notificationManager;
		$this->userSession = $userSession;
		$this->logger = $logger;
	}

	/**
	 * Invitation activity: "{actor} invited you to {call}"
	 *
	 * @param Room $room
	 * @param array[] $participants
	 */
	public function generateInvitation(Room $room, array $participants) {
		$actor = $this->userSession->getUser();
		if (!$actor instanceof IUser) {
			return;
		}
		$actorId = $actor->getUID();


		$notification = $this->notificationManager->createNotification();
		$dateTime = new \DateTime();
		try {
			$notification->setApp('spreed')
				->setDateTime($dateTime)
				->setObject('room', $room->getId())
				->setSubject('invitation', [$actor->getUID()]);
		} catch (\InvalidArgumentException $e) {
			$this->logger->logException($e, ['app' => 'spreed']);
			return;
		}

		foreach ($participants as $participant) {
			if ($actorId === $participant['userId']) {
				// No activity for self-joining and the creator
				continue;
			}

			try {
				$notification->setUser($participant['userId']);
				$this->notificationManager->notify($notification);
			} catch (\InvalidArgumentException $e) {
				$this->logger->logException($e, ['app' => 'spreed']);
			}
		}
	}
}
