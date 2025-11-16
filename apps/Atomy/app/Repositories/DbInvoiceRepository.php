<?php

namespace Nexus\Atomy\Repositories;

use Nexus\ProjectManagement\Contracts\InvoiceRepositoryInterface;
use Nexus\ProjectManagement\Contracts\InvoiceInterface;
use Nexus\Atomy\Models\Invoice;

class DbInvoiceRepository implements InvoiceRepositoryInterface
{
    public function create(array $data): InvoiceInterface
    {
        return Invoice::create($data);
    }

    public function findById(int $id): ?InvoiceInterface
    {
        return Invoice::find($id);
    }

    public function findByProject(int $projectId): array
    {
        return Invoice::where('project_id', $projectId)->get()->all();
    }

    public function update(InvoiceInterface $invoice, array $data): bool
    {
        return Invoice::where('id', $invoice->getId())->update($data) > 0;
    }

    public function delete(InvoiceInterface $invoice): bool
    {
        return Invoice::where('id', $invoice->getId())->delete() > 0;
    }

    public function markAsPaid(InvoiceInterface $invoice): bool
    {
        return $this->update($invoice, ['status' => 'paid']);
    }
}