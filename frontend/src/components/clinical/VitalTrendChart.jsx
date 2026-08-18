import React from "react";

import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer
} from "recharts";



export default function VitalTrendChart({
    vitals = []
}) {



    if(!vitals || vitals.length === 0)
    {

        return (

            <div
            className="
            bg-white
            rounded-xl
            shadow
            p-6
            "
            >

                <h2
                className="
                text-lg
                font-bold
                "
                >
                    Vital Trend Analysis
                </h2>


                <p
                className="
                text-gray-500
                mt-3
                "
                >
                    No historical vital data available.
                </p>


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
            text-lg
            font-bold
            text-gray-800
            mb-5
            "
            >

                Vital Trend Analysis

            </h2>





            <ResponsiveContainer
                width="100%"
                height={350}
            >


                <LineChart
                    data={vitals}
                >


                    <CartesianGrid
                    strokeDasharray="3 3"
                    />



                    <XAxis
                        dataKey="date"
                    />



                    <YAxis />



                    <Tooltip />



                    <Legend />





                    <Line
                        type="monotone"
                        dataKey="blood_pressure_systolic"
                        name="Systolic BP"
                        strokeWidth={3}
                    />



                    <Line
                        type="monotone"
                        dataKey="oxygen_level"
                        name="Oxygen Level"
                        strokeWidth={3}
                    />



                    <Line
                        type="monotone"
                        dataKey="blood_glucose"
                        name="Glucose"
                        strokeWidth={3}
                    />



                    <Line
                        type="monotone"
                        dataKey="temperature"
                        name="Temperature"
                        strokeWidth={3}
                    />



                </LineChart>


            </ResponsiveContainer>





        </div>


    );


}