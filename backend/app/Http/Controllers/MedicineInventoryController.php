<?php

namespace App\Http\Controllers;

use App\Models\MedicineInventory;
use App\Models\MedicineTransaction;
use App\Services\InventoryAlertService;
use App\Services\InventoryExpiryAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicineInventoryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | View Medicine Inventory
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $inventory = MedicineInventory::with(
            'medication'
        )
            ->orderBy('id')
            ->get();

        return response()->json([
            'inventory' => $inventory,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Medicine Inventory Record
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        InventoryAlertService $inventoryAlertService,
        InventoryExpiryAlertService $inventoryExpiryAlertService
    ) {
        $validated = $request->validate([
            'medication_id' =>
                'required|exists:medications,id',

            'quantity' =>
                'required|integer|min:0',

            'minimum_stock' =>
                'nullable|integer|min:0',

            'expiry_date' =>
                'nullable|date',

            'location' =>
                'nullable|string|max:255',
        ]);

        $exists = MedicineInventory::where(
            'medication_id',
            $validated['medication_id']
        )->exists();

        if ($exists) {
            return response()->json([
                'message' =>
                    'Inventory already exists for this medication.',
            ], 422);
        }

        $inventory = DB::transaction(
            function () use (
                $validated,
                $inventoryAlertService,
                $inventoryExpiryAlertService
            ) {
                $inventory =
                    MedicineInventory::create([
                        'medication_id' =>
                            $validated['medication_id'],

                        'quantity' =>
                            $validated['quantity'],

                        'minimum_stock' =>
                            $validated['minimum_stock']
                            ?? 10,

                        'expiry_date' =>
                            $validated['expiry_date']
                            ?? null,

                        'location' =>
                            $validated['location']
                            ?? null,
                    ]);

                if (
                    (int) $validated['quantity']
                    > 0
                ) {
                    MedicineTransaction::create([
                        'medication_id' =>
                            $validated['medication_id'],

                        'resident_id' =>
                            null,

                        'transaction_type' =>
                            'IN',

                        'quantity' =>
                            (int) $validated['quantity'],

                        'reference' =>
                            'Initial inventory',

                        'performed_by' =>
                            auth()->id(),

                        'transaction_date' =>
                            now(),
                    ]);
                }

                $inventory->load('medication');

                $inventoryAlertService->reconcile(
                    $inventory
                );

                $inventoryExpiryAlertService->reconcile(
                    $inventory
                );

                return $inventory;
            }
        );

        $inventory->load(
            'medication'
        );

        return response()->json([
            'message' =>
                'Medicine inventory created successfully.',

            'inventory' =>
                $inventory,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Inventory Details
    |--------------------------------------------------------------------------
    |
    | Quantity is intentionally not editable here.
    | Stock must be changed through stockAdjustment().
    |
    */

    public function update(
        Request $request,
        $id,
        InventoryAlertService $inventoryAlertService,
        InventoryExpiryAlertService $inventoryExpiryAlertService
    ) {
        $validated =
            $request->validate([
                'minimum_stock' =>
                    'sometimes|required|integer|min:0',

                'expiry_date' =>
                    'nullable|date',

                'location' =>
                    'nullable|string|max:255',
            ]);

        $inventory = DB::transaction(
            function () use (
                $id,
                $validated,
                $inventoryAlertService,
                $inventoryExpiryAlertService
            ) {
                $inventory =
                    MedicineInventory::where(
                        'id',
                        $id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $inventory->update(
                    $validated
                );

                $inventory->load(
                    'medication'
                );

                $inventoryAlertService->reconcile(
                    $inventory
                );

                $inventoryExpiryAlertService->reconcile(
                    $inventory
                );

                return $inventory->fresh(
                    'medication'
                );
            }
        );

        return response()->json([
            'message' =>
                'Medicine inventory updated successfully.',

            'inventory' =>
                $inventory,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Stock Adjustment
    |--------------------------------------------------------------------------
    */

    public function stockAdjustment(
        Request $request,
        $id,
        InventoryAlertService $inventoryAlertService,
        InventoryExpiryAlertService $inventoryExpiryAlertService
    ) {
        $validated =
            $request->validate([
                'transaction_type' =>
                    'required|in:STOCK_IN,STOCK_OUT,ADJUSTMENT_IN,ADJUSTMENT_OUT',

                'quantity' =>
                    'required|integer|min:1',

                'reference' =>
                    'nullable|string|max:255',
            ]);

        $result = DB::transaction(
            function () use (
                $id,
                $validated,
                $inventoryAlertService,
                $inventoryExpiryAlertService
            ) {
                $inventory =
                    MedicineInventory::where(
                        'id',
                        $id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $quantity =
                    (int) $validated['quantity'];

                $isOutgoing =
                    in_array(
                        $validated[
                            'transaction_type'
                        ],
                        [
                            'STOCK_OUT',
                            'ADJUSTMENT_OUT',
                        ],
                        true
                    );

                $databaseType =
                    $isOutgoing
                        ? 'OUT'
                        : 'IN';

                if (
                    $isOutgoing
                    &&
                    (int) $inventory->quantity
                    < $quantity
                ) {
                    abort(
                        422,
                        'Insufficient medicine stock.'
                    );
                }

                if ($isOutgoing) {
                    $inventory->quantity =
                        (int) $inventory->quantity
                        - $quantity;
                } else {
                    $inventory->quantity =
                        (int) $inventory->quantity
                        + $quantity;
                }

                $inventory->save();

                $operationLabel =
                    match (
                        $validated[
                            'transaction_type'
                        ]
                    ) {
                        'STOCK_IN' =>
                            'Stock In',

                        'STOCK_OUT' =>
                            'Stock Out',

                        'ADJUSTMENT_IN' =>
                            'Adjustment In',

                        'ADJUSTMENT_OUT' =>
                            'Adjustment Out',

                        default =>
                            'Stock Movement',
                    };

                $reference =
                    $operationLabel;

                if (
                    !empty(
                        $validated['reference']
                    )
                ) {
                    $reference .=
                        ' - ' .
                        $validated['reference'];
                }

                $transaction =
                    MedicineTransaction::create([
                        'medication_id' =>
                            $inventory->medication_id,

                        'resident_id' =>
                            null,

                        'transaction_type' =>
                            $databaseType,

                        'quantity' =>
                            $quantity,

                        'reference' =>
                            $reference,

                        'performed_by' =>
                            auth()->id(),

                        'transaction_date' =>
                            now(),
                    ]);

                $inventory->load(
                    'medication'
                );

                $alertResult =
                    $inventoryAlertService->reconcile(
                        $inventory
                    );

                $expiryAlertResult =
                    $inventoryExpiryAlertService->reconcile(
                        $inventory
                    );

                return [
                    'inventory' =>
                        $inventory->fresh('medication'),

                    'transaction' =>
                        $transaction,

                    'inventory_alert' =>
                        $alertResult,

                    'expiry_alert' =>
                        $expiryAlertResult,
                ];
            }
        );

        return response()->json([
            'message' =>
                'Medicine stock updated successfully.',

            'inventory' =>
                $result['inventory'],

            'transaction' =>
                $result['transaction'],

            'inventory_alert' =>
                $result['inventory_alert'],

            'expiry_alert' =>
                $result['expiry_alert'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Transaction History
    |--------------------------------------------------------------------------
    */

    public function transactions($id)
    {
        $inventory =
            MedicineInventory::findOrFail(
                $id
            );

        $transactions =
            MedicineTransaction::with([
                'resident:id,full_name',
                'performedBy:id,full_name',
            ])
                ->where(
                    'medication_id',
                    $inventory->medication_id
                )
                ->orderByDesc(
                    'transaction_date'
                )
                ->orderByDesc('id')
                ->get();

        return response()->json([
            'inventory' =>
                $inventory->load(
                    'medication'
                ),

            'transactions' =>
                $transactions,
        ]);
    }
}
