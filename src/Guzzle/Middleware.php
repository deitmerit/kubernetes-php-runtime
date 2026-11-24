<?php


namespace KubernetesRuntime\Guzzle;


use GuzzleHttp\Psr7\Utils;
use KubernetesRuntime\APIPatchOperation;
use Psr\Http\Message\RequestInterface;

final class Middleware
{
    public static function setPatchOperation(): callable
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                $modify = [];
                if ('PATCH' == $request->getMethod()) {
                    $requestBody = json_decode($request->getBody(), true);
                    if (null !== $requestBody && array_key_exists('patchOperation', $requestBody)) {
                        switch ($requestBody['patchOperation']) {
                            case APIPatchOperation::MERGE_PATCH:
                                $contentType = APIPatchOperation::CONTENT_TYPE[APIPatchOperation::MERGE_PATCH];
                                break;
                            case APIPatchOperation::STRATEGIC_MERGE_PATCH:
                                $contentType =
                                    APIPatchOperation::CONTENT_TYPE[APIPatchOperation::STRATEGIC_MERGE_PATCH];
                                break;
                            default:
                                $contentType = APIPatchOperation::CONTENT_TYPE[APIPatchOperation::PATCH];
                                break;
                        }

                        unset($requestBody['patchOperation']);

                        // GuzzleHttp\Utils::jsonEncode() no longer exists in Guzzle 8.
                        $modify['body'] = Utils::streamFor(\json_encode($requestBody, \JSON_THROW_ON_ERROR));

                    } else {
                        $contentType = APIPatchOperation::CONTENT_TYPE[APIPatchOperation::PATCH];
                    }
                    $modify['set_headers']['Content-Type'] = $contentType;
                }

                return $handler(Utils::modifyRequest($request, $modify), $options);
            };
        };
    }
}
