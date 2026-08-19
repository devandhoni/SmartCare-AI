import React from "react";


function AIAlertSummaryCard({

    alerts = []

}) {



    const criticalCount =
        alerts.filter(
            alert =>
                alert.severity === "CRITICAL"
        ).length;



    const warningCount =
        alerts.filter(
            alert =>
                alert.severity === "WARNING"
        ).length;



    const acknowledgedCount =
        alerts.filter(
            alert =>
                alert.acknowledged_at
        ).length;



    return (


        <div

        className="
        rounded-xl
        bg-white
        p-6
        shadow
        "

        >



            <div

            className="
            flex
            justify-between
            items-center
            mb-5
            "

            >


                <h2

                className="
                text-xl
                font-bold
                text-slate-800
                "

                >

                    🚨 AI Alert Intelligence

                </h2>



                <span

                className="
                rounded-full
                bg-red-100
                px-3
                py-1
                text-sm
                font-semibold
                text-red-600
                "

                >

                    {alerts.length} Total

                </span>



            </div>







            <div

            className="
            grid
            grid-cols-1
            md:grid-cols-3
            gap-4
            "

            >



                <div

                className="
                rounded-lg
                bg-red-50
                p-5
                text-center
                "

                >

                    <p className="
                    text-sm
                    text-slate-500
                    ">

                        Critical Alerts

                    </p>


                    <h3

                    className="
                    mt-2
                    text-3xl
                    font-bold
                    text-red-600
                    "

                    >

                        {criticalCount}

                    </h3>


                </div>








                <div

                className="
                rounded-lg
                bg-orange-50
                p-5
                text-center
                "

                >

                    <p className="
                    text-sm
                    text-slate-500
                    ">

                        Warning Alerts

                    </p>


                    <h3

                    className="
                    mt-2
                    text-3xl
                    font-bold
                    text-orange-600
                    "

                    >

                        {warningCount}

                    </h3>


                </div>









                <div

                className="
                rounded-lg
                bg-blue-50
                p-5
                text-center
                "

                >

                    <p className="
                    text-sm
                    text-slate-500
                    ">

                        Acknowledged

                    </p>


                    <h3

                    className="
                    mt-2
                    text-3xl
                    font-bold
                    text-blue-600
                    "

                    >

                        {acknowledgedCount}

                    </h3>


                </div>




            </div>








            <div

            className="
            mt-6
            space-y-3
            "

            >


            {
                alerts.slice(0,3).map(

                    alert => (


                    <div

                    key={
                        alert.id
                    }

                    className="
                    border
                    rounded-lg
                    p-4
                    "

                    >


                        <div

                        className="
                        flex
                        justify-between
                        "

                        >


                            <h4

                            className="
                            font-bold
                            text-slate-800
                            "

                            >

                                {
                                    alert.resident?.full_name
                                    ??
                                    "Unknown Resident"
                                }

                            </h4>




                            <span

                            className={`

                            rounded-full
                            px-3
                            py-1
                            text-xs
                            font-bold


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




                        </div>





                        <p

                        className="
                        mt-2
                        text-sm
                        text-gray-600
                        "

                        >

                            {
                                alert.alert_type
                            }

                        </p>



                        <p

                        className="
                        text-sm
                        text-gray-500
                        "

                        >

                            AI Confidence:

                            <strong>

                                {" "}
                                {
                                    alert.ai_confidence
                                }%

                            </strong>


                        </p>



                    </div>


                    )

                )
            }



            </div>





        </div>


    );


}


export default AIAlertSummaryCard;