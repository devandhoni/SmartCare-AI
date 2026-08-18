import React, { useEffect, useState } from "react";

import { getClinicalDashboard } from "../../api/clinicalDashboardApi";

import LatestVitalCard from "./LatestVitalCard";
import ActiveAlertCard from "./ActiveAlertCard";
import AIRiskCard from "./AIRiskCard";
import VitalTrendChart from "./VitalTrendChart";


function ClinicalDashboard({ residentId }) {


    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);



    useEffect(() => {

        if (residentId) {
            loadDashboard();
        }

    }, [residentId]);





    const loadDashboard = async () => {

        try {

            const result = await getClinicalDashboard(residentId);


            console.log(
                "Clinical Dashboard API Result:",
                result
            );


            console.log(
                "Active Alerts FULL:",
                JSON.stringify(result.active_alerts, null, 2)
            );

            console.log(
                "Health Trend:",
                JSON.stringify(result.health_trend, null, 2)
            );

            setData(result);


        } catch (error) {


            console.error(
                "Clinical Dashboard Error:",
                error
            );


        } finally {

            setLoading(false);

        }

    };





    if (loading) {

        return (

            <div>
                Loading Clinical Dashboard...
            </div>

        );

    }





    if (!data) {

        return (

            <div>
                No clinical data available
            </div>

        );

    }





    // Safe data handling

    const activeAlerts =
        data.active_alerts?.data ??
        data.active_alerts?.alerts ??
        data.active_alerts ??
        [];




    const aiPredictions =
        data.ai_predictions ?? [];





    return (

        <div className="clinical-dashboard">


            <h1>
                Clinical Intelligence Dashboard
            </h1>





            <div className="resident-summary">


                <h2 className="
                    text-2xl
                    font-bold
                    text-blue-100
                ">
                    {data.resident?.name}
                </h2>




                <p className="
                    text-blue-100
                    text-lg
                ">

                Risk Level:

                <strong className="
                    text-red-400
                ">

                {data.health_status?.risk_level}

                </strong>

                </p>





                <p>

                    Risk Score:

                    {
                        data.health_status?.risk_score ??
                        "-"
                    }

                </p>





                <p
                    className="
                    text-blue-100
                    text-sm
                    mt-3
                    "
                    >

                    {
                    data.health_status?.summary ??
                    "No clinical summary available"
                    }

                </p>



            </div>


            <div
                className="
                mt-5
                bg-gray-800
                rounded-xl
                p-5
                text-white
                "
                >


                <h3
                className="
                font-bold
                text-lg
                "
                >
                AI Health Intelligence
                </h3>



                <p className="mt-3">

                Current Condition:

                <strong className="text-red-400">

                {
                data.health_trend
                ?.current_condition
                ?.status
                }

                </strong>

                </p>




                <p>

                Trend Status:

                <strong>

                {
                data.health_trend
                ?.trend
                ?.status
                }

                </strong>

                </p>




                <p>

                AI Reliability:

                <strong>

                {
                data.health_trend
                ?.trend_confidence
                }%

                </strong>

                </p>



                <p>

                Data Quality:

                <strong>

                {
                data.health_trend
                ?.data_quality
                ?.status
                }

                </strong>

                </p>


                </div>







            <div className="
                grid
                grid-cols-1
                lg:grid-cols-3
                gap-6
            ">  





                <LatestVitalCard

                    vital={
                        data.latest_vital ?? null
                    }

                />






                <ActiveAlertCard

                    alerts={activeAlerts}

                />







                <AIRiskCard

                 score={
                    data.health_status?.risk_score ?? 0
                }


                priority={
                    data.health_status?.risk_level ?? "UNKNOWN"
                }


                confidence={
                    data.active_alerts?.data?.[0]?.ai_confidence ?? 0
                }


                message={
                    data.active_alerts?.data?.[0]?.message ??
                    "No AI prediction available"
                }

                />






            </div>

            <VitalTrendChart

                vitals={
                    data.health_trend?.vitals ?? []
                }

            />



        </div>

    );



}



export default ClinicalDashboard;