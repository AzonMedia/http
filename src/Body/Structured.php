<?php

declare(strict_types=1);

namespace Azonmedia\Http\Body;

//use Guzaba2\Application\Application;
use Azonmedia\Exceptions\RunTimeException;
use Azonmedia\Translator\Translator as t;
use Psr\Http\Message\StreamInterface;

/**
 * Class Arr
 * Array is a reserved word
 * @package Azonmedia\Http
 */
class Structured implements StreamInterface
{

    /**
     * @var iterable
     */
    protected iterable $structure = [];

    /**
     * Will be lowered when the processing is over
     * @var bool
     */
    protected bool $is_writable_flag = true;

    protected bool $is_readable_flag = true;

    protected bool $is_seekable_flag = true;

    //public const JSON_ENCODE_FLAGS = JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE;
    public const JSON_ENCODE_FLAGS = JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

    /**
     * Structured constructor.
     * @param iterable $structure
     */
    public function __construct(iterable $structure = [])
    {
        $this->structure = $structure;
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     * @throws RunTimeException
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function __toString(): string
    {
        $ret = '';
        if ($this->isReadable()) {
            $this->rewind();
            $ret = $this->getContents();
        } else {
            throw new RunTimeException(sprintf(t::_('Can not convert this stream to string because it is not readable.')));
        }

        return $ret;
    }

    public static function getJsonFlags(): int
    {
        $flags = self::JSON_ENCODE_FLAGS;
//        if (!Application::is_production()) {
//            $flags |= JSON_PRETTY_PRINT;
//        }
        return $flags;
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close(): void
    {
        //does nothing
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach() /* ?resource */
    {
        $this->is_writable_flag = false;
        $this->is_readable_flag = false;
        $this->stream = null;

        return null;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize(): ?int
    {
        //$size = count($this->structure);
        if (is_array($this->structure)) {
            $size = count($this->structure);
        } else {
            $size = iterator_count($this->structure);
        }
        return $size;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell(): int
    {
        $position = 0;
        return $position;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof(): bool
    {
        return false;
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable(): bool
    {
        return $this->is_seekable_flag;
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @throws RunTimeException on failure.
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function seek(/* int */ $offset, /* int */ $whence = SEEK_SET)
    {
        if (!$this->isSeekable()) {
            throw new RunTimeException(t::_('Can not seek this stream.'));
        }
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @throws RunTimeException on failure.
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \ReflectionException
     * @link http://www.php.net/manual/en/function.fseek.php
     * @see seek()
     */
    public function rewind(): void
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException(t::_('Can not rewind this stream.'));
        }
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        return $this->is_writable_flag;
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return void Returns the number of bytes written to the stream.
     * @throws RunTimeException on failure.
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function write(/* string */ $string)
    {
        if (!$this->isWritable()) {
            throw new RuntimeException(t::_('Can not write to this stream.'));
        }
        throw new RunTimeException(t::_('Please use the Structured::getStructure() method.'));
        // return $size;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable(): bool
    {
        return $this->is_readable_flag;
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws RunTimeException if an error occurs.
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function read(/* int */ $length)
    {
        if (!$this->isReadable()) {
            throw new RuntimeException(t::_('Can not read from this stream.'));
        }
        throw new RunTimeException(t::_('Please use the Structured::getStructure() method.'));
        // return $str;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws RunTimeException if unable to read or an error occurs while
     *     reading.
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function getContents(): string
    {
        if (!$this->isReadable()) {
            throw new RuntimeException(t::_('Can not get the contents of this stream.'));
        }
        //convert instead to JSON
        $contents = json_encode($this->structure, self::getJsonFlags());
        return $contents;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata(/* ?string */ $key = null)
    {
        return null;
    }

    /**
     * Non PSR-7 method
     * Returns a reference to the structure.
     * This can be used for reading and writing.
     * @return array
     */
    public function &getStructure(): iterable
    {
        return $this->structure;
    }
}
