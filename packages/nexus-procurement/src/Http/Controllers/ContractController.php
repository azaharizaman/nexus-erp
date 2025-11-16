<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Controllers;

use Nexus\Procurement\Models\ProcurementContract;
use Nexus\Procurement\Models\ContractAmendment;
use Nexus\Procurement\Services\ContractManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contract Management Controller
 *
 * API endpoints for procurement contract management.
 */
class ContractController extends Controller
{
    public function __construct(
        private ContractManagementService $contractService
    ) {}

    /**
     * Get all contracts.
     */
    public function index(Request $request): JsonResource
    {
        $query = ProcurementContract::with(['vendor', 'items', 'amendments']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->has('contract_type')) {
            $query->where('contract_type', $request->contract_type);
        }

        $contracts = $query->paginate($request->get('per_page', 15));

        return JsonResource::collection($contracts);
    }

    /**
     * Create a new contract.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'contract_number' => 'required|string|unique:procurement_contracts',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contract_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'contract_value' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'payment_terms' => 'nullable|string',
            'delivery_terms' => 'nullable|string',
            'auto_renewal' => 'boolean',
            'renewal_period_months' => 'nullable|integer|min:1',
            'minimum_order_value' => 'nullable|numeric|min:0',
            'maximum_order_value' => 'nullable|numeric|min:0',
            'items' => 'array',
            'items.*.item_description' => 'required|string',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.uom' => 'nullable|string',
        ]);

        $contract = $this->contractService->createContract($validated);

        return response()->json($contract->load(['vendor', 'items']), 201);
    }

    /**
     * Get a specific contract.
     */
    public function show(ProcurementContract $contract): JsonResource
    {
        return new JsonResource($contract->load([
            'vendor',
            'items',
            'amendments.creator',
            'amendments.approver',
            'purchaseOrders'
        ]));
    }

    /**
     * Update a contract.
     */
    public function update(Request $request, ProcurementContract $contract): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'contract_value' => 'sometimes|numeric|min:0',
            'payment_terms' => 'nullable|string',
            'delivery_terms' => 'nullable|string',
            'auto_renewal' => 'boolean',
            'renewal_period_months' => 'nullable|integer|min:1',
            'minimum_order_value' => 'nullable|numeric|min:0',
            'maximum_order_value' => 'nullable|numeric|min:0',
        ]);

        $contract->update($validated);

        return response()->json($contract->load(['vendor', 'items']));
    }

    /**
     * Amend a contract.
     */
    public function amend(Request $request, ProcurementContract $contract): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'changes' => 'required|array',
            'effective_date' => 'required|date',
            'approved_by' => 'nullable|exists:users,id',
            'approved_at' => 'nullable|date',
        ]);

        $amendment = $this->contractService->amendContract($contract->id, $validated);

        return response()->json($amendment, 201);
    }

    /**
     * Get contract amendments.
     */
    public function amendments(ProcurementContract $contract): JsonResource
    {
        $amendments = $contract->amendments()->with(['creator', 'approver'])->get();

        return JsonResource::collection($amendments);
    }

    /**
     * Renew a contract.
     */
    public function renew(Request $request, ProcurementContract $contract): JsonResponse
    {
        $validated = $request->validate([
            'new_end_date' => 'nullable|date|after:today',
        ]);

        $renewedContract = $this->contractService->renewContract($contract->id, $validated);

        return response()->json($renewedContract);
    }

    /**
     * Get contract utilization.
     */
    public function utilization(ProcurementContract $contract): JsonResponse
    {
        $utilization = $this->contractService->getContractUtilization($contract->id);

        return response()->json($utilization);
    }

    /**
     * Link PO to contract.
     */
    public function linkPO(Request $request, ProcurementContract $contract): JsonResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
        ]);

        $po = \Nexus\Procurement\Models\PurchaseOrder::findOrFail($validated['purchase_order_id']);

        $this->contractService->linkPOToContract($po, $contract);

        return response()->json(['message' => 'PO linked to contract successfully']);
    }

    /**
     * Get contracts due for renewal.
     */
    public function dueForRenewal(): JsonResource
    {
        $contracts = $this->contractService->getContractsDueForRenewal();

        return JsonResource::collection($contracts);
    }
}