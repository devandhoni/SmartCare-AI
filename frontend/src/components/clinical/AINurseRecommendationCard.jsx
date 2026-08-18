import React from "react";


export default function AINurseRecommendationCard({

    carePlan

}) {


    if(!carePlan)
    {

        return null;

    }



    const priority =
        carePlan.care_priority ?? "NORMAL";



    const recommendations =
        carePlan.personalized_recommendations ?? [];



    const guidance =
        carePlan.lifestyle_guidance ?? [];



    const followUp =
        carePlan.follow_up_plan ?? {};


    const getRecommendationPriority = (category)=>{


    const text =
        category.toLowerCase();



        if(
            text.includes("overall")
            ||
            text.includes("risk")
        )
        {

            return {
                label:"CRITICAL",
                color:"bg-red-100 text-red-700",
                icon:"🔴"
            };

        }



        if(
            text.includes("respiratory")
            ||
            text.includes("oxygen")
        )
        {

            return {
                label:"HIGH PRIORITY",
                color:"bg-orange-100 text-orange-700",
                icon:"🟠"
            };

        }



        if(
            text.includes("blood")
            ||
            text.includes("diabetes")
        )
        {

            return {
                label:"MONITORING",
                color:"bg-yellow-100 text-yellow-700",
                icon:"🟡"
            };

        }



        return {

            label:"ROUTINE",
            color:"bg-green-100 text-green-700",
            icon:"🟢"

        };


    };




    const priorityStyle = () => {


        if(priority === "CRITICAL")
        {

            return "bg-red-100 text-red-700 border-red-300";

        }


        if(priority === "HIGH")
        {

            return "bg-orange-100 text-orange-700 border-orange-300";

        }


        return "bg-green-100 text-green-700 border-green-300";


    };







    return (

        <div
        className="
        bg-white
        rounded-xl
        shadow
        p-6
        mt-6
        border
        "
        >



            <h2
            className="
            !text-gray-900
                text-xl
                font-bold
                mb-6
            "
            >

                AI Nurse Recommendation

            </h2>







            <div
                className="
                flex
                flex-col
                items-center
                justify-center
                mb-6
                "
                >


                    <span
                    className={`
                    px-5
                    py-2
                    rounded-full
                    font-bold
                    border
                    ${priorityStyle()}
                    `}
                    >

                        {priority}

                    </span>



                    <span
                    className="
                    mt-3
                    font-semibold
                    text-gray-700
                    text-center
                    "
                    >

                        Patient Care Priority

                    </span>


                </div>









            <div
            className="
            mb-6
            "
            >


                <h3
                className="
                font-bold
                text-gray-900
                mb-3
                "
                >

                    Recommended Nursing Actions

                </h3>




                {

                recommendations.length > 0 ?


                (

                    <ul
                    className="
                    space-y-4
                    text-center
                    "
                    >

                    {
                        recommendations.map(
                        (item,index)=>{


                        const priority =
                            getRecommendationPriority(
                                item.category
                            );


                        return (

                        <li
                        key={index}
                        className="
                        text-center
                        mb-5
                        "
                        >


                        <div
                        className="
                        flex
                        justify-center
                        mb-2
                        "
                        >


                        <span
                        className={`
                        px-3
                        py-1
                        rounded-full
                        text-xs
                        font-bold
                        ${priority.color}
                        `}
                        >

                        {priority.icon}

                        {" "}

                        {priority.label}

                        </span>


                        </div>




                        <p
                        className="
                        font-bold
                        text-gray-900
                        "
                        >

                        {item.category}

                        </p>



                        <p
                        className="
                        text-sm
                        text-gray-600
                        mt-1
                        "
                        >

                        {item.action}

                        </p>



                        </li>


                        );


                        })
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

                    No specific nursing recommendations available.

                    </p>

                )

                }


            </div>









            <div
            className="
            bg-gray-50
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

                    Follow Up Plan

                </h3>



                <p
                className="
                text-gray-700
                "
                >

                    Next Review:

                    {" "}

                    <strong>
                    {
                        followUp.next_review ??
                        "Not specified"
                    }
                    </strong>


                </p>


            </div>







            {
                guidance.length > 0 &&


                <div
                className="
                mt-5
                "
                >


                    <h3
                    className="
                    font-bold
                    text-gray-900
                    mb-2
                    "
                    >

                        Care Guidance

                    </h3>



                    <ul
                    className="
                    space-y-2
                    text-gray-700
                    "
                    >

                    {
                        guidance.map(
                            (item,index)=>(

                            <li
                            key={index}
                            >

                                • {
                                    typeof item === "string"
                                    ?
                                    item
                                    :
                                    item.action ??
                                    JSON.stringify(item)
                                }

                            </li>

                            )
                        )
                    }

                    </ul>


                </div>


            }





        </div>

    );


}