import React from "react";

import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    ReferenceLine
} from "recharts";



export default function VitalTrendChart({
    vitals = []
}) {



    if(!vitals || vitals.length === 0)
    {

        return (

            <div className="
                bg-white
                rounded-xl
                shadow
                p-6
                mt-6
            ">

                <h2 className="
                    text-lg
                    font-bold
                    text-gray-800
                ">
                    Vital Trend Analysis
                </h2>


                <p className="
                    text-gray-500
                    mt-3
                ">
                    No historical vital data available.
                </p>


            </div>

        );

    }



    const ChartCard = ({
        title,
        dataKey,
        color,
        unit,
        criticalLine,
        criticalLabel
    }) => {


        return (

            <div className="
                bg-gray-50
                rounded-xl
                p-5
                border
            ">


                <h3 className="
                    font-bold
                    text-gray-800
                    mb-3
                ">

                    {title}

                </h3>



                <ResponsiveContainer
                    width="100%"
                    height={250}
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



                        {
                            criticalLine &&
                            (

                                <ReferenceLine

                                    y={criticalLine}

                                    label={criticalLabel}

                                />

                            )
                        }



                        <Line

                            type="monotone"

                            dataKey={dataKey}

                            stroke={color}

                            strokeWidth={3}

                            dot

                        />



                    </LineChart>


                </ResponsiveContainer>


            </div>

        );

    };






    return (

        <div className="
            bg-white
            rounded-xl
            shadow
            p-6
            mt-6
        ">


            <h2 className="
                !text-gray-900
                text-xl
                font-bold
                mb-6
            ">

                Vital Trend Intelligence

            </h2>





            <div className="
                grid
                grid-cols-1
                lg:grid-cols-2
                gap-6
            ">


                <ChartCard

                    title="Blood Pressure (Systolic)"

                    dataKey="blood_pressure_systolic"

                    color="#dc2626"

                    unit="mmHg"

                    criticalLine={180}

                    criticalLabel="Critical BP"

                />





                <ChartCard

                    title="Oxygen Saturation"

                    dataKey="oxygen_level"

                    color="#2563eb"

                    unit="%"

                    criticalLine={90}

                    criticalLabel="Low Oxygen"

                />





                <ChartCard

                    title="Blood Glucose"

                    dataKey="blood_glucose"

                    color="#f59e0b"

                    unit="mmol/L"

                    criticalLine={13}

                    criticalLabel="High Glucose"

                />





                <ChartCard

                    title="Body Temperature"

                    dataKey="temperature"

                    color="#7c3aed"

                    unit="°C"

                    criticalLine={39}

                    criticalLabel="High Temperature"

                />


            </div>


        </div>

    );

}