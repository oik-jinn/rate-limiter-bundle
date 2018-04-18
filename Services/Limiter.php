<?php

namespace RateLimitBundle\Services;

use InvalidArgumentException;

class Limiter
{
    const OPERATOR_USER = 'user_id';
    const OPERATOR_IP = 'ip';

    protected $client;
    protected $limiters;

    /**
     * Get a Redis client.
     *
     * @param  Redis Client $client
     * @param  array $limiters
     *
     * @return void
     */
    public function __construct($client, Array $limiters)
    {
        $this->client = $client;
        $this->limiters = $limiters;
    }

    /**
     * check whether the rate is exceeded
     *
     * @param string  $resourceType
     * @param integer $operatorIdentifier
     * @param integer $resourceIdentifier
     *
     * @return boolean
     * @throws \InvalidArgumentException
     */
    public function isExceeded($resourceType, $operatorIdentifier, $resourceIdentifier = null)
    {
        // Get operatorType  according to operator identifier
        $operatorType = filter_var($operatorIdentifier, FILTER_VALIDATE_IP) ? self::OPERATOR_IP : self:: OPERATOR_USER;
        $configuration = $this->getLimiterConfiguration($resourceType, $operatorType);

        // Check whether resourceIdentifier is legal
        $isResourceUnique = $configuration[0]['is_resource_unique'] ?? false;
        if ($resourceIdentifier  && !$isResourceUnique) {
            throw new InvalidArgumentException("the resource of limiter is not unique,please check your configuration");
        }

        $algorithm = $configuration[0]['algorithm'];
        $limiterKey = $this->getLimiterKey([$resourceType, $algorithm, $operatorIdentifier, $resourceIdentifier]);

        switch ($algorithm) {
            case 'counter':
                $limiter = array_pop($configuration);
                return $this->counterLimiter($limiter, $limiterKey);
                break;
            case 'rolling window':
                // only rolling window can accept two conditions
                $limiter = $configuration;
                return $this->rollingWindowLimiter($limiter, $limiterKey);
                break;
            case 'leaky bucket':
                $limiter = array_pop($configuration);
                return $this->leakyBucketLimiter($limiter, $limiterKey);
                break;
            case 'token bucket':
                $limiter = array_pop($configuration);
                return $this->tokenBucketLimiter($limiter, $limiterKey);
                break;
            default:
                # code...
                break;
        }
    }

    /**
     * get limiter configuration
     *
     * @param string  $resourceType
     * @param string  $operatorType
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function getLimiterConfiguration($resourceType, $operatorType)
    {
        if (isset($this->limiters[$resourceType][$operatorType])) {
            return $this->limiters[$resourceType][$operatorType];
        }

        throw new InvalidArgumentException("No limiters for $resourceType $operatorType");
    }

    /**
     * get limiter key
     *
     * @param array  $limiterIdentifier
     *
     * @return string
     */
    protected function getLimiterKey($limiterIdentifier)
    {
        return implode($limiterIdentifier, ':');
    }
}
