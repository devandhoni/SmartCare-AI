import {
    useEffect,
    useState
}
from "react";


import {
    getNurseTasks
}
from "../api/nurseTaskApi";


import NurseTaskTable
from "../components/NurseTaskTable";





function NurseDashboard() {



    const [tasks,setTasks] =
        useState([]);


    const [loading,setLoading] =
        useState(true);

    const [error,setError] =
        useState("");

    const [lastUpdated,setLastUpdated] =
        useState(null);





    useEffect(()=>{


        loadTasks();


    },[]);







    const loadTasks = async()=>{


        try{


            setLoading(true);

            setError("");



            const response =
                await getNurseTasks();
/*
                API response expected:

                {
                    tasks:[]
                }

                or

                []
            */


            const taskData =
                response?.tasks
                ??
                response?.data
                ??
                response
                ??
                [];

            setTasks(
                Array.isArray(taskData)
                ?
                taskData
                :
                []
            );




            setLastUpdated(
                new Date()
            );



        }

    

        catch(error){


            console.error(

                "Nurse dashboard loading error:",
                error

            );


            setError(

                error?.response?.data?.message
                ??
                error?.message
                ??
                "Unable to load nurse tasks. Please try again."

            );


        }

        finally{


            setLoading(false);


        }


    };








    if(loading)
    {


        return (

            <div className="
            flex
            min-h-screen
            items-center
            justify-center
            ">


                <p className="
                text-lg
                font-semibold
                text-slate-600
                ">

                    Loading SmartCare AI Nurse Dashboard...

                </p>



            </div>


        );


    }



    if(error)
        {

            return (

                <div className="
                flex
                min-h-[50vh]
                items-center
                justify-center
                px-4
                ">

                    <div className="
                    w-full
                    max-w-xl
                    rounded-xl
                    border
                    border-red-200
                    bg-red-50
                    p-6
                    text-center
                    ">

                        <h2 className="
                        text-lg
                        font-bold
                        text-red-800
                        ">

                            Unable to load nurse tasks

                        </h2>

                        <p className="
                        mt-2
                        text-sm
                        text-red-700
                        ">

                            {error}

                        </p>

                        <button
                            type="button"
                            onClick={loadTasks}
                            className="
                            mt-4
                            rounded-lg
                            bg-red-600
                            px-5
                            py-2
                            font-semibold
                            text-white
                            hover:bg-red-700
                            "
                        >

                            Try Again

                        </button>

                    </div>

                </div>

            );

        }



    const pendingTasks =

        tasks.filter(

            task =>
            task.status === "Pending"

        ).length;





    const criticalTasks =

        tasks.filter(

            task =>
            task.alert?.severity === "CRITICAL"

        ).length;






    const completedTasks =

        tasks.filter(

            task =>
            task.status === "Completed"

        ).length;









    return (


        <div className="
        space-y-8
        ">






            {/* HEADER */}


            <div className="
            flex
            justify-between
            items-start
            ">



                <div>


                    <h1 className="
                    text-3xl
                    font-bold
                    text-slate-800
                    ">

                        🩺 SmartCare AI Nurse Command Center

                    </h1>




                    <p className="
                    mt-2
                    text-slate-500
                    ">

                        AI-assisted clinical task management and resident monitoring

                    </p>





                    {
                        lastUpdated &&


                        <p className="
                        mt-3
                        text-sm
                        font-semibold
                        text-green-600
                        ">


                            🟢 Last Updated:

                            {" "}

                            {
                                lastUpdated.toLocaleString()
                            }



                        </p>


                    }



                </div>







                <button


                onClick={
                    loadTasks
                }


                className="
                rounded-lg
                bg-blue-600
                px-5
                py-3
                font-semibold
                text-white
                hover:bg-blue-700
                "


                >

                    🔄 Refresh Tasks


                </button>




            </div>












            {/* SUMMARY CARDS */}



            <div className="
            grid
            grid-cols-1
            md:grid-cols-3
            gap-6
            ">






                <div className="
                rounded-xl
                bg-white
                p-6
                shadow
                ">


                    <p className="
                    text-sm
                    text-gray-500
                    ">

                        Pending Tasks

                    </p>


                    <h2 className="
                    mt-2
                    text-3xl
                    font-bold
                    text-blue-600
                    ">

                        {
                            pendingTasks
                        }


                    </h2>


                </div>









                <div className="
                rounded-xl
                bg-white
                p-6
                shadow
                ">


                    <p className="
                    text-sm
                    text-gray-500
                    ">

                        Critical AI Tasks

                    </p>


                    <h2 className="
                    mt-2
                    text-3xl
                    font-bold
                    text-red-600
                    ">

                        {
                            criticalTasks
                        }


                    </h2>


                </div>









                <div className="
                rounded-xl
                bg-white
                p-6
                shadow
                ">


                    <p className="
                    text-sm
                    text-gray-500
                    ">

                        Completed Tasks

                    </p>


                    <h2 className="
                    mt-2
                    text-3xl
                    font-bold
                    text-green-600
                    ">

                        {
                            completedTasks
                        }


                    </h2>


                </div>






            </div>












            {/* AI TASK QUEUE */}



            <NurseTaskTable


                tasks={tasks}


                onRefresh={
                    loadTasks
                }


            />








        </div>


    );


}





export default NurseDashboard;