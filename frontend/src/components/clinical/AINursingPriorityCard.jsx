import React from "react";


export default function AINursingPriorityCard({

    healthTrend

}) {



    if(!healthTrend)
    {
        return null;
    }



    const reasons =
    healthTrend
    ?.current_condition
    ?.reasons ?? [];





    const generateActions = ()=>{


        let actions = [];



        reasons.forEach(
            reason=>{


                if(
                    reason.includes(
                        "oxygen"
                    )
                )
                {

                    actions.push(
                        "Monitor oxygen saturation regularly"
                    );

                }




                if(
                    reason.includes(
                        "blood pressure"
                    )
                )
                {

                    actions.push(
                        "Recheck blood pressure and observe hypertension symptoms"
                    );

                }




                if(
                    reason.includes(
                        "temperature"
                    )
                )
                {

                    actions.push(
                        "Monitor temperature and infection symptoms"
                    );

                }




                if(
                    reason.includes(
                        "glucose"
                    )
                )
                {

                    actions.push(
                        "Monitor blood glucose level and diabetic risk"
                    );

                }


            }

        );



        return actions;


    };





    const nursingActions =
    generateActions();






    const priority =

    healthTrend
    ?.current_condition
    ?.status === "CRITICAL"

    ?

    "HIGH PRIORITY"

    :

    "NORMAL";









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
            mb-5
            "

            >

                Today's Nursing Priority

            </h2>








            <div

            className="
            bg-red-50
            border
            border-red-200
            rounded-lg
            p-4
            mb-5
            "

            >


                <p

                className="
                text-sm
                text-gray-600
                "

                >

                    Patient Care Priority

                </p>




                <h3

                className="
                text-2xl
                font-bold
                text-red-600
                mt-1
                "

                >

                    {priority}

                </h3>



            </div>









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

                    Clinical Concerns

                </h3>





                <ul

                className="
                space-y-2
                "

                >

                {

                reasons.map(

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
                    "

                    >

                        ⚠ {reason}

                    </li>


                    )

                )

                }


                </ul>


            </div>









            <div>


                <h3

                className="
                font-bold
                text-gray-700
                mb-3
                "

                >

                    Recommended Nursing Actions

                </h3>





                <ul

                className="
                space-y-2
                "

                >

                {

                nursingActions.map(

                    (
                        action,
                        index
                    )=>(


                    <li

                    key={index}

                    className="
                    bg-green-50
                    rounded-lg
                    p-3
                    "

                    >

                        ✓ {action}

                    </li>


                    )

                )

                }


                </ul>



            </div>









            <div

            className="
            mt-5
            bg-blue-50
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

                    Follow Up Plan

                </h3>



                <p

                className="
                text-gray-700
                mt-2
                "

                >

                    Continue close monitoring and escalate to physician review if patient condition deteriorates.

                </p>



            </div>






        </div>


    );


}