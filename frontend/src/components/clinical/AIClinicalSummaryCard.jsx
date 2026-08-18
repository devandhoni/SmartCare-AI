import React from "react";


export default function AIClinicalSummaryCard({

    healthTrend,
    clinicalDecision,
    alerts = []

}) {



    const currentCondition =
        healthTrend?.current_condition;



    const status =
        currentCondition?.status ?? "UNKNOWN";



    const reasons =
        currentCondition?.reasons ?? [];




    const getStatusStyle = () => {


        if(status === "CRITICAL")
        {

            return {
                box:
                "bg-red-50 border-red-300",

                badge:
                "bg-red-600 text-white"
            };

        }



        if(status === "HIGH")
        {

            return {

                box:
                "bg-orange-50 border-orange-300",

                badge:
                "bg-orange-500 text-white"

            };

        }



        return {

            box:
            "bg-green-50 border-green-300",

            badge:
            "bg-green-600 text-white"

        };


    };





    const style =
        getStatusStyle();





    return (

        <div
        className={`
        rounded-xl
        shadow
        p-6
        border
        ${style.box}
        `}
        >



            <h2
            className="
            text-xl
            font-bold
            text-gray-900
            mb-5
            "
            >
                AI Clinical Summary
            </h2>






            <div
            className="
            flex
            items-center
            gap-3
            mb-6
            "
            >


                <span
                className={`
                px-4
                py-2
                rounded-full
                font-bold
                ${style.badge}
                `}
                >

                    {status}

                </span>


                <p
                className="
                text-gray-700
                font-semibold
                "
                >

                    Current Patient Status

                </p>


            </div>







            <div>

                <h3
                className="
                font-bold
                text-gray-900
                mb-3
                "
                >
                    Primary Concerns
                </h3>



                {
                    reasons.length > 0 ?


                    (

                    <ul
                    className="
                    space-y-2
                    "
                    >

                        {
                            reasons.map(
                                (reason,index)=>(

                                <li
                                key={index}
                                className="
                                flex
                                gap-2
                                text-gray-700
                                "
                                >

                                    <span>
                                        ⚠️
                                    </span>

                                    {reason}

                                </li>

                                )
                            )
                        }


                    </ul>

                    )


                    :


                    (

                    <p
                    className="
                    text-gray-500
                    "
                    >

                        No major clinical concerns detected.

                    </p>

                    )

                }


            </div>









            <div
            className="
            mt-6
            bg-white
            rounded-lg
            p-4
            "
            >


                <h3
                className="
                font-bold
                text-gray-900
                mb-2
                "
                >
                    AI Recommendation
                </h3>



                <p
                className="
                text-gray-900
                "
                >

                    {
                        status === "CRITICAL"

                        ?

                        "Continuous monitoring required. Nurse assessment recommended."

                        :

                        "Continue routine monitoring and observation."

                    }


                </p>


            </div>






        </div>

    );

}