<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Models\RequestForQuotation;
use Nexus\Procurement\Models\RFQItem;
use Nexus\Procurement\Models\VendorQuote;
use Nexus\Procurement\Models\VendorQuoteItem;
use Nexus\Procurement\Models\Vendor;
use Nexus\Procurement\Models\PurchaseOrder;
use Nexus\Procurement\Enums\RFQStatus;
use Nexus\Sequencing\Services\SequencingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * RFQ Management Service
 *
 * Handles Request for Quotation creation, vendor invitation, quote comparison, and selection.
 */
class RFQManagementService
{
    public function __construct(
        private SequencingService $sequencingService,
        private PurchaseOrderService $poService
    ) {}

    /**
     * Create RFQ from approved requisition.
     */
    public function createFromRequisition(PurchaseRequisition $requisition, array $rfqData): RequestForQuotation
    {
        if ($requisition->status !== \Nexus\Procurement\Enums\RequisitionStatus::APPROVED) {
            throw new \InvalidArgumentException('Only approved requisitions can be converted to RFQ.');
        }

        return DB::transaction(function () use ($requisition, $rfqData) {
            // Generate RFQ number
            $rfqNumber = $this->sequencingService->generateNumber('rfq');

            // Create RFQ
            $rfq = RequestForQuotation::create([
                'rfq_number' => $rfqNumber,
                'requisition_id' => $requisition->id,
                'created_by' => Auth::id(),
                'title' => $rfqData['title'] ?? "RFQ for {$requisition->requisition_number}",
                'description' => $rfqData['description'] ?? $requisition->justification,
                'quote_deadline' => $rfqData['quote_deadline'],
                'status' => RFQStatus::DRAFT,
                'evaluation_criteria' => $rfqData['evaluation_criteria'] ?? $this->getDefaultEvaluationCriteria(),
            ]);

            // Create RFQ items from requisition items
            foreach ($requisition->items as $index => $requisitionItem) {
                $rfq->items()->create([
                    'requisition_item_id' => $requisitionItem->id,
                    'line_number' => $index + 1,
                    'item_description' => $requisitionItem->item_description,
                    'quantity' => $requisitionItem->quantity,
                    'unit_of_measure' => $requisitionItem->uom,
                    'specifications' => $requisitionItem->specifications,
                    'estimated_unit_price' => $requisitionItem->estimated_unit_price,
                    'required_delivery_date' => $requisition->expected_delivery_date,
                ]);
            }

            return $rfq;
        });
    }

    /**
     * Invite vendors to submit quotes.
     */
    public function inviteVendors(RequestForQuotation $rfq, array $vendorIds): void
    {
        if ($rfq->status !== RFQStatus::DRAFT) {
            throw new \InvalidArgumentException('Only draft RFQs can have vendors invited.');
        }

        foreach ($vendorIds as $vendorId) {
            $rfq->invitedVendors()->attach($vendorId, [
                'invited_at' => now(),
                'response_status' => 'pending',
            ]);
        }
    }

    /**
     * Send RFQ to invited vendors.
     */
    public function sendToVendors(RequestForQuotation $rfq): void
    {
        if ($rfq->status !== RFQStatus::DRAFT) {
            throw new \InvalidArgumentException('Only draft RFQs can be sent to vendors.');
        }

        if ($rfq->invitedVendors()->count() === 0) {
            throw new \InvalidArgumentException('RFQ must have invited vendors before sending.');
        }

        $rfq->update(['status' => RFQStatus::SENT]);

        // TODO: Send email notifications to vendors
        // This would integrate with email service
    }

    /**
     * Submit vendor quote.
     */
    public function submitVendorQuote(RequestForQuotation $rfq, Vendor $vendor, array $quoteData): VendorQuote
    {
        if (!$rfq->isOpen()) {
            throw new \InvalidArgumentException('RFQ is not open for quotes.');
        }

        if (!$rfq->invitedVendors()->where('vendor_id', $vendor->id)->exists()) {
            throw new \InvalidArgumentException('Vendor is not invited to this RFQ.');
        }

        return DB::transaction(function () use ($rfq, $vendor, $quoteData) {
            // Create vendor quote
            $quote = VendorQuote::create([
                'rfq_id' => $rfq->id,
                'vendor_id' => $vendor->id,
                'submitted_at' => now(),
                'status' => 'submitted',
                'total_quoted_price' => $quoteData['total_quoted_price'],
                'delivery_days' => $quoteData['delivery_days'] ?? null,
                'payment_terms' => $quoteData['payment_terms'] ?? null,
                'validity_days' => $quoteData['validity_days'] ?? 30,
                'notes' => $quoteData['notes'] ?? null,
            ]);

            // Create quote items
            foreach ($quoteData['items'] as $itemData) {
                $rfqItem = RFQItem::findOrFail($itemData['rfq_item_id']);

                $quote->items()->create([
                    'rfq_item_id' => $rfqItem->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemData['quantity'] * $itemData['unit_price'],
                    'delivery_days' => $itemData['delivery_days'] ?? $quote->delivery_days,
                    'alternate_offer' => $itemData['alternate_offer'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                    'specifications_met' => $itemData['specifications_met'] ?? true,
                ]);
            }

            // Update invitation status
            $rfq->invitedVendors()->updateExistingPivot($vendor->id, [
                'responded_at' => now(),
                'response_status' => 'accepted',
            ]);

            return $quote;
        });
    }

    /**
     * Compare vendor quotes.
     */
    public function compareQuotes(RequestForQuotation $rfq): array
    {
        $quotes = $rfq->vendorQuotes()->with(['vendor', 'items'])->get();

        $comparison = [
            'rfq' => $rfq,
            'quotes' => [],
            'summary' => [
                'total_quotes' => $quotes->count(),
                'lowest_price' => $quotes->min('total_quoted_price'),
                'highest_price' => $quotes->max('total_quoted_price'),
                'average_price' => $quotes->avg('total_quoted_price'),
                'price_range' => $quotes->max('total_quoted_price') - $quotes->min('total_quoted_price'),
            ],
        ];

        foreach ($quotes as $quote) {
            $comparison['quotes'][] = [
                'quote' => $quote,
                'vendor' => $quote->vendor,
                'price_score' => $quote->getPriceScore(),
                'delivery_score' => $quote->getDeliveryScore(),
                'meets_requirements' => $quote->meetsRequirements(),
                'is_valid' => $quote->isValid(),
                'items' => $quote->items->map(function ($item) {
                    return [
                        'rfq_item' => $item->rfqItem,
                        'quoted_quantity' => $item->quantity,
                        'quoted_price' => $item->unit_price,
                        'total_price' => $item->total_price,
                        'price_variance' => $item->getPriceVariance(),
                        'quantity_matches' => $item->quantityMatches(),
                        'specifications_met' => $item->specifications_met,
                    ];
                }),
            ];
        }

        // Sort by total price (lowest first)
        usort($comparison['quotes'], function ($a, $b) {
            return $a['quote']['total_quoted_price'] <=> $b['quote']['total_quoted_price'];
        });

        return $comparison;
    }

    /**
     * Select winning vendor and create purchase order.
     */
    public function selectWinner(RequestForQuotation $rfq, VendorQuote $winningQuote, array $selectionData = []): PurchaseOrder
    {
        if ($rfq->status !== RFQStatus::SENT) {
            throw new \InvalidArgumentException('RFQ must be sent before selecting winner.');
        }

        if (!$winningQuote->isValid()) {
            throw new \InvalidArgumentException('Selected quote is no longer valid.');
        }

        return DB::transaction(function () use ($rfq, $winningQuote, $selectionData) {
            // Update RFQ with selection
            $rfq->update([
                'selected_vendor_id' => $winningQuote->vendor_id,
                'selected_quote_id' => $winningQuote->id,
                'status' => RFQStatus::CLOSED,
                'closed_at' => now(),
                'closed_by' => Auth::id(),
                'evaluation_notes' => $selectionData['evaluation_notes'] ?? null,
            ]);

            // Update winning quote status
            $winningQuote->update(['status' => 'selected']);

            // Mark other quotes as rejected
            $rfq->vendorQuotes()->where('id', '!=', $winningQuote->id)->update(['status' => 'rejected']);

            // Create purchase order from winning quote
            $purchaseOrder = $this->createPurchaseOrderFromQuote($rfq, $winningQuote);

            // Update requisition status
            if ($rfq->requisition) {
                $rfq->requisition->update([
                    'status' => \Nexus\Procurement\Enums\RequisitionStatus::ORDERED,
                ]);
            }

            return $purchaseOrder;
        });
    }

    /**
     * Create purchase order from winning quote.
     */
    private function createPurchaseOrderFromQuote(RequestForQuotation $rfq, VendorQuote $quote): PurchaseOrder
    {
        // Create PO using the existing PO service
        $poData = [
            'vendor_id' => $quote->vendor_id,
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays($quote->delivery_days ?? 30),
            'payment_terms' => $quote->payment_terms,
            'notes' => "Created from RFQ {$rfq->rfq_number}",
        ];

        // Create PO directly (not from requisition since we already have the quote)
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => $this->sequencingService->generateNumber('purchase_order'),
            'requisition_id' => $rfq->requisition_id,
            'vendor_id' => $quote->vendor_id,
            'status' => \Nexus\Procurement\Enums\PurchaseOrderStatus::DRAFT,
            'order_date' => $poData['order_date'],
            'expected_delivery_date' => $poData['expected_delivery_date'],
            'payment_terms' => $poData['payment_terms'],
            'notes' => $poData['notes'],
            'created_by' => Auth::id(),
        ]);

        // Create PO items from quote items
        foreach ($quote->items as $quoteItem) {
            $purchaseOrder->items()->create([
                'requisition_item_id' => $quoteItem->rfqItem->requisition_item_id,
                'item_description' => $quoteItem->rfqItem->item_description,
                'quantity' => $quoteItem->quantity,
                'unit_price' => $quoteItem->unit_price,
                'total_price' => $quoteItem->total_price,
                'uom' => $quoteItem->rfqItem->unit_of_measure,
                'specifications' => $quoteItem->rfqItem->specifications,
            ]);
        }

        // Calculate totals
        $this->poService->updateTotals($purchaseOrder);

        return $purchaseOrder;
    }

    /**
     * Get default evaluation criteria.
     */
    private function getDefaultEvaluationCriteria(): array
    {
        return [
            'price' => ['weight' => 60, 'description' => 'Total quoted price'],
            'delivery' => ['weight' => 20, 'description' => 'Delivery time'],
            'quality' => ['weight' => 10, 'description' => 'Quality and specifications compliance'],
            'terms' => ['weight' => 5, 'description' => 'Payment and other terms'],
            'vendor_rating' => ['weight' => 5, 'description' => 'Vendor performance rating'],
        ];
    }

    /**
     * Close RFQ without selection.
     */
    public function closeRFQ(RequestForQuotation $rfq, string $reason): void
    {
        $rfq->update([
            'status' => RFQStatus::CLOSED,
            'closed_at' => now(),
            'closed_by' => Auth::id(),
            'evaluation_notes' => $reason,
        ]);

        // Mark all quotes as rejected
        $rfq->vendorQuotes()->update(['status' => 'rejected']);
    }
}