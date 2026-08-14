import { NavLink } from "react-router-dom";


function Sidebar(){


    const menu = [

        {
            name:"Dashboard",
            path:"/admin/dashboard"
        },

        {
            name:"Residents",
            path:"/admin/residents"
        },

        {
            name:"AI Alerts",
            path:"/admin/alerts"
        },

        {
            name:"Nurse Tasks",
            path:"/admin/tasks"
        },

        {
            name:"Analytics",
            path:"/admin/analytics"
        },

        {
            name:"Notifications",
            path:"/admin/notifications"
        }

    ];




    return (

        <div className="w-64 min-h-screen bg-slate-900 text-white p-5">


            <h1 className="text-2xl font-bold mb-8">

                SmartCare AI

            </h1>



            <nav>


            {
                menu.map((item)=>(


                    <NavLink

                    key={item.name}

                    to={item.path}

                    className={({isActive})=>

                        `block p-3 rounded-lg mb-2 ${
                            
                        isActive
                        ?
                        "bg-blue-600"
                        :
                        "hover:bg-slate-700"

                        }`

                    }

                    >

                    {item.name}

                    </NavLink>


                ))
            }


            </nav>


        </div>

    );


}


export default Sidebar;