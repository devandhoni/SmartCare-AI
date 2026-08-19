import { useEffect, useState } from "react";

import { 
    getAICommandCenter,
    getAIAlerts,
    getNurseTasks
} from "../api/dashboardApi";


import KPICard from "../components/KPICard";
import PriorityAttention from "../components/PriorityAttention";
import ClinicalPerformance from "../components/ClinicalPerformance";
import AIAlertTable from "../components/AIAlertTable";
import NurseTaskTable from "../components/NurseTaskTable";
import CriticalResidentTable from "../components/CriticalResidentTable";
import AIAlertSummaryCard from "../components/AIAlertSummaryCard";
import LatestAIDecisionCard from "../components/LatestAIDecisionCard";



function AdminDashboard() {



    const [dashboard,setDashboard] = useState(null);

    const [alerts,setAlerts] = useState([]);

    const [tasks,setTasks] = useState([]);

    const [lastUpdated,setLastUpdated] = useState(null);

    const [refreshing,setRefreshing] = useState(false);






    useEffect(()=>{

        loadDashboard();

    }, []);







    const loadDashboard = async()=>{


        try{


            setRefreshing(true);



            const data =
                await getAICommandCenter();



            console.log(
                "ADMIN COMMAND CENTER FULL:",
                JSON.stringify(data,null,2)
            );



            setDashboard(
                data.data
            );







            const alertResponse =
                await getAIAlerts();



            console.log(
                "ADMIN AI ALERT DATA:",
                JSON.stringify(alertResponse,null,2)
            );



            setAlerts(
                alertResponse?.data?.alerts ?? []
            );







            const taskResponse =
                await getNurseTasks();



            setTasks(
                taskResponse?.data ?? []
            );








            setLastUpdated(
                new Date()
            );



        }

        catch(error){


            console.error(
                "Dashboard loading error:",
                error
            );


        }


        finally{


            setRefreshing(false);


        }


    };









    if(!dashboard)
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

                    Loading SmartCare AI Dashboard...

                </p>



            </div>

        );


    }









    return (


        <div className="space-y-8">





            {/* HEADER */}

            <div

            className="
            flex
            justify-between
            items-start
            "

            >


                <div>


                    <h1

                    className="
                    text-3xl
                    font-bold
                    text-slate-800
                    "

                    >

                        SmartCare AI Command Center

                    </h1>



                    <p

                    className="
                    mt-2
                    text-slate-500
                    "

                    >

                        Clinical intelligence monitoring and decision support dashboard

                    </p>






                    {
                        lastUpdated &&


                        <p

                        className="
                        mt-3
                        text-sm
                        font-semibold
                        text-green-600
                        "

                        >

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
                    loadDashboard
                }


                disabled={
                    refreshing
                }


                className="
                rounded-lg
                bg-blue-600
                px-5
                py-3
                font-semibold
                text-white
                hover:bg-blue-700
                disabled:opacity-50
                "

                >


                    {

                    refreshing

                    ?

                    "Refreshing..."

                    :

                    "🔄 Refresh Intelligence"

                    }


                </button>




            </div>











            {/* KPI CARDS */}


            <div className="
            grid
            grid-cols-1
            gap-6
            md:grid-cols-3
            ">



                <KPICard

                    title="Total Residents"

                    value={
                        dashboard
                        .clinical_overview
                        ?.total_residents ?? 0
                    }

                    icon="👥"

                    color="text-blue-600"

                />





                <KPICard

                    title="Critical Cases"

                    value={
                        dashboard
                        .clinical_overview
                        ?.critical_cases ?? 0
                    }

                    icon="🚨"

                    color="text-red-600"

                />





                <KPICard

                    title="Active AI Alerts"

                    value={
                        dashboard
                        .clinical_overview
                        ?.active_alerts ?? 0
                    }

                    icon="🔔"

                    color="text-orange-600"

                />





                <KPICard

                    title="AI Predictions"

                    value={
                        dashboard
                        .ai_performance
                        ?.predictions_generated ?? 0
                    }

                    icon="🤖"

                    color="text-purple-600"

                />





                <KPICard

                    title="High Risk Predictions"

                    value={
                        dashboard
                        .ai_performance
                        ?.high_risk_predictions ?? 0
                    }

                    icon="⚠️"

                    color="text-yellow-600"

                />





                <KPICard

                    title="Pending Nurse Tasks"

                    value={
                        dashboard
                        .clinical_performance
                        ?.nursing_metrics
                        ?.pending_tasks ?? 0
                    }

                    icon="🩺"

                    color="text-green-600"

                />



            </div>









            {/* CRITICAL RESIDENT RANKING */}


            <CriticalResidentTable

                residents={
                    dashboard.priority_attention ?? []
                }

            />







            {/* LATEST AI DECISION */}


            <LatestAIDecisionCard

                decision={
                    dashboard.latest_ai_decision
                }

            />







            {/* AI ALERT INTELLIGENCE */}


            <AIAlertSummaryCard

                alerts={
                    alerts
                }

            />











            {/* AI SYSTEM STATUS */}


            <div className="
            rounded-xl
            bg-white
            p-6
            shadow
            ">



                <h2 className="
                text-xl
                font-bold
                text-slate-800
                ">

                    AI System Status

                </h2>




                <div className="
                mt-4
                flex
                items-center
                gap-3
                ">


                    <div

                    className={`

                    h-3
                    w-3
                    rounded-full

                    ${
                    dashboard.system_status === "ACTIVE"

                    ?

                    "bg-green-500"

                    :

                    "bg-orange-500"

                    }

                    `}

                    ></div>




                    <p className="
                    font-semibold
                    ">

                        {
                            dashboard.system_status
                        }

                    </p>



                </div>


            </div>









            {/* PRIORITY ATTENTION */}


            <PriorityAttention

                alerts={
                    dashboard.priority_attention ?? []
                }

            />










            {/* CLINICAL PERFORMANCE */}


            <ClinicalPerformance

                performance={
                    dashboard.clinical_performance
                }

            />









            {/* AI ALERT TABLE */}


            <AIAlertTable

                alerts={
                    alerts
                }

                onRefresh={
                    loadDashboard
                }

            />









            {/* NURSE TASK TABLE */}


            <NurseTaskTable

                tasks={
                    tasks
                }

                onRefresh={
                    loadDashboard
                }

            />











            {/* EXECUTIVE SUMMARY */}



            <div className="
            rounded-xl
            bg-white
            p-6
            shadow
            ">


                <h2 className="
                text-xl
                font-bold
                text-slate-800
                ">

                    Executive Summary

                </h2>



                <p className="
                mt-4
                text-slate-600
                ">


                    {
                        dashboard
                        .executive_summary
                        ?.executive_message
                    }


                </p>



            </div>







        </div>


    );


}



export default AdminDashboard;