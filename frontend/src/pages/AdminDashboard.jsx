import { useEffect, useState } from "react";

import { 
    getAICommandCenter,
    getAIAlerts
} from "../api/dashboardApi";

import KPICard from "../components/KPICard";
import PriorityAttention from "../components/PriorityAttention";
import ClinicalPerformance from "../components/ClinicalPerformance";
import AIAlertTable from "../components/AIAlertTable";
import NurseTaskTable from "../components/NurseTaskTable";


function AdminDashboard() {


    const [dashboard,setDashboard] = useState(null);

    const [alerts,setAlerts] = useState([]);

    const [tasks, setTasks] = useState([]);



    useEffect(() => {

        loadDashboard();

    }, []);





    const loadDashboard = async () => {

    try {

        const data =
            await getAICommandCenter();

        setDashboard(
            data.data
        );


        const alertResponse =
            await getAIAlerts();

        setAlerts(
            alertResponse.data.alerts
        );


        const taskResponse =
            await getNurseTasks();

        setTasks(
            taskResponse.data
        );

    }
    catch(error) {

        console.error(
            "Dashboard loading error:",
            error
        );

    }

};






    if(!dashboard)
    {

        return (

            <div className="flex min-h-screen items-center justify-center">

                <p className="text-lg font-semibold text-slate-600">

                    Loading SmartCare AI Dashboard...

                </p>


            </div>

        );

    }







    return (

        <div className="space-y-8">



            {/* Page Title */}

            <div>


                <h1 className="text-3xl font-bold text-slate-800">

                    SmartCare AI Command Center

                </h1>


                <p className="mt-2 text-slate-500">

                    Clinical intelligence monitoring and decision support dashboard

                </p>


            </div>







            {/* KPI Cards */}


            <div className="grid grid-cols-1 gap-6 md:grid-cols-3">



                <KPICard

                    title="Total Residents"

                    value={
                        dashboard
                        .clinical_overview
                        .total_residents
                    }

                    icon="👥"

                    color="text-blue-600"

                />





                <KPICard

                    title="Critical Cases"

                    value={
                        dashboard
                        .clinical_overview
                        .critical_cases
                    }

                    icon="🚨"

                    color="text-red-600"

                />





                <KPICard

                    title="Active AI Alerts"

                    value={
                        dashboard
                        .clinical_overview
                        .active_alerts
                    }

                    icon="🔔"

                    color="text-orange-600"

                />





                <KPICard

                    title="AI Predictions"

                    value={
                        dashboard
                        .ai_performance
                        .predictions_generated
                    }

                    icon="🤖"

                    color="text-purple-600"

                />





                <KPICard

                    title="High Risk Predictions"

                    value={
                        dashboard
                        .ai_performance
                        .high_risk_predictions
                    }

                    icon="⚠️"

                    color="text-yellow-600"

                />





                <KPICard

                    title="Pending Nurse Tasks"

                    value={
                        dashboard
                        .clinical_performance
                        .nursing_metrics
                        .pending_tasks
                    }

                    icon="🩺"

                    color="text-green-600"

                />



            </div>









            {/* AI System Status */}


            <div className="rounded-xl bg-white p-6 shadow">


                <h2 className="text-xl font-bold text-slate-800">

                    AI System Status

                </h2>



                <div className="mt-4 flex items-center gap-3">


                    <div

                        className={`h-3 w-3 rounded-full ${
                            
                            dashboard.system_status === "ACTIVE"

                            ?

                            "bg-green-500"

                            :

                            "bg-orange-500"

                        }`}

                    ></div>




                    <p

                    className={`font-semibold ${
                        
                        dashboard.system_status === "ACTIVE"

                        ?

                        "text-green-600"

                        :

                        "text-orange-600"

                    }`}

                    >

                        {dashboard.system_status}

                    </p>


                </div>



            </div>




            {/* Priority Attention */}

            <PriorityAttention

                alerts={
                    dashboard.priority_attention
                }

            />


            {/* Clinical Performance */}

            <ClinicalPerformance

            performance={
            dashboard.clinical_performance
            }

            />

            {/* AI Alerts Table */}

            <AIAlertTable
            alerts={alerts}
            onRefresh={loadDashboard}
            />

            {/* Nurse Tasks Table */}
            <NurseTaskTable
            tasks={tasks}
            onRefresh={loadDashboard}
            />


            {/* Executive Summary */}


            <div className="rounded-xl bg-white p-6 shadow">


                <h2 className="text-xl font-bold text-slate-800">

                    Executive Summary

                </h2>



                <p className="mt-4 text-slate-600">

                    {
                        dashboard
                        .executive_summary
                        .executive_message
                    }

                </p>



            </div>






        </div>

    );


}



export default AdminDashboard;