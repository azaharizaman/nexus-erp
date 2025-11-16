<?php

namespace Nexus\ProjectManagement\Contracts;

interface ResourceAllocationInterface
{
    public function getId(): int;
    public function getProjectId(): int;
    public function getUserId(): int;
    public function getAllocationPercentage(): float;
    public function getStartDate(): \DateTime;
    public function getEndDate(): ?\DateTime;
}