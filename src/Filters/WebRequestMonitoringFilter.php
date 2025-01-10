<?php

namespace Inspector\CodeIgniter\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use Inspector\CodeIgniter\Config\Inspector;
use Inspector\CodeIgniter\Utils;

class WebRequestMonitoringFilter implements FilterInterface
{
    /**
     * Inspector package configuration.
     *
     * @var Inspector
     */
    protected $config;

    public function __construct()
    {
        $this->config = config('Inspector');
        helper('inspector');
    }

    /**
     * Override this method to implement a custom ignoring logic.
     */
    public function shouldBeMonitored(RequestInterface $request): bool
    {
        return true;
    }

    /**
     * Before the request enters into the application.
     *
     * @param mixed|null $arguments
     *
     * @return void
     *
     * @throws Exception
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $matchedRoute = service('router')->getMatchedRoute();

        if (
            ! $this->shouldBeMonitored($request)
            || $this->isIgnored($matchedRoute[0])
        ) {
            return;
        }

        inspector()
            ->startTransaction($request->getMethod() . ' ' . $this->normalizeURI($matchedRoute[0]))
            ->addContext('Request Body', $request->getBody())
            ->markAsRequest();

        /*if (service('auth')->isLoggedIn()) {
            inspector()->transaction()->withUser();
        }*/
    }

    /**
     * Consistent format for URI.
     *
     * @param string $uri
     * @return string
     */
    protected function normalizeURI(string $uri): string
    {
        return '/' . \trim($uri, '/');
    }

    /**
     * After the controller.
     *
     * @param mixed|null $arguments
     *
     * @return void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        inspector()->transaction()->setResult($response->getStatusCode());
    }

    /**
     * Determine if the current request is in the ignore list.
     */
    protected function isIgnored(string $path): bool
    {
        foreach ($this->config->ignoreRoutes as $pattern) {
            if (Utils::matchWithWildcard($path, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
