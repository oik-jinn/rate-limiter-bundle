<?php

namespace RateLimitBundle\Strategy;

class Counter extends Strategy
{
    /**
     *
     * @param array  $limiter
     * @param string  $key
     *
     * @return boolean
     */
    public function limiter($limiter, $key)
    {
        $now = time();
        // If it meet the condition, that means we should clear cache at every midnight
        if (isset($limiter['flush_cache_by_day']) && $limiter['flush_cache_by_day'] && $limiter['decay'] == 0) {
            $limiter['decay'] = strtotime('tomorrow midnight') - $now;
        }

        // Watch the key to avoid other transaction change the value
        $this->client->watch($key);
        $current = $this->client->llen($key);

        // Transaction start
        $tx = $this->client->transaction();
        if ($current >= $limiter['limit']) {
            return false;
        } else {
            if ($this->client->exists($key)) {
                $tx->rpush($key, $now);

                try {
                     $replies = $tx->execute();
                     return true;
                } catch (Exception $e) {
                    return $e->getMessage();
                }
            } else {
                // Using transaction to let rpush and expire to be an atomic operation
                $tx->rpush($key, $now);
                $tx->expire($key, $limiter['decay']);

                try {
                     $replies = $tx->execute();
                     return true;
                } catch (Exception $e) {
                    return $e->getMessage();
                }
            }
        }
    }
}
