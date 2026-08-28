import { useState } from "react";
import Sidebar from "./Sidebar";
import AdminHeader from "../components/AdminHeader";

function AdminLayout({ children }) {
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    return (
        <div className="min-h-screen w-full min-w-0 bg-slate-100">
            {/* =====================================================
                DESKTOP SIDEBAR
               ===================================================== */}
            <div
                className="
                    fixed
                    inset-y-0
                    left-0
                    z-40
                    hidden
                    w-[285px]
                    lg:block
                "
            >
                <Sidebar />
            </div>

            {/* =====================================================
                MOBILE SIDEBAR OVERLAY
               ===================================================== */}
            {mobileMenuOpen && (
                <div className="fixed inset-0 z-50 lg:hidden">
                    <button
                        type="button"
                        aria-label="Close navigation"
                        onClick={() => setMobileMenuOpen(false)}
                        className="
                            absolute
                            inset-0
                            h-full
                            w-full
                            bg-slate-950/50
                        "
                    />

                    <div
                        className="
                            relative
                            h-screen
                            w-[285px]
                            max-w-[86vw]
                            shadow-2xl
                        "
                    >
                        <Sidebar
                            onNavigate={() =>
                                setMobileMenuOpen(false)
                            }
                        />
                    </div>
                </div>
            )}

            {/* =====================================================
                MOBILE MENU BUTTON
                Floating so it does not consume page width.
               ===================================================== */}
            <button
                type="button"
                onClick={() => setMobileMenuOpen(true)}
                aria-label="Open navigation"
                className="
                    fixed
                    left-2
                    top-24
                    z-40
                    inline-flex
                    h-10
                    w-10
                    items-center
                    justify-center
                    rounded-r-xl
                    border
                    border-l-0
                    border-slate-200
                    bg-white
                    text-xl
                    text-slate-700
                    shadow-md
                    transition
                    hover:bg-slate-50
                    lg:hidden
                "
            >
                ☰
            </button>

            {/* =====================================================
                APPLICATION AREA
               ===================================================== */}
            <div
                className="
                    min-h-screen
                    w-full
                    min-w-0
                    lg:pl-[285px]
                "
            >
                <main
                    className="
                        min-h-screen
                        w-full
                        min-w-0
                        overflow-x-hidden
                        bg-slate-100
                    "
                >
                    {/* =================================================
                        SHARED HEADER
                       ================================================= */}
                    <div
                        className="
                            w-full
                            min-w-0
                            border-b
                            border-slate-200
                            bg-white/95
                            backdrop-blur
                        "
                    >
                        <div
                            className="
                                w-full
                                min-w-0
                                px-3
                                pt-3
                                sm:px-5
                                sm:pt-5
                                md:px-6
                                lg:px-8
                                lg:pt-6
                                2xl:px-10
                            "
                        >
                            <AdminHeader />
                        </div>
                    </div>

                    {/* =================================================
                        PAGE CONTENT
                       ================================================= */}
                    <div
                        className="
                            w-full
                            min-w-0
                            max-w-full
                            px-3
                            py-4
                            sm:px-5
                            sm:py-5
                            md:px-6
                            lg:px-8
                            lg:py-7
                            2xl:px-10
                        "
                    >
                        <div className="w-full min-w-0 max-w-full">
                            {children}
                        </div>
                    </div>
                </main>
            </div>
        </div>
    );
}

export default AdminLayout;