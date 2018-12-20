<?php

namespace Appercode;

use GuzzleHttp\Exception\BadResponseException;

use Appercode\Contracts\Backend;

use Appercode\Exceptions\Settings\TimeReceiveException;
use Appercode\Traits\AppercodeRequest;

use Carbon\Carbon;

class Settings
{
    use AppercodeRequest;

    private static function methods(Backend $backend, string $name, array $data = []): array
    {
        switch ($name) {
            case 'time':
                return [
                    'type' => 'GET',
                    'url' => $backend->server . $backend->project . '/settings/currentDateTime'
                ];

            default:
                throw new \Exception('Can`t find method ' . $name);
        }
    }

    /**
     * Returns current server DateTime
     * @param  Appercode\Contracts\Backend $backend
     * @throws  Appercode\Exceptions\Settings\TimeReceiveException
     * @return Carbon\Carbon
     */
    public static function time(Backend $backend): Carbon
    {
        $method = self::methods($backend, 'time');

        try {
            $response = self::request([
                'method' => $method['type'],
                'url' => $method['url'],
            ]);
        } catch (BadResponseException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $message = $e->hasResponse() ? $e->getResponse()->getBody() : '';

            throw new TimeReceiveException($message, $code, $e);
        }

        $time = (string) $response->getBody();
        $time = str_replace('"', '', $time);

        return Carbon::parse($time);
    }
}
