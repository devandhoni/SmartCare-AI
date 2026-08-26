import { useEffect, useMemo, useState } from "react";
import api from "../services/api";

const EMPTY_MEDICINE = {
    medicine_name: "",
    category: "",
    dosage: "",
    unit: "",
    supplier: "",
};

const EMPTY_INVENTORY = {
    medication_id: "",
    quantity: "",
    minimum_stock: "",
    expiry_date: "",
    location: "",
};

const EMPTY_ADJUSTMENT = {
    transaction_type: "STOCK_IN",
    quantity: "",
    reference: "",
};

function Inventory() {
    const [tab, setTab] = useState("medicines");
    const [medications, setMedications] = useState([]);
    const [inventory, setInventory] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState("");
    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");

    const [medicineForm, setMedicineForm] = useState(EMPTY_MEDICINE);
    const [editingMedicine, setEditingMedicine] = useState(null);
    const [showMedicineForm, setShowMedicineForm] = useState(false);

    const [inventoryForm, setInventoryForm] = useState(EMPTY_INVENTORY);
    const [showInventoryForm, setShowInventoryForm] = useState(false);
    const [editingInventory, setEditingInventory] = useState(null);

    const [adjustmentInventory, setAdjustmentInventory] = useState(null);
    const [adjustmentForm, setAdjustmentForm] = useState(EMPTY_ADJUSTMENT);

    const [transactionInventory, setTransactionInventory] = useState(null);
    const [transactions, setTransactions] = useState([]);
    const [loadingTransactions, setLoadingTransactions] = useState(false);

    useEffect(() => {
        loadAll();
    }, []);

    const loadAll = async () => {
        setLoading(true);
        setError("");

        try {
            const [medicationResponse, inventoryResponse] = await Promise.all([
                api.get("/medications"),
                api.get("/medicine-inventory"),
            ]);

            const medicineData =
                medicationResponse.data?.data ??
                medicationResponse.data?.medications ??
                medicationResponse.data;

            const inventoryData =
                inventoryResponse.data?.inventory ??
                inventoryResponse.data?.data ??
                inventoryResponse.data;

            setMedications(Array.isArray(medicineData) ? medicineData : []);
            setInventory(Array.isArray(inventoryData) ? inventoryData : []);
        } catch (err) {
            setError(readApiError(err, "Unable to load medicine inventory."));
        } finally {
            setLoading(false);
        }
    };

    const filteredMedications = useMemo(() => {
        const term = search.trim().toLowerCase();
        if (!term) return medications;

        return medications.filter((medicine) =>
            [
                medicine.medicine_name,
                medicine.category,
                medicine.dosage,
                medicine.unit,
                medicine.supplier,
            ]
                .filter(Boolean)
                .join(" ")
                .toLowerCase()
                .includes(term)
        );
    }, [medications, search]);

    const filteredInventory = useMemo(() => {
        const term = search.trim().toLowerCase();
        if (!term) return inventory;

        return inventory.filter((item) =>
            [
                item.medication?.medicine_name,
                item.medication?.category,
                item.medication?.dosage,
                item.medication?.unit,
                item.location,
            ]
                .filter(Boolean)
                .join(" ")
                .toLowerCase()
                .includes(term)
        );
    }, [inventory, search]);

    const medicinesWithoutInventory = useMemo(() => {
        const inventoryMedicationIds = new Set(
            inventory.map((item) => String(item.medication_id))
        );

        return medications.filter(
            (medicine) => !inventoryMedicationIds.has(String(medicine.id))
        );
    }, [medications, inventory]);

    const lowStockCount = inventory.filter(
        (item) => Number(item.quantity) <= Number(item.minimum_stock)
    ).length;

    const expiringCount = inventory.filter((item) =>
        isExpiringSoon(item.expiry_date)
    ).length;

    const clearMessages = () => {
        setError("");
        setSuccess("");
    };

    const openAddMedicine = () => {
        clearMessages();
        setEditingMedicine(null);
        setMedicineForm(EMPTY_MEDICINE);
        setShowMedicineForm(true);
    };

    const openEditMedicine = (medicine) => {
        clearMessages();
        setEditingMedicine(medicine);
        setMedicineForm({
            medicine_name: medicine.medicine_name ?? "",
            category: medicine.category ?? "",
            dosage: medicine.dosage ?? "",
            unit: medicine.unit ?? "",
            supplier: medicine.supplier ?? "",
        });
        setShowMedicineForm(true);
    };

    const saveMedicine = async (event) => {
        event.preventDefault();
        clearMessages();

        try {
            const payload = {
                medicine_name: medicineForm.medicine_name.trim(),
                category: emptyToNull(medicineForm.category),
                dosage: emptyToNull(medicineForm.dosage),
                unit: emptyToNull(medicineForm.unit),
                supplier: emptyToNull(medicineForm.supplier),
            };

            if (editingMedicine) {
                await api.put(`/medications/${editingMedicine.id}`, payload);
                setSuccess("Medicine updated successfully.");
            } else {
                await api.post("/medications", payload);
                setSuccess("Medicine added to Medicine Master.");
            }

            setShowMedicineForm(false);
            setEditingMedicine(null);
            setMedicineForm(EMPTY_MEDICINE);
            await loadAll();
        } catch (err) {
            setError(readApiError(err, "Unable to save medicine."));
        }
    };

    const deleteMedicine = async (medicine) => {
        if (
            !window.confirm(
                `Delete ${medicine.medicine_name}${medicine.dosage ? ` ${medicine.dosage}` : ""}?`
            )
        ) {
            return;
        }

        clearMessages();

        try {
            await api.delete(`/medications/${medicine.id}`);
            setSuccess("Medicine deleted.");
            await loadAll();
        } catch (err) {
            setError(
                readApiError(
                    err,
                    "Unable to delete medicine. It may already be assigned to residents."
                )
            );
        }
    };

    const openAddInventory = () => {
        clearMessages();
        setEditingInventory(null);
        setInventoryForm(EMPTY_INVENTORY);
        setShowInventoryForm(true);
    };

    const openEditInventory = (item) => {
        clearMessages();
        setEditingInventory(item);
        setInventoryForm({
            medication_id: String(item.medication_id),
            quantity: String(item.quantity ?? ""),
            minimum_stock: String(item.minimum_stock ?? ""),
            expiry_date: item.expiry_date ? String(item.expiry_date).slice(0, 10) : "",
            location: item.location ?? "",
        });
        setShowInventoryForm(true);
    };

    const saveInventory = async (event) => {
        event.preventDefault();
        clearMessages();

        try {
            if (editingInventory) {
                await api.put(`/medicine-inventory/${editingInventory.id}`, {
                    minimum_stock:
                        inventoryForm.minimum_stock === ""
                            ? 0
                            : Number(inventoryForm.minimum_stock),
                    expiry_date: emptyToNull(inventoryForm.expiry_date),
                    location: emptyToNull(inventoryForm.location),
                });

                setSuccess("Inventory details updated.");
            } else {
                await api.post("/medicine-inventory", {
                    medication_id: Number(inventoryForm.medication_id),
                    quantity:
                        inventoryForm.quantity === ""
                            ? 0
                            : Number(inventoryForm.quantity),
                    minimum_stock:
                        inventoryForm.minimum_stock === ""
                            ? 0
                            : Number(inventoryForm.minimum_stock),
                    expiry_date: emptyToNull(inventoryForm.expiry_date),
                    location: emptyToNull(inventoryForm.location),
                });

                setSuccess("Inventory record created.");
            }

            setShowInventoryForm(false);
            setEditingInventory(null);
            setInventoryForm(EMPTY_INVENTORY);
            await loadAll();
        } catch (err) {
            setError(readApiError(err, "Unable to save inventory."));
        }
    };

    const openAdjustment = (item) => {
        clearMessages();
        setAdjustmentInventory(item);
        setAdjustmentForm(EMPTY_ADJUSTMENT);
    };

    const saveAdjustment = async (event) => {
        event.preventDefault();
        clearMessages();

        try {
            await api.post(
                `/medicine-inventory/${adjustmentInventory.id}/stock-adjustment`,
                {
                    transaction_type: adjustmentForm.transaction_type,
                    quantity: Number(adjustmentForm.quantity),
                    reference: emptyToNull(adjustmentForm.reference),
                }
            );

            setSuccess("Stock updated and transaction recorded.");
            setAdjustmentInventory(null);
            setAdjustmentForm(EMPTY_ADJUSTMENT);
            await loadAll();
        } catch (err) {
            setError(readApiError(err, "Unable to adjust medicine stock."));
        }
    };

    const openTransactions = async (item) => {
        clearMessages();
        setTransactionInventory(item);
        setTransactions([]);
        setLoadingTransactions(true);

        try {
            const response = await api.get(
                `/medicine-inventory/${item.id}/transactions`
            );

            setTransactions(
                Array.isArray(response.data?.transactions)
                    ? response.data.transactions
                    : []
            );
        } catch (err) {
            setError(readApiError(err, "Unable to load stock transactions."));
        } finally {
            setLoadingTransactions(false);
        }
    };

    return (
        <div className="mx-auto max-w-7xl space-y-6">
            <div className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">
                        Care Operations
                    </p>
                    <h1 className="mt-1 text-3xl font-bold text-slate-800">
                        Medicine Inventory
                    </h1>
                    <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                        Maintain the medicine catalogue, monitor stock levels and
                        record every stock movement in one place.
                    </p>
                </div>

                <div className="grid grid-cols-3 gap-2 sm:min-w-[430px]">
                    <Metric label="Medicines" value={medications.length} />
                    <Metric label="Low Stock" value={lowStockCount} tone={lowStockCount ? "warning" : "normal"} />
                    <Metric label="Expiring" value={expiringCount} tone={expiringCount ? "warning" : "normal"} />
                </div>
            </div>

            {error && (
                <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    {error}
                </div>
            )}

            {success && (
                <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {success}
                </div>
            )}

            <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="flex flex-col gap-4 border-b border-slate-200 p-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex rounded-xl bg-slate-100 p-1">
                        <TabButton
                            active={tab === "medicines"}
                            onClick={() => {
                                setTab("medicines");
                                setSearch("");
                            }}
                        >
                            Medicine Master
                        </TabButton>
                        <TabButton
                            active={tab === "stock"}
                            onClick={() => {
                                setTab("stock");
                                setSearch("");
                            }}
                        >
                            Stock
                        </TabButton>
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row">
                        <input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={
                                tab === "medicines"
                                    ? "Search medicine, dosage, category..."
                                    : "Search stock or location..."
                            }
                            className="min-w-0 rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 sm:w-80"
                        />

                        {tab === "medicines" ? (
                            <PrimaryButton onClick={openAddMedicine}>
                                + Add Medicine
                            </PrimaryButton>
                        ) : (
                            <PrimaryButton
                                onClick={openAddInventory}
                                disabled={medicinesWithoutInventory.length === 0}
                            >
                                + Add Stock Record
                            </PrimaryButton>
                        )}
                    </div>
                </div>

                {loading ? (
                    <div className="p-10 text-center text-sm text-slate-500">
                        Loading medicine inventory...
                    </div>
                ) : tab === "medicines" ? (
                    <MedicineMaster
                        medications={filteredMedications}
                        inventory={inventory}
                        onEdit={openEditMedicine}
                        onDelete={deleteMedicine}
                    />
                ) : (
                    <StockTable
                        inventory={filteredInventory}
                        onEdit={openEditInventory}
                        onAdjust={openAdjustment}
                        onTransactions={openTransactions}
                    />
                )}
            </div>

            {showMedicineForm && (
                <Modal
                    title={editingMedicine ? "Edit Medicine" : "Add Medicine"}
                    description="Medicine Master entries are reused across all residents. Do not create a duplicate medicine for each resident."
                    onClose={() => setShowMedicineForm(false)}
                >
                    <form onSubmit={saveMedicine} className="space-y-5">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field
                                label="Medicine Name"
                                required
                                value={medicineForm.medicine_name}
                                onChange={(value) =>
                                    setMedicineForm((current) => ({
                                        ...current,
                                        medicine_name: value,
                                    }))
                                }
                                placeholder="Example: Metformin"
                            />
                            <Field
                                label="Category"
                                value={medicineForm.category}
                                onChange={(value) =>
                                    setMedicineForm((current) => ({
                                        ...current,
                                        category: value,
                                    }))
                                }
                                placeholder="Example: Diabetes"
                            />
                            <Field
                                label="Dosage / Strength"
                                value={medicineForm.dosage}
                                onChange={(value) =>
                                    setMedicineForm((current) => ({
                                        ...current,
                                        dosage: value,
                                    }))
                                }
                                placeholder="Example: 500mg"
                            />
                            <Field
                                label="Unit / Form"
                                value={medicineForm.unit}
                                onChange={(value) =>
                                    setMedicineForm((current) => ({
                                        ...current,
                                        unit: value,
                                    }))
                                }
                                placeholder="Example: Tablet"
                            />
                            <div className="sm:col-span-2">
                                <Field
                                    label="Supplier"
                                    value={medicineForm.supplier}
                                    onChange={(value) =>
                                        setMedicineForm((current) => ({
                                            ...current,
                                            supplier: value,
                                        }))
                                    }
                                    placeholder="Optional supplier"
                                />
                            </div>
                        </div>

                        <ModalActions
                            onCancel={() => setShowMedicineForm(false)}
                            submitLabel={editingMedicine ? "Save Changes" : "Add Medicine"}
                        />
                    </form>
                </Modal>
            )}

            {showInventoryForm && (
                <Modal
                    title={editingInventory ? "Edit Stock Details" : "Create Stock Record"}
                    description={
                        editingInventory
                            ? "Quantity is changed separately through Stock Adjustment so every movement is audited."
                            : "Connect a Medicine Master item to its stock information."
                    }
                    onClose={() => setShowInventoryForm(false)}
                >
                    <form onSubmit={saveInventory} className="space-y-5">
                        <div className="grid gap-4 sm:grid-cols-2">
                            {!editingInventory && (
                                <label className="space-y-2 sm:col-span-2">
                                    <span className="text-sm font-semibold text-slate-700">
                                        Medicine *
                                    </span>
                                    <select
                                        required
                                        value={inventoryForm.medication_id}
                                        onChange={(event) =>
                                            setInventoryForm((current) => ({
                                                ...current,
                                                medication_id: event.target.value,
                                            }))
                                        }
                                        className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                    >
                                        <option value="">Select medicine</option>
                                        {medicinesWithoutInventory.map((medicine) => (
                                            <option key={medicine.id} value={medicine.id}>
                                                {medicine.medicine_name}
                                                {medicine.dosage ? ` · ${medicine.dosage}` : ""}
                                                {medicine.unit ? ` · ${medicine.unit}` : ""}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                            )}

                            {!editingInventory && (
                                <Field
                                    label="Opening Quantity"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    required
                                    value={inventoryForm.quantity}
                                    onChange={(value) =>
                                        setInventoryForm((current) => ({
                                            ...current,
                                            quantity: value,
                                        }))
                                    }
                                    placeholder="0"
                                />
                            )}

                            <Field
                                label="Minimum Stock"
                                type="number"
                                min="0"
                                step="0.01"
                                value={inventoryForm.minimum_stock}
                                onChange={(value) =>
                                    setInventoryForm((current) => ({
                                        ...current,
                                        minimum_stock: value,
                                    }))
                                }
                                placeholder="Example: 20"
                            />
                            <Field
                                label="Expiry Date"
                                type="date"
                                value={inventoryForm.expiry_date}
                                onChange={(value) =>
                                    setInventoryForm((current) => ({
                                        ...current,
                                        expiry_date: value,
                                    }))
                                }
                            />
                            <Field
                                label="Location"
                                value={inventoryForm.location}
                                onChange={(value) =>
                                    setInventoryForm((current) => ({
                                        ...current,
                                        location: value,
                                    }))
                                }
                                placeholder="Example: Main Pharmacy"
                            />
                        </div>

                        <ModalActions
                            onCancel={() => setShowInventoryForm(false)}
                            submitLabel={editingInventory ? "Save Details" : "Create Stock Record"}
                        />
                    </form>
                </Modal>
            )}

            {adjustmentInventory && (
                <Modal
                    title="Stock Adjustment"
                    description={`${medicineLabel(adjustmentInventory.medication)} · Current stock: ${formatQuantity(adjustmentInventory.quantity)}`}
                    onClose={() => setAdjustmentInventory(null)}
                >
                    <form onSubmit={saveAdjustment} className="space-y-5">
                        <label className="block space-y-2">
                            <span className="text-sm font-semibold text-slate-700">
                                Transaction Type *
                            </span>
                            <select
                                value={adjustmentForm.transaction_type}
                                onChange={(event) =>
                                    setAdjustmentForm((current) => ({
                                        ...current,
                                        transaction_type: event.target.value,
                                    }))
                                }
                                className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                            >
                                <option value="STOCK_IN">Stock In</option>
                                <option value="STOCK_OUT">Stock Out</option>
                                <option value="ADJUSTMENT_IN">Adjustment In</option>
                                <option value="ADJUSTMENT_OUT">Adjustment Out</option>
                            </select>
                        </label>

                        <Field
                            label="Quantity"
                            type="number"
                            min="0.01"
                            step="0.01"
                            required
                            value={adjustmentForm.quantity}
                            onChange={(value) =>
                                setAdjustmentForm((current) => ({
                                    ...current,
                                    quantity: value,
                                }))
                            }
                            placeholder="Quantity"
                        />

                        <Field
                            label="Reference / Reason"
                            value={adjustmentForm.reference}
                            onChange={(value) =>
                                setAdjustmentForm((current) => ({
                                    ...current,
                                    reference: value,
                                }))
                            }
                            placeholder="Delivery, correction, damaged stock..."
                        />

                        <ModalActions
                            onCancel={() => setAdjustmentInventory(null)}
                            submitLabel="Record Stock Movement"
                        />
                    </form>
                </Modal>
            )}

            {transactionInventory && (
                <Modal
                    title="Stock Transactions"
                    description={medicineLabel(transactionInventory.medication)}
                    onClose={() => setTransactionInventory(null)}
                    wide
                >
                    {loadingTransactions ? (
                        <div className="py-10 text-center text-sm text-slate-500">
                            Loading transactions...
                        </div>
                    ) : transactions.length === 0 ? (
                        <EmptyState
                            title="No transactions yet"
                            description="Stock movements for this medicine will appear here."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-left text-sm">
                                <thead className="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-3 py-3">Date</th>
                                        <th className="px-3 py-3">Type</th>
                                        <th className="px-3 py-3">Quantity</th>
                                        <th className="px-3 py-3">Reference</th>
                                        <th className="px-3 py-3">Resident</th>
                                        <th className="px-3 py-3">Performed By</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {transactions.map((transaction) => (
                                        <tr key={transaction.id}>
                                            <td className="whitespace-nowrap px-3 py-3 text-slate-600">
                                                {formatDateTime(transaction.transaction_date)}
                                            </td>
                                            <td className="px-3 py-3">
                                                <TransactionBadge type={transaction.transaction_type} />
                                            </td>
                                            <td className="px-3 py-3 font-bold text-slate-800">
                                                {formatQuantity(transaction.quantity)}
                                            </td>
                                            <td className="px-3 py-3 text-slate-600">
                                                {transaction.reference || "—"}
                                            </td>
                                            <td className="px-3 py-3 text-slate-600">
                                                {transaction.resident?.full_name || "—"}
                                            </td>
                                            <td className="px-3 py-3 text-slate-600">
                                                {transaction.performed_by?.full_name ||
                                                    transaction.performedBy?.full_name ||
                                                    "—"}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Modal>
            )}
        </div>
    );
}

function MedicineMaster({ medications, inventory, onEdit, onDelete }) {
    if (medications.length === 0) {
        return (
            <EmptyState
                title="No medicines found"
                description="Add medicines to the master catalogue so nurses can select them during admission and medication setup."
            />
        );
    }

    const stockMap = new Map(
        inventory.map((item) => [String(item.medication_id), item])
    );

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full text-left text-sm">
                <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th className="px-5 py-3">Medicine</th>
                        <th className="px-5 py-3">Category</th>
                        <th className="px-5 py-3">Strength</th>
                        <th className="px-5 py-3">Unit / Form</th>
                        <th className="px-5 py-3">Supplier</th>
                        <th className="px-5 py-3">Stock</th>
                        <th className="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {medications.map((medicine) => {
                        const stock = stockMap.get(String(medicine.id));

                        return (
                            <tr key={medicine.id} className="hover:bg-slate-50">
                                <td className="px-5 py-4">
                                    <div className="font-bold text-slate-800">
                                        {medicine.medicine_name}
                                    </div>
                                </td>
                                <td className="px-5 py-4 text-slate-600">
                                    {medicine.category || "—"}
                                </td>
                                <td className="px-5 py-4 text-slate-600">
                                    {medicine.dosage || "—"}
                                </td>
                                <td className="px-5 py-4 text-slate-600">
                                    {medicine.unit || "—"}
                                </td>
                                <td className="px-5 py-4 text-slate-600">
                                    {medicine.supplier || "—"}
                                </td>
                                <td className="px-5 py-4">
                                    {stock ? (
                                        <StockBadge item={stock} />
                                    ) : (
                                        <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                            Not configured
                                        </span>
                                    )}
                                </td>
                                <td className="px-5 py-4">
                                    <div className="flex justify-end gap-2">
                                        <SecondaryButton onClick={() => onEdit(medicine)}>
                                            Edit
                                        </SecondaryButton>
                                        <DangerButton onClick={() => onDelete(medicine)}>
                                            Delete
                                        </DangerButton>
                                    </div>
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}

function StockTable({ inventory, onEdit, onAdjust, onTransactions }) {
    if (inventory.length === 0) {
        return (
            <EmptyState
                title="No stock records found"
                description="Create a stock record for a medicine from the Medicine Master catalogue."
            />
        );
    }

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full text-left text-sm">
                <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th className="px-5 py-3">Medicine</th>
                        <th className="px-5 py-3">Quantity</th>
                        <th className="px-5 py-3">Minimum</th>
                        <th className="px-5 py-3">Expiry</th>
                        <th className="px-5 py-3">Location</th>
                        <th className="px-5 py-3">Status</th>
                        <th className="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {inventory.map((item) => (
                        <tr key={item.id} className="hover:bg-slate-50">
                            <td className="px-5 py-4">
                                <div className="font-bold text-slate-800">
                                    {item.medication?.medicine_name || `Medication #${item.medication_id}`}
                                </div>
                                <div className="mt-1 text-xs text-slate-500">
                                    {[item.medication?.dosage, item.medication?.unit]
                                        .filter(Boolean)
                                        .join(" · ") || "—"}
                                </div>
                            </td>
                            <td className="px-5 py-4 text-lg font-bold text-slate-800">
                                {formatQuantity(item.quantity)}
                            </td>
                            <td className="px-5 py-4 text-slate-600">
                                {formatQuantity(item.minimum_stock)}
                            </td>
                            <td className="px-5 py-4 text-slate-600">
                                {formatDate(item.expiry_date)}
                            </td>
                            <td className="px-5 py-4 text-slate-600">
                                {item.location || "—"}
                            </td>
                            <td className="px-5 py-4">
                                <StockBadge item={item} />
                            </td>
                            <td className="px-5 py-4">
                                <div className="flex flex-wrap justify-end gap-2">
                                    <SecondaryButton onClick={() => onAdjust(item)}>
                                        Adjust Stock
                                    </SecondaryButton>
                                    <SecondaryButton onClick={() => onTransactions(item)}>
                                        History
                                    </SecondaryButton>
                                    <SecondaryButton onClick={() => onEdit(item)}>
                                        Details
                                    </SecondaryButton>
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function Metric({ label, value, tone = "normal" }) {
    return (
        <div
            className={`rounded-xl border px-3 py-3 text-center ${
                tone === "warning"
                    ? "border-amber-200 bg-amber-50"
                    : "border-slate-200 bg-white"
            }`}
        >
            <div className="text-xl font-bold text-slate-800">{value}</div>
            <div className="mt-0.5 text-xs font-semibold text-slate-500">{label}</div>
        </div>
    );
}

function TabButton({ active, onClick, children }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`flex-1 rounded-lg px-4 py-2 text-sm font-bold transition ${
                active
                    ? "bg-white text-blue-700 shadow-sm"
                    : "text-slate-600 hover:text-slate-800"
            }`}
        >
            {children}
        </button>
    );
}

function PrimaryButton({ children, onClick, disabled = false }) {
    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300"
        >
            {children}
        </button>
    );
}

function SecondaryButton({ children, onClick }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50"
        >
            {children}
        </button>
    );
}

function DangerButton({ children, onClick }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className="rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50"
        >
            {children}
        </button>
    );
}

function Field({
    label,
    value,
    onChange,
    type = "text",
    required = false,
    placeholder = "",
    min,
    step,
}) {
    return (
        <label className="block space-y-2">
            <span className="text-sm font-semibold text-slate-700">
                {label}
                {required ? " *" : ""}
            </span>
            <input
                type={type}
                value={value}
                required={required}
                placeholder={placeholder}
                min={min}
                step={step}
                onChange={(event) => onChange(event.target.value)}
                className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
            />
        </label>
    );
}

function Modal({ title, description, onClose, children, wide = false }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
            <div
                className={`max-h-[90vh] w-full overflow-y-auto rounded-2xl bg-white shadow-2xl ${
                    wide ? "max-w-5xl" : "max-w-2xl"
                }`}
            >
                <div className="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white p-5">
                    <div>
                        <h2 className="text-xl font-bold text-slate-800">{title}</h2>
                        {description && (
                            <p className="mt-1 text-sm leading-6 text-slate-500">
                                {description}
                            </p>
                        )}
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-lg px-3 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100"
                    >
                        Close
                    </button>
                </div>
                <div className="p-5">{children}</div>
            </div>
        </div>
    );
}

function ModalActions({ onCancel, submitLabel }) {
    return (
        <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <button
                type="button"
                onClick={onCancel}
                className="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50"
            >
                Cancel
            </button>
            <button
                type="submit"
                className="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-700"
            >
                {submitLabel}
            </button>
        </div>
    );
}

function EmptyState({ title, description }) {
    return (
        <div className="p-10 text-center">
            <div className="font-bold text-slate-700">{title}</div>
            <p className="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">
                {description}
            </p>
        </div>
    );
}

function StockBadge({ item }) {
    const quantity = Number(item.quantity);
    const minimum = Number(item.minimum_stock);
    const expired = isExpired(item.expiry_date);
    const expiring = isExpiringSoon(item.expiry_date);

    if (expired) {
        return (
            <span className="rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-700">
                Expired
            </span>
        );
    }

    if (quantity <= minimum) {
        return (
            <span className="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700">
                Low stock
            </span>
        );
    }

    if (expiring) {
        return (
            <span className="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-bold text-orange-700">
                Expiring soon
            </span>
        );
    }

    return (
        <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">
            In stock
        </span>
    );
}

function TransactionBadge({ type }) {
    const outgoing = ["OUT", "STOCK_OUT", "ADJUSTMENT_OUT"].includes(type);

    return (
        <span
            className={`rounded-full px-2.5 py-1 text-xs font-bold ${
                outgoing
                    ? "bg-red-100 text-red-700"
                    : "bg-emerald-100 text-emerald-700"
            }`}
        >
            {String(type || "").replaceAll("_", " ")}
        </span>
    );
}

function medicineLabel(medicine) {
    if (!medicine) return "Medicine";
    return [medicine.medicine_name, medicine.dosage, medicine.unit]
        .filter(Boolean)
        .join(" · ");
}

function formatQuantity(value) {
    const number = Number(value);
    if (!Number.isFinite(number)) return "0";
    return Number.isInteger(number) ? String(number) : number.toFixed(2);
}

function formatDate(value) {
    if (!value) return "—";
    const date = new Date(`${String(value).slice(0, 10)}T00:00:00`);
    return Number.isNaN(date.getTime())
        ? String(value)
        : date.toLocaleDateString();
}

function formatDateTime(value) {
    if (!value) return "—";
    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? String(value)
        : date.toLocaleString();
}

function isExpired(value) {
    if (!value) return false;
    const expiry = new Date(`${String(value).slice(0, 10)}T23:59:59`);
    return expiry < new Date();
}

function isExpiringSoon(value) {
    if (!value || isExpired(value)) return false;

    const expiry = new Date(`${String(value).slice(0, 10)}T23:59:59`);
    const now = new Date();
    const thirtyDays = 30 * 24 * 60 * 60 * 1000;

    return expiry.getTime() - now.getTime() <= thirtyDays;
}

function emptyToNull(value) {
    if (value === undefined || value === null) return null;
    const text = String(value).trim();
    return text === "" ? null : text;
}

function readApiError(error, fallback) {
    const data = error?.response?.data;

    if (data?.errors) {
        const first = Object.values(data.errors).flat()[0];
        if (first) return first;
    }

    return data?.message || error?.message || fallback;
}

export default Inventory;