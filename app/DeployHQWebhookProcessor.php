<?php

namespace App;

use App\Exceptions\InvalidSourceException;
use App\Interfaces\WebhookProcessorInterface;

class DeployHQWebhookProcessor implements WebhookProcessorInterface
{
    private string $_publicKey;
    public array $_requestData;

    public function __construct(public string $_respondToUrl)
    {
        $this->_publicKey = file_get_contents(new Path('keys/deployhq-public.key'));
    }

    public function receive(): self
    {
        parse_str($this->getInputStream(), $requestData);
        $this->_requestData = $requestData;

        return $this;
    }

    /**
     * @throws InvalidSourceException
     */
    public function respond(): string|bool
    {
        if ($this->_requestIsValid(
            $this->_requestData['payload'],
            $this->_requestData['signature']
        )) {
            $notificationMessage = $this->_buildMessage($this->_requestData['payload']);
            $ch = curl_init($this->_respondToUrl);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $notificationMessage);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch); //!TODO: wrap this in a class so it is better testable
            curl_close($ch);
        } else {
            throw new InvalidSourceException('Could not verify source.', 403);
        }
        return $result;
    }

    public function getInputStream(): bool|string
    {
        return file_get_contents('php://input');
    }

    /**
     * @param string $payload - DeployHQ json payload
     * @param string $signature - base64 encoded string
     *
     * @return bool
     */
    private function _requestIsValid(string $payload, string $signature): bool
    {
        return openssl_verify($payload, base64_decode($signature), $this->_publicKey);
    }

    /**
     * @param string $payload - DeployHQ json payload
     * build JSON message for Mattermost
     */
    private function _buildMessage(string $payload): string
    {
        $oPayload     = json_decode($payload);
        $emoji        = $oPayload->status === 'running' ? ':rocket:' : ':white_check_mark:';
        $author       = $oPayload->end_revision->author ?? 'Gitlab';
        $refCommitUrl = str_replace(
            's/master',
            $oPayload->end_revision->ref,
            $oPayload->project->repository->hosting_service->commits_url
        );

        return sprintf(
                '{"text":"%s Deployment for %s on `%s` %s.',
                $emoji,
                $oPayload->project->name,
                $oPayload->servers[0]->name,
                $oPayload->status
            ) . PHP_EOL .
            sprintf('Initiated by %s. See [ref commit](%s)."}', $author, $refCommitUrl);
    }
}
