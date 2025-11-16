<?php

namespace Nexus\ProjectManagement\Contracts;

interface ExpenseInterface
{
    public function getId(): int;
    public function getProjectId(): int;
    public function getDescription(): string;
    public function getAmount(): float;
    public function getDate(): \DateTime;
    public function isBillable(): bool;
    public function getStatus(): string; // pending, approved, rejected
    public function getReceiptPath(): ?string;
}