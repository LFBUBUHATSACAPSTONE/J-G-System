<?php

namespace App\Services;

use App\Services\Messaging\MessageService as MessagingMessageService;

/**
 * The messaging service moved to its own namespace (App\Services\Messaging) when the services
 * were split up by topic. This class is kept so older references keep working.
 *
 * @deprecated use App\Services\Messaging\MessageService instead.
 */
class MessageService extends MessagingMessageService
{
}
