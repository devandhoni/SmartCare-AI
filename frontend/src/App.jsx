import {
    BrowserRouter,
    Routes,
    Route,
    useParams,
}
from "react-router-dom";

import Login from "./pages/Login";

import AdminLayout from "./layouts/AdminLayout";

import Today from "./pages/Today";
import Residents from "./pages/Residents";
import ResidentProfile from "./pages/ResidentProfile";
import Medication from "./pages/Medication";
import NurseDashboard from "./pages/NurseDashboard";
import CareRecords from "./pages/CareRecords";

import Admissions from "./pages/Admissions";
import Discharges from "./pages/Discharges";
import Inventory from "./pages/Inventory";
import Visitors from "./pages/Visitors";
import HomeLeave from "./pages/HomeLeave";
import Parcels from "./pages/Parcels";

import Reports from "./pages/Reports";
import AIIntelligence from "./pages/AIIntelligence";

import AdminDashboard from "./pages/AdminDashboard";

import ClinicalDashboard from "./components/clinical/ClinicalDashboard";


function ClinicalDashboardWrapper() {

    const { id } = useParams();

    return (
        <ClinicalDashboard
            residentId={id}
        />
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
                        <AdminLayout>
                            <Today />
                        </AdminLayout>
                    }
                />


                <Route
                    path="/residents"
                    element={
                        <AdminLayout>
                            <Residents />
                        </AdminLayout>
                    }
                />


                <Route
                    path="/residents/:id"
                    element={
                        <AdminLayout>
                            <ResidentProfile />
                        </AdminLayout>
                    }
                />


                <Route
                    path="/medication"
                    element={
                        <AdminLayout>
                            <Medication />
                        </AdminLayout>
                    }
                />


                <Route
                    path="/tasks"
                    element={
                        <AdminLayout>
                            <NurseDashboard />
                        </AdminLayout>
                    }
                />


                <Route
                    path="/care-records"
                    element={
                        <AdminLayout>
                            <CareRecords />
                        </AdminLayout>
                    }
                />


                <Route
                    path="/admissions"
                    element={
                        <AdminLayout>
                            <Admissions />
                        </AdminLayout>
                    }
                />


                <Route
                    path="/discharges"
                    element={
                        <AdminLayout>
                            <Discharges />
                        </AdminLayout>
                    }
                />


                <Route
                    path="/inventory"
                    element={
                        <AdminLayout>
                            <Inventory />
                        </AdminLayout>
                    }
                />


                <Route
                    path="/visitors"
                    element={
                        <AdminLayout>
                            <Visitors />
                        </AdminLayout>
                    }
                />


                <Route
                    path="/home-leave"
                    element={
                        <AdminLayout>
                            <HomeLeave />
                        </AdminLayout>
                    }
                />


                <Route
                    path="/parcels"
                    element={
                        <AdminLayout>
                            <Parcels />
                        </AdminLayout>
                    }
                />


                <Route
                    path="/reports"
                    element={
                        <AdminLayout>
                            <Reports />
                        </AdminLayout>
                    }
                />


                <Route
                    path="/ai-intelligence"
                    element={
                        <AdminLayout>
                            <AIIntelligence />
                        </AdminLayout>
                    }
                />


                <Route
                    path="/admin/dashboard"
                    element={
                        <AdminLayout>
                            <AdminDashboard />
                        </AdminLayout>
                    }
                />


                <Route
                    path="/residents/:id/clinical-dashboard"
                    element={
                        <AdminLayout>
                            <ClinicalDashboardWrapper />
                        </AdminLayout>
                    }
                />

            </Routes>

        </BrowserRouter>

    );

}

export default App;