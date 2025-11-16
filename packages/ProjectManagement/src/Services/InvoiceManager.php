<?php

namespace Nexus\ProjectManagement\Services;

use Nexus\ProjectManagement\Contracts\InvoiceRepositoryInterface;
use Nexus\ProjectManagement\Contracts\InvoiceInterface;

class InvoiceManager
{
    private InvoiceRepositoryInterface $invoiceRepository;

    public function __construct(InvoiceRepositoryInterface $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    public function createInvoice(array $data): InvoiceInterface
    {
        // Generate invoice number, calculate amount, etc.
        return $this->invoiceRepository->create($data);
    }

    public function markAsPaid(InvoiceInterface $invoice): bool
    {
        return $this->invoiceRepository->markAsPaid($invoice);
    }

    public function getInvoicesByProject(int $projectId): array
    {
        return $this->invoiceRepository->findByProject($projectId);
    }
}