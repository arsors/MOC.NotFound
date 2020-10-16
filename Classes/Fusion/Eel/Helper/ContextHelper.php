<?php

namespace MOC\NotFound\Fusion\Eel\Helper;

use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;

class ContextHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\InjectConfiguration(path="contentDimensions", package="Neos.ContentRepository")
     * @var array
     */
    protected $contentDimensionsConfiguration;

    /**
     * Returns a context array with matched dimension values per dimension for given request uri path. If nothing
     * matches, it returns a context array with default dimension values per dimension.
     *
     * @param $requestUriPath
     * @return array
     */
    public function ofRequestUriPath($requestUriPath)
    {
        // No dimensions configured, context is empty
        if (count($this->contentDimensionsConfiguration) === 0) {
            return [];
        }

        $hosts = [$requestUriPath->getHost()];
        $uriSegments = $this->getUriSegments($requestUriPath->getPath());
        $dimensionValues = $this->getDimensionValuesForUriSegmentsOrHost($uriSegments,$hosts);
        if (empty($dimensionValues)) {
            $dimensionValues = $this->getDefaultDimensionValues();
        }

        $targetDimensionValues = array_map(function ($dimensionValues) {
            return reset($dimensionValues); // Default target dimension value is first dimension value
        }, $dimensionValues);


        return [
            'dimensions' => $dimensionValues,
            'targetDimensions' => $targetDimensionValues
        ];
    }

    /**
     * @param array $uriSegments
     * @param array $hosts
     * @return array
     */
    protected function getDimensionValuesForUriSegmentsOrHost($uriSegments,$hosts)
    {
        if (count($uriSegments) !== count($this->contentDimensionsConfiguration)) {
            return [];
        }

        $index = 0;
        $dimensionValues = [];
        foreach ($this->contentDimensionsConfiguration as $dimensionName => $dimensionConfiguration) {
            $index = $index++;
            $uriSegment = $uriSegments[$index];
            $host = $hosts[$index];
            foreach ($dimensionConfiguration['presets'] as $preset) {
                if (
                    (!empty($preset['uriSegment']) && $uriSegment === $preset['uriSegment']) ||
                    (!empty($preset['resolutionHost']) && $host === $preset['resolutionHost'])
                ) {
                    $dimensionValues[$dimensionName] = $preset['values'];
                    continue 2;
                }
            }
        }

        if (count($uriSegments) !== count($dimensionValues)) {
            return [];
        }

        return $dimensionValues;
    }

    /**
     * Returns default dimension values per dimension.
     *
     * @return array
     */
    protected function getDefaultDimensionValues()
    {
        $dimensionValues = [];
        foreach ($this->contentDimensionsConfiguration as $dimensionName => $dimensionConfiguration) {
            $dimensionValues[$dimensionName] =  [$dimensionConfiguration['default']];
        }
        return $dimensionValues;
    }

    protected function getUriSegments($requestUriPath)
    {
        $pathParts = explode('/', trim($requestUriPath, '/'), 2);
        return explode('_', $pathParts[0]);
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
