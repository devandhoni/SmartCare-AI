import {
BrowserRouter,
Routes,
Route,
useParams
}
from "react-router-dom";


import Login from "./pages/Login";

import AdminDashboard from "./pages/AdminDashboard";

import NurseDashboard from "./pages/NurseDashboard";

import AdminLayout from "./layouts/AdminLayout";

import ClinicalDashboard from "./components/clinical/ClinicalDashboard";


function ClinicalDashboardWrapper(){

    const {id} = useParams();


    return (

        <ClinicalDashboard
            residentId={id}
        />

    );

}

function App(){


return(


<BrowserRouter>


<Routes>


<Route
path="/"
element={<Login/>}
/>



<Route
path="/admin"
element={
<AdminLayout>
<AdminDashboard/>
</AdminLayout>
}
/>



<Route
path="/admin/dashboard"
element={
<AdminLayout>
<AdminDashboard/>
</AdminLayout>
}
/>



<Route
path="/nurse/dashboard"
element={<NurseDashboard/>}
/>



<Route

path="/residents/:id/clinical-dashboard"

element={<ClinicalDashboardWrapper/>}

/>


</Routes>


</BrowserRouter>


)


}



export default App;