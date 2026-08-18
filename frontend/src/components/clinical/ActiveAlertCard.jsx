import React from "react";


function ActiveAlertCard({ alerts = [] }) {



    const getSeverityStyle = (severity) => {


        switch(severity){


            case "CRITICAL":

                return {
                    container:
                        "border-red-500 bg-red-50",
                    badge:
                        "bg-red-600 text-white",
                    text:
                        "text-red-700"
                };



            case "HIGH":

                return {
                    container:
                        "border-orange-500 bg-orange-50",
                    badge:
                        "bg-orange-500 text-white",
                    text:
                        "text-orange-700"
                };



            case "MEDIUM":

                return {
                    container:
                        "border-yellow-500 bg-yellow-50",
                    badge:
                        "bg-yellow-500 text-white",
                    text:
                        "text-yellow-700"
                };



            default:

                return {
                    container:
                        "border-gray-300 bg-gray-50",
                    badge:
                        "bg-gray-500 text-white",
                    text:
                        "text-gray-800"
                };


        }

    };






    return (


        <div className="
            bg-white
            rounded-xl
            shadow-lg
            p-6
            text-gray-900
        ">



            <h2 className="
                !text-gray-900
                text-xl
                font-bold
                mb-5
            ">

                Active Alerts

            </h2>





            {
                alerts.length === 0 ?


                (

                    <div className="
                        text-gray-600
                        text-center
                        py-5
                    ">

                        No active alerts

                    </div>

                )


                :


                (

                    <div className="
                        space-y-4
                    ">


                    {
                        alerts.map((alert)=>{


                            const style =
                                getSeverityStyle(
                                    alert.severity
                                );



                            return (


                                <div

                                    key={alert.id}

                                    className={`
                                        border-l-4
                                        rounded-lg
                                        p-5
                                        ${style.container}
                                    `}

                                >



                                    <div className="
                                        flex
                                        justify-between
                                        items-start
                                    ">


                                        <h3 className="
                                            font-bold
                                            text-lg
                                            text-gray-900
                                        ">

                                            {alert.alert_type}

                                        </h3>



                                        <span className={`
                                            px-3
                                            py-1
                                            rounded-full
                                            text-xs
                                            font-bold
                                            ${style.badge}
                                        `}>


                                            {alert.severity}


                                        </span>



                                    </div>






                                    <p className="
                                        mt-3
                                        text-gray-800
                                    ">

                                        {alert.message}

                                    </p>







                                    <div className="
                                        mt-4
                                        flex
                                        justify-between
                                        text-sm
                                    ">



                                        <p>

                                            AI Confidence:

                                            <strong className="
                                                ml-1
                                            ">

                                                {alert.ai_confidence}%

                                            </strong>

                                        </p>






                                        <p className="
                                            text-gray-600
                                        ">

                                            {
                                                alert.created_at
                                            }

                                        </p>



                                    </div>







                                    {
                                        alert.severity === "CRITICAL"
                                        &&

                                        (

                                            <div className="
                                                mt-4
                                                bg-red-100
                                                text-red-700
                                                rounded-lg
                                                p-3
                                                font-semibold
                                                text-center
                                            ">

                                                ⚠ Immediate Clinical Attention Required

                                            </div>

                                        )

                                    }





                                </div>


                            );


                        })

                    }


                    </div>


                )

            }



        </div>


    );


}



export default ActiveAlertCard;