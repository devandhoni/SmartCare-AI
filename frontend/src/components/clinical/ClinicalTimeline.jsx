import { useEffect, useState } from "react";

import {
    getClinicalTimeline
}
from "../../api/clinicalTimelineApi";


import TimelineCard
from "./TimelineCard";



export default function ClinicalTimeline({
    residentId
}) {


    const [timeline,setTimeline] =
        useState([]);


    const [loading,setLoading] =
        useState(true);



    useEffect(()=>{


        loadTimeline();


    },[residentId]);





    const loadTimeline = async()=>{


        try{


            const data =
                await getClinicalTimeline(
                    residentId
                );


            setTimeline(
                data.timeline
            );


        }
        catch(error)
        {

            console.error(
                error
            );

        }
        finally
        {

            setLoading(false);

        }


    };





    if(loading)
    {

        return (
            <div>
                Loading clinical timeline...
            </div>
        );

    }





    return (

        <div className="space-y-4">


            {
                timeline.map(
                    (event,index)=>(


                    <TimelineCard

                        key={index}

                        event={event}

                    />


                    )
                )
            }


        </div>

    );


}