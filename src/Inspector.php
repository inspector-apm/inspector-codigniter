<?php

namespace Inspector\CodeIgniter;

use CodeIgniter\Config\BaseConfig;
use Inspector\Configuration;
use Inspector\Inspector as InspectorLibrary;

use Inspector\Models\Segment;

/**
 * Allows developers to use the inspector library in CI4
 */
class Inspector extends InspectorLibrary
{
    /**
     * The latest version of the client library.
     *
     * @var string
     */
    public const VERSION = '0.1.1';

    private Segment $Segment;

    public static function getInstance(BaseConfig $config)
    {
        $requestURI = $_SERVER['REQUEST_URI'] ?? '';

        $configuration = (new Configuration($config->IngestionKey))
            ->setEnabled($config->Enable ?? true)
            ->setUrl($config->URL ?? 'https://ingest.inspector.dev')
            ->setVersion(self::VERSION)
            ->setTransport($config->Transport ?? 'async')
            ->setOptions($config->Options ?? [])
            ->setMaxItems($config->MaxItems ?? 100);

        $inspector = new self($configuration);

        // Only start a transation if AutoInspect is set to true
        if ($config->AutoInspect) {
            $pathInfo = explode('?', $requestURI);
            $path     = array_shift($pathInfo);

            $inspector->startTransaction($path);
        }

        return $inspector;
    }

    public function setSegment(Segment $segment)
    {
        $this->Segment = $segment;
    }

    public function getSegment()
    {
        return $this->Segment;
    }
}
