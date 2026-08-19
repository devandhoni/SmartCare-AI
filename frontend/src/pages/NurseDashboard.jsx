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



    const [lastUpdated,setLastUpdated] =
        useState(null);





    useEffect(()=>{


        loadTasks();


    },[]);







    const loadTasks = async()=>{


        try{


            setLoading(true);



            const response =
                await getNurseTasks();



            console.log(
                "NURSE TASK DATA:",
                response
            );




            /*
                API response expected:

                {
                    tasks:[]
                }

                or

                []
            */


            setTasks(

                response.tasks
                ??
                response.data
                ??
                response
                ??
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