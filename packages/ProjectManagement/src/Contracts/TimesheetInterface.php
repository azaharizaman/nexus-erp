<?php

namespace Nexus\ProjectManagement\Contracts;

interface TimesheetInterface
{
    public function getId(): int;
    public function getTaskId(): int;
    public function getUserId(): int;
    public function getDate(): \DateTime;
    public function getHours(): float;
    public function getDescription(): ?string;
    public function isBillable(): bool;
    public function getStatus(): string; // pending, approved, rejected
}