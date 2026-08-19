import React from "react";


export default function AITrendInterpretationCard({

    healthTrend

}) {


    if(!healthTrend)
    {

        return null;

    }



    const condition =
    healthTrend
    ?.current_condition;



    const trend =
    healthTrend
    ?.trend;



    const dataQuality =
    healthTrend
    ?.data_quality;




    return (

        <div

        className="
        bg-white
        rounded-xl
        shadow
        p-6
        mt-6
        "

        >



            <h2

            className="
            text-xl
            font-bold
            text-gray-800
            mb-6
            "

            >

                AI Trend Interpretation

            </h2>







            {/* CURRENT CONDITION */}



            <div

            className="
            bg-red-50
            rounded-lg
            p-4
            mb-5
            "

            >


                <h3

                className="
                font-bold
                text-gray-700
                "

                >

                    Current Condition

                </h3>




                <p

                className="
                text-2xl
                font-bold
                text-red-600
                mt-2
                "

                >

                    {
                        condition?.status
                    }

                </p>


            </div>









            {/* RISK FACTORS */}



            <div

            className="
            mb-5
            "

            >



                <h3

                className="
                font-bold
                text-gray-700
                mb-3
                "

                >

                    Risk Factors

                </h3>





                <ul

                className="
                space-y-2
                "

                >


                {

                condition
                ?.reasons
                ?.map(

                    (
                        reason,
                        index
                    )=>(


                    <li

                    key={index}

                    className="
                    bg-gray-50
                    rounded-lg
                    p-3
                    text-gray-700
                    "

                    >

                        ⚠ {reason}


                    </li>


                    )

                )

                }



                </ul>



            </div>









            {/* TREND ANALYSIS */}



            <div

            className="
            bg-blue-50
            rounded-lg
            p-4
            mb-5
            "

            >


                <h3

                className="
                font-bold
                text-gray-700
                "

                >

                    Trend Analysis

                </h3>




                <p

                className="
                font-semibold
                mt-2
                "

                >

                    {
                        trend?.status
                    }

                </p>





                {

                trend
                ?.analysis
                ?.map(

                    (
                        item,
                        index
                    )=>(

                    <p
                    key={index}
                    className="
                    text-gray-600
                    mt-2
                    "
                    >

                        {item}

                    </p>


                    )

                )

                }



            </div>









            {/* DATA QUALITY */}



            {

            dataQuality &&


            <div

            className="
            bg-yellow-50
            rounded-lg
            p-4
            "

            >


                <h3

                className="
                font-bold
                text-gray-700
                "

                >

                    Data Quality

                </h3>




                <p

                className="
                mt-2
                "

                >

                    ⚠ {dataQuality.status}

                </p>




                <p

                className="
                text-sm
                text-gray-600
                mt-1
                "

                >

                    {
                        dataQuality.message
                    }

                </p>



            </div>


            }



        </div>

    );


}