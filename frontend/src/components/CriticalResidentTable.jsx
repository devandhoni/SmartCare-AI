import React from "react";
import { useNavigate } from "react-router-dom";


export default function CriticalResidentTable({

    residents=[]

}) {


    const navigate = useNavigate();



    const rankedResidents =
        [...residents]
        .sort(
            (a,b)=>
            (b.risk_score ?? 0)
            -
            (a.risk_score ?? 0)
        );





    return (

        <div

        className="
        bg-white
        rounded-xl
        shadow
        p-6
        "

        >



            <div

            className="
            flex
            justify-between
            items-center
            mb-6
            "

            >


                <h2

                className="
                text-xl
                font-bold
                text-slate-800
                "

                >

                    🔥 Critical Resident Ranking

                </h2>



                <span

                className="
                bg-red-100
                text-red-700
                px-3
                py-1
                rounded-full
                text-sm
                font-bold
                "

                >

                    {residents.length}
                    Critical

                </span>



            </div>






            {
                rankedResidents.length === 0

                ?

                (

                    <p className="
                    text-gray-500
                    ">

                        No critical residents.

                    </p>

                )


                :


                (

                <div className="
                space-y-4
                ">


                {

                rankedResidents.map(

                    (resident,index)=>(


                    <div

                    key={
                        resident.resident_id
                    }

                    className="
                    border
                    rounded-xl
                    p-5
                    flex
                    justify-between
                    items-center
                    hover:shadow-md
                    transition
                    "

                    >





                        <div

                        className="
                        flex
                        gap-4
                        "

                        >



                            <div

                            className="
                            w-10
                            h-10
                            rounded-full
                            bg-red-600
                            text-white
                            flex
                            items-center
                            justify-center
                            font-bold
                            "

                            >

                                {index+1}

                            </div>






                            <div>


                                <h3

                                className="
                                font-bold
                                text-gray-800
                                text-lg
                                "

                                >

                                    {
                                    resident.resident_name
                                    }

                                </h3>





                                <div className="
                                flex
                                gap-2
                                mt-2
                                ">



                                    <span

                                    className="
                                    bg-red-100
                                    text-red-700
                                    px-3
                                    py-1
                                    rounded-full
                                    text-xs
                                    font-bold
                                    "

                                    >

                                        {
                                        resident.priority
                                        }

                                    </span>





                                    <span

                                    className="
                                    bg-orange-100
                                    text-orange-700
                                    px-3
                                    py-1
                                    rounded-full
                                    text-xs
                                    font-bold
                                    "

                                    >

                                        Risk Score:
                                        {" "}
                                        {
                                        resident.risk_score ?? "-"
                                        }

                                    </span>



                                </div>






                                <p

                                className="
                                text-sm
                                text-gray-600
                                mt-3
                                "

                                >

                                    {
                                    resident.recommendation
                                    }

                                </p>



                            </div>


                        </div>








                        <button


                        onClick={()=>


                            navigate(

                            `/residents/${resident.resident_id}/clinical-dashboard`

                            )

                        }


                        className="
                        bg-blue-600
                        text-white
                        px-4
                        py-2
                        rounded-lg
                        font-semibold
                        hover:bg-blue-700
                        "

                        >

                            View Clinical

                        </button>





                    </div>


                    )

                )


                }


                </div>

                )

            }



        </div>


    );


}