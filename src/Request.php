<?php

declare(strict_types=1);

namespace Azonmedia\Http;

use Azonmedia\Exceptions\NotImplementedException;
use Azonmedia\Http\Body\Structured;
use Azonmedia\Exceptions\RunTimeException;
use Azonmedia\Http\Body\Stream;
use Azonmedia\Exceptions\InvalidArgumentException;
use Azonmedia\Translator\Translator as t;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class Request
 * @package Azonmedia\Http
 *
 * This class uses code from Slim framework
 * @see https://github.com/slimphp/Slim/blob/3.x/Slim/Http/Request.php
 */
class Request extends Message implements ServerRequestInterface, \ArrayAccess, \Countable, \Iterator
{
    /**
     * @var string
     */
    protected string $method;

    /**
     * @var UriInterface
     */
    protected UriInterface $uri;

    /**
     * @var array
     */
    protected array $cookies = [];

    /**
     * @var array
     */
    protected array $attributes = [];

    /**
     * @var array
     */
    protected array $server_params = [];

    /**
     * @var array
     */
    protected array $uploaded_files = [];

    /**
     * @var string
     */
    protected string $request_target;

    /**
     * @var array
     */
    protected array $query_params = [];



    /**
    * @var null|array|object
     */
    protected $parsedBody;

    public function __construct(
        $method = Method::METHODS_MAP[Method::HTTP_GET],
        ?UriInterface $uri = null,
        array $headers = [],
        array $cookies = [],
        array $server_params = [],
        ?StreamInterface $Body = null,
        array $uploaded_files = []
    ) {
        $method = strtoupper($method);
        if (!$method) {
            throw new InvalidArgumentException(sprintf(t::_('No HTTP method provided.')));
        }
        if (!in_array($method, Method::METHODS_MAP)) {
            throw new InvalidArgumentException(sprintf(t::_('Wrong HTTP method %1$s provided.'), $method));
        }

        $this->method = $method;
        $this->uri = $uri ?? new Uri();
        $this->headers = array_change_key_case($headers, CASE_LOWER);
        $this->cookies = $cookies;
        $this->server_params = $server_params;
        $this->Body = $Body ?? new Stream();
        $this->uploaded_files = $uploaded_files;
    }

    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withrequest_target()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withrequest_target() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getRequestTarget()
    {
        if ($this->request_target) {
            return $this->request_target;
        }
        if ($this->uri === null) {
            return '/';
        }
        if ($this->uri instanceof Uri) {
            $basePath = $this->uri->getBasePath();
        } else {
            $basePath = '';
        }
        $path = $this->uri->getPath();
        $path = $basePath . '/' . ltrim($path, '/');
        $query = $this->uri->getQuery();
        if ($query) {
            $path .= '?' . $query;
        }
        $this->request_target = $path;
        return $this->request_target;
    }

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
     *     request-target forms allowed in request messages)
     * @param mixed $request_target
     * @return static
     * @throws InvalidArgumentException
     */
    public function withRequestTarget($request_target): self
    {
        if (preg_match('#\s#', $request_target)) {
            throw new InvalidArgumentException(
                'Invalid request target provided; must be a string and cannot contain whitespace'
            );
        }
        $clone = clone $this;
        $clone->request_target = $request_target;
        return $clone;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Non PSR-7 method
     * Returns the constant of the method as per Azonmedia\Http\Method::METHODS_MAP
     * @return int
     */
    public function getMethodConstant(): int
    {
        //$method_const = array_search(strtoupper($this->getMethod()), Method::METHODS_MAP);
        //return $method_const;
        return Method::get_method_constant($this);
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param string $method Case-sensitive method.
     * @return static
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    //public function withMethod(string $method) : self
    public function withMethod(/* string */ $method): self
    {
        $request = clone $this;
        $request->method = $method;
        return $request;
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return static
     */
    //public function withUri(UriInterface $uri, bool $preserveHost = false) : self
    public function withUri(UriInterface $uri, /* bool */ $preserveHost = false): self
    {
        $request = clone $this;
        $request->uri = $uri;
        if (!$preserveHost) {
            if ($uri->getHost() !== '') {
                $request->headers['Host'] = $uri->getHost();
            }
        } else {
            if ($uri->getHost() !== '' && (!$this->hasHeader('Host') || $this->getHeaderLine('Host') === '')) {
                $request->headers['Host'] = $uri->getHost();
            }
        }
        return $request;
    }

    ///////////////////////////////////////////////

    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams(): array
    {
        return $this->server_params;
    }

    /**
     * Return an instance with the specified server params.
     *
     * @param $server_params
     * @return $this
     */
    public function withServerParams($server_params): self
    {
        $clone = clone $this;
        $clone->server_params = $server_params;

        return $clone;
    }

    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The data MUST be compatible with the structure of the $_COOKIE
     * superglobal.
     *
     * @return array
     */
    public function getCookieParams(): array
    {
        return $this->cookies;
    }

    /**
     * Return an instance with the specified cookies.
     *
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * This method MUST NOT update the related Cookie header of the request
     * instance, nor related values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated cookie values.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return static
     */
    public function withCookieParams(array $cookies): self
    {
        $clone = clone $this;
        $clone->cookies = $cookies;

        return $clone;
    }

    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * Note: the query params might not be in sync with the URI or server
     * params. If you need to ensure you are only getting the original
     * values, you may need to parse the query string from `getUri()->getQuery()`
     * or from the `QUERY_STRING` server param.
     *
     * @return array
     */
    public function getQueryParams(): array
    {
        if (is_array($this->query_params)) {
            return $this->query_params;
        }
        if ($this->uri === null) {
            return [];
        }
        parse_str($this->uri->getQuery(), $this->query_params); // <-- URL decodes data
        return $this->query_params;
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's parse_str() would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * Setting query string arguments MUST NOT change the URI stored by the
     * request, nor the values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated query string arguments.
     *
     * @param array $query Array of query string arguments, typically from
     *     $_GET.
     * @return static
     */
    public function withQueryParams(array $query): self
    {
        $request = clone $this;
        $request->query_params = $query;
        return $request;
    }

    /**
     * Retrieve normalized file upload data.
     *
     * This method returns upload metadata in a normalized tree, with each leaf
     * an instance of Psr\Http\Message\UploadedFileInterface.
     *
     * These values MAY be prepared from $_FILES or the message body during
     * instantiation, or MAY be injected via withUploadedFiles().
     *
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadedFiles(): array
    {
        return $this->uploaded_files;
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param array $uploadedFiles An array tree of UploadedFileInterface instances.
     * @return static
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploaded_files): self
    {
        $clone = clone $this;
        $clone->uploaded_files = $uploaded_files;

        return $clone;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this method MUST
     * return the contents of $_POST.
     *
     * Otherwise, this method may return any results of deserializing
     * the request body content; as parsing returns structured content, the
     * potential types MUST be arrays or objects only. A null value indicates
     * the absence of body content.
     *
     * @return null|array|object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     * @throws NotImplementedException
     * @throws RunTimeException
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function getParsedBody() /* mixed */
    {
        if (!$this->parsedBody) {
            $body_contents = $this->getBody()->getContents();
            $this->getBody()->rewind();
            if ($body_contents) {
                $request_type = $this->getContentType();
                switch ($request_type) {
                    case ContentType::TYPE_HTML:
                        //throw new NotImplementedException(sprintf('Parsing a HTML request body is not implemented.'));
                        $content_types = $this->getHeader('Content-Type') ?? [];
                        foreach ($content_types as $content_type) {
                            $parsed_content_type = current(explode(';', $content_type));
                        }
                        if ($this->getMethod() === 'POST' && in_array($parsed_content_type, ['application/x-www-form-urlencoded', 'multipart/form-data'] )) {
                            //$this->parsedBody = $this->getBody()->getContents();
                            if ($parsed_content_type === 'application/x-www-form-urlencoded') {
                                parse_str($this->getBody()->getContents(), $result);
                                $this->parsedBody = $result;
                            } else {
                                throw new NotImplementedException(sprintf('Parsing a multipart/form-data request is not implemented.'));
                            }
                        }
                        break;
                    case ContentType::TYPE_JSON:
                        $this->parsedBody = json_decode($body_contents, true);
                        break;
                    case ContentType::TYPE_SOAP:
                        throw new NotImplementedException(sprintf('Parsing a SOAP request body is not implemented.'));
                        break;
                    case ContentType::TYPE_XML:
                        throw new NotImplementedException(sprintf('Parsing a XML request body is not implemented.'));
                        break;
                    case ContentType::TYPE_YAML:
                        throw new NotImplementedException(sprintf('Parsing a YAML request body is not implemented.'));
                        break;
                    case ContentType::TYPE_TEXT:
                        throw new NotImplementedException(sprintf('Parsing a TEXT request body is not implemented.'));
                        break;
                    case ContentType::TYPE_NATIVE:
                        $Body = $this->getBody();
                        if ($Body instanceof Structured) {
                            $structure = $Body->getStructure();
                            $this->parsedBody = $structure;
                        } else {
                            throw new RunTimeException(sprintf('PHP/native request provided but the Body is not of class %1$s but is %2$s.', Structured::class, get_class($Body)));
                        }
                        break;
                    default:
                        throw new NotImplementedException(sprintf('Parsing an unknown request body is not implemented.'));
                }
            } else {
                $this->parsedBody = null;
            }
        }
        return $this->parsedBody ?? null;
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * These MAY be injected during instantiation.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, use this method
     * ONLY to inject the contents of $_POST.
     *
     * The data IS NOT REQUIRED to come from $_POST, but MUST be the results of
     * deserializing the request body content. Deserialization/parsing returns
     * structured data, and, as such, this method ONLY accepts arrays or objects,
     * or a null value if nothing was available to parse.
     *
     * As an example, if content negotiation determines that the request data
     * is a JSON payload, this method could be used to create a request
     * instance with the deserialized parameters.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param null|array|object $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return static
     * @throws \InvalidArgumentException if an unsupported argument type is
     *     provided.
     */
    public function withParsedBody(/* mixed */ $data): self
    {
        $this->parsedBody = $data;

        $clone = clone $this;
        return $clone;
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    //public function getAttribute(string $name, /* mixed */ $default = NULL) /* mixed */
    public function getAttribute(/* string */ $name, /* mixed */ $default = null) /* mixed */
    {
        $ret = $default;
        if (array_key_exists($name, $this->attributes)) {
            $ret = $this->attributes[$name];
        }
        return $ret;
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return static
     */
    //public function withAttribute(string $name, /* mixed */ $value) : self
    public function withAttribute(/* string */ $name, /* mixed */ $value): self
    {
        $Request = clone $this;
        $Request->attributes[$name] = $value;
        return $Request;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return static
     */
    //public function withoutAttribute(string $name) : self
    public function withoutAttribute(/* string */ $name): self
    {
        $Request = clone $this;
        unset($this->attributes[$name]);
        return $Request;
    }

    public function __clone()
    {
        $this->Body = clone $this->Body;
    }

    /**
     * Check is the requested/accepted content JSON
     * @return bool
     */
    public function isContentJson(): bool
    {
        $content_type = $this->getContentType();
        return stripos($content_type, 'json') !== false ? true : false;
    }

    public function isContentHtml(): bool
    {
        $content_type = $this->getContentType();
        return stripos($content_type, 'html') !== false ? true : false;
    }

    public function isContentXml(): bool
    {
        $content_type = $this->getContentType();
        return stripos($content_type, 'xml') !== false ? true : false;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(/* scalar */ $offset): bool
    {
        // TODO: Implement offsetExists() method.
        return array_key_exists($offset, $this->query_params);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(/* scalar */ $offset)  /* mixed */
    {
        // TODO: Implement offsetGet() method.
        return $this->query_params[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws RunTimeException
     */
    public function offsetSet(/* scalar */ $offset, /* mixed */ $value): void
    {
        throw new RunTimeException(sprintf(t::_('The Request object is immutable - it is not allowed to modify the request params.')));
    }

    /**
     * @param mixed $offset
     * @throws RunTimeException
     */
    public function offsetUnset(/* scalar */ $offset): void
    {
        throw new RunTimeException(sprintf(t::_('The Request object is immutable - it is not allowed to modify the request params.')));
    }

    public function count(): int
    {
        return count($this->query_params);
    }

    //@implements \Iterator
    public function rewind(): void
    {
        reset($this->query_params);
    }

    //@implements \Iterator
    public function current() /* mixed */
    {
        $var = current($this->query_params);
        return $var;
    }

    //@implements \Iterator
    public function key() /* scalar */
    {
        $var = key($this->query_params);
        return $var;
    }

    //@implements \Iterator
    public function next() /* mixed */
    {
        $var = next($this->query_params);
        return $var;
    }

    //@implements \Iterator
    public function valid(): bool
    {
        $var = $this->current() !== false;
        return $var;
    }
}
