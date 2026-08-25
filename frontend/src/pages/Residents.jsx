import { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import api from "../services/api";

function Residents() {
    const [residents, setResidents] = useState([]);
    const [search, setSearch] = useState("");
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        const loadResidents = async () => {
            try {
                setLoading(true);
                setError("");

                const response = await api.get("/residents");
                const payload = response.data;

                let rows = [];

                if (Array.isArray(payload)) {
                    rows = payload;
                } else if (Array.isArray(payload?.residents)) {
                    rows = payload.residents;
                } else if (Array.isArray(payload?.data)) {
                    rows = payload.data;
                } else if (Array.isArray(payload?.residents?.data)) {
                    rows = payload.residents.data;
                }

                setResidents(rows);
            } catch (requestError) {
                console.error("Failed to load residents:", requestError);
                setError(
                    requestError.response?.data?.message ||
                    "Unable to load residents. Please try again."
                );
            } finally {
                setLoading(false);
            }
        };

        loadResidents();
    }, []);

    const visibleResidents = useMemo(() => {
        const query = search.trim().toLowerCase();

        if (!query) {
            return residents;
        }

        return residents.filter((resident) => {
            const name = String(
                resident.full_name ||
                resident.name ||
                ""
            ).toLowerCase();

            const room = String(
                resident.room_number ||
                resident.room ||
                ""
            ).toLowerCase();

            return name.includes(query) || room.includes(query);
        });
    }, [residents, search]);

    return (
        <div className="w-full min-w-0 space-y-5 sm:space-y-6">
            <section className="
                overflow-hidden
                rounded-2xl
                border
                border-slate-200
                bg-white
                shadow-sm
            ">
                <div className="
                    flex
                    flex-col
                    gap-5
                    p-5
                    sm:p-6
                    lg:flex-row
                    lg:items-center
                    lg:justify-between
                ">
                    <div>
                        <p className="text-sm font-semibold text-blue-600">
                            Residents
                        </p>

                        <h1 className="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">
                            Current Residents
                        </h1>

                        <p className="mt-2 text-sm text-slate-500">
                            Select a resident to view their full care record.
                        </p>
                    </div>

                    <div className="
                        grid
                        w-full
                        gap-3
                        sm:grid-cols-[140px_minmax(0,1fr)]
                        lg:max-w-[620px]
                    ">
                        <div className="
                            rounded-xl
                            bg-slate-50
                            px-4
                            py-3
                        ">
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Current
                            </p>
                            <p className="mt-1 text-2xl font-bold text-slate-900">
                                {residents.length}
                            </p>
                        </div>

                        <div>
                            <label className="mb-2 block text-sm font-semibold text-slate-700">
                                Find Resident
                            </label>

                            <input
                                type="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search by name or room..."
                                className="
                                    w-full
                                    rounded-xl
                                    border
                                    border-slate-300
                                    bg-white
                                    px-4
                                    py-3
                                    outline-none
                                    transition
                                    focus:border-blue-500
                                    focus:ring-4
                                    focus:ring-blue-100
                                "
                            />
                        </div>
                    </div>
                </div>
            </section>

            {error && (
                <div className="
                    rounded-2xl
                    border
                    border-red-200
                    bg-red-50
                    px-5
                    py-4
                    text-sm
                    font-medium
                    text-red-700
                ">
                    {error}
                </div>
            )}

            <section className="
                overflow-hidden
                rounded-2xl
                border
                border-slate-200
                bg-white
                shadow-sm
            ">
                {loading ? (
                    <div className="p-8 text-center text-sm text-slate-500">
                        Loading residents...
                    </div>
                ) : visibleResidents.length === 0 ? (
                    <div className="p-8 text-center">
                        <div className="
                            mx-auto
                            flex
                            h-12
                            w-12
                            items-center
                            justify-center
                            rounded-full
                            bg-slate-100
                            text-xl
                        ">
                            👥
                        </div>

                        <p className="mt-4 font-semibold text-slate-700">
                            No residents found
                        </p>

                        <p className="mt-1 text-sm text-slate-500">
                            Try another name or room number.
                        </p>
                    </div>
                ) : (
                    <>
                        {/* Mobile / tablet */}
                        <div className="divide-y divide-slate-100 lg:hidden">
                            {visibleResidents.map((resident) => {
                                const name =
                                    resident.full_name ||
                                    resident.name ||
                                    "Unnamed Resident";

                                const room =
                                    resident.room_number ||
                                    resident.room ||
                                    "Room not assigned";

                                return (
                                    <Link
                                        key={resident.id}
                                        to={`/residents/${resident.id}`}
                                        className="
                                            block
                                            p-5
                                            transition
                                            hover:bg-slate-50
                                        "
                                    >
                                        <div className="flex items-center gap-4">
                                            <div className="
                                                flex
                                                h-12
                                                w-12
                                                shrink-0
                                                items-center
                                                justify-center
                                                rounded-full
                                                bg-blue-100
                                                font-bold
                                                text-blue-700
                                            ">
                                                {name
                                                    .split(" ")
                                                    .filter(Boolean)
                                                    .map((part) => part[0])
                                                    .slice(0, 2)
                                                    .join("")
                                                    .toUpperCase()}
                                            </div>

                                            <div className="min-w-0 flex-1">
                                                <p className="break-words font-bold text-slate-900">
                                                    {name}
                                                </p>

                                                <p className="mt-1 text-sm text-slate-500">
                                                    {room}
                                                    {resident.gender
                                                        ? ` • ${resident.gender}`
                                                        : ""}
                                                </p>
                                            </div>

                                            <span className="shrink-0 text-blue-600">
                                                →
                                            </span>
                                        </div>
                                    </Link>
                                );
                            })}
                        </div>

                        {/* Desktop */}
                        <div className="hidden lg:block">
                            <div className="
                                grid
                                grid-cols-[minmax(260px,1.5fr)_minmax(140px,.7fr)_minmax(120px,.6fr)_140px]
                                gap-4
                                border-b
                                border-slate-200
                                bg-slate-50
                                px-6
                                py-4
                                text-xs
                                font-semibold
                                uppercase
                                tracking-wide
                                text-slate-500
                            ">
                                <div>Resident</div>
                                <div>Room</div>
                                <div>Status</div>
                                <div className="text-right">Action</div>
                            </div>

                            <div className="divide-y divide-slate-100">
                                {visibleResidents.map((resident) => {
                                    const name =
                                        resident.full_name ||
                                        resident.name ||
                                        "Unnamed Resident";

                                    const room =
                                        resident.room_number ||
                                        resident.room ||
                                        "Room not assigned";

                                    return (
                                        <div
                                            key={resident.id}
                                            className="
                                                grid
                                                grid-cols-[minmax(260px,1.5fr)_minmax(140px,.7fr)_minmax(120px,.6fr)_140px]
                                                items-center
                                                gap-4
                                                px-6
                                                py-5
                                                transition
                                                hover:bg-slate-50
                                            "
                                        >
                                            <div className="flex min-w-0 items-center gap-4">
                                                <div className="
                                                    flex
                                                    h-11
                                                    w-11
                                                    shrink-0
                                                    items-center
                                                    justify-center
                                                    rounded-full
                                                    bg-blue-100
                                                    text-sm
                                                    font-bold
                                                    text-blue-700
                                                ">
                                                    {name
                                                        .split(" ")
                                                        .filter(Boolean)
                                                        .map((part) => part[0])
                                                        .slice(0, 2)
                                                        .join("")
                                                        .toUpperCase()}
                                                </div>

                                                <div className="min-w-0">
                                                    <p className="truncate font-bold text-slate-900">
                                                        {name}
                                                    </p>

                                                    <p className="mt-1 text-sm text-slate-500">
                                                        {resident.gender || "Gender not recorded"}
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="text-sm font-medium text-slate-700">
                                                {room}
                                            </div>

                                            <div>
                                                <span className="
                                                    inline-flex
                                                    rounded-full
                                                    bg-blue-50
                                                    px-3
                                                    py-1
                                                    text-xs
                                                    font-semibold
                                                    text-blue-700
                                                ">
                                                    {resident.status || "Active"}
                                                </span>
                                            </div>

                                            <div className="text-right">
                                                <Link
                                                    to={`/residents/${resident.id}`}
                                                    className="
                                                        inline-flex
                                                        rounded-xl
                                                        border
                                                        border-slate-300
                                                        bg-white
                                                        px-4
                                                        py-2
                                                        text-sm
                                                        font-semibold
                                                        text-slate-700
                                                        transition
                                                        hover:border-blue-300
                                                        hover:bg-blue-50
                                                        hover:text-blue-700
                                                    "
                                                >
                                                    View Resident
                                                </Link>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </>
                )}
            </section>
        </div>
    );
}

export default Residents;