<?php

namespace Nexus\ProjectManagement\Contracts;

interface InvoiceInterface
{
    public function getId(): int;
    public function getProjectId(): int;
    public function getInvoiceNumber(): string;
    public function getAmount(): float;
    public function getStatus(): string; // draft, sent, paid
    public function getDueDate(): \DateTime;
    public function getItems(): array; // array of invoice items
}