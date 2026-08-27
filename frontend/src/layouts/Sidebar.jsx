import { NavLink } from "react-router-dom";

const menuSections = [
    {
        title: null,
        items: [
            { name: "Today", path: "/today", icon: "☀️" },
            { name: "Residents", path: "/residents", icon: "👥" },
            { name: "Medication", path: "/medication", icon: "💊" },
            { name: "Nurse Tasks", path: "/tasks", icon: "✅" },
            { name: "Care Records", path: "/care-records", icon: "🩺" },
            { name: "Admissions", path: "/admissions", icon: "➕" },
            { name: "Discharges", path: "/discharges", icon: "↗" },
            { name: "Inventory", path: "/inventory", icon: "📦" },
            { name: "Monthly Glucose", path: "/monthly-glucose", icon: "🩸" },
            { name: "Visitors", path: "/visitors", icon: "🚪" },
            { name: "Home Leave", path: "/home-leave", icon: "🏡" },
            { name: "Parcels", path: "/parcels", icon: "📬" },
        ],
    },
    {
        title: "Management",
        items: [
            { name: "Reports", path: "/reports", icon: "📊" },
            { name: "AI Intelligence", path: "/ai-intelligence", icon: "✦" },
            { name: "Management Dashboard", path: "/admin/dashboard", icon: "⚙️" },
        ],
    },
];

function Sidebar({ onNavigate }) {
    return (
        <aside className="
            flex
            h-screen
            w-[285px]
            shrink-0
            flex-col
            overflow-hidden
            bg-slate-950
            text-white
        ">
            <div className="shrink-0 border-b border-white/10 px-6 py-6">
                <div className="flex items-center gap-3">
                    <div className="
                        flex
                        h-11
                        w-11
                        shrink-0
                        items-center
                        justify-center
                        rounded-xl
                        bg-blue-600
                        text-lg
                        font-bold
                    ">
                        S
                    </div>

                    <div className="min-w-0">
                        <h1 className="truncate text-lg font-bold tracking-tight">
                            SmartCare-AI
                        </h1>
                        <p className="mt-0.5 text-xs text-slate-400">
                            Care Management
                        </p>
                    </div>
                </div>
            </div>

            <nav className="
                min-h-0
                flex-1
                overflow-y-auto
                overscroll-contain
                px-4
                py-5
                [scrollbar-width:thin]
                [scrollbar-color:#64748b_#020617]
            ">
                {menuSections.map((section, sectionIndex) => (
                    <div
                        key={section.title || `main-${sectionIndex}`}
                        className={sectionIndex > 0 ? "mt-7" : ""}
                    >
                        {section.title && (
                            <p className="
                                mb-3
                                px-3
                                text-xs
                                font-semibold
                                uppercase
                                tracking-[0.12em]
                                text-slate-500
                            ">
                                {section.title}
                            </p>
                        )}

                        <div className="space-y-1">
                            {section.items.map((item) => (
                                <NavLink
                                    key={item.name}
                                    to={item.path}
                                    onClick={onNavigate}
                                    className={({ isActive }) =>
                                        `
                                            flex
                                            items-center
                                            gap-3
                                            rounded-xl
                                            px-3
                                            py-3
                                            text-sm
                                            font-semibold
                                            transition
                                            ${
                                                isActive
                                                    ? "bg-blue-600 text-white shadow-sm"
                                                    : "text-slate-200 hover:bg-white/10 hover:text-white"
                                            }
                                        `
                                    }
                                >
                                    <span className="w-6 shrink-0 text-center">
                                        {item.icon}
                                    </span>
                                    <span className="min-w-0 truncate">
                                        {item.name}
                                    </span>
                                </NavLink>
                            ))}
                        </div>
                    </div>
                ))}
            </nav>

            <div className="
                shrink-0
                border-t
                border-white/10
                bg-slate-950
                px-6
                py-4
            ">
                <p className="text-xs text-slate-500">
                    © 2026 SmartCare-AI
                </p>
            </div>
        </aside>
    );
}

export default Sidebar;