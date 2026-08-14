import Sidebar from "./Sidebar";
import AdminHeader from "../components/AdminHeader";


function AdminLayout({children}){


return (

<div className="flex min-h-screen bg-slate-100">


    <Sidebar />


    <main className="flex-1 p-8">


        <AdminHeader />


        {children}


    </main>


</div>


);


}


export default AdminLayout;