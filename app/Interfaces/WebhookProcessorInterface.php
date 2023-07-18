<?php

namespace App\Interfaces;

interface WebhookProcessorInterface
{
    public function __construct(string $_respondToUrl);
    public function receive(): self;
    public function respond(): string|bool;
}
