<?php

namespace Nexus\ProjectManagement\Contracts;

interface InvoiceRepositoryInterface
{
    public function create(array $data): InvoiceInterface;
    public function findById(int $id): ?InvoiceInterface;
    public function findByProject(int $projectId): array;
    public function update(InvoiceInterface $invoice, array $data): bool;
    public function delete(InvoiceInterface $invoice): bool;
    public function markAsPaid(InvoiceInterface $invoice): bool;
}