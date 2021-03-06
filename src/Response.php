<?php

declare(strict_types=1);

/**
 *
 * The following code contains large parts from:
 *
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Azonmedia\Http;

use Azonmedia\Http\Body\Stream;
use Azonmedia\Translator\Translator as t;
use Azonmedia\Exceptions\InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class Response
 * @package Azonmedia\Http
 */
class Response extends Message implements ResponseInterface
{

    /**
     * EOL characters used for HTTP response.
     *
     * @var string
     */
    const EOL = "\r\n";

    protected $status = StatusCode::HTTP_OK;//200

    public function __construct(int $status = StatusCode::HTTP_OK, array $headers = [], ?StreamInterface $Body = null)
    {
        $this->checkStatus($status);
        $this->status = $status;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
        $this->Body = $Body ?? new Stream();
    }

    public function __toString()
    {
        // TODO: Implement __toString() method.
        return (string) $this->getBody();
    }

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int Status code.
     */
    public function getStatusCode(): int
    {
        return $this->status;
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     *
     * If no reason phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * status code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated status and reason phrase.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param int $code The 3-digit integer result code to set.
     * @param string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return static
     * @throws \InvalidArgumentException For invalid status code arguments.
     * @throws InvalidArgumentException
     */
    public function withStatus(/* int */ $code, /* string */ $reasonPhrase = '')
    {
        $this->checkStatus($code);

        if (!$reasonPhrase) {
            $reasonPhrase = StatusCode::MESSAGES_MAP[$code];
        }

        $new_response = clone ($this);
        $new_response->status = $code;
        $new_response->reasonPhrase = $reasonPhrase;

        return $new_response;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * Because a reason phrase is not a required element in a response
     * status line, the reason phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * status code.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string Reason phrase; must return an empty string if none present.
     */
    public function getReasonPhrase(): string
    {
        return StatusCode::MESSAGES_MAP[$this->status];
    }

    private function checkStatus(int $status): void
    {
        if (!isset(StatusCode::MESSAGES_MAP[$status])) {
            throw new InvalidArgumentException(sprintf(t::_('Invalid HTTP status code %s provided.'), $status));
        }
    }
}
