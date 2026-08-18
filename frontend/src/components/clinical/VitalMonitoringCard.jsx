import React, { useEffect, useState } from "react";


import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer
} from "recharts";


import { getVitalTrend } from "../../api/vitalTrendApi";








export default function VitalMonitoringCard({
    residentId
}) {



    const [period,setPeriod] = useState("24hours");


    const [metric,setMetric] = useState("all");


    const [chartData,setChartData] = useState([]);


    const [loading,setLoading] = useState(true);









    useEffect(()=>{


        if(residentId)
        {
            loadVitalTrend();
        }


    },[
        residentId,
        period,
        metric
    ]);









    const loadVitalTrend = async()=>{


        try
        {


            setLoading(true);



            const result =
                await getVitalTrend(
                    residentId,
                    period,
                    metric
                );



            console.log(
                "Vital Trend Result:",
                result
            );



            setChartData(
                result.data ?? []
            );



        }
        catch(error)
        {


            console.error(
                "Vital Trend Error:",
                error
            );


        }
        finally
        {

            setLoading(false);

        }


    };









    const latestReading =
        chartData.length > 0
        ?
        chartData[
            chartData.length - 1
        ]
        :
        null;









    const getRiskStatus = (
        type,
        value
    )=>{


        if(!value)
            return "normal";




        if(type==="temperature")
        {

            if(value >= 39)
                return "critical";


            if(value >= 38)
                return "warning";

        }






        if(type==="oxygen")
        {

            if(value < 90)
                return "critical";


            if(value < 94)
                return "warning";

        }






        if(type==="glucose")
        {

            if(value >= 13)
                return "critical";


            if(value >= 10)
                return "warning";

        }






        if(type==="bp")
        {


            const systolic =
                Number(
                    value?.split("/")?.[0]
                );



            if(systolic >= 180)
                return "critical";


            if(systolic >= 140)
                return "warning";


        }





        return "normal";


    };









    const statusStyle = (
        status
    )=>{


        if(status==="critical")
        {

            return "bg-red-100 text-red-700";

        }



        if(status==="warning")
        {

            return "bg-orange-100 text-orange-700";

        }



        return "bg-green-100 text-green-700";


    };









    const VitalBox = ({
        title,
        value,
        type,
        unit=""
    })=>{


        const status =
            getRiskStatus(
                type,
                value
            );



        return (

            <div
            className="
            bg-gray-50
            rounded-xl
            p-4
            border
            "
            >


                <p
                className="
                text-sm
                font-semibold
                text-gray-600
                "
                >

                    {title}

                </p>





                <p
                className="
                text-2xl
                font-bold
                text-gray-800
                mt-2
                "
                >

                    {value ?? "-"}

                    {unit}


                </p>





                <span
                className={`
                inline-block
                mt-2
                px-3
                py-1
                rounded-full
                text-xs
                font-bold
                ${statusStyle(status)}
                `}
                >

                    {status.toUpperCase()}

                </span>



            </div>

        );


    };









    const getXAxisKey = ()=>{


        if(
            period==="today"
            ||
            period==="24hours"
        )
        {

            return "time";

        }


        return "date";


    };









    const TrendChart = ({
        title,
        dataKey,
        unit=""
    })=>{


        return (

            <div
            className="
            bg-gray-50
            rounded-xl
            p-5
            border
            "
            >


                <h3
                className="
                text-lg
                font-bold
                text-gray-800
                mb-4
                "
                >

                    {title}

                </h3>





                <ResponsiveContainer
                    width="100%"
                    height={280}
                >


                    <LineChart
                        data={chartData}
                    >


                        <CartesianGrid
                        strokeDasharray="3 3"
                        />



                        <XAxis

                        dataKey={
                            getXAxisKey()
                        }

                        />



                        <YAxis />



                        <Tooltip

                        formatter={
                            (value)=>
                            [
                                `${value}${unit}`,
                                title
                            ]
                        }

                        />



                        <Line

                        type="monotone"

                        dataKey={dataKey}

                        strokeWidth={3}

                        dot={true}

                        />


                    </LineChart>


                </ResponsiveContainer>


            </div>


        );


    };

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

                Vital Trend Intelligence

            </h2>









            {/* Latest Vital Summary */}

            <div
            className="
            mb-8
            "
            >


                <h3
                className="
                text-lg
                font-bold
                text-gray-700
                mb-4
                "
                >

                    Latest Vital Summary

                </h3>





                {
                    latestReading ?


                    (

                    <div
                    className="
                    grid
                    grid-cols-1
                    md:grid-cols-2
                    lg:grid-cols-5
                    gap-4
                    "
                    >


                        <VitalBox

                            title="Temperature"

                            value={
                                latestReading.temperature
                            }

                            unit=" °C"

                            type="temperature"

                        />




                        <VitalBox

                            title="Oxygen Level"

                            value={
                                latestReading.oxygen_level
                            }

                            unit=" %"

                            type="oxygen"

                        />





                        <VitalBox

                            title="Blood Glucose"

                            value={
                                latestReading.blood_glucose
                            }

                            type="glucose"

                        />





                        <VitalBox

                            title="Blood Pressure"

                            value={
                                latestReading.blood_pressure
                            }

                            type="bp"

                        />





                        <VitalBox

                            title="Heart Rate"

                            value={
                                latestReading.heart_rate
                            }

                            unit=" bpm"

                            type="heart_rate"

                        />



                    </div>

                    )


                    :


                    (

                    <div
                    className="
                    bg-gray-50
                    rounded-lg
                    p-5
                    text-gray-500
                    "
                    >

                        No latest vital reading available.

                    </div>

                    )

                }



            </div>












            {/* Filters */}


            <div
            className="
            grid
            grid-cols-1
            md:grid-cols-2
            gap-5
            mb-8
            "
            >





                <div>


                    <label
                    className="
                    block
                    font-semibold
                    text-gray-700
                    mb-2
                    "
                    >

                        Monitoring Period

                    </label>




                    <select

                    value={period}

                    onChange={
                        (e)=>
                        setPeriod(
                            e.target.value
                        )
                    }


                    className="
                    w-full
                    border
                    rounded-lg
                    p-2
                    text-gray-700
                    "

                    >


                        <option value="24hours">
                            Last 24 Hours
                        </option>


                        <option value="today">
                            Today
                        </option>


                        <option value="7days">
                            Last 7 Days
                        </option>


                        <option value="30days">
                            Last 30 Days
                        </option>



                    </select>


                </div>









                <div>


                    <label
                    className="
                    block
                    font-semibold
                    text-gray-700
                    mb-2
                    "
                    >

                        Vital Metric

                    </label>





                    <select

                    value={metric}

                    onChange={
                        (e)=>
                        setMetric(
                            e.target.value
                        )
                    }


                    className="
                    w-full
                    border
                    rounded-lg
                    p-2
                    text-gray-700
                    "

                    >


                        <option value="all">
                            All Vitals
                        </option>


                        <option value="temperature">
                            Temperature
                        </option>



                        <option value="oxygen">
                            Oxygen Level
                        </option>



                        <option value="glucose">
                            Blood Glucose
                        </option>



                        <option value="heart_rate">
                            Heart Rate
                        </option>



                    </select>



                </div>




            </div>












            {/* Chart Area */}


            {
                loading ?



                (

                    <div
                    className="
                    text-center
                    py-10
                    text-gray-500
                    "
                    >

                        Loading vital trend data...

                    </div>


                )




                :



                chartData.length === 0 ?



                (

                    <div
                    className="
                    bg-gray-50
                    rounded-xl
                    p-8
                    text-center
                    text-gray-500
                    "
                    >

                        <p
                        className="
                        font-semibold
                        "
                        >

                            No vital readings available
                            for selected period.

                        </p>



                        <p
                        className="
                        text-sm
                        mt-2
                        "
                        >

                            Please ensure vital signs
                            are recorded for this period.

                        </p>



                    </div>

                )




                :



                (

                    <div
                    className="
                    grid
                    grid-cols-1
                    gap-6
                    "
                    >



                        {
                            (
                            metric==="all"
                            ||
                            metric==="temperature"
                            )
                            &&


                            <TrendChart

                                title="Temperature Trend (°C)"

                                dataKey="temperature"

                                unit=" °C"

                            />

                        }







                        {
                            (
                            metric==="all"
                            ||
                            metric==="oxygen"
                            )
                            &&


                            <TrendChart

                                title="Oxygen Saturation Trend (%)"

                                dataKey="oxygen_level"

                                unit=" %"

                            />

                        }








                        {
                            (
                            metric==="all"
                            ||
                            metric==="glucose"
                            )
                            &&


                            <TrendChart

                                title="Blood Glucose Trend"

                                dataKey="blood_glucose"

                                unit=""

                            />

                        }








                        {
                            (
                            metric==="all"
                            ||
                            metric==="heart_rate"
                            )
                            &&


                            <TrendChart

                                title="Heart Rate Trend (bpm)"

                                dataKey="heart_rate"

                                unit=" bpm"

                            />

                        }


                        {
                                metric==="all"
                                &&


                                <div
                                className="
                                bg-gray-50
                                rounded-xl
                                p-5
                                border
                                "
                                >


                                    <h3
                                    className="
                                    text-lg
                                    font-bold
                                    text-gray-800
                                    mb-4
                                    "
                                    >

                                        Blood Pressure Trend (mmHg)

                                    </h3>





                                    <ResponsiveContainer
                                        width="100%"
                                        height={280}
                                    >


                                        <LineChart
                                            data={chartData}
                                        >


                                            <CartesianGrid
                                            strokeDasharray="3 3"
                                            />



                                            <XAxis

                                                dataKey={
                                                    getXAxisKey()
                                                }

                                            />



                                            <YAxis />



                                            <Tooltip />





                                            <Line

                                                type="monotone"

                                                dataKey="blood_pressure_systolic"

                                                name="Systolic"

                                                strokeWidth={3}

                                                dot={true}

                                            />





                                            <Line

                                                type="monotone"

                                                dataKey="blood_pressure_diastolic"

                                                name="Diastolic"

                                                strokeWidth={3}

                                                dot={true}

                                            />



                                        </LineChart>


                                    </ResponsiveContainer>



                                </div>


                            }





                    </div>

                )

            }







        </div>

    );


}