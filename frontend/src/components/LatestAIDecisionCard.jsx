import React from "react";


function LatestAIDecisionCard({

    decision

}) {


    if(!decision)
    {

        return (

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

                    🤖 Latest AI Clinical Decision

                </h2>


                <p className="
                mt-4
                text-gray-500
                ">

                    No AI decision available.

                </p>


            </div>

        );

    }





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
            "

            >


                <h2

                className="
                text-xl
                font-bold
                text-slate-800
                "

                >

                    🤖 Latest AI Clinical Decision

                </h2>



                <span

                className="
                rounded-full
                bg-red-600
                px-3
                py-1
                text-xs
                font-bold
                text-white
                "

                >

                    {
                        decision.priority
                    }

                </span>


            </div>






            <div

            className="
            mt-5
            grid
            grid-cols-1
            md:grid-cols-3
            gap-4
            "

            >


                <div

                className="
                rounded-lg
                bg-slate-50
                p-4
                "

                >

                    <p className="
                    text-sm
                    text-gray-500
                    ">

                        Resident

                    </p>


                    <p className="
                    mt-2
                    font-bold
                    text-gray-800
                    ">

                        {
                            decision.resident_name
                        }

                    </p>


                </div>






                <div

                className="
                rounded-lg
                bg-red-50
                p-4
                "

                >

                    <p className="
                    text-sm
                    text-gray-500
                    ">

                        AI Decision Score

                    </p>


                    <p className="
                    mt-2
                    text-2xl
                    font-bold
                    text-red-600
                    ">

                        {
                            decision.decision_score
                        }

                        %

                    </p>


                </div>







                <div

                className="
                rounded-lg
                bg-blue-50
                p-4
                "

                >

                    <p className="
                    text-sm
                    text-gray-500
                    ">

                        Generated

                    </p>


                    <p className="
                    mt-2
                    font-semibold
                    text-gray-800
                    ">

                        {
                            decision.generated_at
                        }

                    </p>


                </div>



            </div>







            <div

            className="
            mt-6
            "

            >


                <h3

                className="
                font-bold
                text-gray-800
                mb-3
                "

                >

                    AI Risk Factors

                </h3>





                <ul

                className="
                space-y-2
                "

                >


                {
                    decision.risk_factors?.map(

                        (factor,index)=>(


                            <li

                            key={index}

                            className="
                            rounded-lg
                            bg-red-50
                            p-3
                            text-sm
                            text-gray-700
                            "

                            >

                                ⚠️ {factor}

                            </li>


                        )

                    )
                }


                </ul>


            </div>





        </div>


    );


}


export default LatestAIDecisionCard;