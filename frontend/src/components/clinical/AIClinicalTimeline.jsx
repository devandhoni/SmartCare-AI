import React, { useEffect, useState } from "react";
import axios from "axios";


const AIClinicalTimeline = ({
    residentId,
    onTimelineAction
}) => {


    const [
        timeline,
        setTimeline
    ] = useState([]);



    const [
        summary,
        setSummary
    ] = useState(null);



    const [
        loading,
        setLoading
    ] = useState(true);



    const [
        filter,
        setFilter
    ] = useState("ALL");



    const [
        expanded,
        setExpanded
    ] = useState({});







    useEffect(()=>{


        if(residentId)
        {

            fetchTimeline();

        }


    },[
        residentId
    ]);









    const fetchTimeline = async()=>{


        try
        {


            const token =
            localStorage.getItem("token");



            const response =
            await axios.get(

                `http://127.0.0.1:8000/api/residents/${residentId}/timeline`,

                {

                    headers:{

                        Authorization:
                        `Bearer ${token}`

                    }

                }

            );



            console.log(
                "AI Clinical Timeline:",
                response.data
            );



            setTimeline(
                response.data.timeline ?? []
            );



            setSummary(
                response.data.clinical_summary ?? null
            );



        }
        catch(error)
        {


            console.error(
                "Timeline Error:",
                error
            );


        }
        finally
        {

            setLoading(false);

        }


    };









    const toggleExpand = (
        index
    )=>{


        setExpanded({

            ...expanded,

            [index]:
            !expanded[index]

        });


    };









    const getEventStyle = (
        type
    )=>{


        switch(type)
        {


            case "AI_DECISION":

                return {

                    icon:"🤖",

                    border:
                    "border-red-500",

                    badge:
                    "bg-red-100 text-red-700"

                };




            case "AI_MONITORING":

                return {

                    icon:"🧠",

                    border:
                    "border-purple-500",

                    badge:
                    "bg-purple-100 text-purple-700"

                };




            case "NURSE_ACTION":

                return {

                    icon:"👩‍⚕️",

                    border:
                    "border-orange-500",

                    badge:
                    "bg-orange-100 text-orange-700"

                };




            case "MEDICATION":

                return {

                    icon:"💊",

                    border:
                    "border-green-500",

                    badge:
                    "bg-green-100 text-green-700"

                };




            case "VITAL_SIGN":

                return {

                    icon:"❤️",

                    border:
                    "border-blue-500",

                    badge:
                    "bg-blue-100 text-blue-700"

                };




            default:

                return {

                    icon:"📋",

                    border:
                    "border-gray-400",

                    badge:
                    "bg-gray-100 text-gray-700"

                };


        }


    };









    const getFilterCount = (
        type
    )=>{


        switch(type)
        {


            case "AI":

                return timeline.filter(
                    item =>
                    item.type==="AI_DECISION"
                    ||
                    item.type==="AI_MONITORING"
                ).length;




            case "NURSE":

                return timeline.filter(
                    item =>
                    item.type==="NURSE_ACTION"
                ).length;




            case "MEDICATION":

                return timeline.filter(
                    item =>
                    item.type==="MEDICATION"
                ).length;




            case "VITAL":

                return timeline.filter(
                    item =>
                    item.type==="VITAL_SIGN"
                ).length;




            default:

                return timeline.length;


        }


    };


        const filteredTimeline =
    timeline.filter(
        item=>{


            if(filter==="ALL")
                return true;



            if(filter==="AI")
            {

                return (
                    item.type==="AI_DECISION"
                    ||
                    item.type==="AI_MONITORING"
                );

            }



            if(filter==="NURSE")
            {

                return (
                    item.type==="NURSE_ACTION"
                );

            }



            if(filter==="MEDICATION")
            {

                return (
                    item.type==="MEDICATION"
                );

            }



            if(filter==="VITAL")
            {

                return (
                    item.type==="VITAL_SIGN"
                );

            }


            return true;


        }
    );









    const formatDate = (
        date
    )=>{


        if(!date)
            return "-";


        return new Date(date)
        .toLocaleString();


    };









    const handleTimelineAction = (
        item
    )=>{


        if(!onTimelineAction)
            return;




        /*
        AI Decision:
        Show overall vital trend
        */


        if(
            item.type === "AI_DECISION"
        )
        {

            onTimelineAction({

                section:"VITAL",

                period:"7days",

                metric:"all"

            });


        }






        /*
        AI Monitoring:
        Usually related to oxygen/blood pressure
        */


        else if(
            item.type === "AI_MONITORING"
        )
        {


            onTimelineAction({

                section:"VITAL",

                period:"24hours",

                metric:"oxygen"

            });


        }






        /*
        Vital sign event
        */


        else if(
            item.type === "VITAL_SIGN"
        )
        {


            onTimelineAction({

                section:"VITAL",

                period:"24hours",

                metric:"all"

            });


        }



    };









    if(loading)
    {

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

                Loading AI Clinical Timeline...

            </div>

        );

    }









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
            text-center
            text-gray-900
            mb-6
            "
            >

                AI Clinical Timeline

            </h2>









            {
                summary &&

                <div
                className="
                grid
                grid-cols-1
                md:grid-cols-4
                gap-4
                mb-6
                "
                >



                    <div className="bg-blue-50 rounded-lg p-4 text-center">

                        <p className="text-sm">
                            Total Events
                        </p>

                        <h3 className="text-2xl font-bold">
                            {summary.total_events}
                        </h3>

                    </div>




                    <div className="bg-red-50 rounded-lg p-4 text-center">

                        <p className="text-sm">
                            Critical Events
                        </p>

                        <h3 className="text-2xl font-bold text-red-600">
                            {summary.critical_events}
                        </h3>

                    </div>




                    <div className="bg-yellow-50 rounded-lg p-4 text-center">

                        <p className="text-sm">
                            Latest Condition
                        </p>

                        <h3 className="font-bold">
                            {summary.latest_condition}
                        </h3>

                    </div>




                    <div className="bg-red-100 rounded-lg p-4 text-center">

                        <p className="text-sm">
                            Risk Level
                        </p>

                        <h3 className="font-bold text-red-600">
                            {summary.risk_level}
                        </h3>

                    </div>



                </div>

            }









            {/* FILTER BUTTONS */}


            <div
            className="
            flex
            justify-center
            flex-wrap
            gap-3
            mb-6
            "
            >


            {
                [
                    {
                        name:"ALL",
                        label:"All"
                    },
                    {
                        name:"AI",
                        label:"AI"
                    },
                    {
                        name:"NURSE",
                        label:"Nurse"
                    },
                    {
                        name:"MEDICATION",
                        label:"Medication"
                    },
                    {
                        name:"VITAL",
                        label:"Vital"
                    }

                ]
                .map(
                    option=>(


                    <button

                    key={option.name}

                    onClick={()=>
                        setFilter(
                            option.name
                        )
                    }

                    className={`
                    px-4
                    py-2
                    rounded-full
                    text-sm
                    font-semibold

                    ${
                        filter===option.name

                        ?

                        "bg-blue-600 text-white"

                        :

                        "bg-gray-100 text-gray-700"

                    }

                    `}

                    >

                        {option.label}

                        {" "}

                        (
                        {
                            getFilterCount(
                                option.name
                            )
                        }
                        )

                    </button>


                    )

                )

            }


            </div>









            {/* TIMELINE CONTAINER */}


            <div
            className="
            max-h-[500px]
            overflow-y-auto
            pr-3
            space-y-5
            "
            >



            {
                filteredTimeline.map(

                    (
                        item,
                        index
                    )=>{


                    const style =
                    getEventStyle(
                        item.type
                    );



                    return (

                    <div

                    key={index}

                    className={`
                    border-l-4
                    ${style.border}
                    pl-5
                    pb-5
                    `}

                    >





                        <div
                        className="
                        flex
                        gap-3
                        "
                        >



                            <div
                            className="
                            text-3xl
                            "
                            >

                                {
                                    style.icon
                                }

                            </div>






                            <div
                            className="
                            flex-1
                            "
                            >





                                <div
                                className="
                                flex
                                justify-between
                                gap-3
                                "
                                >



                                    <h3
                                    className="
                                    font-bold
                                    text-gray-900
                                    "
                                    >

                                        {
                                            item.title
                                        }

                                    </h3>




                                    <span
                                    className="
                                    text-xs
                                    text-gray-500
                                    "
                                    >

                                        {
                                            formatDate(
                                                item.date
                                            )
                                        }

                                    </span>


                                </div>







                                <span
                                className={`
                                inline-block
                                mt-2
                                px-3
                                py-1
                                rounded-full
                                text-xs
                                font-bold
                                ${style.badge}
                                `}
                                >

                                    {
                                        item.type
                                    }

                                </span>







                                <p
                                className="
                                text-sm
                                text-gray-600
                                mt-3
                                "
                                >

                                    {
                                        item.clinical_summary
                                    }

                                </p>








                                <div
                                className="
                                flex
                                gap-3
                                mt-3
                                flex-wrap
                                "
                                >



                                    {
                                        (
                                            item.type==="AI_DECISION"
                                            ||
                                            item.type==="AI_MONITORING"
                                            ||
                                            item.type==="VITAL_SIGN"
                                        )

                                        &&


                                        <button

                                        onClick={()=>
                                            handleTimelineAction(
                                                item
                                            )
                                        }


                                        className="
                                        bg-blue-600
                                        text-white
                                        px-4
                                        py-2
                                        rounded-lg
                                        text-sm
                                        font-semibold
                                        "

                                        >

                                            View Vital Trend

                                        </button>

                                    }






                                    <button

                                    onClick={()=>
                                        toggleExpand(index)
                                    }


                                    className="
                                    text-blue-600
                                    text-sm
                                    font-semibold
                                    "

                                    >

                                    {
                                        expanded[index]

                                        ?

                                        "Hide Details ▲"

                                        :

                                        "View Details ▼"

                                    }

                                    </button>



                                </div>








                                {
                                    expanded[index]

                                    &&


                                    <div
                                    className="
                                    mt-4
                                    bg-gray-50
                                    rounded-lg
                                    p-4
                                    text-sm
                                    "
                                    >



                                        <p>

                                        <strong>
                                        Category:
                                        </strong>

                                        {" "}

                                        {
                                            item.category ?? "-"
                                        }

                                        </p>





                                        <p>

                                        <strong>
                                        Source:
                                        </strong>

                                        {" "}

                                        {
                                            item.source ?? "-"
                                        }

                                        </p>







                                        {
                                            item.data
                                            &&
                                            item.data.length > 0

                                            &&


                                            <pre
                                            className="
                                            mt-3
                                            text-xs
                                            whitespace-pre-wrap
                                            "
                                            >

                                                {
                                                    JSON.stringify(
                                                        item.data,
                                                        null,
                                                        2
                                                    )
                                                }

                                            </pre>

                                        }



                                    </div>

                                }





                            </div>




                        </div>





                    </div>


                    );


                    }

                )

            }



            </div>







        </div>

    );


};


export default AIClinicalTimeline;