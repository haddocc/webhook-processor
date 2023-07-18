<?php


namespace App;


use HttpException;

class Application
{
    // language=regexp
    private const ROUTING_REGEX = '/\/?wh\/([^\/]+)/';
    private const SERVICE_ID_CASE_MAP = [
        'deployhq' => 'DeployHQ'
    ];

    /**
     * @throws HttpException
     */
    public static function boot(): void
    {
        // grab first path level for routing
        preg_match(self::ROUTING_REGEX, $_GET['uri'], $matches);

        // stop when there is no match
        if(!isset($matches[1])) {
            throw new HttpException('Page not found', 404);
        }

        // build processor class name and environment url from uri
        $serviceId = self::SERVICE_ID_CASE_MAP[strtolower($matches[1])];
        $serviceIdEnvUrl = strtoupper($serviceId).'_WEBHOOK_URL';
        $processorClass = "\App\\{$serviceId}WebhookProcessor";

        // only proceed if the processor class exists and if its webhook url is set in the environment variables
        if(class_exists($processorClass) && isset($_ENV[$serviceIdEnvUrl])) {
            /** @var WebhookProcessorInterface $processor */
            $processor = (new $processorClass($_ENV[$serviceIdEnvUrl]));
            $processor->receive()->respond();
        } else {
            throw new HttpException('Page not found', 404);
        }
    }
}
