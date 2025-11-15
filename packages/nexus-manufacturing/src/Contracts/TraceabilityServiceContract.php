<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Illuminate\Support\Collection;

interface TraceabilityServiceContract
{
    /**
     * Record batch genealogy (which raw materials went into which finished goods).
     * 
     * @param string $workOrderId
     * @param string $finishedGoodsLot
     * @param array $rawMaterialLots [['product_id', 'lot_number', 'quantity'], ...]
     * @return array BatchGenealogy record
     */
    public function recordBatchGenealogy(string $workOrderId, string $finishedGoodsLot, array $rawMaterialLots): array;

    /**
     * Forward traceability: Where did this raw material lot go?
     * 
     * @param string $rawMaterialLotNumber
     * @return Collection Finished goods lots that used this material
     */
    public function traceForward(string $rawMaterialLotNumber): Collection;

    /**
     * Backward traceability: What raw materials went into this finished goods lot?
     * 
     * @param string $finishedGoodsLot
     * @return Collection Raw material lots used
     */
    public function traceBackward(string $finishedGoodsLot): Collection;

    /**
     * Get complete traceability chain (full genealogy).
     * 
     * @param string $lotNumber
     * @return array ['forward' => Collection, 'backward' => Collection]
     */
    public function getCompleteChain(string $lotNumber): array;

    /**
     * Identify impacted lots for a recall (which finished goods lots used a recalled material).
     * 
     * @param string $recalledLotNumber
     * @return Collection Finished goods lots to recall
     */
    public function identifyRecallImpact(string $recalledLotNumber): Collection;
}
