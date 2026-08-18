export default function AIRiskCard({

    score = 0,
    priority = "UNKNOWN",
    confidence = 0,
    message = "No AI prediction available"

}) {



    const getRiskStyle = () => {


        switch(priority){


            case "CRITICAL":

                return {

                    badge:
                    "bg-red-100 text-red-700",

                    number:
                    "text-red-600",

                    progress:
                    "bg-red-600",

                    border:
                    "border-red-300"

                };



            case "HIGH":

                return {

                    badge:
                    "bg-orange-100 text-orange-700",

                    number:
                    "text-orange-600",

                    progress:
                    "bg-orange-500",

                    border:
                    "border-orange-300"

                };



            case "MEDIUM":

                return {

                    badge:
                    "bg-yellow-100 text-yellow-700",

                    number:
                    "text-yellow-600",

                    progress:
                    "bg-yellow-500",

                    border:
                    "border-yellow-300"

                };



            default:


                return {

                    badge:
                    "bg-green-100 text-green-700",

                    number:
                    "text-green-600",

                    progress:
                    "bg-green-600",

                    border:
                    "border-green-300"

                };


        }


    };





    const style = getRiskStyle();







    return (


        <div className={`
            bg-white
            rounded-xl
            shadow-lg
            p-6
            border
            text-gray-900
            ${style.border}
        `}>


            <h2 className="
                !text-gray-900
                text-xl
                font-bold
            ">

                AI Risk Assessment

            </h2>






            {/* Risk Score */}


            <div className="
                mt-6
                text-center
            ">



                <div className={`
                    text-6xl
                    font-bold
                    ${style.number}
                `}>

                    {score}

                </div>



                <p className="
                    text-gray-600
                    mt-1
                ">

                    Risk Score / 100

                </p>






                <span className={`
                    inline-block
                    mt-4
                    px-5
                    py-2
                    rounded-full
                    font-bold
                    ${style.badge}
                `}>

                    {priority}

                </span>



            </div>








            {/* Risk Meter */}


            <div className="
                mt-6
            ">


                <div className="
                    flex
                    justify-between
                    text-sm
                    mb-2
                ">


                    <span className="text-gray-600">

                        Risk Level

                    </span>


                    <span className="font-bold">

                        {score}%

                    </span>


                </div>





                <div className="
                    w-full
                    bg-gray-200
                    rounded-full
                    h-3
                ">


                    <div

                    className={`
                        h-3
                        rounded-full
                        ${style.progress}
                    `}


                    style={{
                        width:`${score}%`
                    }}

                    >

                    </div>


                </div>


            </div>









            {/* AI Metrics */}


            <div className="
                mt-6
                grid
                grid-cols-2
                gap-4
            ">



                <div className="
                    bg-gray-100
                    rounded-lg
                    p-4
                ">


                    <p className="
                        text-sm
                        text-gray-600
                    ">

                        AI Confidence

                    </p>



                    <p className="
                        text-xl
                        font-bold
                        text-gray-900
                    ">

                        {confidence}%

                    </p>


                </div>







                <div className="
                    bg-gray-100
                    rounded-lg
                    p-4
                ">


                    <p className="
                        text-sm
                        text-gray-600
                    ">

                        Status

                    </p>



                    <p className="
                        text-xl
                        font-bold
                    ">

                        {
                            priority === "CRITICAL"
                            ?
                            "Urgent"
                            :
                            "Monitor"
                        }

                    </p>


                </div>



            </div>









            {/* Prediction */}


            <div className="
                mt-6
            ">


                <p className="
                    text-sm
                    font-semibold
                    text-gray-600
                    mb-2
                ">

                    AI Clinical Prediction

                </p>




                <div className="
                    bg-gray-100
                    rounded-lg
                    p-4
                ">


                    <p className="
                        text-sm
                        text-gray-900
                        leading-relaxed
                    ">

                        {message}

                    </p>


                </div>


            </div>









            {/* Critical Warning */}



            {
                priority === "CRITICAL" &&


                (

                    <div className="
                        mt-6
                        bg-red-50
                        border
                        border-red-200
                        rounded-lg
                        p-4
                        text-center
                    ">


                        <p className="
                            text-red-700
                            font-bold
                        ">

                            ⚠ Immediate Clinical Attention Required

                        </p>




                        <p className="
                            text-red-600
                            text-sm
                            mt-1
                        ">

                            Nurse assessment recommended based on AI prediction.

                        </p>



                    </div>

                )

            }





        </div>


    );


}