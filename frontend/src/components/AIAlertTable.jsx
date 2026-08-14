import { useState } from "react";

import {
    acknowledgeAlert,
    resolveAlert
} from "../api/alertApi";



function AIAlertTable({
    alerts,
    onRefresh
}) {


    const [processingId, setProcessingId] =
        useState(null);





    const handleAcknowledge = async(id)=>{


        try{


            setProcessingId(id);


            await acknowledgeAlert(id);


            await onRefresh();


        }
        catch(error){


            console.error(
                "Acknowledge alert error:",
                error
            );


            alert(
                error?.response?.data?.message
                ??
                "Unable to acknowledge alert."
            );


        }
        finally{


            setProcessingId(null);


        }


    };









    const handleResolve = async(id)=>{


        const resolutionNote =
            window.prompt(
                "Enter resolution note:"
            );



        if(!resolutionNote)
        {
            return;
        }




        try{


            setProcessingId(id);



            await resolveAlert(
                id,
                resolutionNote
            );



            await onRefresh();



        }
        catch(error){


            console.error(
                "Resolve alert error:",
                error
            );



            alert(
                error?.response?.data?.message
                ??
                "Unable to resolve alert."
            );


        }
        finally{


            setProcessingId(null);


        }


    };









    return (


        <div className="rounded-xl bg-white p-6 shadow">



            <div className="flex items-center justify-between">


                <h2 className="text-xl font-bold text-slate-800">

                    🚨 Active AI Alerts

                </h2>



                <span className="rounded-full bg-red-100 px-3 py-1 text-sm font-semibold text-red-600">

                    {alerts.length} Active

                </span>



            </div>






            {
                alerts.length === 0


                ?


                (

                    <p className="mt-5 text-slate-500">

                        No active AI alerts.

                    </p>

                )


                :


                (

                <div className="mt-5 overflow-x-auto">


                    <table className="w-full">



                        <thead>


                            <tr className="border-b text-left text-sm text-slate-500">


                                <th className="p-3">
                                    Resident
                                </th>


                                <th className="p-3">
                                    Alert Type
                                </th>


                                <th className="p-3">
                                    Severity
                                </th>


                                <th className="p-3">
                                    Confidence
                                </th>


                                <th className="p-3">
                                    Status
                                </th>


                                <th className="p-3">
                                    Actions
                                </th>



                            </tr>


                        </thead>






                        <tbody>


                        {
                            alerts.map((alert)=>(


                                <tr

                                key={alert.id}

                                className="border-b hover:bg-slate-50"

                                >





                                    <td className="p-3 font-semibold">

                                        {
                                            alert.resident?.full_name
                                            ??
                                            "Unknown"
                                        }

                                    </td>






                                    <td className="p-3">

                                        {
                                            alert.alert_type
                                        }

                                    </td>







                                    <td className="p-3">


                                        <span

                                        className={`
                                        
                                        rounded-full px-3 py-1 text-xs font-bold

                                        ${
                                            alert.severity === "CRITICAL"

                                            ?

                                            "bg-red-600 text-white"

                                            :

                                            "bg-orange-100 text-orange-700"

                                        }

                                        `}

                                        >


                                            {
                                                alert.severity
                                            }


                                        </span>



                                    </td>









                                    <td className="p-3">


                                        {
                                            alert.ai_confidence
                                        }%


                                    </td>









                                    <td className="p-3">


                                        {

                                            alert.acknowledged_at


                                            ?


                                            (

                                                <span className="rounded-full bg-yellow-100 px-3 py-1 text-xs font-bold text-yellow-700">

                                                    ACKNOWLEDGED

                                                </span>

                                            )


                                            :


                                            (

                                                <span className="rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">

                                                    OPEN

                                                </span>

                                            )

                                        }



                                    </td>









                                    <td className="p-3">


                                        <div className="flex gap-2">





                                            <button


                                            onClick={()=>
                                                handleAcknowledge(
                                                    alert.id
                                                )
                                            }


                                            disabled={

                                                processingId === alert.id

                                                ||

                                                alert.acknowledged_at

                                            }


                                            className="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"


                                            >


                                                {

                                                    alert.acknowledged_at

                                                    ?

                                                    "Acknowledged ✓"

                                                    :

                                                    "Acknowledge"

                                                }



                                            </button>









                                            <button


                                            onClick={()=>
                                                handleResolve(
                                                    alert.id
                                                )
                                            }


                                            disabled={
                                                processingId === alert.id
                                            }


                                            className="rounded-lg bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50"


                                            >


                                                Resolve


                                            </button>





                                        </div>



                                    </td>





                                </tr>


                            ))
                        }


                        </tbody>





                    </table>



                </div>


                )

            }



        </div>


    );


}



export default AIAlertTable;