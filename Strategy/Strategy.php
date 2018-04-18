<?php

namespace RateLimitBundle\Strategy;

abstract class Strategy
{
    public $waitingTime;

    abstract protected function limiter();
}
