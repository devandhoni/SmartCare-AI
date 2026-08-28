import { useNavigate } from "react-router-dom";

function AdminHeader() {
    const navigate = useNavigate();

    let user = null;

    try {
        user = JSON.parse(localStorage.getItem("user"));
    } catch {
        user = null;
    }

    const logout = () => {
        localStorage.removeItem("token");
        localStorage.removeItem("user");

        navigate("/");
    };

    const displayName =
        user?.full_name ||
        user?.name ||
        "User";

    const role =
        user?.role ||
        "User";

    return (
        <header
            className="
                mb-4
                w-full
                min-w-0
                rounded-xl
                bg-white
                p-4
                shadow
                sm:mb-5
                sm:p-5
                lg:mb-6
                lg:p-6
            "
        >
            <div
                className="
                    flex
                    min-w-0
                    flex-col
                    gap-4
                    md:flex-row
                    md:items-center
                    md:justify-between
                "
            >
                {/* USER / TITLE */}
                <div className="min-w-0">
                    <h1
                        className="
                            text-xl
                            font-bold
                            leading-snug
                            text-slate-800
                            sm:text-2xl
                        "
                    >
                        SmartCare AI Command Center
                    </h1>

                    <p
                        className="
                            mt-2
                            break-words
                            text-sm
                            text-slate-500
                            sm:text-base
                        "
                    >
                        Welcome, {displayName}
                    </p>

                    <span
                        className="
                            mt-2
                            inline-flex
                            rounded-full
                            bg-blue-100
                            px-3
                            py-1
                            text-sm
                            font-medium
                            text-blue-700
                        "
                    >
                        {role}
                    </span>
                </div>

                {/* STATUS / LOGOUT */}
                <div
                    className="
                        flex
                        min-w-0
                        items-end
                        justify-between
                        gap-3
                        border-t
                        border-slate-100
                        pt-4
                        md:shrink-0
                        md:items-center
                        md:justify-end
                        md:border-t-0
                        md:pt-0
                    "
                >
                    <div className="min-w-0 md:text-right">
                        <p className="text-xs text-slate-500 sm:text-sm">
                            System Status
                        </p>

                        <p
                            className="
                                mt-0.5
                                text-xs
                                font-bold
                                text-orange-600
                                sm:text-sm
                            "
                        >
                            ATTENTION REQUIRED
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={logout}
                        className="
                            inline-flex
                            shrink-0
                            items-center
                            justify-center
                            rounded-lg
                            bg-red-600
                            px-4
                            py-2
                            text-sm
                            font-semibold
                            text-white
                            transition
                            hover:bg-red-700
                            focus:outline-none
                            focus:ring-2
                            focus:ring-red-500
                            focus:ring-offset-2
                        "
                    >
                        Logout
                    </button>
                </div>
            </div>
        </header>
    );
}

export default AdminHeader;