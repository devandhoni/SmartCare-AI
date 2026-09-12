import {
    BrowserRouter,
    Routes,
    Route,
    useParams,
}
from "react-router-dom";

import Login from "./pages/Login";

import AdminLayout from "./layouts/AdminLayout";
import ProtectedRoute from "./components/ProtectedRoute";

import Today from "./pages/Today";
import Residents from "./pages/Residents";
import ResidentProfile from "./pages/ResidentProfile";
import Medication from "./pages/Medication";
import FamilyMessages from "./pages/FamilyMessages";
import NurseDashboard from "./pages/NurseDashboard";
import CareRecords from "./pages/CareRecords";

import Admissions from "./pages/Admissions";
import Discharges from "./pages/Discharges";
import Inventory from "./pages/Inventory";
import MonthlyGlucose from "./pages/MonthlyGlucose";
import Visitors from "./pages/Visitors";
import HomeLeave from "./pages/HomeLeave";
import Parcels from "./pages/Parcels";
import WeeklyVitals from "./pages/WeeklyVitals";
import Reports from "./pages/Reports";
import AIIntelligence from "./pages/AIIntelligence";

import AdminDashboard from "./pages/AdminDashboard";

import ClinicalDashboard from "./components/clinical/ClinicalDashboard";
import Billing from "./pages/Billing";


const OPERATIONAL_ROLES = [
    "Administrator",
    "Nurse",
];

const ADMIN_ROLES = [
    "Administrator",
];


function ClinicalDashboardWrapper() {
    const { id } = useParams();

    return (
        <ClinicalDashboard
            residentId={id}
        />
    );
}


function OperationalPage({ children }) {
    return (
        <ProtectedRoute
            allowedRoles={OPERATIONAL_ROLES}
        >
            <AdminLayout>
                {children}
            </AdminLayout>
        </ProtectedRoute>
    );
}


function AdministratorPage({ children }) {
    return (
        <ProtectedRoute
            allowedRoles={ADMIN_ROLES}
        >
            <AdminLayout>
                {children}
            </AdminLayout>
        </ProtectedRoute>
    );
}


function App() {
    return (
        <BrowserRouter>
            <Routes>
                <Route
                    path="/"
                    element={<Login />}
                />

                <Route
                    path="/today"
                    element={
                        <OperationalPage>
                            <Today />
                        </OperationalPage>
                    }
                />

                <Route
                    path="/residents"
                    element={
                        <OperationalPage>
                            <Residents />
                        </OperationalPage>
                    }
                />

                <Route
                    path="/residents/:id"
                    element={
                        <OperationalPage>
                            <ResidentProfile />
                        </OperationalPage>
                    }
                />

                <Route
                    path="/medication"
                    element={
                        <OperationalPage>
                            <Medication />
                        </OperationalPage>
                    }
                />


                <Route
                    path="/family-messages"
                    element={
                        <OperationalPage>
                            <FamilyMessages />
                        </OperationalPage>
                    }
                />

                <Route
                    path="/tasks"
                    element={
                        <OperationalPage>
                            <NurseDashboard />
                        </OperationalPage>
                    }
                />

                <Route
                    path="/care-records"
                    element={
                        <OperationalPage>
                            <CareRecords />
                        </OperationalPage>
                    }
                />

                <Route
                    path="/admissions"
                    element={
                        <OperationalPage>
                            <Admissions />
                        </OperationalPage>
                    }
                />

                <Route
                    path="/discharges"
                    element={
                        <OperationalPage>
                            <Discharges />
                        </OperationalPage>
                    }
                />

                <Route
                    path="/inventory"
                    element={
                        <OperationalPage>
                            <Inventory />
                        </OperationalPage>
                    }
                />

                <Route
                    path="/monthly-glucose"
                    element={
                        <OperationalPage>
                            <MonthlyGlucose />
                        </OperationalPage>
                    }
                />

                <Route
                    path="/weekly-vitals"
                    element={
                        <OperationalPage>
                            <WeeklyVitals />
                        </OperationalPage>
                    }
                />

                <Route
                    path="/visitors"
                    element={
                        <OperationalPage>
                            <Visitors />
                        </OperationalPage>
                    }
                />

                <Route
                    path="/home-leave"
                    element={
                        <OperationalPage>
                            <HomeLeave />
                        </OperationalPage>
                    }
                />

                <Route
                    path="/parcels"
                    element={
                        <OperationalPage>
                            <Parcels />
                        </OperationalPage>
                    }
                />

                <Route
                    path="/ai-intelligence"
                    element={
                        <OperationalPage>
                            <AIIntelligence />
                        </OperationalPage>
                    }
                />

                <Route
                    path="/residents/:id/clinical-dashboard"
                    element={
                        <OperationalPage>
                            <ClinicalDashboardWrapper />
                        </OperationalPage>
                    }
                />

                <Route
                    path="/reports"
                    element={
                        <AdministratorPage>
                            <Reports />
                        </AdministratorPage>
                    }
                />

                <Route
                    path="/admin/dashboard"
                    element={
                        <AdministratorPage>
                            <AdminDashboard />
                        </AdministratorPage>
                    }
                />

                <Route
                    path="/billing"
                    element={
                        <OperationalPage>
                            <Billing />
                        </OperationalPage>
                    }
                />
            </Routes>

        </BrowserRouter>
    );
}

export default App;