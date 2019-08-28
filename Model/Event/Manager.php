<?php

/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Event;

use Magento\Framework\Event\ManagerInterface;

class Manager
{
    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * Manager constructor.
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        ManagerInterface $eventManager
    ) {
        $this->eventManager = $eventManager;
    }

    /**
     * @param string $name
     * @param array $data
     */
    public function dispatch(string $name, array $data = [])
    {
        $this->eventManager->dispatch(
            sprintf('staempfli_commerceimport_%s', $name),
            $data
        );
    }

    /**
     * @param string $message
     */
    public function notify(string $message)
    {
        $message = sprintf("<strong>mediaSolution3 Commerce Import</strong>\n %s", $message);
        $this->eventManager->dispatch(
            'chatconnector_notification',
            [
                'event' => $this,
                'message' => $message,
            ]
        );
    }
}
