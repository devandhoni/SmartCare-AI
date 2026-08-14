import {

BrowserRouter,

Routes,

Route

}

from "react-router-dom";


import Login from "./pages/Login";

import AdminDashboard from "./pages/AdminDashboard";

import NurseDashboard from "./pages/NurseDashboard";

import AdminLayout from "./layouts/AdminLayout";




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




</Routes>


</BrowserRouter>


)


}



export default App;