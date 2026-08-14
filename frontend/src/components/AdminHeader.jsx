import { useNavigate } from "react-router-dom";


function AdminHeader(){


    const navigate = useNavigate();


    const user =
    JSON.parse(
        localStorage.getItem("user")
    );



    const logout = ()=>{


        localStorage.removeItem("token");

        localStorage.removeItem("user");


        navigate("/");


    };



    return (

        <div className="mb-8 flex items-center justify-between rounded-xl bg-white p-6 shadow">


            <div>


                <h1 className="text-2xl font-bold text-slate-800">

                    SmartCare AI Command Center

                </h1>



                <p className="mt-2 text-slate-500">

                    Welcome, {user?.name}

                </p>



                <span className="mt-2 inline-block rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-700">

                    {user?.role}

                </span>


            </div>





            <div className="flex items-center gap-5">


                <div className="text-right">


                    <p className="text-sm text-slate-500">

                        System Status

                    </p>


                    <p className="font-bold text-orange-600">

                        ATTENTION REQUIRED

                    </p>


                </div>





                <button

                onClick={logout}

                className="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700"

                >

                    Logout

                </button>



            </div>



        </div>

    );


}


export default AdminHeader;