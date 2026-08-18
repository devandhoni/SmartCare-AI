export default function LatestVitalCard({ vital }) {


    if (!vital) {
        return (
            <div className="text-gray-500">
                No vital data available
            </div>
        );
    }


    return (

        <div
        className="
        bg-white
        rounded-xl
        shadow-lg
        p-6
        border
        border-gray-200
        "
        >


            <h2
            className="
            !text-gray-900
                text-xl
                font-bold
                mb-5
            "
            >
                Latest Vital Signs
            </h2>





            <div
            className="
            grid
            grid-cols-2
            gap-4
            "
            >





                {/* Blood Pressure */}

                <VitalBox
                    title="Blood Pressure"
                    value={`${vital.blood_pressure_systolic}/${vital.blood_pressure_diastolic}`}
                    status="CRITICAL"
                    statusColor="bg-red-500"
                    valueColor="text-red-600"
                />




                {/* Oxygen */}

                <VitalBox
                    title="Oxygen Level"
                    value={`${vital.oxygen_level}%`}
                    status="CRITICAL"
                    statusColor="bg-red-500"
                    valueColor="text-red-600"
                />





                {/* Glucose */}

                <VitalBox
                    title="Glucose"
                    value={vital.blood_glucose}
                    status="HIGH"
                    statusColor="bg-red-500"
                    valueColor="text-orange-600"
                />





                {/* Temperature */}

                <VitalBox
                    title="Temperature"
                    value={`${vital.temperature}°C`}
                    status="HIGH"
                    statusColor="bg-red-500"
                    valueColor="text-orange-600"
                />





                {/* Heart Rate */}

                <VitalBox
                    title="Heart Rate"
                    value={`${vital.heart_rate} bpm`}
                    status="NORMAL"
                    statusColor="bg-green-500"
                    valueColor="text-green-600"
                />



            </div>


        </div>

    );

}





function VitalBox({
    title,
    value,
    status,
    statusColor,
    valueColor
}) {


    return (

        <div
        className="
        bg-gray-50
        rounded-lg
        p-4
        border
        border-gray-200
        flex
        flex-col
        justify-between
        "
        >



            <p
            className="
            text-sm
            font-semibold
            text-gray-700
            "
            >
                {title}
            </p>





            <p
                className={`
                mt-2
                font-bold
                ${valueColor}
                whitespace-nowrap
                text-xl
                `}
                >
                    {value}
            </p>





            <span
            className={`
            mt-3
            w-fit
            px-3
            py-1
            rounded-full
            text-xs
            font-bold
            text-white
            ${statusColor}
            `}
            >

                {status}

            </span>




        </div>

    );

}