import { useState } from "react";
import Sidebar from "./Sidebar";
import AdminHeader from "../components/AdminHeader";

function AdminLayout({ children }) {
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    return (
        <div className="min-h-screen w-full bg-slate-100">
            {/* Fixed desktop sidebar: always full viewport height */}
            <div className="
                fixed
                inset-y-0
                left-0
                z-40
                hidden
                w-[285px]
                lg:block
            ">
                <Sidebar />
            </div>

            {/* Mobile sidebar */}
            {mobileMenuOpen && (
                <div className="fixed inset-0 z-50 lg:hidden">
                    <button
                        type="button"
                        aria-label="Close navigation"
                        onClick={() => setMobileMenuOpen(false)}
                        className="absolute inset-0 bg-slate-950/50"
                    />

                    <div className="
                        relative
                        h-screen
                        w-[285px]
                        max-w-[86vw]
                        shadow-2xl
                    ">
                        <Sidebar
                            onNavigate={() => setMobileMenuOpen(false)}
                        />
                    </div>
                </div>
            )}

            {/* Main application area */}
            <div className="
                min-h-screen
                w-full
                lg:pl-[285px]
            ">
                <main className="min-h-screen min-w-0 bg-slate-100">
                    <div className="
                        sticky
                        top-0
                        z-30
                        border-b
                        border-slate-200
                        bg-white/95
                        backdrop-blur
                    ">
                        <div className="
                            flex
                            min-h-[72px]
                            items-center
                            gap-3
                            px-4
                            sm:px-6
                            lg:px-8
                        ">
                            <button
                                type="button"
                                onClick={() => setMobileMenuOpen(true)}
                                className="
                                    inline-flex
                                    h-10
                                    w-10
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-xl
                                    border
                                    border-slate-200
                                    bg-white
                                    text-xl
                                    text-slate-700
                                    shadow-sm
                                    lg:hidden
                                "
                                aria-label="Open navigation"
                            >
                                ☰
                            </button>

                            <div className="min-w-0 flex-1">
                                <AdminHeader />
                            </div>
                        </div>
                    </div>

                    <div className="
                        w-full
                        min-w-0
                        px-3
                        py-4
                        sm:px-5
                        sm:py-5
                        md:px-6
                        lg:px-8
                        lg:py-7
                        2xl:px-10
                    ">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}

export default AdminLayout;