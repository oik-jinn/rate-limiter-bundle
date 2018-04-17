<?php

namespace RateLimitBundle\Strategy;

abstract class Strategy
{
    abstract protected function limiter();
}
