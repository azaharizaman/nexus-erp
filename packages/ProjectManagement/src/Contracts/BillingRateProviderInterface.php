<?php

namespace Nexus\ProjectManagement\Contracts;

interface BillingRateProviderInterface
{
    public function getHourlyRateForUser(int $userId): float;
}