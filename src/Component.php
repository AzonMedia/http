<?php
declare(strict_types=1);

namespace Azonmedia\Http;

use Azonmedia\Components\BaseComponent;

class Component extends BaseComponent
{

    protected const COMPONENT_NAME = "HTTP library";
    protected const COMPONENT_URL = 'https://http.packages.guzaba.org/';
    protected const COMPONENT_NAMESPACE = __NAMESPACE__;
    protected const COMPONENT_VERSION = '0.0.1';
    protected const VENDOR_NAME = 'Azonmedia';
    protected const VENDOR_URL = 'https://azonmedia.com';
    protected const ERROR_REFERENCE_URL = 'https://github.com/AzonMedia/http/docs/ErrorReference/';
}