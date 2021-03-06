<?php

declare(strict_types=1);

namespace Azonmedia\Http\Interfaces;

use InvalidArgumentException;

/**
 *
 */
interface CookiesInterface
{
    /**
     * Get request cookie
     *
     * @param  string $name    Cookie name
     * @param  mixed  $default Cookie default value
     *
     * @return mixed Cookie value if present, else default
     */
    public function get($name, $default = null);

    /**
     * Set response cookie
     *
     * @param string       $name  Cookie name
     * @param string|array $value Cookie value, or cookie properties
     */
    public function set($name, $value);

    /**
     * Convert to array of `Set-Cookie` headers
     *
     * @return string[]
     */
    public function toHeaders();

    /**
     * Parse HTTP request `Cookie:` header and extract into a PHP associative array.
     *
     * @param  string $header The raw HTTP request `Cookie:` header
     *
     * @return array Associative array of cookie names and values
     *
     * @throws InvalidArgumentException if the cookie data cannot be parsed
     */
    public static function parseHeader($header);
}
